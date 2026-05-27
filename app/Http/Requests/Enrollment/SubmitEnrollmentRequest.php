<?php

namespace App\Http\Requests\Enrollment;

use App\Models\EnrollmentApplicant;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;

class SubmitEnrollmentRequest extends FormRequest
{

    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    public function rules(): array
    {
        $applicant = $this->editableApplicant();

        return [
            'student_type'            => 'required|in:New,Old',
            'amis_student_id'         => 'nullable|string|max:20',
            'learning_mode'           => 'required|string',
            'timezone'                => 'nullable|string|max:64',
            'lrn'                     => 'nullable|digits:12',
            'grade_level'             => 'required|string',
            'last_name'               => 'required|string|max:255',
            'first_name'              => 'required|string|max:255',
            'middle_name'             => 'nullable|string|max:255',
            'gender'                  => 'required|in:Male,Female',
            'date_of_birth'           => 'required|date|before:today',
            'place_of_birth'          => 'required|string|max:255',
            'religion'                => 'required|string|max:255',
            'ethnicity'               => 'nullable|string|max:255',
            'country'                 => 'required|string|max:255',
            'state_province'          => 'nullable|string|max:255',
            'city'                    => 'nullable|string|max:255',
            'street_address'          => 'required|string|max:255',
            'postal_code'             => 'nullable|string|max:32',
            'address'                 => 'nullable|string|max:500',
            'email'                   => 'nullable|email|max:255',
            'mobile_country_code'     => 'required|string|max:8',
            'mobile_number'           => 'required|string|min:7|max:20',
            'father_last_name'        => 'nullable|string|max:255',
            'father_first_name'       => 'nullable|string|max:255',
            'father_middle_name'      => 'nullable|string|max:255',
            'father_occupation'       => 'nullable|string|max:255',
            'mother_last_name'        => 'nullable|string|max:255',
            'mother_first_name'       => 'nullable|string|max:255',
            'mother_middle_name'      => 'nullable|string|max:255',
            'mother_occupation'       => 'nullable|string|max:255',
            'home_address'            => 'nullable|string|max:500',
            'home_state_province'     => 'nullable|string|max:255',
            'home_city'               => 'nullable|string|max:255',
            'home_street_address'     => 'nullable|string|max:255',
            'home_postal_code'        => 'nullable|string|max:32',
            'parent_country_code'     => 'required|string|max:8',
            'parent_mobile'           => 'required|string|min:7|max:20',
            'parent_email'            => 'nullable|email|max:255',
            'referral_source'         => 'nullable|string|max:255',
            'psych_testing'           => 'nullable|string|max:255',
            'prescription_med'        => 'nullable|string|max:255',
            'medical_has_concern'     => 'required|in:Yes,No',
            'allergies'               => 'nullable|string|max:1000',
            'current_medications'     => 'nullable|string|max:1000',
            'health_conditions'       => 'nullable|string|max:1000',
            'emergency_instructions'  => 'nullable|string|max:1000',
            'medical_history'         => 'nullable|string|max:1000',
            'med_explanation'         => 'nullable|string|max:1000',
            'family_physician'        => 'nullable|string|max:255',
            'physician_phone'         => 'nullable|string|max:20',
            'emergency_name'          => 'required|string|max:255',
            'emergency_relationship'  => 'required|string|max:255',
            'emergency_phone'         => 'required|string|max:20',
            'agreed_to_terms'         => 'nullable',
            'agreed_to_fee_policy'    => 'nullable',
            'agreed_to_data_privacy'  => 'nullable',
            'school_year'             => 'required|string',
            'photo_2x2'               => ($applicant?->photo_2x2_url ? 'nullable' : 'required') . '|mimes:jpg,jpeg,png|max:5120',
            'birth_cert'              => 'nullable|mimes:jpg,jpeg,png|max:5120',
            'report_card'             => 'nullable|mimes:jpg,jpeg,png|max:5120',
            'marriage_contract'       => 'nullable|mimes:jpg,jpeg,png|max:5120',
            'medical_record'          => 'nullable|mimes:jpg,jpeg,png|max:5120',
            'affidavit'               => 'nullable|mimes:jpg,jpeg,png|max:5120',
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator) {
            $mode = $this->input('learning_mode');
            $applicant = $this->editableApplicant();

            $allowedModes = ['Face-to-Face'];
            if (\Illuminate\Support\Facades\Schema::hasTable('enrollment_shifts')) {
                $shifts = \App\Models\EnrollmentShift::where('is_active', true)
                    ->where('school_year', '2026-2027')
                    ->pluck('name')
                    ->map(fn($name) => "Flexible Online Learning - {$name}")
                    ->all();
                $allowedModes = array_merge($allowedModes, $shifts);
            } else {
                $allowedModes = array_merge($allowedModes, [
                    'Flexible Online Learning - 1st Shift',
                    'Flexible Online Learning - 2nd Shift',
                ]);
            }

            if ($mode && !in_array($mode, $allowedModes, true)) {
                $validator->errors()->add(
                    'learning_mode',
                    'Please choose Face-to-Face or an available Flexible Online Learning shift.'
                );
            }

            if (str_starts_with((string) $mode, 'Flexible Online Learning') && !$this->filled('timezone')) {
                $validator->errors()->add('timezone', 'Please select your timezone.');
            }

            $hasReportCard = $this->hasFile('report_card') || filled($applicant?->report_card_url);
            $hasTemporaryProof = $this->hasFile('affidavit') || filled($applicant?->affidavit_url);
            $gradeLevel = strtolower((string) $this->input('grade_level'));
            $isKinderOrNursery = str_contains($gradeLevel, 'kinder');

            if (!$isKinderOrNursery && $this->input('student_type') !== 'Old' && !$hasReportCard && !$hasTemporaryProof) {
                $validator->errors()->add(
                    'report_card',
                    'Upload the report card, or upload a fully filled out and signed temporary proof if the report card is not yet available.'
                );
            }
        });
    }

    public function enrollmentData(): array
    {
        $data = $this->validated();

        foreach ([
            'agreed_to_terms', 'agreed_to_fee_policy', 'agreed_to_data_privacy',
            'photo_2x2', 'birth_cert', 'report_card', 'marriage_contract', 'medical_record', 'affidavit',
        ] as $field) {
            unset($data[$field]);
        }

        if (($data['learning_mode'] ?? null) === 'Face-to-Face') {
            $data['timezone'] = null;
        }

        $data['lrn'] = ($data['lrn'] ?? null) ?: 'NA';
        $data['email'] = $data['email'] ?? null;
        $data['address'] = ($data['address'] ?? null) ?: trim(implode(', ', array_filter([
            $data['street_address'] ?? null,
            $data['postal_code'] ?? null,
            $data['country'] ?? null,
        ])));

        return $data;
    }

    private function editableApplicant(): ?EnrollmentApplicant
    {
        $user = $this->user();

        if (!$user) {
            return null;
        }

        $routeApplicant = $this->route('applicant');
        $applicantId = $this->input('applicant_id')
            ?? $this->query('applicant')
            ?? ($routeApplicant instanceof EnrollmentApplicant ? $routeApplicant->id : $routeApplicant)
            ?? session('current_enrollment_applicant_id');

        if (!$applicantId) {
            return null;
        }

        $applicant = $user->enrollmentApplicants()
            ->whereKey($applicantId)
            ->first();

        if (!$applicant || !in_array($applicant->status, ['draft', 'ready_for_submission', 'rejected'], true)) {
            return null;
        }

        return $applicant;
    }
}
