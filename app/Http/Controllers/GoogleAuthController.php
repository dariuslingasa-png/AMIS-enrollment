<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;
use Laravel\Socialite\Facades\Socialite;
use Exception;

class GoogleAuthController extends Controller
{
    public function redirect(): RedirectResponse
    {
        return Socialite::driver('google')->redirect();
    }

    public function callback(Request $request): RedirectResponse
    {
        try {
            $googleUser = Socialite::driver('google')->user();
            
            if (!$googleUser || !$googleUser->getEmail()) {
                return redirect()->route('login')->withErrors(['email' => 'Failed to retrieve email from Google.']);
            }

            // Find or create user
            $user = User::where('email', $googleUser->getEmail())->first();

            if ($user) {
                // If user exists, log them in
                Auth::login($user);
                $user->forceFill(['last_active_at' => now()])->save();
            } else {
                // Create new user account if they don't exist
                $user = User::create([
                    'name' => mb_strtoupper($googleUser->getName() ?: 'Google User', 'UTF-8'),
                    'email' => $googleUser->getEmail(),
                    'password' => bcrypt(Str::random(24)),
                    'role' => 'applicant',
                    'account_status' => 'verified',
                    'email_verified_at' => now(),
                    'last_active_at' => now(),
                ]);
                Auth::login($user);
            }

            $request->session()->regenerate();

            return redirect()->route('enrollment.dashboard');
        } catch (Exception $e) {
            return redirect()->route('login')->withErrors(['email' => 'Google authentication failed: ' . $e->getMessage()]);
        }
    }
}
