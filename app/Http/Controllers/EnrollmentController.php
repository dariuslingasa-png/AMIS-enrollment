<?php

namespace App\Http\Controllers;

use App\Http\Requests\Enrollment\SaveDraftRequest;
use App\Http\Requests\Enrollment\SubmitEnrollmentRequest;
use App\Services\Enrollment\AffidavitPdfService;
use App\Services\Enrollment\EnrollmentApplicationService;
use App\Services\Enrollment\EnrollmentNotificationService;
use Illuminate\Http\Request;
use App\Services\Enrollment\GradeShiftService;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;

class EnrollmentController extends Controller
{
    public function __construct(
        private EnrollmentApplicationService $applications,
        private GradeShiftService $gradeShifts,
        private AffidavitPdfService $affidavitPdf,
    ) {
    }

    public function showEnrollmentForm(Request $request)
    {
        $user = Auth::user();
        $startFresh = $request->boolean('fresh')
            && !$request->route('applicant')
            && !$request->query('applicant')
            && !$request->input('applicant_id');

        if ($startFresh) {
            session()->forget('current_enrollment_applicant_id');
            $applicant = null;
        } else {
            $applicant = $this->applications->resolveForUser($user, $request, editableFirst: true);
        }

        if ($applicant && !in_array($applicant->status, EnrollmentApplicationService::EDITABLE_STATUSES, true)) {
            return redirect()->route('enrollment.dashboard')
                ->with('info', 'This application has already been finalized and is locked for review.');
        }

        $hasErrors = session()->has('errors') && session('errors')->any();
        $rejectionFixSteps = $this->rejectionFixSteps($applicant);
        $initialStep = $hasErrors
            ? 7
            : ($rejectionFixSteps ? min(array_keys($rejectionFixSteps)) : ($applicant ? min((int) ($applicant->last_step ?? 1), 7) : 1));
        $completedSteps = $hasErrors
            ? range(1, 6)
            : ($applicant ? range(1, min((int) ($applicant->last_step ?? 1), 7)) : []);

        return view('enrollment.form', [
            'gradeLevels' => $this->gradeShifts->getGradeLevels(),
            'shifts' => $this->gradeShifts->getShifts(),
            'applicant' => $applicant,
            'initialStep' => $initialStep,
            'completedSteps' => $completedSteps,
            'rejectionFixSteps' => $rejectionFixSteps,
            'startFresh' => $startFresh,
            'siblingData' => $this->applications->getSiblingReusableData($user, $applicant),
        ]);
    }

    private function rejectionFixSteps($applicant): array
    {
        if (!$applicant || $applicant->status !== 'rejected') {
            return [];
        }

        $stepLabels = [
            'registration_form' => [2, 'Registration form'],
            'student_information' => [2, 'Student information'],
            'address' => [3, 'Address or contact details'],
            'parent_information' => [4, 'Parent or guardian details'],
            'medical_information' => [5, 'Medical or emergency details'],
            'documents' => [6, 'Documents'],
            'photo_2x2' => [6, '2x2 picture'],
            'birth_cert' => [6, 'Birth certificate'],
            'report_card' => [6, 'Report card'],
            'marriage_contract' => [6, 'Marriage contract'],
            'medical_record' => [6, 'Medical record'],
            'affidavit' => [6, 'Affidavit'],
            'payment_proof' => [6, 'Payment proof'],
        ];

        $fixSteps = [];

        foreach (($applicant->document_statuses ?? []) as $key => $status) {
            if ($status !== 'rejected' || !isset($stepLabels[$key])) {
                continue;
            }

            [$step, $label] = $stepLabels[$key];
            $fixSteps[$step][] = $label;
        }

        if (!$fixSteps && filled($applicant->review_remarks)) {
            $fixSteps[2][] = 'Application details';
        }

        ksort($fixSteps);

        return collect($fixSteps)
            ->map(fn (array $labels) => array_values(array_unique($labels)))
            ->all();
    }

    public function startNewApplication()
    {
        $applicant = $this->applications->startNewFor(Auth::user());

        if (!$applicant) {
            return redirect()->route('enrollment.dashboard')
                ->with('info', 'Please complete your current child application first before adding another child.');
        }

        return redirect()->route('enrollment.form.child', $applicant)
            ->with('success', 'New child enrollment draft started. This stays grouped with your parent account.');
    }

