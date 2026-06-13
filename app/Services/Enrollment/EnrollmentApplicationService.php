<?php

namespace App\Services\Enrollment;

use App\Models\EnrollmentApplicant;
use App\Models\User;
use App\Services\Upload\EnrollmentUploadService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Collection;

class EnrollmentApplicationService
{
    public const STATUS_DRAFT = 'draft';
    public const STATUS_READY = 'ready_for_submission';
    public const STATUS_PENDING = 'pending';
    public const STATUS_SUBMITTED = 'submitted';
    public const EDITABLE_STATUSES = [self::STATUS_DRAFT, self::STATUS_READY, 'rejected'];
    public const FINAL_STATUSES = [self::STATUS_PENDING, self::STATUS_SUBMITTED, 'under_review', 'approved'];

    public function __construct(
        private SiblingDiscountService $discounts,
        private EnrollmentUploadService $uploads
    ) {
    }

    public function resolveForUser(User $user, Request $request, bool $editableFirst = false): ?EnrollmentApplicant
    {
        $routeApplicant = $request->route('applicant');
        $explicitId = $request->input('applicant_id')
            ?? $request->query('applicant')
            ?? ($routeApplicant instanceof EnrollmentApplicant ? $routeApplicant->id : $routeApplicant);

        if ($explicitId) {
            $existsGlobally = EnrollmentApplicant::whereKey($explicitId)->exists();
            if ($existsGlobally) {
                $applicant = $user->enrollmentApplicants()->whereKey($explicitId)->first();
                if (!$applicant) {
                    abort(403, 'Unauthorized access to applicant.');
                }
                session(['current_enrollment_applicant_id' => $applicant->id]);
                return $applicant;
            }
        }

        $sessionApplicantId = session('current_enrollment_applicant_id');
        if ($sessionApplicantId) {
            $applicant = $user->enrollmentApplicants()->whereKey($sessionApplicantId)->first();
            if ($applicant) {
                return $applicant;
            }
            session()->forget('current_enrollment_applicant_id');
        }

        if ($editableFirst) {
            return $user->enrollmentApplicants()
                ->whereIn('status', self::EDITABLE_STATUSES)
                ->latest()
                ->first()
                ?? $user->enrollmentApplicants()->latest()->first();
        }

        return null;
    }

    public function startNewFor(User $user): ?EnrollmentApplicant
    {
        if (!$this->canAddAnotherChild($user)) {
            return null;
        }

        $familyId = $this->familyApplicationIdFor($user);
        $applicant = EnrollmentApplicant::create(array_merge($this->reusableParentData($user), [
            'user_id' => $user->id,
            'family_application_id' => $familyId,
            'status' => self::STATUS_DRAFT,
            'school_year' => '2026-2027',
            'last_step' => 1,
        ]));

        $this->ensureFamilyApplication($applicant, $user);
        $this->discounts->apply($user, $applicant);
        session(['current_enrollment_applicant_id' => $applicant->id]);

        return $applicant;
    }

    public function canAddAnotherChild(User $user): bool
    {
        $applications = $user->enrollmentApplicants()->get(['id', 'status']);

        if ($applications->isEmpty()) {
            return true;
        }

        $hasCompletedApplication = $applications->contains(
            fn (EnrollmentApplicant $applicant) => in_array($applicant->status, array_merge([self::STATUS_READY], self::FINAL_STATUSES), true)
        );
        $hasUnfinishedDraft = $applications->contains(
            fn (EnrollmentApplicant $applicant) => in_array($applicant->status, [self::STATUS_DRAFT, 'rejected'], true)
        );

        return $hasCompletedApplication && !$hasUnfinishedDraft;
    }

    public function saveDraft(User $user, Request $request, array $data): EnrollmentApplicant|array
    {
        $data['user_id'] = $user->id;
        $familyId = $this->familyApplicationIdFor($user);
        if ($familyId) {
            $data['family_application_id'] = $familyId;
        }

        $shouldCheckDuplicate = $request->boolean('check_duplicate')
            && (int) $request->input('from_step', $data['last_step'] ?? 0) === 2;

        if ($shouldCheckDuplicate) {
            $existingApplicant = $this->resolveEditableApplication($user, $request);
            $duplicate = $this->findDuplicate($data, $existingApplicant);
            if ($duplicate) {
                return [
                    'duplicate' => true,
                    'message' => 'Possible duplicate enrollment record found.',
                    'existing' => $duplicate,
                ];
            }
        }

        $applicant = $this->resolveEditableApplication($user, $request);
        $data['status'] = $applicant?->status === 'rejected' ? 'rejected' : self::STATUS_DRAFT;

        if ($applicant) {
            $applicant->update($data);
        } else {
            // Deduplication: if no applicant_id was provided, check if a draft was
            // just created in the last 30 seconds with the same student name to
            // prevent race-condition duplicates (autosave + cancelAndSave firing together).
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
        }

        $this->ensureFamilyApplication($applicant, $user);
        $this->discounts->apply($user, $applicant);
        session(['current_enrollment_applicant_id' => $applicant->id]);
        $this->uploads->storeEnrollmentDocuments($applicant, $request);

        return $applicant->refresh();
    }

