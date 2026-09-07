const LEGACY_DRAFT_KEY = 'amis_enrollment_draft';
const CURRENT_APPLICANT_ID = @json($applicant?->id);
window.AMIS_CURRENT_APPLICANT_ID = CURRENT_APPLICANT_ID;
const DRAFT_KEY = 'amis_enrollment_draft_user_{{ auth()->id() }}_applicant_' + (CURRENT_APPLICANT_ID || 'new');
const SHOULD_CLEAR_DRAFT_CACHE = @json((bool) session('clear_draft_cache'));
const DISCARDED_DRAFT_APPLICANT_ID = @json(session('discarded_draft_applicant_id'));
const START_FRESH_FORM = @json((bool) ($startFresh ?? false));
const SIBLING_DATA = @json($siblingData);
const REJECTION_FIX_STEPS = @json($rejectionFixSteps ?? []);
const REJECTION_REMARKS = @json($applicant?->review_remarks ?? '');
const INITIAL_PHOTO_GUIDE_GENDER = @js(old('gender', $applicant?->gender) ?: '');
const AFFIDAVIT_URL_TEMPLATE = @js(route('enrollment.affidavit', ['applicant' => '__APPLICANT__']));

document.addEventListener('alpine:init', () => {
    Alpine.store('enrollmentGuide', {
        gender: INITIAL_PHOTO_GUIDE_GENDER,
    });
});

// Force all enrollment form text inputs and textareas to uppercase visually and programmatically
document.addEventListener('input', function (e) {
    if (e.target && (e.target.tagName === 'INPUT' || e.target.tagName === 'TEXTAREA')) {
        const type = e.target.type ? e.target.type.toLowerCase() : 'text';
        if ((type === 'text' || type === 'search' || type === 'url' || e.target.tagName === 'TEXTAREA') && type !== 'email') {
            const originalVal = e.target.value;
            const upperVal = originalVal.toUpperCase();
            if (originalVal !== upperVal) {
                // Determine cursor position
                let start = e.target.selectionStart;
                let end = e.target.selectionEnd;

                e.target.value = upperVal;

                // Preserve selection range for text fields that support it
                if (start !== null && end !== null) {
                    try {
                        e.target.setSelectionRange(start, end);
                    } catch (err) {
                        // ignore error for inputs that don't support setSelectionRange
                    }
                }

                // Trigger a bubbling input event to update AlpineJS's x-model reactive state
                if (!e.target._uppercasing) {
                    e.target._uppercasing = true;
                    e.target.dispatchEvent(new Event('input', { bubbles: true }));
                    e.target._uppercasing = false;
                }
            }
        }
    }
}, true); // Use capture phase so we modify the value BEFORE Alpine's input/x-model listener reads it!

