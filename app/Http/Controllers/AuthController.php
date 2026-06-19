<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\VerificationCode;
use App\Models\MagicLink;
use Illuminate\Auth\Events\Verified;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Throwable;

class AuthController extends Controller
{


    public function sendOtp(Request $request)
    {
        $validated = $request->validate([
            'email' => ['required', 'string', 'email', 'max:255'],
        ]);

        $email = Str::lower(trim($validated['email']));

        if (Auth::check()) {
            Auth::guard('web')->logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();
        }

        // Rate limit sending OTP codes to 3 per 60 seconds per email
        $limiterKey = 'send-otp:' . $email;
        if (RateLimiter::tooManyAttempts($limiterKey, 3)) {
            $seconds = RateLimiter::availableIn($limiterKey);
            $waitText = "{$seconds} second(s)";
            return response()->json([
                'status' => 'error',
                'message' => "Too many verification requests. Please wait {$waitText}."
            ], 429);
        }
        RateLimiter::hit($limiterKey, 60);

        // Find or create user
        $user = User::where('email', $email)->first();

        if ($user) {
            if (in_array($user->account_status, ['blocked', 'suspended'], true)) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'This account is blocked or suspended. Please contact AMIS support.'
                ], 403);
            }
        } else {
            // Register a new user
            $user = User::create([
                'name' => Str::before($email, '@'),
                'username' => User::makeUniqueUsername($email),
                'email' => $email,
                'password' => Hash::make(Str::random(48)),
                'role' => 'applicant',
                'account_status' => 'pending',
            ]);
        }

        // Generate 4-digit numeric code
        $code = sprintf("%04d", rand(1000, 9999));

        // Save verification code in DB (expires in 10 minutes)
        VerificationCode::updateOrCreate(
            ['email' => $email],
            [
                'code' => $code,
                'expires_at' => now()->addMinutes(10),
                'used' => false,
            ]
        );

        // Send Notification containing the code
        try {
            $user->notify(new \App\Notifications\SendOtpCode($code));
        } catch (\Throwable $exception) {
            Log::error('Failed to send OTP verification code.', [
                'email' => $email,
                'error' => $exception->getMessage(),
            ]);
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to send the verification code. Please check your SMTP mail configuration.'
            ], 500);
        }

        // Log OTP Generation
        try {
            DB::table('admin_audit_logs')->insert([
                'user_id' => $user->id,
                'event' => 'otp_generated',
                'email' => $email,
                'ip_address' => $request->ip(),
                'user_agent' => Str::limit((string) $request->userAgent(), 1000, ''),
                'successful' => true,
                'message' => '4-digit OTP code generated and sent',
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        } catch (\Throwable $e) {
            Log::error('Failed to log OTP generation in audit logs', ['error' => $e->getMessage()]);
        }

        return response()->json([
            'status' => 'success',
            'message' => 'A 4-digit verification code has been sent to your email.'
        ]);
    }

    public function verifyOtp(Request $request)
    {
        $validated = $request->validate([
            'email' => ['required', 'string', 'email', 'max:255'],
            'code' => ['required', 'string', 'size:4'],
        ]);

        $email = Str::lower(trim($validated['email']));
        $code = trim($validated['code']);

        // Rate limit OTP code verification attempts to 5 per 600 seconds per email (10 minutes)
        $limiterKey = 'verify-otp:' . $email;
        if (RateLimiter::tooManyAttempts($limiterKey, 5)) {
            $seconds = RateLimiter::availableIn($limiterKey);
            $waitText = $seconds <= 120 ? "{$seconds} second(s)" : (floor($seconds / 60) . " minute(s) and " . ($seconds % 60) . " second(s)");
            return response()->json([
                'status' => 'error',
                'message' => "Too many verification attempts. Please wait {$waitText} before trying again."
            ], 429);
        }
        RateLimiter::hit($limiterKey, 600);

        // Retrieve the latest valid unused, unexpired verification code
        $verifyCode = VerificationCode::where('email', $email)
            ->where('code', $code)
            ->where('used', false)
            ->where('expires_at', '>', now())
            ->first();

        if (!$verifyCode) {
            // Log verification failure
            try {
                DB::table('admin_audit_logs')->insert([
                    'user_id' => null,
                    'event' => 'otp_verification_failed',
                    'email' => $email,
                    'ip_address' => $request->ip(),
                    'user_agent' => Str::limit((string) $request->userAgent(), 1000, ''),
                    'successful' => false,
                    'message' => 'Invalid or expired OTP code entered',
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            } catch (\Throwable $e) {}

            return response()->json([
                'status' => 'error',
                'message' => 'Invalid or expired verification code.'
            ], 422);
        }

        // Mark code as used
        $verifyCode->update(['used' => true]);

        // Find the user
        $user = User::where('email', $email)->first();
        if (!$user) {
            return response()->json([
                'status' => 'error',
                'message' => 'User not found. Please try requesting a new code.'
            ], 404);
        }

        // Mark email as verified and account status as verified
        if (!$user->hasVerifiedEmail()) {
            $user->forceFill([
                'email_verified_at' => now(),
                'account_status' => 'verified',
            ])->save();

            event(new Verified($user));
        } elseif ($user->account_status !== 'verified') {
            $user->update(['account_status' => 'verified']);
        }

        $user->forceFill(['last_active_at' => now()])->save();

        // Authenticate the user
        Auth::login($user);
        $request->session()->regenerate();

        // Log OTP Verification Success
        try {
            DB::table('admin_audit_logs')->insert([
                'user_id' => $user->id,
                'event' => 'otp_verified',
                'email' => $email,
                'ip_address' => $request->ip(),
                'user_agent' => Str::limit((string) $request->userAgent(), 1000, ''),
                'successful' => true,
                'message' => 'OTP verification successful. User logged in.',
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        } catch (\Throwable $e) {}

        return response()->json([
            'status' => 'success',
            'redirectUrl' => route('enrollment.dashboard')
        ]);
    }


    public function showLogin()
    {
        if (Auth::check()) {
            return redirect()->route('enrollment.dashboard');
        }

        return view('auth.login');
    }

    public function login(Request $request)
    {
        if (Auth::check()) {
            return redirect()->route('enrollment.dashboard');
        }

        $validated = $request->validate([
            'email' => ['required', 'email', 'max:255'],
            'password' => ['required', 'string'],
        ]);

        $credentials = [
            'email' => Str::lower(trim($validated['email'])),
            'password' => $validated['password'],
        ];

        if (Auth::attempt($credentials)) {
            $user = Auth::user();

            if ($user->account_status !== 'verified' || !$user->hasVerifiedEmail()) {
                Auth::logout();

                return back()->withErrors([
                    'email' => 'Please verify your email first. Check your inbox or Spam/Junk folder for the verification link.',
                ])->withInput($request->only('email', 'auth_mode'));
            }

            $user->forceFill(['last_active_at' => now()])->save();

            $request->session()->regenerate();

            return redirect()
                ->route('enrollment.dashboard')
                ->with('show_beta_notice', true);
        }

        // Log failed attempt to admin_audit_logs and warn in log file
        $email = Str::lower(trim($request->input('email')));
        try {
            \Illuminate\Support\Facades\DB::table('admin_audit_logs')->insert([
                'user_id' => null,
                'event' => 'login_failed',
                'email' => $email,
                'ip_address' => $request->ip(),
                'user_agent' => Str::limit((string) $request->userAgent(), 1000, ''),
                'successful' => false,
                'message' => "Failed login attempt for account: {$email}",
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        } catch (\Throwable $e) {
            // Ignore
        }

        Log::warning('Failed login attempt', [
            'ip' => $request->ip(),
            'email' => $email,
            'user_agent' => $request->userAgent(),
        ]);

        return back()->withErrors([
            'email' => 'The provided credentials do not match our records.',
        ])->withInput($request->only('email', 'auth_mode'));
    }

    public function logout(Request $request)
    {
        Auth::guard('web')->logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect('/');
    }



    public function setOffline(Request $request)
    {
        $user = Auth::user();
        if ($user) {
            $user->forceFill(['last_active_at' => now()->subMinutes(10)])->save();
        }

        return response()->json(['status' => 'success']);
    }
}
