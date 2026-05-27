<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\File;

class CleanupStorage extends Command
{
    protected $signature = 'enrollment:cleanup-storage {--dry-run : Run in simulation mode without deleting physical folders}';
    protected $description = 'Safely clean up all old duplicate numeric and intermediate folders in cPanel root and storage, leaving only standardized family folders';

    public function handle(): void
    {
        $dryRun = $this->option('dry-run');

        if ($dryRun) {
            $this->info("=== DRY RUN MODE: Simulating cleanup. No files or directories will be deleted ===\n");
        } else {
            $this->info("=== REAL RUN MODE: Deleting old duplicate folders from root and storage ===\n");
        }

        // Dynamic cPanel home directory detection
        $homeDir = null;
        $basePath = base_path();
        if (preg_match('/^(\/home\d*\/[^\/]+)/', $basePath, $matches)) {
            $homeDir = $matches[1];
        }

        // System folders at base path level that we must NEVER inspect or delete
        $systemFolders = [
            'app', 'bootstrap', 'config', 'database', 'public', 'resources', 'routes', 'storage', 
            'tests', 'vendor', 'node_modules', '.git', 'docs', 'images', 'etc', 'home2', 'logs', 'mail'
        ];

        $foldersToDelete = [];

        // 1. Scan public documents storage folder
        $publicDocumentsPath = Storage::disk('public')->path('documents');
        if (File::isDirectory($publicDocumentsPath)) {
            $subDirs = File::directories($publicDocumentsPath);
            foreach ($subDirs as $dir) {
                $name = basename($dir);
                if ($this->shouldDeleteFolder($name)) {
                    $foldersToDelete[] = [
                        'path' => $dir,
                        'source' => 'public_storage'
                    ];
                }
            }
        }

        // 2. Scan cPanel site root directory dynamically
        if (File::isDirectory($basePath)) {
            $rootDirs = File::directories($basePath);
            foreach ($rootDirs as $dir) {
                $name = basename($dir);
                // Skip Laravel system folders
                if (in_array(strtolower($name), $systemFolders)) {
                    continue;
                }

                if ($this->shouldDeleteFolder($name)) {
                    $foldersToDelete[] = [
                        'path' => $dir,
                        'source' => 'site_root'
                    ];
                }
            }
        }

        // 3. Scan cPanel home directory repositories dynamically
        if ($homeDir) {
            $reposPath = $homeDir . '/repositories';
            if (File::isDirectory($reposPath)) {
                $repos = File::directories($reposPath);
                foreach ($repos as $repo) {
                    $repoPublicDocs = "{$repo}/storage/app/public/documents";
                    if (File::isDirectory($repoPublicDocs)) {
                        $repoSubDirs = File::directories($repoPublicDocs);
                        foreach ($repoSubDirs as $dir) {
                            $name = basename($dir);
                            if ($this->shouldDeleteFolder($name)) {
                                $foldersToDelete[] = [
                                    'path' => $dir,
                                    'source' => 'repository_storage'
                                ];
                            }
                        }
                    }

                    // Scan repository root itself
                    $repoDirs = File::directories($repo);
                    foreach ($repoDirs as $dir) {
                        $name = basename($dir);
                        if (in_array(strtolower($name), $systemFolders)) {
                            continue;
                        }
                        if ($this->shouldDeleteFolder($name)) {
                            $foldersToDelete[] = [
                                'path' => $dir,
                                	'source' => 'repository_root'
                            ];
                        }
                    }
                }
            }
        }

        // Perform cleanup
        $deletedCount = 0;

        if (empty($foldersToDelete)) {
            $this->info("✨ No old duplicate or intermediate folders found. Your storage is already clean!");
            return;
        }

        foreach ($foldersToDelete as $folder) {
            $path = $folder['path'];
            $source = $folder['source'];

            if ($dryRun) {
                $this->line("👉 [DRY RUN] Would delete: {$path} (Found in {$source})");
                $deletedCount++;
            } else {
                if (File::isDirectory($path)) {
                    File::deleteDirectory($path);
                    $this->line("🗑️ Deleted: {$path} (From {$source})");
                    $deletedCount++;
                }
            }
        }

        $this->info("\n=== Cleanup Summary ===");
        if ($dryRun) {
            $this->info("Folders Identified for Deletion: {$deletedCount}");
            $this->info("Run without --dry-run to physically delete these folders.");
        } else {
            $this->info("Successfully Deleted Folders:    {$deletedCount}");
            $this->info("Standardized family folders are completely safe and untouched.");
        }
        $this->info("=======================");
    }

    /**
     * Determine if a folder is an old duplicate numeric folder or old intermediate renamed folder.
     */
    private function shouldDeleteFolder(string $name): bool
    {
        $nameLower = strtolower($name);

        // Never delete standardized family folders
        if (str_starts_with($nameLower, 'family_')) {
            return false;
        }

        // 1. Is it purely numeric (e.g. '87', '09', '252')?
        if (is_numeric($name)) {
            return true;
        }

        // 2. Does it match the renamed pattern: {applicant_id}_{lastName}_{firstName} (e.g. '87_GUMPAL_MIKHAIL', '09_ALSAEED_MESHARI')?
        if (preg_match('/^\d+_[a-z0-9_]+$/i', $name)) {
            return true;
        }

        return false;
    }
}
