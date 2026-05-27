<?php

namespace App\Console\Commands;

use App\Models\EnrollmentApplicant;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\File;

class StandardizeStorage extends Command
{
    protected $signature = 'enrollment:standardize-storage {--dry-run : Run in simulation mode without making actual changes}';
    protected $description = 'Standardize all existing/old numeric and hashed files in storage to the premium family and child folder name slug format';

    public function handle(): void
    {
        $dryRun = $this->option('dry-run');

        if ($dryRun) {
            $this->info("=== DRY RUN MODE: No physical files or database records will be modified ===\n");
        } else {
            $this->info("=== REAL RUN MODE: Standardizing files and updating database ===\n");
        }

        $applicants = EnrollmentApplicant::with('payment')->get();
        $fields = [
            'photo_2x2' => '2x2',
            'birth_cert' => 'birth_certificate',
            'report_card' => 'report_card',
            'marriage_contract' => 'marriage_contract',
            'medical_record' => 'medical_record',
            'affidavit' => 'affidavit',
        ];

        $movedCount = 0;
        $skippedCount = 0;
        $missingCount = 0;
        $logs = [];

        foreach ($applicants as $applicant) {
            // Determine premium folder structures (exact same logic as EnrollmentUploadService)
            $lastName = strtolower(trim($applicant->last_name ?? ''));
            if (empty($lastName)) {
                continue;
            }

            $schoolYear = strtolower(trim($applicant->school_year ?? '2026-2027'));
            $familyFolder = 'family_' . $lastName . '_' . str_replace(' ', '_', $schoolYear);
            $familyFolder = preg_replace('/[^a-z0-9_\-]+/', '', $familyFolder);

            $childName = strtolower(trim(
                ($applicant->first_name ?? '') . ' ' .
                ($applicant->middle_name ?? '') . ' ' .
                ($applicant->last_name ?? '') . ' ' .
                ($applicant->suffix ?? '')
            ));
            $childFullNameSlug = preg_replace('/[^a-z0-9]+/', '_', $childName);
            $childFullNameSlug = trim($childFullNameSlug, '_');

            $gradeSlug = preg_replace('/[^a-z0-9]+/', '_', strtolower(trim($applicant->grade_level ?? 'grade_pending')));
            $gradeSlug = trim($gradeSlug, '_');

            $childFolder = $childFullNameSlug . '_' . $gradeSlug;

            // Process main documents
            foreach ($fields as $field => $prefix) {
                $column = $field . '_url';
                $oldPath = $applicant->{$column};

                if (empty($oldPath)) {
                    continue;
                }

                $extension = strtolower(pathinfo($oldPath, PATHINFO_EXTENSION) ?: 'jpg');
                $newFilename = $prefix . '_' . $childFolder . '.' . $extension;
                $newPath = 'documents/' . $familyFolder . '/' . $childFolder . '/' . $newFilename;

                if ($oldPath === $newPath) {
                    $skippedCount++;
                    continue;
                }

                // Locate file physically (including base_path fallbacks and smart folder searches)
                $physicalPath = $this->locatePhysicalFile($oldPath, $applicant, $prefix);

                if (!$physicalPath) {
                    if (Storage::disk('public')->exists($newPath)) {
                        $this->warn("⚠️ File already in new path but DB was outdated for Child #{$applicant->id} ({$applicant->full_name}) - Field: {$field}");
                        if (!$dryRun) {
                            $applicant->update([$column => $newPath]);
                        }
                        $movedCount++;
                        $logs[] = [
                            'name' => $applicant->full_name,
                            'type' => $field,
                            'old_path' => $oldPath,
                            'new_path' => $newPath,
                            'status' => 'DB Updated Only'
                        ];
                    } else {
                        $this->error("❌ Missing File on disk for Child #{$applicant->id} ({$applicant->full_name}) - Path: {$oldPath}");
                        $missingCount++;
                        $logs[] = [
                            'name' => $applicant->full_name,
                            'type' => $field,
                            'old_path' => $oldPath,
                            'new_path' => 'N/A',
                            'status' => 'Missing'
                        ];
                    }
                    continue;
                }

                $this->line("👉 Standardizing: [Child #{$applicant->id}] {$applicant->full_name} ({$field})");
                $this->line("   Old: {$oldPath} (Found in {$physicalPath['source']})");
                $this->line("   New: {$newPath}");

                if (!$dryRun) {
                    // Ensure destination directory exists
                    Storage::disk('public')->makeDirectory('documents/' . $familyFolder . '/' . $childFolder);

                    // Ensure target filename is unique or overwritten cleanly
                    if (Storage::disk('public')->exists($newPath)) {
                        Storage::disk('public')->delete($newPath);
                    }

                    // Physically copy/move file to its standardized place
                    if ($physicalPath['source'] === 'public_disk') {
                        Storage::disk('public')->move($oldPath, $newPath);
                    } else {
                        Storage::disk('public')->put($newPath, file_get_contents($physicalPath['path']));
                        
                        // If it was in the base directory root, clean it up if it's a real run
                        if (File::exists($physicalPath['path'])) {
                            File::delete($physicalPath['path']);
                        }
                    }

                    // Update the database field
                    $applicant->update([$column => $newPath]);
                }

                $movedCount++;
                $logs[] = [
                    'name' => $applicant->full_name,
                    'type' => $field,
                    'old_path' => $oldPath,
                    'new_path' => $newPath,
                    'status' => 'Success'
                ];
            }

            // Process payment proof receipt if exists
            if ($applicant->payment && !empty($applicant->payment->receipt_url)) {
                $oldPath = $applicant->payment->receipt_url;
                $extension = strtolower(pathinfo($oldPath, PATHINFO_EXTENSION) ?: 'jpg');
                $newFilename = 'payment_receipt_' . $childFullNameSlug . '_' . time() . '.' . $extension;
                $newPath = 'documents/' . $familyFolder . '/' . $newFilename;

                if ($oldPath !== $newPath) {
                    $physicalPath = $this->locatePhysicalFile($oldPath, $applicant, 'payment_proof');
                    if ($physicalPath) {
                        $this->line("👉 Standardizing: [Child #{$applicant->id}] {$applicant->full_name} (payment_proof)");
                        $this->line("   Old: {$oldPath} (Found in {$physicalPath['source']})");
                        $this->line("   New: {$newPath}");

                        if (!$dryRun) {
                            Storage::disk('public')->makeDirectory('documents/' . $familyFolder);
                            
                            if ($physicalPath['source'] === 'public_disk') {
                                Storage::disk('public')->move($oldPath, $newPath);
                            } else {
                                Storage::disk('public')->put($newPath, file_get_contents($physicalPath['path']));
                                if (File::exists($physicalPath['path'])) {
                                    File::delete($physicalPath['path']);
                                }
                            }
                            $applicant->payment->update(['receipt_url' => $newPath]);
                        }
                        $movedCount++;
                    }
                }
            }
        }

        $this->generateCsvLog($logs);

        $this->info("\n=== Standardization Summary ===");
        $this->info("Standardized/Updated: {$movedCount}");
        $this->info("Already Standardized: {$skippedCount}");
        $this->info("Missing from Disk:    {$missingCount}");
        $this->info("CSV Log Generated at: storage/app/public/standardization_log.csv");
        $this->info("==============================");
    }

