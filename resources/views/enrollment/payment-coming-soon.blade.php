<x-guest-layout>
<div class="enrollment-page">
    <div class="enrollment-header">
        <div class="enrollment-header-content">
            <div class="enrollment-header-left">
                <img src="{{ asset('images/AMIS_Logo.png') }}" alt="AMIS Logo" class="enrollment-header-logo">
                <div class="enrollment-header-text">
                    <div class="arabic">Al Munawwara Islamic School</div>
                    <div class="school">AMIS Enrollment</div>
                </div>
            </div>
            <div class="enrollment-header-right">
                <h1>Enrollment Fee Payment</h1>
                <div class="school-year">School Year 2026-2027</div>
            </div>
        </div>
    </div>

    <div class="enrollment-main">
        <div class="enrollment-form-container" style="max-width:760px;position:relative;">
            <a href="{{ route('enrollment.dashboard') }}"
               style="position:absolute;top:1.5rem;right:1.5rem;display:flex;align-items:center;justify-content:center;width:32px;height:32px;border-radius:50%;background:#f3f4f6;color:#6b7280;text-decoration:none;border:1px solid #e5e7eb;"
               aria-label="Back to dashboard">
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5">
                    <line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/>
                </svg>
            </a>

            <div style="text-align:center;padding:3rem 1.5rem 2.5rem;">
                <div style="width:72px;height:72px;margin:0 auto 1.25rem;border-radius:18px;background:#f0fdf4;border:1px solid #bbf7d0;display:flex;align-items:center;justify-content:center;color:#059669;">
                    <svg width="34" height="34" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8">
                        <rect x="2" y="5" width="20" height="14" rx="2"/>
                        <line x1="2" y1="10" x2="22" y2="10"/>
                    </svg>
                </div>

                <div style="font-size:0.75rem;font-weight:800;letter-spacing:0.12em;text-transform:uppercase;color:#059669;margin-bottom:0.75rem;">
                    Coming Soon
                </div>

                <h2 style="font-size:1.75rem;line-height:1.2;font-weight:800;color:#111827;margin:0 0 0.75rem;">
                    Payment proof upload is not yet available.
                </h2>

                <p style="font-size:0.95rem;line-height:1.7;color:#6b7280;max-width:540px;margin:0 auto 1.75rem;">
                    The Finance Office is still preparing the enrollment fee payment proof submission page. Please check your dashboard for updates.
                </p>

                <div style="display:flex;align-items:center;justify-content:center;gap:0.75rem;flex-wrap:wrap;">
                    <a href="{{ route('enrollment.dashboard') }}" class="btn btn-primary">
                        Back to Dashboard
                    </a>
                </div>
            </div>

            @if ($applicant)
                <div style="border-top:1px solid #e5e7eb;padding:1rem 1.25rem;background:#f9fafb;border-radius:0 0 12px 12px;">
                    <div style="font-size:0.75rem;font-weight:700;color:#9ca3af;text-transform:uppercase;letter-spacing:0.06em;margin-bottom:0.25rem;">Application</div>
                    <div style="font-size:0.9375rem;font-weight:700;color:#111827;">
                        {{ $applicant->last_name }}, {{ $applicant->first_name }} {{ $applicant->middle_name }}
                    </div>
                    <div style="font-size:0.8125rem;color:#6b7280;margin-top:0.2rem;">
                        {{ $applicant->grade_level }} · {{ strtoupper($applicant->student_type) }} Student · SY {{ $applicant->school_year }}
                    </div>
                </div>
            @endif
        </div>
    </div>
</div>
</x-guest-layout>
