<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\VerificationCode;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;
use Illuminate\Validation\Rules\Password;

class AuthController extends Controller
{
    public function showRegister()
    {
        return view('auth.register');
    }

    public function register(Request $request)
    {
        $validated = $request->validate([
            'username' => 'required|string|max:255|unique:users',
            'email' => 'required|string|email|max:255|unique:users',
            'password' => ['required', 'confirmed', Password::defaults()],
        ]);

        $user = User::create([
            'name' => $validated['username'],
            'username' => $validated['username'],
            'email' => $validated['email'],
            'password' => Hash::make($validated['password']),
            'role' => 'applicant',
            'account_status' => 'pending',
        ]);

        // Generate and store verification code
        $code = str_pad(random_int(1000, 9999), 4, '0', STR_PAD_LEFT);
        VerificationCode::create([
            'email' => $validated['email'],
            'code' => $code,
            'expires_at' => now()->addMinutes(10),
        ]);

        // Send verification email
        $emailSent = $this->sendCode($validated['email'], $code);

        $msg = $emailSent
            ? 'Registration successful! Check your email for the verification code.'
            : "Registration successful! Your verification code is: {$code}";

        return redirect()->route('verify.email')
            ->with('email', $validated['email'])
            ->with('success', $msg);
    }

    public function showLogin()
    {
        return view('auth.login');
    }

    public function login(Request $request)
    {
        $credentials = $request->validate([
            'email' => 'required|email',
            'password' => 'required|string',
        ]);

        if (Auth::attempt($credentials)) {
            $user = Auth::user();

            if ($user->account_status !== 'verified') {
                Auth::logout();
                return back()->withErrors([
                    'email' => 'Please verify your email first before logging in.',
                ]);
            }

            $request->session()->regenerate();

            return redirect()->intended('/dashboard');
        }

        return back()->withErrors([
            'email' => 'The provided credentials do not match our records.',
        ]);
    }

    public function logout(Request $request)
    {
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect('/');
    }

    public function showVerification()
    {
        return view('auth.verify-email');
    }

    public function sendVerificationCode(Request $request)
    {
        $validated = $request->validate([
            'email' => 'required|email|exists:users,email',
        ]);

        $code = str_pad(random_int(1000, 9999), 4, '0', STR_PAD_LEFT);

        VerificationCode::create([
            'email' => $validated['email'],
            'code' => $code,
            'expires_at' => now()->addMinutes(10),
        ]);

        $emailSent = $this->sendCode($validated['email'], $code);

        $msg = $emailSent
            ? 'Verification code sent to your email!'
            : "Verification code: {$code}";

        return back()
            ->with('email', $validated['email'])
            ->with('success', $msg);
    }

    public function verifyCode(Request $request)
    {
        $validated = $request->validate([
            'email' => 'required|email',
            'code' => 'required|string|size:4',
        ]);

        $verificationCode = VerificationCode::where('email', $validated['email'])
            ->where('code', $validated['code'])
            ->where('expires_at', '>', now())
            ->where('used', false)
            ->first();

        if (!$verificationCode) {
            return back()
                ->with('email', $validated['email'])
                ->withErrors(['code' => 'Invalid or expired verification code.']);
        }

        // Mark code as used
        $verificationCode->update(['used' => true]);

        // Update user account status
        $user = User::where('email', $validated['email'])->first();
        if ($user) {
            $user->update([
                'account_status' => 'verified',
                'email_verified_at' => now(),
            ]);
        }

        return redirect()->route('login')
            ->with('success', 'Email verified successfully! You can now log in.');
    }

    /**
     * Send verification code via email.
     * Returns true if sent, false if failed.
     */
    private function sendCode(string $email, string $code): bool
    {
        try {
            $html = '
<!DOCTYPE html>
<html>
<head><meta charset="utf-8"></head>
<body style="margin:0;padding:0;background:#f3f4f6;font-family:Inter,Arial,sans-serif;">
<table width="100%" cellpadding="0" cellspacing="0" style="background:#f3f4f6;padding:40px 20px;">
<tr><td align="center">
<table width="480" cellpadding="0" cellspacing="0" style="background:#ffffff;border-radius:16px;overflow:hidden;box-shadow:0 4px 12px rgba(0,0,0,0.08);">
    <tr><td style="background:linear-gradient(135deg,#059669,#047857);padding:32px;text-align:center;">
        <img src="' . asset('images/AMIS_Logo.png') . '" alt="AMIS" width="60" height="60" style="margin-bottom:12px;">
        <h1 style="color:#fff;font-size:20px;margin:0;">AMIS Enrollment</h1>
        <p style="color:rgba(255,255,255,0.85);font-size:13px;margin:4px 0 0;">Al Munawwara Islamic School</p>
    </td></tr>
    <tr><td style="padding:32px 40px;">
        <h2 style="color:#111827;font-size:22px;margin:0 0 8px;text-align:center;">Email Verification</h2>
        <p style="color:#6b7280;font-size:14px;text-align:center;margin:0 0 24px;">Enter this code to verify your email address</p>
        <div style="background:#f0fdf4;border:2px solid #bbf7d0;border-radius:12px;padding:24px;text-align:center;margin-bottom:24px;">
            <p style="color:#6b7280;font-size:12px;margin:0 0 8px;text-transform:uppercase;letter-spacing:1px;">Your Verification Code</p>
            <p style="color:#059669;font-size:36px;font-weight:700;letter-spacing:8px;margin:0;">' . $code . '</p>
        </div>
        <p style="color:#6b7280;font-size:13px;text-align:center;margin:0 0 8px;">This code expires in <strong>10 minutes</strong>.</p>
        <p style="color:#9ca3af;font-size:12px;text-align:center;margin:0;">If you did not request this, please ignore this email.</p>
    </td></tr>
    <tr><td style="background:#f9fafb;padding:20px 40px;text-align:center;border-top:1px solid #e5e7eb;">
        <p style="color:#9ca3af;font-size:11px;margin:0;">&copy; ' . date('Y') . ' Al Munawwara Islamic School. All rights reserved.</p>
    </td></tr>
</table>
</td></tr>
</table>
</body>
</html>';

            Mail::html($html, function ($message) use ($email) {
                $message->to($email)
                    ->subject('AMIS Enrollment - Email Verification');
            });
            return true;
        } catch (\Exception $e) {
            \Log::error("Failed to send verification email to {$email}: " . $e->getMessage());
            return false;
        }
    }
}
