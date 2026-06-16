<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\VerificationCode;
use Illuminate\Auth\Events\Verified;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;
use Throwable;

class AuthController extends Controller
{
    public function showRegister()
    {
        return redirect()->route('login');
    }

    public function register(Request $request)
    {
        if (Auth::check()) {
            return redirect()->route('enrollment.dashboard');
        }

        $validated = $request->validate([
            'email' => ['required', 'string', 'email', 'max:255'],
        ]);

        $email = Str::lower(trim($validated['email']));

        // Rate limit registration attempts per email address to 2 per 60 seconds
        $limiterKey = 'register-email:' . $email;
        if (RateLimiter::tooManyAttempts($limiterKey, 2)) {
            $seconds = RateLimiter::availableIn($limiterKey);
            return back()
                ->withInput($request->only('email'))
                ->withErrors(['email' => "Too many verification requests. Please wait {$seconds} seconds."]);
        }
        
        RateLimiter::hit($limiterKey, 60);

        $user = User::where('email', $email)->first();

        if ($user) {
            if (in_array($user->account_status, ['blocked', 'suspended'], true)) {
                return back()
                    ->withInput($request->only('email'))
                    ->withErrors(['email' => 'This account is not available. Please contact AMIS support.']);
            }

            if (! $this->sendVerificationLink($user, $request)) {
                return back()
                    ->withInput($request->only('email'))
                    ->withErrors(['email' => 'We could not send the verification link right now. Please contact AMIS support or try again later.']);
            }

            $request->session()->put('verify_email', $email);
            $request->session()->put('verify_timer_start', time());

            return redirect()->route('verify.email.notice')
                ->with('email', $email)
                ->with('success', 'We sent a secure sign-in link to your email.');
        }

        $user = User::create([
            'name' => Str::before($email, '@'),
            'username' => User::makeUniqueUsername($email),
            'email' => $email,
            'password' => Hash::make(Str::random(48)),
            'role' => 'applicant',
            'account_status' => 'pending',
        ]);

        if (! $this->sendVerificationLink($user, $request)) {
            return back()
                ->withInput($request->only('email'))
                ->withErrors(['email' => 'We could not send the verification link right now. Please contact AMIS support or try again later.']);
        }

        $request->session()->put('verify_email', $email);
        $request->session()->put('verify_timer_start', time());

        return redirect()->route('verify.email.notice')
            ->with('email', $email)
            ->with('success', 'We sent a verification link to your email.');
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

    public function checkVerificationStatus(Request $request)
    {
        $email = $request->session()->get('verify_email');
        
        if (Auth::check()) {
            $email = Auth::user()->email;
        }

        if (!$email) {
            return response()->json(['verified' => false]);
        }

        $user = User::where('email', $email)->first();
        $isVerified = $user && $user->hasVerifiedEmail() && $user->account_status === 'verified';

        return response()->json([
            'verified' => $isVerified,
        ]);
    }

    public function showVerificationNotice(Request $request)
    {
        if (Auth::check()) {
            if (Auth::user()->hasVerifiedEmail()) {
                return redirect()->route('enrollment.dashboard');
            }
            return view('auth.verify-email');
        }

        if (!$request->session()->has('verify_email')) {
            return redirect()->route('login');
        }
        return view('auth.verify-email');
    }

    public function verifyEmail(Request $request, int $id, string $hash)
    {
        $user = User::findOrFail($id);

        abort_unless(hash_equals(sha1($user->getEmailForVerification()), $hash), 403);
        abort_if(in_array($user->account_status, ['blocked', 'suspended'], true), 403);

        if ($user->hasVerifiedEmail() && $user->account_status === 'verified') {
            Auth::login($user);
            $request->session()->regenerate();
            $request->session()->forget('verify_email');
            $request->session()->forget('verify_timer_start');

            return redirect()
                ->route('enrollment.dashboard')
                ->with('success', 'Email verified! Welcome to AMIS.')
                ->with('show_beta_notice', true);
        }

        $verificationCode = VerificationCode::where('email', $user->getEmailForVerification())
            ->where('code', (string) $request->query('code'))
            ->latest()
            ->first();

        if (!$verificationCode || !$verificationCode->isValid()) {
            return redirect()
                ->route('login')
                ->withErrors(['email' => 'This email link has already been used or expired. Please request a new secure link.']);
        }

        $verificationCode->update(['used' => true]);

        if (!$user->hasVerifiedEmail()) {
            $user->forceFill([
                'email_verified_at' => now(),
                'account_status' => 'verified',
            ])->save();

            event(new Verified($user));
        } elseif ($user->account_status !== 'verified') {
            $user->update(['account_status' => 'verified']);
        }

        Auth::login($user);
        $request->session()->regenerate();
        $request->session()->forget('verify_email');
        $request->session()->forget('verify_timer_start');

        return redirect()
            ->route('enrollment.dashboard')
            ->with('success', 'Email verified! Welcome to AMIS.')
            ->with('show_beta_notice', true);
    }

    public function resendVerificationLink(Request $request)
    {
        if (Auth::check()) {
            if (Auth::user()->hasVerifiedEmail()) {
                return redirect()->route('enrollment.dashboard');
            }
            $sessionEmail = Auth::user()->email;
        } else {
            if (!$request->session()->has('verify_email')) {
                abort(403, 'Unauthorized verification resend request.');
            }
            $sessionEmail = $request->session()->get('verify_email');
        }

        $request->validate(['email' => 'required|email|exists:users,email']);

        $email = strtolower(trim($request->email));
        if ($email !== strtolower(trim($sessionEmail))) {
            abort(403, 'Unauthorized verification resend request.');
        }

        // Rate limit resending to 2 requests per 60 seconds per email address
        $limiterKey = 'resend-verification:' . $email;
        if (RateLimiter::tooManyAttempts($limiterKey, 2)) {
            $seconds = RateLimiter::availableIn($limiterKey);
            return back()->withErrors([
                'email' => "Please wait {$seconds} seconds before requesting another verification link."
            ]);
        }
        
        RateLimiter::hit($limiterKey, 60);

        $user = User::where('email', $request->email)->first();

        if ($user && !in_array($user->account_status, ['blocked', 'suspended'], true)) {
            if (! $this->sendVerificationLink($user, $request)) {
                // Clear rate limit attempt so they can try again immediately if it failed to send
                RateLimiter::clear($limiterKey);
                return back()
                    ->withInput($request->only('email'))
                    ->withErrors(['email' => 'We could not resend the verification link right now. Please try again later.']);
            }
        }

        $request->session()->put('verify_email', $request->email);
        $request->session()->put('verify_timer_start', time());

        return back()->with('success', 'Verification link resent! Please check your inbox or Spam/Junk folder.');
    }

    private function sendVerificationLink(User $user, Request $request): bool
    {
        try {
            $user->sendEmailVerificationNotification();
            return true;
        } catch (Throwable $exception) {
            Log::error('Failed to send enrollment verification link.', [
                'email' => $user->email,
                'ip' => $request->ip(),
                'error' => $exception->getMessage(),
            ]);

            return false;
        }
    }
}
