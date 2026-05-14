<?php

namespace Database\Seeders;

use App\Models\EnrollmentApplicant;
use App\Models\Payment;
use App\Models\SchoolFee;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class TestKinder1GirlsSeeder extends Seeder
{
    public function run(): void
    {
        // Ensure school fee exists for Kinder 1
        SchoolFee::firstOrCreate(
            ['grade_level' => 'Kinder 1', 'school_year' => '2026-2027'],
            ['tuition_fee' => 18000.00, 'misc_fee' => 1900.00, 'books_fee' => 2500.00]
        );

        $students = [
            ['first' => 'Aisha',   'last' => 'Reyes',    'email' => 'aisha.reyes@gmail.com',    'parent' => 'parent.reyes@gmail.com'],
            ['first' => 'Fatima',  'last' => 'Santos',   'email' => 'fatima.santos@gmail.com',  'parent' => 'parent.santos@gmail.com'],
            ['first' => 'Maryam',  'last' => 'Garcia',   'email' => 'maryam.garcia@gmail.com',  'parent' => 'parent.garcia@gmail.com'],
            ['first' => 'Khadija', 'last' => 'Mendoza',  'email' => 'khadija.mendoza@gmail.com','parent' => 'parent.mendoza@gmail.com'],
        ];

        foreach ($students as $i => $s) {
            $user = User::firstOrCreate(
                ['email' => $s['email']],
                [
                    'name'              => $s['first'] . ' ' . $s['last'],
                    'username'          => strtolower($s['first'] . '.' . $s['last']),
                    'password'          => Hash::make('password'),
                    'role'              => 'applicant',
                    'account_status'    => 'verified',
                    'email_verified_at' => now(),
                ]
            );

            $app = EnrollmentApplicant::updateOrCreate(
                ['user_id' => $user->id],
                [
                    'student_type'           => 'New',
                    'learning_mode'          => 'Face-to-Face',
                    'lrn'                    => 'NA',
                    'grade_level'            => 'Kinder 1',
                    'first_name'             => $s['first'],
                    'last_name'              => $s['last'],
                    'middle_name'            => 'Test',
                    'gender'                 => 'Female',
                    'date_of_birth'          => '2020-0' . ($i + 1) . '-15',
                    'place_of_birth'         => 'Manila',
                    'religion'               => 'Islam',
                    'country'                => 'Philippines',
                    'address'                => '123 Test St, Manila',
                    'mobile_number'          => '0917000000' . ($i + 1),
                    'parent_mobile'          => '0918000000' . ($i + 1),
                    'parent_email'           => $s['parent'],
                    'emergency_name'         => 'Test Parent',
                    'emergency_relationship' => 'Mother',
                    'emergency_phone'        => '0919000000' . ($i + 1),
                    'school_year'            => '2026-2027',
                    'status'                 => 'submitted',
                    'last_step'              => 5,
                    'document_statuses'      => [
                        'photo_2x2'   => 'approved',
                        'birth_cert'  => 'approved',
                        'report_card' => 'approved',
                    ],
                ]
            );

            Payment::updateOrCreate(
                ['enrollment_applicant_id' => $app->id],
                [
                    'user_id'     => $user->id,
                    'method'      => 'gcash',
                    'amount'      => 4000.00,
                    'status'      => 'verified',
                    'paid_at'     => now()->subDays(2),
                    'verified_at' => now()->subDays(1),
                ]
            );

            $this->command->info("✓ {$s['first']} {$s['last']} — ready for approval ({$s['email']} / password)");
        }

        $this->command->info('');
        $this->command->info('All 4 Kinder 1 girls are ready. Go to admin → Applicants and approve them.');
    }
}
