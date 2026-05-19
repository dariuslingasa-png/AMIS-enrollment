<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class GradeLevel extends Model
{
    protected $fillable = [
        'name',
        'sort_order',
        'capacity',
        'enrolled_count',
        'is_active',
        'school_year',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeForYear($query, string $year = '2026-2027')
    {
        return $query->where('school_year', $year);
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
