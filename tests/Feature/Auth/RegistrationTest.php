<?php

namespace Tests\Feature\Auth;

use App\Models\User;
use App\Notifications\AmisVerifyEmail;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

class RegistrationTest extends TestCase
{
    use RefreshDatabase;

    public function test_registration_screen_redirects_to_login(): void
    {
        $response = $this->get('/register');

        $response->assertRedirect(route('login', absolute: false));
    }

    public function test_new_users_can_register(): void
    {
        Notification::fake();

        $response = $this->post('/register', [
            'email' => 'test@example.com',
        ]);

        $this->assertGuest();
        $response->assertRedirect(route('verify.email.notice', absolute: false));

        $this->assertDatabaseHas('users', [
            'email' => 'test@example.com',
            'username' => 'test',
            'role' => 'applicant',
            'account_status' => 'pending',
        ]);

        Notification::assertSentTo(
            User::where('email', 'test@example.com')->first(),
            AmisVerifyEmail::class
        );
    }

    public function test_existing_verified_email_receives_secure_link_without_login(): void
    {
        Notification::fake();

        $user = User::factory()->create([
            'email' => 'parent@gmail.com',
            'account_status' => 'verified',
        ]);

        $response = $this->post('/register', [
            'email' => 'parent@gmail.com',
        ]);

        $this->assertGuest();
        $response->assertRedirect(route('verify.email.notice', absolute: false));
        Notification::assertSentTo($user, AmisVerifyEmail::class);
    }

    public function test_authenticated_browser_still_receives_secure_link_instead_of_dashboard_redirect(): void
    {
        Notification::fake();

        $user = User::factory()->create([
            'email' => 'parent@gmail.com',
            'account_status' => 'verified',
        ]);

        $response = $this->actingAs($user)->post('/register', [
            'email' => 'parent@gmail.com',
        ]);

        $this->assertGuest();
        $response->assertRedirect(route('verify.email.notice', absolute: false));
        $response->assertSessionHas('verify_email', 'parent@gmail.com');
        Notification::assertSentTo($user, AmisVerifyEmail::class);
    }
}
