<?php
/**
 * AMIS Enrollment DB Query Tool
 */
require __DIR__.'/../vendor/autoload.php';
$app = require_once __DIR__.'/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use Illuminate\Support\Facades\DB;

$action = $_GET['action'] ?? 'find';
$studentNumber = $_GET['student_number'] ?? '260401';
$newGrade = $_GET['new_grade'] ?? 'Grade 2';

header('Content-Type: application/json; charset=utf-8');

if ($action === 'find') {
    $student = DB::table('students')->where('student_number', $studentNumber)->first();
    $applicant = null;
    if ($student && isset($student->enrollment_applicant_id) && $student->enrollment_applicant_id) {
        $applicant = DB::table('enrollment_applicants')->where('id', $student->enrollment_applicant_id)->first();
    } else {
        // Search by name fallback
        $applicant = DB::table('enrollment_applicants')
            ->where('first_name', 'like', '%Ayesha%')
            ->where('last_name', 'like', '%Mindo%')
            ->first();
    }
    
    echo json_encode([
        'status' => 'success',
        'student' => $student,
        'applicant' => $applicant
    ], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
} elseif ($action === 'update') {
    // Perform update
    $studentUpdated = DB::table('students')
        ->where('student_number', $studentNumber)
        ->update(['grade_level' => $newGrade]);
        
    $student = DB::table('students')->where('student_number', $studentNumber)->first();
    $applicantUpdated = 0;
    
    if ($student && isset($student->enrollment_applicant_id) && $student->enrollment_applicant_id) {
        $applicantUpdated = DB::table('enrollment_applicants')
            ->where('id', $student->enrollment_applicant_id)
            ->update(['grade_level' => $newGrade]);
    } else {
        $applicantUpdated = DB::table('enrollment_applicants')
            ->where('first_name', 'like', '%Ayesha%')
            ->where('last_name', 'like', '%Mindo%')
            ->update(['grade_level' => $newGrade]);
    }
    
    echo json_encode([
        'status' => 'success',
        'student_updated_rows' => $studentUpdated,
        'applicant_updated_rows' => $applicantUpdated,
        'new_grade' => $newGrade
    ], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
} else {
    echo json_encode(['status' => 'error', 'message' => 'Invalid action']);
}
