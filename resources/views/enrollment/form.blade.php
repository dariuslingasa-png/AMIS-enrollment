<x-guest-layout>
<div x-data="enrollmentForm()" @open-affidavit-builder.window="openAffidavitBuilder()" class="enrollment-page">
    <!-- Dynamic Floating Toast Notifications Stack -->
    <div class="toast-stack" style="position: fixed; top: 1.25rem; right: 1.25rem; z-index: 100000; display: flex; flex-direction: column; gap: 0.75rem; width: min(380px, calc(100vw - 2rem)); pointer-events: none;">
        <template x-for="t in toasts" :key="t.id">
            <div
                class="toast"
                :class="'toast-' + (t.type || 'error')"
                x-transition:enter="toast-enter"
                x-transition:leave="toast-leave"
                style="pointer-events: auto; background: white; border-radius: 12px; padding: 0.85rem 1rem; box-shadow: 0 20px 45px rgba(15, 23, 42, 0.18); border-left: 4px solid #ef4444; display: flex; align-items: flex-start; gap: 0.75rem;"
                :style="t.type === 'success' ? 'border-left-color: #10b981;' : (t.type === 'warning' ? 'border-left-color: #f59e0b;' : 'border-left-color: #ef4444;')"
                role="status"
            >
                <span class="toast-icon" style="display: flex; align-items: center; justify-content: center; width: 26px; height: 26px; border-radius: 50%; flex-shrink: 0; margin-top: 1px;"
                      :style="t.type === 'success' ? 'background: #dcfce7; color: #059669;' : (t.type === 'warning' ? 'background: #fef3c7; color: #d97706;' : 'background: #fee2e2; color: #dc2626;')"
                >
                    <svg x-show="t.type === 'success'" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><path d="M20 6 9 17l-5-5"/></svg>
                    <svg x-show="t.type === 'warning'" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><path d="M10.29 3.86 1.82 18a2 2 0 0 0 1.71 3h16.94a2 2 0 0 0 1.71-3L13.71 3.86a2 2 0 0 0-3.42 0z"/><path d="M12 9v4m0 4h.01"/></svg>
                    <svg x-show="!t.type || t.type === 'error'" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/><path d="m15 9-6 6m0-6 6 6"/></svg>
                </span>
                <div style="flex: 1; min-width: 0;">
                    <div style="font-weight: 700; font-size: 0.85rem; color: #0f172a; margin-bottom: 0.15rem;" x-text="t.type === 'success' ? 'Success' : (t.type === 'warning' ? 'Notice' : 'Required Field Missing')"></div>
                    <span class="toast-message" style="font-size: 0.82rem; color: #334155; line-height: 1.4; display: block;" x-text="t.message"></span>
                </div>
                <button type="button" class="toast-close" @click="removeToast(t.id)" aria-label="Dismiss notification" style="background: transparent; border: none; color: #94a3b8; cursor: pointer; padding: 2px; border-radius: 4px; line-height: 1;">
                    <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round">
                        <path d="M18 6 6 18M6 6l12 12"/>
                    </svg>
                </button>
            </div>
        </template>
    </div>
    <style>
        /* Visually uppercase all text input fields and textareas on the enrollment form */
        .enrollment-page input[type="text"],
        .enrollment-page input[type="email"],
        .enrollment-page textarea {
            text-transform: uppercase !important;
        }

        /* Prevent placeholders from being converted to uppercase */
        .enrollment-page input[type="text"]::placeholder,
        .enrollment-page input[type="email"]::placeholder,
        .enrollment-page textarea::placeholder {
            text-transform: none !important;
        }
    </style>
    <!-- Full Page Loading Skeleton -->
    <div x-show="initialLoading" x-cloak>
        <x-skeleton-enrollment />
    </div>

    <!-- Actual Enrollment Form -->
    <div x-show="!initialLoading" x-cloak x-transition:enter="transition ease-out duration-300" x-transition:enter-start="opacity-0" x-transition:enter-end="opacity-100">
        <x-enrollment.brand-header />

        <!-- Step Progress Bar -->
        <div class="enrollment-steps-bar">
            <div class="enrollment-progress-summary">
                <span x-text="`Step ${step} of ${steps.length}`"></span>
                <strong x-text="currentStepLabel()"></strong>
            </div>
            <div class="enrollment-progress-track" aria-hidden="true">
                <span :style="`width: ${steps.length > 1 ? ((step - 1) / (steps.length - 1)) * 100 : 100}%`"></span>
            </div>
            <div class="enrollment-steps-container">
                <template x-for="s in steps" :key="s.num">
                    <div
                        :class="{
                            'enrollment-step-item': true,
                            'active': step === s.num,
                            'done': isStepComplete(s.num),
                            'warning': isStepWarning(s.num)
                        }"
                        role="button"
                        tabindex="0"
                        :aria-current="step === s.num ? 'step' : null"
                        :aria-label="`Go to step ${s.num}: ${s.label}`"
                        @click="goToStep(s.num)"
                        @keydown.enter.prevent="goToStep(s.num)"
                        @keydown.space.prevent="goToStep(s.num)"
                    >
                        <div class="enrollment-step-circle" aria-hidden="true">
                            <span x-text="s.num"></span>
                        </div>
                        <span class="enrollment-step-label" x-text="s.label"></span>
                    </div>
                </template>
            </div>
        </div>

        <!-- Main Content -->
        <div class="enrollment-main">
            <div class="enrollment-form-container">

            <x-enrollment.form-heading />

            <x-enrollment.autosave-status />

            @if ($applicant && $applicant->status === 'rejected')
                <div x-show="currentFixMessage()" x-cloak class="enrollment-error" style="align-items:flex-start;background:#fff1f2;border-color:#fecdd3;color:#991b1b;">
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" style="margin-top:0.15rem;flex-shrink:0;">
                        <circle cx="12" cy="12" r="10"/><line x1="12" y1="7" x2="12" y2="13"/><line x1="12" y1="17" x2="12.01" y2="17"/>
                    </svg>
                    <div>
                        <strong style="display:block;margin-bottom:0.25rem;">Needs fixing on this step</strong>
                        <span x-text="currentFixMessage()"></span>
                        @if ($applicant->review_remarks)
                            <div style="margin-top:0.45rem;color:#7f1d1d;font-weight:600;">{{ $applicant->review_remarks }}</div>
                        @endif
                    </div>
                </div>
            @endif



            <form
                method="POST"
                action="{{ route('enrollment.submit') }}"
                enctype="multipart/form-data"
                @submit="handleSubmit($event)"
                novalidate
                autocomplete="off"
                data-no-browser-autofill
            >
                @csrf
                {{-- Dummy fields to prevent browser autofill --}}
                <input type="text" name="prevent_autofill" class="hidden-autofill-field" tabindex="-1" autocomplete="off">
                <input type="password" name="prevent_autofill_pwd" class="hidden-autofill-field" tabindex="-1" autocomplete="new-password">
                {{-- Pass account email silently so it gets saved --}}
                <input type="hidden" name="email" value="{{ Auth::user()->email }}">
                @if ($applicant)
                    <input type="hidden" name="applicant_id" value="{{ $applicant->id }}">
                @endif

                <!-- Skeleton Loading (during step transitions) -->
                <div x-show="pageLoading" class="skeleton-container">
                    <div class="skeleton-header">
                        <div class="skeleton skeleton-title"></div>
                        <div class="skeleton skeleton-subtitle"></div>
                    </div>
                    <div class="skeleton-form">
                        <div class="skeleton skeleton-input"></div>
                        <div class="skeleton skeleton-input"></div>
                        <div class="skeleton skeleton-input"></div>
                        <div class="skeleton skeleton-input-large"></div>
                        <div class="skeleton skeleton-input"></div>
                        <div class="skeleton skeleton-input"></div>
                    </div>
                </div>

                <div x-show="!pageLoading">
                    <!-- STEP 1: Enrollment Setup -->
                    <template x-if="step === 1">
                        @include('enrollment.partials.step1')
                    </template>

                    <!-- STEP 2: Student Information -->
                    <template x-if="step === 2">
                        @include('enrollment.partials.step2')
                    </template>

                    <!-- STEP 3: Address & Contact -->
                    <template x-if="step === 3">
                        @include('enrollment.partials.step3')
                    </template>

                    <!-- STEP 4: Parent / Guardian Information -->
                    <template x-if="step === 4">
                        @include('enrollment.partials.step4')
                    </template>

                    <!-- STEP 5: Medical & Emergency -->
                    <template x-if="step === 5">
                        @include('enrollment.partials.step5')
                    </template>

                    <!-- STEP 6: Documents -->
                    <div x-show="step === 6" x-cloak class="space-y-5">
                        @include('enrollment.partials.step6')
                    </div>

                    <!-- STEP 7: Payment & Proof of Payment Upload -->
                    <div x-show="step === 7" x-cloak class="space-y-5">
                        @include('enrollment.partials.step7')
                    </div>

                    <!-- STEP 8: Final Review & Confirmation -->
                    <div x-show="step === 8" x-cloak class="space-y-5">
                        @include('enrollment.partials.step8')
                    </div>

                    <!-- Hidden fields — always in DOM for final form submission -->
                    @include('enrollment.partials.hidden_fields')

                </div><!-- end x-show="!pageLoading" -->

                <!-- Form Actions -->
                <div class="form-actions form-actions-row" x-show="!pageLoading">
                    {{-- Left: Cancel & Save Draft --}}
                    <div style="display: flex; gap: 0.5rem; align-items: center;">
                        <button type="button" @click="openCancelPrompt()" class="btn-secondary">
                            Cancel
                        </button>
                        <button type="button" @click="saveDraft({ force: true, showStatus: true })" class="btn-secondary" :disabled="stepSaving" :class="{ 'is-disabled': stepSaving }">
                            Save Draft
                        </button>
                    </div>
                    {{-- Right: Back + Next/Submit --}}
                    <div class="form-actions-group">
                        <button type="button" x-show="step > 1" @click="prevStep()" class="btn-secondary">
                            Back
                        </button>
                        <button type="button" x-show="step < totalSteps" @click="nextStep()" class="btn-primary"
                            :disabled="stepSaving"
                            :class="{ 'is-disabled': stepSaving }">
                            <span>Next</span>
                            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <polyline points="9 18 15 12 9 6"/>
                            </svg>
                        </button>
                        <button type="button" x-show="step === totalSteps" @click="openSubmitConfirmModal()" class="btn-primary"
                            style="background-color: #059669 !important; border-color: #047857 !important;"
                            :disabled="loading || hasFilePreparationPending() || draftSaving"
                            :class="{ 'is-disabled': loading || hasFilePreparationPending() || draftSaving }">
                            <span x-text="hasFilePreparationPending() ? 'Preparing files...' : (draftSaving ? 'Saving files...' : 'CONFIRM & FINAL SUBMIT ENROLLMENT')"></span>
                        </button>
                    </div>
                </div>

                {{-- Confirm Final Submission Pop-up Modal --}}
                <div x-show="showSubmitConfirmModal" x-cloak class="confirm-overlay" @keydown.escape.window="showSubmitConfirmModal = false">
                    <div class="confirm-dialog" @click.outside="showSubmitConfirmModal = false" style="max-width: 480px;">
                        <button type="button" class="confirm-close-button" @click="showSubmitConfirmModal = false" aria-label="Close dialog">
                            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4">
                                <line x1="18" y1="6" x2="6" y2="18"/>
                                <line x1="6" y1="6" x2="18" y2="18"/>
                            </svg>
                        </button>
                        <div class="confirm-dialog-copy">
                            <div style="display:flex;align-items:center;gap:0.75rem;margin-bottom:0.5rem;">
                                <div style="width:38px;height:38px;border-radius:10px;background:#dcfce7;color:#15803d;display:flex;align-items:center;justify-content:center;flex-shrink:0;">
                                    <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><polyline points="20 6 9 17 4 12"/></svg>
                                </div>
                                <h3 style="margin:0;font-size:1.25rem;color:#0f172a;font-weight:900;">Confirm Final Submission?</h3>
                            </div>
                            <p style="font-size:0.88rem;line-height:1.5;color:#475569;margin-top:0.5rem;">
                                Are you sure all student information, uploaded documents, and payment receipt details are complete and authentic?
                            </p>
                            <div style="background:#f8fafc;border:1px solid #e2e8f0;border-radius:12px;padding:0.85rem;margin-top:0.85rem;font-size:0.82rem;line-height:1.4;color:#334155;">
                                <strong>Note:</strong> Once submitted, your application will be locked for review by the Admissions & Finance office and recorded automatically in the AMIS Admin Portal.
                            </div>
                        </div>
                        <div class="confirm-dialog-actions" style="margin-top:1.25rem;display:flex;gap:0.75rem;justify-content:flex-end;">
                            <button type="button" class="btn-secondary" @click="showSubmitConfirmModal = false">Edit Application</button>
                            <button type="button" class="btn-primary" style="background-color:#059669 !important;" @click="confirmAndSubmitForm($event)">Yes, Submit Enrollment Now</button>
                        </div>
                    </div>
                </div>
            </form>

            <div x-show="showDuplicateModal" x-cloak class="confirm-overlay" @keydown.escape.window="closeDuplicateModal()">
                <div class="confirm-dialog duplicate-dialog" @click.outside="closeDuplicateModal()">
                    <button type="button" class="confirm-close-button" @click="closeDuplicateModal()" aria-label="Close dialog">
                        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4">
                            <line x1="18" y1="6" x2="6" y2="18"/>
                            <line x1="6" y1="6" x2="18" y2="18"/>
                        </svg>
                    </button>
                    <div class="confirm-dialog-copy">
                        <h3 style="color:#991b1b;font-size:1.45rem;line-height:1.25;">Possible duplicate enrollment record found.</h3>
                        <p>An enrollment, application, or student record with the same full name and birthdate already exists in the system. Please review the student details before continuing.</p>
                    </div>
                    <div class="confirm-dialog-actions" style="grid-template-columns:1fr;">
                        <button type="button" class="btn-primary duplicate-dialog-button" style="width:100%;" @click="closeDuplicateModal()">OK</button>
                    </div>
                </div>
            </div>

            <div x-show="showCancelPrompt" x-cloak class="confirm-overlay" @keydown.escape.window="closeCancelPrompt()">
                <div class="confirm-dialog" @click.outside="closeCancelPrompt()">
                    <button type="button" class="confirm-close-button" @click="closeCancelPrompt()" aria-label="Close dialog">
                        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4">
                            <line x1="18" y1="6" x2="6" y2="18"/>
                            <line x1="6" y1="6" x2="18" y2="18"/>
                        </svg>
                    </button>
                    <div class="confirm-dialog-copy">
                        <h3>Leave this enrollment form?</h3>
                        <p>Save your latest changes, stay on this page, or discard the draft completely so it will not come back when you reopen the form.</p>
                    </div>
                    <div class="confirm-dialog-actions">
                        @if ($applicant && $applicant->status === 'draft')
                            <form method="POST" action="{{ route('enrollment.draft.discard') }}" data-clear-draft-form>
                                @csrf
                                @method('DELETE')
                                <input type="hidden" name="applicant_id" value="{{ $applicant->id }}">
                                <button type="submit" class="danger-text-button">Discard Draft</button>
                            </form>
                        @else
                            <button type="button" class="danger-text-button" @click="discardUnsavedDraft()">Discard Unsaved Draft</button>
                        @endif
                        <button type="button" class="btn-primary" @click="cancelAndSave()">Save & Exit</button>
                    </div>
                </div>
            </div>
            </div>
        </div>

        <!-- Footer -->
        <div class="enrollment-footer">
            &copy; {{ date('Y') }} Al Munawwara Islamic School. All rights reserved.
        </div>
    </div>
</div>

@push('scripts')
<script>
    @include('enrollment.partials.script')
</script>
@endpush
</x-guest-layout>
