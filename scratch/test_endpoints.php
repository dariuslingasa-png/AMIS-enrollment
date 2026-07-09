<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Http\Controllers\EnrollmentController;
use Illuminate\Http\Request;
use App\Models\User;

// Find applicant user
$user = User::find(16);
Auth::login($user);

$controller = app(EnrollmentController::class);

// Create request
$request = Request::create('/enroll/search-old-student', 'POST', [
    'student_number' => '260001',
    'date_of_birth' => '2011-05-20'
]);

$response = $controller->searchOldStudent($request);
echo "=== TESTING ENROLLMENT SEARCH ENDPOINT ===" . PHP_EOL;
echo "Status Code: " . $response->getStatusCode() . PHP_EOL;
$data = json_decode($response->getContent(), true);
echo "Success: " . ($data['success'] ? 'YES' : 'NO') . PHP_EOL;
echo "Message: " . $data['message'] . PHP_EOL;
echo "Auto-filled First Name: " . ($data['student']['first_name'] ?? 'N/A') . PHP_EOL;
echo "Auto-filled Last Name: " . ($data['student']['last_name'] ?? 'N/A') . PHP_EOL;
echo "Auto-filled Address: " . ($data['student']['address'] ?? 'N/A') . PHP_EOL;
echo "Auto-filled Father Occupation: " . ($data['student']['father_occupation'] ?? 'N/A') . PHP_EOL;
echo "---" . PHP_EOL;

// Test with wrong DOB
$requestWrong = Request::create('/enroll/search-old-student', 'POST', [
    'student_number' => '260001',
    'date_of_birth' => '2011-05-21'
]);
try {
    $responseWrong = $controller->searchOldStudent($requestWrong);
    echo "Status Code (Wrong DOB): " . $responseWrong->getStatusCode() . PHP_EOL;
    echo "Content: " . $responseWrong->getContent() . PHP_EOL;
} catch (\Illuminate\Validation\ValidationException $e) {
    echo "Validation failed: " . json_encode($e->errors()) . PHP_EOL;
} catch (\Exception $e) {
    echo "Error (expected): " . $e->getMessage() . PHP_EOL;
}
