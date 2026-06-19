<?php

namespace App\Services\Upload;

use App\Models\EnrollmentApplicant;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class EnrollmentUploadService
{
    public const DOCUMENT_FIELDS = [
        'photo_2x2',
        'birth_cert',
        'report_card',
        'marriage_contract',
        'medical_record',
        'affidavit',
    ];

    public function storeEnrollmentDocuments(EnrollmentApplicant $applicant, Request $request): void
    {
        // 1. Determine Family Folder
        $familyFolder = 'family_' . strtolower(trim($applicant->last_name)) . '_' . str_replace(' ', '_', strtolower(trim($applicant->school_year ?? '2026-2027')));
        $familyFolder = preg_replace('/[^a-z0-9_\-]+/', '', $familyFolder);

        // 2. Determine Child Full Name & Folder (fullname_grade)
        $childName = strtolower(trim($applicant->first_name . ' ' . ($applicant->middle_name ?? '') . ' ' . $applicant->last_name . ' ' . ($applicant->suffix ?? '')));
        $childFullNameSlug = preg_replace('/[^a-z0-9]+/', '_', $childName);
        $childFullNameSlug = trim($childFullNameSlug, '_');

        $gradeSlug = preg_replace('/[^a-z0-9]+/', '_', strtolower(trim($applicant->grade_level ?? 'grade_pending')));
        $gradeSlug = trim($gradeSlug, '_');

        $childFolder = $childFullNameSlug . '_' . $gradeSlug;

        foreach (self::DOCUMENT_FIELDS as $key) {
            if (!$request->hasFile($key)) {
                continue;
            }

            $oldPath = $applicant->{$key . '_url'};

            if ($oldPath) {
                Storage::disk('public')->delete($oldPath);
                if (str_contains($oldPath, '/optimized/')) {
                    Storage::disk('public')->delete(str_replace('/optimized/', '/original/', $oldPath));
                    Storage::disk('public')->delete(str_replace('/optimized/', '/thumbnails/small/', $oldPath));
                    Storage::disk('public')->delete(str_replace('/optimized/', '/thumbnails/medium/', $oldPath));
                    Storage::disk('public')->delete(str_replace('/optimized/', '/thumbnails/large/', $oldPath));
                }
            }

            $prefix = match ($key) {
                'photo_2x2' => '2x2',
                'birth_cert' => 'birth_certificate',
                'report_card' => 'report_card',
                'marriage_contract' => 'marriage_contract',
                'medical_record' => 'medical_record',
                'affidavit' => 'affidavit',
                default => $key,
            };

            $file = $request->file($key);
            $optimizer = new \App\Services\ImageOptimizerService();
            $isImage = $optimizer->isOptimizable($file->getClientMimeType());

            $dir = 'documents/' . $familyFolder . '/' . $childFolder;
            $filenameWithoutExt = $prefix . '_' . $childFolder;

            if ($key === 'photo_2x2' && $isImage) {
                $paths = $optimizer->optimize($file, 'public', $dir, $filenameWithoutExt);
                $applicant->update([$key . '_url' => $paths['optimized']]);
            } elseif ($isImage) {
                $extension = $file->getClientOriginalExtension() ?: $file->guessExtension() ?: 'bin';
                $originalName = "{$filenameWithoutExt}.{$extension}";
                $webpName = "{$filenameWithoutExt}.webp";

                Storage::disk('public')->putFileAs("{$dir}/original", $file, $originalName);
                $originalPath = "{$dir}/original/{$originalName}";
                
                $optimizedDir = Storage::disk('public')->path("{$dir}/optimized");
                if (!is_dir($optimizedDir)) {
                    mkdir($optimizedDir, 0755, true);
                }

                $absoluteOriginal = Storage::disk('public')->path("{$dir}/original/{$originalName}");
                $absoluteOptimized = Storage::disk('public')->path("{$dir}/optimized/{$webpName}");

                $resultCode = -1;
                if (function_exists('exec')) {
                    try {
                        @exec('magick ' . escapeshellarg($absoluteOriginal) . ' -quality 85 ' . escapeshellarg($absoluteOptimized) . ' 2>&1', $output, $resultCode);
                    } catch (\Throwable $e) {
                        \Illuminate\Support\Facades\Log::error("Failed to run magick command in EnrollmentUploadService: " . $e->getMessage());
                    }
                }

                $optimizedPath = "{$dir}/optimized/{$webpName}";
                $applicant->update([
                    $key . '_url' => $resultCode === 0 && Storage::disk('public')->exists($optimizedPath)
                        ? $optimizedPath
                        : $originalPath,
                ]);
            } else {
                $extension = $file->getClientOriginalExtension() ?: $file->guessExtension() ?: 'bin';
                $filename = "{$filenameWithoutExt}.{$extension}";
                $path = $file->storeAs($dir, $filename, 'public');
                $applicant->update([$key . '_url' => $path]);
            }
        }
    }

    public function deleteEnrollmentDocuments(EnrollmentApplicant $applicant): void
    {
        foreach (self::DOCUMENT_FIELDS as $key) {
            $path = $applicant->{$key . '_url'};

            if ($path) {
                Storage::disk('public')->delete($path);
            }
        }
    }

    public function removeDraftDocument(EnrollmentApplicant $applicant, string $document): void
    {
        $column = $document . '_url';
        $path = $applicant->{$column};

        if ($path) {
            Storage::disk('public')->delete($path);
        }

        $documentStatuses = $applicant->document_statuses ?? [];
        unset($documentStatuses[$document]);

        $updates = [
            $column => null,
            'document_statuses' => empty($documentStatuses) ? null : $documentStatuses,
        ];

        if ($document === 'affidavit') {
            $updates['affidavit_data'] = null;
        }

        if ($document === 'report_card') {
            if ($applicant->affidavit_url) {
                Storage::disk('public')->delete($applicant->affidavit_url);
            }
            $updates['affidavit_url'] = null;
            $updates['affidavit_data'] = null;
            unset($documentStatuses['affidavit']);
            $updates['document_statuses'] = empty($documentStatuses) ? null : $documentStatuses;
        }

        $applicant->update($updates);
    }

    public function isEnrollmentDocument(string $document): bool
    {
        return in_array($document, self::DOCUMENT_FIELDS, true);
    }
}
