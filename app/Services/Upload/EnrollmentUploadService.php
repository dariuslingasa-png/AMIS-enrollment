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

            $extension = $request->file($key)->getClientOriginalExtension() ?: $request->file($key)->guessExtension() ?: 'bin';
            $filename = $prefix . '_' . $childFolder . '.' . $extension;

            $path = $request->file($key)->storeAs('documents/' . $familyFolder . '/' . $childFolder, $filename, 'public');
            $applicant->update([$key . '_url' => $path]);
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

        $applicant->update($updates);
    }

    public function isEnrollmentDocument(string $document): bool
    {
        return in_array($document, self::DOCUMENT_FIELDS, true);
    }
}
