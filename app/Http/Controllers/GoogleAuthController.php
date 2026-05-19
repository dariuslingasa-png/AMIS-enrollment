<?php

namespace App\Http\Controllers;

use Illuminate\Http\RedirectResponse;

class GoogleAuthController extends Controller
{
    public function redirect(): RedirectResponse
    {
        return redirect()->route('login')->with('success', 'Google sign-in is coming soon. Please use email verification for now.');
    }

    public function callback(): RedirectResponse
    {
        return $this->redirect();
    }
}