    public function submit(User $user, Request $request, array $data): EnrollmentApplicant
    {
        $applicant = $this->resolveEditableApplication($user, $request);

        $submitData = array_merge($data, [
            'user_id' => $user->id,
            'status' => $this->submitStatusFor($applicant),
            'last_step' => 7,
            'document_statuses' => $this->documentStatusesForResubmission($applicant),
            'review_remarks' => null,
        ]);
        $familyId = $this->familyApplicationIdFor($user);
        if ($familyId) {
            $submitData['family_application_id'] = $familyId;
        }

        if ($applicant) {
            $applicant->update($submitData);
        } else {
            $applicant = EnrollmentApplicant::create($submitData);
        }

        $this->ensureFamilyApplication($applicant, $user);
        $this->discounts->apply($user, $applicant);
        session(['current_enrollment_applicant_id' => $applicant->id]);
        $this->uploads->storeEnrollmentDocuments($applicant, $request);

        return $applicant->refresh();
    }

    public function readyApplications(User $user): Collection
    {
        return $user->enrollmentApplicants()
            ->where('status', self::STATUS_READY)
            ->oldest()
            ->get();
    }

    public function draftApplications(User $user): Collection
    {
        return $user->enrollmentApplicants()
            ->whereIn('status', [self::STATUS_DRAFT, 'rejected'])
            ->oldest()
            ->get();
    }

    public function finalizeReadyApplications(User $user): Collection
    {
        $applications = $this->readyApplications($user);
        $incomplete = $this->incompleteApplications($applications);

        if ($applications->isEmpty() || $incomplete->isNotEmpty()) {
            return collect();
        }

        $applications->each(function (EnrollmentApplicant $applicant) {
            $applicant->update([
                'status' => self::STATUS_SUBMITTED,
                'review_remarks' => null,
            ]);
        });

        return $applications->fresh();
    }

    public function incompleteApplications(Collection $applications): Collection
    {
        return $applications
            ->mapWithKeys(fn (EnrollmentApplicant $applicant) => [
                $applicant->id => $this->missingRequirements($applicant),
            ])
            ->filter(fn (array $missing) => !empty($missing));
    }

    public function missingRequirements(EnrollmentApplicant $applicant): array
    {
        $requirements = [
            'Student type' => $applicant->student_type,
            'Learning mode' => $applicant->learning_mode,
            'Grade level' => $applicant->grade_level,
            'First name' => $applicant->first_name,
            'Last name' => $applicant->last_name,
            'Gender' => $applicant->gender,
            'Date of birth' => $applicant->date_of_birth,
            'Place of birth' => $applicant->place_of_birth,
            'Religion' => $applicant->religion,
            'Country' => $applicant->country,
            'Street address' => $applicant->street_address,
            'Mobile country code' => $applicant->mobile_country_code,
            'Mobile number' => $applicant->mobile_number,
            'Parent country code' => $applicant->parent_country_code,
            'Parent mobile' => $applicant->parent_mobile,
            'Medical concern answer' => $applicant->medical_has_concern,
            'Emergency name' => $applicant->emergency_name,
            'Emergency relationship' => $applicant->emergency_relationship,
            'Emergency phone' => $applicant->emergency_phone,
            'School year' => $applicant->school_year,
            '2x2 photo' => $applicant->photo_2x2_url,
        ];

        if ($applicant->student_type !== 'Old' && !in_array($applicant->grade_level, ['Kinder 1', 'Kinder 2'], true)) {
            $requirements['Report card or signed temporary proof'] = $applicant->report_card_url ?: $applicant->affidavit_url;
        }

        if (str_starts_with((string) $applicant->learning_mode, 'Flexible Online Learning')) {
            $requirements['Timezone'] = $applicant->timezone;
        }

        return collect($requirements)
            ->filter(fn ($value) => $value === null || $value === '')
            ->keys()
            ->all();
    }

