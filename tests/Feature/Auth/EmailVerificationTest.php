<?php

namespace Tests\Feature\Auth;

use App\Models\User;
use App\Models\MagicLink;
use Illuminate\Auth\Events\Verified;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Str;
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

        $verificationUrl = $this->magicLinkUrl($user);

        $this->get($verificationUrl)
            ->assertOk()
            ->assertSee('Confirm Verification');

        $response = $this->post($verificationUrl);

        Event::assertDispatched(Verified::class);
        $this->assertTrue($user->fresh()->hasVerifiedEmail());
        $this->assertAuthenticatedAs($user);
        $this->assertSame('verified', $user->fresh()->account_status);
        $response->assertOk()->assertSee('Verification Successful');
        $this->assertNotNull(MagicLink::firstWhere('user_id', $user->id)->used_at);
    }

    public function test_email_verification_link_can_not_be_reused_after_logout(): void
    {
        $user = User::factory()->unverified()->create();

        $verificationUrl = $this->magicLinkUrl($user);

        $this->post($verificationUrl)->assertOk()->assertSee('Verification Successful');
        $this->post('/logout');

        $this->get($verificationUrl)
            ->assertOk()
            ->assertSee('Link Already Used');

        $this->assertGuest();
    }

    public function test_email_is_not_verified_with_invalid_hash(): void
    {
        $user = User::factory()->unverified()->create();

        $verificationUrl = $this->magicLinkUrl($user, sha1('wrong-email'));

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
        $response3->assertOk()->assertJson(['verified' => false]);
    }

    public function test_email_verification_status_polling_stays_false_without_pending_email_session(): void
    {
        $user = User::factory()->create([
            'email_verified_at' => now(),
            'account_status' => 'verified',
        ]);

        $response = $this->actingAs($user)->getJson('/verify-email/status');

        $response->assertOk()->assertJson(['verified' => false]);
    }

    private function magicLinkUrl(User $user, ?string $hash = null): string
    {
        $token = Str::random(40);

        MagicLink::create([
            'user_id' => $user->id,
            'token_hash' => hash('sha256', $token),
            'expires_at' => now()->addMinutes(5),
        ]);

        return route('verification.verify', [
            'id' => $user->id,
            'hash' => $hash ?? sha1($user->email),
            'token' => $token,
        ]);
    }
}
