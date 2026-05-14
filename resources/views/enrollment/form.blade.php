<x-guest-layout>
<div x-data="enrollmentForm()" class="enrollment-page">
    <!-- Full Page Loading Skeleton -->
    <div x-show="initialLoading" x-cloak>
        <x-skeleton-enrollment />
    </div>

    <!-- Actual Enrollment Form -->
    <div x-show="!initialLoading" x-cloak x-transition:enter="transition ease-out duration-300" x-transition:enter-start="opacity-0" x-transition:enter-end="opacity-100">
        <!-- Top Header -->
        <div class="enrollment-header">
            <div class="enrollment-header-content">
                <div class="enrollment-header-left">
                    <img src="{{ asset('images/AMIS_Logo.png') }}" alt="AMIS Logo" class="enrollment-header-logo">
                    <div class="enrollment-header-text">
                        <div class="arabic">Ø§Ù„Ù…Ø¯Ø±Ø³Ø© Ø§Ù„Ù…Ù†ÙˆØ±Ø© Ø§Ù„Ø¥Ø³Ù„Ø§Ù…ÙŠØ©</div>
                        <div class="school">Al Munawwara Islamic School</div>
                    </div>
                </div>
                <div class="enrollment-header-right">
                    <h1>Online Pre-Enrollment</h1>
                    <div class="school-year">School Year 2026â€“2027</div>
                </div>
            </div>
        </div>

        <!-- Step Progress Bar -->
        <div class="enrollment-steps-bar">
            <div class="enrollment-steps-container">
                <template x-for="s in steps" :key="s.num">
                    <div
                        :class="{
                            'enrollment-step-item': true,
                            'active': step === s.num,
                            'done': completedSteps.includes(s.num),
                            'disabled': s.num > 1 && !completedSteps.includes(s.num - 1)
                        }"
                        @click="goToStep(s.num)"
                    >
                        <div class="enrollment-step-circle">
                            <template x-if="completedSteps.includes(s.num)">
                                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3"><path d="M20 6L9 17l-5-5"/></svg>
                            </template>
                            <template x-if="!completedSteps.includes(s.num)">
                                <span x-text="s.num"></span>
                            </template>
                        </div>
                        <span class="enrollment-step-label" x-text="s.label"></span>
                    </div>
                </template>
            </div>
        </div>

        <!-- Main Content -->
        <div class="enrollment-main">
            <div class="enrollment-form-container">

            <!-- Form Header -->
            <div class="enrollment-form-header" style="position:relative;">
                <a href="{{ route('enrollment.dashboard') }}" title="Back to Dashboard"
                   style="position:absolute;top:0;right:0;display:flex;align-items:center;justify-content:center;width:32px;height:32px;border-radius:50%;background:#f3f4f6;color:#6b7280;text-decoration:none;border:1px solid #e5e7eb;transition:background 0.15s;"
                   onmouseover="this.style.background='#e5e7eb'" onmouseout="this.style.background='#f3f4f6'">
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5">
                        <line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/>
                    </svg>
                </a>
                <h2 x-text="stepTitles[step - 1]"></h2>
                <p>Step <span x-text="step"></span> of <span x-text="totalSteps"></span></p>
            </div>

            <!-- Server Errors -->
            @if ($errors->any())
                <div class="enrollment-error">
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/>
                    </svg>
                    <span>{{ $errors->first() }}</span>
                </div>
            @endif

            <!-- Client Error -->
            <div x-show="error" x-cloak class="enrollment-error">
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/>
                </svg>
                <span x-text="error"></span>
            </div>

            <!-- Auto-save indicator (fixed bottom-right) -->
            <div x-show="draftSaving || draftSaved" x-cloak x-transition
                 style="position:fixed;bottom:1.5rem;right:1.5rem;z-index:999;display:flex;align-items:center;gap:0.5rem;padding:0.5rem 1rem;background:white;border:1px solid #e5e7eb;border-radius:999px;font-size:0.8125rem;font-weight:500;color:#6b7280;box-shadow:0 2px 8px rgba(0,0,0,0.1);">
                <template x-if="draftSaving">
                    <span style="display:flex;align-items:center;gap:0.4rem;">
                        <svg style="animation:spin 1s linear infinite" width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="#9ca3af" stroke-width="2.5"><path d="M21 12a9 9 0 11-6.219-8.56"/></svg>
                        Saving...
                    </span>
                </template>
                <template x-if="!draftSaving && draftSaved">
                    <span style="display:flex;align-items:center;gap:0.4rem;color:#059669;">
                        <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><polyline points="20 6 9 17 4 12"/></svg>
                        Draft saved
                    </span>
                </template>
            </div>

            <form method="POST" action="{{ route('enrollment.submit') }}" enctype="multipart/form-data" @submit="handleSubmit($event)" novalidate autocomplete="off">
                @csrf
                {{-- Dummy fields to prevent browser autofill --}}
                <input type="text" name="prevent_autofill" style="display:none;" tabindex="-1" autocomplete="off">
                <input type="password" name="prevent_autofill_pwd" style="display:none;" tabindex="-1" autocomplete="new-password">

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
                <!-- STEP 1: Student Information -->
                <div x-show="step === 1" x-transition class="form-grid-3">
                    <!-- Student Type -->
                    <div class="form-group col-3">
                        <label>Student Type <span class="required">*</span></label>
                        <div style="display:flex;gap:0.75rem;">
                            <button type="button" @click="form.student_type = 'New'"
                                :style="form.student_type === 'New'
                                    ? 'flex:1;padding:0.625rem 1rem;border-radius:0.5rem;font-size:0.875rem;font-weight:600;border:2px solid #059669;background:#f0fdf4;color:#065f46;cursor:pointer;font-family:inherit;'
                                    : 'flex:1;padding:0.625rem 1rem;border-radius:0.5rem;font-size:0.875rem;font-weight:600;border:2px solid #e5e7eb;background:white;color:#374151;cursor:pointer;font-family:inherit;'">
                                New Student
                            </button>
                            <button type="button" @click="form.student_type = 'Old'"
                                :style="form.student_type === 'Old'
                                    ? 'flex:1;padding:0.625rem 1rem;border-radius:0.5rem;font-size:0.875rem;font-weight:600;border:2px solid #059669;background:#f0fdf4;color:#065f46;cursor:pointer;font-family:inherit;'
                                    : 'flex:1;padding:0.625rem 1rem;border-radius:0.5rem;font-size:0.875rem;font-weight:600;border:2px solid #e5e7eb;background:white;color:#374151;cursor:pointer;font-family:inherit;'">
                                Old Student
                            </button>
                        </div>
                        <input type="hidden" name="student_type" :value="form.student_type">
                    </div>

                    <!-- Learning Mode -->
                    <div class="form-group col-3">
                        <label>Learning Modalities <span class="required">*</span></label>

                        {{-- 2 options only --}}
                        <div style="display:grid;grid-template-columns:1fr 1fr;gap:0.75rem;margin-bottom:0.75rem;">
                            @php
                                $btnActive = 'padding:0.625rem 1rem;border-radius:0.5rem;font-size:0.875rem;font-weight:600;border:2px solid #059669;background:#f0fdf4;color:#065f46;cursor:pointer;font-family:inherit;';
                                $btnIdle   = 'padding:0.625rem 1rem;border-radius:0.5rem;font-size:0.875rem;font-weight:600;border:2px solid #e5e7eb;background:white;color:#374151;cursor:pointer;font-family:inherit;';
                            @endphp

                            {{-- Face-to-Face — no shift preference --}}
                            <button type="button"
                                @click="form.learning_mode_main = 'Face-to-Face'; form.learning_mode_shift = ''; form.learning_mode = 'Face-to-Face'"
                                :style="form.learning_mode_main === 'Face-to-Face' ? '{{ $btnActive }}' : '{{ $btnIdle }}'">
                                Face-to-Face
                            </button>

                            {{-- Flexible Online Learning — shows shift preference --}}
                            <button type="button"
                                @click="form.learning_mode_main = 'Flexible Online Learning'; form.learning_mode_shift = ''; form.learning_mode = 'Flexible Online Learning'"
                                :style="form.learning_mode_main === 'Flexible Online Learning' ? '{{ $btnActive }}' : '{{ $btnIdle }}'">
                                Flexible Online Learning
                            </button>
                        </div>

                        {{-- Shift preference — only shown when Flexible Online Learning is selected --}}
                        <div x-show="form.learning_mode_main === 'Flexible Online Learning'" x-cloak>
                            <label style="font-size:0.8125rem;font-weight:600;color:#374151;display:block;margin-bottom:0.5rem;">
                                Shift Preference <span class="required">*</span>
                            </label>

                            {{-- Info notice --}}
                            <div style="background:#fffbeb;border:1px solid #fde68a;border-radius:10px;padding:0.875rem 1rem;margin-bottom:0.875rem;display:flex;align-items:flex-start;gap:0.625rem;">
                                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="#d97706" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="flex-shrink:0;margin-top:1px;">
                                    <circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/>
                                </svg>
                                <div>
                                    <div style="font-size:0.8125rem;font-weight:700;color:#92400e;margin-bottom:0.25rem;">⚠️ Important — Online Class Schedule Notice</div>
                                    <div style="font-size:0.8125rem;color:#78350f;line-height:1.6;">
                                        Classes are conducted <strong>online via Microsoft Teams</strong> and follow <strong>Philippine Standard Time (PST)</strong>. If you are enrolling from a <strong>different city, province, or country</strong> (e.g. Manila, Zamboanga, Kuwait, UAE), please choose the shift that works best for your local time. The times below are already converted for common locations.
                                    </div>
                                </div>
                            </div>
                            <div style="display:grid;grid-template-columns:1fr 1fr;gap:0.75rem;">
                                <button type="button"
                                    @click="form.learning_mode_shift = '1st Shift'; form.learning_mode = 'Flexible Online Learning - 1st Shift'"
                                    :style="form.learning_mode_shift === '1st Shift' ? '{{ $btnActive }}text-align:left;padding:0.875rem 1rem;' : '{{ $btnIdle }}text-align:left;padding:0.875rem 1rem;'">
                                    <div style="font-weight:700;font-size:0.9375rem;margin-bottom:0.625rem;">1st Shift</div>
                                    {{-- PH --}}
                                    <div style="display:flex;align-items:center;gap:0.375rem;font-size:0.8125rem;font-weight:700;margin-bottom:0.5rem;">
                                        <img src="https://flagcdn.com/16x12/ph.png" width="16" height="12" alt="PH" style="border-radius:2px;flex-shrink:0;">
                                        12:40 PM ~ 3:00 PM <span style="font-size:0.6875rem;font-weight:500;opacity:0.7;">(PST)</span>
                                    </div>
                                    {{-- Countries --}}
                                    <div style="display:flex;flex-direction:column;gap:0.25rem;">
                                        <div style="display:flex;align-items:center;gap:0.375rem;font-size:0.75rem;">
                                            <img src="https://flagcdn.com/16x12/ae.png" width="16" height="12" alt="UAE" style="border-radius:2px;flex-shrink:0;">
                                            <span style="font-weight:600;">UAE</span> <span style="opacity:0.75;">8:40 AM ~ 11:00 AM</span>
                                        </div>
                                        <div style="display:flex;align-items:center;gap:0.375rem;font-size:0.75rem;">
                                            <img src="https://flagcdn.com/16x12/sa.png" width="16" height="12" alt="KSA" style="border-radius:2px;flex-shrink:0;">
                                            <span style="font-weight:600;">KSA</span> <span style="opacity:0.75;">7:40 AM ~ 10:00 AM</span>
                                        </div>
                                        <div style="display:flex;align-items:center;gap:0.375rem;font-size:0.75rem;">
                                            <img src="https://flagcdn.com/16x12/qa.png" width="16" height="12" alt="Qatar" style="border-radius:2px;flex-shrink:0;">
                                            <span style="font-weight:600;">Qatar</span> <span style="opacity:0.75;">7:40 AM ~ 10:00 AM</span>
                                        </div>
                                        <div style="display:flex;align-items:center;gap:0.375rem;font-size:0.75rem;">
                                            <img src="https://flagcdn.com/16x12/kw.png" width="16" height="12" alt="Kuwait" style="border-radius:2px;flex-shrink:0;">
                                            <span style="font-weight:600;">Kuwait</span> <span style="opacity:0.75;">7:40 AM ~ 10:00 AM</span>
                                        </div>
                                    </div>
                                </button>
                                <button type="button"
                                    @click="form.learning_mode_shift = '2nd Shift'; form.learning_mode = 'Flexible Online Learning - 2nd Shift'"
                                    :style="form.learning_mode_shift === '2nd Shift' ? '{{ $btnActive }}text-align:left;padding:0.875rem 1rem;' : '{{ $btnIdle }}text-align:left;padding:0.875rem 1rem;'">
                                    <div style="font-weight:700;font-size:0.9375rem;margin-bottom:0.625rem;">2nd Shift</div>
                                    {{-- PH --}}
                                    <div style="display:flex;align-items:center;gap:0.375rem;font-size:0.8125rem;font-weight:700;margin-bottom:0.5rem;">
                                        <img src="https://flagcdn.com/16x12/ph.png" width="16" height="12" alt="PH" style="border-radius:2px;flex-shrink:0;">
                                        3:40 PM ~ 6:00 PM <span style="font-size:0.6875rem;font-weight:500;opacity:0.7;">(PST)</span>
                                    </div>
                                    {{-- Countries --}}
                                    <div style="display:flex;flex-direction:column;gap:0.25rem;">
                                        <div style="display:flex;align-items:center;gap:0.375rem;font-size:0.75rem;">
                                            <img src="https://flagcdn.com/16x12/ae.png" width="16" height="12" alt="UAE" style="border-radius:2px;flex-shrink:0;">
                                            <span style="font-weight:600;">UAE</span> <span style="opacity:0.75;">11:40 AM ~ 2:00 PM</span>
                                        </div>
                                        <div style="display:flex;align-items:center;gap:0.375rem;font-size:0.75rem;">
                                            <img src="https://flagcdn.com/16x12/sa.png" width="16" height="12" alt="KSA" style="border-radius:2px;flex-shrink:0;">
                                            <span style="font-weight:600;">KSA</span> <span style="opacity:0.75;">10:40 AM ~ 1:00 PM</span>
                                        </div>
                                        <div style="display:flex;align-items:center;gap:0.375rem;font-size:0.75rem;">
                                            <img src="https://flagcdn.com/16x12/qa.png" width="16" height="12" alt="Qatar" style="border-radius:2px;flex-shrink:0;">
                                            <span style="font-weight:600;">Qatar</span> <span style="opacity:0.75;">10:40 AM ~ 1:00 PM</span>
                                        </div>
                                        <div style="display:flex;align-items:center;gap:0.375rem;font-size:0.75rem;">
                                            <img src="https://flagcdn.com/16x12/kw.png" width="16" height="12" alt="Kuwait" style="border-radius:2px;flex-shrink:0;">
                                            <span style="font-weight:600;">Kuwait</span> <span style="opacity:0.75;">10:40 AM ~ 1:00 PM</span>
                                        </div>
                                    </div>
                                </button>
                            </div>
                        </div>

                        <input type="hidden" name="learning_mode" :value="form.learning_mode">
                    </div>

                    <!-- LRN -->
                    <div class="form-group col-2">
                        <label>LRN (Learner Reference Number)</label>
                        <input type="text" name="lrn" class="plain-input" placeholder="12-digit LRN or leave empty for N/A"
                            x-model="form.lrn" maxlength="12" @input="form.lrn = $event.target.value.replace(/\D/g, '').slice(0, 12)">
                        <span class="text-xs text-gray-500">Will be saved as "NA" if left empty</span>
                    </div>

                    <!-- Grade Level -->
                    <div class="form-group col-1">
                        <label>Grade Level <span class="required">*</span></label>
                        <select name="grade_level" class="select-input" x-model="form.grade_level" required>
                            <option value="">Select</option>
                            @foreach($gradeLevels as $grade)
                                <option value="{{ $grade }}">{{ $grade }}</option>
                            @endforeach
                        </select>
                    </div>

                    <!-- Names -->
                    <x-form-input label="Last Name" name="last_name" required :col="3" x-model="form.last_name" />
                    <x-form-input label="First Name" name="first_name" required :col="3" x-model="form.first_name" />
                    <x-form-input label="Middle Name" name="middle_name" required :col="3" x-model="form.middle_name" />

                    <!-- Gender & DOB -->
                    <div class="form-group col-1">
                        <label>Gender <span class="required">*</span></label>
                        <select name="gender" class="select-input" x-model="form.gender" required>
                            <option value="">Select</option>
                            <option value="Male">Male</option>
                            <option value="Female">Female</option>
                        </select>
                    </div>

                    <div class="form-group col-1">
                        <label>Date of Birth <span class="required">*</span></label>
                        <input type="date" name="date_of_birth" class="plain-input" x-model="form.date_of_birth" required>
                    </div>

                    <x-form-input label="Place of Birth" name="place_of_birth" required :col="1" x-model="form.place_of_birth" />

                    <!-- Religion & Country -->
                    <div class="form-group col-3" style="display:grid;grid-template-columns:1fr 1fr;gap:1rem;">
                        <div>
                            <label>Religion <span class="required">*</span></label>
                            <input type="text" name="religion" class="plain-input" placeholder="Religion" x-model="form.religion" required>
                        </div>
                        <div>
                            <label>Country <span class="required">*</span></label>
                            <select name="country" class="select-input" x-model="form.country" required>
                                <option value="">Select Country</option>
                                <option value="Philippines">Philippines</option>
                                <option value="Saudi Arabia">Saudi Arabia</option>
                                <option value="UAE">UAE</option>
                                <option value="Qatar">Qatar</option>
                                <option value="Kuwait">Kuwait</option>
                                <option value="Bahrain">Bahrain</option>
                                <option value="Malaysia">Malaysia</option>
                                <option value="USA">USA</option>
                                <option value="Other">Other</option>
                            </select>
                        </div>
                    </div>

                    <!-- Address -->
                    <div class="form-group col-3">
                        <label>Address <span class="required">*</span></label>
                        <textarea name="address" class="textarea-input" rows="2" placeholder="Complete address" x-model="form.address" required></textarea>
                    </div>

                    <!-- Contact -->
                    <div class="form-group col-3">
                        <label>Mobile Number <span class="required">*</span></label>
                        <input type="tel" name="mobile_number" class="plain-input" placeholder="9171234567" x-model="form.mobile_number"
                            @input="form.mobile_number = $event.target.value.replace(/\D/g, '')" required>
                        <span style="font-size:0.75rem;color:#6b7280;">Enter mobile number without country code</span>
                    </div>
                </div>

                <!-- STEP 2: Parent Information -->
                <div x-show="step === 2" x-transition class="form-grid-3">
                    <div class="form-divider">Father's Information</div>
                    <x-form-input label="Last Name" name="father_last_name" :col="1" x-model="form.father_last_name" />
                    <x-form-input label="First Name" name="father_first_name" :col="1" x-model="form.father_first_name" />
                    <x-form-input label="Middle Name" name="father_middle_name" :col="1" x-model="form.father_middle_name" />
                    <x-form-input label="Occupation" name="father_occupation" :col="3" x-model="form.father_occupation" />

                    <div class="form-divider">Mother's Information</div>
                    <x-form-input label="Last Name" name="mother_last_name" :col="1" x-model="form.mother_last_name" />
                    <x-form-input label="First Name" name="mother_first_name" :col="1" x-model="form.mother_first_name" />
                    <x-form-input label="Middle Name" name="mother_middle_name" :col="1" x-model="form.mother_middle_name" />
                    <x-form-input label="Occupation" name="mother_occupation" :col="3" x-model="form.mother_occupation" />

                    <div class="form-divider">Contact Information</div>
                    <div class="form-group col-3">
                        <label>Home Address</label>
                        <textarea name="home_address" class="textarea-input" rows="2" placeholder="Home address (if different)" x-model="form.home_address"></textarea>
                    </div>
                    <div class="form-group col-1">
                        <label>Parent Mobile <span class="required">*</span></label>
                        <input type="tel" name="parent_mobile" class="plain-input" placeholder="Parent mobile number" x-model="form.parent_mobile"
                            @input="form.parent_mobile = $event.target.value.replace(/\D/g, '')" required>
                    </div>
                    <div class="form-group col-1">
                        <label>Parent Email</label>
                        <input type="email" name="parent_email" class="plain-input" placeholder="parent@email.com" x-model="form.parent_email">
                    </div>
                </div>

                <!-- STEP 3: Medical & Emergency -->
                <div x-show="step === 3" x-transition class="form-grid-3">
                    <div class="form-divider">Medical Information</div>
                    <div class="form-group col-3">
                        <label>Has the student undergone psychological testing?</label>
                        <select name="psych_testing" class="select-input" x-model="form.psych_testing">
                            <option value="">Select</option>
                            <option value="Yes">Yes</option>
                            <option value="No">No</option>
                        </select>
                    </div>
                    <div class="form-group col-3">
                        <label>Is the student taking any prescription medication?</label>
                        <select name="prescription_med" class="select-input" x-model="form.prescription_med">
                            <option value="">Select</option>
                            <option value="Yes">Yes</option>
                            <option value="No">No</option>
                        </select>
                    </div>
                    <div class="form-group col-3">
                        <label>If yes, please explain</label>
                        <textarea name="med_explanation" class="textarea-input" rows="2" placeholder="Explain if applicable" x-model="form.med_explanation"></textarea>
                    </div>
                    <x-form-input label="Family Physician" name="family_physician" :col="2" x-model="form.family_physician" />
                    <x-form-input label="Physician Phone" name="physician_phone" :col="1" x-model="form.physician_phone" />

                    <div class="form-divider">Emergency Contact</div>
                    <x-form-input label="Contact Name" name="emergency_name" required :col="1" x-model="form.emergency_name" />
                    <x-form-input label="Relationship" name="emergency_relationship" required :col="1" x-model="form.emergency_relationship" />
                    <div class="form-group col-1">
                        <label>Phone <span class="required">*</span></label>
                        <input type="tel" name="emergency_phone" class="plain-input" placeholder="Emergency phone" x-model="form.emergency_phone"
                            @input="form.emergency_phone = $event.target.value.replace(/\D/g, '')" required>
                    </div>
                </div>

                <!-- STEP 4: Agreement -->
                <div x-show="step === 4" x-transition>
                    <div class="enrollment-notice mb-6">
                        <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" class="flex-shrink-0 mt-0.5" style="color: var(--primary)">
                            <path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"/>
                        </svg>
                        <div>
                            <p>By submitting this enrollment form, I certify that all information provided is true and correct. I understand that any false information may result in the denial or cancellation of enrollment.</p>
                        </div>
                    </div>

                    <div class="form-group">
                        <label class="flex items-start gap-3 cursor-pointer">
                            <input type="checkbox" name="agreed_to_terms" x-model="form.agreed_to_terms"
                                class="mt-1 rounded border-gray-300 text-emerald-600 focus:ring-emerald-500">
                            <span class="text-sm text-gray-700">
                                I agree to the terms and conditions of enrollment at Al Munawwara Islamic School. I understand that submission of this form does not guarantee acceptance.
                            </span>
                        </label>
                    </div>
                </div>

                <!-- STEP 5: Documents -->
                <div x-show="step === 5" x-transition>
                    <div x-show="error" x-cloak class="enrollment-error mb-4">
                        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/>
                        </svg>
                        <span x-text="error"></span>
                    </div>

                    <div class="space-y-4">
                        <x-form-file-upload label="2x2 Picture" name="photo_2x2" :required="!$applicant?->photo_2x2_url" :uploaded="$applicant?->photo_2x2_url" />
                        <x-form-file-upload label="Photocopy of Birth Certificate" name="birth_cert" :required="!$applicant?->birth_cert_url" :uploaded="$applicant?->birth_cert_url" />
                        <x-form-file-upload label="Official Transcript / Report Card" name="report_card" :required="!$applicant?->report_card_url" :uploaded="$applicant?->report_card_url" />
                        <x-form-file-upload label="Marriage Contract (Parents) - Optional" name="marriage_contract" :uploaded="$applicant?->marriage_contract_url" />
                        <x-form-file-upload label="Medical Record (if any) - Optional" name="medical_record" :uploaded="$applicant?->medical_record_url" />
                    </div>

                    <div class="form-group mt-6">
                        <label class="flex items-start gap-3 cursor-pointer">
                            <input type="checkbox" name="agreed_to_data_privacy" x-model="form.agreed_to_data_privacy"
                                class="mt-1 rounded border-gray-300 text-emerald-600 focus:ring-emerald-500">
                            <span class="text-sm text-gray-700">
                                I agree to the Data Privacy Policy. I consent to the collection and processing of my personal information for enrollment purposes.
                            </span>
                        </label>
                    </div>
                </div>

                <!-- Hidden fields -->
                <input type="hidden" name="school_year" value="2026-2027">

                </div><!-- end x-show="!pageLoading" -->

                <!-- Form Actions -->
                <div class="form-actions" x-show="!pageLoading" style="display:flex;align-items:center;justify-content:space-between;">
                    {{-- Left: Cancel --}}
                    <button type="button" @click="cancelAndSave()" class="btn-secondary">
                        Cancel
                    </button>
                    {{-- Right: Back + Next/Submit --}}
                    <div style="display:flex;gap:0.75rem;align-items:center;">
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
                            :disabled="!form.agreed_to_data_privacy || loading"
                            :style="(!form.agreed_to_data_privacy || loading) ? 'opacity:0.7;cursor:not-allowed;' : ''">
                            <template x-if="loading">
                                <svg style="animation:spin 0.8s linear infinite;flex-shrink:0;" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path d="M12 2a10 10 0 0 1 10 10"/></svg>
                            </template>
                            <span x-text="loading ? 'Submitting...' : 'Submit Application'"></span>
                        </button>
                    </div>
                </div>
            </form>
            </div>
        </div>

        <!-- Footer -->
        <div class="enrollment-footer">
            &copy; {{ date('Y') }} Al Munawwara Islamic School. All rights reserved.
        </div>
    </div>
