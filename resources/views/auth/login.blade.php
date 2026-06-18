<x-guest-layout :show-loader="false">
<div id="login-page" class="login-grid login-page visible">
    <section class="login-info auth-hero-panel">
        <div class="login-brand-block auth-hero-brand">
            <img src="{{ asset('images/AMIS_Logo.png') }}" alt="AMIS">
            <div>
                <div class="auth-hero-arabic" lang="ar" dir="rtl">المدرسة المنورة الإسلامية</div>
                <div class="auth-hero-school">AL MUNAWWARA ISLAMIC SCHOOL</div>
                <div class="auth-hero-subtitle">Online Enrollment Portal</div>
            </div>
        </div>

        <div class="login-headline-block auth-hero-copy">
            <span class="auth-hero-eyebrow">
                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" aria-hidden="true">
                    <path d="M22 10v6M2 10l10-5 10 5-10 5z"/>
                </svg>
                Now Enrolling SY 2026-2027
            </span>
            <h1>Start with your email.</h1>
            <p>Enter any parent or student email. We send a secure verification link every time before opening the dashboard.</p>
        </div>

        <div class="auth-hero-flow">
            @php
                $steps = [
                    ['Email verification', 'Use your email and open the secure link we send.'],
                    ['Enrollment form', 'Complete student, parent, medical, and document details.'],
                    ['School review', 'Track status and payment updates from your dashboard.'],
                ];
            @endphp
            @foreach ($steps as $index => [$title, $copy])
                <div class="auth-hero-flow-item">
                    <span>{{ $index + 1 }}</span>
                    <div>
                        <strong>{{ $title }}</strong>
                        <p>{{ $copy }}</p>
                    </div>
                </div>
            @endforeach
        </div>
    </section>

    <section class="login-form">
        <div class="login-form-panel">
            <div class="auth-entry-card" x-data="{ 
                email: '', 
                otp: ['', '', '', ''], 
                step: 'email', 
                loading: false, 
                errorMessage: '', 
                successMessage: '',
                submitEmail() {
                    if (!this.email || !this.email.includes('@')) {
                        this.errorMessage = 'Please enter a valid email address.';
                        return;
                    }
                    this.loading = true;
                    this.errorMessage = '';
                    this.successMessage = '';

                    fetch('{{ route('auth.send-otp') }}', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'X-CSRF-TOKEN': document.querySelector('meta[name=&quot;csrf-token&quot;]').getAttribute('content')
                        },
                        body: JSON.stringify({ email: this.email })
                    })
                    .then(response => response.json().then(data => ({ status: response.status, data })))
                    .then(res => {
                        this.loading = false;
                        if (res.status === 200 && res.data.status === 'success') {
                            this.step = 'otp';
                            this.successMessage = res.data.message;
                            this.$nextTick(() => {
                                this.$refs.otp0.focus();
                            });
                        } else {
                            this.errorMessage = res.data.message || 'An error occurred. Please try again.';
                        }
                    })
                    .catch(err => {
                        this.loading = false;
                        this.errorMessage = 'Network error. Please check your internet connection.';
                    });
                },
                handleOtpInput(event, index) {
                    const val = event.target.value;
                    this.otp[index] = val.replace(/[^0-9]/g, '');

                    if (this.otp[index] && index < 3) {
                        this.$refs['otp' + (index + 1)].focus();
                    }

                    if (this.otp.join('').length === 4) {
                        this.verifyOtpCode();
                    }
                },
                handleOtpKeydown(event, index) {
                    if (event.key === 'Backspace') {
                        if (!this.otp[index] && index > 0) {
                            this.otp[index - 1] = '';
                            this.$refs['otp' + (index - 1)].focus();
                        }
                    }
                },
                verifyOtpCode() {
                    const code = this.otp.join('');
                    if (code.length !== 4) return;

                    this.loading = true;
                    this.errorMessage = '';
                    this.successMessage = '';

                    fetch('{{ route('auth.verify-otp') }}', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'X-CSRF-TOKEN': document.querySelector('meta[name=&quot;csrf-token&quot;]').getAttribute('content')
                        },
                        body: JSON.stringify({ email: this.email, code: code })
                    })
                    .then(response => response.json().then(data => ({ status: response.status, data })))
                    .then(res => {
                        this.loading = false;
                        if (res.status === 200 && res.data.status === 'success') {
                            window.location.href = res.data.redirectUrl;
                        } else {
                            this.errorMessage = res.data.message || 'Invalid verification code.';
                            this.otp = ['', '', '', ''];
                            this.$nextTick(() => {
                                this.$refs.otp0.focus();
                            });
                        }
                    })
                    .catch(err => {
                        this.loading = false;
                        this.errorMessage = 'Network error. Please try again.';
                    });
                },
                resendOtpCode() {
                    this.loading = true;
                    this.errorMessage = '';
                    this.successMessage = '';

                    fetch('{{ route('auth.send-otp') }}', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'X-CSRF-TOKEN': document.querySelector('meta[name=&quot;csrf-token&quot;]').getAttribute('content')
                        },
                        body: JSON.stringify({ email: this.email })
                    })
                    .then(response => response.json().then(data => ({ status: response.status, data })))
                    .then(res => {
                        this.loading = false;
                        if (res.status === 200 && res.data.status === 'success') {
                            this.successMessage = 'A new 4-digit code has been sent!';
                            this.otp = ['', '', '', ''];
                            this.$nextTick(() => {
                                this.$refs.otp0.focus();
                            });
                        } else {
                            this.errorMessage = res.data.message || 'Could not resend the code.';
                        }
                    })
                    .catch(err => {
                        this.loading = false;
                        this.errorMessage = 'Network error. Please try again.';
                    });
                }
            }">
                <div class="auth-entry-heading">
                    <span class="auth-entry-kicker">AMIS Enrollment</span>
                    <h2 x-show="step === 'email'">Log in or sign up</h2>
                    <h2 x-show="step === 'otp'">Verify email</h2>
                    <p x-show="step === 'email'">Enter your email to verify and open your pre-enrollment dashboard.</p>
                    <p x-show="step === 'otp'">We sent a 4-digit verification code to <strong x-text="email" style="color:#0f172a; word-break:break-all;"></strong>. Enter the code to continue.</p>
                </div>

                <!-- Messages -->
                <div class="auth-success-message" x-show="successMessage" x-text="successMessage" style="display:none;"></div>
                <div class="auth-error-message" x-show="errorMessage" x-text="errorMessage" style="display:none;"></div>

                <!-- Step 1: Email View -->
                <div x-show="step === 'email'">
                    <!-- Google Sign In Button at the Top -->
                    <a href="{{ route('auth.google') }}" class="btn-google-auth-premium">
                        <svg width="20" height="20" viewBox="0 0 48 48" aria-hidden="true" style="margin-right: 12px; flex-shrink: 0;">
                            <path fill="#FFC107" d="M43.6 20.5H42V20H24v8h11.3C33.6 32.7 29.2 36 24 36c-6.6 0-12-5.4-12-12s5.4-12 12-12c3.1 0 5.9 1.2 8 3.1l5.7-5.7C34.1 6.1 29.3 4 24 4 13 4 4 13 4 24s9 20 20 20 20-9 20-20c0-1.3-.1-2.4-.4-3.5z"/>
                            <path fill="#FF3D00" d="M6.3 14.7l6.6 4.8C14.7 15.1 19 12 24 12c3.1 0 5.9 1.2 8 3.1l5.7-5.7C34.1 6.1 29.3 4 24 4 16.2 4 9.5 8.5 6.3 14.7z"/>
                            <path fill="#4CAF50" d="M24 44c5.1 0 9.8-2 13.3-5.2l-6.1-5.2C29.2 35.1 26.7 36 24 36c-5.2 0-9.6-3.3-11.3-7.9l-6.5 5C9.3 39.5 16.1 44 24 44z"/>
                            <path fill="#1976D2" d="M43.6 20.5H42V20H24v8h11.3c-.8 2.3-2.2 4.2-4.1 5.6l6.1 5.2C36.9 39.2 44 34 44 24c0-1.3-.1-2.4-.4-3.5z"/>
                        </svg>
                        <span>Continue with Google</span>
                    </a>

                    <div class="auth-option-divider"><span>or</span></div>

                    <div class="form-group">
                        <label for="email" class="premium-input-label">Email address</label>
                        <div class="input-with-icon premium-input-wrapper">
                            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true" style="color: #64748b;">
                                <path d="M4 4h16c1.1 0 2 .9 2 2v12c0 1.1-.9 2-2 2H4c-1.1 0-2-.9-2-2V6c0-1.1.9-2 2-2z"/>
                                <polyline points="22,6 12,13 2,6"/>
                            </svg>
                            <input type="email" id="email" x-model="email" placeholder="Email address" required class="premium-input-field" @keydown.enter.prevent="submitEmail()">
                        </div>
                    </div>

                    <button type="button" class="auth-button premium-continue-button" @click="submitEmail()" :disabled="loading">
                        <span x-show="!loading">Continue</span>
                        <span x-show="loading" class="premium-spinner"></span>
                    </button>
                </div>

                <!-- Step 2: OTP View -->
                <div x-show="step === 'otp'" style="display:none;">
                    <div class="otp-inputs-row">
                        <input type="text" maxlength="1" pattern="[0-9]*" inputmode="numeric" class="otp-input-box" 
                               x-model="otp[0]" @input="handleOtpInput($event, 0)" @keydown="handleOtpKeydown($event, 0)" x-ref="otp0">
                        <input type="text" maxlength="1" pattern="[0-9]*" inputmode="numeric" class="otp-input-box" 
                               x-model="otp[1]" @input="handleOtpInput($event, 1)" @keydown="handleOtpKeydown($event, 1)" x-ref="otp1">
                        <input type="text" maxlength="1" pattern="[0-9]*" inputmode="numeric" class="otp-input-box" 
                               x-model="otp[2]" @input="handleOtpInput($event, 2)" @keydown="handleOtpKeydown($event, 2)" x-ref="otp2">
                        <input type="text" maxlength="1" pattern="[0-9]*" inputmode="numeric" class="otp-input-box" 
                               x-model="otp[3]" @input="handleOtpInput($event, 3)" @keydown="handleOtpKeydown($event, 3)" x-ref="otp3">
                    </div>

                    <button type="button" class="auth-button premium-continue-button" @click="verifyOtpCode()" :disabled="loading || otp.join('').length !== 4" style="margin-top: 1.5rem;">
                        <span x-show="!loading">Verify Code</span>
                        <span x-show="loading" class="premium-spinner"></span>
                    </button>

                    <div style="display:flex; justify-content:space-between; align-items:center; margin-top:1.5rem; font-size:0.85rem;">
                        <a href="#" @click.prevent="step = 'email'; errorMessage = ''; successMessage = '';" class="otp-back-link">&larr; Back to email</a>
                        <a href="#" @click.prevent="resendOtpCode()" class="otp-resend-link" :style="loading ? 'pointer-events:none; opacity:0.5;' : ''">Resend Code</a>
                    </div>
                </div>

                <p class="auth-entry-note" style="margin-top: 1.5rem; text-align: center; color: #64748b; font-size: 0.8rem;">
                    Sign in options are protected by AMIS security policies.
                </p>
            </div>
        </div>
    </section>
</div>
</x-guest-layout>
