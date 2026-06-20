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
        $mockProvider->shouldReceive('scopes')->with(['openid', 'email'])->andReturnSelf();
        $mockProvider->shouldReceive('with')->with(['response_mode' => 'form_post'])->andReturnSelf();
        
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
        $mockProvider->shouldReceive('scopes')->with(['openid', 'email'])->andReturnSelf();
        $mockProvider->shouldReceive('stateless')->andReturnSelf();
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

    public function test_google_sign_in_redirects_to_warning_on_unsupported_browser(): void
    {
        $userAgent = "Mozilla/5.0 (iPhone; CPU iPhone OS 13_3 like Mac OS X) AppleWebKit/605.1.15 (KHTML, like Gecko) Mobile/15E148 [FBAN/FBIOS;FBAV/251.0.0.31.111;]";

        $response = $this->withHeaders([
            'User-Agent' => $userAgent,
        ])->get(route('auth.google'));

        $response->assertRedirect(route('auth.unsupported-browser'));
    }

    public function test_unsupported_browser_warning_page_renders(): void
    {
        $response = $this->get(route('auth.unsupported-browser'));

        $response->assertStatus(200);
        $response->assertSee('Google Sign-in Blocked');
        $response->assertSee('Google Sign-In is not supported inside Messenger or Facebook browser.');
    }

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }
}
