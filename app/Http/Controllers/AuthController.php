<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\VerificationCode;
use Illuminate\Auth\Events\Registered;
use Illuminate\Auth\Events\Verified;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

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

        $user = User::where('email', $email)->first();

        if ($user) {
            if (in_array($user->account_status, ['blocked', 'suspended'], true)) {
                return back()
                    ->withInput($request->only('email'))
                    ->withErrors(['email' => 'This account is not available. Please contact AMIS support.']);
            }

            $user->sendEmailVerificationNotification();

            $request->session()->put('verify_email', $email);

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

        // Sends a signed activation link; clicking it verifies and logs the user in.
        event(new Registered($user));

        $request->session()->put('verify_email', $email);

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

        if (strtolower(trim($request->email)) !== strtolower(trim($sessionEmail))) {
            abort(403, 'Unauthorized verification resend request.');
        }

        $user = User::where('email', $request->email)->first();

        if ($user && !in_array($user->account_status, ['blocked', 'suspended'], true)) {
            $user->sendEmailVerificationNotification();
        }

        $request->session()->put('verify_email', $request->email);

        return back()->with('success', 'Verification link resent! Please check your inbox or Spam/Junk folder.');
    }
}
