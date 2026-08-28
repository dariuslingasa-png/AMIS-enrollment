<style>
    @keyframes amisSpin {
        0% { transform: rotate(0deg); }
        100% { transform: rotate(360deg); }
    }
    .amis-spinner-icon {
        animation: amisSpin 0.85s linear infinite;
        flex-shrink: 0;
    }
    .premium-input-wrapper input#email {
        padding-left: 3.2rem !important;
        padding-right: 1.25rem !important;
    }
</style>
<x-guest-layout :show-loader="false">
<div id="login-page" class="login-grid login-page visible">
    {{-- Left Hero Panel --}}
    <section class="login-info auth-hero-panel" style="justify-content: center; padding: clamp(2.5rem, 4vw, 4rem); gap: 1rem;">
        {{-- Branding Block --}}
        <div class="login-brand-block auth-hero-brand">
            <img src="{{ asset('images/AMIS_Logo.png') }}" alt="AMIS Logo">
            <div style="min-width: 0;">
                <div class="auth-hero-arabic" lang="ar" dir="rtl">المدرسة المنورة الإسلامية</div>
                <div class="auth-hero-school">AL MUNAWWARA ISLAMIC SCHOOL</div>
            </div>
        </div>

        {{-- Main Left Headline & Subtitle --}}
        <div class="auth-hero-copy" style="max-width: 580px; margin-top: 0.25rem;">
            <h1 style="font-size: clamp(1.5rem, 2.2vw, 2.1rem); font-weight: 900; line-height: 1.25; color: #ffffff; margin: 0 0 0.4rem 0;">
                AMIS Enrollment System
            </h1>
            <p style="font-size: 0.9rem; line-height: 1.5; color: rgba(255, 255, 255, 0.88); font-weight: 500; margin: 0;">
                Watch our enrollment guide and review the frequently asked questions before starting your application.
            </p>
        </div>

        {{-- Video Guide Preview Card (Disabled non-interactive state) --}}
        <div class="video-guide-preview-card" style="margin-top: 0.85rem; background: rgba(15, 23, 42, 0.4); border: 1px solid rgba(255, 255, 255, 0.18); border-radius: 18px; padding: 1.35rem 1.25rem; text-align: center; backdrop-filter: blur(12px); position: relative; overflow: hidden; max-width: 580px; box-shadow: 0 12px 32px rgba(0,0,0,0.18);">
            <div style="position: absolute; top: 12px; right: 12px; background: rgba(245, 158, 11, 0.25); border: 1px solid rgba(245, 158, 11, 0.5); color: #fef08a; font-size: 0.68rem; font-weight: 800; padding: 0.25rem 0.65rem; border-radius: 999px; text-transform: uppercase; letter-spacing: 0.06em;">
                COMING SOON
            </div>
            
            <div style="width: 46px; height: 46px; margin: 0 auto 0.75rem; border-radius: 50%; background: rgba(255, 255, 255, 0.12); border: 2px solid rgba(255, 255, 255, 0.3); color: rgba(255, 255, 255, 0.6); display: flex; align-items: center; justify-content: center; pointer-events: none;">
                <svg width="20" height="20" viewBox="0 0 24 24" fill="currentColor" style="margin-left: 3px;">
                    <polygon points="5 3 19 12 5 21 5 3"></polygon>
                </svg>
            </div>

            <h3 style="margin: 0; font-size: 1rem; font-weight: 800; color: #ffffff; letter-spacing: 0.03em;">
                ENROLLMENT VIDEO GUIDE
            </h3>
            <p style="margin: 0.25rem 0 0 0; font-size: 0.78rem; color: rgba(255, 255, 255, 0.7); font-weight: 500;">
                Video walkthrough is currently being prepared for SY 2026–2027.
            </p>
        </div>

        {{-- Enrollment FAQs Component --}}
        <div style="max-width: 580px; width: 100%; margin-top: 0.85rem;">
            <x-enrollment-faq-modal />
        </div>

        {{-- Left Footer Copyright --}}
        <div style="margin-top: 2rem; padding-top: 1.25rem; border-top: 1px solid rgba(255, 255, 255, 0.15); color: rgba(255, 255, 255, 0.75); font-size: 0.8rem; font-weight: 500;">
            © 2026 Al Munawwara Islamic School. All rights reserved. &bull; AMIS Enrollment System
        </div>
    </section>

    {{-- Right Login Panel --}}
    <section class="login-form">
        <div class="login-form-panel">
            <div class="auth-entry-card" x-data="{ 
                email: '', 
                otp: ['', '', '', ''], 
                step: 'providers',
                showEmailForm: false, 
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
                            'Accept': 'application/json, text/plain, */*',
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
                            'Accept': 'application/json, text/plain, */*',
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
                            'Accept': 'application/json, text/plain, */*',
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
                {{-- Mobile Brand Header --}}
                <div class="auth-entry-mobile-brand">
                    <img src="{{ asset('images/AMIS_Logo.png') }}" alt="AMIS" class="auth-entry-mobile-logo">
                    <div class="auth-entry-mobile-arabic" lang="ar" dir="rtl">المدرسة المنورة الإسلامية</div>
                    <div class="auth-entry-mobile-school">AL MUNAWWARA ISLAMIC SCHOOL</div>
                    <div style="color: #059669; font-size: 0.75rem; font-weight: 800; letter-spacing: 0.05em; text-transform: uppercase; margin-top: 2px;">AMIS ENROLLMENT SYSTEM</div>
                </div>

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

                <!-- Step 1: Login Providers Options -->
                <div x-show="step === 'providers'" x-transition:enter="transition ease-out duration-200" x-transition:enter-start="opacity-0 transform scale-95" x-transition:enter-end="opacity-100 transform scale-100">
                    <!-- Google Sign In -->
                    <a href="{{ route('auth.google') }}" class="auth-provider-button google-auth-button" style="margin-bottom: 0.75rem; width: 100%; min-height: 50px; border-radius: 9999px; border: 1.5px solid #e2e8f0; background: #ffffff; padding: 0.6rem 1.25rem; display: flex; align-items: center; justify-content: center; gap: 0.65rem; text-decoration: none; font-weight: 600; color: #1e293b;">
                        <svg class="auth-google-logo" width="18" height="18" viewBox="0 0 24 24" aria-hidden="true" style="flex-shrink: 0;">
                            <path fill="#4285F4" d="M22.56 12.25c0-.78-.07-1.53-.2-2.25H12v4.26h5.92c-.26 1.37-1.04 2.53-2.21 3.31v2.77h3.57c2.08-1.92 3.28-4.74 3.28-8.09z"/>
                            <path fill="#34A853" d="M12 23c2.97 0 5.46-.98 7.28-2.66l-3.57-2.77c-.98.66-2.23 1.06-3.71 1.06-2.86 0-5.29-1.93-6.16-4.53H2.18v2.84C3.99 20.53 7.7 23 12 23z"/>
                            <path fill="#FBBC05" d="M5.84 14.09c-.22-.66-.35-1.36-.35-2.09s.13-1.43.35-2.09V7.06H2.18C1.43 8.55 1 10.22 1 12s.43 3.45 1.18 4.94l2.85-2.22.81-.63z"/>
                            <path fill="#EA4335" d="M12 5.38c1.62 0 3.06.56 4.21 1.64l3.15-3.15C17.45 2.09 14.97 1 12 1 7.7 1 3.99 3.47 2.18 7.06l3.66 2.84c.87-2.6 3.3-4.52 6.16-4.52z"/>
                        </svg>
                        <span>Sign in with Google</span>
                    </a>

                    <!-- Microsoft Sign In -->
                    <a href="{{ route('auth.microsoft') }}" class="auth-provider-button microsoft-auth-button" style="margin-bottom: 0.75rem; width: 100%; min-height: 50px; border-radius: 9999px; border: 1.5px solid #e2e8f0; background: #ffffff; padding: 0.6rem 1.25rem; display: flex; align-items: center; justify-content: center; gap: 0.65rem; text-decoration: none; font-weight: 600; color: #1e293b;">
                        <svg class="auth-microsoft-logo" width="18" height="18" viewBox="0 0 23 23" fill="none" xmlns="http://www.w3.org/2000/svg" style="flex-shrink: 0;">
                            <rect width="10.5" height="10.5" fill="#F25022"/>
                            <rect x="12.5" width="10.5" height="10.5" fill="#7FBA00"/>
                            <rect y="12.5" width="10.5" height="10.5" fill="#00A4EF"/>
                            <rect x="12.5" y="12.5" width="10.5" height="10.5" fill="#FFB900"/>
                        </svg>
                        <span>Sign in with Microsoft</span>
                    </a>

                    <!-- WhatsApp Sign In (Coming Soon) -->
                    <div class="auth-coming-soon-option" style="margin-bottom: 0.75rem; min-height: 50px; border-radius: 9999px; border: 1.5px dashed #cbd5e1; background: #f8fafc; padding: 0.6rem 1.25rem; display: flex; align-items: center; justify-content: space-between;">
                        <div style="display: flex; align-items: center; gap: 0.65rem;">
                            <svg width="20" height="20" viewBox="0 0 24 24" fill="#25D366" style="flex-shrink: 0;">
                                <path d="M.057 24l1.687-6.163c-1.041-1.804-1.588-3.849-1.587-5.946.003-6.556 5.338-11.891 11.893-11.891 3.181.001 6.167 1.24 8.413 3.488 2.245 2.248 3.481 5.236 3.48 8.414-.003 6.557-5.338 11.892-11.893 11.892-1.99-.001-3.951-.5-5.688-1.448l-6.305 1.654zm6.597-3.807c1.676.995 3.276 1.591 5.392 1.592 5.448 0 9.886-4.434 9.889-9.885.002-5.462-4.415-9.89-9.881-9.892-5.452 0-9.887 4.434-9.889 9.884-.001 2.225.651 3.891 1.746 5.634l-.999 3.648 3.742-.981zm11.387-5.464c-.074-.124-.272-.198-.57-.347-.297-.149-1.758-.868-2.031-.967-.272-.099-.47-.149-.669.149-.198.297-.768.967-.941 1.165-.173.198-.347.223-.644.074-.297-.149-1.255-.462-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.297-.347.446-.521.151-.172.2-.296.3-.495.099-.198.05-.372-.025-.521-.075-.148-.669-1.611-.916-2.206-.242-.579-.487-.501-.669-.51l-.57-.01c-.198 0-.52.074-.792.372s-1.04 1.016-1.04 2.479 1.065 2.876 1.213 3.074c.149.198 2.095 3.2 5.076 4.487.709.306 1.263.489 1.694.626.712.226 1.36.194 1.872.118.571-.085 1.758-.719 2.006-1.413.248-.695.248-1.29.173-1.414z"/>
                            </svg>
                            <span style="font-size: 0.9rem; font-weight: 600; color: #64748b;">Sign in with WhatsApp</span>
                        </div>
                        <span style="background: #fef3c7; color: #92400e; font-size: 0.65rem; font-weight: 800; padding: 0.2rem 0.5rem; border-radius: 999px; text-transform: uppercase;">COMING SOON</span>
                    </div>

                    <!-- Active Email Sign In Button (Transitions to Step 2 Card) -->
                    <button type="button" @click="step = 'email'; errorMessage = ''; successMessage = '';" class="auth-provider-button email-auth-button" style="margin-bottom: 0.75rem; width: 100%; min-height: 50px; border-radius: 9999px; border: 1.5px solid #064e3b; background: #ffffff; padding: 0.6rem 1.25rem; display: flex; align-items: center; justify-content: center; gap: 0.65rem; font-weight: 600; color: #064e3b; cursor: pointer; transition: all 0.2s ease;">
                        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="#064e3b" stroke-width="2" aria-hidden="true" style="flex-shrink: 0;">
                            <path d="M4 4h16c1.1 0 2 .9 2 2v12c0 1.1-.9 2-2 2H4c-1.1 0-2-.9-2-2V6c0-1.1.9-2 2-2z"/>
                            <polyline points="22,6 12,13 2,6"/>
                        </svg>
                        <span style="font-size: 0.9rem; font-weight: 700;">Sign in with Email</span>
                    </button>
                </div>

                <!-- Step 2: Dedicated Email Card View -->
                <div x-show="step === 'email'" style="display:none;" x-transition:enter="transition ease-out duration-200" x-transition:enter-start="opacity-0 transform scale-95" x-transition:enter-end="opacity-100 transform scale-100">
                    <div style="margin-bottom: 1.25rem;">
                        <a href="#" @click.prevent="step = 'providers'; errorMessage = ''; successMessage = '';" style="display: inline-flex; align-items: center; gap: 0.4rem; color: #064e3b; font-size: 0.85rem; font-weight: 700; text-decoration: none;">
                            &larr; Back to login options
                        </a>
                    </div>

                    <h2 style="font-size: 1.35rem; font-weight: 800; color: #064e3b; margin-bottom: 0.35rem; font-family: 'Outfit', sans-serif;">Sign in with Email</h2>
                    <p style="font-size: 0.85rem; color: #64748b; margin-bottom: 1.25rem;">Enter your email address below to receive a 4-digit verification code.</p>

                    <div class="form-group" style="margin-bottom: 1rem;">
                        <label for="email" class="premium-input-label" style="display: block; font-size: 0.85rem; font-weight: 700; color: #1e293b; margin-bottom: 0.4rem;">Email address</label>
                        <div class="premium-input-wrapper" style="position: relative; display: flex; align-items: center; width: 100%;">
                            <svg class="premium-input-icon" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="#64748b" stroke-width="2" aria-hidden="true" style="position: absolute; left: 1.1rem; top: 50%; transform: translateY(-50%); pointer-events: none; z-index: 10; flex-shrink: 0;">
                                <path d="M4 4h16c1.1 0 2 .9 2 2v12c0 1.1-.9 2-2 2H4c-1.1 0-2-.9-2-2V6c0-1.1.9-2 2-2z"/>
                                <polyline points="22,6 12,13 2,6"/>
                            </svg>
                            <input type="email" id="email" x-model="email" placeholder="Enter your email address" class="premium-input-field" style="width: 100%; padding-left: 3.1rem !important; padding-right: 1.25rem !important; padding-top: 0.8rem !important; padding-bottom: 0.8rem !important; border-radius: 9999px; border: 1.5px solid #cbd5e1; font-size: 0.95rem; outline: none; background: #ffffff; color: #0f172a; box-sizing: border-box;" @keydown.enter.prevent="submitEmail()">
                        </div>
                    </div>

                    <button type="button" class="auth-button premium-continue-button" @click="submitEmail()" :disabled="loading || !email" style="width: 100%; min-height: 48px; border-radius: 9999px; background: #064e3b; color: #ffffff; font-weight: 700; font-size: 0.95rem; border: none; cursor: pointer; display: flex; align-items: center; justify-content: center; gap: 0.6rem;">
                        <span x-show="!loading">Send OTP Code</span>
                        <span x-show="loading" style="display: flex; align-items: center; justify-content: center; gap: 0.6rem;">
                            <svg class="amis-spinner-icon" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5">
                                <circle cx="12" cy="12" r="10" stroke="currentColor" stroke-width="2.5" stroke-dasharray="32" stroke-dashoffset="10" fill="none"/>
                            </svg>
                            <span>Sending OTP Code...</span>
                        </span>
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

                    <button type="button" class="auth-button premium-continue-button" @click="verifyOtpCode()" :disabled="loading || otp.join('').length !== 4" style="width: 100%; min-height: 48px; border-radius: 9999px; background: #064e3b; color: #ffffff; font-weight: 700; font-size: 0.95rem; border: none; cursor: pointer; display: flex; align-items: center; justify-content: center; gap: 0.6rem; margin-top: 1.5rem;">
                        <span x-show="!loading">Verify Code & Sign In</span>
                        <span x-show="loading" style="display: flex; align-items: center; justify-content: center; gap: 0.6rem;">
                            <svg class="amis-spinner-icon" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5">
                                <circle cx="12" cy="12" r="10" stroke="currentColor" stroke-width="2.5" stroke-dasharray="32" stroke-dashoffset="10" fill="none"/>
                            </svg>
                            <span>Verifying Code...</span>
                        </span>
                    </button>

                    <div style="display:flex; justify-content:space-between; align-items:center; margin-top:1.5rem; font-size:0.85rem;">
                        <a href="#" @click.prevent="step = 'email'; errorMessage = ''; successMessage = '';" class="otp-back-link">&larr; Back to email</a>
                        <a href="#" @click.prevent="resendOtpCode()" class="otp-resend-link" :style="loading ? 'pointer-events:none; opacity:0.5;' : ''">Resend Code</a>
                    </div>
                </div>

                <p class="auth-entry-note" style="margin-top: 1.5rem; text-align: center; color: #64748b; font-size: 0.8rem;">
                    Sign in options are protected by AMIS security policies.
                </p>

                {{-- Mobile FAQ Section --}}
                <div class="mobile-faq-wrapper" style="display: none; margin-top: 1.5rem;">
                    <x-enrollment-faq-modal />
                </div>

                {{-- Mobile Copyright Footer --}}
                <div style="margin-top: 1.5rem; text-align: center; color: #94a3b8; font-size: 0.78rem; font-weight: 500;">
                    © 2026 Al Munawwara Islamic School. All rights reserved.
                </div>
            </div>
        </div>
    </section>
</div>
</x-guest-layout>
