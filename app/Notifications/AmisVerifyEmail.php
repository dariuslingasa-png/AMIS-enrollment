<?php

namespace App\Notifications;

use App\Models\VerificationCode;
use Illuminate\Auth\Notifications\VerifyEmail;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\URL;

class AmisVerifyEmail extends VerifyEmail
{
    protected function verificationUrl($notifiable): string
    {
        $expiresAt = now()->addMinutes(Config::get('auth.verification.expire', 60));
        $email = $notifiable->getEmailForVerification();

        VerificationCode::where('email', $email)
            ->where('used', false)
            ->update(['used' => true]);

        $code = (string) random_int(100000, 999999);

        VerificationCode::create([
            'email' => $email,
            'code' => $code,
            'expires_at' => $expiresAt,
        ]);

        return URL::temporarySignedRoute(
            'verification.verify',
            $expiresAt,
            [
                'id' => $notifiable->getKey(),
                'hash' => sha1($email),
                'code' => $code,
            ]
        );
    }

    public function toMail($notifiable): MailMessage
    {
        $verificationUrl = $this->verificationUrl($notifiable);

        return (new MailMessage)
            ->subject('Verify Your AMIS Enrollment Email')
            ->view('emails.verify-email', [
                'user' => $notifiable,
                'verificationUrl' => $verificationUrl,
            ]);
    }
}
