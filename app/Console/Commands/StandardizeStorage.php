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
                $oldPaths = $applicant->payment->receipt_urls;
                $newPaths = [];
                $timestamp = time();
                foreach ($oldPaths as $index => $oldPath) {
                    $extension = strtolower(pathinfo($oldPath, PATHINFO_EXTENSION) ?: 'jpg');
                    $suffix = count($oldPaths) > 1 ? "_{$index}" : "";
                    $newFilename = 'payment_receipt_' . $childFullNameSlug . '_' . $timestamp . $suffix . '.' . $extension;
                    $newPath = 'documents/' . $familyFolder . '/' . $newFilename;

                    if ($oldPath !== $newPath) {
                        $physicalPath = $this->locatePhysicalFile($oldPath, $applicant, 'payment_proof');
                        if ($physicalPath) {
                            $this->line("👉 Standardizing: [Child #{$applicant->id}] {$applicant->full_name} (payment_proof #{$index})");
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
                            }
                            $newPaths[] = $newPath;
                            $movedCount++;
                        } else {
                            $newPaths[] = $oldPath; // Keep old path if file missing
                        }
                    } else {
                        $newPaths[] = $oldPath; // Keep old path if already standardized
                    }
                }

                if (!$dryRun && !empty($newPaths)) {
                    $finalVal = count($newPaths) === 1 ? $newPaths[0] : json_encode($newPaths);
                    $applicant->payment->update(['receipt_url' => $finalVal]);
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

        // Dynamic detection of cPanel home folder (e.g. /home/amisdavc or /home/username)
        $homeDir = null;
        $basePath = base_path();
        if (preg_match('/^(\/home\/[^\/]+)/', $basePath, $matches)) {
            $homeDir = $matches[1];
        }

        if ($homeDir) {
            // 3. Fallback: Check the repository storage directory dynamically
            // Scan for any folder under $homeDir/repositories/
            $reposPath = $homeDir . '/repositories';
            if (File::isDirectory($reposPath)) {
                $repos = File::directories($reposPath);
                foreach ($repos as $repo) {
                    $repPath = "{$repo}/storage/app/public/{$oldPath}";
                    if (File::exists($repPath)) {
                        return [
                            'source' => 'repository_disk_dynamic',
                            'path' => $repPath
                        ];
                    }

                    $repRootPath = "{$repo}/" . str_replace('documents/', '', $oldPath);
                    if (File::exists($repRootPath)) {
                        return [
                            'source' => 'repository_root_dynamic',
                            'path' => $repRootPath
                        ];
                    }
                }
            }

            // 4. Fallback: Check all other site directories under homeDir (e.g., enrollment.amis.edu.ph, amis.edu.ph, etc.)
            $homeSubFolders = File::directories($homeDir);
            foreach ($homeSubFolders as $subFolder) {
                $subBaseName = basename($subFolder);
                if (str_contains(strtolower($subBaseName), 'amis') || str_contains(strtolower($subBaseName), 'enrollment')) {
                    $livePath = "{$subFolder}/storage/app/public/{$oldPath}";
                    if (File::exists($livePath)) {
                        return [
                            'source' => 'site_disk_dynamic',
                            'path' => $livePath
                        ];
                    }

                    $liveRootPath = "{$subFolder}/" . str_replace('documents/', '', $oldPath);
                    if (File::exists($liveRootPath)) {
                        return [
                            'source' => 'site_root_dynamic',
                            'path' => $liveRootPath
                        ];
                    }
                }
            }
        }

        // 5. Check intermediate renamed path (from enrollment:rename-documents command)
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

            if ($homeDir) {
                // Check all subfolders of home dir and repos for the intermediate path dynamically!
                if (isset($repos) && is_array($repos)) {
                    foreach ($repos as $repo) {
                        $repInter = "{$repo}/storage/app/public/{$intermediatePath}";
                        if (File::exists($repInter)) {
                            return [
                                'source' => 'intermediate_repository_dynamic',
                                'path' => $repInter
                            ];
                        }
                    }
                }

                foreach ($homeSubFolders as $subFolder) {
                    $subBaseName = basename($subFolder);
                    if (str_contains(strtolower($subBaseName), 'amis') || str_contains(strtolower($subBaseName), 'enrollment')) {
                        $liveInter = "{$subFolder}/storage/app/public/{$intermediatePath}";
                        if (File::exists($liveInter)) {
                            return [
                                'source' => 'intermediate_site_dynamic',
                                'path' => $liveInter
                            ];
                        }
                    }
                }
            }
        }

        // 6. SMART SEARCH: Scan all directories for matching applicant folder & leading-zero variations
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
            ];

            if ($homeDir) {
                // Dynamically scan any subdirectories inside $homeDir/repositories/
                if (isset($repos) && is_array($repos)) {
                    foreach ($repos as $repo) {
                        $parentDirs[] = $repo . '/storage/app/public/documents';
                        $parentDirs[] = $repo . '/storage/app/public';
                        $parentDirs[] = $repo;
                    }
                }

                // Scan all folders matching "amis" or "enrollment"
                foreach ($homeSubFolders as $subFolder) {
                    $subBaseName = basename($subFolder);
                    if (str_contains(strtolower($subBaseName), 'amis') || str_contains(strtolower($subBaseName), 'enrollment')) {
                        $parentDirs[] = $subFolder . '/storage/app/public/documents';
                        $parentDirs[] = $subFolder . '/storage/app/public';
                        $parentDirs[] = $subFolder;
                    }
                }
            }

            // Remove duplicates
            $parentDirs = array_values(array_unique(array_filter($parentDirs)));

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

            // 7. GLOBAL RECURSIVE SEARCH (Ultimate fallback to find files anywhere in cPanel)
            $globalPath = $this->findFileGlobally($filename);
            if ($globalPath) {
                return [
                    'source' => 'global_system_search',
                    'path' => $globalPath
                ];
            }
        }

        return null;
    }

    /**
     * Globally search for a filename under home web roots recursively, skipping heavy vendor folders.
     */
    private function findFileGlobally(string $filename): ?string
    {
        $searchRoots = [
            base_path(),
        ];

        $homeDir = null;
        $basePath = base_path();
        if (preg_match('/^(\/home\/[^\/]+)/', $basePath, $matches)) {
            $homeDir = $matches[1];
        }

        if ($homeDir) {
            $reposPath = $homeDir . '/repositories';
            if (File::isDirectory($reposPath)) {
                $repos = File::directories($reposPath);
                foreach ($repos as $repo) {
                    $searchRoots[] = $repo;
                }
            }

            $homeSubFolders = File::directories($homeDir);
            foreach ($homeSubFolders as $subFolder) {
                $subBaseName = basename($subFolder);
                if (str_contains(strtolower($subBaseName), 'amis') || str_contains(strtolower($subBaseName), 'enrollment')) {
                    $searchRoots[] = $subFolder;
                }
            }

            $searchRoots[] = $homeDir;
        }

        $searchRoots = array_values(array_unique(array_filter($searchRoots)));

        foreach ($searchRoots as $root) {
            if (empty($root) || !File::isDirectory($root)) {
                continue;
            }

            $foundPath = $this->recursiveSearchFile($root, $filename);
            if ($foundPath) {
                return $foundPath;
            }
        }

        return null;
    }

    /**
     * Recursively find a file skipping heavy directories to prevent timeouts/memory issues.
     */
    private function recursiveSearchFile(string $dir, string $filename): ?string
    {
        $dir = rtrim($dir, '/\\');
        
        $directPath = $dir . DIRECTORY_SEPARATOR . $filename;
        if (File::exists($directPath)) {
            return $directPath;
        }

        try {
            $files = scandir($dir);
            if ($files === false) {
                return null;
            }
        } catch (\Exception $e) {
            return null;
        }

        foreach ($files as $file) {
            if ($file === '.' || $file === '..') {
                continue;
            }

            $path = $dir . DIRECTORY_SEPARATOR . $file;
            if (is_dir($path)) {
                $lowerName = strtolower($file);
                
                // Skip heavy directories to protect execution time
                if (in_array($lowerName, [
                    'vendor', 'node_modules', '.git', 'cache', 'framework', 'sessions', 
                    'logs', 'views', 'dompdf', 'mpdf', 'tcpdf', 'socialiteproviders', 'setasign'
                ])) {
                    continue;
                }

                $found = $this->recursiveSearchFile($path, $filename);
                if ($found) {
                    return $found;
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
