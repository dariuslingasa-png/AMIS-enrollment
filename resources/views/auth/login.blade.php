<x-guest-layout>
<style>
@keyframes slideInLeft {
    from { opacity:0; transform:translateX(-20px); }
    to   { opacity:1; transform:translateX(0); }
}
@keyframes slideFromLeft {
    from { opacity:0; transform:translateX(-40px); }
    to   { opacity:1; transform:translateX(0); }
}
@keyframes slideToRight {
    from { opacity:1; transform:translateX(0); }
    to   { opacity:0; transform:translateX(40px); }
}
.slide-enter { animation: slideFromLeft 0.45s ease-out forwards; }
.slide-leave { animation: slideToRight 0.3s ease-in forwards; }

/* Mobile: stack vertically, hide info panel */
@media (max-width: 768px) {
    .login-grid { grid-template-columns: 1fr !important; }
    .login-info  { display: none !important; }
    .login-form  { padding: 2rem 1.25rem !important; min-height: 100vh; }
    .mobile-logo { display: block !important; margin: 0 auto 0.875rem !important; }
}
</style>

<div style="min-height:100vh;display:grid;grid-template-columns:1fr 1fr;" class="login-grid">

    {{-- ── LEFT: Sliding Info Panel ── --}}
    <div style="background:linear-gradient(160deg,#059669 0%,#065f46 100%);display:flex;flex-direction:column;justify-content:center;padding:4rem;color:white;overflow:hidden;" class="login-info"
         x-data="{ slide: 0 }" x-init="setInterval(() => slide = slide === 0 ? 1 : 0, 6000)">

        {{-- Logo --}}
        <div style="display:flex;align-items:center;gap:1.25rem;margin-bottom:2.5rem;animation:slideInLeft 0.5s ease-out both;">
            <img src="{{ asset('images/AMIS_Logo.png') }}" alt="AMIS" style="width:80px;height:80px;object-fit:contain;flex-shrink:0;">
            <div style="text-align:center;">
                <div style="font-size:1.125rem;font-weight:600;opacity:0.9;margin-bottom:0.25rem;">المدرسة المنورة الإسلامية</div>
                <div style="font-size:1.125rem;font-weight:800;line-height:1.2;">Al Munawwara Islamic School</div>
            </div>
        </div>

        {{-- Headline --}}
        <div style="margin-bottom:2rem;animation:slideInLeft 0.5s 0.1s ease-out both;">
            <div style="display:inline-flex;align-items:center;gap:0.5rem;background:rgba(255,255,255,0.15);border:1px solid rgba(255,255,255,0.25);border-radius:999px;padding:0.3rem 1rem;font-size:0.8125rem;font-weight:600;margin-bottom:1rem;">
                <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M22 10v6M2 10l10-5 10 5-10 5z"/></svg>
                Now Enrolling — SY 2026–2027
            </div>
            <h1 style="font-size:2.5rem;font-weight:900;line-height:1.1;margin:0 0 0.75rem;white-space:nowrap;">
                Enrollment is Now Open!
            </h1>
            <p style="font-size:0.9375rem;opacity:0.85;line-height:1.7;margin:0;">
                Complete your enrollment online in a few easy steps.
            </p>
        </div>

        {{-- Slide indicators with transition --}}
        <div style="display:flex;gap:0.5rem;margin-bottom:1.75rem;align-items:center;">
            <div @click="slide=0"
                 :style="slide===0 ? 'width:28px;background:white;box-shadow:0 0 8px rgba(255,255,255,0.5);' : 'width:10px;background:rgba(255,255,255,0.35);'"
                 style="height:10px;border-radius:999px;cursor:pointer;transition:all 0.4s cubic-bezier(0.4,0,0.2,1);"></div>
            <div @click="slide=1"
                 :style="slide===1 ? 'width:28px;background:white;box-shadow:0 0 8px rgba(255,255,255,0.5);' : 'width:10px;background:rgba(255,255,255,0.35);'"
                 style="height:10px;border-radius:999px;cursor:pointer;transition:all 0.4s cubic-bezier(0.4,0,0.2,1);"></div>
        </div>

        {{-- Slides container --}}
        <div style="position:relative;min-height:380px;overflow:hidden;">

        {{-- Slide 1: How to Enroll --}}
        <div x-show="slide === 0"
             x-transition:enter="slide-enter"
             x-transition:leave="slide-leave"
             style="position:absolute;top:0;left:0;right:0;">
            <div style="font-size:0.75rem;font-weight:700;letter-spacing:0.12em;opacity:0.6;text-transform:uppercase;margin-bottom:1.25rem;">How to Enroll</div>
            @php
                $steps = [
                    ['Create an Account',    'Register with your email and verify it via OTP.'],
                    ['Fill Enrollment Form', 'Complete all 5 steps: student, parent, medical, agreement, documents.'],
                    ['Pay Enrollment Fee',   'Submit ₱4,000 non-refundable fee via GCash, Maya, or BDO.'],
                    ['Admin Review',         'Your documents will be reviewed within 2–3 business days.'],
                    ['Receive Credentials',  'Get your student number and school email once approved.'],
                ];
            @endphp
            <div style="display:flex;flex-direction:column;gap:1.125rem;">
                @foreach ($steps as $i => $s)
                    <div style="display:flex;align-items:flex-start;gap:1rem;">
                        <div style="width:30px;height:30px;border-radius:50%;background:rgba(255,255,255,0.2);border:1.5px solid rgba(255,255,255,0.4);display:flex;align-items:center;justify-content:center;font-size:0.875rem;font-weight:800;flex-shrink:0;margin-top:1px;">
                            {{ $i + 1 }}
                        </div>
                        <div>
                            <div style="font-size:1rem;font-weight:700;margin-bottom:0.2rem;">{{ $s[0] }}</div>
                            <div style="font-size:0.9rem;opacity:0.75;line-height:1.5;">{{ $s[1] }}</div>
                        </div>
                    </div>
                @endforeach
            </div>
        </div>

        {{-- Slide 2: Requirements --}}
        <div x-show="slide === 1"
             x-transition:enter="slide-enter"
             x-transition:leave="slide-leave"
             style="position:absolute;top:0;left:0;right:0;">
            <div style="font-size:0.75rem;font-weight:700;letter-spacing:0.12em;opacity:0.6;text-transform:uppercase;margin-bottom:1.25rem;">Requirements to Bring</div>
            <div style="display:flex;flex-direction:column;gap:1rem;">
                @php
                    $reqs = [
                        ['2x2 ID Picture (white background)', true, 'Required for your student profile.'],
                        ['Birth Certificate (PSA)', true, 'Official PSA-issued copy required.'],
                        ['Report Card / Transcript', true, 'From your previous school.'],
                        ['Marriage Contract of Parents', false, 'Optional — for family records.'],
                        ['Medical Record', false, 'Optional — if applicable.'],
                    ];
                @endphp
                @foreach ($reqs as [$req, $required, $desc])
                    <div style="display:flex;align-items:flex-start;gap:1rem;">
                        <div style="width:30px;height:30px;border-radius:50%;display:flex;align-items:center;justify-content:center;flex-shrink:0;margin-top:1px;
                            {{ $required ? 'background:rgba(255,255,255,0.2);border:1.5px solid rgba(255,255,255,0.4);' : 'background:rgba(255,255,255,0.08);border:1.5px dashed rgba(255,255,255,0.25);' }}">
                            @if ($required)
                                <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="white" stroke-width="3"><polyline points="20 6 9 17 4 12"/></svg>
                            @else
                                <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="rgba(255,255,255,0.5)" stroke-width="2"><line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/></svg>
                            @endif
                        </div>
                        <div>
                            <div style="font-size:1rem;font-weight:700;margin-bottom:0.2rem;{{ !$required ? 'opacity:0.65;' : '' }}">
                                {{ $req }}
                                @if (!$required) <span style="font-size:0.8125rem;font-weight:400;opacity:0.7;">(optional)</span> @endif
                            </div>
                            <div style="font-size:0.9rem;opacity:0.65;line-height:1.5;">{{ $desc }}</div>
                        </div>
                    </div>
                @endforeach
            </div>
        </div>

        </div>{{-- end slides container --}}

    </div>

    {{-- ── RIGHT: Login Form ── --}}
    <div style="background:#f3f4f6;display:flex;align-items:center;justify-content:center;padding:3rem 2rem;" class="login-form">
        <div style="width:100%;max-width:400px;">

            <div style="text-align:center;margin-bottom:2rem;">
                {{-- Logo shown only on mobile (info panel hidden) --}}
                <img src="{{ asset('images/AMIS_Logo.png') }}" alt="AMIS" class="mobile-logo" style="width:72px;height:72px;object-fit:contain;margin:0 auto 0.875rem;display:none;">
                <h2 style="font-size:1.5rem;font-weight:800;color:#111827;margin:0 0 0.375rem;">Welcome Back</h2>
                <p style="font-size:0.875rem;color:#6b7280;margin:0;">Sign in to your enrollment account</p>
            </div>

            <form method="POST" action="{{ route('login.store') }}" class="auth-form">
                @csrf

                @if (session('success'))
                    <div style="background:#f0fdf4;border:1px solid #bbf7d0;color:#065f46;padding:12px 16px;border-radius:10px;font-size:14px;margin-bottom:1.25rem;">
                        {{ session('success') }}
                    </div>
                @endif

                @if ($errors->any())
                    <div class="auth-error">{{ $errors->first() }}</div>
                @endif

                <div class="form-group">
                    <label for="email">Email Address</label>
                    <div class="input-with-icon">
                        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <path d="M4 4h16c1.1 0 2 .9 2 2v12c0 1.1-.9 2-2 2H4c-1.1 0-2-.9-2-2V6c0-1.1.9-2 2-2z"/>
                            <polyline points="22,6 12,13 2,6"/>
                        </svg>
                        <input type="email" id="email" name="email" placeholder="your@email.com" value="{{ old('email') }}" required autofocus>
                    </div>
                </div>

                <div class="form-group" x-data="{ show: false }">
                    <label for="password">Password</label>
                    <div style="position:relative;">
                        <div class="input-with-icon">
                            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <rect x="3" y="11" width="18" height="11" rx="2" ry="2"/>
                                <path d="M7 11V7a5 5 0 0110 0v4"/>
                            </svg>
                            <input :type="show ? 'text' : 'password'" id="password" name="password" placeholder="Password" required style="padding-right:44px;">
                        </div>
                        <button type="button" @click="show = !show" style="position:absolute;right:14px;top:50%;transform:translateY(-50%);background:none;border:none;cursor:pointer;color:#9ca3af;padding:2px;display:flex;align-items:center;z-index:2;">
                            <svg x-show="!show" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/><circle cx="12" cy="12" r="3"/>
                            </svg>
                            <svg x-show="show" x-cloak width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <path d="M17.94 17.94A10.07 10.07 0 0112 20c-7 0-11-8-11-8a18.45 18.45 0 015.06-5.94M9.9 4.24A9.12 9.12 0 0112 4c7 0 11 8 11 8a18.5 18.5 0 01-2.16 3.19m-6.72-1.07a3 3 0 11-4.24-4.24"/>
                                <line x1="1" y1="1" x2="23" y2="23"/>
                            </svg>
                        </button>
                    </div>
                </div>

                <button type="submit" class="auth-button" style="margin-top:0.5rem;">Sign In</button>
            </form>

            <div class="auth-footer" style="margin-top:1.5rem;">
                Don't have an account? <a href="{{ route('register') }}">Sign Up</a>
            </div>
        </div>
    </div>

</div>
</x-guest-layout>
