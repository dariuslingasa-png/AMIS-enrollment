<x-guest-layout>
    <div class="enrollment-closed-page">
        <div class="enrollment-closed-card">
            <div class="closed-logo-section">
                <img src="{{ asset('images/AMIS_Logo.png') }}" alt="AMIS Logo" class="closed-logo">
            </div>

            <div class="closed-icon">
                <svg width="64" height="64" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                </svg>
            </div>

            <h1>Enrollment Currently Closed</h1>
            <p class="closed-subtitle">
                Thank you for your interest in <strong>Al Munawwara Islamic School</strong>!
            </p>

            <div class="closed-message">
                <p>Online enrollment is temporarily closed. We appreciate your patience and look forward to welcoming you in the next enrollment period.</p>
            </div>

            <div class="closed-contact">
                <h3>Have Questions?</h3>
                <p>Feel free to reach out to us:</p>
                <div class="contact-items">
                    <div class="contact-item">
                        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <path d="M4 4h16c1.1 0 2 .9 2 2v12c0 1.1-.9 2-2 2H4c-1.1 0-2-.9-2-2V6c0-1.1.9-2 2-2z"/>
                            <polyline points="22,6 12,13 2,6"/>
                        </svg>
                        <span>admission@almunawwara.edu.ph</span>
                    </div>
                    <div class="contact-item">
                        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <path d="M22 16.92v3a2 2 0 01-2.18 2 19.79 19.79 0 01-8.63-3.07 19.5 19.5 0 01-6-6 19.79 19.79 0 01-3.07-8.67A2 2 0 014.11 2h3a2 2 0 012 1.72 12.84 12.84 0 00.7 2.81 2 2 0 01-.45 2.11L8.09 9.91a16 16 0 006 6l1.27-1.27a2 2 0 012.11-.45 12.84 12.84 0 002.81.7A2 2 0 0122 16.92z"/>
                        </svg>
                        <span>(02) 1234-5678</span>
                    </div>
                </div>
            </div>

            <a href="{{ url('/') }}" class="btn-primary" style="text-decoration: none;">
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <line x1="19" y1="12" x2="5" y2="12"/><polyline points="12 19 5 12 12 5"/>
                </svg>
                Back to Home
            </a>
        </div>
    </div>
</x-guest-layout>
