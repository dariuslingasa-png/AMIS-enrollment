<?php

namespace Tests\Feature\Auth;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class GoogleAuthTest extends TestCase
{
    use RefreshDatabase;

    public function test_google_sign_in_redirect_is_coming_soon(): void
    {
        $response = $this->get('/auth/google');

        $this->assertGuest();
        $response->assertRedirect(route('login', absolute: false));
    }

    public function test_google_callback_is_coming_soon_and_does_not_create_account(): void
    {
        $response = $this->get('/auth/google/callback');

        $this->assertGuest();
        $this->assertSame(0, User::count());
        $response->assertRedirect(route('login', absolute: false));
    }
}
