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
