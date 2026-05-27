<?php

namespace App\Console\Commands;

use App\Models\EnrollmentApplicant;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Storage;

class StandardizeStorage extends Command
{
    protected $signature = 'enrollment:standardize-storage {--dry-run : Run in simulation mode without making actual changes}';
    protected $description = 'Standardize all existing/old numeric and hashed files in storage to the premium family and child folder name slug format';

    public function handle(): void
    {
        $dryRun = $this->option('dry-run');

        if ($dryRun) {
            $this->info("=== DRY RUN MODE: No physical files or database records will be modified ===\n");
        }

        $applicants = EnrollmentApplicant::all();
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

            foreach ($fields as $field => $prefix) {
                $column = $field . '_url';
                $oldPath = $applicant->{$column};

                if (empty($oldPath)) {
                    continue;
                }

                // Get extension
                $extension = strtolower(pathinfo($oldPath, PATHINFO_EXTENSION) ?: 'jpg');
                $newFilename = $prefix . '_' . $childFolder . '.' . $extension;
                $newPath = 'documents/' . $familyFolder . '/' . $childFolder . '/' . $newFilename;

                if ($oldPath === $newPath) {
                    $skippedCount++;
                    continue;
                }

                // Check if file exists on disk
                if (!Storage::disk('public')->exists($oldPath)) {
                    // Maybe it was already renamed or is missing?
                    if (Storage::disk('public')->exists($newPath)) {
                        $this->warn("⚠️ File already in new path but DB was outdated for Child #{$applicant->id} ({$applicant->full_name}) - Field: {$field}");
                        if (!$dryRun) {
                            $applicant->update([$column => $newPath]);
                        }
                        $movedCount++;
                    } else {
                        $this->error("❌ Missing File on disk for Child #{$applicant->id} ({$applicant->full_name}) - Path: {$oldPath}");
                        $missingCount++;
                    }
                    continue;
                }

                $this->line("👉 Standardizing: [Child #{$applicant->id}] {$applicant->full_name}");
                $this->line("   Old: {$oldPath}");
                $this->line("   New: {$newPath}");

                if (!$dryRun) {
                    // Ensure destination directory doesn't conflict
                    if (Storage::disk('public')->exists($newPath)) {
                        Storage::disk('public')->delete($newPath);
                    }

                    // Move the file physically
                    Storage::disk('public')->move($oldPath, $newPath);

                    // Update the database field
                    $applicant->update([$column => $newPath]);
                }

                $movedCount++;
            }
        }

        $this->info("\n=== Standardization Summary ===");
        $this->info("Standardized/Updated: {$movedCount}");
        $this->info("Already Standardized: {$skippedCount}");
        $this->info("Missing from Disk:    {$missingCount}");
        $this->info("==============================");
    }
}
