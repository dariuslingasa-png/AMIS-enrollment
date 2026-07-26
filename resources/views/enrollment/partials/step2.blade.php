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
            <x-form-input label="Suffix" name="suffix" :col="1" placeholder="e.g. Jr., III" x-model="form.suffix" />
        </div>
    </section>

    <section class="student-section">
        <div class="student-identity-grid">
            <div class="form-group">
                <x-form-field-label required>Gender</x-form-field-label>
                <select name="gender" class="select-input" :class="{ 'is-invalid-field': isFieldInvalid('gender') }" x-model="form.gender">
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
                <input type="text" name="place_of_birth" class="plain-input" :class="{ 'is-invalid-field': isFieldInvalid('place_of_birth') }" placeholder="Place of birth" x-model="form.place_of_birth">
            </div>

            <div class="form-group">
                <x-form-field-label required>Religion</x-form-field-label>
                <input type="text" name="religion" class="plain-input" :class="{ 'is-invalid-field': isFieldInvalid('religion') }" placeholder="Religion" x-model="form.religion">
            </div>

            <div class="form-group">
                <x-form-field-label optional>Ethnicity / Ethnolinguistic Group</x-form-field-label>
                <input type="text" name="ethnicity" class="plain-input" placeholder="e.g. Tagalog, Bisaya, Cebuano, Ilocano" x-model="form.ethnicity">
                <span class="field-hint">Optional. This may refer to tribe, Indigenous group, Moro community, or ethnolinguistic group.</span>
            </div>
        </div>
    </section>
</div>
