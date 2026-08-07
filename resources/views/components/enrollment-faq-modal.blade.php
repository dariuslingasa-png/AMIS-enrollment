<div x-data="{ 
    showFaqModal: false, 
    activeFaqIndex: 1,
    faqs: [
        {
            id: 1,
            title: 'How do I enroll online?',
            shortTitle: 'How do I enroll online?',
            tag: 'Process Overview'
        },
        {
            id: 2,
            title: 'What are the requirements for New, Transferee, and Old/Returning Students?',
            shortTitle: 'Enrollment requirements',
            tag: 'Document Checklist'
        },
        {
            id: 3,
            title: 'How do I prepare and submit my payment?',
            shortTitle: 'Prepare and submit payment',
            tag: 'Payment Guide'
        },
        {
            id: 4,
            title: 'What happens after I submit my enrollment?',
            shortTitle: 'After final submission',
            tag: 'Verification & Status'
        }
    ],
    openFaq(index) {
        this.activeFaqIndex = index;
        this.showFaqModal = true;
    }
}">
    <!-- Compact Desktop/Mobile FAQ Card Component -->
    <div class="faq-section-card" style="background:#ffffff; border:1px solid #e2e8f0; border-radius:18px; padding:1.35rem 1.5rem; box-shadow:0 4px 20px -2px rgba(0,0,0,0.04); margin-top:1.5rem;">
        <div style="display:flex; align-items:center; justify-content:space-between; margin-bottom:1rem; flex-wrap:wrap; gap:0.5rem;">
            <div style="display:flex; align-items:center; gap:0.65rem;">
                <div style="width:38px; height:38px; border-radius:12px; background:#ecfdf5; color:#059669; display:flex; align-items:center; justify-content:center; flex-shrink:0;">
                    <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
                        <circle cx="12" cy="12" r="10"/>
                        <path d="M9.09 9a3 3 0 0 1 5.83 1c0 2-3 3-3 3"/>
                        <line x1="12" y1="17" x2="12.01" y2="17"/>
                    </svg>
                </div>
                <div>
                    <h3 style="margin:0; font-size:1.15rem; font-weight:800; color:#0f172a; line-height:1.2;">Enrollment FAQs</h3>
                    <p style="margin:2px 0 0; font-size:0.8rem; color:#64748b; font-weight:500;">Quick answers to common online enrollment questions</p>
                </div>
            </div>
            <span style="font-size:0.75rem; font-weight:700; color:#059669; background:#ecfdf5; padding:0.25rem 0.65rem; border-radius:999px; border:1px solid #a7f3d0;">4 Topics</span>
        </div>

        <div class="faq-compact-grid" style="display:grid; grid-template-columns:1fr; gap:0.55rem;">
            <template x-for="faq in faqs" :key="faq.id">
                <button type="button" @click="openFaq(faq.id)" class="faq-item-button" style="width:100%; text-align:left; background:#f8fafc; border:1px solid #e2e8f0; border-radius:12px; padding:0.85rem 1rem; display:flex; align-items:center; justify-content:space-between; gap:0.75rem; cursor:pointer; transition:all 0.15s ease;" onmouseover="this.style.background='#f1f5f9'; this.style.borderColor='#cbd5e1';" onmouseout="this.style.background='#f8fafc'; this.style.borderColor='#e2e8f0';">
                    <div style="display:flex; align-items:flex-start; gap:0.75rem; flex:1; min-width:0;">
                        <span style="width:28px; height:28px; border-radius:8px; background:#059669; color:#ffffff; font-size:0.75rem; font-weight:800; display:flex; align-items:center; justify-content:center; flex-shrink:0; margin-top:1px;">?</span>
                        <span style="font-size:0.88rem; font-weight:700; color:#1e293b; line-height:1.35; flex:1; min-width:0;" x-text="faq.title"></span>
                    </div>
                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="#64748b" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" style="flex-shrink:0; margin-top:3px;">
                        <polyline points="9 18 15 12 9 6"/>
                    </svg>
                </button>
            </template>
        </div>
    </div>

    <!-- Single Reusable Clean White FAQ Modal Popup -->
    <div x-show="showFaqModal" x-cloak class="confirm-overlay" style="z-index: 999999; background: rgba(15, 23, 42, 0.6); backdrop-filter: blur(4px); position: fixed; inset: 0; display: flex; align-items: center; justify-content: center; padding: 1rem;" @keydown.escape.window="showFaqModal = false">
        <div class="confirm-dialog" style="max-width: 680px; width: 100%; max-height: 85vh; display: flex; flex-direction: column; padding: 0; border-radius: 20px; box-shadow: 0 20px 40px -8px rgba(0, 0, 0, 0.2); background: #ffffff; border: 1px solid #e2e8f0; overflow: hidden;" @click.outside="showFaqModal = false">
            
            <!-- Modal Header -->
            <div style="padding: 1.25rem 1.5rem 1rem; background: #ffffff; border-bottom: 1px solid #f1f5f9; display: flex; align-items: flex-start; justify-content: space-between; flex-shrink: 0;">
                <div>
                    <h3 style="margin: 0; font-size: 1.25rem; font-weight: 800; color: #0f172a;">Enrollment FAQs</h3>
                    <p style="margin: 2px 0 0; font-size: 0.82rem; color: #64748b; font-weight: 500;">AMIS Enrollment Help Guide</p>
                </div>
                <button type="button" @click="showFaqModal = false" aria-label="Close dialog" style="background: transparent; border: none; color: #94a3b8; padding: 4px; cursor: pointer; border-radius: 8px; transition: color 0.15s ease;" onmouseover="this.style.color='#0f172a';" onmouseout="this.style.color='#94a3b8';">
                    <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round">
                        <line x1="18" y1="6" x2="6" y2="18"/>
                        <line x1="6" y1="6" x2="18" y2="18"/>
                    </svg>
                </button>
            </div>

            <!-- Tab Switcher Navigation -->
            <div style="display: flex; background: #ffffff; border-bottom: 1px solid #e2e8f0; overflow-x: auto; flex-shrink: 0; scrollbar-width: none; padding: 0 1.25rem;">
                <template x-for="faq in faqs" :key="faq.id">
                    <button type="button" @click="activeFaqIndex = faq.id" 
                            :style="activeFaqIndex === faq.id ? 'color:#059669; font-weight:700; border-bottom:2px solid #059669;' : 'color:#64748b; font-weight:500; border-bottom:2px solid transparent;'" 
                            style="padding: 0.75rem 0.85rem; font-size: 0.85rem; background: transparent; border-top: none; border-left: none; border-right: none; cursor: pointer; white-space: nowrap; transition: all 0.15s ease; flex-shrink: 0;">
                        <span x-text="faq.shortTitle"></span>
                    </button>
                </template>
            </div>

            <!-- Modal Content Scrollable Body -->
            <div style="padding: 1.5rem; overflow-y: auto; flex-grow: 1; font-family: 'Inter', sans-serif;">

                <!-- QUESTION 1: HOW DO I ENROLL ONLINE? -->
                <div x-show="activeFaqIndex === 1">
                    <h4 style="margin: 0 0 1rem 0; font-size: 1.1rem; font-weight: 800; color: #0f172a;">How do I enroll online?</h4>

                    <ol style="margin: 0; padding-left: 1.25rem; font-size: 0.88rem; color: #334155; line-height: 1.8; font-weight: 400;">
                        <li>Sign in using your Google or Microsoft account.</li>
                        <li>Start a new enrollment application.</li>
                        <li>Complete the required student and parent information.</li>
                        <li>Upload the required documents.</li>
                        <li>Upload your proof of payment.</li>
                        <li>Review all information carefully.</li>
                        <li>Click Confirm and Final Submit.</li>
                        <li>Wait for AMIS verification.</li>
                    </ol>

                    <div style="background: #f0fdf4; border: 1px solid #dcfce7; border-radius: 10px; padding: 0.75rem 1rem; font-size: 0.82rem; color: #166534; font-weight: 500; margin-top: 1.25rem;">
                        Your application progress is automatically saved while completing the enrollment form.
                    </div>
                </div>

                <!-- QUESTION 2: REQUIREMENTS FOR NEW, TRANSFEREE, OLD/RETURNING STUDENTS -->
                <div x-show="activeFaqIndex === 2">
                    <h4 style="margin: 0 0 0.5rem 0; font-size: 1.1rem; font-weight: 800; color: #0f172a;">What are the requirements for New, Transferee, and Old/Returning Students?</h4>

                    <p style="font-size: 0.88rem; color: #64748b; font-weight: 400; margin: 0 0 1rem 0;">Enrollment requirements depend on the student's enrollment type:</p>

                    <div style="display: flex; flex-direction: column; gap: 0.85rem;">
                        <!-- NEW STUDENT -->
                        <div style="background: #f8fafc; border: 1px solid #e2e8f0; border-left: 3px solid #059669; border-radius: 10px; padding: 0.85rem 1rem;">
                            <div style="display: flex; align-items: center; gap: 0.5rem; margin-bottom: 0.4rem;">
                                <span style="background: #059669; color: white; font-size: 0.68rem; font-weight: 700; padding: 0.1rem 0.45rem; border-radius: 4px; text-transform: uppercase;">NEW STUDENT</span>
                                <strong style="font-size: 0.85rem; color: #0f172a;">Nursery, Kindergarten, Grade 1 to 12</strong>
                            </div>
                            <ul style="margin: 0; padding-left: 1.15rem; font-size: 0.85rem; color: #334155; line-height: 1.6;">
                                <li>2×2 Recent Student Photo (1:1 ratio, plain white background).</li>
                                <li>Photocopy of Birth Certificate (PSA or readable copy).</li>
                                <li>Report Card / Official Transcript (For Grade 1–12; affidavit allowed if processing).</li>
                            </ul>
                        </div>

                        <!-- TRANSFEREE STUDENT -->
                        <div style="background: #f8fafc; border: 1px solid #e2e8f0; border-left: 3px solid #0284c7; border-radius: 10px; padding: 0.85rem 1rem;">
                            <div style="display: flex; align-items: center; gap: 0.5rem; margin-bottom: 0.4rem;">
                                <span style="background: #0284c7; color: white; font-size: 0.68rem; font-weight: 700; padding: 0.1rem 0.45rem; border-radius: 4px; text-transform: uppercase;">TRANSFEREE STUDENT</span>
                                <strong style="font-size: 0.85rem; color: #0f172a;">Transferring from another school</strong>
                            </div>
                            <ul style="margin: 0; padding-left: 1.15rem; font-size: 0.85rem; color: #334155; line-height: 1.6;">
                                <li>2×2 Recent Student Photo (1:1 ratio, plain white background).</li>
                                <li>Photocopy of Birth Certificate (PSA or readable copy).</li>
                                <li>Official Report Card / Transcript of Records (Form 138 / SF9).</li>
                                <li>Good Moral Certificate (For High School applicants).</li>
                            </ul>
                        </div>

                        <!-- OLD / RETURNING STUDENT -->
                        <div style="background: #f8fafc; border: 1px solid #e2e8f0; border-left: 3px solid #ea580c; border-radius: 10px; padding: 0.85rem 1rem;">
                            <div style="display: flex; align-items: center; gap: 0.5rem; margin-bottom: 0.4rem;">
                                <span style="background: #ea580c; color: white; font-size: 0.68rem; font-weight: 700; padding: 0.1rem 0.45rem; border-radius: 4px; text-transform: uppercase;">OLD / RETURNING STUDENT</span>
                                <strong style="font-size: 0.85rem; color: #0f172a;">Re-enrolling student</strong>
                            </div>
                            <ul style="margin: 0; padding-left: 1.15rem; font-size: 0.85rem; color: #334155; line-height: 1.6;">
                                <li>2×2 Recent Student Photo (Updated picture for SY 2026-2027).</li>
                                <li>Current Grade Level & Shift Selection.</li>
                                <li><em>Note: Birth Certificate & Transcript are already on file in school records.</em></li>
                            </ul>
                        </div>
                    </div>
                </div>

                <!-- QUESTION 3: HOW DO I PREPARE AND SUBMIT MY PAYMENT? -->
                <div x-show="activeFaqIndex === 3">
                    <h4 style="margin: 0 0 0.5rem 0; font-size: 1.1rem; font-weight: 800; color: #0f172a;">How do I prepare and submit my payment?</h4>

                    <p style="font-size: 0.88rem; color: #64748b; font-weight: 400; margin: 0 0 0.85rem 0;">
                        Prepare the required enrollment payment using one of the payment methods currently accepted by AMIS:
                    </p>

                    <div style="background: #f8fafc; border: 1px solid #e2e8f0; border-radius: 10px; padding: 0.85rem 1rem; margin-bottom: 1rem;">
                        <strong style="font-size: 0.8rem; color: #0f172a; text-transform: uppercase; tracking-wider: 0.05em; display: block; margin-bottom: 0.4rem;">Accepted Payment Channels:</strong>
                        <ul style="margin: 0; padding-left: 1.15rem; font-size: 0.85rem; color: #334155; line-height: 1.6;">
                            <li><strong>BDO Bank Deposit / Transfer:</strong> AL MUNAWWARA ISLAMIC SCHOOL Inc. / CABEL B. NURHASAN</li>
                            <li><strong>GCash / Maya Payment Center:</strong> (+63) 927 299 1833 / (+63) 995 233 9423 (CABEL B. NURHASAN)</li>
                            <li><strong>School Cashier:</strong> On-site cashier payment at AMIS Campus.</li>
                        </ul>
                    </div>

                    <strong style="font-size: 0.88rem; color: #0f172a; display: block; margin-top: 0.85rem;">Payment Submission Steps:</strong>
                    <ol style="margin: 0.4rem 0 0 0; padding-left: 1.25rem; font-size: 0.88rem; color: #334155; line-height: 1.7; font-weight: 400;">
                        <li>Keep your official receipt or transaction confirmation.</li>
                        <li>Take a clear photo or screenshot of the payment proof.</li>
                        <li>Upload the payment proof in the enrollment form.</li>
                        <li>Verify that the payment information and receipt are readable.</li>
                        <li>Review everything before Final Submit.</li>
                    </ol>
                </div>

                <!-- QUESTION 4: WHAT HAPPENS AFTER I SUBMIT MY ENROLLMENT? -->
                <div x-show="activeFaqIndex === 4">
                    <h4 style="margin: 0 0 0.5rem 0; font-size: 1.1rem; font-weight: 800; color: #0f172a;">What happens after I submit my enrollment?</h4>

                    <p style="font-size: 0.88rem; color: #64748b; font-weight: 400; margin: 0 0 0.85rem 0;">
                        After Final Submit, your application is submitted to AMIS for verification.
                    </p>

                    <div style="background: #f8fafc; border: 1px solid #e2e8f0; border-radius: 10px; padding: 0.85rem 1rem; margin-bottom: 0.85rem;">
                        <strong style="font-size: 0.85rem; color: #0f172a; display: block; margin-bottom: 0.35rem;">Verification Review:</strong>
                        <p style="margin: 0; font-size: 0.85rem; color: #334155; line-height: 1.6;">
                            AMIS will verify your enrollment information, submitted documents, and payment proof.
                        </p>
                        <div style="margin-top: 0.5rem; font-size: 0.82rem; color: #059669; font-weight: 600;">
                            Estimated processing time: 1–2 banking/business days.
                        </div>
                    </div>

                    <ul style="margin: 0.5rem 0 0 0; padding-left: 1.25rem; font-size: 0.88rem; color: #334155; line-height: 1.7; font-weight: 400;">
                        <li>During this period, no additional application is required.</li>
                        <li>You can sign in again using the same Google or Microsoft account to check your status.</li>
                        <li>Do not create another duplicate application while your existing application is under review.</li>
                    </ul>
                </div>

            </div>

            <!-- Modal Footer Actions -->
            <div style="padding: 0.85rem 1.5rem; background: #ffffff; border-top: 1px solid #f1f5f9; display: flex; align-items: center; justify-content: flex-end; flex-shrink: 0;">
                <button type="button" class="btn-primary" style="padding: 0.5rem 1.35rem; font-size: 0.85rem; font-weight: 700; border-radius: 10px; background: #059669; color: #ffffff; border: none; cursor: pointer;" @click="showFaqModal = false">
                    Close
                </button>
            </div>

        </div>
    </div>
</div>
