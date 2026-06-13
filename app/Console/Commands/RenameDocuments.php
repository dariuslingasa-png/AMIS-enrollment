<?php

namespace App\Console\Commands;

use App\Models\EnrollmentApplicant;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\File;

class RenameDocuments extends Command
{
    protected $signature = 'enrollment:rename-documents {--dry-run : Run in simulation mode without making actual changes}';
    protected $description = 'Laravel one-time script to RENAME old uploaded files based on database records into the requested custom format';

    public function handle(): void
    {
        $dryRun = $this->option('dry-run');

        if ($dryRun) {
            $this->info("=== DRY RUN MODE: Simulation only. No files or database records will be modified ===\n");
        } else {
            $this->info("=== REAL RUN MODE: Files will be renamed/moved and database paths updated ===\n");
        }

        $applicants = EnrollmentApplicant::with('payment')->get();
        
        $documentFields = [
            'photo_2x2_url' => '2x2',
            'birth_cert_url' => 'birth_certificate',
            'report_card_url' => 'report_card',
            'marriage_contract_url' => 'marriage_contract',
            'medical_record_url' => 'medical_record',
            'affidavit_url' => 'affidavit',
        ];

        $logs = [];
        $migratedCount = 0;
        $missingCount = 0;
        $skippedCount = 0;

        foreach ($applicants as $applicant) {
            $lastName = strtoupper(preg_replace('/[^a-zA-Z0-9]+/', '', trim($applicant->last_name ?? '')));
            $firstName = strtoupper(preg_replace('/[^a-zA-Z0-9]+/', '', trim($applicant->first_name ?? '')));
            $applicantId = $applicant->id;

            if (empty($lastName) || empty($firstName)) {
                continue;
            }

            // Target Folder Name: {applicant_id}_{last_name}_{first_name}
            $folderName = "{$applicantId}_{$lastName}_{$firstName}";

            // Process typical enrollment documents
            foreach ($documentFields as $column => $docType) {
                $oldPath = $applicant->{$column};
                if (empty($oldPath)) {
                    continue;
                }

                $result = $this->processFile($applicant, $column, $docType, $oldPath, $folderName, $lastName, $firstName, $applicantId, $dryRun);
                
                $logs[] = $result['log'];
                if ($result['status'] === 'Migrated' || $result['status'] === 'Updated DB Only') {
                    $migratedCount++;
                } elseif ($result['status'] === 'Missing') {
                    $missingCount++;
                } else {
                    $skippedCount++;
                }
            }

            // Process payment proof receipt
            if ($applicant->payment && !empty($applicant->payment->receipt_url)) {
                $oldPaths = $applicant->payment->receipt_urls;
                $newPaths = [];
                foreach ($oldPaths as $index => $oldPath) {
                    $result = $this->processPaymentReceipt($applicant, $oldPath, $folderName, $lastName, $firstName, $applicantId, $dryRun, count($oldPaths) > 1 ? $index : null);
                    
                    $logs[] = $result['log'];
                    if ($result['status'] === 'Migrated' || $result['status'] === 'Updated DB Only') {
                        $migratedCount++;
                        $newPaths[] = $result['log']['new_path'];
                    } elseif ($result['status'] === 'Missing') {
                        $missingCount++;
                        $newPaths[] = $oldPath;
                    } else {
                        $skippedCount++;
                        $newPaths[] = $result['log']['new_path'];
                    }
                }
                
                if (!$dryRun && !empty($newPaths)) {
                    $finalVal = count($newPaths) === 1 ? $newPaths[0] : json_encode($newPaths);
                    $applicant->payment->update(['receipt_url' => $finalVal]);
                }
            }
        }

        // Generate CSV Migration Log
        $this->generateCsvLog($logs);

        // Display beautiful summary
        $this->info("\n=== Migration & Standardization Summary ===");
        $this->info("Successfully Standardized/Migrated: {$migratedCount}");
        $this->info("Missing from Disk (Logged):         {$missingCount}");
        $this->info("Already in New Format (Skipped):    {$skippedCount}");
        $this->info("CSV log generated at:               storage/app/public/migration_log.csv");
        $this->info("===========================================");
    }

