<?php

namespace App\Services;

use App\Models\EnrollmentApplicant;
use App\Models\SchoolFee;
use App\Models\SoaMonthlyBilling;
use App\Models\Student;
use App\Models\StudentAccount;

class SoaService
{
    /**
     * Generate SOA + 10 monthly billing rows for a newly approved student.
     */
    public function generate(Student $student, EnrollmentApplicant $applicant): StudentAccount
    {
        $fee = SchoolFee::forGrade($applicant->grade_level, $applicant->school_year);

        if (!$fee) {
            throw new \Exception("No school fees found for {$applicant->grade_level} SY {$applicant->school_year}");
        }

        $tuition            = (float) $fee->tuition_fee;
        $misc               = (float) $fee->misc_fee;
        $books              = (float) $fee->books_fee;
        $discountPercentage = (float) ($applicant->discount_percentage ?? 0);
        $discountAmount     = (float) ($applicant->discount_amount ?? 0);

        if ($discountPercentage > 0 && $discountAmount <= 0) {
            $discountAmount = round($tuition * ($discountPercentage / 100), 2);
            $applicant->update(['discount_amount' => $discountAmount]);
        }

        $discountedTuition = max(0, $tuition - $discountAmount);
        $monthlyTuition    = round($discountedTuition / 10, 2);
        $gross             = $discountedTuition + $misc + $books;
        $enrollPaid     = 4000.00;
        $totalBalance   = $gross - $enrollPaid;

        $account = StudentAccount::create([
            'student_id'              => $student->id,
            'enrollment_applicant_id' => $applicant->id,
            'school_year'             => $applicant->school_year,
            'grade_level'             => $applicant->grade_level,
            'tuition_fee'             => $tuition,
            'monthly_tuition'         => $monthlyTuition,
            'miscellaneous_fee'       => $misc,
            'books_fee'               => $books,
            'sibling_order'           => $applicant->sibling_order,
            'discount_type'           => $discountPercentage > 0 ? ($applicant->discount_type ?: 'sibling') : null,
            'discount_percentage'     => $discountPercentage,
            'discount_amount'         => $discountAmount,
            'gross_total'             => $gross,
            'enrollment_fee_paid'     => $enrollPaid,
            'total_balance'           => $totalBalance,
            'amount_paid'             => 0.00,
            'remaining_balance'       => $totalBalance,
            'status'                  => 'unpaid',
        ]);

        // Generate 10 monthly billing rows (June=1 to March=10)
        $this->generateMonthlyBillings($account, $student, $monthlyTuition, $misc, $books, $applicant->school_year);

        return $account;
    }

    private function generateMonthlyBillings(
        StudentAccount $account,
        Student $student,
        float $monthlyTuition,
        float $misc,
        float $books,
        string $schoolYear
    ): void {
        $startYear = (int) explode('-', $schoolYear)[0]; // 2026

        $months = [
            1  => ['June',      "{$startYear}-06-15", $monthlyTuition + $misc + $books, 'Tuition + Miscellaneous + Books'],
            2  => ['July',      "{$startYear}-07-15", $monthlyTuition, 'Monthly Tuition'],
            3  => ['August',    "{$startYear}-08-15", $monthlyTuition, 'Monthly Tuition'],
            4  => ['September', "{$startYear}-09-15", $monthlyTuition, 'Monthly Tuition'],
            5  => ['October',   "{$startYear}-10-15", $monthlyTuition, 'Monthly Tuition'],
            6  => ['November',  "{$startYear}-11-15", $monthlyTuition, 'Monthly Tuition'],
            7  => ['December',  "{$startYear}-12-15", $monthlyTuition, 'Monthly Tuition'],
            8  => ['January',   ($startYear + 1) . '-01-15', $monthlyTuition, 'Monthly Tuition'],
            9  => ['February',  ($startYear + 1) . '-02-15', $monthlyTuition, 'Monthly Tuition'],
            10 => ['March',     ($startYear + 1) . '-03-15', $monthlyTuition, 'Monthly Tuition'],
        ];

        foreach ($months as $num => [$name, $due, $amount, $desc]) {
            SoaMonthlyBilling::create([
                'student_account_id' => $account->id,
                'student_id'         => $student->id,
                'month_number'       => $num,
                'month_name'         => $name,
                'due_date'           => $due,
                'amount_due'         => $amount,
                'description'        => $desc,
                'status'             => 'unpaid',
            ]);
        }
    }
}
