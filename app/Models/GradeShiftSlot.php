<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class GradeShiftSlot extends Model
{
    protected $fillable = [
        'grade_level_id',
        'enrollment_shift_id',
        'capacity',
        'enrolled_count',
        'is_active',
        'school_year',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    public function gradeLevel(): BelongsTo
    {
        return $this->belongsTo(GradeLevel::class);
    }

    public function shift(): BelongsTo
    {
        return $this->belongsTo(EnrollmentShift::class, 'enrollment_shift_id');
    }

    public function getAvailableSlotsAttribute(): int
    {
        return max(0, $this->capacity - $this->enrolled_count);
    }

    public function isFull(): bool
    {
        return $this->enrolled_count >= $this->capacity;
    }
}