    private function processFile($applicant, $column, $docType, $oldPath, $folderName, $lastName, $firstName, $applicantId, $dryRun): array
    {
        $extension = strtolower(pathinfo($oldPath, PATHINFO_EXTENSION) ?: 'jpg');
        
        // Target format: {document_type}_{applicant_id}_{last_name}_{first_name}.{extension}
        $targetDirectory = "documents/{$folderName}/{$docType}";
        $newFilename = "{$docType}_{$applicantId}_{$lastName}_{$firstName}.{$extension}";
        $newPath = "{$targetDirectory}/{$newFilename}";

        // If already in the correct format, skip
        if ($oldPath === $newPath) {
            return [
                'status' => 'Skipped',
                'log' => [
                    'applicant_name' => $applicant->full_name,
                    'document_type' => $docType,
                    'old_filename' => basename($oldPath),
                    'new_filename' => $newFilename,
                    'old_path' => $oldPath,
                    'new_path' => $newPath,
                    'status' => 'Already in New Format',
                ]
            ];
        }

        // Find file physically (including robust fallbacks for cPanel paths)
        $physicalPath = $this->locatePhysicalFile($oldPath);

        if (!$physicalPath) {
            // Check if it already exists in the new path (already migrated in a previous run)
            if (Storage::disk('public')->exists($newPath)) {
                $this->warn("⚠️ File already migrated, updating DB record for Child #{$applicantId} ({$applicant->full_name}) -> {$newPath}");
                if (!$dryRun) {
                    $applicant->update([$column => $newPath]);
                }
                return [
                    'status' => 'Updated DB Only',
                    'log' => [
                        'applicant_name' => $applicant->full_name,
                        'document_type' => $docType,
                        'old_filename' => basename($oldPath),
                        'new_filename' => $newFilename,
                        'old_path' => $oldPath,
                        'new_path' => $newPath,
                        'status' => 'Updated DB Only (File Already Migrated)',
                    ]
                ];
            }

            $this->error("❌ File NOT found for Child #{$applicantId} ({$applicant->full_name}) -> Path: {$oldPath}");
            return [
                'status' => 'Missing',
                'log' => [
                    'applicant_name' => $applicant->full_name,
                    'document_type' => $docType,
                    'old_filename' => basename($oldPath),
                    'new_filename' => 'N/A',
                    'old_path' => $oldPath,
                    'new_path' => 'N/A',
                    'status' => 'File Missing on Disk',
                ]
            ];
        }

        // Handle file name collisions (Counter _1, _2, _3)
        $finalNewPath = $newPath;
        $finalFilename = $newFilename;
        $counter = 1;
        while (Storage::disk('public')->exists($finalNewPath)) {
            $finalFilename = "{$docType}_{$applicantId}_{$lastName}_{$firstName}_{$counter}.{$extension}";
            $finalNewPath = "{$targetDirectory}/{$finalFilename}";
            $counter++;
        }

        $this->line("👉 Migrating: [Child #{$applicantId}] {$applicant->full_name} ({$docType})");
        $this->line("   Old Path: {$oldPath}");
        $this->line("   New Path: {$finalNewPath}");

        if (!$dryRun) {
            // Ensure target directory exists
            Storage::disk('public')->makeDirectory($targetDirectory);

            // Copy file safely (preserving original)
            if ($physicalPath['source'] === 'public_disk') {
                Storage::disk('public')->copy($oldPath, $finalNewPath);
            } else {
                // If found in fallback directory (e.g. root or repository), copy it to target
                Storage::disk('public')->put($finalNewPath, file_get_contents($physicalPath['path']));
            }

            // Update database record
            $applicant->update([$column => $finalNewPath]);
        }

        return [
            'status' => 'Migrated',
            'log' => [
                'applicant_name' => $applicant->full_name,
                'document_type' => $docType,
                'old_filename' => basename($oldPath),
                'new_filename' => $finalFilename,
                'old_path' => $oldPath,
                'new_path' => $finalNewPath,
                'status' => 'Success',
            ]
        ];
    }

