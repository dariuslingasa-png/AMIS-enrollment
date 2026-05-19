# Saved-Draft Fix — May 18, 2026

## Problem Summary

Three bugs in the enrollment form's autosave/draft system:

1. **Race condition on "Save & Exit"** — When the user clicks "Save & Exit" (`cancelAndSave()`), the 2-second debounce timer might still be running. Both the timer callback and `cancelAndSave()` fire simultaneously with `applicant_id = null`, creating duplicate DB records.

2. **"Discard unsaved" doesn't delete DB record** — The `discardUnsavedDraft()` method (triggered from the cancel prompt) only clears localStorage and redirects. If autosave already fired and created a DB record, that orphan record stays in the database and reappears on the dashboard.

3. **No deduplication on backend** — When two save requests arrive at the same time without an `applicant_id`, the service creates two separate `enrollment_applicants` rows for the same child.

---

## Files Changed

| File | Change |
|------|--------|
| `resources/views/enrollment/form.blade.php` | `cancelAndSave()`: clear debounce timer + set `leavingWithoutSaving` before saving |
| `resources/views/enrollment/form.blade.php` | `discardUnsavedDraft()`: send beacon DELETE to backend if `savedApplicantId` exists |
| `app/Services/Enrollment/EnrollmentApplicationService.php` | `saveDraft()`: deduplication guard — if no applicant resolved, check for a recent draft (last 30s) with same student name before creating a new row |

---

## Fix Details

### 1. `cancelAndSave()` — Stop debounce timer first

```javascript
async cancelAndSave() {
    this.showCancelPrompt = false;
    clearTimeout(this._debounceTimer);       // ← added
    this.leavingWithoutSaving = true;        // ← added (prevents scheduleDraft from firing)
    const result = await this.saveDraft({ force: true });
    // ...
}
```

**Why:** `clearTimeout` kills any pending autosave. `leavingWithoutSaving = true` prevents `scheduleDraft()` from scheduling a new one while the save is in flight.

### 2. `discardUnsavedDraft()` — Delete DB record via beacon

```javascript
discardUnsavedDraft() {
    // ... existing cleanup ...

    // If autosave already created a DB record, delete it
    if (this.savedApplicantId) {
        const fd = new FormData();
        fd.append('_token', csrfToken);
        fd.append('_method', 'DELETE');
        fd.append('applicant_id', this.savedApplicantId);
        navigator.sendBeacon('/enroll/draft', fd);
    }

    // ... redirect ...
}
```

**Why:** `sendBeacon` is fire-and-forget — it works even during page navigation. Laravel's method spoofing reads `_method=DELETE` from the POST body and routes to `discardDraft()`.

### 3. Backend deduplication in `saveDraft()`

```php
// If no existing applicant was resolved, check for a recent draft
// created in the last 30 seconds with the same student name.
$recent = $user->enrollmentApplicants()
    ->where('status', self::STATUS_DRAFT)
    ->where('updated_at', '>=', now()->subSeconds(30))
    ->when(!empty($data['first_name']), fn ($q) => $q->where('first_name', $data['first_name']))
    ->when(!empty($data['last_name']), fn ($q) => $q->where('last_name', $data['last_name']))
    ->latest()
    ->first();

if ($recent) {
    $recent->update($data);
    $applicant = $recent;
} else {
    $applicant = EnrollmentApplicant::create($data);
}
```

**Why:** If two requests arrive within 30 seconds with the same student name and no `applicant_id`, the second one updates the first instead of creating a duplicate.

---

## How the Draft System Works (Reference)

```
┌─────────────────────────────────────────────────────────┐
│  User types in form                                      │
│       ↓                                                  │
│  scheduleDraft() — debounce 2s                           │
│       ↓                                                  │
│  saveDraft()                                             │
│    1. Save to localStorage (instant backup)              │
│    2. POST /enroll/draft → backend saves to DB           │
│    3. Backend returns applicant_id                       │
│    4. savedApplicantId is set for future saves           │
│       ↓                                                  │
│  On page load:                                           │
│    - If $applicant exists → Blade pre-fills from DB      │
│    - If no $applicant → check localStorage fallback      │
│    - If discarded flag → don't restore anything          │
│       ↓                                                  │
│  Dashboard always reads from DB (Eloquent queries)       │
│  Final submit always uses DB data (not localStorage)     │
└─────────────────────────────────────────────────────────┘
```

---

## Testing Checklist

1. ✅ Start form, type data, wait 2s, refresh → DB draft restores
2. ✅ Start form, turn off internet, type data → localStorage saves
3. ✅ Reconnect internet → next autosave syncs to backend
4. ✅ Click "Save & Exit" → only 1 DB record created (no duplicate)
5. ✅ Click "Discard unsaved" after autosave fired → DB record deleted
6. ✅ Reopen form after discard → old data does not return
7. ✅ Create Child A and Child B drafts → both appear on dashboard
8. ✅ Discard Child A → Child B remains untouched
9. ✅ Final submit uses DB data only
10. ✅ Dashboard shows DB drafts only (not localStorage)
