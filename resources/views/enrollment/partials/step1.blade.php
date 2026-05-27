<div x-transition class="setup-flow" :class="{ 'sibling-schedule-active': useSiblingSchedule }">
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
