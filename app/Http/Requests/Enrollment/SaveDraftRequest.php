<?php

namespace App\Http\Requests\Enrollment;

use Illuminate\Foundation\Http\FormRequest;

class SaveDraftRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    public function rules(): array
    {
        return [
            'photo_2x2'         => 'nullable|file|mimes:png,jpg,jpeg',
            'birth_cert'        => 'nullable|file|mimes:png,jpg,jpeg,webp,pdf',
            'report_card'       => 'nullable|file|mimes:pdf,jpg,jpeg,png,webp',
            'marriage_contract' => 'nullable|file|mimes:pdf,jpg,jpeg,png',
            'medical_record'    => 'nullable|file|mimes:pdf,jpg,jpeg,png',
            'affidavit'         => 'nullable|file|mimes:pdf,jpg,jpeg,png',
            'payment_receipt'   => 'nullable|file|mimes:png,jpg,jpeg,webp,pdf',
        ];
    }

    public function draftData(): array
    {
        $data = $this->only([
            'student_type', 'amis_student_id', 'learning_mode', 'timezone', 'lrn', 'grade_level',
            'last_name', 'first_name', 'middle_name', 'gender',
            'date_of_birth', 'place_of_birth', 'religion', 'ethnicity', 'country',
            'state_province', 'city', 'street_address', 'postal_code', 'address',
            'email', 'mobile_country_code', 'mobile_number',
            'father_last_name', 'father_first_name', 'father_middle_name', 'father_occupation',
            'mother_last_name', 'mother_first_name', 'mother_middle_name', 'mother_occupation',
            'home_address', 'home_state_province', 'home_city', 'home_street_address', 'home_postal_code',
            'parent_country_code', 'parent_mobile', 'parent_email', 'facebook', 'whatsapp', 'referral_source',
            'psych_testing', 'prescription_med', 'medical_has_concern', 'allergies', 'current_medications',
            'health_conditions', 'emergency_instructions', 'medical_history', 'med_explanation',
            'family_physician', 'physician_phone',
            'emergency_name', 'emergency_relationship', 'emergency_phone',
            'school_year', 'last_step',
        ]);

        $data = array_map(fn ($value) => $value === '' ? null : $value, $data);
        $data['lrn'] = ($data['lrn'] ?? null) ?: null;

        return $data;
    }
}
