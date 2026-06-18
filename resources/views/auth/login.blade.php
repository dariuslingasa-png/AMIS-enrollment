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
            <div class="auth-entry-card">
                <div class="auth-entry-heading">
                    <span class="auth-entry-kicker">AMIS Enrollment</span>
                    <h2>Continue to enrollment</h2>
                    <p>Use your student or parent email. Existing and new emails always receive a verification link.</p>
                </div>

                @if (session('success'))
                    <div class="auth-success-message">
                        {{ session('success') }}
                    </div>
                @endif

                @if ($errors->any())
                    <div class="auth-error-message">
                        {{ $errors->first() }}
                    </div>
                @endif

                <form method="POST" action="{{ route('register.store') }}" class="auth-form auth-username-form" data-loading-form>
                    @csrf

                    <div class="form-group">
                        <label for="email">Email Address</label>
                        <div class="input-with-icon">
                            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true">
                                <path d="M4 4h16c1.1 0 2 .9 2 2v12c0 1.1-.9 2-2 2H4c-1.1 0-2-.9-2-2V6c0-1.1.9-2 2-2z"/>
                                <polyline points="22,6 12,13 2,6"/>
                            </svg>
                            <input type="email" id="email" name="email" placeholder="student@gmail.com or parent@gmail.com" value="{{ old('email') }}" required autofocus autocomplete="email">
                        </div>
                    </div>

                    <x-loading-button class="auth-button auth-link-button" loading="Sending link...">
                        Send Verification Link
                    </x-loading-button>
                </form>

                <div class="auth-option-divider"><span>or</span></div>

                <a href="{{ route('auth.google') }}" class="auth-coming-soon-option auth-google-soon" style="border-style: solid; background: white; cursor: pointer; text-decoration: none; transition: all 0.2s;" onmouseover="this.style.background='#f8fafc'; this.style.borderColor='#94a3b8';" onmouseout="this.style.background='white'; this.style.borderColor='#cbd5e1';">
                    <svg width="18" height="18" viewBox="0 0 48 48" aria-hidden="true" style="justify-self: center;">
                        <path fill="#FFC107" d="M43.6 20.5H42V20H24v8h11.3C33.6 32.7 29.2 36 24 36c-6.6 0-12-5.4-12-12s5.4-12 12-12c3.1 0 5.9 1.2 8 3.1l5.7-5.7C34.1 6.1 29.3 4 24 4 13 4 4 13 4 24s9 20 20 20 20-9 20-20c0-1.3-.1-2.4-.4-3.5z"/>
                        <path fill="#FF3D00" d="M6.3 14.7l6.6 4.8C14.7 15.1 19 12 24 12c3.1 0 5.9 1.2 8 3.1l5.7-5.7C34.1 6.1 29.3 4 24 4 16.2 4 9.5 8.5 6.3 14.7z"/>
                        <path fill="#4CAF50" d="M24 44c5.1 0 9.8-2 13.3-5.2l-6.1-5.2C29.2 35.1 26.7 36 24 36c-5.2 0-9.6-3.3-11.3-7.9l-6.5 5C9.3 39.5 16.1 44 24 44z"/>
                        <path fill="#1976D2" d="M43.6 20.5H42V20H24v8h11.3c-.8 2.3-2.2 4.2-4.1 5.6l6.1 5.2C36.9 39.2 44 34 44 24c0-1.3-.1-2.4-.4-3.5z"/>
                    </svg>
                    <div>
                        <strong>Continue with Google</strong>
                        <span>Sign in quickly with your Google account.</span>
                    </div>
                </a>

                <p class="auth-entry-note">
                    Open the link in your email to continue. This keeps existing and new accounts on the same secure flow.
                </p>
            </div>
        </div>
    </section>
</div>
</x-guest-layout>
