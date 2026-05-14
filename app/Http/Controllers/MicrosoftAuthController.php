<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Laravel\Socialite\Facades\Socialite;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;

class MicrosoftAuthController extends Controller
{
    public function redirect()
    {
        return Socialite::driver('microsoft')->redirect();
    }

    public function callback()
    {
        $microsoftUser = Socialite::driver('microsoft')->user();
        
        $user = User::updateOrCreate([
            'email' => $microsoftUser->getEmail(),
        ], [
            'name' => $microsoftUser->getName(),
            'microsoft_id' => $microsoftUser->getId(),
            'password' => bcrypt(Str::random(24)),
            'email_verified_at' => now(),
        ]);

        Auth::login($user);

        return redirect()->intended('/dashboard');
    }
}
