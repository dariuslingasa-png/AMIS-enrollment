<x-guest-layout :show-loader="false">
<div id="login-page" class="login-grid login-page visible">
    <section class="login-info auth-hero-panel">
        <div class="login-brand-block auth-hero-brand">
            <img src="{{ asset('images/AMIS_Logo.png') }}" alt="AMIS">
            <div>
                <div class="auth-hero-arabic" lang="ar" dir="rtl">المدرسة المنورة الإسلامية</div>
                <div class="auth-hero-school">AL MUNAWWARA ISLAMIC SCHOOL</div>
                <div class="auth-hero-subtitle">AMIS ENROLLMENT SYSTEM</div>
            </div>
        </div>

        <div class="login-headline-block auth-hero-copy">
            <span class="auth-hero-eyebrow">
                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" aria-hidden="true">
                    <path d="M22 10v6M2 10l10-5 10 5-10 5z"/>
                </svg>
                Now Enrolling SY 2026-2027
            </span>
            <h1>Select login method.</h1>
            <p>Log in or sign up using your Google or Microsoft account to open your pre-enrollment dashboard.</p>
        </div>

        <div class="auth-hero-flow">
            @php
                $steps = [
                    ['Student authentication', 'Sign in using Google or Microsoft.'],
                    ['Enrollment form', 'Complete student, parent, medical, and document details.'],
                    ['Payment proof', 'Upload GCash, Maya, or BDO receipt to secure the slot.'],
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
                    <span class="auth-entry-kicker">AMIS ENROLLMENT SYSTEM</span>
                    <h2 x-show="step === 'email'">Log in or sign up</h2>
                    <h2 x-show="step === 'otp'">Verify email</h2>
                    <p x-show="step === 'email'">Choose Google or Microsoft to open your pre-enrollment dashboard.</p>
                    <p x-show="step === 'otp'">We sent a 4-digit verification code to <strong x-text="email" style="color:#0f172a; word-break:break-all;"></strong>. Enter the code to continue.</p>
                </div>

                <!-- Messages -->
                <div class="auth-success-message" x-show="successMessage" x-text="successMessage" style="display:none;"></div>
                <div class="auth-error-message" x-show="errorMessage" x-text="errorMessage" style="display:none;"></div>

                <!-- Step 1: Email View -->
                <div x-show="step === 'email'">
                    <!-- Google Sign In Button -->
                    <a href="{{ route('auth.google') }}" class="btn-google-auth-premium" style="margin-bottom: 0.75rem;">
                        <svg class="auth-google-logo" width="18" height="18" viewBox="0 0 24 24" style="margin-right: 12px; flex-shrink: 0;">
                            <path fill="#EA4335" d="M12 5.04c1.62 0 3.06.56 4.21 1.66l3.15-3.15C17.45 1.76 14.94 1 12 1 7.35 1 3.39 3.65 1.44 7.5l3.8 2.94c.9-2.7 3.4-4.4 6.76-4.4z"/>
                            <path fill="#4285F4" d="M23.49 12.27c0-.81-.07-1.59-.2-2.34H12v4.44h6.44c-.28 1.48-1.12 2.73-2.38 3.58l3.69 2.87c2.16-1.99 3.4-4.93 3.4-8.55z"/>
                            <path fill="#FBBC05" d="M5.24 14.56c-.23-.69-.36-1.43-.36-2.2s.13-1.51.36-2.2L1.44 7.22C.52 9.07 0 11.13 0 13.3c0 2.17.52 4.23 1.44 6.08l3.8-2.82z"/>
                            <path fill="#34A853" d="M12 23c3.24 0 5.97-1.07 7.96-2.92l-3.69-2.87c-1.02.68-2.33 1.09-3.97 1.09-3.36 0-5.86-1.7-6.76-4.4l-3.8 2.94C3.39 20.35 7.35 23 12 23z"/>
                        </svg>
                        <span>Sign in with Google</span>
                    </a>

                    <!-- Microsoft Sign In Button -->
                    <a href="{{ route('auth.microsoft') }}" class="btn-microsoft-auth-premium" style="margin-bottom: 0.75rem;">
                        <svg class="auth-microsoft-logo" width="18" height="18" viewBox="0 0 23 23" fill="none" xmlns="http://www.w3.org/2000/svg" style="margin-right: 12px; flex-shrink: 0;">
                            <rect width="10.5" height="10.5" fill="#F25022"/>
                            <rect x="12.5" width="10.5" height="10.5" fill="#7FBA00"/>
                            <rect y="12.5" width="10.5" height="10.5" fill="#00A4EF"/>
                            <rect x="12.5" y="12.5" width="10.5" height="10.5" fill="#FFB900"/>
                        </svg>
                        <span>Sign in with Microsoft</span>
                    </a>

                    <div class="auth-option-divider" style="display: none;"><span>or</span></div>

                    <div class="form-group" style="display: none;">
                        <label for="email" class="premium-input-label">Email address</label>
                        <div class="premium-input-wrapper">
                            <svg class="premium-input-icon" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true">
                                <path d="M4 4h16c1.1 0 2 .9 2 2v12c0 1.1-.9 2-2 2H4c-1.1 0-2-.9-2-2V6c0-1.1.9-2 2-2z"/>
                                <polyline points="22,6 12,13 2,6"/>
                            </svg>
                            <input type="email" id="email" x-model="email" placeholder="Email address" required class="premium-input-field" @keydown.enter.prevent="submitEmail()">
                        </div>
                    </div>

                    <button type="button" class="auth-button premium-continue-button" @click="submitEmail()" :disabled="loading" style="display: none;">
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