    /**
     * Locate physical file across standard public storage, cPanel root directory fallbacks, and smart search.
     */
    private function locatePhysicalFile(string $oldPath, ?EnrollmentApplicant $applicant = null, ?string $prefix = null): ?array
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

        // 3. Fallback: Check the repository storage directory
        $repositoryPath = "/home/amisdavc/repositories/AMIS-enrollment/storage/app/public/{$oldPath}";
        if (File::exists($repositoryPath)) {
            return [
                'source' => 'repository_disk',
                'path' => $repositoryPath
            ];
        }

        // 4. Fallback: Check the repository root directory
        $repositoryRootClean = "/home/amisdavc/repositories/AMIS-enrollment/" . str_replace('documents/', '', $oldPath);
        if (File::exists($repositoryRootClean)) {
            return [
                'source' => 'repository_root',
                'path' => $repositoryRootClean
            ];
        }

        // 5. Fallback: Check the live site storage directory
        $liveSitePath = "/home/amisdavc/enrollment.amis.edu.ph/storage/app/public/{$oldPath}";
        if (File::exists($liveSitePath)) {
            return [
                'source' => 'live_site_disk',
                'path' => $liveSitePath
            ];
        }

        // 6. Check intermediate renamed path (from enrollment:rename-documents command)
        if ($applicant && $prefix) {
            $lastNameRenamed = strtoupper(preg_replace('/[^a-zA-Z0-9]+/', '', trim($applicant->last_name ?? '')));
            $firstNameRenamed = strtoupper(preg_replace('/[^a-zA-Z0-9]+/', '', trim($applicant->first_name ?? '')));
            $folderNameRenamed = "{$applicant->id}_{$lastNameRenamed}_{$firstNameRenamed}";
            $extension = strtolower(pathinfo($oldPath, PATHINFO_EXTENSION) ?: 'jpg');
            $intermediatePath = "documents/{$folderNameRenamed}/{$prefix}/{$prefix}_{$applicant->id}_{$lastNameRenamed}_{$firstNameRenamed}.{$extension}";

            // Check standard Laravel public storage disk at intermediate path
            if (Storage::disk('public')->exists($intermediatePath)) {
                return [
                    'source' => 'intermediate_disk',
                    'path' => Storage::disk('public')->path($intermediatePath)
                ];
            }

            // Check root and cPanel paths for intermediate path
            $rootIntermediatePath = base_path($intermediatePath);
            if (File::exists($rootIntermediatePath)) {
                return [
                    'source' => 'intermediate_root',
                    'path' => $rootIntermediatePath
                ];
            }

            $liveIntermediatePath = "/home/amisdavc/enrollment.amis.edu.ph/storage/app/public/{$intermediatePath}";
            if (File::exists($liveIntermediatePath)) {
                return [
                    'source' => 'intermediate_live_site',
                    'path' => $liveIntermediatePath
                ];
            }

            $repIntermediatePath = "/home/amisdavc/repositories/AMIS-enrollment/storage/app/public/{$intermediatePath}";
            if (File::exists($repIntermediatePath)) {
                return [
                    'source' => 'intermediate_repository',
                    'path' => $repIntermediatePath
                ];
            }
        }

