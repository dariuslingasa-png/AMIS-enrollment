<x-guest-layout :show-loader="false">
    <div class="auth-page">
        <div class="auth-card" style="max-width: 440px; text-align: center; padding: 2.5rem 2rem;">
            {{-- Envelope Icon --}}
            <div style="width:72px;height:72px;border-radius:50%;background:#ecfdf5;border:2.2px solid #a7f3d0;display:flex;align-items:center;justify-content:center;margin:0 auto 1.5rem;box-shadow: 0 10px 15px -3px rgba(5, 150, 105, 0.08);">
                <svg width="30" height="30" viewBox="0 0 24 24" fill="none" stroke="#059669" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"></path>
                    <polyline points="22 4 12 14.01 9 11.01"></polyline>
                </svg>
            </div>

            <h1 style="font-size:1.5rem;font-weight:900;color:#111827;margin:0 0 0.75rem;letter-spacing:-0.025em;">
                Confirm Verification
            </h1>
            <p style="font-size:0.9375rem;color:#4b5563;margin:0 0 2rem;line-height:1.6;">
                Click the button below to verify your email address (<strong style="color:#111827;">{{ $email }}</strong>) and continue to your AMIS pre-enrollment dashboard.
            </p>

            <form method="POST" action="{{ route('verification.verify.post', ['id' => $id, 'hash' => $hash, 'token' => $token]) }}" data-loading-form>
                @csrf
                <x-loading-button class="auth-button auth-link-button" loading="Verifying..." style="width:100%; box-sizing:border-box; cursor: pointer;">
                    Verify and Continue
                </x-loading-button>
            </form>
        </div>
    </div>
</x-guest-layout>
