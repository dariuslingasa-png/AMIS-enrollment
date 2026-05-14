<?php

use App\Http\Controllers\ProfileController;
use App\Http\Controllers\MicrosoftAuthController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\EnrollmentController;
use Illuminate\Support\Facades\Route;

// Root route - redirect to enrollment portal
Route::get('/', function () {
    if (auth()->check()) {
        return redirect()->route('enrollment.dashboard');
    }
    return redirect()->route('login');
})->name('welcome');

Route::get('/enrollment-closed', [EnrollmentController::class, 'showClosed'])->name('enrollment.closed');

// Dashboard (redirect to enrollment dashboard)
Route::get('/dashboard', function () {
    return redirect()->route('enrollment.dashboard');
})->middleware(['auth'])->name('dashboard');

// Profile
Route::middleware('auth')->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});

// Microsoft Azure AD SSO
Route::get('/auth/microsoft', [MicrosoftAuthController::class, 'redirect'])->name('auth.microsoft');
Route::get('/auth/microsoft/callback', [MicrosoftAuthController::class, 'callback']);

// Authentication routes
Route::post('/register', [AuthController::class, 'register'])->middleware('throttle:20,1')->name('register.store');
Route::get('/register', [AuthController::class, 'showRegister'])->name('register');
Route::post('/login', [AuthController::class, 'login'])->middleware('throttle:20,1')->name('login.store');
Route::get('/login', [AuthController::class, 'showLogin'])->name('login');
Route::post('/logout', [AuthController::class, 'logout'])->name('logout');

// Email verification routes
Route::get('/verify-email', [AuthController::class, 'showVerification'])->name('verify.email');
Route::post('/send-verification', [AuthController::class, 'sendVerificationCode'])->middleware('throttle:10,1')->name('send.verification');
Route::post('/verify-code', [AuthController::class, 'verifyCode'])->middleware('throttle:20,1')->name('verify.code');

// Dashboard — accessible to both applicants and enrolled students
Route::middleware(['auth'])->group(function () {
    Route::get('/enrollment/dashboard', [EnrollmentController::class, 'showDashboard'])->name('enrollment.dashboard');
    Route::get('/enrollment/status', [EnrollmentController::class, 'checkApplicationStatus'])->name('enrollment.status');
});

// Enrollment routes (protected — applicant role only)
Route::middleware(['auth', 'applicant'])->group(function () {
    Route::get('/enroll', [EnrollmentController::class, 'showEnrollmentForm'])->name('enrollment.form');
    Route::post('/enroll', [EnrollmentController::class, 'submitEnrollment'])->name('enrollment.submit');
    Route::post('/enroll/draft', [EnrollmentController::class, 'saveDraft'])->name('enrollment.draft');
    Route::get('/enrollment/success', [EnrollmentController::class, 'showSuccess'])->name('enrollment.success');
    Route::get('/enrollment/payment', [EnrollmentController::class, 'showPayment'])->name('enrollment.payment');
    Route::post('/enrollment/payment', [EnrollmentController::class, 'submitPayment'])->name('enrollment.payment.submit');

    Route::get('/demo/loading-states', function () {
        return view('demo.loading-states');
    })->name('demo.loading-states');
});

// Note: Not using Breeze's auth.php since we have custom AuthController
// require __DIR__.'/auth.php';
