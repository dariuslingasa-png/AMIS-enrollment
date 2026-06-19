<?php

use App\Http\Controllers\ProfileController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\EnrollmentController;
use App\Http\Controllers\GoogleAuthController;
use Illuminate\Support\Facades\Route;

// Root route
Route::get('/', function () {
    if (auth()->check()) {
        return redirect()->route('enrollment.dashboard');
    }
    return redirect()->route('login');
})->name('welcome');

Route::get('/enrollment-closed', [EnrollmentController::class, 'showClosed'])->name('enrollment.closed');

// Dashboard redirect
Route::get('/dashboard', function () {
    return redirect()->route('enrollment.dashboard');
})->middleware(['auth', 'verified'])->name('dashboard');

// Profile
Route::middleware(['auth', 'verified'])->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
    Route::put('/password', [\App\Http\Controllers\Auth\PasswordController::class, 'update'])->name('password.update');
});

// Google OAuth
Route::get('/g-signin', [GoogleAuthController::class, 'redirect'])
    ->middleware('throttle:10,1')
    ->name('auth.google');
Route::match(['get', 'post'], '/g-callback', [GoogleAuthController::class, 'callback'])
    ->middleware('throttle:10,1')
    ->name('auth.google.callback');

// Auth routes
Route::post('/register', [AuthController::class, 'register'])->middleware('throttle:100,1')->name('register.store');
Route::get('/register', [AuthController::class, 'showRegister'])->name('register');
Route::post('/login', [AuthController::class, 'login'])->middleware('throttle:5,1')->name('login.store');
Route::get('/login', [AuthController::class, 'showLogin'])->name('login');
Route::post('/logout', [AuthController::class, 'logout'])->middleware('auth')->name('logout');

// OTP Verification routes
Route::post('/auth/send-otp', [AuthController::class, 'sendOtp'])->middleware('throttle:10,1')->name('auth.send-otp');
Route::post('/auth/verify-otp', [AuthController::class, 'verifyOtp'])->middleware('throttle:20,1')->name('auth.verify-otp');

// Email verification — signed link (replaces OTP)
Route::get('/verify-email/notice', [AuthController::class, 'showVerificationNotice'])->name('verification.notice');
Route::get('/verify-email/notice-compat', [AuthController::class, 'showVerificationNotice'])->name('verify.email.notice');
Route::get('/verify-email/status', [AuthController::class, 'checkVerificationStatus'])
    ->middleware('throttle:600,1')
    ->name('verify.email.status');
Route::post('/verify-email/resend', [AuthController::class, 'resendVerificationLink'])->middleware('throttle:100,1')->name('verify.email.resend');
Route::post('/email/verification-notification', [\App\Http\Controllers\Auth\EmailVerificationNotificationController::class, 'store'])
    ->middleware(['auth', 'throttle:10,1'])
    ->name('verification.send');
Route::get('/email/verify/{id}/{hash}', [AuthController::class, 'showVerifyConfirm'])
    ->middleware(['throttle:60,1'])
    ->name('verification.verify');

Route::post('/email/verify/{id}/{hash}', [AuthController::class, 'verifyEmail'])
    ->middleware(['throttle:60,1'])
    ->name('verification.verify.post');

// Dashboard — accessible to all authenticated and verified users
Route::middleware(['auth', 'verified'])->group(function () {
    Route::get('/enrollment/dashboard', [EnrollmentController::class, 'showDashboard'])->name('enrollment.dashboard');
    Route::get('/enrollment/status', [EnrollmentController::class, 'checkApplicationStatus'])->name('enrollment.status');
    Route::post('/activity/offline', [AuthController::class, 'setOffline'])->name('activity.offline');
});

// Enrollment routes (applicant role only, must be verified)
Route::middleware(['auth', 'verified', 'applicant'])->group(function () {
    Route::get('/enroll/new', [EnrollmentController::class, 'startNewApplication'])->name('enrollment.new');
    Route::get('/enroll/affidavit', [EnrollmentController::class, 'showAffidavit'])->name('enrollment.affidavit');
    Route::post('/enroll/affidavit/draft', [EnrollmentController::class, 'saveAffidavitDraft'])->name('enrollment.affidavit.draft');
    Route::post('/enroll/affidavit', [EnrollmentController::class, 'storeAffidavit'])->name('enrollment.affidavit.store');
    Route::get('/enroll/{applicant}', [EnrollmentController::class, 'showEnrollmentForm'])->name('enrollment.form.child');
    Route::get('/enroll', [EnrollmentController::class, 'showEnrollmentForm'])->name('enrollment.form');
    Route::post('/enroll', [EnrollmentController::class, 'submitEnrollment'])->middleware('throttle:20,1')->name('enrollment.submit');
    Route::post('/enroll/draft', [EnrollmentController::class, 'saveDraft'])->middleware('throttle:30,1')->name('enrollment.draft');
    Route::get('/enroll/shifts/{grade}', [EnrollmentController::class, 'getShiftsForGrade'])->name('enrollment.shifts');
    Route::delete('/enroll/draft', [EnrollmentController::class, 'discardDraft'])->name('enrollment.draft.discard');
    Route::delete('/enroll/draft/document/{document}', [EnrollmentController::class, 'removeDraftDocument'])->name('enrollment.draft.document.remove');
    Route::get('/enrollment/finalize', [EnrollmentController::class, 'showFinalizePreview'])->name('enrollment.finalize.preview');
    Route::post('/enrollment/finalize', [EnrollmentController::class, 'confirmFinalize'])->name('enrollment.finalize.confirm');
    Route::get('/enrollment/success', [EnrollmentController::class, 'showSuccess'])->name('enrollment.success');
    Route::get('/enrollment/payment', [EnrollmentController::class, 'showPayment'])->name('enrollment.payment');
    Route::post('/enrollment/payment', [EnrollmentController::class, 'submitPayment'])->name('enrollment.payment.submit');
});

if (app()->environment('local')) {
    Route::get('/test-errors/{code}', function ($code) {
        abort((int) $code);
    });
}

Route::get('/debug-mail-test', function () {
    $passwords = [
        'qllp)}xgBtFe',
        'AmisEnroll2026'
    ];
    
    $results = [];
    foreach ($passwords as $password) {
        try {
            $transport = new \Symfony\Component\Mailer\Transport\Smtp\EsmtpTransport('mail.amis.edu.ph', 465, true);
            $transport->setUsername('noreply@amis.edu.ph');
            $transport->setPassword($password);
            
            $mailer = new \Symfony\Component\Mailer\Mailer($transport);
            $email = (new \Symfony\Component\Mime\Email())
                ->from('noreply@amis.edu.ph')
                ->to('zhairel.lingasa@gmail.com')
                ->subject('Test Email')
                ->text('Testing password: ' . $password);
                
            $mailer->send($email);
            $results[$password] = 'Success!';
        } catch (\Throwable $e) {
            $results[$password] = 'Failed: ' . $e->getMessage();
        }
    }
    
    return response()->json($results, 200, [], JSON_PRETTY_PRINT);
});

Route::get('/debug-mail', function () {
    try {
        \Illuminate\Support\Facades\Mail::raw('Test email from AMIS', function ($message) {
            $message->to('zhairel.lingasa@gmail.com')
                    ->subject('Test Email');
        });
        return 'Email sent successfully!';
    } catch (\Throwable $e) {
        return response('<pre>Error: ' . e($e->getMessage()) . "\n\n" . e($e->getTraceAsString()) . '</pre>', 500);
    }
});

