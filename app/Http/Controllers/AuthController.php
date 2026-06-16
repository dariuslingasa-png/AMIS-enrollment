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
    public function showRegister()
    {
        return redirect()->route('login');
    }

    public function register(Request $request)
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
        // Legacy endpoint for older cached verification pages. Never report true,
        // because cached polling scripts must not auto-open dashboard.
        return response()->json([
            'verified' => false,
        ]);
    }

    public function showVerificationNotice(Request $request)
    {
        if (Auth::check()) {
            $user = Auth::user();

            // Already verified → go straight to dashboard
            if ($user->hasVerifiedEmail() && $user->account_status === 'verified') {
                // Clean up verification session data if present
                $request->session()->forget(['verify_email', 'verify_timer_start']);
                return redirect()->route('enrollment.dashboard');
            }

            if ($request->session()->has('verify_email')) {
                // Preserve verification data, then log out so the waiting
                // page doesn't appear "authenticated" to other middleware.
                $verifyEmail = $request->session()->get('verify_email');
                $verifyTimerStart = $request->session()->get('verify_timer_start');

                Auth::guard('web')->logout();

                $request->session()->put('verify_email', $verifyEmail);
                if ($verifyTimerStart) {
                    $request->session()->put('verify_timer_start', $verifyTimerStart);
                }
            }
            return view('auth.verify-email');
        }

        if (!$request->session()->has('verify_email')) {
            return redirect()->route('login');
        }
        return view('auth.verify-email');
    }

    public function showVerifyConfirm(Request $request, int $id, string $hash)
    {
        $token = $request->query('token');
        $ip = $request->ip();
        $userAgent = Str::limit((string) $request->userAgent(), 1000, '');
        $timestamp = now();

        // 1. Check if token exists in request
        if (!$token) {
            $this->logVerificationAttempt(null, 'invalid_link', 'No token provided in verification GET request', $ip, $userAgent, $timestamp);
            return view('auth.verify-result', [
                'status' => 'error',
                'message' => 'Invalid Link',
            ]);
        }

        $tokenHash = hash('sha256', $token);
        $magicLink = MagicLink::where('token_hash', $tokenHash)->first();

        // 2. Check if token exists in DB
        if (!$magicLink) {
            $this->logVerificationAttempt(null, 'invalid_link', 'Magic link token not found on GET', $ip, $userAgent, $timestamp);
            return view('auth.verify-result', [
                'status' => 'error',
                'message' => 'Invalid Link',
            ]);
        }

        // 3. Check if token is expired
        if ($magicLink->expires_at->isPast()) {
            $this->logVerificationAttempt($magicLink->user_id, 'magic_link_expired', "Token expired at {$magicLink->expires_at} on GET", $ip, $userAgent, $timestamp);
            return view('auth.verify-result', [
                'status' => 'error',
                'message' => 'Link Expired',
            ]);
        }

        // 4. Check if token is already used
        if ($magicLink->used_at !== null) {
            $this->logVerificationAttempt($magicLink->user_id, 'magic_link_reused_attempt', 'Attempted reuse of already used magic link on GET', $ip, $userAgent, $timestamp);
            return view('auth.verify-result', [
                'status' => 'error',
                'message' => 'Link Already Used',
            ]);
        }

        // 5. Check if user matches token
        $user = User::find($id);
        if (!$user || $magicLink->user_id !== $user->id || !hash_equals(sha1($user->getEmailForVerification()), $hash)) {
            $this->logVerificationAttempt($magicLink->user_id, 'invalid_link', 'User mismatch or invalid email hash on GET', $ip, $userAgent, $timestamp);
            return view('auth.verify-result', [
                'status' => 'error',
                'message' => 'Invalid Link',
            ]);
        }

        // Token is valid! Render the landing page with confirmation form
        return view('auth.verify-confirm', [
            'id' => $id,
            'hash' => $hash,
            'token' => $token,
            'email' => $user->email,
        ]);
    }

    public function verifyEmail(Request $request, int $id, string $hash)
    {
        $token = $request->query('token') ?? $request->input('token');
        $ip = $request->ip();
        $userAgent = Str::limit((string) $request->userAgent(), 1000, '');
        $timestamp = now();

        // 1. Check if token exists
        if (!$token) {
            $this->logVerificationAttempt(null, 'invalid_link', 'No token provided in verification POST request', $ip, $userAgent, $timestamp);
            return view('auth.verify-result', [
                'status' => 'error',
                'message' => 'Invalid Link',
            ]);
        }

        $tokenHash = hash('sha256', $token);
        $magicLink = MagicLink::where('token_hash', $tokenHash)->first();

        // 2. Check if token exists in DB
        if (!$magicLink) {
            $this->logVerificationAttempt(null, 'invalid_link', 'Magic link token not found on POST', $ip, $userAgent, $timestamp);
            return view('auth.verify-result', [
                'status' => 'error',
                'message' => 'Invalid Link',
            ]);
        }

        // 3. Check if token is expired
        if ($magicLink->expires_at->isPast()) {
            $this->logVerificationAttempt($magicLink->user_id, 'magic_link_expired', "Token expired at {$magicLink->expires_at} on POST", $ip, $userAgent, $timestamp);
            return view('auth.verify-result', [
                'status' => 'error',
                'message' => 'Link Expired',
            ]);
        }

        // 4. Check if token is already used
        if ($magicLink->used_at !== null) {
            $this->logVerificationAttempt($magicLink->user_id, 'magic_link_reused_attempt', 'Attempted reuse of magic link on POST', $ip, $userAgent, $timestamp);
            return view('auth.verify-result', [
                'status' => 'error',
                'message' => 'Link Already Used',
            ]);
        }

        // 5. Check if user matches token
        $user = User::find($id);
        if (!$user || $magicLink->user_id !== $user->id || !hash_equals(sha1($user->getEmailForVerification()), $hash)) {
            $this->logVerificationAttempt($magicLink->user_id, 'invalid_link', 'User mismatch or invalid hash on POST', $ip, $userAgent, $timestamp);
            return view('auth.verify-result', [
                'status' => 'error',
                'message' => 'Invalid Link',
            ]);
        }

        // Verification successful! Mark as used and update user status
        $magicLink->update(['used_at' => $timestamp]);

        if (!$user->hasVerifiedEmail()) {
            $user->forceFill([
                'email_verified_at' => $timestamp,
                'account_status' => 'verified',
            ])->save();

            event(new Verified($user));
        } elseif ($user->account_status !== 'verified') {
            $user->update(['account_status' => 'verified']);
        }

        // Log Magic Link Verified event
        $this->logVerificationAttempt($user->id, 'magic_link_verified', 'Verification Successful', $ip, $userAgent, $timestamp);

        // Authenticate the user
        Auth::login($user);
        $request->session()->regenerate();
        $request->session()->forget('verify_email');
        $request->session()->forget('verify_timer_start');

        return view('auth.verify-result', [
            'status' => 'success',
            'message' => 'Verification Successful',
            'redirectUrl' => route('enrollment.dashboard'),
        ]);
    }

    private function logVerificationAttempt(?int $userId, string $event, string $message, string $ip, string $userAgent, $timestamp): void
    {
        try {
            $email = null;
            if ($userId) {
                $user = User::find($userId);
                $email = $user?->email;
            } else {
                $email = request()->input('email') ?? request()->session()->get('verify_email');
            }

            DB::table('admin_audit_logs')->insert([
                'user_id' => $userId,
                'event' => $event,
                'email' => $email,
                'ip_address' => $ip,
                'user_agent' => $userAgent,
                'successful' => ($event === 'magic_link_verified'),
                'message' => $message,
                'created_at' => $timestamp,
                'updated_at' => $timestamp,
            ]);
        } catch (\Throwable $e) {
            Log::error('Failed to log verification attempt in audit logs', [
                'error' => $e->getMessage(),
                'event' => $event,
                'message' => $message,
            ]);
        }

        Log::info("Verification attempt: {$event} - {$message}", [
            'user_id' => $userId,
            'ip' => $ip,
        ]);
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
