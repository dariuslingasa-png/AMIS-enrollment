<?php

namespace Database\Seeders;

use App\Models\EnrollmentApplicant;
use App\Models\Payment;
use App\Models\SchoolFee;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class TestGrade2BoysSeeder extends Seeder
{
    public function run(): void
    {
        SchoolFee::firstOrCreate(
            ['grade_level' => 'Grade 2', 'school_year' => '2026-2027'],
            ['tuition_fee' => 22000.00, 'misc_fee' => 1900.00, 'books_fee' => 3000.00]
        );

        $students = [
            ['first' => 'Abdurrahman', 'last' => 'Mercado'],
            ['first' => 'Faris',       'last' => 'Ocampo'],
            ['first' => 'Ghazi',       'last' => 'Pascual'],
            ['first' => 'Harith',      'last' => 'Quizon'],
            ['first' => 'Jabir',       'last' => 'Roxas'],
        ];

        foreach ($students as $i => $s) {
            $email = strtolower($s['first'] . '.' . str_replace(' ', '', $s['last'])) . '@gmail.com';

            $user = User::firstOrCreate(
                ['email' => $email],
                [
                    'name'              => $s['first'] . ' ' . $s['last'],
                    'username'          => strtolower($s['first'] . '.' . str_replace(' ', '', $s['last'])),
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
                    'learning_mode'          => 'Flexible Online Learning - 1st Shift',
                    'lrn'                    => 'NA',
                    'grade_level'            => 'Grade 2',
                    'first_name'             => $s['first'],
                    'last_name'              => $s['last'],
                    'middle_name'            => 'Test',
                    'gender'                 => 'Male',
                    'date_of_birth'          => '2018-' . str_pad($i + 1, 2, '0', STR_PAD_LEFT) . '-15',
                    'place_of_birth'         => 'Manila',
                    'religion'               => 'Islam',
                    'country'                => 'Philippines',
                    'address'                => '100 Test St, Manila',
                    'mobile_number'          => '091700005' . ($i + 1),
                    'parent_mobile'          => '091800005' . ($i + 1),
                    'parent_email'           => 'parent.' . strtolower($s['last']) . '5@gmail.com',
                    'emergency_name'         => 'Test Parent',
                    'emergency_relationship' => 'Father',
                    'emergency_phone'        => '091900005' . ($i + 1),
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

            $this->command->info("✓ {$s['first']} {$s['last']} ({$email} / password)");
        }

        $this->command->info('');
        $this->command->info('5 Grade 2 boys (Flexible 1st Shift) ready.');
        $this->command->info('First approval will auto-create G2 [Boys & 1st Shift] team.');
        $this->command->info('Remaining 4 will join the same team.');
    }
}
