<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;
use Laravel\Socialite\Facades\Socialite;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Firebase\JWT\JWT;
use Firebase\JWT\JWK;
use Exception;

class GoogleAuthController extends Controller
{
    public function redirect()
    {
        try {
            $redirectResponse = Socialite::driver('google')
                ->scopes(['openid', 'email'])
                ->with(['response_mode' => 'form_post'])
                ->redirect();
            $targetUrl = $redirectResponse->getTargetUrl();

            return response("<html><head><script>window.location.href = '" . addslashes($targetUrl) . "';</script></head><body>Redirecting to Google...</body></html>");
        } catch (Exception $e) {
            return redirect()->route('login')->withErrors(['email' => 'Failed to initialize Google redirect: ' . $e->getMessage()]);
        }
    }

    public function callback(Request $request): RedirectResponse
    {
        try {
            $email = null;
            $name = null;

            // Check if it is a POST request from Google Identity Services containing a credential token
            if ($request->isMethod('post') && $request->has('credential')) {
                $idToken = $request->input('credential');

                // Retrieve and cache Google's public JWKS certificates (valid for 24 hours)
                $jwks = Cache::remember('google_oauth_jwks', 86400, function () {
                    return Http::get('https://www.googleapis.com/oauth2/v3/certs')->json();
                });

                // Decode and verify JWT signature using Google's public key set
                $keys = JWK::parseKeySet($jwks);
                $decoded = JWT::decode($idToken, $keys);

                // Verify standard ID Token claims
                if ($decoded->iss !== 'https://accounts.google.com' && $decoded->iss !== 'accounts.google.com') {
                    throw new Exception("Invalid ID Token issuer.");
                }
                if ($decoded->aud !== config('services.google.client_id')) {
                    throw new Exception("Invalid ID Token audience.");
                }
                if ($decoded->exp < time()) {
                    throw new Exception("ID Token has expired.");
                }

                $email = $decoded->email;
                $name = $decoded->name ?? '';
            } else {
                // Fallback to standard redirect code exchange flow
                $googleUser = Socialite::driver('google')
                    ->scopes(['openid', 'email'])
                    ->stateless()
                    ->user();

                if (!$googleUser || !$googleUser->getEmail()) {
                    throw new Exception("Failed to retrieve user from Google.");
                }

                $email = $googleUser->getEmail();
                $name = $googleUser->getName();
            }

            if (!$email) {
                return redirect()->route('login')->withErrors(['email' => 'Failed to retrieve email from Google.']);
            }

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
            return redirect()->route('login')->withErrors(['email' => 'Google authentication failed: ' . $e->getMessage()]);
        }
    }
}
