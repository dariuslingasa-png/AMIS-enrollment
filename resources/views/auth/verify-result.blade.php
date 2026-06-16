<x-guest-layout :show-loader="false">
    <div class="auth-page">
        <div class="auth-card" style="max-width: 440px; text-align: center; padding: 2.5rem 2rem;">
            @if ($status === 'success')
                {{-- Success Icon --}}
                <div style="width:72px;height:72px;border-radius:50%;background:#f0fdf4;border:2.2px solid #bbf7d0;display:flex;align-items:center;justify-content:center;margin:0 auto 1.5rem;box-shadow: 0 10px 15px -3px rgba(16, 185, 129, 0.1);">
                    <svg width="32" height="32" viewBox="0 0 24 24" fill="none" stroke="#059669" stroke-width="3" stroke-linecap="round" stroke-linejoin="round">
                        <polyline points="20 6 9 17 4 12"></polyline>
                    </svg>
                </div>

                <h1 style="font-size:1.5rem;font-weight:900;color:#111827;margin:0 0 0.75rem;letter-spacing:-0.025em;">
                    {{ $message }}
                </h1>
                <p style="font-size:0.9375rem;color:#4b5563;margin:0 0 2rem;line-height:1.6;">
                    Your email has been verified successfully. Redirecting you to your enrollment dashboard...
                </p>

                <div style="margin-bottom: 0.5rem;">
                    <a href="{{ $redirectUrl }}" class="auth-button auth-link-button" style="text-decoration:none; display:inline-flex; align-items:center; justify-content:center; width:100%; box-sizing:border-box;">
                        Go to Dashboard
                    </a>
                </div>

                <script>
                    setTimeout(function() {
                        window.location.href = "{{ $redirectUrl }}";
                    }, 2500);
                </script>
            @else
                {{-- Error Icon --}}
                <div style="width:72px;height:72px;border-radius:50%;background:#fef2f2;border:2.2px solid #fecaca;display:flex;align-items:center;justify-content:center;margin:0 auto 1.5rem;box-shadow: 0 10px 15px -3px rgba(239, 68, 68, 0.1);">
                    <svg width="32" height="32" viewBox="0 0 24 24" fill="none" stroke="#dc2626" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
                        <circle cx="12" cy="12" r="10"></circle>
                        <line x1="15" y1="9" x2="9" y2="15"></line>
                        <line x1="9" y1="9" x2="15" y2="15"></line>
                    </svg>
                </div>

                <h1 style="font-size:1.5rem;font-weight:900;color:#111827;margin:0 0 0.75rem;letter-spacing:-0.025em;">
                    @if ($message === 'Link Expired')
                        Verification Link Expired
                    @elseif ($message === 'Link Already Used')
                        Link Already Used
                    @else
                        Invalid Verification Link
                    @endif
                </h1>
                
                <p style="font-size:0.9375rem;color:#4b5563;margin:0 0 2rem;line-height:1.6;">
                    @if ($message === 'Link Expired')
                        This secure verification link has expired because it was not used within 5 minutes. Please request a new secure link.
                    @elseif ($message === 'Link Already Used')
                        This verification link has already been used to log in. For security, magic links can only be opened once.
                    @else
                        This verification link is invalid, corrupted, or has been tampered with. Please check the URL or request a new link.
                    @endif
                </p>

                <div>
                    <a href="{{ route('login') }}" class="auth-button auth-link-button" style="text-decoration:none; display:inline-flex; align-items:center; justify-content:center; width:100%; box-sizing:border-box;">
                        Back to Login
                    </a>
                </div>
            @endif
        </div>
    </div>
</x-guest-layout>