    private function processPaymentReceipt($applicant, $oldPath, $folderName, $lastName, $firstName, $applicantId, $dryRun, $index = null): array
    {
        $extension = strtolower(pathinfo($oldPath, PATHINFO_EXTENSION) ?: 'jpg');
        $docType = 'payment_proof';

        $targetDirectory = "documents/{$folderName}/{$docType}";
        $suffix = $index !== null ? "_{$index}" : "";
        $newFilename = "{$docType}_{$applicantId}_{$lastName}_{$firstName}{$suffix}.{$extension}";
        $newPath = "{$targetDirectory}/{$newFilename}";

        if ($oldPath === $newPath) {
            return [
                'status' => 'Skipped',
                'log' => [
                    'applicant_name' => $applicant->full_name,
                    'document_type' => $docType,
                    'old_filename' => basename($oldPath),
                    'new_filename' => $newFilename,
                    'old_path' => $oldPath,
                    'new_path' => $newPath,
                    'status' => 'Already in New Format',
                ]
            ];
        }

        $physicalPath = $this->locatePhysicalFile($oldPath);

        if (!$physicalPath) {
            if (Storage::disk('public')->exists($newPath)) {
                $this->warn("⚠️ Payment receipt already migrated, updating DB record for Child #{$applicantId} ({$applicant->full_name}) -> {$newPath}");
                return [
                    'status' => 'Updated DB Only',
                    'log' => [
                        'applicant_name' => $applicant->full_name,
                        'document_type' => $docType,
                        'old_filename' => basename($oldPath),
                        'new_filename' => $newFilename,
                        'old_path' => $oldPath,
                        'new_path' => $newPath,
                        'status' => 'Updated DB Only (File Already Migrated)',
                    ]
                ];
            }

            $this->error("❌ Payment receipt NOT found for Child #{$applicantId} ({$applicant->full_name}) -> Path: {$oldPath}");
            return [
                'status' => 'Missing',
                'log' => [
                    'applicant_name' => $applicant->full_name,
                    'document_type' => $docType,
                    'old_filename' => basename($oldPath),
                    'new_filename' => 'N/A',
                    'old_path' => $oldPath,
                    'new_path' => 'N/A',
                    'status' => 'File Missing on Disk',
                ]
            ];
        }

        $finalNewPath = $newPath;
        $finalFilename = $newFilename;
        $counter = 1;
        while (Storage::disk('public')->exists($finalNewPath)) {
            $finalFilename = "{$docType}_{$applicantId}_{$lastName}_{$firstName}_{$counter}.{$extension}";
            $finalNewPath = "{$targetDirectory}/{$finalFilename}";
            $counter++;
        }

        $this->line("👉 Migrating: [Child #{$applicantId}] {$applicant->full_name} ({$docType})");
        $this->line("   Old Path: {$oldPath}");
        $this->line("   New Path: {$finalNewPath}");

        if (!$dryRun) {
            Storage::disk('public')->makeDirectory($targetDirectory);

            if ($physicalPath['source'] === 'public_disk') {
                Storage::disk('public')->copy($oldPath, $finalNewPath);
            } else {
                Storage::disk('public')->put($finalNewPath, file_get_contents($physicalPath['path']));
            }
        }

        return [
            'status' => 'Migrated',
            'log' => [
                'applicant_name' => $applicant->full_name,
                'document_type' => $docType,
                'old_filename' => basename($oldPath),
                'new_filename' => $finalFilename,
                'old_path' => $oldPath,
                'new_path' => $finalNewPath,
                'status' => 'Success',
            ]
        ];
    }

    /**
     * Intelligent physical file locator that checks current local storage as well as cPanel backup paths
     * to recover 100% of files even if they were moved to the root or exist only in the repository!
     */
    private function locatePhysicalFile(string $oldPath): ?array
    {
        // 1. Check standard Laravel public storage disk
        if (Storage::disk('public')->exists($oldPath)) {
            return [
                'source' => 'public_disk',
                'path' => Storage::disk('public')->path($oldPath)
            ];
        }

        // 2. Check base path of current site (in case numbered folders are in the root directory!)
        $rootRelativePath = base_path($oldPath);
        if (File::exists($rootRelativePath)) {
            return [
                'source' => 'local_root',
                'path' => $rootRelativePath
            ];
        }

        // 2b. Check if numbered folders are in root directly (removing 'documents/' prefix)
        $rootCleanPath = base_path(str_replace('documents/', '', $oldPath));
        if (File::exists($rootCleanPath)) {
            return [
                'source' => 'local_root_direct',
                'path' => $rootCleanPath
            ];
        }

        // 3. Fallback: Check the repository storage directory (if running on live site)
        $repositoryPath = "/home/amisdavc/repositories/AMIS-enrollment/storage/app/public/{$oldPath}";
        if (File::exists($repositoryPath)) {
            return [
                'source' => 'repository_disk',
                'path' => $repositoryPath
            ];
        }

        // 4. Fallback: Check the live site storage directory (if running on repository site)
        $liveSitePath = "/home/amisdavc/enrollment.amis.edu.ph/storage/app/public/{$oldPath}";
        if (File::exists($liveSitePath)) {
            return [
                'source' => 'live_site_disk',
                'path' => $liveSitePath
            ];
        }

        // 5. Check repository root folder (for files sitting directly in repositories root)
        $repositoryRootClean = "/home/amisdavc/repositories/AMIS-enrollment/" . str_replace('documents/', '', $oldPath);
        if (File::exists($repositoryRootClean)) {
            return [
                'source' => 'repository_root',
                'path' => $repositoryRootClean
            ];
        }

        return null;
    }

    private function generateCsvLog(array $logs): void
    {
        $headers = ['Applicant Name', 'Document Type', 'Old Filename', 'New Filename', 'Old Path', 'New Path', 'Status'];
        $filePath = Storage::disk('public')->path('migration_log.csv');

        // Ensure directories exist
        Storage::disk('public')->makeDirectory('');

        $file = fopen($filePath, 'w');
        fputcsv($file, $headers);

        foreach ($logs as $log) {
            fputcsv($file, [
                $log['applicant_name'],
                $log['document_type'],
                $log['old_filename'],
                $log['new_filename'],
                $log['old_path'],
                $log['new_path'],
                $log['status'],
            ]);
        }

        fclose($file);
    }
}