    public function getShiftsForGrade(string $grade)
    {
        $shifts = $this->gradeShifts->getShiftsForGrade($grade);
        return response()->json($shifts);
    }

    public function showAffidavit(Request $request)
    {
        $user = Auth::user();
        $applicant = $this->applications->resolveForUser($user, $request, editableFirst: true);

        if (!$applicant || !in_array($applicant->status, EnrollmentApplicationService::EDITABLE_STATUSES, true)) {
            return redirect()->route('enrollment.form')
                ->with('info', 'Please save the student details first before preparing the affidavit.');
        }

        return view('enrollment.affidavit', [
            'applicant' => $applicant,
            'user' => $user,
            'storedAffidavitData' => $applicant->affidavit_data ?? [],
            'affidavitFields' => $this->affidavitPdf->fields(),
            'signatureField' => $this->affidavitPdf->signatureField(),
            'signatureNameField' => $this->affidavitPdf->signatureNameField(),
        ]);
    }

    public function saveAffidavitDraft(Request $request)
    {
        $user = $request->user();
        $applicant = $this->applications->resolveForUser($user, $request, editableFirst: true);

        if (!$applicant || !in_array($applicant->status, EnrollmentApplicationService::EDITABLE_STATUSES, true)) {
            return response()->json([
                'success' => false,
                'message' => 'This affidavit draft can no longer be edited.',
            ], 422);
        }

        $validated = $request->validate($this->affidavitRules(requireSignature: false));
        $normalized = $this->normalizeAffidavitData($validated);

        if (!empty($normalized['signature_data'])) {
            $signatureError = $this->signatureValidationError($normalized['signature_data']);

            if ($signatureError) {
                return response()->json([
                    'success' => false,
                    'message' => $signatureError,
                ], 422);
            }
        }

        $applicant->update([
            'affidavit_data' => $this->affidavitDraftData($normalized),
            'last_step' => max((int) ($applicant->last_step ?? 1), 6),
        ]);

        return response()->json([
            'success' => true,
            'last_saved' => $applicant->fresh()->updated_at?->diffForHumans(),
        ]);
    }

    public function storeAffidavit(Request $request)
    {
        $user = $request->user();
        $applicant = $this->applications->resolveForUser($user, $request, editableFirst: true);

        if (!$applicant || !in_array($applicant->status, EnrollmentApplicationService::EDITABLE_STATUSES, true)) {
            return redirect()->route('enrollment.form')
                ->with('info', 'Please save the student details first before preparing the affidavit.');
        }

        $validated = $this->normalizeAffidavitData($request->validate($this->affidavitRules(requireSignature: true)));
        $signatureError = $this->signatureValidationError($validated['signature_data']);

        if ($signatureError) {
            return back()
                ->withInput()
                ->withErrors(['signature_data' => $signatureError]);
        }

        $oldPath = $applicant->affidavit_url;
        $path = 'documents/' . $applicant->id . '/affidavit-undertaking-' . now()->format('YmdHis') . '.pdf';

        Storage::disk('public')->put($path, $this->affidavitPdf->build($validated));

        if ($oldPath) {
            Storage::disk('public')->delete($oldPath);
        }

        $applicant->update([
            'affidavit_url' => $path,
            'affidavit_data' => $this->affidavitDraftData($validated),
            'last_step' => max((int) ($applicant->last_step ?? 1), 6),
        ]);

        return redirect()
            ->route('enrollment.form.child', ['applicant' => $applicant->id])
            ->with('success', 'Signed affidavit saved. You can continue the enrollment documents step.');
    }

    private function affidavitRules(bool $requireSignature): array
    {
        $required = $requireSignature ? 'required' : 'nullable';

        return [
            'guardian_name' => $required . '|string|max:255',
            'guardian_relationship' => $required . '|string|max:100',
            'guardian_address' => $required . '|string|max:500',
            'student_name' => $required . '|string|max:255',
            'grade_level' => $required . '|string|max:100',
            'school_year' => $required . '|string|max:20',
            'missing_credential' => $required . '|string|max:120',
            'other_missing_credential' => 'nullable|string|max:120',
            'reason' => $required . '|string|max:600',
            'commitment_date' => $required . '|string|max:120',
            'attested_day' => $required . '|string|max:10',
            'attested_month' => $required . '|string|max:30',
            'attested_place' => $required . '|string|max:120',
            'govt_id_presented' => 'nullable|string|max:120',
            'id_number' => 'nullable|string|max:120',
            'date_issued' => 'nullable|string|max:120',
            'govt_id_type' => 'nullable|string|max:120',
            'govt_id_number' => 'nullable|string|max:120',
            'govt_id_date' => 'nullable|string|max:120',
            'signature_data' => $required . '|string',
            'agreement' => $requireSignature ? 'accepted' : 'nullable',
        ];
    }

