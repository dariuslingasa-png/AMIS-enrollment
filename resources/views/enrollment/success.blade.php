<x-guest-layout>
    <div class="success-page">
        <div class="success-card">
            <div class="success-logo-section">
                <img src="{{ asset('images/AMIS_Logo.png') }}" alt="AMIS Logo" class="success-logo">
            </div>

            <h1>Enrollment Submitted!</h1>
            <p class="success-subtitle">
                Your enrollment application has been submitted successfully. Please wait for the school/admin review. You will be notified once your application has been checked.
            </p>

            <div class="success-info-box">
                <div class="success-info-row">
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="color: var(--primary); flex-shrink: 0;">
                        <circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/>
                    </svg>
                    <span>Your application is now <span class="badge-pending">Pending Review</span></span>
                </div>

            </div>

            <!-- Processing Time Notice -->
            <div class="success-notice">
                <div class="notice-icon">
                    <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/>
                    </svg>
                </div>
                <div class="notice-content">
                    <strong>Processing Time: 2-3 Business Days</strong>
                    <p>Please allow 2-3 business days for the admin team to review your application. You will be notified once your application has been processed.</p>
                </div>
            </div>

            <!-- Fast-Track Approval / Facebook Follow-Up Card -->
            <div style="background: linear-gradient(135deg, #eff6ff 0%, #dbeafe 100%); border: 1.5px solid #93c5fd; border-radius: 16px; padding: 1.25rem 1.35rem; margin: 1.25rem 0; box-shadow: 0 4px 14px rgba(37, 99, 235, 0.08); text-align: left;">
                <div style="display: flex; align-items: flex-start; gap: 0.85rem;">
                    <div style="width: 44px; height: 44px; border-radius: 12px; background: #1d4ed8; color: #ffffff; display: flex; align-items: center; justify-content: center; flex-shrink: 0; box-shadow: 0 4px 10px rgba(29, 78, 216, 0.25);">
                        <svg width="22" height="22" viewBox="0 0 24 24" fill="currentColor">
                            <path d="M24 12.073c0-6.627-5.373-12-12-12s-12 5.373-12 12c0 5.99 4.388 10.954 10.125 11.854v-8.385H7.078v-3.47h3.047V9.43c0-3.007 1.792-4.669 4.533-4.669 1.312 0 2.686.235 2.686.235v2.953H15.83c-1.491 0-1.956.925-1.956 1.874v2.25h3.328l-.532 3.47h-2.796v8.385C19.612 23.027 24 18.062 24 12.073z"/>
                        </svg>
                    </div>
                    <div style="flex: 1; min-width: 0;">
                        <div style="font-size: 0.75rem; font-weight: 800; color: #1d4ed8; text-transform: uppercase; letter-spacing: 0.05em;">Need Faster Approval?</div>
                        <h4 style="margin: 0.15rem 0 0 0; font-size: 1.05rem; font-weight: 800; color: #0f172a;">Direct Facebook Follow-Up</h4>
                        <p style="margin: 0.3rem 0 0.75rem 0; font-size: 0.85rem; color: #334155; line-height: 1.45; font-weight: 500;">
                            For faster verification and approval, message <strong>Sir Mohaymen Unos</strong> directly on Facebook with your Application ID:
                            @if(isset($applicant) && $applicant?->id)
                                <strong style="color: #059669; font-size: 0.95rem; background: #ecfdf5; padding: 2px 8px; border-radius: 6px; border: 1px solid #a7f3d0;">#{{ $applicant->id }}</strong>
                            @endif
                        </p>
                        <a href="https://web.facebook.com/sirmo.amis" target="_blank" rel="noopener" style="display: inline-flex; align-items: center; gap: 0.4rem; background: #1d4ed8; color: #ffffff; font-size: 0.85rem; font-weight: 700; padding: 0.6rem 1.1rem; border-radius: 10px; text-decoration: none; transition: background 0.15s ease;" onmouseover="this.style.background='#1e40af';" onmouseout="this.style.background='#1d4ed8';">
                            <span>Message Sir Mohaymen on Facebook</span>
                            <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
                                <line x1="7" y1="17" x2="17" y2="7"></line>
                                <polyline points="7 7 17 7 17 17"></polyline>
                            </svg>
                        </a>
                    </div>
                </div>
            </div>

            <div class="success-steps">
                <h3>What happens next?</h3>
                <ol>
                    <li>
                        <span class="step-dot">1</span>
                        <div>
                            <strong>Admin Review</strong>
                            <p>The school admin will review your application within 2-3 business days.</p>
                        </div>
                    </li>
                    <li>
                        <span class="step-dot">2</span>
                        <div>
                            <strong>Account Creation</strong>
                            <p>Once approved, your <strong>@amis.edu.ph</strong> student account will be created.</p>
                        </div>
                    </li>
                    <li>
                        <span class="step-dot">3</span>
                        <div>
                            <strong>Receive Credentials</strong>
                            <p>You'll receive your student number, email, and temporary password from the school.</p>
                        </div>
                    </li>
                    <li>
                        <span class="step-dot">4</span>
                        <div>
                            <strong>Login to Student Portal</strong>
                            <p>Use your credentials to access the AMIS Student Portal.</p>
                        </div>
                    </li>
                </ol>
            </div>

            <div style="display:flex;gap:0.75rem;justify-content:center;flex-wrap:wrap;">
                <a href="{{ route('enrollment.dashboard', ['applicant' => $applicant?->id]) }}" class="btn-secondary" style="text-decoration: none;display:inline-flex;align-items:center;gap:0.5rem;">
                    Back to Dashboard
                </a>
            </div>
        </div>
    </div>
</x-guest-layout>
