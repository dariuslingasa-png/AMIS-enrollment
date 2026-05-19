<?php

namespace App\Services\Enrollment;

use App\Models\EnrollmentShift;
use App\Models\GradeLevel;
use App\Models\GradeShiftSlot;
use Illuminate\Support\Collection;

class GradeShiftService
{
    private const SCHOOL_YEAR = '2026-2027';

    /**
     * Get all active grade levels (no slots shown in dropdown).
     */
    public function getGradeLevels(): Collection
    {
        return GradeLevel::active()
            ->forYear(self::SCHOOL_YEAR)
            ->orderBy('sort_order')
            ->get();
    }

    /**
     * Get all active shifts (base info only — slots depend on grade).
     */
    public function getShifts(): Collection
    {
        return EnrollmentShift::active()
            ->forYear(self::SCHOOL_YEAR)
            ->orderBy('start_time')
            ->get();
    }

    /**
     * Get shift slots for a specific grade level.
     * Returns shifts with their available slots for that grade.
     */
    public function getShiftsForGrade(string $gradeName): Collection
    {
        $grade = GradeLevel::where('name', $gradeName)
            ->where('school_year', self::SCHOOL_YEAR)
            ->first();

        if (!$grade) {
            return collect();
        }

        return GradeShiftSlot::where('grade_level_id', $grade->id)
            ->where('school_year', self::SCHOOL_YEAR)
            ->where('is_active', true)
            ->with('shift')
            ->get()
            ->map(fn ($slot) => [
                'id' => $slot->shift->id,
                'name' => $slot->shift->name,
                'start_time' => substr($slot->shift->start_time, 0, 5),
                'end_time' => substr($slot->shift->end_time, 0, 5),
                'pht_time_range' => $slot->shift->pht_time_range,
                'capacity' => $slot->capacity,
                'available' => $slot->available_slots,
                'is_full' => $slot->isFull(),
            ]);
    }

    /**
     * Increment enrolled count for a grade+shift when enrollment is finalized.
     */
    public function incrementSlotCount(string $gradeName, string $learningMode): void
    {
        if (!str_starts_with($learningMode, 'Flexible Online Learning')) {
            return;
        }

        $parts = explode(' - ', $learningMode);
        $shiftName = end($parts);

        $grade = GradeLevel::where('name', $gradeName)->where('school_year', self::SCHOOL_YEAR)->first();
        $shift = EnrollmentShift::where('name', $shiftName)->where('school_year', self::SCHOOL_YEAR)->first();

        if ($grade && $shift) {
            GradeShiftSlot::where('grade_level_id', $grade->id)
                ->where('enrollment_shift_id', $shift->id)
                ->where('school_year', self::SCHOOL_YEAR)
                ->increment('enrolled_count');
        }
    }
}
