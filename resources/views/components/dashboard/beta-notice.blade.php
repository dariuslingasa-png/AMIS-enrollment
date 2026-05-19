@props(['show' => (bool) session('show_beta_notice')])

<div
    x-data="{ closeNotice() { window.dispatchEvent(new CustomEvent('close-modal', { detail: 'beta-notice' })) } }"
    x-init="@js($show) && $nextTick(() => window.dispatchEvent(new CustomEvent('open-modal', { detail: 'beta-notice' })))"
>
    <x-modal name="beta-notice" maxWidth="lg" focusable aria-labelledby="beta-notice-title" aria-describedby="beta-notice-description">
        <section class="beta-notice-card" aria-labelledby="beta-notice-title">
            <button type="button" class="beta-notice-close" @click="closeNotice()" aria-label="Close important notice">
                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg>
            </button>

            <header class="beta-notice-header">
                <div class="beta-notice-icon" aria-hidden="true">
                    <svg width="23" height="23" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2"><path d="M3 11v2a2 2 0 002 2h2l4 4v-7l8 3V7l-8 3V3L7 7H5a2 2 0 00-2 2v2z"/><path d="M21 9v6"/></svg>
                </div>
                <div>
                    <span class="beta-notice-badge">Beta Phase</span>
                    <h2 id="beta-notice-title">Important Notice</h2>
                    <p>Beta Phase Enrollment System</p>
                </div>
            </header>

            <div id="beta-notice-description" class="beta-notice-body">
                <p class="beta-notice-greeting">Assalamualaikum,</p>
                <div class="beta-notice-section">
                    <h3>What this means</h3>
                    <ul>
                        <li>The AMIS Online Enrollment System is currently in Beta Phase.</li>
                        <li>Some features may still be improving, updated, or temporarily limited.</li>
                        <li>Minor issues may occur while we continue system testing and enhancement.</li>
                        <li>We appreciate your patience as we finalize a smoother enrollment experience.</li>
                    </ul>
                </div>
                <div class="beta-notice-section">
                    <h3>Need help?</h3>
                    <p>If you encounter issues, errors, or concerns, please contact the school office or support team for assistance.</p>
                </div>
            </div>

            <footer class="beta-notice-footer">
                <a href="mailto:amisonlinesupport@gmail.com" class="beta-notice-secondary">Contact Support</a>
                <button type="button" class="beta-notice-primary" @click="closeNotice()">Got it</button>
            </footer>
        </section>
    </x-modal>
</div>