    private function normalizeAffidavitData(array $data): array
    {
        foreach ($data as $key => $value) {
            if (is_string($value)) {
                $data[$key] = trim($value);
            }
        }

        if (($data['missing_credential'] ?? '') === 'Other') {
            $data['missing_credential'] = $data['other_missing_credential'] ?: 'Other required admission document';
        }

        return $data;
    }

    private function signatureValidationError(?string $signatureData): ?string
    {
        if (!is_string($signatureData) || !preg_match('/^data:image\/png;base64,[A-Za-z0-9+\/=]+$/', $signatureData)) {
            return 'Please sign inside the signature box before saving.';
        }

        if (strlen($signatureData) > 2_000_000) {
            return 'The signature image is too large. Please clear and sign again.';
        }

        return null;
    }

    private function affidavitDraftData(array $data): array
    {
        return collect($data)
            ->only([
                'guardian_name',
                'guardian_relationship',
                'guardian_address',
                'student_name',
                'grade_level',
                'school_year',
                'missing_credential',
                'other_missing_credential',
                'reason',
                'commitment_date',
                'attested_day',
                'attested_month',
                'attested_place',
                'govt_id_presented',
                'id_number',
                'date_issued',
                'govt_id_type',
                'govt_id_number',
                'govt_id_date',
                'signature_data',
            ])
            ->filter(fn ($value) => $value !== null && $value !== '')
            ->all();
    }

    public function saveDraft(SaveDraftRequest $request)
    {
        $result = $this->applications->saveDraft(
            $request->user(),
            $request,
            $request->draftData()
        );

        if (is_array($result) && ($result['duplicate'] ?? false)) {
            return response()->json([
                'success' => false,
                'duplicate' => true,
                'message' => $result['message'],
            ], 409);
        }

        return response()->json([
            'success' => true,
            'applicant_id' => $result->id,
            'percentage' => $result->completion_percentage,
            'last_step' => $result->last_step,
        ]);
    }

    public function discardDraft(Request $request)
    {
        $discardedApplicant = $this->applications->discardDraft($request->user(), $request);

        if (!$discardedApplicant) {
            return redirect()->route('enrollment.dashboard')
                ->with('error', 'We could not find that child draft. Please refresh and try again.');
        }

        return redirect()->route('enrollment.dashboard')
            ->with('success', 'Draft cleared. You can start a fresh enrollment form.')
            ->with('clear_draft_cache', true)
            ->with('discarded_draft_applicant_id', $discardedApplicant->id);
    }

    public function removeDraftDocument(Request $request, string $document)
    {
        $removed = $this->applications->removeDraftDocument($request->user(), $request, $document);

        if (!$removed) {
            return response()->json([
                'success' => false,
                'message' => 'This document can no longer be removed.',
            ], 422);
        }

        return response()->json([
            'success' => true,
            'message' => 'Document removed.',
        ]);
    }

    public function submitEnrollment(
        SubmitEnrollmentRequest $request
    ) {
        $user = $request->user();
        $applicant = $this->applications->submit($user, $request, $request->enrollmentData());

        return redirect()->route('enrollment.dashboard', ['applicant' => $applicant->id])
            ->with('success', 'Child application is ready for submission. You may add another child or finalize enrollment.');
    }

    public function showFinalizePreview(Request $request)
    {
        $user = $request->user();
        $readyApplications = $this->applications->readyApplications($user);
        $draftApplications = $this->applications->draftApplications($user);
        $incompleteApplications = $this->applications->incompleteApplications($readyApplications);

        if ($readyApplications->isEmpty()) {
            return redirect()->route('enrollment.dashboard')
                ->with('info', 'Complete at least one child application before finalizing enrollment.');
        }

        if ($draftApplications->isNotEmpty()) {
            return redirect()->route('enrollment.dashboard')
                ->with('info', 'Please complete all child drafts before finalizing enrollment.');
        }

        return view('enrollment.finalize', [
            'user' => $user,
            'readyApplications' => $readyApplications,
            'draftApplications' => $draftApplications,
            'incompleteApplications' => $incompleteApplications,
        ]);
    }

