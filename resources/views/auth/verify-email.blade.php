<x-guest-layout>
    <div class="auth-page">
        <div class="auth-card verify-email-card" 
             x-data="{ 
                 email: '{{ addslashes(auth()->check() ? auth()->user()->email : session('email', session('verify_email', ''))) }}',
                 timeLeft: 300, 
                 timerText: '05:00', 
                 isExpired: false, 
                 interval: null,
                 init() {
                     const serverTimeLeft = {{ max(0, 300 - (time() - session('verify_timer_start', time()))) }};
                     
                     // Use localStorage to persist timer across page navigations
                     // (e.g. user clicks verify link in email, then comes back)
                     const storageKey = this.email 
                         ? 'amis_verify_expires_' + btoa(this.email).replace(/=/g, '') 
                         : null;
                     
                     let expiresAt = null;
                     if (storageKey) {
                         expiresAt = localStorage.getItem(storageKey);
                     }
                     
                     if (expiresAt) {
                         // Resume timer from localStorage
                         const remaining = Math.max(0, Math.floor((parseInt(expiresAt, 10) - Date.now()) / 1000));
                         this.timeLeft = remaining;
                     } else {
                         // First load — use server-computed time and store expiry
                         this.timeLeft = serverTimeLeft;
                         if (storageKey && serverTimeLeft > 0) {
                             localStorage.setItem(storageKey, String(Date.now() + (serverTimeLeft * 1000)));
                         }
                     }
                     
                     this.isExpired = this.timeLeft <= 0;
                     this.updateText();
                     
                     if (!this.isExpired) {
                         this.checkStatus();
                         this.interval = setInterval(() => {
                             if (this.timeLeft > 0) {
                                 this.timeLeft--;
                                 this.updateText();
                                 
                                 if (this.timeLeft % 3 === 0) {
                                     this.checkStatus();
                                 }
                             } else {
                                 this.isExpired = true;
                                 clearInterval(this.interval);
                             }
                         }, 1000);
                     }
                 },
                 updateText() {
                     const mins = Math.floor(this.timeLeft / 60);
                     const secs = this.timeLeft % 60;
                     this.timerText = `${String(mins).padStart(2, '0')}:${String(secs).padStart(2, '0')}`;
                 },
                 async checkStatus() {
                     try {
                         const response = await fetch('{{ route("verify.email.status") }}');
                         if (response.ok) {
                             const data = await response.json();
                             if (data.verified && data.redirectUrl) {
                                 this.resetTimer();
                                 window.location.href = data.redirectUrl;
                             }
                         }
                     } catch (e) {
                         console.error('Verification status check failed:', e);
                     }
                 },
                 resetTimer() {
                     // Clear localStorage timer so resend starts fresh
                     if (this.email) {
                         const storageKey = 'amis_verify_expires_' + btoa(this.email).replace(/=/g, '');
                         localStorage.removeItem(storageKey);
                     }
                 }
             }"
             style="max-width: 420px; text-align: center;">
            <div style="width:64px;height:64px;border-radius:50%;background:#f0fdf4;border:2px solid #bbf7d0;display:flex;align-items:center;justify-content:center;margin:0 auto 1.25rem;">
                <svg width="28" height="28" viewBox="0 0 24 24" fill="none" stroke="#059669" stroke-width="2">
                    <path d="M4 4h16c1.1 0 2 .9 2 2v12c0 1.1-.9 2-2 2H4c-1.1 0-2-.9-2-2V6c0-1.1.9-2 2-2z"/>
                    <polyline points="22,6 12,13 2,6"/>
                </svg>
            </div>

            <h1 style="font-size:1.375rem;font-weight:800;color:#111827;margin:0 0 0.5rem;">Check Your Email</h1>
            <p style="font-size:0.9375rem;color:#6b7280;margin:0 0 1.25rem;line-height:1.6;">
                We sent a verification link to<br>
                <strong style="color:#111827;">{{ auth()->check() ? auth()->user()->email : session('email', session('verify_email', 'your email address')) }}</strong>
            </p>

            {{-- Countdown Notice Box --}}
            <div style="background: #fffbeb; border: 1.5px solid #fcd34d; border-radius: 12px; padding: 0.75rem 1rem; margin-bottom: 1.25rem; display: flex; gap: 0.65rem; align-items: center; color: #b45309; text-align: left; font-family: inherit; font-size: 0.85rem; line-height: 1.4;">
                {{-- Clock Icon --}}
                <svg style="width: 20px; height: 20px; flex-shrink: 0;" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
                    <circle cx="12" cy="12" r="10"></circle>
                    <polyline points="12 6 12 12 16 14"></polyline>
                </svg>
                <div style="flex: 1; min-width: 0;">
                    <div style="font-weight: 800; text-transform: uppercase; font-size: 0.72rem; letter-spacing: 0.05em; margin-bottom: 0.15rem;">Verification Expiry</div>
                    <div x-show="!isExpired">
                        Verification link expires in <strong style="color: #b45309;">5 minutes</strong>.
                        <div style="margin-top: 0.15rem; font-weight: 750;">
                            Time remaining: <span x-text="timerText" style="font-family: monospace; font-size: 0.9rem; color: #dc2626; font-weight: 900;">05:00</span>
                        </div>
                    </div>
                    <div x-show="isExpired" x-cloak style="color: #dc2626; font-weight: 800; text-transform: uppercase; font-size: 0.76rem; letter-spacing: 0.03em;">
                        Verification link has expired! Please request a new one below.
                    </div>
                </div>
            </div>

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

            <form method="POST" action="{{ route('verify.email.resend') }}" data-loading-form @submit="resetTimer()">
                @csrf
                <input type="hidden" name="email" value="{{ auth()->check() ? auth()->user()->email : session('email', session('verify_email')) }}">
                <x-loading-button 
                    class="auth-button auth-button-outline" 
                    style="margin-bottom:1rem; transition: all 0.2s;" 
                    ::style="!isExpired ? 'opacity: 0.6; cursor: not-allowed; pointer-events: none;' : 'cursor: pointer;'"
                    loading="Sending email..."
                    ::disabled="!isExpired">
                    Resend Verification Email
                </x-loading-button>
            </form>

            <a href="{{ route('login') }}" style="font-size:0.875rem;color:#6b7280;text-decoration:none;">
                Back to Login
            </a>
        </div>
    </div>
</x-guest-layout>
