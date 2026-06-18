<?php

namespace Tests\Feature\Auth;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Socialite\Facades\Socialite;
use Tests\TestCase;
use Mockery;

class GoogleAuthTest extends TestCase
{
    use RefreshDatabase;

    public function test_google_sign_in_redirects_to_google(): void
    {
        $mockProvider = Mockery::mock(\Laravel\Socialite\Two\GoogleProvider::class);
        
        // Mock the target URL logic
        $redirectResponse = Mockery::mock(\Illuminate\Http\RedirectResponse::class);
        $redirectResponse->shouldReceive('getTargetUrl')->andReturn('https://accounts.google.com/o/oauth2/auth');
        
        $mockProvider->shouldReceive('redirect')->andReturn($redirectResponse);

        Socialite::shouldReceive('driver')->with('google')->andReturn($mockProvider);

        // When navigating to our signin route
        $response = $this->get(route('auth.google'));

        // It should contain the script redirect
        $response->assertStatus(200);
        $response->assertSee('window.location.href');
    }

    public function test_google_callback_authenticates_user(): void
    {
        $mockUser = Mockery::mock(\Laravel\Socialite\Two\User::class);
        $mockUser->shouldReceive('getEmail')->andReturn('testuser@gmail.com');
        $mockUser->shouldReceive('getName')->andReturn('Test User');

        $mockProvider = Mockery::mock(\Laravel\Socialite\Two\GoogleProvider::class);
        $mockProvider->shouldReceive('user')->andReturn($mockUser);

        Socialite::shouldReceive('driver')->with('google')->andReturn($mockProvider);

        // Run callback
        $response = $this->get(route('auth.google.callback'));

        // Assert user was created and authenticated
        $this->assertDatabaseHas('users', [
            'email' => 'testuser@gmail.com',
            'role' => 'applicant',
            'account_status' => 'verified'
        ]);

        $this->assertAuthenticated();
        $response->assertRedirect(route('enrollment.dashboard'));
    }

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }
}