    public function discardDraft(User $user, Request $request): ?EnrollmentApplicant
    {
        $applicant = $this->resolveDraftForDiscard($user, $request);

        if (!$applicant || !in_array($applicant->status, [self::STATUS_DRAFT, self::STATUS_READY], true)) {
            Log::warning('Enrollment draft discard skipped because no selected draft was found.', [
                'user_id' => $user->id,
                'requested_applicant_id' => $request->input('applicant_id'),
                'session_applicant_id' => session('current_enrollment_applicant_id'),
            ]);

            return null;
        }

        $this->uploads->deleteEnrollmentDocuments($applicant);
        $applicant->delete();

        if ((int) session('current_enrollment_applicant_id') === (int) $applicant->id) {
            session()->forget('current_enrollment_applicant_id');
        }

        return $applicant;
    }

    private function resolveDraftForDiscard(User $user, Request $request): ?EnrollmentApplicant
    {
        $applicantId = $request->input('applicant_id');

        if (!$applicantId) {
            $sessionApplicantId = session('current_enrollment_applicant_id');
            $applicantId = $sessionApplicantId ?: null;
        }

        if (!$applicantId) {
            return null;
        }

        $existsGlobal = EnrollmentApplicant::whereKey($applicantId)->exists();
        if ($existsGlobal) {
            $applicant = $user->enrollmentApplicants()
                ->whereKey($applicantId)
                ->whereIn('status', [self::STATUS_DRAFT, self::STATUS_READY])
                ->first();
            if (!$applicant) {
                abort(403, 'Unauthorized access to applicant.');
            }
            return $applicant;
        }

        return null;
    }

    public function removeDraftDocument(User $user, Request $request, string $document): bool
    {
        if (!$this->uploads->isEnrollmentDocument($document)) {
            abort(404);
        }

        $applicant = $this->resolveForUser($user, $request)
            ?? $user->enrollmentApplicants()->whereIn('status', self::EDITABLE_STATUSES)->latest()->first();

        if (!$applicant || !in_array($applicant->status, self::EDITABLE_STATUSES, true)) {
            return false;
        }

        $this->uploads->removeDraftDocument($applicant, $document);

        return true;
    }

    public function canAccessPayment(?EnrollmentApplicant $applicant): bool
    {
        if (!$applicant) {
            return false;
        }

        return in_array($applicant->status, array_merge([self::STATUS_READY], self::FINAL_STATUSES), true);
    }

    /**
     * Get reusable sibling data for the "Same as sibling" checkboxes.
     * Returns null if this is the first child (no sibling to copy from).
     */
    public function getSiblingReusableData(User $user, ?EnrollmentApplicant $currentApplicant): ?array
    {
        // Only show sibling reuse for 2nd+ applicants (not the first/oldest one)
        if ($currentApplicant) {
            $oldestApplicant = $user->enrollmentApplicants()->oldest()->first();
            if ($oldestApplicant && (int) $oldestApplicant->id === (int) $currentApplicant->id) {
                return null;
            }
        }

        $source = $user->enrollmentApplicants()
            ->whereIn('status', array_merge([self::STATUS_READY], self::FINAL_STATUSES))
            ->when($currentApplicant, fn ($q) => $q->where('id', '!=', $currentApplicant->id))
            ->oldest()
            ->first();

        if (!$source) {
            return null;
        }

        return [
            // Sibling identity (for display)
            'sibling_name' => trim(($source->first_name ?? '') . ' ' . ($source->last_name ?? '')),
            // Schedule fields
            'student_type' => $source->student_type,
            'learning_mode' => $source->learning_mode,
            'timezone' => $source->timezone,
            // Address & contact fields
            'country' => $source->country,
            'state_province' => $source->state_province,
            'city' => $source->city,
            'street_address' => $source->street_address,
            'postal_code' => $source->postal_code,
            'mobile_country_code' => $source->mobile_country_code,
            'mobile_number' => $source->mobile_number,
            // Parent fields
            'father_last_name' => $source->father_last_name,
            'father_first_name' => $source->father_first_name,
            'father_middle_name' => $source->father_middle_name,
            'father_occupation' => $source->father_occupation,
            'mother_last_name' => $source->mother_last_name,
            'mother_first_name' => $source->mother_first_name,
            'mother_middle_name' => $source->mother_middle_name,
            'mother_occupation' => $source->mother_occupation,
            'home_address' => $source->home_address,
            'home_state_province' => $source->home_state_province,
            'home_city' => $source->home_city,
            'home_street_address' => $source->home_street_address,
            'home_postal_code' => $source->home_postal_code,
            'parent_country_code' => $source->parent_country_code,
            'parent_mobile' => $source->parent_mobile,
            'parent_email' => $source->parent_email,
            // Emergency fields
            'emergency_name' => $source->emergency_name,
            'emergency_relationship' => $source->emergency_relationship,
            'emergency_phone' => $source->emergency_phone,
            'emergency_instructions' => $source->emergency_instructions,
        ];
    }

