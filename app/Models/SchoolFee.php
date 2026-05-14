<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SchoolFee extends Model
{
    protected $fillable = ['school_year', 'grade_level', 'tuition_fee', 'misc_fee', 'books_fee'];

    protected $casts = [
        'tuition_fee' => 'decimal:2',
        'misc_fee'    => 'decimal:2',
        'books_fee'   => 'decimal:2',
    ];

    public static function forGrade(string $gradeLevel, string $schoolYear = '2026-2027'): ?self
    {
        return static::where('grade_level', $gradeLevel)->where('school_year', $schoolYear)->first();
    }
}
