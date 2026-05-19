<?php

namespace Database\Seeders;

use App\Models\EnrollmentShift;
use App\Models\GradeLevel;
use App\Models\GradeShiftSlot;
use Illuminate\Database\Seeder;

class GradeShiftSlotSeeder extends Seeder
{
    public function run(): void
    {
        $grades = GradeLevel::where('school_year', '2026-2027')->get();
        $shifts = EnrollmentShift::where('school_year', '2026-2027')->get();

        foreach ($grades as $grade) {
            foreach ($shifts as $shift) {
                GradeShiftSlot::updateOrCreate(
                    [
                        'grade_level_id' => $grade->id,
                        'enrollment_shift_id' => $shift->id,
                        'school_year' => '2026-2027',
                    ],
                    [
                        'capacity' => 40,
                        'enrolled_count' => 0,
                    ]
                );
            }
        }
    }
}
