<x-guest-layout>
<div x-data="enrollmentForm()" @open-affidavit-builder.window="openAffidavitBuilder()" class="enrollment-page">
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

            @if ($applicant && $applicant->status === 'draft')
                <form method="POST" action="{{ route('enrollment.draft.discard') }}" class="draft-discard-bar" data-clear-draft-form data-confirm-message="Discard changes? This will remove the saved draft for this child.">
                    @csrf
                    @method('DELETE')
                    <input type="hidden" name="applicant_id" value="{{ $applicant->id }}">
                    <div>
                        <strong>Draft loaded</strong>
                        <span>If this form has old or incorrect details, clear it and start fresh.</span>
                    </div>
                    <button type="submit">Start fresh</button>
                </form>
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
                <div x-transition class="setup-flow" :class="{ 'sibling-schedule-active': useSiblingSchedule }">
                    <!-- Sibling schedule reuse -->
                    <template x-if="SIBLING_DATA">
                        <section class="setup-section" :style="useSiblingSchedule
                            ? 'background:#dcfce7;border:2px solid #16a34a;border-radius:12px;padding:1rem 1.25rem;transition:all 0.2s;'
                            : 'background:#f9fafb;border:2px solid #e5e7eb;border-radius:12px;padding:1rem 1.25rem;transition:all 0.2s;'">
                            <label style="display:flex;align-items:flex-start;gap:0.75rem;cursor:pointer;font-size:0.9rem;">
                                <input type="checkbox" x-model="useSiblingSchedule" @change="applySiblingSchedule(useSiblingSchedule)"
                                    style="width:18px;height:18px;accent-color:#16a34a;border-radius:4px;margin-top:2px;flex-shrink:0;">
                                <span>
                                    <strong :style="useSiblingSchedule ? 'color:#15803d;' : 'color:#374151;'">Same schedule as sibling</strong>
                                    <span style="display:block;font-size:0.8rem;color:#4b5563;margin-top:4px;line-height:1.4;">
                                        <template x-if="SIBLING_DATA.sibling_name">
                                            <span style="font-weight:600;" x-text="'From: ' + SIBLING_DATA.sibling_name"></span>
                                        </template>
                                        <template x-if="SIBLING_DATA.learning_mode">
                                            <span> — <span x-text="SIBLING_DATA.learning_mode"></span></span>
                                        </template>
                                        <template x-if="SIBLING_DATA.timezone">
                                            <span> · <span x-text="SIBLING_DATA.timezone"></span></span>
                                        </template>
                                    </span>
                                </span>
                            </label>
                        </section>
                    </template>

                    <!-- Student Type -->
                    <section class="setup-section">
                        <x-form-field-label required>Are you an OLD or NEW AMIS student?</x-form-field-label>
                        <div class="choice-card-grid">
                            <x-enrollment.choice-card
                                label="OLD AMIS Student"
                                description="Previously enrolled at AMIS"
                                icon="school"
                                click="toggleStudentType('Old')"
                                selected="form.student_type === 'Old'"
                            />
                            <x-enrollment.choice-card
                                label="NEW AMIS Student"
                                description="First time enrolling here"
                                icon="spark"
                                click="toggleStudentType('New')"
                                selected="form.student_type === 'New'"
                            />
                        </div>
                        <input type="hidden" name="student_type" :value="form.student_type">
                    </section>

                    <section class="setup-section" x-show="form.student_type === 'Old'" x-cloak>
                        <div class="form-divider">OLD Student Record</div>
                        <div class="returning-coming-soon">
                            <span>Coming Soon</span>
                            <strong>OLD student auto-fill is not available yet.</strong>
                            <p>Please manually fill up the enrollment form again. This is the new AMIS enrollment portal and the old student database is not connected yet.</p>
                        </div>
                    </section>

                    <section class="setup-section">
                        <x-form-field-label required>Grade Level</x-form-field-label>
                        <select name="grade_level" class="select-input" x-model="form.grade_level" @change="loadShiftsForGrade()">
                            <option value="">Select</option>
                            @foreach($gradeLevels as $grade)
                                <option value="{{ $grade->name }}">{{ $grade->name }}</option>
                            @endforeach
                        </select>
                    </section>

                    <!-- Learning Mode -->
                    <section class="setup-section">
                        <x-form-field-label required>Which type do you prefer?</x-form-field-label>

                        <div class="choice-card-grid">
                            <x-enrollment.choice-card
                                label="Face-to-Face"
                                description="Attend onsite classes"
                                icon="building"
                                click="toggleLearningMode('Face-to-Face')"
                                selected="form.learning_mode_main === 'Face-to-Face'"
                            />
                            <x-enrollment.choice-card
                                label="Flexible Online Learning"
                                description="Online classes with shift selection"
                                icon="monitor"
                                click="toggleLearningMode('Flexible Online Learning')"
                                selected="form.learning_mode_main === 'Flexible Online Learning'"
                            />
                        </div>

                        <div x-show="form.learning_mode_main === 'Flexible Online Learning'" x-cloak>
                            <div class="setup-section setup-section-nested timezone-picker">
                                <x-form-field-label required>Your timezone</x-form-field-label>
                                <div class="timezone-control">
                                    <select class="select-input" x-model="form.timezone">
                                        <option value="">Select timezone</option>
                                        <option value="Asia/Manila">Philippines (PHT / UTC+8)</option>
                                        <option value="Asia/Dubai">UAE / Dubai (UTC+4)</option>
                                        <option value="Asia/Riyadh">Saudi Arabia / Riyadh (UTC+3)</option>
                                        <option value="Asia/Qatar">Qatar / Doha (UTC+3)</option>
                                        <option value="Asia/Kuwait">Kuwait (UTC+3)</option>
                                        <option value="America/New_York">Eastern Time - US/Canada</option>
                                        <option value="America/Los_Angeles">Pacific Time - US/Canada</option>
                                        <option value="Other">Others</option>
                                    </select>
                                    <button type="button" class="location-button" @click="detectTimezone()" :disabled="detectingTimezone" aria-label="Detect timezone">
                                        <svg class="location-button-icon" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4" stroke-linecap="round" stroke-linejoin="round"><path d="M12 2v4"/><path d="M12 18v4"/><path d="M2 12h4"/><path d="M18 12h4"/><circle cx="12" cy="12" r="4"/></svg>
                                        <span x-text="detectingTimezone ? 'Detecting...' : 'Detect timezone'"></span>
                                    </button>
                                </div>
                                <p class="field-hint" x-show="timezoneMessage" x-text="timezoneMessage"></p>
                            </div>

                            <div class="setup-section setup-section-nested setup-shift-section">
                                <x-form-field-label required>Which shift do you prefer?</x-form-field-label>

                                <x-enrollment.schedule-notice />
                                <div class="shift-card-grid">
                                    <template x-if="gradeShifts.length === 0 && form.grade_level">
                                        <div style="grid-column:1/-1;text-align:center;padding:1rem;color:#6b7280;font-size:0.85rem;">
                                            Loading shifts...
                                        </div>
                                    </template>
                                    <template x-for="shift in gradeShifts" :key="shift.name">
                                        <button type="button"
                                            :disabled="shift.is_full"
                                            @click="toggleLearningShift(shift.name)"
                                            class="shift-card"
                                            :class="{ 'is-selected': form.learning_mode_shift === shift.name, 'is-full': shift.is_full, 'is-limited': !shift.is_full && shift.available <= 5 }">
                                            <div class="shift-card-topline">
                                                <div class="shift-card-title" x-text="shift.name"></div>
                                                <span class="shift-slot-badge" x-text="shift.is_full ? 'Full' : (shift.available <= 5 ? 'Limited slots' : 'Open slots')"></span>
                                            </div>
                                            <div class="shift-slot-meter">
                                                <span class="shift-slot-meter-fill" :style="'width:' + Math.min(100, Math.round(((shift.capacity - shift.available) / shift.capacity) * 100)) + '%'"></span>
                                            </div>
                                            <div class="shift-slot-copy">
                                                <strong x-text="shift.available"></strong> of <span x-text="shift.capacity"></span> slots available
                                            </div>
                                            <div class="shift-card-primary-time">
                                                <img src="https://flagcdn.com/16x12/ph.png" width="16" height="12" alt="PH" class="flag-icon">
                                                <span x-text="shift.pht_time_range"></span> <span>(PHT / UTC+8)</span>
                                            </div>
                                            <div class="shift-card-local-time">
                                                <span>Local time guide</span>
                                                <strong x-text="formatShiftTime(shift.start_time, shift.end_time)"></strong>
                                            </div>
                                        </button>
                                    </template>
                                    <template x-if="!form.grade_level">
                                        <div style="grid-column:1/-1;text-align:center;padding:1rem;color:#9ca3af;font-size:0.85rem;">
                                            Select a grade level first to see available shifts.
                                        </div>
                                    </template>
                                </div>
                            </div>
                        </div>

                        <input type="hidden" name="learning_mode" :value="form.learning_mode">
                    </section>
                </div>
                </template>

                <!-- STEP 2: Student Information -->
                <template x-if="step === 2">
                <div x-transition class="student-info-flow">
                    <section class="student-section">
                        <div class="form-group">
                            <x-form-field-label optional>LRN (Learner Reference Number)</x-form-field-label>
                            <input type="text" name="lrn" class="plain-input" placeholder="12-digit LRN or leave empty for N/A"
                                x-model="form.lrn" maxlength="12" @input="form.lrn = $event.target.value.replace(/\D/g, '').slice(0, 12)">
                            <span class="field-hint">Will be saved as "NA" if left empty</span>
                        </div>
                    </section>

                    <section class="student-section">
                        <div class="student-name-grid">
                            <x-form-input label="Last Name" name="last_name" required :col="1" x-model="form.last_name" />
                            <x-form-input label="First Name" name="first_name" required :col="1" x-model="form.first_name" />
                            <x-form-input label="Middle Name" name="middle_name" :col="1" x-model="form.middle_name" />
                        </div>
                    </section>

                    <section class="student-section">
                        <div class="student-identity-grid">
                            <div class="form-group">
                                <x-form-field-label required>Gender</x-form-field-label>
                                <select name="gender" class="select-input" x-model="form.gender">
                                    <option value="">Select</option>
                                    <option value="Male">Male</option>
                                    <option value="Female">Female</option>
                                </select>
                            </div>

                            <div class="form-group student-grid-date">
                                <x-form-date label="Date of Birth" name="date_of_birth" required x-model="form.date_of_birth" />
                            </div>
                        </div>
                    </section>

                    <section class="student-section">
                        <div class="student-origin-grid">
                            <div class="form-group">
                                <x-form-field-label required>Place of Birth</x-form-field-label>
                                <input type="text" name="place_of_birth" class="plain-input" placeholder="Place of birth" x-model="form.place_of_birth">
                            </div>

                            <div class="form-group">
                                <x-form-field-label required>Religion</x-form-field-label>
                                <input type="text" name="religion" class="plain-input" placeholder="Religion" x-model="form.religion">
                            </div>

                            <div class="form-group">
                                <x-form-field-label optional>Ethnicity / Ethnolinguistic Group</x-form-field-label>
                                <input type="text" name="ethnicity" class="plain-input" placeholder="e.g. Tagalog, Bisaya, Cebuano, Ilocano" x-model="form.ethnicity">
                                <span class="field-hint">Optional. This may refer to tribe, Indigenous group, Moro community, or ethnolinguistic group.</span>
                            </div>
                        </div>
                    </section>
                </div>
                </template>

                <!-- STEP 3: Address & Contact -->
                <template x-if="step === 3">
                <div x-transition class="address-contact-grid">
                    <!-- Sibling address reuse -->
                    <template x-if="SIBLING_DATA && SIBLING_DATA.street_address">
                        <section class="address-full-field" :style="useSiblingAddress
                            ? 'background:#dcfce7;border:2px solid #16a34a;border-radius:12px;padding:1rem 1.25rem;transition:all 0.2s;margin-bottom:0.5rem;'
                            : 'background:#f9fafb;border:2px solid #e5e7eb;border-radius:12px;padding:1rem 1.25rem;transition:all 0.2s;margin-bottom:0.5rem;'">
                            <label style="display:flex;align-items:flex-start;gap:0.75rem;cursor:pointer;font-size:0.9rem;">
                                <input type="checkbox" x-model="useSiblingAddress" @change="applySiblingAddress(useSiblingAddress)"
                                    style="width:18px;height:18px;accent-color:#16a34a;border-radius:4px;margin-top:2px;flex-shrink:0;">
                                <span>
                                    <strong :style="useSiblingAddress ? 'color:#15803d;' : 'color:#374151;'">Same address as sibling</strong>
                                    <span style="display:block;font-size:0.8rem;color:#4b5563;margin-top:4px;line-height:1.4;">
                                        <template x-if="SIBLING_DATA.sibling_name">
                                            <span style="font-weight:600;" x-text="'From: ' + SIBLING_DATA.sibling_name"></span>
                                        </template>
                                        <span x-text="' — ' + (SIBLING_DATA.street_address || '')"></span>
                                        <template x-if="SIBLING_DATA.country">
                                            <span>, <span x-text="SIBLING_DATA.country"></span></span>
                                        </template>
                                    </span>
                                </span>
                            </label>
                        </section>
                    </template>

                    <div class="form-divider">Present Address</div>
                    <input type="hidden" name="address" :value="compiledPresentAddress">

                    <div class="form-group address-country-field">
                        <x-form-field-label required>Country</x-form-field-label>
                        <div class="country-combobox" x-data="{ open: false, search: '' }" @click.outside="open = false">
                            <button type="button" class="country-combobox-trigger" @click="open = !open">
                                <template x-if="selectedCountry?.flagPng">
                                    <img :src="selectedCountry.flagPng" alt="" class="country-flag-img-static">
                                </template>
                                <span class="country-combobox-value" x-text="selectedCountry ? selectedCountry.name : (countriesLoading ? 'Loading countries...' : 'Search country...')"></span>
                                <svg class="country-combobox-chevron" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2"><path d="m6 9 6 6 6-6"/></svg>
                            </button>
                            <div x-show="open" x-transition class="country-combobox-menu">
                                <input type="text" class="country-combobox-search" placeholder="Search country..." x-model="search" @keydown.escape="open = false">
                                <div class="country-combobox-list">
                                    <template x-for="country in filteredCountries(search)" :key="country.code">
                                        <button type="button" class="country-combobox-option" @click="selectCountry(country); open = false; search = ''">
                                            <img :src="country.flagPng" alt="" class="country-option-flag">
                                            <span class="country-option-name" x-text="country.name"></span>
                                        </button>
                                    </template>
                                </div>
                            </div>
                        </div>
                        <input type="hidden" name="country" :value="form.country">
                    </div>
                    <div class="form-group address-postal-field">
                        <x-form-field-label optional>Postal Code</x-form-field-label>
                        <input type="text" name="postal_code" class="plain-input" placeholder="Postal code" x-model="form.postal_code">
                    </div>
                    <div class="form-group address-full-field">
                        <x-form-field-label required>Complete Address</x-form-field-label>
                        <input type="text" name="street_address" class="plain-input" placeholder="House no., street, building, unit, city/province" x-model="form.street_address">
                    </div>

                    <div class="form-divider address-full-field">Contact Information</div>

                    <div class="form-group address-full-field">
                        <x-form-field-label required>Mobile Number</x-form-field-label>
                        <div class="contact-number-grid">
                            <div class="country-combobox phone-code-combobox" x-data="{ open: false, search: '' }" @click.outside="open = false">
                                <button type="button" class="country-combobox-trigger phone-code-trigger" @click="open = !open">
                                    <template x-if="selectedMobileCodeCountry?.flagPng">
                                        <img :src="selectedMobileCodeCountry.flagPng" alt="" class="country-flag-img-static">
                                    </template>
                                    <span class="country-combobox-value" x-text="form.mobile_country_code || 'Code'"></span>
                                    <svg class="country-combobox-chevron" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2"><path d="m6 9 6 6 6-6"/></svg>
                                </button>
                                <div x-show="open" x-transition class="country-combobox-menu phone-code-menu">
                                    <input type="text" class="country-combobox-search" placeholder="Search code or country..." x-model="search" @keydown.escape="open = false">
                                    <div class="country-combobox-list">
                                        <template x-for="country in filteredCallingCountries(search)" :key="'student-code-' + country.code">
                                            <button type="button" class="country-combobox-option" @click="selectCallingCode(country, 'mobile_country_code'); open = false; search = ''">
                                                <img :src="country.flagPng" alt="" class="country-option-flag">
                                                <span class="country-option-name" x-text="country.name"></span>
                                                <span class="country-option-code" x-text="country.callingCode"></span>
                                            </button>
                                        </template>
                                    </div>
                                </div>
                            </div>
                            <input type="hidden" name="mobile_country_code" :value="form.mobile_country_code">
                            <input type="tel" name="mobile_number" class="plain-input phone-number-input" placeholder="9123456789" x-model="form.mobile_number"
                                @input="form.mobile_number = $event.target.value.replace(/\D/g, '')">
                        </div>
                        <span class="field-hint">Country code is based on country of residence, but you can change it.</span>
                    </div>
                </div>
                </template>

                <!-- STEP 4: Parent / Guardian Information -->
                <template x-if="step === 4">
                <div x-transition class="parent-info-flow">
                    <!-- Sibling parent reuse -->
                    <template x-if="SIBLING_DATA">
                        <section class="parent-section-card" :style="useSiblingParent
                            ? 'background:#dcfce7;border:2px solid #16a34a;border-radius:12px;padding:1rem 1.25rem;margin-bottom:1rem;transition:all 0.2s;'
                            : 'background:#f9fafb;border:2px solid #e5e7eb;border-radius:12px;padding:1rem 1.25rem;margin-bottom:1rem;transition:all 0.2s;'">
                            <label style="display:flex;align-items:flex-start;gap:0.75rem;cursor:pointer;font-size:0.9rem;">
                                <input type="checkbox" x-model="useSiblingParent" @change="applySiblingParent(useSiblingParent)"
                                    style="width:18px;height:18px;accent-color:#16a34a;border-radius:4px;margin-top:2px;flex-shrink:0;">
                                <span>
                                    <strong :style="useSiblingParent ? 'color:#15803d;' : 'color:#374151;'">Same parent info as sibling</strong>
                                    <span style="display:block;font-size:0.8rem;color:#4b5563;margin-top:4px;line-height:1.4;">
                                        <template x-if="SIBLING_DATA.sibling_name">
                                            <span style="font-weight:600;" x-text="'From: ' + SIBLING_DATA.sibling_name"></span>
                                        </template>
                                        <template x-if="SIBLING_DATA.father_first_name || SIBLING_DATA.father_last_name">
                                            <span> · Father: <span x-text="(SIBLING_DATA.father_first_name || '') + ' ' + (SIBLING_DATA.father_last_name || '')"></span></span>
                                        </template>
                                        <template x-if="SIBLING_DATA.mother_first_name || SIBLING_DATA.mother_last_name">
                                            <span> · Mother: <span x-text="(SIBLING_DATA.mother_first_name || '') + ' ' + (SIBLING_DATA.mother_last_name || '')"></span></span>
                                        </template>
                                        <template x-if="SIBLING_DATA.parent_mobile">
                                            <span> · <span x-text="(SIBLING_DATA.parent_country_code || '') + ' ' + SIBLING_DATA.parent_mobile"></span></span>
                                        </template>
                                    </span>
                                </span>
                            </label>
                        </section>
                    </template>

                    <section class="parent-section-card">
                        <div class="form-divider">Father's Information</div>
                        <div class="parent-name-grid">
                            <x-form-input label="Last Name" name="father_last_name" :col="1" x-model="form.father_last_name" />
                            <x-form-input label="First Name" name="father_first_name" :col="1" x-model="form.father_first_name" />
                            <x-form-input label="Middle Name" name="father_middle_name" :col="1" x-model="form.father_middle_name" />
                        </div>
                        <div class="parent-occupation-field">
                            <x-form-input label="Occupation" name="father_occupation" :col="1" x-model="form.father_occupation" />
                        </div>
                    </section>

                    <section class="parent-section-card">
                        <div class="form-divider">Mother's Information</div>
                        <div class="parent-name-grid">
                            <x-form-input label="Last Name" name="mother_last_name" :col="1" x-model="form.mother_last_name" />
                            <x-form-input label="First Name" name="mother_first_name" :col="1" x-model="form.mother_first_name" />
                            <x-form-input label="Middle Name" name="mother_middle_name" :col="1" x-model="form.mother_middle_name" />
                        </div>
                        <div class="parent-occupation-field">
                            <x-form-input label="Occupation" name="mother_occupation" :col="1" x-model="form.mother_occupation" />
                        </div>
                    </section>

                    <section class="parent-section-card">
                        <div class="form-divider">Address</div>
                        <label class="same-address-toggle">
                            <input type="checkbox" x-model="form.same_as_permanent" class="same-address-checkbox">
                            <span class="same-address-checkmark" :class="{ 'is-checked': form.same_as_permanent }" aria-hidden="true">
                                <svg width="12" height="10" viewBox="0 0 12 10" fill="none">
                                    <path d="M1 5.2L4.2 8.2L11 1.2" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"/>
                                </svg>
                            </span>
                            <span>Same as Student Present Address</span>
                        </label>
                        <input type="hidden" name="home_address" :value="compiledHomeAddress">
                        <div class="permanent-address-grid" x-show="!form.same_as_permanent" x-cloak>
                            <div class="form-group address-country-field">
                                <x-form-field-label optional>Country</x-form-field-label>
                                <div class="country-combobox" x-data="{ open: false, search: '' }" @click.outside="open = false">
                                    <button type="button" class="country-combobox-trigger" @click="open = !open">
                                        <template x-if="selectedPermanentCountry?.flagPng">
                                            <img :src="selectedPermanentCountry.flagPng" alt="" class="country-flag-img-static">
                                        </template>
                                        <span class="country-combobox-value" x-text="selectedPermanentCountry ? selectedPermanentCountry.name : 'Search country...'"></span>
                                        <svg class="country-combobox-chevron" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2"><path d="m6 9 6 6 6-6"/></svg>
                                    </button>
                                    <div x-show="open" x-transition class="country-combobox-menu">
                                        <input type="text" class="country-combobox-search" placeholder="Search country..." x-model="search" @keydown.escape="open = false">
                                        <div class="country-combobox-list">
                                            <template x-for="country in filteredCountries(search)" :key="'home-country-' + country.code">
                                                <button type="button" class="country-combobox-option" @click="selectPermanentCountry(country); open = false; search = ''">
                                                    <img :src="country.flagPng" alt="" class="country-option-flag">
                                                    <span class="country-option-name" x-text="country.name"></span>
                                                </button>
                                            </template>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div class="form-group address-postal-field">
                                <x-form-field-label optional>Postal Code</x-form-field-label>
                                <input type="text" name="home_postal_code" class="plain-input" placeholder="Postal code" x-model="form.home_postal_code">
                            </div>
                            <div class="form-group address-full-field">
                                <x-form-field-label optional>Complete Address</x-form-field-label>
                                <input type="text" name="home_street_address" class="plain-input" placeholder="Permanent complete address" x-model="form.home_street_address">
                            </div>
                        </div>
                    </section>

                    <section class="parent-section-card">
                        <div class="form-divider">Contact Information</div>
                        <div class="parent-contact-grid">
                            <div class="form-group parent-mobile-field">
                                <x-form-field-label required>Parent Mobile</x-form-field-label>
                                <div class="contact-number-grid">
                                    <div class="country-combobox phone-code-combobox" x-data="{ open: false, search: '' }" @click.outside="open = false">
                                        <button type="button" class="country-combobox-trigger phone-code-trigger" @click="open = !open">
                                            <template x-if="selectedParentCodeCountry?.flagPng">
                                                <img :src="selectedParentCodeCountry.flagPng" alt="" class="country-flag-img-static">
                                            </template>
                                            <span class="country-combobox-value" x-text="form.parent_country_code || 'Code'"></span>
                                            <svg class="country-combobox-chevron" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2"><path d="m6 9 6 6 6-6"/></svg>
                                        </button>
                                        <div x-show="open" x-transition class="country-combobox-menu phone-code-menu">
                                            <input type="text" class="country-combobox-search" placeholder="Search code or country..." x-model="search" @keydown.escape="open = false">
                                            <div class="country-combobox-list">
                                                <template x-for="country in filteredCallingCountries(search)" :key="'parent-code-' + country.code">
                                                    <button type="button" class="country-combobox-option" @click="selectCallingCode(country, 'parent_country_code'); open = false; search = ''">
                                                        <img :src="country.flagPng" alt="" class="country-option-flag">
                                                        <span class="country-option-name" x-text="country.name"></span>
                                                        <span class="country-option-code" x-text="country.callingCode"></span>
                                                    </button>
                                                </template>
                                            </div>
                                        </div>
                                    </div>
                                    <input type="hidden" name="parent_country_code" :value="form.parent_country_code">
                                    <input type="tel" name="parent_mobile" class="plain-input phone-number-input" placeholder="Parent mobile number" x-model="form.parent_mobile"
                                        @input="form.parent_mobile = $event.target.value.replace(/\D/g, '')">
                                </div>
                            </div>
                            <div class="form-group parent-email-field">
                                <x-form-field-label>Parent Email</x-form-field-label>
                                <input type="email" name="parent_email" class="plain-input" placeholder="parent@email.com" x-model="form.parent_email">
                            </div>
                        </div>
                    </section>
                </div>
                </template>

                <!-- STEP 5: Medical & Emergency -->
                <template x-if="step === 5">
                <div x-transition class="medical-info-flow">
                    <section class="enrollment-step-section">
                        <div class="form-divider">Medical Information</div>
                        <div class="medical-gate-card">
                            <div>
                                <x-form-field-label required>Does the student have any medical concern?</x-form-field-label>
                                <p class="field-hint">Select Yes only if there are allergies, medications, health conditions, special care notes, or physician details to provide.</p>
                            </div>
                            <div class="choice-row medical-choice-row">
                                <button type="button" class="choice-button" :class="{ 'is-selected': form.medical_has_concern === 'Yes' }"
                                    @click="toggleMedicalConcern('Yes')">
                                    Yes
                                </button>
                                <button type="button" class="choice-button" :class="{ 'is-selected': form.medical_has_concern === 'No' }"
                                    @click="toggleMedicalConcern('No')">
                                    No
                                </button>
                            </div>
                            <input type="hidden" name="medical_has_concern" :value="form.medical_has_concern">
                        </div>

                        <div x-show="form.medical_has_concern === 'Yes'" x-cloak class="medical-disclosure-panel">
                            <div class="medical-question-grid">
                                <div class="form-group">
                                    <x-form-field-label>Has the student undergone psychological testing?</x-form-field-label>
                                    <select name="psych_testing" class="select-input" x-model="form.psych_testing">
                                        <option value="">Select</option>
                                        <option value="Yes">Yes</option>
                                        <option value="No">No</option>
                                    </select>
                                </div>
                                <div class="form-group">
                                    <x-form-field-label>Is the student taking any prescription medication?</x-form-field-label>
                                    <select name="prescription_med" class="select-input" x-model="form.prescription_med">
                                        <option value="">Select</option>
                                        <option value="Yes">Yes</option>
                                        <option value="No">No</option>
                                    </select>
                                </div>
                            </div>
                            <div class="medical-text-grid">
                                <div class="form-group">
                                    <x-form-field-label optional>Allergies</x-form-field-label>
                                    <textarea name="allergies" class="textarea-input" rows="2" placeholder="Food, medicine, environment" x-model="form.allergies"></textarea>
                                </div>
                                <div class="form-group">
                                    <x-form-field-label optional>Current Medications</x-form-field-label>
                                    <textarea name="current_medications" class="textarea-input" rows="2" placeholder="Medicine name, dosage, schedule" x-model="form.current_medications"></textarea>
                                </div>
                                <div class="form-group">
                                    <x-form-field-label optional>Health Conditions</x-form-field-label>
                                    <textarea name="health_conditions" class="textarea-input" rows="2" placeholder="Asthma, diabetes, seizures, heart condition" x-model="form.health_conditions"></textarea>
                                </div>
                                <div class="form-group">
                                    <x-form-field-label optional>Emergency Instructions</x-form-field-label>
                                    <textarea name="emergency_instructions" class="textarea-input" rows="2" placeholder="Special instructions during emergency" x-model="form.emergency_instructions"></textarea>
                                </div>
                                <div class="form-group">
                                    <x-form-field-label optional>Other Medical History</x-form-field-label>
                                    <textarea name="medical_history" class="textarea-input" rows="2" placeholder="Past conditions or special care notes" x-model="form.medical_history"></textarea>
                                </div>
                                <div class="form-group">
                                    <x-form-field-label optional>If yes, please explain</x-form-field-label>
                                    <textarea name="med_explanation" class="textarea-input" rows="2" placeholder="Explain psychological testing or prescription medication" x-model="form.med_explanation"></textarea>
                                </div>
                            </div>
                        </div>
                    </section>

                    <section class="enrollment-step-section" x-show="form.medical_has_concern === 'Yes'" x-cloak>
                        <div class="form-divider">Physician Information</div>
                        <div class="physician-grid">
                            <x-form-input label="Family Physician" name="family_physician" :col="1" x-model="form.family_physician" />
                            <x-form-input label="Physician Phone" name="physician_phone" :col="1" x-model="form.physician_phone" />
                        </div>
                    </section>

                    <section class="enrollment-step-section">
                        <div class="form-divider">Emergency Contact</div>
                        <div class="emergency-contact-grid">
                            <x-form-input label="Contact Name" name="emergency_name" required :col="1" x-model="form.emergency_name" />
                            <x-form-input label="Relationship" name="emergency_relationship" required :col="1" x-model="form.emergency_relationship" />
                            <div class="form-group">
                                <x-form-field-label required>Phone</x-form-field-label>
                                <input type="tel" name="emergency_phone" class="plain-input" placeholder="Emergency phone" x-model="form.emergency_phone"
                                    @input="form.emergency_phone = $event.target.value.replace(/\D/g, '')">
                            </div>
                        </div>
                    </section>
                </div>
                </template>

                <!-- STEP 7: Privacy & Agreement -->
                <template x-if="step === 7">
                <div x-transition class="agreement-flow">
                    <section class="enrollment-step-section">
                        <div class="form-divider">Referral Information</div>
                        <div class="referral-privacy-grid">
                            <div class="form-group">
                                <x-form-field-label optional>Referral Source</x-form-field-label>
                                <select name="referral_source" class="select-input" x-model="form.referral_source">
                                    <option value="">Select</option>
                                    <option value="Facebook">Facebook</option>
                                    <option value="Family or Friend">Family or Friend</option>
                                    <option value="Current AMIS Parent">Current AMIS Parent</option>
                                    <option value="School Event">School Event</option>
                                    <option value="Other">Other</option>
                                </select>
                            </div>
                        </div>
                    </section>

                    <section class="enrollment-step-section agreement-checklist">
                        <div class="form-divider">Data Privacy</div>
                        <label class="agreement-check">
                            <input type="checkbox" name="agreed_to_data_privacy" x-model="form.agreed_to_data_privacy"
                                class="agreement-checkbox">
                            <span class="agreement-checkmark" :class="{ 'is-checked': form.agreed_to_data_privacy }" aria-hidden="true">
                                <svg width="12" height="10" viewBox="0 0 12 10" fill="none">
                                    <path d="M1 5.2L4.2 8.2L11 1.2" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"/>
                                </svg>
                            </span>
                            <span>
                                I agree to the Data Privacy Policy. I consent to the collection and processing of my personal information for enrollment purposes.
                            </span>
                        </label>

                        <div class="form-divider">Final Agreement</div>
                        <div class="enrollment-notice">
                            <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" class="flex-shrink-0 mt-0.5 icon-primary">
                                <path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"/>
                            </svg>
                            <div>
                                <p>By submitting this enrollment form, I certify that all information provided is true and correct. I understand that any false information may result in the denial or cancellation of enrollment.</p>
                            </div>
                        </div>

                        <label class="agreement-check">
                            <input type="checkbox" name="agreed_to_terms" x-model="form.agreed_to_terms"
                                class="agreement-checkbox">
                            <span class="agreement-checkmark" :class="{ 'is-checked': form.agreed_to_terms }" aria-hidden="true">
                                <svg width="12" height="10" viewBox="0 0 12 10" fill="none">
                                    <path d="M1 5.2L4.2 8.2L11 1.2" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"/>
                                </svg>
                            </span>
                            <span>
                                I agree to the terms and conditions of enrollment at Al Munawwara Islamic School. I understand that submission of this form does not guarantee acceptance.
                            </span>
                        </label>

                        <label class="agreement-check">
                            <input type="checkbox" name="agreed_to_fee_policy" x-model="form.agreed_to_fee_policy"
                                class="agreement-checkbox">
                            <span class="agreement-checkmark" :class="{ 'is-checked': form.agreed_to_fee_policy }" aria-hidden="true">
                                <svg width="12" height="10" viewBox="0 0 12 10" fill="none">
                                    <path d="M1 5.2L4.2 8.2L11 1.2" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"/>
                                </svg>
                            </span>
                            <span>
                                I understand that the enrollment fee is non-refundable once paid, even if the application is later rejected due to incomplete, invalid, or unqualified documents. I understand that the admin may reject an application even if payment was already made.
                            </span>
                        </label>
                    </section>
                </div>
                </template>

                <!-- STEP 6: Documents -->
                <div x-show="step === 6" x-cloak class="space-y-5">

                    <section class="space-y-5">
                        <div class="grid grid-cols-1 gap-5 lg:grid-cols-2 xl:gap-6">
                            <x-upload-requirement-card
                                title="Recent or Annual 1:1 Ratio"
                                description="Please provide a recent clear student photo for admissions review."
                                name="photo_2x2"
                                :required="true"
                                :uploaded="$applicant?->photo_2x2_url"
                                hint="JPG or PNG only, clear 2x2 photo up to 5MB"
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
                                        ['src' => 'images/2x2-guide/non-hijab-guidelines.png', 'label' => 'Non-hijab guidelines', 'alt' => 'Non-hijab photo guidelines for elementary and high school students'],
                                        ['src' => 'images/2x2-guide/hijab-guidelines.png', 'label' => 'Hijab guidelines', 'alt' => 'Hijab photo guidelines for elementary and high school students'],
                                    ],
                                    'Male' => [
                                        ['src' => 'images/2x2-guide/boys-guidelines.png', 'label' => 'Boys guidelines', 'alt' => 'Boys photo guidelines for elementary and high school students'],
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

                            <div x-show="form.student_type !== 'Old'" x-cloak>
                                <x-upload-requirement-card
                                    title="Official Transcript / Report Card"
                                    description="Choose the option that applies to the student."
                                    name="report_card"
                                    :required="true"
                                    :uploaded="$applicant?->report_card_url"
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

                </div>

                <!-- Hidden fields — always in DOM for final form submission -->
                <input type="hidden" name="student_type" :value="form.student_type">
                <input type="hidden" name="amis_student_id" :value="form.amis_student_id">
                <input type="hidden" name="learning_mode" :value="form.learning_mode">
                <input type="hidden" name="timezone" :value="form.timezone">
                <input type="hidden" name="grade_level" :value="form.grade_level">
                <input type="hidden" name="lrn" :value="form.lrn">
                <input type="hidden" name="last_name" :value="form.last_name">
                <input type="hidden" name="first_name" :value="form.first_name">
                <input type="hidden" name="middle_name" :value="form.middle_name">
                <input type="hidden" name="gender" :value="form.gender">
                <input type="hidden" name="date_of_birth" :value="form.date_of_birth">
                <input type="hidden" name="place_of_birth" :value="form.place_of_birth">
                <input type="hidden" name="religion" :value="form.religion">
                <input type="hidden" name="ethnicity" :value="form.ethnicity">
                <input type="hidden" name="country" :value="form.country">
                <input type="hidden" name="street_address" :value="form.street_address">
                <input type="hidden" name="postal_code" :value="form.postal_code">
                <input type="hidden" name="address" :value="compiledPresentAddress">
                <input type="hidden" name="mobile_country_code" :value="form.mobile_country_code">
                <input type="hidden" name="mobile_number" :value="form.mobile_number">
                <input type="hidden" name="father_last_name" :value="form.father_last_name">
                <input type="hidden" name="father_first_name" :value="form.father_first_name">
                <input type="hidden" name="father_middle_name" :value="form.father_middle_name">
                <input type="hidden" name="father_occupation" :value="form.father_occupation">
                <input type="hidden" name="mother_last_name" :value="form.mother_last_name">
                <input type="hidden" name="mother_first_name" :value="form.mother_first_name">
                <input type="hidden" name="mother_middle_name" :value="form.mother_middle_name">
                <input type="hidden" name="mother_occupation" :value="form.mother_occupation">
                <input type="hidden" name="home_address" :value="compiledHomeAddress">
                <input type="hidden" name="home_street_address" :value="form.home_street_address">
                <input type="hidden" name="home_postal_code" :value="form.home_postal_code">
                <input type="hidden" name="parent_country_code" :value="form.parent_country_code">
                <input type="hidden" name="parent_mobile" :value="form.parent_mobile">
                <input type="hidden" name="parent_email" :value="form.parent_email">
                <input type="hidden" name="referral_source" :value="form.referral_source">
                <input type="hidden" name="medical_has_concern" :value="form.medical_has_concern">
                <input type="hidden" name="psych_testing" :value="form.psych_testing">
                <input type="hidden" name="prescription_med" :value="form.prescription_med">
                <input type="hidden" name="allergies" :value="form.allergies">
                <input type="hidden" name="current_medications" :value="form.current_medications">
                <input type="hidden" name="health_conditions" :value="form.health_conditions">
                <input type="hidden" name="emergency_instructions" :value="form.emergency_instructions">
                <input type="hidden" name="medical_history" :value="form.medical_history">
                <input type="hidden" name="med_explanation" :value="form.med_explanation">
                <input type="hidden" name="family_physician" :value="form.family_physician">
                <input type="hidden" name="physician_phone" :value="form.physician_phone">
                <input type="hidden" name="emergency_name" :value="form.emergency_name">
                <input type="hidden" name="emergency_relationship" :value="form.emergency_relationship">
                <input type="hidden" name="emergency_phone" :value="form.emergency_phone">
                <input type="hidden" name="school_year" value="2026-2027">
                <input type="hidden" name="agreed_to_data_privacy" :value="form.agreed_to_data_privacy ? '1' : ''">
                <input type="hidden" name="agreed_to_terms" :value="form.agreed_to_terms ? '1' : ''">
                <input type="hidden" name="agreed_to_fee_policy" :value="form.agreed_to_fee_policy ? '1' : ''">

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
                        <button type="button" x-show="step < totalSteps" @click="nextStep()" class="btn-primary">
                            Next
                            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <polyline points="9 18 15 12 9 6"/>
                            </svg>
                        </button>
                        <button type="submit" x-show="step === totalSteps" class="btn-primary"
                            :disabled="!form.agreed_to_data_privacy || !form.agreed_to_terms || !form.agreed_to_fee_policy || loading"
                            :class="{ 'is-disabled': !form.agreed_to_data_privacy || !form.agreed_to_terms || !form.agreed_to_fee_policy || loading }">
                            <template x-if="loading">
                                <svg class="spin-icon" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path d="M12 2a10 10 0 0 1 10 10"/></svg>
                            </template>
                            <span x-text="loading ? 'Saving...' : 'Mark Ready for Submission'"></span>
                        </button>
                    </div>
                </div>
            </form>

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

function enrollmentForm() {
    return {
        step: {{ $initialStep }},
        totalSteps: 7,
        loading: false,
        leavingWithoutSaving: false,
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
        detectingTimezone: false,
        timezoneMessage: '',
        error: '',
        rejectionFixSteps: REJECTION_FIX_STEPS,
        rejectionRemarks: REJECTION_REMARKS,
        countriesLoading: true,
        countriesSource: 'api',
        countryApiUrl: 'https://restcountries.com/v3.1/all?fields=name,cca2,idd,flag,flags',
        countries: [],
        _debounceTimer: null,
        _submitted: false,
        uploadedFiles: {
            photo_2x2: {{ $applicant?->photo_2x2_url ? 'true' : 'false' }},
            birth_cert: {{ $applicant?->birth_cert_url ? 'true' : 'false' }},
            report_card: {{ $applicant?->report_card_url ? 'true' : 'false' }},
            marriage_contract: {{ $applicant?->marriage_contract_url ? 'true' : 'false' }},
            medical_record: {{ $applicant?->medical_record_url ? 'true' : 'false' }},
            affidavit: {{ $applicant?->affidavit_url ? 'true' : 'false' }},
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
            { num: 7, label: 'Agreement' },
        ],
        stepTitles: ['Enrollment Setup', 'Student Information', 'Address & Contact', 'Parent / Guardian Information', 'Medical & Emergency', 'Documents', 'Privacy & Agreement'],
        form: {
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
            gender: '{{ old("gender", $applicant?->gender) }}',
            date_of_birth: '{{ old("date_of_birth", $applicant?->date_of_birth?->format("Y-m-d")) }}',
            place_of_birth: '{{ old("place_of_birth", $applicant?->place_of_birth) }}',
            religion: '{{ old("religion", $applicant?->religion) }}',
            ethnicity: '{{ old("ethnicity", $applicant?->ethnicity ?? "") }}',
            country: '{{ old("country", $applicant?->country) }}',
            country_choice: @js(old("country", $applicant?->country) ? old("country", $applicant?->country) : ''),
            ethnicity: '{{ old("ethnicity", $applicant?->ethnicity) }}',
            state_province: '{{ old("state_province", $applicant?->state_province) }}',
            city: '{{ old("city", $applicant?->city) }}',
            street_address: '{{ old("street_address", $applicant?->street_address) }}',
            postal_code: '{{ old("postal_code", $applicant?->postal_code) }}',
            address: '{{ old("address", $applicant?->address) }}',
            email: '{{ old("email", $applicant?->email) }}',
            mobile_country_code: '{{ old("mobile_country_code", $applicant?->mobile_country_code ?? "") }}',
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
            same_as_permanent: {{ old('same_as_permanent', $applicant ? (blank($applicant->home_address) ? '1' : '0') : '0') ? 'true' : 'false' }},
            parent_country_code: '{{ old("parent_country_code", $applicant?->parent_country_code ?? "") }}',
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
                this.form.learning_mode = 'Flexible Online Learning';
                return;
            }

            this.form.learning_mode_shift = shift;
            this.form.learning_mode = 'Flexible Online Learning - ' + shift;
        },

        toggleMedicalConcern(value) {
            if (this.form.medical_has_concern === value) {
                this.form.medical_has_concern = '';
                if (value === 'No') this.clearMedicalFields();
                return;
            }

            this.form.medical_has_concern = value;

            if (value === 'No') {
                this.clearMedicalFields();
            }
        },

        filteredCountries(search) {
            const query = (search || '').trim().toLowerCase();
            return this.countries
                .filter(country => {
                    if (!query) return true;
                    return country.name.toLowerCase().includes(query)
                        || country.code.toLowerCase().includes(query);
                })
                .slice(0, 80);
        },

        filteredCallingCountries(search) {
            const query = (search || '').trim().toLowerCase();
            return this.countriesWithCallingCode
                .filter(country => {
                    if (!query) return true;
                    return country.name.toLowerCase().includes(query)
                        || country.code.toLowerCase().includes(query)
                        || country.callingCode.includes(query);
                })
                .slice(0, 80);
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
            try {
                const response = await fetch(this.countryApiUrl, { cache: 'force-cache' });
                if (!response.ok) throw new Error('Country API unavailable');

                const data = await response.json();
                this.countries = data
                    .map(country => this.normalizeCountry(country))
                    .filter(country => country.name && country.code)
                    .sort((a, b) => a.name.localeCompare(b.name));
                this.countriesSource = 'api';
            } catch (_) {
                this.countries = this.fallbackCountries;
                this.countriesSource = 'fallback';
            } finally {
                this.countriesLoading = false;
                this.syncCountryChoice();
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
        async saveDraft({ force = false } = {}) {
            if (this.isDiscarding || this.draftDiscarded) return;
            if (this._submitted || this.leavingWithoutSaving) return;
            if (!force && !this.hasUserEdited) return;
            this.draftSaving = true;

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
                fd.append('last_step', this.step);
                fd.append('school_year', '2026-2027');
                if (this.savedApplicantId) fd.append('applicant_id', this.savedApplicantId);
                ['photo_2x2','birth_cert','report_card','marriage_contract','medical_record','affidavit'].forEach(name => {
                    const input = document.querySelector('input[name="' + name + '"]');
                    if (input && input.files.length) fd.append(name, input.files[0]);
                });
                const response = await fetch('{{ route("enrollment.draft") }}', { method: 'POST', body: fd });
                if (!response.ok) throw new Error('Draft save failed');

                const data = await response.json();
                if (data.applicant_id) this.savedApplicantId = data.applicant_id;
                this.draftSaving = false;
                this.draftSaved = true;
                setTimeout(() => { this.draftSaved = false; }, 3000);
                return data;
            } catch (_) { /* network error — localStorage already saved */ }

            this.draftSaving = false;
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
            this._debounceTimer = setTimeout(() => this.saveDraft(), 2000);
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
                if (!this.form.student_type) return 'Please answer if the student is OLD or NEW to AMIS.';
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
                if (this.form.mobile_number.length < 7) return 'Mobile number must be at least 7 digits.';
            }
            if (this.step === 4) {
                if (!this.form.parent_country_code) return 'Parent mobile country code is required.';
                if (!this.form.parent_mobile.trim()) return 'Parent mobile number is required.';
                if (this.form.parent_mobile.length < 7) return 'Parent mobile number must be at least 7 digits.';
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
                const hasDocument = (name) => {
                    if (this.uploadedFiles[name]) return true;
                    const input = document.querySelector('input[name="' + name + '"]');
                    return !!(input && input.files.length);
                };

                if (!hasDocument('photo_2x2')) {
                    return '1:1 Ratio Picture is required.';
                }

                if (this.form.student_type !== 'Old' && !hasDocument('report_card') && !hasDocument('affidavit')) {
                    return 'Upload the Report Card, or use the signed Affidavit / Temporary Proof option if it is not yet available.';
                }
            }
            if (this.step === 7) {
                if (!this.form.agreed_to_data_privacy) return 'You must agree to the data privacy policy.';
                if (!this.form.agreed_to_terms) return 'You must agree to the terms before submitting.';
                if (!this.form.agreed_to_fee_policy) return 'You must agree to the non-refundable enrollment fee policy.';
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
                    && this.form.mobile_number.trim().length >= 7;
            }
            if (num === 4) {
                return !!this.form.parent_country_code
                    && this.form.parent_mobile.trim().length >= 7;
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
                    && (this.form.student_type === 'Old' || hasDocument('report_card') || hasDocument('affidavit'));
            }
            if (num === 7) {
                return !!this.form.agreed_to_data_privacy
                    && !!this.form.agreed_to_terms
                    && !!this.form.agreed_to_fee_policy;
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
            const err = this.validateStep();
            if (!this.visitedSteps.includes(this.step)) this.visitedSteps.push(this.step);
            if (err) { this.error = err; return; }
            if (this.isStepComplete(this.step) && !this.completedSteps.includes(this.step)) this.completedSteps.push(this.step);
            await this.saveDraft({ force: true });
            this.pageLoading = true;
            setTimeout(() => {
                this.step++;
                if (!this.visitedSteps.includes(this.step)) this.visitedSteps.push(this.step);
                this.pageLoading = false;
                window.scrollTo({ top: 0, behavior: 'smooth' });
            }, 400);
        },

        prevStep() {
            this.error = '';
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
            if (!this.visitedSteps.includes(this.step)) this.visitedSteps.push(this.step);

            if (!this.canGoToStep(num)) {
                this.error = this.validateStep() || 'Please complete the previous steps before going there.';
                return;
            }

            this.error = '';
            this.pageLoading = true;
            setTimeout(() => {
                this.step = num;
                if (!this.visitedSteps.includes(this.step)) this.visitedSteps.push(this.step);
                this.pageLoading = false;
                window.scrollTo({ top: 0, behavior: 'smooth' });
            }, 400);
        },

        handleSubmit(e) {
            const err = this.validateStep();
            if (err) { e.preventDefault(); this.error = err; return; }
            this._submitted = true;
            this.clearLocalDraft();
            this.loading = true;
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
                    headers: { 'Accept': 'application/json' },
                });
                if (response.ok) {
                    this.gradeShifts = await response.json();
                }
            } catch (_) {}
        },

        applySiblingParent(checked) {
            this.useSiblingParent = checked;
            if (!SIBLING_DATA) return;
            const fields = [
                'father_last_name', 'father_first_name', 'father_middle_name', 'father_occupation',
                'mother_last_name', 'mother_first_name', 'mother_middle_name', 'mother_occupation',
                'home_street_address', 'home_postal_code',
                'parent_country_code', 'parent_mobile', 'parent_email',
            ];
            if (checked) {
                fields.forEach(f => { if (SIBLING_DATA[f]) this.form[f] = SIBLING_DATA[f]; });
            } else {
                fields.forEach(f => { this.form[f] = ''; });
            }
            this.hasUserEdited = true;
        },

        applySiblingAddress(checked) {
            this.useSiblingAddress = checked;
            if (!SIBLING_DATA) return;
            const fields = ['country', 'street_address', 'postal_code', 'mobile_country_code', 'mobile_number'];
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
            // If nothing was edited and no draft exists, just go back
            if (!this.hasUserEdited && !this.savedApplicantId) {
                window.location.href = '{{ route("enrollment.dashboard") }}';
                return;
            }
            this.showCancelPrompt = true;
        },

        closeCancelPrompt() {
            this.showCancelPrompt = false;
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

            // If there are server-side validation errors, scroll to top
            @if ($errors->any())
            setTimeout(() => { window.scrollTo({ top: 0, behavior: 'smooth' }); }, 300);
            @endif

            // Watch all form fields — schedule debounced save on any change
            const enrollmentFormEl = document.querySelector('[data-no-browser-autofill]');
            if (enrollmentFormEl) {
                const markUserEdited = () => {
                    if (!this.isDiscarding && !this.draftDiscarded) this.hasUserEdited = true;
                };
                enrollmentFormEl.addEventListener('pointerdown', markUserEdited, true);
                enrollmentFormEl.addEventListener('keydown', markUserEdited, true);
                enrollmentFormEl.addEventListener('beforeinput', markUserEdited, true);
            }

            // Initial setup also changes form values, so autosave is gated until real user input/change.
            this.$watch('form', () => this.scheduleDraft(), { deep: true });
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

            window.addEventListener('enrollment:file-removed', (event) => {
                const name = event.detail?.name;
                if (name) this.uploadedFiles[name] = false;
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
</script>
@endpush

    </div> <!-- End actual enrollment form -->
</div> <!-- End enrollment-page -->
</x-guest-layout>
