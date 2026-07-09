<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Models\Student;

$students = Student::with('applicant')->take(5)->get();
foreach ($students as $student) {
    echo "Student Number: " . $student->student_number . PHP_EOL;
    echo "DOB: " . ($student->applicant->date_of_birth ? $student->applicant->date_of_birth->format('Y-m-d') : 'None') . PHP_EOL;
    echo "Name: " . ($student->applicant->first_name ?? 'None') . " " . ($student->applicant->last_name ?? 'None') . PHP_EOL;
    echo "User ID: " . ($student->user_id ?? 'NULL') . PHP_EOL;
    echo "---" . PHP_EOL;
}
