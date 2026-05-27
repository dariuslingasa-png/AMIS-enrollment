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
</div>