    public function confirmFinalize(
        Request $request,
        EnrollmentNotificationService $notifications
    ) {
        $user = $request->user();
        $readyApplications = $this->applications->readyApplications($user);
        $incompleteApplications = $this->applications->incompleteApplications($readyApplications);

        if ($readyApplications->isEmpty()) {
            return redirect()->route('enrollment.dashboard')
                ->with('info', 'No ready child applications found for final submission.');
        }

        if ($this->applications->draftApplications($user)->isNotEmpty()) {
            return redirect()->route('enrollment.dashboard')
                ->with('info', 'Please complete all child drafts before finalizing enrollment.');
        }

        if ($incompleteApplications->isNotEmpty()) {
            return redirect()->route('enrollment.finalize.preview')
                ->with('error', 'Please complete all required child application fields before final submission.');
        }

        $submittedApplications = $this->applications->finalizeReadyApplications($user);

        foreach ($submittedApplications as $submittedApplication) {
            $notifications->sendSubmissionConfirmation($user->email, $submittedApplication);

            if (!empty($submittedApplication->parent_email) && $submittedApplication->parent_email !== $user->email) {
                $notifications->sendSubmissionConfirmation($submittedApplication->parent_email, $submittedApplication);
            }
        }

        return redirect()->route('enrollment.dashboard')
            ->with('success', 'Your enrollment application has been submitted successfully. You may upload your payment proof from the dashboard.');
    }

    public function showSuccess(Request $request)
    {
        $user = Auth::user();
        $applicant = $this->applications->resolveForUser($user, $request);

        if (!$applicant || !in_array($applicant->status, EnrollmentApplicationService::FINAL_STATUSES, true)) {
            $applicant = $user->enrollmentApplicants()
                ->whereIn('status', EnrollmentApplicationService::FINAL_STATUSES)
                ->latest()
                ->first();
        }

        return view('enrollment.success', compact('applicant'));
    }

    public function showDashboard(Request $request)
    {
        $user = Auth::user();
        $applicants = $user->enrollmentApplicants()
            ->with(['payment', 'student'])
            ->oldest()
            ->get();
        $applicant = $this->applications->resolveForUser($user, $request) ?? $applicants->first();
        $payment = $applicant?->payment;
        $student = $applicant?->student;
        $canAddAnotherChild = $this->applications->canAddAnotherChild($user);
        $readyApplications = $this->applications->readyApplications($user);
        $draftApplications = $this->applications->draftApplications($user);

        return view('enrollment.dashboard', compact(
            'user',
            'applicant',
            'applicants',
            'payment',
            'student',
            'canAddAnotherChild',
            'readyApplications',
            'draftApplications'
        ));
    }

    public function showPayment(Request $request)
    {
        $user = Auth::user();
        $applicant = $this->applications->resolveForUser($user, $request)
            ?? $user->enrollmentApplicants()->latest()->first();

        if (!$this->applications->canAccessPayment($applicant)) {
            return redirect()->route('enrollment.dashboard')
                ->with('info', 'Please complete your enrollment application first.');
        }

        return view('enrollment.payment-coming-soon', compact('applicant'));
    }

    public function submitPayment(Request $request)
    {
        $user = Auth::user();
        $applicant = $this->applications->resolveForUser($user, $request)
            ?? $user->enrollmentApplicants()->latest()->first();

        if (!$this->applications->canAccessPayment($applicant)) {
            return redirect()->route('enrollment.dashboard')
                ->with('info', 'Please complete your enrollment application first.');
        }

        return redirect()->route('enrollment.dashboard')
            ->with('info', 'Payment proof upload is coming soon. Please wait for the Finance Office announcement.');
    }

    public function showClosed()
    {
        return view('enrollment.closed');
    }

    public function checkApplicationStatus(Request $request)
    {
        $user = Auth::user();
        $applicant = $this->applications->resolveForUser($user, $request)
            ?? $user->enrollmentApplicants()->latest()->first();

        return response()->json([
            'status' => $applicant->status ?? 'not_found',
            'submitted_at' => $applicant->created_at ?? null,
            'percentage' => $applicant?->completion_percentage ?? 0,
            'last_step' => $applicant?->last_step ?? 1,
            'last_saved' => $applicant?->updated_at?->diffForHumans() ?? null,
        ]);
    }

}
