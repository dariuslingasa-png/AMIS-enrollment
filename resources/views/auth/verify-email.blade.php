<x-guest-layout>
    <div class="auth-page">
        <div class="auth-card" style="max-width: 400px;">
            <!-- Back link -->
            <a href="{{ route('login') }}" style="display: inline-flex; align-items: center; gap: 6px; color: #6b7280; font-size: 14px; text-decoration: none; margin-bottom: 20px;">
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <line x1="19" y1="12" x2="5" y2="12"/><polyline points="12 19 5 12 12 5"/>
                </svg>
                Back to Login
            </a>

            <!-- Icon -->
            <div class="verify-icon">
                <svg width="28" height="28" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <path d="M4 4h16c1.1 0 2 .9 2 2v12c0 1.1-.9 2-2 2H4c-1.1 0-2-.9-2-2V6c0-1.1.9-2 2-2z"/>
                    <polyline points="22,6 12,13 2,6"/>
                </svg>
            </div>

            <div class="auth-logo" style="margin-bottom: 16px;">
                <h1>Verify Your Email</h1>
                <p>Enter the 4-digit code sent to your email</p>
            </div>

            @if (session('success'))
                <div style="background: #f0fdf4; border: 1px solid #bbf7d0; color: #065f46; padding: 12px 16px; border-radius: 10px; font-size: 14px; margin-bottom: 16px;">
                    {{ session('success') }}
                </div>
            @endif

            @if ($errors->any())
                <div class="auth-error" style="margin-bottom: 16px;">{{ $errors->first() }}</div>
            @endif

            <!-- Verify Code Form -->
            <form method="POST" action="{{ route('verify.code') }}" class="auth-form" x-data="verifyForm()">
                @csrf

                <input type="hidden" name="email" value="{{ session('email', old('email')) }}">

                <!-- Email display -->
                <div style="text-align: center; margin-bottom: 8px;">
                    <span style="font-size: 13px; color: #6b7280;">Code sent to:</span>
                    <strong style="font-size: 14px; color: #111827; display: block;">{{ session('email', old('email', 'your email')) }}</strong>
                </div>

                <!-- Code inputs -->
                <div class="code-inputs">
                    <template x-for="(digit, index) in digits" :key="index">
                        <input
                            type="text"
                            maxlength="1"
                            class="code-input"
                            :x-ref="'digit' + index"
                            x-model="digits[index]"
                            @input="handleInput(index, $event)"
                            @keydown.backspace="handleBackspace(index, $event)"
                            @paste.prevent="handlePaste($event)"
                            inputmode="numeric"
                        >
                    </template>
                </div>

                <input type="hidden" name="code" :value="digits.join('')">

                <button type="submit" class="auth-button" :disabled="digits.join('').length < 4">
                    Verify Email
                </button>
            </form>

            <!-- Resend Code -->
            <div class="resend-section">
                <p>Didn't receive the code?</p>
                <form method="POST" action="{{ route('send.verification') }}" style="display: inline;">
                    @csrf
                    <input type="hidden" name="email" value="{{ session('email', old('email')) }}">
                    <button type="submit" class="resend-link">Resend Code</button>
                </form>
            </div>
        </div>
    </div>

    @push('scripts')
    <script>
    function verifyForm() {
        return {
            digits: ['', '', '', ''],
            handleInput(index, event) {
                const value = event.target.value.replace(/\D/g, '');
                this.digits[index] = value;
                if (value && index < 3) {
                    this.$nextTick(() => {
                        const next = this.$root.querySelectorAll('.code-input')[index + 1];
                        if (next) next.focus();
                    });
                }
            },
            handleBackspace(index, event) {
                if (!this.digits[index] && index > 0) {
                    const prev = this.$root.querySelectorAll('.code-input')[index - 1];
                    if (prev) prev.focus();
                }
            },
            handlePaste(event) {
                const paste = event.clipboardData.getData('text').replace(/\D/g, '').slice(0, 4);
                for (let i = 0; i < 4; i++) {
                    this.digits[i] = paste[i] || '';
                }
            }
        }
    }
    </script>
    @endpush
</x-guest-layout>
