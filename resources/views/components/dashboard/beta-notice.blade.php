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
                    <span class="beta-notice-badge" style="background: #e6fdf5; border-color: #a7f3d0; color: #047857;">Teacher feedback applied</span>
                    <h2 id="beta-notice-title">Enrollment Readiness Guide</h2>
                    <p>SY 2026-2027</p>
                </div>
            </header>

            <div id="beta-notice-description" class="beta-notice-body">
                <p class="beta-notice-greeting">Assalamualaikum,</p>
                
                <div class="beta-notice-section">
                    <h3>Required before submission</h3>
                    <ul>
                        <li>Student profile setup & religion verification</li>
                        <li>Parent or guardian contact details</li>
                        <li>Emergency contact instructions</li>
                        <li>Recent 1:1 picture or annual photo</li>
                        <li>Report card or signed temporary affidavit proof</li>
                    </ul>
                </div>

                <div class="beta-notice-section">
                    <h3>Optional but helpful</h3>
                    <ul>
                        <li>Birth certificate copy (if available)</li>
                        <li>Medical record or health history</li>
                        <li>Marriage contract (if applicable)</li>
                        <li>Physician details (if available)</li>
                    </ul>
                </div>

                <div class="beta-notice-section">
                    <h3>Important Guidelines</h3>
                    <p style="margin-bottom: 0.5rem; line-height: 1.55;">
                        <strong>Document not available?</strong> Upload an affidavit or temporary proof for review, then prepare the original document when admissions requests it.
                    </p>
                    <p style="line-height: 1.55;">
                        <strong>Multiple students?</strong> Use one parent email for coordination. For another child under the same parent, contact admissions to group the records correctly.
                    </p>
                </div>
            </div>

            <footer class="beta-notice-footer">
                <a href="mailto:amisonlinesupport@gmail.com" class="beta-notice-secondary">Contact Support</a>
                <button type="button" class="beta-notice-primary" @click="closeNotice()">Got it</button>
            </footer>
        </section>
    </x-modal>
</div>
