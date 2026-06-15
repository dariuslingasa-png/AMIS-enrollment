<?php

require '/home/tatsuya/Projects/AMIS/amis_enrollment/vendor/autoload.php';
$app = require_once '/home/tatsuya/Projects/AMIS/amis_enrollment/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use App\Models\EnrollmentApplicant;
use App\Models\User;
use Illuminate\Support\Facades\Auth;

try {
    $user = User::first();
    if (!$user) {
        echo "No users found in database.\n";
        exit;
    }
    Auth::login($user);
    echo "Logged in as user: " . $user->email . "\n";

    // Bind errors globally
    view()->share('errors', new \Illuminate\Support\ViewErrorBag());

    $applicant = EnrollmentApplicant::first();
    if (!$applicant) {
        echo "No applicants found in the database at all.\n";
        exit;
    }

    echo "Rendering enrollment.payment view for applicant ID " . $applicant->id . "...\n";
    
    $html = view('enrollment.payment', [
        'applicant' => $applicant,
        'payment' => $applicant->payment,
        'invoiceApplicants' => collect([$applicant]),
    ])->render();
    
    echo "SUCCESSFULLY rendered view! Length of output: " . strlen($html) . "\n";

} catch (\Throwable $e) {
    echo "ERROR caught:\n";
    echo $e->getMessage() . "\n";
    echo $e->getFile() . ":" . $e->getLine() . "\n";
    echo $e->getTraceAsString() . "\n";
}
