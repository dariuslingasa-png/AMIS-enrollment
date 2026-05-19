<?php

namespace App\Services\Enrollment;

use App\Models\DiscountSetting;
use App\Models\EnrollmentApplicant;
use App\Models\SchoolFee;
use App\Models\User;

class SiblingDiscountService
{
    public const ELIGIBLE_STATUSES = [
        'ready_for_submission',
        'pending',
        'submitted',
        'under_review',
        'approved',
    ];

    public function apply(User $user, EnrollmentApplicant $applicant): void
    {
        $siblingOrder = $this->siblingOrderFor($user, $applicant);
        $setting = DiscountSetting::current();
        $percentage = $setting->siblingPercentageForOrder($siblingOrder);
        $discountAmount = 0.0;

        if ($percentage > 0 && $applicant->grade_level && $applicant->school_year) {
            $fee = SchoolFee::forGrade($applicant->grade_level, $applicant->school_year);
            $discountAmount = $fee ? round(((float) $fee->tuition_fee) * ($percentage / 100), 2) : 0.0;
        }

        $applicant->update([
            'sibling_order' => $siblingOrder,
            'discount_type' => $percentage > 0 ? 'sibling' : null,
            'discount_percentage' => $percentage,
            'discount_amount' => $discountAmount,
        ]);
    }

    public function siblingOrderFor(User $user, ?EnrollmentApplicant $applicant = null): int
    {
        $query = $user->enrollmentApplicants()->whereIn('status', self::ELIGIBLE_STATUSES);

        if ($applicant?->id) {
            $query->whereKeyNot($applicant->id);
        }

        return $query->count() + 1;
    }
}
