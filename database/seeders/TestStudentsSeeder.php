<?php

namespace Database\Seeders;

use App\Models\EnrollmentApplicant;
use App\Models\Payment;
use App\Models\Student;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class TestStudentsSeeder extends Seeder
{
    public function run(): void
    {
        // ── Student 1: Officially Enrolled (approved + student record) ──────
        $user1 = User::firstOrCreate(
            ['email' => 'student1@test.com'],
            [
                'name'             => 'student1',
                'username'         => 'student1',
                'password'         => Hash::make('password'),
                'role'             => 'applicant',
                'account_status'   => 'verified',
                'email_verified_at'=> now(),
            ]
        );

        $app1 = EnrollmentApplicant::updateOrCreate(
            ['user_id' => $user1->id],
            [
                'student_type'           => 'New',
                'learning_mode'          => 'Face-to-Face',
                'lrn'                    => 'NA',
                'grade_level'            => 'Grade 7',
                'first_name'             => 'Maria',
                'last_name'              => 'Santos',
                'middle_name'            => 'Cruz',
                'gender'                 => 'Female',
                'date_of_birth'          => '2010-03-15',
                'place_of_birth'         => 'Manila',
                'religion'               => 'Islam',
                'country'                => 'Philippines',
                'address'                => '123 Rizal St, Manila',
                'mobile_number'          => '09171234567',
                'parent_mobile'          => '09181234567',
                'parent_email'           => 'parent1@test.com',
                'emergency_name'         => 'Jose Santos',
                'emergency_relationship' => 'Father',
                'emergency_phone'        => '09191234567',
                'school_year'            => '2026-2027',
                'status'                 => 'approved',
                'last_step'              => 5,
                'document_statuses'      => [
                    'photo_2x2'  => 'approved',
                    'birth_cert' => 'approved',
                    'report_card'=> 'approved',
                ],
            ]
        );

        Payment::updateOrCreate(
            ['enrollment_applicant_id' => $app1->id],
            [
                'user_id'     => $user1->id,
                'method'      => 'gcash',
                'amount'      => 4000.00,
                'status'      => 'verified',
                'paid_at'     => now()->subDays(3),
                'verified_at' => now()->subDays(2),
            ]
        );

        // Generate student number
        $year     = substr(date('Y'), 2);
        $seq      = Student::whereYear('created_at', date('Y'))->count() + 1;
        $studNum  = $year . str_pad($seq, 4, '0', STR_PAD_LEFT);
        $lastName = strtolower(preg_replace('/\s+/', '', $app1->last_name));

        Student::updateOrCreate(
            ['enrollment_applicant_id' => $app1->id],
            [
                'user_id'             => $user1->id,
                'student_number'      => $studNum,
                'school_email'        => $studNum . $lastName . '@amis.edu.ph',
                'temp_password'       => Hash::make('Amis@TEST01'),
                'grade_level'         => $app1->grade_level,
                'school_year'         => $app1->school_year,
                'credentials_sent_at' => now(),
            ]
        );

        $this->command->info("✓ Student 1 created: {$studNum}santos@amis.edu.ph (approved+enrolled)");

        // ── Student 2: Rejected — needs to re-upload documents ───────────────
        $user2 = User::firstOrCreate(
            ['email' => 'student2@test.com'],
            [
                'name'             => 'student2',
                'username'         => 'student2',
                'password'         => Hash::make('password'),
                'role'             => 'applicant',
                'account_status'   => 'verified',
                'email_verified_at'=> now(),
            ]
        );

        $app2 = EnrollmentApplicant::updateOrCreate(
            ['user_id' => $user2->id],
            [
                'student_type'           => 'Old',
                'learning_mode'          => 'Flexible Online Learners',
                'lrn'                    => 'NA',
                'grade_level'            => 'Grade 10',
                'first_name'             => 'Ahmad',
                'last_name'              => 'Reyes',
                'middle_name'            => 'Ali',
                'gender'                 => 'Male',
                'date_of_birth'          => '2008-07-22',
                'place_of_birth'         => 'Quezon City',
                'religion'               => 'Islam',
                'country'                => 'Philippines',
                'address'                => '456 Mabini Ave, QC',
                'mobile_number'          => '09271234567',
                'parent_mobile'          => '09281234567',
                'parent_email'           => 'parent2@test.com',
                'emergency_name'         => 'Fatima Reyes',
                'emergency_relationship' => 'Mother',
                'emergency_phone'        => '09291234567',
                'school_year'            => '2026-2027',
                'status'                 => 'rejected',
                'last_step'              => 5,
                'document_statuses'      => [
                    'photo_2x2'  => 'rejected',
                    'birth_cert' => 'approved',
                    'report_card'=> 'rejected',
                ],
            ]
        );

        Payment::updateOrCreate(
            ['enrollment_applicant_id' => $app2->id],
            [
                'user_id'  => $user2->id,
                'method'   => 'maya',
                'amount'   => 4000.00,
                'status'   => 'verified',
                'paid_at'  => now()->subDays(5),
                'verified_at' => now()->subDays(4),
            ]
        );

        $this->command->info('✓ Student 2 created: student2@test.com (rejected — 2x2 & report card rejected)');
        $this->command->info('');
        $this->command->info('Login credentials:');
        $this->command->info('  Student 1 (Enrolled): student1@test.com / password');
        $this->command->info('  Student 2 (Rejected): student2@test.com / password');
    }
}
