<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;
use Laravel\Socialite\Facades\Socialite;
use Exception;

class MicrosoftAuthController extends Controller
{
    public function redirect()
    {
        try {
            return Socialite::driver('microsoft')->redirect();
        } catch (Exception $e) {
            return redirect()->route('login')->withErrors(['email' => 'Failed to initialize Microsoft redirect: ' . $e->getMessage()]);
        }
    }

    public function callback(Request $request): RedirectResponse
    {
        try {
            $microsoftUser = Socialite::driver('microsoft')->user();

            if (!$microsoftUser || !$microsoftUser->getEmail()) {
                throw new Exception("Failed to retrieve user from Microsoft.");
            }

            $email = $microsoftUser->getEmail();
            $name = $microsoftUser->getName();

            // Find or create user
            $user = User::where('email', $email)->first();

            if ($user) {
                // If user exists, log them in
                Auth::login($user);
                $user->forceFill(['last_active_at' => now()])->save();
            } else {
                // Create new user account if they don't exist
                $emailName = explode('@', $email)[0];
                $fallbackName = ucwords(str_replace(['.', '_', '-'], ' ', $emailName));
                
                $user = User::create([
                    'name' => mb_strtoupper($name ?: $fallbackName, 'UTF-8'),
                    'email' => $email,
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
            return redirect()->route('login')->withErrors(['email' => 'Microsoft authentication failed: ' . $e->getMessage()]);
        }
    }
}
