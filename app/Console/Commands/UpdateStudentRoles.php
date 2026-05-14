<?php

namespace App\Console\Commands;

use App\Models\Student;
use Illuminate\Console\Command;

class UpdateStudentRoles extends Command
{
    protected $signature   = 'students:update-roles';
    protected $description = 'Set role=student for all users who have a student record';

    public function handle(): void
    {
        $students = Student::with('applicant.user')->get();
        $count = 0;

        foreach ($students as $student) {
            $user = $student->applicant?->user;
            if ($user && $user->role !== 'student') {
                $user->update(['role' => 'student']);
                $this->info("✓ {$user->email} → role=student");
                $count++;
            }
        }

        $this->info("\nDone. {$count} user(s) updated.");
    }
}
