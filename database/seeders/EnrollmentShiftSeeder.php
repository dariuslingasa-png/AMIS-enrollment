<?php

namespace Database\Seeders;

use App\Models\EnrollmentShift;
use Illuminate\Database\Seeder;

class EnrollmentShiftSeeder extends Seeder
{
    public function run(): void
    {
        $shifts = [
            [
                'name' => '1st Shift',
                'start_time' => '12:40',
                'end_time' => '15:00',
                'capacity' => 40,
                'enrolled_count' => 0,
            ],
            [
                'name' => '2nd Shift',
                'start_time' => '15:40',
                'end_time' => '18:00',
                'capacity' => 40,
                'enrolled_count' => 0,
            ],
        ];

        foreach ($shifts as $shift) {
            EnrollmentShift::updateOrCreate(
                ['name' => $shift['name'], 'school_year' => '2026-2027'],
                $shift
            );
        }
    }
}
