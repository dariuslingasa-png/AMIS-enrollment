<?php

// Bootstrap Laravel Console
require __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

$id = 874;
$applicant = \App\Models\EnrollmentApplicant::find($id);

if (!$applicant) {
    echo "Applicant #{$id} NOT found in database.\n";
    exit(1);
}

echo "Found Applicant:\n";
echo " - ID: {$applicant->id}\n";
echo " - Name: {$applicant->first_name} {$applicant->middle_name} {$applicant->last_name}\n";
echo " - Email: {$applicant->email}\n";
echo " - Family ID: {$applicant->family_application_id}\n";
echo " - Status: {$applicant->status}\n\n";

// Check related records
$payment = \Illuminate\Support\Facades\DB::table('payments')->where('enrollment_applicant_id', $id)->first();
if ($payment) {
    echo "Related Payment Record found:\n";
    echo " - ID: {$payment->id}\n";
    echo " - Reference No: {$payment->reference_number}\n";
    echo " - Amount: {$payment->amount}\n\n";
} else {
    echo "No related payment record found.\n";
}

$student = \Illuminate\Support\Facades\DB::table('students')->where('enrollment_applicant_id', $id)->first();
if ($student) {
    echo "Related Student Record found:\n";
    echo " - ID: {$student->id}\n";
    echo " - Amis Student ID: " . ($student->amis_student_id ?? 'N/A') . "\n\n";
} else {
    echo "No related student record found.\n";
}
