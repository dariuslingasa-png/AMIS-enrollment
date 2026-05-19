# Grade Levels & Shifts from Database + Live Timezone

## Goal
- Remove hardcoded grade levels and shifts
- Store them in database tables (admin can update via SQL or admin panel later)
- Live timezone detection → auto-convert shift times to user's local time
- Show available slots per grade level

---

## Database Tables

### `grade_levels`
| Column | Type | Description |
|--------|------|-------------|
| id | bigint | PK |
| name | string | "Kinder 1", "Grade 1", etc. |
| sort_order | int | Display order |
| capacity | int | Max students per grade |
| enrolled_count | int | Current enrolled (updated by admin/system) |
| is_active | boolean | Show/hide in enrollment form |
| school_year | string | "2026-2027" |
| timestamps | | |

### `enrollment_shifts`
| Column | Type | Description |
|--------|------|-------------|
| id | bigint | PK |
| name | string | "1st Shift", "2nd Shift" |
| start_time | time | PHT start (e.g. 12:40) |
| end_time | time | PHT end (e.g. 15:00) |
| capacity | int | Max students |
| enrolled_count | int | Current enrolled |
| is_active | boolean | Show/hide |
| school_year | string | "2026-2027" |
| timestamps | | |

---

## Files to Create/Modify

### New Files
| File | Purpose |
|------|---------|
| `database/migrations/2026_05_18_040000_create_grade_levels_table.php` | Migration |
| `database/migrations/2026_05_18_040001_create_enrollment_shifts_table.php` | Migration |
| `app/Models/GradeLevel.php` | Model |
| `app/Models/EnrollmentShift.php` | Model |
| `database/seeders/GradeLevelSeeder.php` | Seed default grades |
| `database/seeders/EnrollmentShiftSeeder.php` | Seed default shifts |
| `app/Services/Enrollment/GradeShiftService.php` | Service to fetch grades/shifts with availability |

### Modified Files
| File | Change |
|------|--------|
| `app/Http/Controllers/EnrollmentController.php` | Pass grades/shifts from DB to view |
| `resources/views/enrollment/form.blade.php` | Use dynamic grades/shifts instead of hardcoded |
| `resources/views/components/enrollment/shift-card.blade.php` | Accept dynamic data + live timezone conversion |

---

## Timezone Conversion Logic

### How it works:
1. User selects timezone (or auto-detected via JS `Intl.DateTimeFormat().resolvedOptions().timeZone`)
2. Shift times are stored in PHT (UTC+8) in the database
3. Frontend JS converts PHT times to user's selected timezone in real-time
4. When timezone changes, shift cards update automatically

### Conversion formula (JS):
```javascript
// PHT is UTC+8
// Convert PHT time to user's timezone
function convertPHTToLocal(phtTime, userTimezone) {
    // Create a date in PHT
    const today = new Date().toISOString().split('T')[0];
    const phtDate = new Date(`${today}T${phtTime}:00+08:00`);
    
    // Format in user's timezone
    return phtDate.toLocaleTimeString('en-US', {
        timeZone: userTimezone,
        hour: 'numeric',
        minute: '2-digit',
        hour12: true
    });
}
```

---

## Grade Level Display

### Current (hardcoded):
```php
$gradeLevels = ['Kinder 1', 'Kinder 2', 'Grade 1', ...];
```

### New (from DB):
```php
$gradeLevels = GradeLevel::where('is_active', true)
    ->where('school_year', '2026-2027')
    ->orderBy('sort_order')
    ->get();
```

### In the form:
- Show grade name
- Show available slots: "12 of 40 available" (like shift cards)
- Gray out / disable if full

---

## Shift Card Updates

### Current (hardcoded in Blade component):
```blade
<x-enrollment.shift-card
    shift="1st Shift"
    ph-time="12:40 PM ~ 3:00 PM"
    start="12:40"
    end="15:00"
    :capacity="40"
    :available="12"
/>
```

### New (dynamic from DB):
```blade
@foreach ($shifts as $shift)
    <x-enrollment.shift-card
        :shift="$shift"
        :user-timezone="$userTimezone"
    />
@endforeach
```

The component reads `start_time`, `end_time`, `capacity`, `enrolled_count` from the model and converts times via Alpine.js based on the selected timezone.

---

## Implementation Order

1. Create migrations + models
2. Create seeders with current data
3. Create GradeShiftService
4. Update controller to pass DB data
5. Update form blade to use dynamic data
6. Add live timezone conversion to shift cards
7. Run migrations + seed
8. Test

---

## Notes
- Admin can update grades/shifts via direct SQL or future admin panel
- `enrolled_count` is updated when enrollment is finalized (not on draft)
- Slots shown are real-time based on DB count
- If a grade/shift is full, it shows "Full" badge and is not selectable
