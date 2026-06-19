<section class="space-y-5">
    <div class="grid grid-cols-1 gap-5 lg:grid-cols-2 xl:gap-6">
        <x-upload-requirement-card
            title="Recent or Annual 1:1 Ratio"
            description="Please provide a recent clear student photo for admissions review."
            name="photo_2x2"
            :required="true"
            :uploaded="$applicant?->photo_2x2_url"
            accept="image/jpeg,image/jpg,image/png"
            :maxSizeMB="2"
            guide-title="Photo guide"
            :guide="[
                'Recent or annual student picture.',
                'Plain white background, front-facing, clear face.',
                'Hijab color: white for elementary students; black for high school students.',
                'No filters, heavy shadows, or hats.',
            ]"
            guide-notice="For niqab-wearing students, please wear hijab for the photo and follow the hijab photo guide. A female staff member or female admin will review it respectfully when privacy is needed."
            guide-notice-gender="Female"
            :support-panel-groups="[
                'Female' => [
                    ['src' => 'images/2x2-guide/non-hijab-guidelines.webp', 'label' => 'Non-hijab guidelines', 'alt' => 'Non-hijab photo guidelines for elementary and high school students'],
                    ['src' => 'images/2x2-guide/hijab-guidelines.webp', 'label' => 'Hijab guidelines', 'alt' => 'Hijab photo guidelines for elementary and high school students'],
                ],
                'Male' => [
                    ['src' => 'images/2x2-guide/boys-guidelines.webp', 'label' => 'Boys guidelines', 'alt' => 'Boys photo guidelines for elementary and high school students'],
                ],
            ]"
            :show-photo-sample="true"
        />

        <x-upload-requirement-card
            title="Photocopy Birth Certificate"
            description="Upload a readable photocopy of the student birth certificate."
            name="birth_cert"
            :required="false"
            :uploaded="$applicant?->birth_cert_url"
            accept="image/jpeg,image/jpg,image/png,application/pdf"
            :maxSizeMB="10"
            guide-title="Preparation note"
            :guide="[
                'Upload the birth certificate copy with the student name readable.',
                'Upload a clear JPG or PNG image.',
                'If the original is not yet available, you may upload an affidavit below.',
            ]"
            :guide-images="[
                ['src' => 'images/document-guide/birth-cert-blurry.svg', 'label' => 'Blurry', 'tone' => 'danger', 'alt' => 'Blurry birth certificate upload example'],
                ['src' => 'images/document-guide/document-cropped.svg', 'label' => 'Cropped', 'tone' => 'danger', 'alt' => 'Cropped birth certificate upload example'],
                ['src' => 'images/document-guide/birth-cert-correct.svg', 'label' => 'Correct', 'tone' => 'success', 'alt' => 'Correct readable birth certificate upload example'],
            ]"
        />

        <div x-show="form.student_type !== 'Old' && !isKinderOrNursery()" x-cloak>
            <x-upload-requirement-card
                title="Official Transcript / Report Card"
                description="Choose the option that applies to the student."
                name="report_card"
                :required="true"
                :uploaded="$applicant?->report_card_url"
                accept="image/jpeg,image/jpg,image/png,application/pdf"
                :maxSizeMB="5"
                guide-title="Preparation note"
                :guide="[
                    'Latest report card or transcript copy.',
                    'Make sure grades and school name are visible.',
                    'Do not have a report card yet? Use the affidavit option in this card.',
                    'Temporary proof must be fully filled out and signed.',
                    'Upload a flat, uncropped image for faster review.',
                ]"
                :guide-images="[
                    ['src' => 'images/document-guide/document-blurry.svg', 'label' => 'Blurry', 'tone' => 'danger', 'alt' => 'Blurry report card upload example'],
                    ['src' => 'images/document-guide/document-cropped.svg', 'label' => 'Cropped', 'tone' => 'danger', 'alt' => 'Cropped report card upload example'],
                    ['src' => 'images/document-guide/document-correct.svg', 'label' => 'Correct', 'tone' => 'success', 'alt' => 'Correct readable report card upload example'],
                ]"
                :defer-upload="true"
            >
            <div
                class="report-card-options"
                x-data="{
                    hasAffidavit: {{ $applicant?->affidavit_url ? 'true' : 'false' }},
                    affidavitMode: {{ $applicant?->affidavit_url ? 'true' : 'false' }},
                    removingAffidavit: false,
                    async removeAffidavit() {
                        if (!this.hasAffidavit || this.removingAffidavit) return;

                        this.removingAffidavit = true;

                        try {
                            const applicantQuery = window.AMIS_CURRENT_APPLICANT_ID ? '?applicant=' + encodeURIComponent(window.AMIS_CURRENT_APPLICANT_ID) : '';
                            const response = await fetch('{{ route('enrollment.draft.document.remove', ['document' => 'affidavit']) }}' + applicantQuery, {
                                method: 'DELETE',
                                headers: {
                                    'X-CSRF-TOKEN': document.querySelector('meta[name=&quot;csrf-token&quot;]').content,
                                    'Accept': 'application/json',
                                },
                            });

                            if (!response.ok) throw new Error('Unable to remove affidavit');

                            if (window.AMIS_CURRENT_APPLICANT_ID) {
                                localStorage.removeItem('enrollment_affidavit_draft_' + window.AMIS_CURRENT_APPLICANT_ID);
                            }

                            this.hasAffidavit = false;
                            this.affidavitMode = false;
                            window.dispatchEvent(new CustomEvent('enrollment:file-removed', {
                                detail: { name: 'affidavit' }
                            }));
                        } catch (_) {
                            window.dispatchEvent(new CustomEvent('enrollment:file-remove-failed', {
                                detail: { name: 'affidavit' }
                            }));
                        } finally {
                            this.removingAffidavit = false;
                        }
                    },
                }"
            >
                <div x-show="!affidavitMode && !showUpload">
                    <div style="display:flex;flex-direction:column;gap:0.75rem;">
                        <button type="button" class="report-card-option" @click="revealUpload(false)">
                            <div class="report-card-option-icon">
                                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/><line x1="16" y1="13" x2="8" y2="13"/><line x1="16" y1="17" x2="8" y2="17"/></svg>
                            </div>
                            <div class="report-card-option-text">
                                <span>Do you have a report card?</span>
                                <small>Upload the student's report card, transcript, or school record.</small>
                            </div>
                        </button>
                        <button type="button" class="report-card-option report-card-option-alt" @click="affidavitMode = true">
                            <div class="report-card-option-icon">
                                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"/><path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"/></svg>
                            </div>
                            <div class="report-card-option-text">
                                <span>Do not have a report card?</span>
                                <small>Upload the signed affidavit instead.</small>
                            </div>
                        </button>
                    </div>
                </div>

                {{-- Affidavit upload area --}}
                <div x-show="affidavitMode" x-cloak>
                    <button x-show="!hasAffidavit" x-cloak type="button" class="upload-choice-back" @click="affidavitMode = false" aria-label="Back to options">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M15 18l-6-6 6-6"/></svg>
                        <span>Back</span>
                    </button>

                    <div style="display:flex;flex-direction:column;gap:0.75rem;margin-top:0.75rem;">
                        <a x-show="!hasAffidavit" x-cloak href="{{ route('enrollment.affidavit', ['applicant' => $applicant?->id ?? '__APPLICANT__']) }}" class="report-card-option" style="text-decoration:none;" @click.prevent="window.dispatchEvent(new CustomEvent('open-affidavit-builder'))">
                            <div class="report-card-option-icon">
                                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"/><path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"/></svg>
                            </div>
                            <div class="report-card-option-text">
                                <span>Create Affidavit</span>
                                <small>Fill out and sign the affidavit form online.</small>
                            </div>
                        </a>

                        @if ($applicant?->affidavit_url)
                            <div x-show="hasAffidavit" x-cloak class="report-card-option report-card-affidavit-file">
                                <div class="report-card-affidavit-main">
                                    <div class="report-card-option-icon">
                                        <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M20 6 9 17l-5-5"/></svg>
                                    </div>
                                    <div class="report-card-option-text">
                                        <span>Signed affidavit saved</span>
                                        <small>Temporary proof is ready for review.</small>
                                    </div>
                                </div>
                                <div class="report-card-affidavit-actions">
                                    <a
                                        href="{{ asset('storage/' . $applicant->affidavit_url) }}"
                                        target="_blank"
                                        rel="noopener"
                                        class="report-card-affidavit-view"
                                    >
                                        View PDF
                                    </a>
                                    <a
                                        href="{{ route('enrollment.affidavit', ['applicant' => $applicant?->id ?? '__APPLICANT__']) }}"
                                        class="report-card-affidavit-edit"
                                        @click.prevent="window.dispatchEvent(new CustomEvent('open-affidavit-builder'))"
                                    >
                                        Edit
                                    </a>
                                    <button
                                        type="button"
                                        class="report-card-affidavit-delete"
                                        @click="removeAffidavit()"
                                        :disabled="removingAffidavit"
                                    >
                                        <span x-text="removingAffidavit ? 'Deleting...' : 'Delete'"></span>
                                    </button>
                                </div>
                            </div>
                        @endif
                    </div>

                    <div class="!mt-4 !rounded-xl !bg-slate-50 !p-4">
                        <p class="!m-0 !text-sm !font-semibold !leading-6 !text-slate-800">How to submit</p>
                        <ul x-show="!hasAffidavit" class="!mt-2 !m-0 !list-disc !space-y-1.5 !pl-5 !text-sm !leading-6 !text-slate-600">
                            <li>Click "Create Affidavit" to fill out the form online.</li>
                            <li>Fill all fields and sign with parent/guardian signature.</li>
                            <li>Save the signed affidavit.</li>
                            <li>Or download the <a href="{{ asset('docs/Affidavit_enrollee.pdf') }}" target="_blank" class="!text-emerald-700 !font-semibold !underline">blank affidavit PDF</a> to print and fill manually.</li>
                        </ul>
                        <ul x-show="hasAffidavit" x-cloak class="!mt-2 !m-0 !list-disc !space-y-1.5 !pl-5 !text-sm !leading-6 !text-slate-600">
                            <li>The signed affidavit has been saved as temporary academic proof.</li>
                            <li>Use Edit if you need to update the affidavit details or signature.</li>
                            <li>Use Delete only if you want to remove the saved affidavit and create a new one later.</li>
                        </ul>
                    </div>
                </div>
            </div>

            <div class="!mt-4 !rounded-xl !bg-slate-50 !p-4">
                <p class="!m-0 !text-sm !font-semibold !leading-6 !text-slate-800">Required Academic Document</p>
                <div class="!mt-2 !space-y-1.5">
                    <ul class="!m-0 !list-disc !space-y-1.5 !pl-5 !text-sm !leading-6 !text-slate-600">
                        <li>If the report card is available, please upload it here.</li>
                        <li>If it is not yet available at the time of admission, complete the affidavit page with all required information and the parent or guardian signature.</li>
                        <li>The original report card or credential must still be submitted when the school requests it.</li>
                    </ul>
                </div>
            </div>

            </x-upload-requirement-card>
        </div>

        <x-upload-requirement-card
            title="Marriage Contract (Parents)"
            description="Optional supporting document if available or requested."
            name="marriage_contract"
            :uploaded="$applicant?->marriage_contract_url"
            accept="image/jpeg,image/jpg,image/png,application/pdf"
            :maxSizeMB="10"
            guide-title="Preparation note"
            :guide="[
                'Only upload if available or requested.',
                'A clear JPG or PNG photo is okay.',
                'Make sure the full page, names, and signatures are visible.',
            ]"
            :guide-images="[
                ['src' => 'images/document-guide/document-blurry.svg', 'label' => 'Blurry', 'tone' => 'danger', 'alt' => 'Blurry marriage contract upload example'],
                ['src' => 'images/document-guide/document-cropped.svg', 'label' => 'Cropped', 'tone' => 'danger', 'alt' => 'Cropped marriage contract upload example'],
                ['src' => 'images/document-guide/document-correct.svg', 'label' => 'Correct', 'tone' => 'success', 'alt' => 'Correct readable marriage contract upload example'],
            ]"
        />

        <x-upload-requirement-card
            title="Medical Record (if any)"
            description="Optional health record to help the school prepare support."
            name="medical_record"
            :uploaded="$applicant?->medical_record_url"
            accept="image/jpeg,image/jpg,image/png,application/pdf"
            :maxSizeMB="10"
            guide-title="Preparation note"
            :guide="[
                'Upload lab results, prescriptions, or health notes if relevant.',
                'This helps the school prepare support for the student.',
                'Make sure the clinic name, date, and details are readable.',
            ]"
            :guide-images="[
                ['src' => 'images/document-guide/document-blurry.svg', 'label' => 'Blurry', 'tone' => 'danger', 'alt' => 'Blurry medical record upload example'],
                ['src' => 'images/document-guide/document-cropped.svg', 'label' => 'Cropped', 'tone' => 'danger', 'alt' => 'Cropped medical record upload example'],
                ['src' => 'images/document-guide/document-correct.svg', 'label' => 'Correct', 'tone' => 'success', 'alt' => 'Correct readable medical record upload example'],
            ]"
        />

    </div>
</section>
