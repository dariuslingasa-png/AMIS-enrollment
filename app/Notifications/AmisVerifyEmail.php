<?php

namespace App\Notifications;

use App\Models\MagicLink;
use Illuminate\Auth\Notifications\VerifyEmail;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class AmisVerifyEmail extends VerifyEmail
{
    protected function verificationUrl($notifiable): string
    {
        $expiresAt = now()->addMinutes(Config::get('auth.verification.expire', 5));
        $email = $notifiable->getEmailForVerification();

        // Expire any existing magic links for this user to enforce single active link
        MagicLink::where('user_id', $notifiable->id)
            ->whereNull('used_at')
            ->update(['expires_at' => now()]);

        $token = Str::random(40);
        $tokenHash = hash('sha256', $token);

        MagicLink::create([
            'user_id' => $notifiable->id,
            'token_hash' => $tokenHash,
            'expires_at' => $expiresAt,
        ]);

        // Audit Log: Magic Link Generated
        try {
            DB::table('admin_audit_logs')->insert([
                'user_id' => $notifiable->id,
                'event' => 'magic_link_generated',
                'email' => $email,
                'ip_address' => request()->ip(),
                'user_agent' => Str::limit((string) request()->userAgent(), 1000, ''),
                'successful' => true,
                'message' => 'Magic link generated for verification',
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        } catch (\Throwable $e) {
            Log::error('Failed to log magic link generation', ['error' => $e->getMessage()]);
        }

        return route('verification.verify', [
            'id' => $notifiable->getKey(),
            'hash' => sha1($email),
            'token' => $token,
        ]);
    }

    public function toMail($notifiable): MailMessage
    {
        $verificationUrl = $this->verificationUrl($notifiable);

        return (new MailMessage)
            ->subject('Verify Your AMIS Enrollment Email')
            ->replyTo('noreply@amis.edu.ph', 'AMIS Support')
            ->view('emails.verify-email', [
                'user' => $notifiable,
                'verificationUrl' => $verificationUrl,
            ]);
    }
}
