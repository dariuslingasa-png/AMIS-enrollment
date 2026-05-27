<x-guest-layout>
<div x-data="enrollmentForm()" @open-affidavit-builder.window="openAffidavitBuilder()" class="enrollment-page">
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

            <!-- Client Error -->
            <div x-show="error" x-cloak class="enrollment-error">
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/>
                </svg>
                <span x-text="error"></span>
            </div>

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

                    <!-- Hidden fields — always in DOM for final form submission -->
                    @include('enrollment.partials.hidden_fields')

                </div><!-- end x-show="!pageLoading" -->

                <!-- Form Actions -->
                <div class="form-actions form-actions-row" x-show="!pageLoading">
                    {{-- Left: Cancel --}}
                    <button type="button" @click="openCancelPrompt()" class="btn-secondary">
                        Cancel
                    </button>
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
                        <button type="submit" x-show="step === totalSteps" class="btn-primary"
                            :disabled="loading"
                            :class="{ 'is-disabled': loading }">
                            <span>Submit Application</span>
                        </button>
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
