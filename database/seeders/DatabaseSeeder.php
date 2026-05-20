<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // User::factory(10)->create();

        User::updateOrCreate(['email' => 'test@example.com'], [
            'name' => 'Test User',
            'username' => User::where('email', 'test@example.com')->value('username') ?? User::makeUniqueUsername('test@example.com'),
            'role' => 'applicant',
            'account_status' => 'verified',
            'email_verified_at' => now(),
            'password' => Hash::make('password'),
        ]);

        $this->call([
            GradeLevelSeeder::class,
            EnrollmentShiftSeeder::class,
            GradeShiftSlotSeeder::class,
            SchoolFeesSeeder::class,
        ]);
    }
}