function enrollmentForm() {
    return {
        step: {{ $initialStep }},
        totalSteps: 8,
        loading: false,
        stepSaving: false,
        leavingWithoutSaving: false,
        showSubmitConfirmModal: false,
        paymentReceiptPreview: @js($applicant?->payment?->receipt_url ? (str_ends_with(strtolower($applicant->payment->receipt_url), '.pdf') ? 'pdf' : asset('storage/' . $applicant->payment->receipt_url)) : null),
        useSiblingSchedule: false,
        useSiblingParent: false,
        useSiblingAddress: false,
        gradeShifts: [],
        pageLoading: false,
        initialLoading: true,
        draftSaving: false,
        draftSaved: false,
        hasUserEdited: false,
        isDiscarding: false,
        draftDiscarded: false,
        savedApplicantId: CURRENT_APPLICANT_ID,
        showCancelPrompt: false,
        showDuplicateModal: false,
        detectingTimezone: false,
        timezoneMessage: '',
        error: '',
        searchStudentNumber: '',
        searchDOB: '',
        searchingStudent: false,
        searchError: '',
        searchSuccess: '',
        rejectionFixSteps: REJECTION_FIX_STEPS,
        rejectionRemarks: REJECTION_REMARKS,
        countriesLoading: true,
        countriesSource: 'api',
        countryApiUrl: '/countries.json',
        countries: [],
        toasts: [],
        hasAttemptedNext: false,
        _toastTimeout: null,
        submittingEnrollmentOverlay: false,
        submitProgressTitle: 'Submitting Enrollment',
        submitProgressStatus: 'Saving application...',
        showDuplicateErrorModal: false,
        isDuplicateRecord: false,
        duplicateErrorMessage: '',

        openSubmitConfirmModal() {
            this.error = '';
            const err = this.validateStep();
            if (err) {
                this.error = err;
                this.showToast(err, 'error');
                return;
            }
            this.showSubmitConfirmModal = true;
        },

        async confirmAndSubmitForm(event) {
            if (this._submitted || this.loading) return;
            this._submitted = true;
            this.loading = true;
            this.showSubmitConfirmModal = false;

            // Stop/clear autosave timers and wait for inflight request
            clearTimeout(this._debounceTimer);
            while (this._savingInflight) {
                await new Promise(resolve => setTimeout(resolve, 50));
            }

            // Display loading overlay
            this.submittingEnrollmentOverlay = true;
            this.submitProgressTitle = 'Submitting Enrollment';
            this.submitProgressStatus = 'Saving application...';

            const formEl = document.querySelector('[data-no-browser-autofill]');
            const formData = formEl ? new FormData(formEl) : new FormData();

            // Progress status stages
            setTimeout(() => { if (this.submittingEnrollmentOverlay) this.submitProgressStatus = 'Validating information...'; }, 500);
            setTimeout(() => { if (this.submittingEnrollmentOverlay) this.submitProgressStatus = 'Checking duplicate enrollment...'; }, 1000);
            setTimeout(() => { if (this.submittingEnrollmentOverlay) this.submitProgressStatus = 'Finalizing submission...'; }, 1500);

            try {
                const response = await fetch('{{ route("enrollment.submit") }}', {
                    method: 'POST',
                    headers: {
                        'Accept': 'application/json, text/plain, */*',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                    },
                    body: formData,
                });

                const data = await response.json();

                if (response.ok && data.success !== false) {
                    this.submitProgressTitle = '✓ Enrollment Submitted';
                    this.submitProgressStatus = 'Enrollment submitted successfully. Redirecting...';
                    this.clearLocalDraft();
                    setTimeout(() => {
                        window.location.href = data.redirect || '{{ route("enrollment.dashboard") }}';
                    }, 600);
                } else {
                    // Hide loading overlay and re-enable submission controls on validation/duplicate failure
                    this.submittingEnrollmentOverlay = false;
                    this._submitted = false;
                    this.loading = false;

                    const errMsg = data.message || (data.errors ? Object.values(data.errors).flat()[0] : null) || 'A validation error occurred.';
                    this.error = errMsg;
                    this.duplicateErrorMessage = errMsg;
                    this.isDuplicateRecord = !!data.duplicate;
                    this.showDuplicateErrorModal = true;
                    this.highlightInvalidFields();
                }
            } catch (err) {
                // Hide loading overlay and re-enable controls on server error
                this.submittingEnrollmentOverlay = false;
                this._submitted = false;
                this.loading = false;

                const networkMsg = 'A network error occurred. Your draft remains safe. Please try again.';
                this.error = networkMsg;
                this.duplicateErrorMessage = networkMsg;
                this.isDuplicateRecord = false;
                this.showDuplicateErrorModal = true;
            }
        },

        showToast(message, type = 'error') {
            if (!message) return;
            this.toasts = [{ id: Date.now(), message, type }];
            clearTimeout(this._toastTimeout);
            this._toastTimeout = setTimeout(() => {
                this.toasts = [];
            }, 6000);
        },

        removeToast(id) {
            this.toasts = [];
        },

        isFieldInvalid(field) {
            if (!this.hasAttemptedNext) return false;
            if (this.step === 1) {
                if (field === 'student_type') return !this.form.student_type;
                if (field === 'grade_level') return !this.form.grade_level;
                if (field === 'learning_mode_main') return !this.form.learning_mode_main;
                if (field === 'timezone') return this.form.learning_mode_main === 'Flexible Online Learning' && !this.form.timezone;
                if (field === 'learning_mode_shift') return this.form.learning_mode_main === 'Flexible Online Learning' && !this.form.learning_mode_shift;
            }
            if (this.step === 2) {
                if (field === 'last_name') return !this.form.last_name || !this.form.last_name.trim();
                if (field === 'first_name') return !this.form.first_name || !this.form.first_name.trim();
                if (field === 'gender') return !this.form.gender;
                if (field === 'date_of_birth') return !this.form.date_of_birth;
                if (field === 'place_of_birth') return !this.form.place_of_birth || !this.form.place_of_birth.trim();
                if (field === 'religion') return !this.form.religion || !this.form.religion.trim();
                if (field === 'lrn') return this.form.lrn && this.form.lrn.length !== 12;
            }
            if (this.step === 3) {
                if (field === 'country') return !this.form.country;
                if (field === 'street_address') return !this.form.street_address || !this.form.street_address.trim();
                if (field === 'mobile_country_code') return !this.form.mobile_country_code;
                if (field === 'mobile_number') return !this.form.mobile_number || !this.form.mobile_number.trim() || this.form.mobile_number.replace(/\D/g, '').length < 7;
            }
            if (this.step === 4) {
                const hasFather = !!(this.form.father_first_name || '').trim() && !!(this.form.father_last_name || '').trim();
                const hasMother = !!(this.form.mother_first_name || '').trim() && !!(this.form.mother_last_name || '').trim();
                if (['father_last_name', 'father_first_name', 'mother_last_name', 'mother_first_name'].includes(field)) {
                    return !hasFather && !hasMother;
                }
                if (field === 'parent_country_code') return !this.form.parent_country_code;
                if (field === 'parent_mobile') return !this.form.parent_mobile || !this.form.parent_mobile.trim() || this.form.parent_mobile.replace(/\D/g, '').length < 7;
                if (field === 'facebook') return !this.form.facebook || !this.form.facebook.trim();
                if (field === 'facebook_screenshot') {
                    const fbInput = document.querySelector('input[name="facebook_screenshot"]');
                    const hasFbScreenshot = this.uploadedFiles.facebook_screenshot || (fbInput && fbInput.files && fbInput.files.length > 0);
                    return !hasFbScreenshot;
                }
            }
            if (this.step === 5) {
                if (field === 'medical_has_concern') return !this.form.medical_has_concern;
                if (field === 'emergency_name') return !this.form.emergency_name || !this.form.emergency_name.trim();
                if (field === 'emergency_relationship') return !this.form.emergency_relationship || !this.form.emergency_relationship.trim();
                if (field === 'emergency_phone') return !this.form.emergency_phone || !this.form.emergency_phone.trim();
            }
            if (this.step === 7) {
                if (field === 'payment_method') return !this.form.payment_method;
                if (field === 'amount') return !this.form.amount || parseFloat(this.form.amount) <= 0;
                if (field === 'payment_receipt' || field === 'remarks') {
                    const receiptInput = document.querySelector('input[name="payment_receipt"]');
                    const hasReceipt = this.paymentReceiptPreview || (receiptInput && receiptInput.files && receiptInput.files.length > 0);
                    const hasRemarks = this.form.remarks && this.form.remarks.trim().length > 0;
                    return !hasReceipt && !hasRemarks;
                }
            }
            return false;
        },

        init() {
            this.$watch('error', (val) => {
                if (val) {
                    this.showToast(val, 'error');
                }
            });
        },
        _debounceTimer: null,
        _submitted: false,
        _savingInflight: false,
        uploadedFiles: {
            photo_2x2: {{ $applicant?->photo_2x2_url ? 'true' : 'false' }},
            birth_cert: {{ $applicant?->birth_cert_url ? 'true' : 'false' }},
            report_card: {{ $applicant?->report_card_url ? 'true' : 'false' }},
            marriage_contract: {{ $applicant?->marriage_contract_url ? 'true' : 'false' }},
            medical_record: {{ $applicant?->medical_record_url ? 'true' : 'false' }},
            affidavit: {{ $applicant?->affidavit_url ? 'true' : 'false' }},
            facebook_screenshot: {{ $applicant?->facebook_screenshot_url ? 'true' : 'false' }},
        },
        filePreparation: {
            photo_2x2: false,
            birth_cert: false,
            report_card: false,
            marriage_contract: false,
            medical_record: false,
            affidavit: false,
            facebook_screenshot: false,
        },
        completedSteps: @json($completedSteps),
        visitedSteps: @json($completedSteps ? array_values(array_unique(array_merge([1], $completedSteps))) : [1]),
        steps: [
            { num: 1, label: 'Setup' },
            { num: 2, label: 'Student' },
            { num: 3, label: 'Address' },
            { num: 4, label: 'Parents' },
            { num: 5, label: 'Medical' },
            { num: 6, label: 'Documents' },
            { num: 7, label: 'Payment' },
            { num: 8, label: 'Preview' },
        ],
        stepTitles: ['Enrollment Setup', 'Student Information', 'Address & Contact', 'Parent / Guardian Information', 'Medical & Emergency', 'Documents', 'Mode of Payment & Receipt', 'Final Review & Submit'],
        form: {
            payment_method: '{{ old("method", $applicant?->payment?->method ?? "gcash") }}',
            amount: '{{ old("amount", ($applicant?->payment?->amount ?? "4000.00")) }}',
            reference_no: '{{ old("reference_no", ($applicant?->payment?->reference_no ?? "")) }}',
            remarks: '{{ old("remarks", ($applicant?->payment?->remarks ?? "")) }}',
            facebook: '{{ old("facebook", $applicant?->facebook ?? "") }}',
            whatsapp: '{{ old("whatsapp", $applicant?->whatsapp ?? "") }}',
            student_type: '{{ old("student_type", $applicant?->student_type ?? "") }}',
            amis_student_id: '{{ old("amis_student_id", $applicant?->amis_student_id ?? "") }}',
            learning_mode: '{{ old("learning_mode", $applicant?->learning_mode ?? "") }}',
            learning_mode_main: '{{ old("learning_mode", $applicant?->learning_mode ?? "") }}'.split(' - ')[0],
            learning_mode_shift: '{{ old("learning_mode", $applicant?->learning_mode ?? "") }}'.includes(' - ') ? '{{ old("learning_mode", $applicant?->learning_mode ?? "") }}'.split(' - ')[1] : '',
            timezone: @js(old("timezone", $applicant?->timezone ?? "")),
            lrn: '{{ old("lrn", ($applicant?->lrn === "NA" ? "" : $applicant?->lrn)) }}',
            grade_level: '{{ old("grade_level", $applicant?->grade_level) }}',
            last_name: '{{ old("last_name", $applicant?->last_name) }}',
            first_name: '{{ old("first_name", $applicant?->first_name) }}',
            middle_name: '{{ old("middle_name", $applicant?->middle_name) }}',
            suffix: '{{ old("suffix", $applicant?->suffix) }}',
            gender: '{{ old("gender", $applicant?->gender) }}',
            date_of_birth: '{{ old("date_of_birth", $applicant?->date_of_birth?->format("Y-m-d")) }}',
            place_of_birth: '{{ old("place_of_birth", $applicant?->place_of_birth) }}',
            religion: '{{ old("religion", $applicant?->religion) }}',
            ethnicity: '{{ old("ethnicity", $applicant?->ethnicity ?? "") }}',
            country: '{{ old("country", $applicant?->country) }}',
            country_choice: @js(old("country", $applicant?->country) ? old("country", $applicant?->country) : ''),
            state_province: '{{ old("state_province", $applicant?->state_province) }}',
            city: '{{ old("city", $applicant?->city) }}',
            street_address: '{{ old("street_address", $applicant?->street_address) }}',
            postal_code: '{{ old("postal_code", $applicant?->postal_code) }}',
            address: '{{ old("address", $applicant?->address) }}',
            email: '{{ old("email", $applicant?->email) }}',
            mobile_country_code: '{{ old("mobile_country_code", $applicant?->mobile_country_code) ?: "+63" }}',
            mobile_number: '{{ old("mobile_number", $applicant?->mobile_number) }}',
            father_last_name: '{{ old("father_last_name", $applicant?->father_last_name) }}',
            father_first_name: '{{ old("father_first_name", $applicant?->father_first_name) }}',
            father_middle_name: '{{ old("father_middle_name", $applicant?->father_middle_name) }}',
            father_occupation: '{{ old("father_occupation", $applicant?->father_occupation) }}',
            mother_last_name: '{{ old("mother_last_name", $applicant?->mother_last_name) }}',
            mother_first_name: '{{ old("mother_first_name", $applicant?->mother_first_name) }}',
            mother_middle_name: '{{ old("mother_middle_name", $applicant?->mother_middle_name) }}',
            mother_occupation: '{{ old("mother_occupation", $applicant?->mother_occupation) }}',
            home_address: '{{ old("home_address", $applicant?->home_address) }}',
            home_state_province: '{{ old("home_state_province", $applicant?->home_state_province) }}',
            home_city: '{{ old("home_city", $applicant?->home_city) }}',
            home_street_address: '{{ old("home_street_address", $applicant?->home_street_address) }}',
            home_postal_code: '{{ old("home_postal_code", $applicant?->home_postal_code) }}',
            home_country: '{{ old("home_country", "") }}',
            same_as_permanent: {{ old('same_as_permanent', $applicant ? ((blank($applicant->home_address) || $applicant->home_address === $applicant->address) ? '1' : '0') : '0') ? 'true' : 'false' }},
            parent_country_code: '{{ old("parent_country_code", $applicant?->parent_country_code) ?: "+63" }}',
            parent_mobile: '{{ old("parent_mobile", $applicant?->parent_mobile) }}',
            parent_email: '{{ old("parent_email", $applicant?->parent_email) }}',
            referral_source: '{{ old("referral_source", $applicant?->referral_source) }}',
            psych_testing: '{{ old("psych_testing", $applicant?->psych_testing) }}',
            prescription_med: '{{ old("prescription_med", $applicant?->prescription_med) }}',
            medical_has_concern: '{{ old("medical_has_concern", $applicant?->medical_has_concern) }}',
            allergies: '{{ old("allergies", $applicant?->allergies) }}',
            current_medications: '{{ old("current_medications", $applicant?->current_medications) }}',
            health_conditions: '{{ old("health_conditions", $applicant?->health_conditions) }}',
            emergency_instructions: '{{ old("emergency_instructions", $applicant?->emergency_instructions) }}',
            medical_history: '{{ old("medical_history", $applicant?->medical_history) }}',
            med_explanation: '{{ old("med_explanation", $applicant?->med_explanation) }}',
            family_physician: '{{ old("family_physician", $applicant?->family_physician) }}',
            physician_phone: '{{ old("physician_phone", $applicant?->physician_phone) }}',
            emergency_name: '{{ old("emergency_name", $applicant?->emergency_name) }}',
            emergency_relationship: '{{ old("emergency_relationship", $applicant?->emergency_relationship) }}',
            emergency_phone: '{{ old("emergency_phone", $applicant?->emergency_phone) }}',
            agreed_to_terms: {{ old('agreed_to_terms') ? 'true' : 'false' }},
            agreed_to_fee_policy: {{ old('agreed_to_fee_policy') ? 'true' : 'false' }},
            agreed_to_data_privacy: {{ old('agreed_to_data_privacy') ? 'true' : 'false' }},
        },
        fallbackCountries: [
            { name: 'Philippines', code: 'PH', callingCode: '+63', flagPng: 'https://flagcdn.com/w80/ph.png' },
            { name: 'Saudi Arabia', code: 'SA', callingCode: '+966', flagPng: 'https://flagcdn.com/w80/sa.png' },
            { name: 'United Arab Emirates', code: 'AE', callingCode: '+971', flagPng: 'https://flagcdn.com/w80/ae.png' },
            { name: 'Qatar', code: 'QA', callingCode: '+974', flagPng: 'https://flagcdn.com/w80/qa.png' },
            { name: 'Kuwait', code: 'KW', callingCode: '+965', flagPng: 'https://flagcdn.com/w80/kw.png' },
            { name: 'Bahrain', code: 'BH', callingCode: '+973', flagPng: 'https://flagcdn.com/w80/bh.png' },
            { name: 'Malaysia', code: 'MY', callingCode: '+60', flagPng: 'https://flagcdn.com/w80/my.png' },
            { name: 'United States', code: 'US', callingCode: '+1', flagPng: 'https://flagcdn.com/w80/us.png' },
        ],

        get countriesWithCallingCode() {
            return this.countries.filter(country => country.callingCode);
        },

        get selectedCountry() {
            return this.countries.find(country => country.name === this.form.country_choice);
        },

        get selectedMobileCodeCountry() {
            return this.countriesWithCallingCode.find(country => country.callingCode === this.form.mobile_country_code);
        },

        get selectedParentCodeCountry() {
            return this.countriesWithCallingCode.find(country => country.callingCode === this.form.parent_country_code);
        },

        get selectedPermanentCountry() {
            const countryName = this.form.same_as_permanent ? this.form.country : this.form.home_country;
            return this.countries.find(country => country.name === countryName);
        },

        get compiledPresentAddress() {
            return [
                this.form.street_address,
                this.form.postal_code,
                this.form.country,
            ].filter(Boolean).join(', ');
        },

        get compiledHomeAddress() {
            if (this.form.same_as_permanent) return this.compiledPresentAddress;

            return [
                this.form.home_street_address,
                this.form.home_postal_code,
                this.form.home_country,
            ].filter(Boolean).join(', ');
        },

        clearMedicalFields() {
            this.form.psych_testing = '';
            this.form.prescription_med = '';
            this.form.allergies = '';
            this.form.current_medications = '';
            this.form.health_conditions = '';
            this.form.emergency_instructions = '';
            this.form.medical_history = '';
            this.form.med_explanation = '';
            this.form.family_physician = '';
            this.form.physician_phone = '';
        },

        isKinderOrNursery() {
            const g = (this.form.grade_level || '').toLowerCase();
            return g.includes('kinder');
        },

        hasFilePreparationPending() {
            return Object.values(this.filePreparation).some(Boolean);
        },

        toggleStudentType(value) {
            this.form.student_type = this.form.student_type === value ? '' : value;
            if (this.form.student_type !== 'Old') {
                this.form.amis_student_id = '';
            }
        },

        toggleLearningMode(value) {
            if (this.form.learning_mode_main === value) {
                this.form.learning_mode_main = '';
                this.form.learning_mode_shift = '';
                this.form.learning_mode = '';
                this.form.timezone = '';
                this.timezoneMessage = '';
                return;
            }

            this.form.learning_mode_main = value;
            this.form.learning_mode_shift = '';
            this.form.learning_mode = value;
            this.timezoneMessage = '';

            if (value === 'Face-to-Face') {
                this.form.timezone = '';
            }
        },

        toggleLearningShift(shift) {
            if (this.form.learning_mode_shift === shift) {
                this.form.learning_mode_shift = '';
                this.form.learning_mode = this.form.learning_mode_main;
                return;
            }

            this.form.learning_mode_shift = shift;
            this.form.learning_mode = this.form.learning_mode_main + ' - ' + shift;
            this.hasUserEdited = true;
        },

        toggleMedicalConcern(value) {
            if (this.form.medical_has_concern === value) {
                this.form.medical_has_concern = '';
                this.clearMedicalFields();
                this.hasUserEdited = true;
                return;
            }

            this.form.medical_has_concern = value;

            if (value === 'No') {
                this.clearMedicalFields();
            }
            this.hasUserEdited = true;
        },

        filteredCountries(search) {
            const query = (search || '').trim().toLowerCase();
            return this.countries
                .filter(country => {
                    if (!query) return true;
                    const name = country.name.toLowerCase();
                    const code = country.code.toLowerCase();

                    // Direct match
                    if (name.includes(query) || code.includes(query)) return true;

                    // UAE Aliases
                    if (code === 'ae') {
                        const uaeTerms = ['uae', 'united arab', 'united arabs', 'emirate', 'emirates'];
                        if (uaeTerms.some(term => query.includes(term) || term.includes(query))) return true;
                    }

                    // Saudi Aliases
                    if (code === 'sa') {
                        const saudiTerms = ['saudi', 'saudi arabia', 'ksa'];
                        if (saudiTerms.some(term => query.includes(term) || term.includes(query))) return true;
                    }

                    // US Aliases
                    if (code === 'us') {
                        const usaTerms = ['usa', 'united states', 'america'];
                        if (usaTerms.some(term => query.includes(term) || term.includes(query))) return true;
                    }

                    // UK Aliases
                    if (code === 'gb') {
                        const ukTerms = ['uk', 'united kingdom', 'great britain', 'britain'];
                        if (ukTerms.some(term => query.includes(term) || term.includes(query))) return true;
                    }

                    return false;
                })
                .slice(0, query ? 80 : 250);
        },

        filteredCallingCountries(search) {
            const query = (search || '').trim().toLowerCase();
            if (!query) return this.countriesWithCallingCode;
            return this.countriesWithCallingCode
                .filter(country => {
                    const name = country.name.toLowerCase();
                    const code = country.code.toLowerCase();
                    const calling = (country.callingCode || '').toLowerCase();

                    // Direct match
                    if (name.includes(query) || code.includes(query) || calling.includes(query)) return true;

                    // UAE Aliases
                    if (code === 'ae') {
                        const uaeTerms = ['uae', 'united arab', 'united arabs', 'emirate', 'emirates'];
                        if (uaeTerms.some(term => query.includes(term) || term.includes(query))) return true;
                    }

                    // Saudi Aliases
                    if (code === 'sa') {
                        const saudiTerms = ['saudi', 'saudi arabia', 'ksa'];
                        if (saudiTerms.some(term => query.includes(term) || term.includes(query))) return true;
                    }

                    // US Aliases
                    if (code === 'us') {
                        const usaTerms = ['usa', 'united states', 'america'];
                        if (usaTerms.some(term => query.includes(term) || term.includes(query))) return true;
                    }

                    // UK Aliases
                    if (code === 'gb') {
                        const ukTerms = ['uk', 'united kingdom', 'great britain', 'britain'];
                        if (ukTerms.some(term => query.includes(term) || term.includes(query))) return true;
                    }

                    return false;
                });
        },

        selectCountry(country) {
            this.form.country_choice = country.name;
            this.form.country = country.name;
            if (this.form.same_as_permanent) this.form.home_country = country.name;
            if (country.callingCode) {
                this.form.mobile_country_code = country.callingCode;
                this.form.parent_country_code = country.callingCode;
            }
        },

        selectPermanentCountry(country) {
            this.form.home_country = country.name;
        },

        selectCallingCode(country, target) {
            this.form[target] = country.callingCode;
        },

        normalizeCountry(country) {
            if (country.dial_code !== undefined && country.code !== undefined) {
                const codeUpper = country.code.toUpperCase();
                return {
                    name: country.name,
                    code: codeUpper,
                    callingCode: country.dial_code,
                    flagPng: 'https://flagcdn.com/w80/' + codeUpper.toLowerCase() + '.png',
                };
            }

            const root = country.idd?.root || '';
            const suffixes = country.idd?.suffixes || [];
            let callingCode = '';

            if (root) {
                if (root === '+1') {
                    callingCode = '+1';
                } else {
                    callingCode = root + (suffixes[0] || '');
                }
            }

            return {
                name: country.name?.common || country.name?.official || country.cca2,
                code: country.cca2,
                callingCode,
                flagPng: country.flags?.png || '',
            };
        },

        async loadCountries() {
            this.countriesLoading = true;
            // Non-sovereign territories and excluded countries
            const excludeCodes = new Set([
                'AQ','AS','AW','AX','BL','BM','BQ','BV','CC','CK','CW','CX','EH','FK',
                'FO','GF','GG','GI','GL','GP','GS','GU','HK','HM','IM','IO','JE','KY',
                'MF','MO','MP','MQ','MS','NC','NF','NU','PF','PM','PN','PR','RE','SH',
                'SJ','SX','TC','TF','TK','UM','VG','VI','WF','YT','XK','IL'
            ]);
            try {
                const response = await fetch(this.countryApiUrl);
                if (!response.ok) throw new Error('Country API unavailable');

                const data = await response.json();
                this.countries = data
                    .filter(country => country.independent !== false)
                    .map(country => this.normalizeCountry(country))
                    .filter(country => country.name && country.code && !excludeCodes.has(country.code))
                    .sort((a, b) => a.name.localeCompare(b.name));

                // Sanity check: API should return 150+ countries. If not, use fallback.
                if (this.countries.length < 100) throw new Error('Incomplete country data');
                this.countriesSource = 'api';
            } catch (_) {
                this.countries = this.fallbackCountries;
                this.countriesSource = 'fallback';
            } finally {
                this.countriesLoading = false;
                this.syncCountryChoice();
            }
        },

        formatPhoneNumber(value, countryCode) {
            let digits = value.replace(/\D/g, '');
            let code = (countryCode || '').trim();

            if (code === '+63') {
                if (digits.length > 10) digits = digits.slice(0, 10);
                let formatted = '';
                if (digits.length > 0) formatted += digits.substring(0, 3);
                if (digits.length > 3) formatted += ' ' + digits.substring(3, 6);
                if (digits.length > 6) formatted += ' ' + digits.substring(6, 10);
                return formatted;
            } else if (code === '+966' || code === '+971') {
                if (digits.length > 9) digits = digits.slice(0, 9);
                let formatted = '';
                if (digits.length > 0) formatted += digits.substring(0, 2);
                if (digits.length > 2) formatted += ' ' + digits.substring(2, 5);
                if (digits.length > 5) formatted += ' ' + digits.substring(5, 9);
                return formatted;
            } else if (code === '+974' || code === '+965' || code === '+973') {
                if (digits.length > 8) digits = digits.slice(0, 8);
                let formatted = '';
                if (digits.length > 0) formatted += digits.substring(0, 4);
                if (digits.length > 4) formatted += ' ' + digits.substring(4, 8);
                return formatted;
            } else if (code === '+1') {
                if (digits.length > 10) digits = digits.slice(0, 10);
                let formatted = '';
                if (digits.length > 0) formatted += digits.substring(0, 3);
                if (digits.length > 3) formatted += ' ' + digits.substring(3, 6);
                if (digits.length > 6) formatted += ' ' + digits.substring(6, 10);
                return formatted;
            } else if (code === '+60') {
                if (digits.length > 10) digits = digits.slice(0, 10);
                let formatted = '';
                if (digits.length > 0) formatted += digits.substring(0, 2);
                if (digits.length > 2) formatted += ' ' + digits.substring(2, 5);
                if (digits.length > 5) formatted += ' ' + digits.substring(5, 10);
                return formatted;
            } else {
                if (digits.length > 15) digits = digits.slice(0, 15);
                let formatted = '';
                for (let i = 0; i < digits.length; i += 3) {
                    if (i > 0) formatted += ' ';
                    formatted += digits.substring(i, i + 3);
                }
                return formatted;
            }
        },

        getPhonePlaceholder(countryCode) {
            let code = (countryCode || '').trim();
            if (code === '+63') {
                return '912 345 6789';
            } else if (code === '+966') {
                return '50 123 4567';
            } else if (code === '+971') {
                return '50 123 4567';
            } else if (code === '+974' || code === '+965' || code === '+973') {
                return '3333 4444';
            } else if (code === '+1') {
                return '305 555 0123';
            } else if (code === '+60') {
                return '12 345 6789';
            } else {
                return '912 345 6789';
            }
        },

        syncCountryChoice() {
            if (!this.form.country) {
                this.form.country_choice = '';
                return;
            }

            const match = this.countries.find(country => country.name === this.form.country);
            this.form.country_choice = match ? match.name : 'Other';
        },

        applyCountryChoice() {
            if (this.form.country_choice === 'Other') {
                this.form.country = '';
                return;
            }

            this.form.country = this.form.country_choice;
            const selectedCountry = this.countries.find(country => country.name === this.form.country_choice);
            if (selectedCountry?.callingCode) {
                this.form.mobile_country_code = selectedCountry.callingCode;
                this.form.parent_country_code = selectedCountry.callingCode;
            }
        },

        formatShiftTime(start, end) {
            if (!this.form.timezone) return 'Select timezone first';
            if (this.form.timezone === 'Other') return 'Use Philippine Time as guide';
            const timezone = this.form.timezone;
            const baseDate = '2026-06-01';
            const format = (time) => {
                const date = new Date(baseDate + 'T' + time + ':00+08:00');
                return new Intl.DateTimeFormat('en-US', {
                    hour: 'numeric',
                    minute: '2-digit',
                    hour12: true,
                    timeZone: timezone,
                }).format(date);
            };
            return format(start) + ' ~ ' + format(end);
        },

        detectTimezone() {
            this.detectingTimezone = true;
            this.timezoneMessage = '';

            try {
                const browserTimezone = Intl.DateTimeFormat().resolvedOptions().timeZone;
                const supportedTimezones = [
                    'Asia/Manila',
                    'Asia/Dubai',
                    'Asia/Riyadh',
                    'Asia/Qatar',
                    'Asia/Kuwait',
                    'America/New_York',
                    'America/Los_Angeles',
                ];

                if (supportedTimezones.includes(browserTimezone)) {
                    this.form.timezone = browserTimezone;
                    this.timezoneMessage = 'Timezone detected from your browser.';
                } else {
                    this.form.timezone = 'Other';
                    this.timezoneMessage = 'Timezone set to Others. Class schedules still follow Philippine Time.';
                }
            } catch (_) {
                this.timezoneMessage = 'Timezone detection is unavailable. Please choose manually.';
            }

            if (navigator.geolocation) {
                navigator.geolocation.getCurrentPosition(
                    () => { this.detectingTimezone = false; },
                    () => {
                        this.detectingTimezone = false;
                        if (!this.timezoneMessage) this.timezoneMessage = 'Location permission was not allowed. You can still choose manually.';
                    },
                    { enableHighAccuracy: false, timeout: 5000, maximumAge: 3600000 }
                );
            } else {
                this.detectingTimezone = false;
            }
        },

        // ── Core draft save (localStorage + backend) ──────────────────
        async saveDraft({ force = false, checkDuplicate = false, showStatus = true, fromStep = null } = {}) {
            if (this._savingInflight) return null;
            if (this.isDiscarding || this.draftDiscarded) return;
            if (this._submitted || this.leavingWithoutSaving) return;
            if (!force && !this.hasUserEdited) return;
            if (showStatus) this.draftSaving = true;
            this._savingInflight = true;

            // 1. Always save to localStorage first (instant, no network needed)
            const snapshot = { ...this.form, last_step: this.step };
            try { localStorage.setItem(DRAFT_KEY, JSON.stringify(snapshot)); } catch (_) {}

            // 2. Sync to backend
            try {
                const fd = new FormData();
                fd.append('_token', document.querySelector('meta[name="csrf-token"]').content);
                Object.entries(this.form).forEach(([k, v]) => {
                    if (typeof v !== 'boolean') fd.append(k, v ?? '');
                });
                if (checkDuplicate) {
                    fd.append('check_duplicate', '1');
                }
                if (fromStep !== null) {
                    fd.append('from_step', fromStep);
                }
                fd.append('last_step', this.step);
                fd.append('school_year', '2026-2027');
                if (this.savedApplicantId) fd.append('applicant_id', this.savedApplicantId);
                const uploadedNames = [];
                ['photo_2x2','birth_cert','report_card','marriage_contract','medical_record','affidavit','facebook_screenshot'].forEach(name => {
                    const input = document.querySelector('input[name="' + name + '"]');
                    if (input && input.files.length) {
                        fd.append(name, input.files[0]);
                        uploadedNames.push(name);
                    }
                });
                const response = await fetch('{{ route("enrollment.draft") }}', {
                    method: 'POST',
                    headers: {
                        'Accept': 'application/json, text/plain, */*',
                    },
                    body: fd
                });
                if (response.status === 409) {
                    await response.json().catch(() => ({}));
                    this.error = '';
                    this.showDuplicateModal = true;
                    this._savingInflight = false;
                    if (showStatus) this.draftSaving = false;
                    return { success: false, duplicate: true };
                }
                if (response.status === 429) {
                    // Too many requests — data already in localStorage, silently retry later
                    this._savingInflight = false;
                    if (showStatus) this.draftSaving = false;
                    return null;
                }
                if (!response.ok) throw new Error('Draft save failed');

                const data = await response.json();
                if (data.applicant_id) this.savedApplicantId = data.applicant_id;
                this._savingInflight = false;

                // Dispatch event so upload cards clear their file input and set hasUploaded = true
                uploadedNames.forEach(name => {
                    window.dispatchEvent(new CustomEvent('enrollment:file-uploaded', {
                        detail: { name: name }
                    }));
                });

                if (showStatus) {
                    this.draftSaving = false;
                    this.draftSaved = true;
                    setTimeout(() => { this.draftSaved = false; }, 3000);
                }
                return data;
            } catch (_) { /* network error — localStorage already saved */ }

            this._savingInflight = false;
            if (showStatus) this.draftSaving = false;
            if (force) return { success: false };
            return null;
        },

        async openAffidavitBuilder() {
            this.error = '';
            const data = await this.saveDraft({ force: true });
            const applicantId = data?.applicant_id || this.savedApplicantId || CURRENT_APPLICANT_ID;

            if (!applicantId) {
                this.error = 'Please fill and save the student details first before preparing the affidavit.';
                return;
            }

            window.location.href = AFFIDAVIT_URL_TEMPLATE.replace('__APPLICANT__', encodeURIComponent(applicantId));
        },

        // ── Debounced auto-save (fires 2s after user stops typing) ────
        scheduleDraft() {
            if (this.isDiscarding || this.draftDiscarded) return;
            if (this.leavingWithoutSaving) return;
            if (!this.hasUserEdited) return;
            clearTimeout(this._debounceTimer);
            this._debounceTimer = setTimeout(() => this.saveDraft(), 5000);
        },

        // ── Synchronous save for beforeunload (no await) ──────────────
        saveDraftSync() {
            if (this.isDiscarding || this.draftDiscarded) return;
            if (this._submitted || this.leavingWithoutSaving) return;
            if (!this.hasUserEdited) return;
            const snapshot = { ...this.form, last_step: this.step };
            try { localStorage.setItem(DRAFT_KEY, JSON.stringify(snapshot)); } catch (_) {}
            // Fire-and-forget beacon to backend
            const fd = new FormData();
            fd.append('_token', document.querySelector('meta[name="csrf-token"]').content);
            Object.entries(this.form).forEach(([k, v]) => {
                if (typeof v !== 'boolean') fd.append(k, v ?? '');
            });
            fd.append('last_step', this.step);
            fd.append('school_year', '2026-2027');
            if (this.savedApplicantId) fd.append('applicant_id', this.savedApplicantId);
            navigator.sendBeacon('{{ route("enrollment.draft") }}', fd);
        },

        validateStep() {
            this.error = '';
            if (this.step === 1) {
                if (!this.form.student_type) return 'Please answer if the student is OLD, NEW, or a TRANSFER student.';
                if (!this.form.grade_level) return 'Grade level is required.';
                if (!this.form.learning_mode_main) return 'Please select a learning modality.';
                if (this.form.learning_mode_main === 'Flexible Online Learning' && !this.form.timezone) {
                    return 'Please select your timezone.';
                }
                if (this.form.learning_mode_main === 'Flexible Online Learning' && !this.form.learning_mode_shift) {
                    return 'Please choose an available online learning shift.';
                }
            }
            if (this.step === 2) {
                if (!this.form.last_name.trim()) return 'Last name is required.';
                if (!this.form.first_name.trim()) return 'First name is required.';
                if (!this.form.gender) return 'Gender is required.';
                if (!this.form.date_of_birth) return 'Date of birth is required.';
                if (!this.form.place_of_birth.trim()) return 'Place of birth is required.';
                if (!this.form.religion.trim()) return 'Religion is required.';
                if (this.form.lrn && this.form.lrn.length !== 12) return 'LRN must be exactly 12 digits.';
            }
            if (this.step === 3) {
                if (!this.form.country) return 'Country is required.';
                if (!this.form.street_address.trim()) return 'Street address is required.';
                if (!this.form.mobile_country_code) return 'Mobile country code is required.';
                if (!this.form.mobile_number.trim()) return 'Mobile number is required.';
                if (this.form.mobile_number.replace(/\D/g, '').length < 7) return 'Mobile number must be at least 7 digits.';
            }
            if (this.step === 4) {
                const hasFather = !!(this.form.father_first_name || '').trim() && !!(this.form.father_last_name || '').trim();
                const hasMother = !!(this.form.mother_first_name || '').trim() && !!(this.form.mother_last_name || '').trim();
                if (!hasFather && !hasMother) {
                    return "Please provide either Father's Name (First & Last Name) or Mother's Name (First & Last Name).";
                }
                if (!this.form.parent_country_code) return 'Parent mobile country code is required.';
                if (!this.form.parent_mobile.trim()) return 'Parent mobile number is required.';
                if (this.form.parent_mobile.replace(/\D/g, '').length < 7) return 'Parent mobile number must be at least 7 digits.';
                if (!this.form.facebook || !this.form.facebook.trim()) return 'Facebook Account Link / Name is required.';
                const fbInput = document.querySelector('input[name="facebook_screenshot"]');
                const hasFbScreenshot = this.uploadedFiles.facebook_screenshot || (fbInput && fbInput.files && fbInput.files.length > 0);
                if (!hasFbScreenshot) {
                    return 'Please upload a screenshot of your Facebook Profile.';
                }
            }
            if (this.step === 5) {
                if (!this.form.medical_has_concern) return 'Please answer if the student has any medical concern.';
                if (this.form.medical_has_concern === 'Yes') {
                    const hasMedicalDetails = [
                        this.form.allergies,
                        this.form.current_medications,
                        this.form.health_conditions,
                        this.form.emergency_instructions,
                        this.form.medical_history,
                        this.form.med_explanation,
                        this.form.family_physician,
                        this.form.physician_phone,
                    ].some(value => (value || '').trim().length > 0);
                    if (!hasMedicalDetails) return 'Please add at least one medical detail, or choose No if none.';
                }
                if (!this.form.emergency_name.trim()) return 'Emergency contact name is required.';
                if (!this.form.emergency_relationship.trim()) return 'Emergency contact relationship is required.';
                if (!this.form.emergency_phone.trim()) return 'Emergency contact phone is required.';
            }
            if (this.step === 6) {
                if (this.hasFilePreparationPending()) {
                    return 'Please wait until the selected file finishes preparing.';
                }

                const hasDocument = (name) => {
                    if (this.uploadedFiles[name]) return true;
                    const input = document.querySelector('input[name="' + name + '"]');
                    return !!(input && input.files.length);
                };

                if (!hasDocument('photo_2x2')) {
                    return '1:1 Ratio Picture is required.';
                }

                if (!this.isKinderOrNursery() && this.form.student_type !== 'Old' && !hasDocument('report_card') && !hasDocument('affidavit')) {
                    return 'Upload the Report Card, or use the signed Affidavit / Temporary Proof option if it is not yet available.';
                }
            }
            if (this.step === 7) {
                if (!this.form.payment_method) {
                    return 'Please select a payment method.';
                }
                if (!this.form.amount || parseFloat(this.form.amount) <= 0) {
                    return 'Please enter a valid amount paid.';
                }
                const receiptInput = document.querySelector('input[name="payment_receipt"]');
                const hasReceipt = this.paymentReceiptPreview || (receiptInput && receiptInput.files && receiptInput.files.length > 0);
                const hasRemarks = this.form.remarks && this.form.remarks.trim().length > 0;
                if (!hasReceipt && !hasRemarks) {
                    return 'Please upload your proof of payment / receipt image, or write an explanation in the remarks field if you don\'t have a receipt.';
                }
            }
            if (this.step === 8) {
                const checkbox = document.querySelector('input[name="agreed_final_confirmation"]');
                if (checkbox && !checkbox.checked) {
                    return 'Please check the final confirmation box certifying that all details and payment receipt are authentic.';
                }
            }
            return null;
        },

        isStepTouched(num) {
            return this.visitedSteps.includes(num) || this.step === num || this.completedSteps.includes(num);
        },

        isStepComplete(num) {
            if (num === 1) {
                return !!this.form.student_type
                    && !!this.form.grade_level
                    && !!this.form.learning_mode_main
                    && (this.form.learning_mode_main !== 'Flexible Online Learning' || (!!this.form.timezone && !!this.form.learning_mode_shift));
            }
            if (num === 2) {
                return !!this.form.last_name.trim()
                    && !!this.form.first_name.trim()
                    && !!this.form.gender
                    && !!this.form.date_of_birth
                    && !!this.form.place_of_birth.trim()
                    && !!this.form.religion.trim()
                    && (!this.form.lrn || this.form.lrn.length === 12);
            }
            if (num === 3) {
                return !!this.form.country
                    && !!this.form.street_address.trim()
                    && !!this.form.mobile_country_code
                    && this.form.mobile_number.replace(/\D/g, '').length >= 7;
            }
            if (num === 4) {
                const hasFather = !!(this.form.father_first_name || '').trim() && !!(this.form.father_last_name || '').trim();
                const hasMother = !!(this.form.mother_first_name || '').trim() && !!(this.form.mother_last_name || '').trim();
                return (hasFather || hasMother)
                    && !!this.form.parent_country_code
                    && this.form.parent_mobile.replace(/\D/g, '').length >= 7;
            }
            if (num === 5) {
                const hasMedicalDetails = [
                    this.form.allergies,
                    this.form.current_medications,
                    this.form.health_conditions,
                    this.form.emergency_instructions,
                    this.form.medical_history,
                    this.form.med_explanation,
                    this.form.family_physician,
                    this.form.physician_phone,
                ].some(value => (value || '').trim().length > 0);

                return !!this.form.medical_has_concern
                    && (this.form.medical_has_concern !== 'Yes' || hasMedicalDetails)
                    && !!this.form.emergency_name.trim()
                    && !!this.form.emergency_relationship.trim()
                    && !!this.form.emergency_phone.trim();
            }
            if (num === 6) {
                const hasDocument = (name) => {
                    if (this.uploadedFiles[name]) return true;
                    const input = document.querySelector('input[name="' + name + '"]');
                    return !!(input && input.files.length);
                };

                return hasDocument('photo_2x2')
                    && (this.isKinderOrNursery() || this.form.student_type === 'Old' || hasDocument('report_card') || hasDocument('affidavit'));
            }
            if (num === 7) {
                const receiptInput = document.querySelector('input[name="payment_receipt"]');
                return !!(receiptInput && receiptInput.files && receiptInput.files.length > 0);
            }
            if (num === 8) {
                const checkbox = document.querySelector('input[name="agreed_final_confirmation"]');
                return !!(checkbox && checkbox.checked);
            }
            return false;
        },

        isStepWarning(num) {
            return this.fixStepNumbers().includes(Number(num)) || (this.isStepTouched(num) && !this.isStepComplete(num));
        },

        currentStepLabel() {
            const current = this.steps.find(item => item.num === this.step);
            return current ? current.label : '';
        },

        fixStepNumbers() {
            return Object.keys(this.rejectionFixSteps || {}).map(Number);
        },

        currentFixMessage() {
            const items = this.rejectionFixSteps?.[this.step] || this.rejectionFixSteps?.[String(this.step)] || [];
            if (!items.length) return '';
            return 'Please correct: ' + items.join(', ') + '.';
        },

        canGoToStep(num) {
            if (num <= this.step) return true;
            for (let i = 1; i < num; i++) {
                if (!this.isStepComplete(i)) return false;
            }
            return true;
        },

        async nextStep() {
            if (this.stepSaving) return;
            this.hasAttemptedNext = true;
            const err = this.validateStep();
            if (!this.visitedSteps.includes(this.step)) this.visitedSteps.push(this.step);
            if (err) {
                this.error = err;
                this.showToast(err, 'error');
                this.highlightInvalidFields();
                return;
            }
            if (this.isStepComplete(this.step) && !this.completedSteps.includes(this.step)) this.completedSteps.push(this.step);
            this.error = '';
            this.hasAttemptedNext = false;
            this.pageLoading = true;
            this.stepSaving = true;
            this.draftSaving = false;
            this.draftSaved = false;
            const saveResult = await this.saveDraft({ force: true, checkDuplicate: this.step === 2, showStatus: false, fromStep: this.step });
            if (saveResult && saveResult.duplicate) {
                this.stepSaving = false;
                this.pageLoading = false;
                return;
            }
            setTimeout(() => {
                this.step++;
                if (!this.visitedSteps.includes(this.step)) this.visitedSteps.push(this.step);
                this.pageLoading = false;
                this.stepSaving = false;
                window.scrollTo({ top: 0, behavior: 'smooth' });
            }, 400);
        },

        prevStep() {
            this.error = '';
            this.hasAttemptedNext = false;
            if (!this.visitedSteps.includes(this.step)) this.visitedSteps.push(this.step);
            this.pageLoading = true;
            setTimeout(() => {
                this.step--;
                if (!this.visitedSteps.includes(this.step)) this.visitedSteps.push(this.step);
                this.pageLoading = false;
                window.scrollTo({ top: 0, behavior: 'smooth' });
            }, 400);
        },

        goToStep(num) {
            if (num === this.step) return;
            this.hasAttemptedNext = true;
            if (!this.visitedSteps.includes(this.step)) this.visitedSteps.push(this.step);

            if (!this.canGoToStep(num)) {
                const err = this.validateStep() || 'Please complete the previous steps before going there.';
                this.error = err;
                this.showToast(err, 'error');
                this.highlightInvalidFields();
                return;
            }

            this.error = '';
            this.hasAttemptedNext = false;
            this.pageLoading = true;
            setTimeout(() => {
                this.step = num;
                if (!this.visitedSteps.includes(this.step)) this.visitedSteps.push(this.step);
                this.pageLoading = false;
                window.scrollTo({ top: 0, behavior: 'smooth' });
            }, 400);
        },

        handleSubmit(e) {
            this.hasAttemptedNext = true;
            if (this.loading || this.hasFilePreparationPending() || this.draftSaving) {
                e.preventDefault();
                const err = this.hasFilePreparationPending()
                    ? 'Please wait until the selected file finishes preparing.'
                    : (this.draftSaving ? 'Please wait until the selected file finishes saving.' : '');
                if (err) {
                    this.error = err;
                    this.showToast(err, 'error');
                    this.highlightInvalidFields();
                }
                return;
            }

            const err = this.validateStep();
            if (err) {
                e.preventDefault();
                this.error = err;
                this.showToast(err, 'error');
                this.highlightInvalidFields();
                return;
            }
            this._submitted = true;
            this.clearLocalDraft();
            this.loading = true;
            this.pageLoading = true; // Show skeleton loading immediately on submission!
        },

        highlightInvalidFields() {
            const container = document.querySelector('.enrollment-main');
            if (!container) return;

            let invalidElements = [];

            if (this.step === 1) {
                if (!this.form.student_type) {
                    const grid = container.querySelector('.setup-section:first-child .choice-card-grid');
                    if (grid) invalidElements.push(grid);
                }
                if (!this.form.grade_level) {
                    const select = container.querySelector('select[name="grade_level"]');
                    if (select) invalidElements.push(select);
                }
                if (!this.form.learning_mode_main) {
                    const grids = container.querySelectorAll('.choice-card-grid');
                    if (grids[1]) invalidElements.push(grids[1]);
                }
                if (this.form.learning_mode_main === 'Flexible Online Learning') {
                    if (!this.form.timezone) {
                        const tzSelect = container.querySelector('.timezone-control select');
                        if (tzSelect) invalidElements.push(tzSelect);
                    }
                    if (!this.form.learning_mode_shift) {
                        const shiftGrid = container.querySelector('.shift-card-grid');
                        if (shiftGrid) invalidElements.push(shiftGrid);
                    }
                }
            } else if (this.step === 2) {
                if (!this.form.last_name.trim()) invalidElements.push(container.querySelector('input[name="last_name"]'));
                if (!this.form.first_name.trim()) invalidElements.push(container.querySelector('input[name="first_name"]'));
                if (!this.form.gender) invalidElements.push(container.querySelector('.student-identity-grid .choice-row'));
                if (!this.form.date_of_birth) invalidElements.push(container.querySelector('input[name="date_of_birth"]'));
                if (!this.form.place_of_birth.trim()) invalidElements.push(container.querySelector('input[name="place_of_birth"]'));
                if (!this.form.religion.trim()) invalidElements.push(container.querySelector('input[name="religion"]'));
                if (this.form.lrn && this.form.lrn.length !== 12) invalidElements.push(container.querySelector('input[name="lrn"]'));
            } else if (this.step === 3) {
                if (!this.form.country) invalidElements.push(container.querySelector('.address-country-field .country-combobox-trigger'));
                if (!this.form.street_address.trim()) invalidElements.push(container.querySelector('input[name="street_address"]'));
                if (!this.form.mobile_country_code) invalidElements.push(container.querySelector('.phone-code-trigger'));
                if (!this.form.mobile_number.trim() || this.form.mobile_number.replace(/\D/g, '').length < 7) invalidElements.push(container.querySelector('input[name="mobile_number"]'));
            } else if (this.step === 4) {
                const hasFather = !!(this.form.father_first_name || '').trim() && !!(this.form.father_last_name || '').trim();
                const hasMother = !!(this.form.mother_first_name || '').trim() && !!(this.form.mother_last_name || '').trim();
                if (!hasFather && !hasMother) {
                    invalidElements.push(container.querySelector('input[name="father_last_name"]'));
                    invalidElements.push(container.querySelector('input[name="father_first_name"]'));
                    invalidElements.push(container.querySelector('input[name="mother_last_name"]'));
                    invalidElements.push(container.querySelector('input[name="mother_first_name"]'));
                }
                if (!this.form.parent_country_code) invalidElements.push(container.querySelector('.parent-mobile-field .phone-code-trigger'));
                if (!this.form.parent_mobile.trim() || this.form.parent_mobile.replace(/\D/g, '').length < 7) invalidElements.push(container.querySelector('input[name="parent_mobile"]'));
            } else if (this.step === 5) {
                if (!this.form.medical_has_concern) invalidElements.push(container.querySelector('.medical-choice-row'));
                if (!this.form.emergency_name.trim()) invalidElements.push(container.querySelector('input[name="emergency_name"]'));
                if (!this.form.emergency_relationship.trim()) invalidElements.push(container.querySelector('input[name="emergency_relationship"]'));
                if (!this.form.emergency_phone.trim()) invalidElements.push(container.querySelector('input[name="emergency_phone"]'));
            }

            invalidElements = invalidElements.filter(Boolean);

            invalidElements.forEach(el => {
                el.classList.remove('is-invalid-shake');
                void el.offsetWidth;
                el.classList.add('is-invalid-shake');

                const clearHandler = () => {
                    el.classList.remove('is-invalid-shake');
                    el.removeEventListener('input', clearHandler);
                    el.removeEventListener('change', clearHandler);
                    el.removeEventListener('click', clearHandler);
                };
                el.addEventListener('input', clearHandler);
                el.addEventListener('change', clearHandler);
                el.addEventListener('click', clearHandler);
            });

            if (invalidElements.length > 0) {
                invalidElements[0].scrollIntoView({ behavior: 'smooth', block: 'center' });
            }
        },

        clearLocalDraft() {
            const applicantId = this.savedApplicantId || CURRENT_APPLICANT_ID || 'new';
            if (window.amisEnrollmentDraftCache) {
                window.amisEnrollmentDraftCache.clear(applicantId);
            }

            try { localStorage.removeItem(DRAFT_KEY); } catch (_) {}
            try { localStorage.removeItem(LEGACY_DRAFT_KEY); } catch (_) {}
            try { sessionStorage.removeItem(DRAFT_KEY); } catch (_) {}
            try { sessionStorage.removeItem(LEGACY_DRAFT_KEY); } catch (_) {}
        },

        resetDraftFormState() {
            this.hasUserEdited = false;
            this.draftSaving = false;
            this.draftSaved = false;
            clearTimeout(this._debounceTimer);

            Object.keys(this.form).forEach(key => {
                this.form[key] = typeof this.form[key] === 'boolean' ? false : '';
            });

            const formEl = document.querySelector('[data-no-browser-autofill]');
            if (formEl) formEl.reset();

            this.step = 1;
            this.error = '';
            this.completedSteps = [];
            this.visitedSteps = [1];
            this.uploadedFiles = Object.fromEntries(Object.keys(this.uploadedFiles).map(key => [key, false]));
            Alpine.store('enrollmentGuide').gender = '';
            this.hasUserEdited = false;
        },

        async cancelAndSave() {
            this.showCancelPrompt = false;
            clearTimeout(this._debounceTimer);
            const result = await this.saveDraft({ force: true });
            this.leavingWithoutSaving = true;

            if (!result?.success) {
                this.error = 'We could not save this draft. Please check your connection and try again.';
                return;
            }

            const applicantParam = result.applicant_id ? '?applicant=' + encodeURIComponent(result.applicant_id) : '';
            window.location.href = '{{ route("enrollment.dashboard") }}' + applicantParam;
        },

        saveFileSelection() {
            if (this.isDiscarding || this.draftDiscarded) return;
            if (this.step !== 6) return;
            this.hasUserEdited = true;
            this.saveDraft();
        },

        applySiblingSchedule(checked) {
            this.useSiblingSchedule = checked;
            if (!SIBLING_DATA) return;
            if (checked) {
                if (SIBLING_DATA.learning_mode) {
                    this.form.learning_mode = SIBLING_DATA.learning_mode;
                    this.form.learning_mode_main = SIBLING_DATA.learning_mode.split(' - ')[0];
                    this.form.learning_mode_shift = SIBLING_DATA.learning_mode.includes(' - ') ? SIBLING_DATA.learning_mode.split(' - ')[1] : '';
                }
                if (SIBLING_DATA.timezone) this.form.timezone = SIBLING_DATA.timezone;
            } else {
                this.form.learning_mode = '';
                this.form.learning_mode_main = '';
                this.form.learning_mode_shift = '';
                this.form.timezone = '';
            }
            this.hasUserEdited = true;
        },

        async loadShiftsForGrade() {
            if (!this.form.grade_level) {
                this.gradeShifts = [];
                return;
            }
            try {
                const response = await fetch('/enroll/shifts/' + encodeURIComponent(this.form.grade_level), {
                    headers: { 'Accept': 'application/json, text/plain, */*' },
                });
                if (response.ok) {
                    this.gradeShifts = await response.json();
                }
            } catch (_) {}
        },

        async searchAndAutofillStudent() {
            if (!this.searchStudentNumber || !this.searchDOB) return;
            this.searchingStudent = true;
            this.searchError = '';
            this.searchSuccess = '';
            
            try {
                const response = await fetch('{{ route("enrollment.search-old-student") }}', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'Accept': 'application/json, text/plain, */*',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
                    },
                    body: JSON.stringify({
                        student_number: this.searchStudentNumber,
                        date_of_birth: this.searchDOB
                    })
                });

                const data = await response.json();
                if (!response.ok) {
                    throw new Error(data.message || 'Failed to search student record.');
                }

                // Auto-fill form fields
                Object.keys(data.student).forEach(key => {
                    if (key in this.form) {
                        this.form[key] = data.student[key];
                    }
                });

                // Sync UI elements that rely on form properties
                if (this.form.country) {
                    this.syncCountryChoice();
                }
                if (this.form.grade_level) {
                    this.loadShiftsForGrade();
                }

                this.searchSuccess = 'Student profile auto-filled successfully! Please review the fields in the next steps.';
                this.hasUserEdited = true;
                
                // Trigger auto-save draft
                this.saveDraft();
            } catch (error) {
                this.searchError = error.message;
            } finally {
                this.searchingStudent = false;
            }
        },

        applySiblingParent(checked) {
            this.useSiblingParent = checked;
            if (!SIBLING_DATA) return;
            const fields = [
                'father_last_name', 'father_first_name', 'father_middle_name', 'father_occupation',
                'mother_last_name', 'mother_first_name', 'mother_middle_name', 'mother_occupation',
                'home_state_province', 'home_city', 'home_street_address', 'home_postal_code',
                'parent_country_code', 'parent_mobile', 'parent_email',
                'emergency_name', 'emergency_relationship', 'emergency_phone', 'emergency_instructions',
            ];
            if (checked) {
                fields.forEach(f => { if (SIBLING_DATA[f]) this.form[f] = SIBLING_DATA[f]; });
                // Also parse home_country from SIBLING_DATA.home_address if it's not empty
                if (SIBLING_DATA.home_address) {
                    const parts = SIBLING_DATA.home_address.split(', ');
                    if (parts.length > 0) {
                        this.form.home_country = parts[parts.length - 1].trim();
                    }
                }
            } else {
                fields.forEach(f => { this.form[f] = ''; });
                this.form.home_country = '';
            }
            this.hasUserEdited = true;
        },

        applySiblingAddress(checked) {
            this.useSiblingAddress = checked;
            if (!SIBLING_DATA) return;
            const fields = ['country', 'state_province', 'city', 'street_address', 'postal_code', 'mobile_country_code', 'mobile_number'];
            if (checked) {
                fields.forEach(f => { if (SIBLING_DATA[f]) this.form[f] = SIBLING_DATA[f]; });
                // Also update country_choice for the combobox display
                if (SIBLING_DATA.country) this.form.country_choice = SIBLING_DATA.country;
            } else {
                fields.forEach(f => { this.form[f] = ''; });
                this.form.country_choice = '';
            }
            this.hasUserEdited = true;
        },

        openCancelPrompt() {
            // If nothing was edited during this session, just go back directly
            if (!this.hasUserEdited) {
                window.location.href = '{{ route("enrollment.dashboard") }}';
                return;
            }
            this.showCancelPrompt = true;
        },

        closeCancelPrompt() {
            this.showCancelPrompt = false;
        },

        closeDuplicateModal() {
            this.showDuplicateModal = false;
        },

        leaveWithoutSaving() {
            this.showCancelPrompt = false;
            this.leavingWithoutSaving = true;
            clearTimeout(this._debounceTimer);
            window.location.href = '{{ route("enrollment.dashboard") }}';
        },

        discardUnsavedDraft() {
            this.showCancelPrompt = false;
            this.isDiscarding = true;
            this.draftDiscarded = true;
            this.leavingWithoutSaving = true;
            clearTimeout(this._debounceTimer);
            this.clearLocalDraft();

            // If autosave already created a DB record, delete it via backend
            if (this.savedApplicantId) {
                const fd = new FormData();
                fd.append('_token', document.querySelector('meta[name="csrf-token"]').content);
                fd.append('_method', 'DELETE');
                fd.append('applicant_id', this.savedApplicantId);
                navigator.sendBeacon('{{ route("enrollment.draft.discard") }}', fd);
            }

            this.resetDraftFormState();
            window.location.href = '{{ route("enrollment.dashboard") }}';
        },

        init() {
            setTimeout(() => { this.initialLoading = false; }, 1000);
            this.loadCountries();
            this.disableBrowserAutofill();

            // Auto duplicate if query parameter contains duplicate=1
            const urlParams = new URLSearchParams(window.location.search);
            if (urlParams.get('duplicate') === '1') {
                this.useSiblingSchedule = true;
                this.useSiblingAddress = true;
                this.useSiblingParent = true;

                this.applySiblingSchedule(true);
                this.applySiblingAddress(true);
                this.applySiblingParent(true);
            }

            @if ($applicant && !blank($applicant->home_address))
                if (!this.form.same_as_permanent) {
                    const addrParts = @json($applicant->home_address).split(', ');
                    if (addrParts.length > 0) {
                        this.form.home_country = addrParts[addrParts.length - 1].trim();
                    }
                }
            @endif

            // Auto-detect sibling checkboxes if form data matches sibling (after draft restore)
            if (this.form.grade_level) this.loadShiftsForGrade();
            if (SIBLING_DATA && this.form.learning_mode) {
                if (this.form.learning_mode === SIBLING_DATA.learning_mode) {
                    this.useSiblingSchedule = true;
                }
            }
            if (SIBLING_DATA && this.form.father_last_name && this.form.parent_mobile) {
                if (this.form.father_last_name === SIBLING_DATA.father_last_name
                    && this.form.parent_mobile === SIBLING_DATA.parent_mobile) {
                    this.useSiblingParent = true;
                }
            }
            if (SIBLING_DATA && this.form.street_address && this.form.country) {
                if (this.form.street_address === SIBLING_DATA.street_address
                    && this.form.country === SIBLING_DATA.country) {
                    this.useSiblingAddress = true;
                }
            }

            if (START_FRESH_FORM) this.clearLocalDraft();
            if (SHOULD_CLEAR_DRAFT_CACHE && !START_FRESH_FORM) {
                this.draftDiscarded = true;
                this.clearLocalDraft();
            }
            try { localStorage.removeItem(LEGACY_DRAFT_KEY); } catch (_) {}
            Alpine.store('enrollmentGuide').gender = this.form.gender || '';
            this.$watch('form.gender', value => {
                Alpine.store('enrollmentGuide').gender = value || '';
            });

            // If there are server-side validation errors, pop up the dedicated error modal
            @if ($errors->has('duplicate'))
                this.duplicateErrorMessage = @json($errors->first('duplicate'));
                this.isDuplicateRecord = true;
                this.showDuplicateErrorModal = true;
            @elseif ($errors->any())
                this.duplicateErrorMessage = @json($errors->first());
                this.isDuplicateRecord = false;
                this.showDuplicateErrorModal = true;
            @endif

            // Precise User-Edited Detection: Only input & change events that are user-triggered (trusted)
            const enrollmentFormEl = document.querySelector('[data-no-browser-autofill]');
            if (enrollmentFormEl) {
                const markUserEdited = (e) => {
                    if (!this.isDiscarding && !this.draftDiscarded && e.isTrusted) {
                        this.hasUserEdited = true;
                    }
                };
                enrollmentFormEl.addEventListener('input', markUserEdited, true);
                enrollmentFormEl.addEventListener('change', markUserEdited, true);
            }

            // Initial setup also changes form values, so autosave is gated until real user input/change.
            this.$watch('form', () => this.scheduleDraft(), { deep: true });
            this.$watch('form.mobile_country_code', (code) => {
                this.form.mobile_number = this.formatPhoneNumber(this.form.mobile_number, code);
            });
            this.$watch('form.parent_country_code', (code) => {
                this.form.parent_mobile = this.formatPhoneNumber(this.form.parent_mobile, code);
            });
            // Save on page unload: close tab, close window, F5, navigate away
            const unloadHandler = () => this.saveDraftSync();
            window.addEventListener('beforeunload', unloadHandler);
            // Also save when tab becomes hidden (switch tab, minimize)
            document.addEventListener('visibilitychange', () => {
                if (document.visibilityState === 'hidden') this.saveDraftSync();
            });

            window.addEventListener('enrollment:file-selected', (event) => {
                const name = event.detail?.name;
                if (name) this.uploadedFiles[name] = true;
                this.saveFileSelection();
            });

            window.addEventListener('enrollment:file-processing-started', (event) => {
                const name = event.detail?.name;
                if (name && Object.prototype.hasOwnProperty.call(this.filePreparation, name)) {
                    this.filePreparation[name] = true;
                }
            });

            window.addEventListener('enrollment:file-processing-finished', (event) => {
                const name = event.detail?.name;
                if (name && Object.prototype.hasOwnProperty.call(this.filePreparation, name)) {
                    this.filePreparation[name] = false;
                }
            });

            window.addEventListener('enrollment:file-removed', (event) => {
                const name = event.detail?.name;
                if (name) this.uploadedFiles[name] = false;
                if (name && Object.prototype.hasOwnProperty.call(this.filePreparation, name)) {
                    this.filePreparation[name] = false;
                }
            });

            // Restore from localStorage if backend has no draft yet
            @if (!$applicant)
            if (
                !SHOULD_CLEAR_DRAFT_CACHE
                && !START_FRESH_FORM
                && !this.isDiscarding
                && !(DISCARDED_DRAFT_APPLICANT_ID && String(DISCARDED_DRAFT_APPLICANT_ID) === String(CURRENT_APPLICANT_ID || ''))
            ) {
                try {
                    const saved = localStorage.getItem(DRAFT_KEY);
                    if (saved) {
                        const parsed = JSON.parse(saved);
                        const { last_step, ...fields } = parsed;
                        Object.assign(this.form, fields);
                        if (last_step) this.step = Math.min(Number(last_step), this.totalSteps);
                        if (last_step > 1) {
                            this.visitedSteps = Array.from({ length: Math.min(Number(last_step), this.totalSteps) }, (_, i) => i + 1);
                            this.completedSteps = this.visitedSteps.filter(num => this.isStepComplete(num));
                        }
                    }
                } catch (_) {}
            }
            @endif

            // Apply initial formatting to phone numbers on load (if values exist)
            if (this.form.mobile_number) {
                this.form.mobile_number = this.formatPhoneNumber(this.form.mobile_number, this.form.mobile_country_code);
            }
            if (this.form.parent_mobile) {
                this.form.parent_mobile = this.formatPhoneNumber(this.form.parent_mobile, this.form.parent_country_code);
            }

            document.querySelectorAll('[data-clear-draft-form]').forEach(form => {
                form.addEventListener('submit', () => {
                    this.isDiscarding = true;
                    this.draftDiscarded = true;
                    this.leavingWithoutSaving = true;
                    clearTimeout(this._debounceTimer);
                    this.clearLocalDraft();
                    this.resetDraftFormState();
                });
            });
        },

        disableBrowserAutofill() {
            const form = document.querySelector('[data-no-browser-autofill]');
            if (!form) return;

            form.setAttribute('autocomplete', 'off');

            const fields = form.querySelectorAll('input:not([type="hidden"]):not([type="file"]):not([type="checkbox"]):not([type="radio"]), textarea, select');
            fields.forEach((field, index) => {
                const token = 'amis-no-autofill-' + index;
                field.setAttribute('autocomplete', token);
                field.setAttribute('autocorrect', 'off');
                field.setAttribute('autocapitalize', 'off');
                field.setAttribute('spellcheck', 'false');

                if (field.matches('input[type="text"], input[type="email"], input[type="tel"], input:not([type]), textarea')) {
                    field.readOnly = true;
                    field.dataset.autofillLocked = '1';
                }
            });

            const unlock = (field) => {
                if (!field?.dataset?.autofillLocked) return;
                field.readOnly = false;
                delete field.dataset.autofillLocked;
            };

            form.addEventListener('pointerdown', event => unlock(event.target), true);
            form.addEventListener('focusin', event => unlock(event.target), true);

            setTimeout(() => fields.forEach(unlock), 800);
        }
    }
}
