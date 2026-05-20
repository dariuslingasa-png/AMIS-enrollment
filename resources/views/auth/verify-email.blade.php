<x-guest-layout>
    <div class="auth-page">
        <div class="auth-card verify-email-card" style="max-width: 420px; text-align: center;">
            <div style="width:64px;height:64px;border-radius:50%;background:#f0fdf4;border:2px solid #bbf7d0;display:flex;align-items:center;justify-content:center;margin:0 auto 1.25rem;">
                <svg width="28" height="28" viewBox="0 0 24 24" fill="none" stroke="#059669" stroke-width="2">
                    <path d="M4 4h16c1.1 0 2 .9 2 2v12c0 1.1-.9 2-2 2H4c-1.1 0-2-.9-2-2V6c0-1.1.9-2 2-2z"/>
                    <polyline points="22,6 12,13 2,6"/>
                </svg>
            </div>

            <h1 style="font-size:1.375rem;font-weight:800;color:#111827;margin:0 0 0.5rem;">Check Your Email</h1>
            <p style="font-size:0.9375rem;color:#6b7280;margin:0 0 1.5rem;line-height:1.6;">
                We sent a verification link to<br>
                <strong style="color:#111827;">{{ session('email', 'your email address') }}</strong>
            </p>

            <div style="background:#f9fafb;border:1px solid #e5e7eb;border-radius:12px;padding:1.25rem;margin-bottom:1.5rem;text-align:left;">
                <div style="font-size:0.8125rem;font-weight:700;color:#374151;margin-bottom:0.75rem;">What to do:</div>
                <ol style="font-size:0.875rem;color:#6b7280;margin:0;padding-left:1.25rem;line-height:2;">
                    <li>Open your email inbox</li>
                    <li>If it is not there, check your Spam or Junk folder</li>
                    <li>Find the email from AMIS</li>
                    <li>Click the <strong style="color:#059669;">Verify My Email</strong> button</li>
                    <li>You'll be redirected to your dashboard</li>
                </ol>
            </div>

            <form method="POST" action="{{ route('verify.email.resend') }}" data-loading-form>
                @csrf
                <input type="hidden" name="email" value="{{ session('email') }}">
                <x-loading-button class="auth-button auth-button-outline" style="margin-bottom:1rem;" loading="Sending email...">
                    Resend Verification Email
                </x-loading-button>
            </form>

            <a href="{{ route('login') }}" style="font-size:0.875rem;color:#6b7280;text-decoration:none;">
                Back to Login
            </a>
        </div>
    </div>
</x-guest-layout>
