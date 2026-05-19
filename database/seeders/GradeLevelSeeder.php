<?php

namespace Database\Seeders;

use App\Models\GradeLevel;
use Illuminate\Database\Seeder;

class GradeLevelSeeder extends Seeder
{
    public function run(): void
    {
        $grades = [
            ['name' => 'Kinder 1', 'sort_order' => 1, 'capacity' => 40],
            ['name' => 'Kinder 2', 'sort_order' => 2, 'capacity' => 40],
            ['name' => 'Grade 1', 'sort_order' => 3, 'capacity' => 40],
            ['name' => 'Grade 2', 'sort_order' => 4, 'capacity' => 40],
            ['name' => 'Grade 3', 'sort_order' => 5, 'capacity' => 40],
            ['name' => 'Grade 4', 'sort_order' => 6, 'capacity' => 40],
            ['name' => 'Grade 5', 'sort_order' => 7, 'capacity' => 40],
            ['name' => 'Grade 6', 'sort_order' => 8, 'capacity' => 40],
            ['name' => 'Grade 7', 'sort_order' => 9, 'capacity' => 40],
            ['name' => 'Grade 8', 'sort_order' => 10, 'capacity' => 40],
            ['name' => 'Grade 9', 'sort_order' => 11, 'capacity' => 40],
            ['name' => 'Grade 10', 'sort_order' => 12, 'capacity' => 40],
            ['name' => 'Grade 11', 'sort_order' => 13, 'capacity' => 40],
            ['name' => 'Grade 12', 'sort_order' => 14, 'capacity' => 40],
        ];

        foreach ($grades as $grade) {
            GradeLevel::updateOrCreate(
                ['name' => $grade['name'], 'school_year' => '2026-2027'],
                $grade
            );
        }
    }
}
