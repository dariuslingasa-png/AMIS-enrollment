<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Student extends Model
{
    protected static function booted()
    {
        static::updated(function ($student) {
            if ($student->wasChanged('grade_level') && $student->applicant) {
                $applicant = $student->applicant;
                $applicant->grade_level = $student->grade_level;
                $applicant->saveQuietly();
            }
            if ($student->wasChanged('grade_level') && $student->account) {
                $account = $student->account;
                $account->grade_level = $student->grade_level;
                $account->saveQuietly();
            }
        });
    }

    protected $fillable = [
        'user_id',
        'enrollment_applicant_id',
        'student_number',
        'school_email',
        'temp_password',
        'grade_level',
        'school_year',
        'section',
        'student_id_url',
        'credentials_sent_at',
    ];

    protected $casts = [
        'credentials_sent_at' => 'datetime',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function applicant(): BelongsTo
    {
        return $this->belongsTo(EnrollmentApplicant::class, 'enrollment_applicant_id');
    }
}
