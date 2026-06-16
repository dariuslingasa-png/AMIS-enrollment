<?php

namespace Tests\Feature\Auth;

use App\Models\User;
use App\Models\VerificationCode;
use Illuminate\Auth\Events\Verified;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\URL;
use Tests\TestCase;

class EmailVerificationTest extends TestCase
{
    use RefreshDatabase;

    public function test_email_verification_screen_can_be_rendered(): void
    {
        $user = User::factory()->unverified()->create();

        $response = $this->actingAs($user)->get('/verify-email/notice');

        $response->assertStatus(200);
    }

    public function test_email_can_be_verified(): void
    {
        $user = User::factory()->unverified()->create();

        Event::fake();

        $verificationUrl = URL::temporarySignedRoute(
            'verification.verify',
            now()->addMinutes(60),
            ['id' => $user->id, 'hash' => sha1($user->email), 'code' => '123456']
        );

        VerificationCode::create([
            'email' => $user->email,
            'code' => '123456',
            'expires_at' => now()->addMinutes(60),
        ]);

        $response = $this->get($verificationUrl);

        Event::assertDispatched(Verified::class);
        $this->assertTrue($user->fresh()->hasVerifiedEmail());
        $this->assertAuthenticatedAs($user);
        $this->assertSame('verified', $user->fresh()->account_status);
        $response->assertRedirect(route('enrollment.dashboard', absolute: false));
        $response->assertSessionHas('show_beta_notice', true);
        $this->assertTrue(VerificationCode::where('email', $user->email)->first()->used);
    }

    public function test_email_verification_link_can_not_be_reused_after_logout(): void
    {
        $user = User::factory()->unverified()->create();

        $verificationUrl = URL::temporarySignedRoute(
            'verification.verify',
            now()->addMinutes(60),
            ['id' => $user->id, 'hash' => sha1($user->email), 'code' => '654321']
        );

        VerificationCode::create([
            'email' => $user->email,
            'code' => '654321',
            'expires_at' => now()->addMinutes(60),
        ]);

        $this->get($verificationUrl)->assertRedirect(route('enrollment.dashboard', absolute: false));
        $this->post('/logout');

        $this->get($verificationUrl)
            ->assertRedirect(route('login', absolute: false))
            ->assertSessionHasErrors('email');

        $this->assertGuest();
    }

    public function test_email_is_not_verified_with_invalid_hash(): void
    {
        $user = User::factory()->unverified()->create();

        $verificationUrl = URL::temporarySignedRoute(
            'verification.verify',
            now()->addMinutes(60),
            ['id' => $user->id, 'hash' => sha1('wrong-email')]
        );

        $this->get($verificationUrl);

        $this->assertFalse($user->fresh()->hasVerifiedEmail());
    }

    public function test_email_verification_status_polling(): void
    {
        $user = User::factory()->unverified()->create();

        $response1 = $this->getJson('/verify-email/status');
        $response1->assertOk()->assertJson(['verified' => false]);

        $response2 = $this->withSession(['verify_email' => $user->email])->getJson('/verify-email/status');
        $response2->assertOk()->assertJson(['verified' => false]);

        $user->forceFill([
            'email_verified_at' => now(),
            'account_status' => 'verified',
        ])->save();

        $response3 = $this->actingAs($user)->withSession(['verify_email' => $user->email])->getJson('/verify-email/status');
        $response3->assertOk()->assertJson(['verified' => true]);
    }
}