    private function resolveEditableApplication(User $user, Request $request): ?EnrollmentApplicant
    {
        $applicant = $this->resolveForUser($user, $request);

        if ($applicant && !in_array($applicant->status, self::EDITABLE_STATUSES, true)) {
            return null;
        }

        return $applicant;
    }

    private function reusableParentData(User $user): array
    {
        $source = $user->enrollmentApplicants()
            ->whereIn('status', array_merge([self::STATUS_READY], self::FINAL_STATUSES))
            ->where(function ($query) {
                $query->whereNotNull('parent_mobile')
                    ->orWhereNotNull('parent_email')
                    ->orWhereNotNull('father_last_name')
                    ->orWhereNotNull('mother_last_name');
            })
            ->latest()
            ->first();

        if (!$source) {
            return [];
        }

        // Do NOT auto-copy any fields for another child.
        // Parent must use the "Same schedule" and "Same parent info" checkboxes to opt-in.
        return [];
    }

    private function familyApplicationIdFor(User $user): ?int
    {
        $existingFamilyId = $user->enrollmentApplicants()
            ->whereNotNull('family_application_id')
            ->oldest()
            ->value('family_application_id');

        if ($existingFamilyId) {
            return (int) $existingFamilyId;
        }

        $rootId = $user->enrollmentApplicants()->oldest()->value('id');
        return $rootId ? (int) $rootId : null;
    }

    private function ensureFamilyApplication(EnrollmentApplicant $applicant, User $user): void
    {
        if ($applicant->family_application_id) {
            return;
        }

        $applicant->forceFill([
            'family_application_id' => $this->familyApplicationIdFor($user) ?: $applicant->id,
        ])->save();
    }

    private function findDuplicate(array $data, ?EnrollmentApplicant $currentApplicant = null): ?EnrollmentApplicant
    {
        $lastName = trim($data['last_name'] ?? '');
        $firstName = trim($data['first_name'] ?? '');
        $middleName = trim($data['middle_name'] ?? '');
        $dob = $data['date_of_birth'] ?? null;

        if (!$lastName || !$firstName || !$dob) {
            return null;
        }

        return EnrollmentApplicant::query()
            ->when($currentApplicant, fn ($q) => $q->where('id', '!=', $currentApplicant->id))
            ->whereRaw('LOWER(TRIM(last_name)) = ?', [strtolower($lastName)])
            ->whereRaw('LOWER(TRIM(first_name)) = ?', [strtolower($firstName)])
            ->whereRaw('LOWER(TRIM(COALESCE(middle_name, \'\'))) = ?', [strtolower($middleName)])
            ->whereDate('date_of_birth', $dob)
            ->first();
    }

    private function submitStatusFor(?EnrollmentApplicant $applicant): string
    {
        if ($applicant?->status !== 'rejected') {
            return self::STATUS_READY;
        }

        return $this->hasReusablePayment($applicant)
            ? self::STATUS_SUBMITTED
            : self::STATUS_READY;
    }

    private function hasReusablePayment(EnrollmentApplicant $applicant): bool
    {
        $familyId = $applicant->family_application_id ?: $applicant->id;

        return \App\Models\Payment::query()
            ->whereIn('status', ['pending', 'verified'])
            ->whereNotNull('receipt_url')
            ->where(function ($query) use ($applicant, $familyId) {
                $query->where('enrollment_applicant_id', $applicant->id)
                    ->orWhereIn('enrollment_applicant_id', function ($subquery) use ($familyId) {
                        $subquery->select('id')
                            ->from('enrollment_applicants')
                            ->where('family_application_id', $familyId)
                            ->orWhere('id', $familyId);
                    });
            })
            ->exists();
    }

    private function documentStatusesForResubmission(?EnrollmentApplicant $applicant): ?array
    {
        if ($applicant?->status !== 'rejected') {
            return null;
        }

        $statuses = collect($applicant->document_statuses ?? [])
            ->filter(fn ($status, string $key) => $key === 'payment_proof' && in_array($status, ['pending', 'approved'], true))
            ->all();

        return empty($statuses) ? null : $statuses;
    }
}