        // 7. SMART SEARCH: Scan all directories for matching applicant folder & leading-zero variations
        if ($applicant) {
            $applicantId = $applicant->id;
            $filename = basename($oldPath);

            $applicantIdStr = (string)$applicantId;
            $paddedId2 = sprintf('%02d', $applicantId);
            $paddedId3 = sprintf('%03d', $applicantId);

            $allowedFolderNames = [$applicantIdStr, $paddedId2, $paddedId3];
            $allowedPrefixes = [$applicantIdStr . '_', $paddedId2 . '_', $paddedId3 . '_'];

            // List of parent directories to search for applicant-specific folders
            $parentDirs = [
                Storage::disk('public')->path('documents'),
                Storage::disk('public')->path(''),
                base_path('documents'),
                base_path(''),
                "/home/amisdavc/repositories/AMIS-enrollment/storage/app/public/documents",
                "/home/amisdavc/repositories/AMIS-enrollment/storage/app/public",
                "/home/amisdavc/repositories/AMIS-enrollment",
                "/home/amisdavc/enrollment.amis.edu.ph/storage/app/public/documents",
                "/home/amisdavc/enrollment.amis.edu.ph/storage/app/public",
                "/home/amisdavc/enrollment.amis.edu.ph",
            ];

            foreach ($parentDirs as $parentDir) {
                if (empty($parentDir) || !File::isDirectory($parentDir)) {
                    continue;
                }

                // Scan all directories inside this parent directory
                $subDirs = File::directories($parentDir);
                foreach ($subDirs as $subDir) {
                    $dirName = basename($subDir);
                    $dirNameLower = strtolower($dirName);
                    $matchFound = false;

                    // Check exact matches
                    foreach ($allowedFolderNames as $allowedName) {
                        if ($dirNameLower === strtolower($allowedName)) {
                            $matchFound = true;
                            break;
                        }
                    }

                    // Check prefix matches
                    if (!$matchFound) {
                        foreach ($allowedPrefixes as $allowedPrefix) {
                            if (str_starts_with($dirNameLower, strtolower($allowedPrefix))) {
                                $matchFound = true;
                                break;
                            }
                        }
                    }

                    if ($matchFound) {
                        // A. Check if the original file is directly in this directory
                        $directFilePath = $subDir . '/' . $filename;
                        if (File::exists($directFilePath)) {
                            return [
                                'source' => 'smart_search_direct',
                                'path' => $directFilePath
                            ];
                        }

                        // B. Check if the original file is in any subdirectory of this folder (recursive)
                        if (File::isDirectory($subDir)) {
                            $allFiles = File::allFiles($subDir);
                            foreach ($allFiles as $file) {
                                if (strtolower($file->getFilename()) === strtolower($filename)) {
                                    return [
                                        'source' => 'smart_search_recursive',
                                        'path' => $file->getRealPath()
                                    ];
                                }
                            }
                        }
                    }
                }
            }
        }

        return null;
    }

    private function generateCsvLog(array $logs): void
    {
        $headers = ['Applicant Name', 'Document Type', 'Old Path', 'New Path', 'Status'];
        $filePath = Storage::disk('public')->path('standardization_log.csv');

        $file = fopen($filePath, 'w');
        fputcsv($file, $headers);

        foreach ($logs as $log) {
            fputcsv($file, [
                $log['name'],
                $log['type'],
                $log['old_path'],
                $log['new_path'],
                $log['status'],
            ]);
        }

        fclose($file);
    }
}