</div>

@push('scripts')
<style>
@keyframes spin { to { transform: rotate(360deg); } }
</style>
<script>
const DRAFT_KEY = 'amis_enrollment_draft';

function enrollmentForm() {
    return {
        step: {{ $initialStep }},
        totalSteps: 5,
        loading: false,
        pageLoading: false,
        initialLoading: true,
        draftSaving: false,
        draftSaved: false,
        error: '',
        _debounceTimer: null,
        _submitted: false,
        completedSteps: @json($completedSteps),
        steps: [
            { num: 1, label: 'Student' },
            { num: 2, label: 'Parents' },
            { num: 3, label: 'Medical' },
            { num: 4, label: 'Agreement' },
            { num: 5, label: 'Documents' },
        ],
        stepTitles: ['Student Information', 'Parent Information', 'Medical & Emergency', 'Referral & Agreement', 'Documents to Attach'],
        form: {
            student_type: '{{ old("student_type", $applicant?->student_type ?? "New") }}',
            learning_mode: '{{ old("learning_mode", $applicant?->learning_mode ?? "Face-to-Face") }}',
            learning_mode_main: '{{ old("learning_mode", $applicant?->learning_mode ?? "Face-to-Face") }}'.split(' - ')[0],
            learning_mode_shift: '{{ old("learning_mode", $applicant?->learning_mode ?? "") }}'.includes(' - ') ? '{{ old("learning_mode", $applicant?->learning_mode ?? "") }}'.split(' - ')[1] : '',
            lrn: '{{ old("lrn", ($applicant?->lrn === "NA" ? "" : $applicant?->lrn)) }}',
            grade_level: '{{ old("grade_level", $applicant?->grade_level) }}',
            last_name: '{{ old("last_name", $applicant?->last_name) }}',
            first_name: '{{ old("first_name", $applicant?->first_name) }}',
            middle_name: '{{ old("middle_name", $applicant?->middle_name) }}',
            gender: '{{ old("gender", $applicant?->gender) }}',
            date_of_birth: '{{ old("date_of_birth", $applicant?->date_of_birth?->format("Y-m-d")) }}',
            place_of_birth: '{{ old("place_of_birth", $applicant?->place_of_birth) }}',
            religion: '{{ old("religion", $applicant?->religion) }}',
            country: '{{ old("country", $applicant?->country) }}',
            address: '{{ old("address", $applicant?->address) }}',
            email: '{{ old("email", $applicant?->email) }}',
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
            parent_mobile: '{{ old("parent_mobile", $applicant?->parent_mobile) }}',
            parent_email: '{{ old("parent_email", $applicant?->parent_email) }}',
            psych_testing: '{{ old("psych_testing", $applicant?->psych_testing) }}',
            prescription_med: '{{ old("prescription_med", $applicant?->prescription_med) }}',
            med_explanation: '{{ old("med_explanation", $applicant?->med_explanation) }}',
            family_physician: '{{ old("family_physician", $applicant?->family_physician) }}',
            physician_phone: '{{ old("physician_phone", $applicant?->physician_phone) }}',
            emergency_name: '{{ old("emergency_name", $applicant?->emergency_name) }}',
            emergency_relationship: '{{ old("emergency_relationship", $applicant?->emergency_relationship) }}',
            emergency_phone: '{{ old("emergency_phone", $applicant?->emergency_phone) }}',
            agreed_to_terms: {{ old('agreed_to_terms') ? 'true' : 'false' }},
            agreed_to_data_privacy: {{ old('agreed_to_data_privacy') ? 'true' : 'false' }},
        },

        // ── Core draft save (localStorage + backend) ──────────────────
        async saveDraft() {
            if (this._submitted) return;
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
                ['photo_2x2','birth_cert','report_card','marriage_contract','medical_record'].forEach(name => {
                    const input = document.querySelector('input[name="' + name + '"]');
                    if (input && input.files.length) fd.append(name, input.files[0]);
                });
                await fetch('{{ route("enrollment.draft") }}', { method: 'POST', body: fd });
            } catch (_) { /* network error — localStorage already saved */ }

            this.draftSaving = false;
            this.draftSaved = true;
            setTimeout(() => { this.draftSaved = false; }, 3000);
        },

        // ── Debounced auto-save (fires 2s after user stops typing) ────
        scheduleDraft() {
            clearTimeout(this._debounceTimer);
            this._debounceTimer = setTimeout(() => this.saveDraft(), 2000);
        },

        // ── Synchronous save for beforeunload (no await) ──────────────
        saveDraftSync() {
            if (this._submitted) return;
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
            navigator.sendBeacon('{{ route("enrollment.draft") }}', fd);
        },

        validateStep() {
            this.error = '';
            if (this.step === 1) {
                if (!this.form.student_type) return 'Please select New or Old student.';
                if (!this.form.grade_level) return 'Grade level is required.';
                if (!this.form.last_name.trim()) return 'Last name is required.';
                if (!this.form.first_name.trim()) return 'First name is required.';
                if (!this.form.middle_name.trim()) return 'Middle name is required.';
                if (!this.form.gender) return 'Gender is required.';
                if (!this.form.date_of_birth) return 'Date of birth is required.';
                if (!this.form.place_of_birth.trim()) return 'Place of birth is required.';
                if (!this.form.religion.trim()) return 'Religion is required.';
                if (!this.form.country) return 'Country is required.';
                if (!this.form.address.trim()) return 'Address is required.';
                if (!this.form.mobile_number.trim()) return 'Mobile number is required.';
                if (this.form.mobile_number.length < 7) return 'Mobile number must be at least 7 digits.';
                if (this.form.lrn && this.form.lrn.length !== 12) return 'LRN must be exactly 12 digits.';
            }
            if (this.step === 2) {
                if (!this.form.parent_mobile.trim()) return 'Parent mobile number is required.';
                if (this.form.parent_mobile.length < 7) return 'Parent mobile number must be at least 7 digits.';
            }
            if (this.step === 3) {
                if (!this.form.emergency_name.trim()) return 'Emergency contact name is required.';
                if (!this.form.emergency_relationship.trim()) return 'Emergency contact relationship is required.';
                if (!this.form.emergency_phone.trim()) return 'Emergency contact phone is required.';
            }
            if (this.step === 4) {
                if (!this.form.agreed_to_terms) return 'You must agree to the terms before proceeding.';
            }
            if (this.step === 5) {
                if (!this.form.agreed_to_data_privacy) return 'You must agree to the data privacy policy.';
                const alreadyUploaded = {
                    photo_2x2: {{ $applicant?->photo_2x2_url ? 'true' : 'false' }},
                    birth_cert: {{ $applicant?->birth_cert_url ? 'true' : 'false' }},
                    report_card: {{ $applicant?->report_card_url ? 'true' : 'false' }},
                };
                const labels = { photo_2x2: '2x2 Picture', birth_cert: 'Birth Certificate', report_card: 'Report Card' };
                for (let fname of ['photo_2x2', 'birth_cert', 'report_card']) {
                    if (!alreadyUploaded[fname]) {
                        const input = document.querySelector('input[name="' + fname + '"]');
                        if (input && !input.files.length) return labels[fname] + ' is required.';
                    }
                }
            }
            return null;
        },

        async nextStep() {
            const err = this.validateStep();
            if (err) { this.error = err; return; }
            if (!this.completedSteps.includes(this.step)) this.completedSteps.push(this.step);
            await this.saveDraft();
            this.pageLoading = true;
            setTimeout(() => {
                this.step++;
                this.pageLoading = false;
                window.scrollTo({ top: 0, behavior: 'smooth' });
            }, 400);
        },

        prevStep() {
            this.error = '';
            this.pageLoading = true;
            setTimeout(() => {
                this.step--;
                this.pageLoading = false;
                window.scrollTo({ top: 0, behavior: 'smooth' });
            }, 400);
        },

        goToStep(num) {
            if (num === 1 || this.completedSteps.includes(num - 1)) {
                this.error = '';
                this.pageLoading = true;
                setTimeout(() => {
                    this.step = num;
                    this.pageLoading = false;
                    window.scrollTo({ top: 0, behavior: 'smooth' });
                }, 400);
            }
        },

        handleSubmit(e) {
            const err = this.validateStep();
            if (err) { e.preventDefault(); this.error = err; return; }
            this._submitted = true;
            // Clear localStorage on successful submit
            try { localStorage.removeItem(DRAFT_KEY); } catch (_) {}
            this.loading = true;
        },

        async cancelAndSave() {
            await this.saveDraft();
            window.location.href = '{{ route("enrollment.dashboard") }}';
        },

        init() {
            setTimeout(() => { this.initialLoading = false; }, 1000);

            // If there are server-side validation errors, scroll to top
            @if ($errors->any())
            setTimeout(() => { window.scrollTo({ top: 0, behavior: 'smooth' }); }, 300);
            @endif

            // Watch all form fields — schedule debounced save on any change
            this.$watch('form', () => this.scheduleDraft(), { deep: true });

            // Save on page unload: close tab, close window, F5, navigate away
            const unloadHandler = () => this.saveDraftSync();
            window.addEventListener('beforeunload', unloadHandler);
            // Also save when tab becomes hidden (switch tab, minimize)
            document.addEventListener('visibilitychange', () => {
                if (document.visibilityState === 'hidden') this.saveDraftSync();
            });

            // Restore from localStorage if backend has no draft yet
            @if (!$applicant)
            try {
                const saved = localStorage.getItem(DRAFT_KEY);
                if (saved) {
                    const parsed = JSON.parse(saved);
                    const { last_step, ...fields } = parsed;
                    Object.assign(this.form, fields);
                    if (last_step) this.step = last_step;
                    if (last_step > 1) {
                        this.completedSteps = Array.from({ length: last_step - 1 }, (_, i) => i + 1);
                    }
                }
            } catch (_) {}
            @endif
        }
    }
}
</script>
@endpush

    </div> <!-- End actual enrollment form -->
</div> <!-- End enrollment-page -->
</x-guest-layout>