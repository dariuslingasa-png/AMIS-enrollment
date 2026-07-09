<?php

// Bootstrap Laravel
require __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Http\Controllers\IdVerificationController;

$controller = new IdVerificationController();

// We will use reflection to test private/protected methods
$refClass = new ReflectionClass(IdVerificationController::class);

$nameMatches = $refClass->getMethod('nameMatches');
$nameMatches->setAccessible(true);

$normalizeAmisId = $refClass->getMethod('normalizeAmisId');
$normalizeAmisId->setAccessible(true);

$searchCsv = $refClass->getMethod('searchCsv');
$searchCsv->setAccessible(true);

// 1. Test name matching
echo "Testing name matching...\n";

$m1 = $nameMatches->invoke($controller, 'Khayyir Kunam', 'KHAYYIR A. KUNAM');
$m2 = $nameMatches->invoke($controller, 'khayyir a kunam', 'KHAYYIR A. KUNAM');
$m3 = $nameMatches->invoke($controller, 'Aayan Abdulwahab', 'AAYAN C. ABDULWAHAB');
$m4 = $nameMatches->invoke($controller, 'Wrong Name', 'KHAYYIR A. KUNAM');

echo "Match 'Khayyir Kunam' with 'KHAYYIR A. KUNAM': " . ($m1 ? 'PASS' : 'FAIL') . "\n";
echo "Match 'khayyir a kunam' with 'KHAYYIR A. KUNAM': " . ($m2 ? 'PASS' : 'FAIL') . "\n";
echo "Match 'Aayan Abdulwahab' with 'AAYAN C. ABDULWAHAB': " . ($m3 ? 'PASS' : 'FAIL') . "\n";
echo "Match 'Wrong Name' with 'KHAYYIR A. KUNAM': " . (!$m4 ? 'PASS' : 'FAIL') . "\n";

if ($m1 && $m2 && $m3 && !$m4) {
    echo "✅ Name matching tests passed!\n\n";
} else {
    echo "❌ Name matching tests failed!\n\n";
    exit(1);
}

// 2. Test ID normalization
echo "Testing ID normalization...\n";
$n1 = $normalizeAmisId->invoke($controller, 'AMIS-2026-000246');
$n2 = $normalizeAmisId->invoke($controller, '260246');
$n3 = $normalizeAmisId->invoke($controller, 'amis-2026-000246');

echo "Normalize 'AMIS-2026-000246' (expected 260246): " . ($n1 === '260246' ? 'PASS' : "FAIL (got $n1)") . "\n";
echo "Normalize '260246' (expected 260246): " . ($n2 === '260246' ? 'PASS' : "FAIL (got $n2)") . "\n";
echo "Normalize 'amis-2026-000246' (expected 260246): " . ($n3 === '260246' ? 'PASS' : "FAIL (got $n3)") . "\n";

if ($n1 === '260246' && $n2 === '260246' && $n3 === '260246') {
    echo "✅ ID normalization tests passed!\n\n";
} else {
    echo "❌ ID normalization tests failed!\n\n";
    exit(1);
}

// 3. Test CSV searching
echo "Testing CSV search...\n";
$record = $searchCsv->invoke($controller, '260246');
if ($record) {
    echo "Found record for ID 260246:\n";
    print_r($record);
    if ($record['full_name'] === 'KHAYYIR A. KUNAM' && $record['grade_level'] === 'Kinder 1') {
        echo "✅ CSV search test passed!\n\n";
    } else {
        echo "❌ CSV search record details mismatch!\n\n";
        exit(1);
    }
} else {
    echo "❌ CSV search failed to find ID 260246!\n\n";
    exit(1);
}

// 4. Test verify function with mock request
echo "Testing verify controller method...\n";
$request = new \Illuminate\Http\Request();
$request->replace([
    'student_id' => 'AMIS-2026-000246',
    'full_name' => 'Khayyir Kunam',
    'grade_level' => 'Kinder 1',
    'school_year' => '2026-2027'
]);

$response = $controller->verify($request);
$data = $response->getData(true);

if (isset($data['success']) && $data['success'] === true) {
    echo "Verify response data:\n";
    print_r($data);
    echo "✅ Controller verification test passed!\n\n";
} else {
    echo "❌ Controller verification failed!\n";
    print_r($data);
    exit(1);
}

echo "🎉 ALL TESTS PASSED SUCCESSFULLY! 🎉\n";
