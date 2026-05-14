<?php

namespace Database\Seeders;

use App\Models\EnrollmentApplicant;
use App\Models\Payment;
use App\Models\SchoolFee;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class TestKinder1Boys2Seeder extends Seeder
{
    public function run(): void
    {
        SchoolFee::firstOrCreate(
            ['grade_level' => 'Kinder 1', 'school_year' => '2026-2027'],
            ['tuition_fee' => 18000.00, 'misc_fee' => 1900.00, 'books_fee' => 2500.00]
        );

        $students = [
            ['first' => 'Bilal',    'last' => 'Navarro'],
            ['first' => 'Hamza',    'last' => 'Lim'],
            ['first' => 'Idris',    'last' => 'Tan'],
            ['first' => 'Mikail',   'last' => 'Cruz'],
            ['first' => 'Nuh',      'last' => 'Diaz'],
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
                    'grade_level'            => 'Kinder 1',
                    'first_name'             => $s['first'],
                    'last_name'              => $s['last'],
                    'middle_name'            => 'Test',
                    'gender'                 => 'Male',
                    'date_of_birth'          => '2020-' . str_pad($i + 1, 2, '0', STR_PAD_LEFT) . '-20',
                    'place_of_birth'         => 'Manila',
                    'religion'               => 'Islam',
                    'country'                => 'Philippines',
                    'address'                => '456 Test Ave, Manila',
                    'mobile_number'          => '091700002' . str_pad($i + 1, 2, '0', STR_PAD_LEFT),
                    'parent_mobile'          => '091800002' . str_pad($i + 1, 2, '0', STR_PAD_LEFT),
                    'parent_email'           => 'parent.' . strtolower($s['last']) . '2@gmail.com',
                    'emergency_name'         => 'Test Parent',
                    'emergency_relationship' => 'Father',
                    'emergency_phone'        => '091900002' . str_pad($i + 1, 2, '0', STR_PAD_LEFT),
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

            $this->command->info("✓ {$s['first']} {$s['last']} — Kinder 1 | Male | Flexible 1st Shift ({$email} / password)");
        }

        $this->command->info('');
        $this->command->info('5 more Kinder 1 boys (Flexible 1st Shift) ready for approval.');
        $this->command->info('They should all join the existing "Kinder 1 — HUSAYN IBN ALI" team.');
    }
}
