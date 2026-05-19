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
        foreach (self::DOCUMENT_FIELDS as $key) {
            if (!$request->hasFile($key)) {
                continue;
            }

            $oldPath = $applicant->{$key . '_url'};

            if ($oldPath) {
                Storage::disk('public')->delete($oldPath);
            }

            $path = $request->file($key)->store('documents/' . $applicant->id, 'public');
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

        $applicant->update([
            $column => null,
            'document_statuses' => empty($documentStatuses) ? null : $documentStatuses,
        ]);
    }

    public function isEnrollmentDocument(string $document): bool
    {
        return in_array($document, self::DOCUMENT_FIELDS, true);
    }
}
