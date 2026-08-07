<?php

namespace App\Services\Enrollment;

use App\Models\EnrollmentApplicant;

class EnrollmentDuplicateChecker
{
    /**
     * Active application statuses that represent an active or submitted enrollment attempt.
     */
    public const ACTIVE_STATUSES = [
        'pending',
        'submitted',
        'under_review',
        'under_verification',
        'approved',
        'enrolled',
    ];

    /**
     * Find an active duplicate enrollment for the exact same normalized Full Name + Grade Level + School Year.
     *
     * @param array $data Form payload (first_name, last_name, middle_name, suffix, grade_level, school_year)
     * @param EnrollmentApplicant|int|null $currentApplicant The draft applicant being submitted
     * @return EnrollmentApplicant|null The matching active duplicate application, if found.
     */
    public static function findActiveDuplicate(array $data, EnrollmentApplicant|int|null $currentApplicant = null): ?EnrollmentApplicant
    {
        $firstName = self::normalizeString($data['first_name'] ?? '');
        $lastName = self::normalizeString($data['last_name'] ?? '');
        $middleName = self::normalizeString($data['middle_name'] ?? '');
        $suffix = self::normalizeString($data['suffix'] ?? '');
        $gradeLevel = self::normalizeGrade($data['grade_level'] ?? '');
        $schoolYear = self::normalizeString($data['school_year'] ?? '2026-2027');

        if (!$firstName || !$lastName || !$gradeLevel || !$schoolYear) {
            return null;
        }

        $currentId = $currentApplicant instanceof EnrollmentApplicant ? $currentApplicant->id : (int) $currentApplicant;

        return EnrollmentApplicant::query()
            ->when($currentId > 0, fn ($q) => $q->where('id', '!=', $currentId))
            ->whereIn('status', self::ACTIVE_STATUSES)
            ->whereRaw('LOWER(TRIM(school_year)) = ?', [$schoolYear])
            ->whereRaw('LOWER(TRIM(last_name)) = ?', [$lastName])
            ->whereRaw('LOWER(TRIM(first_name)) = ?', [$firstName])
            ->where(function ($query) use ($middleName) {
                if ($middleName === '') {
                    $query->whereNull('middle_name')
                        ->orWhereRaw('LOWER(TRIM(middle_name)) = \'\'');
                } else {
                    $query->whereRaw('LOWER(TRIM(middle_name)) = ?', [$middleName]);
                }
            })
            ->where(function ($query) use ($gradeLevel) {
                $query->whereRaw('LOWER(TRIM(grade_level)) = ?', [$gradeLevel]);
            })
            ->lockForUpdate()
            ->first();
    }

    public static function normalizeString(?string $str): string
    {
        if (blank($str)) {
            return '';
        }
        $normalized = preg_replace('/\s+/', ' ', trim($str));
        return strtolower($normalized);
    }

    public static function normalizeGrade(?string $grade): string
    {
        if (blank($grade)) {
            return '';
        }
        return strtolower(trim($grade));
    }
}
