<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class EnrollmentApplicant extends Model
{
    protected $fillable = [
        'user_id',
        // Student Info
        'student_type',
        'learning_mode',
        'lrn',
        'grade_level',
        'first_name',
        'last_name',
        'middle_name',
        'gender',
        'date_of_birth',
        'place_of_birth',
        'religion',
        'country',
        'address',
        'email',
        'mobile_number',
        // Parent Info
        'father_last_name',
        'father_first_name',
        'father_middle_name',
        'father_occupation',
        'mother_last_name',
        'mother_first_name',
        'mother_middle_name',
        'mother_occupation',
        'home_address',
        'parent_mobile',
        'parent_email',
        // Medical & Emergency
        'psych_testing',
        'prescription_med',
        'med_explanation',
        'family_physician',
        'physician_phone',
        'emergency_name',
        'emergency_relationship',
        'emergency_phone',
        // Documents
        'photo_2x2_url',
        'birth_cert_url',
        'report_card_url',
        'marriage_contract_url',
        'medical_record_url',
        'document_statuses',
        // Meta
        'school_year',
        'status',
        'last_step',
    ];

    protected $casts = [
        'date_of_birth'      => 'date',
        'last_step'          => 'integer',
        'document_statuses'  => 'array',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function payment(): \Illuminate\Database\Eloquent\Relations\HasOne
    {
        return $this->hasOne(Payment::class, 'enrollment_applicant_id');
    }

    public function student(): \Illuminate\Database\Eloquent\Relations\HasOne
    {
        return $this->hasOne(\App\Models\Student::class, 'enrollment_applicant_id');
    }

    public function getFullNameAttribute(): string
    {
        return trim($this->first_name . ' ' . ($this->middle_name ?? '') . ' ' . $this->last_name);
    }

    /**
     * Calculate completion percentage based on filled required fields.
     */
    public function getCompletionPercentageAttribute(): int
    {
        $checks = [
            // Step 1 (weight: 5 fields)
            !empty($this->student_type),
            !empty($this->grade_level),
            !empty($this->first_name),
            !empty($this->last_name),
            !empty($this->gender),
            !empty($this->date_of_birth),
            !empty($this->place_of_birth),
            !empty($this->religion),
            !empty($this->country),
            !empty($this->address),
            !empty($this->mobile_number),
            // Step 2
            !empty($this->parent_mobile),
            // Step 3
            !empty($this->emergency_name),
            !empty($this->emergency_relationship),
            !empty($this->emergency_phone),
            // Step 5 - documents
            !empty($this->photo_2x2_url),
            !empty($this->birth_cert_url),
            !empty($this->report_card_url),
        ];

        $filled = count(array_filter($checks));
        return (int) round(($filled / count($checks)) * 100);
    }
}
