<?php

namespace App\Http\Controllers;

use App\Models\EnrollmentApplicant;
use App\Models\Payment;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;

class EnrollmentController extends Controller
{
    private array $gradeLevels = [
        'Kinder 1', 'Kinder 2',
        'Grade 1', 'Grade 2', 'Grade 3', 'Grade 4', 'Grade 5', 'Grade 6',
        'Grade 7', 'Grade 8', 'Grade 9', 'Grade 10',
        'Grade 11', 'Grade 12',
    ];

    public function showEnrollmentForm()
    {
        $user = Auth::user();
        $applicant = $user->enrollmentApplicant;

        // If already submitted (not a draft), redirect to dashboard
        if ($applicant && !in_array($applicant->status, ['draft', 'rejected'])) {
            return redirect()->route('enrollment.dashboard')
                ->with('info', 'Your application has already been submitted.');
        }

        $hasErrors    = session()->has('errors') && session('errors')->any();
        $initialStep  = $hasErrors ? 5 : ($applicant ? ($applicant->last_step ?? 1) : 1);
        $completedSteps = $hasErrors
            ? range(1, 4)
            : ($applicant ? range(1, min((int)($applicant->last_step ?? 1), 5)) : []);

        return view('enrollment.form', [
            'gradeLevels'    => $this->gradeLevels,
            'applicant'      => $applicant,
            'initialStep'    => $initialStep,
            'completedSteps' => $completedSteps,
        ]);
    }

    /**
     * Save current step as draft (AJAX-friendly, returns JSON).
     */
    public function saveDraft(Request $request)
    {
        $user = Auth::user();

        $data = $request->only([
            'student_type', 'learning_mode', 'lrn', 'grade_level',
            'last_name', 'first_name', 'middle_name', 'gender',
            'date_of_birth', 'place_of_birth', 'religion', 'country',
            'address', 'email', 'mobile_number',
            'father_last_name', 'father_first_name', 'father_middle_name', 'father_occupation',
            'mother_last_name', 'mother_first_name', 'mother_middle_name', 'mother_occupation',
            'home_address', 'parent_mobile', 'parent_email',
            'psych_testing', 'prescription_med', 'med_explanation',
            'family_physician', 'physician_phone',
            'emergency_name', 'emergency_relationship', 'emergency_phone',
            'school_year', 'last_step',
        ]);

        // Strip empty strings to null so DB stays clean
        $data = array_map(fn($v) => $v === '' ? null : $v, $data);
        $data['user_id'] = $user->id;
        $data['status']  = 'draft';
        $data['lrn']     = $data['lrn'] ?: null;

        $applicant = $user->enrollmentApplicant;

        if ($applicant) {
            $applicant->update($data);
        } else {
            $applicant = EnrollmentApplicant::create($data);
        }

        // Handle file uploads if any were sent with this draft save
        $this->handleFileUploads($applicant, $request);

        return response()->json([
            'success'    => true,
            'percentage' => $applicant->completion_percentage,
            'last_step'  => $applicant->last_step,
        ]);
    }

    /**
     * Final submit — validates everything and marks as pending.
     */
    public function submitEnrollment(Request $request)
    {
        $user = Auth::user();

        $validated = $request->validate([
            'student_type'            => 'required|in:New,Old',
            'learning_mode'           => 'required|string',
            'lrn'                     => 'nullable|digits:12',
            'grade_level'             => 'required|string',
            'last_name'               => 'required|string|max:255',
            'first_name'              => 'required|string|max:255',
            'middle_name'             => 'required|string|max:255',
            'gender'                  => 'required|in:Male,Female',
            'date_of_birth'           => 'required|date|before:today',
            'place_of_birth'          => 'required|string|max:255',
            'religion'                => 'required|string|max:255',
            'country'                 => 'required|string|max:255',
            'address'                 => 'required|string|max:500',
            'email'                   => 'nullable|email|max:255',
            'mobile_number'           => 'required|string|min:7|max:20',
            'father_last_name'        => 'nullable|string|max:255',
            'father_first_name'       => 'nullable|string|max:255',
            'father_middle_name'      => 'nullable|string|max:255',
            'father_occupation'       => 'nullable|string|max:255',
            'mother_last_name'        => 'nullable|string|max:255',
            'mother_first_name'       => 'nullable|string|max:255',
            'mother_middle_name'      => 'nullable|string|max:255',
            'mother_occupation'       => 'nullable|string|max:255',
            'home_address'            => 'nullable|string|max:500',
            'parent_mobile'           => 'required|string|min:7|max:20',
            'parent_email'            => 'nullable|email|max:255',
            'psych_testing'           => 'nullable|string|max:255',
            'prescription_med'        => 'nullable|string|max:255',
            'med_explanation'         => 'nullable|string|max:1000',
            'family_physician'        => 'nullable|string|max:255',
            'physician_phone'         => 'nullable|string|max:20',
            'emergency_name'          => 'required|string|max:255',
            'emergency_relationship'  => 'required|string|max:255',
            'emergency_phone'         => 'required|string|max:20',
            'agreed_to_terms'         => 'accepted',
            'agreed_to_data_privacy'  => 'accepted',
            'school_year'             => 'required|string',
        ]);

        // File validation — only required if not already uploaded in a draft
        $applicant = $user->enrollmentApplicant;
        $request->validate([
            'photo_2x2'        => ($applicant?->photo_2x2_url   ? 'nullable' : 'required') . '|image|max:5120',
            'birth_cert'       => ($applicant?->birth_cert_url   ? 'nullable' : 'required') . '|image|max:5120',
            'report_card'      => ($applicant?->report_card_url  ? 'nullable' : 'required') . '|image|max:5120',
            'marriage_contract'=> 'nullable|image|max:5120',
            'medical_record'   => 'nullable|image|max:5120',
        ]);

        $validated['lrn']   = $validated['lrn'] ?: 'NA';
        $validated['email'] = $validated['email'] ?? null;

        $submitData = array_merge($validated, [
            'user_id'   => $user->id,
            'status'    => 'pending',
            'last_step' => 5,
        ]);

        unset($submitData['agreed_to_terms'], $submitData['agreed_to_data_privacy']);

        if ($applicant) {
            $applicant->update($submitData);
        } else {
            $applicant = EnrollmentApplicant::create($submitData);
        }

        $this->handleFileUploads($applicant, $request);

        return redirect()->route('enrollment.dashboard')
            ->with('success', 'Enrollment application submitted successfully!');
    }

    public function showSuccess()
    {
        return view('enrollment.success');
    }

    public function showDashboard()
    {
        $user      = Auth::user();
        $applicant = $user->enrollmentApplicant;
        $payment   = $applicant?->payment;
        $student   = $applicant?->student;

        return view('enrollment.dashboard', compact('user', 'applicant', 'payment', 'student'));
    }

    public function showPayment()
    {
        $user      = Auth::user();
        $applicant = $user->enrollmentApplicant;

        if (!$applicant || !in_array($applicant->status, ['pending', 'submitted', 'under_review', 'approved'])) {
            return redirect()->route('enrollment.dashboard')
                ->with('info', 'Please complete your enrollment application first.');
        }

        return view('enrollment.payment', compact('applicant'));
    }

    public function submitPayment(Request $request)
    {
        $user      = Auth::user();
        $applicant = $user->enrollmentApplicant;

        if (!$applicant) {
            return redirect()->route('enrollment.dashboard');
        }

        $request->validate([
            'method'  => 'required|in:gcash,maya,bdo',
            'receipt' => 'required|file|mimes:jpg,jpeg,png,pdf|max:5120',
        ]);

        // Store receipt
        $path = $request->file('receipt')->store('receipts/' . $applicant->id, 'public');

        // Create or update payment record
        $applicant->payment()->updateOrCreate(
            ['enrollment_applicant_id' => $applicant->id],
            [
                'user_id'                  => $user->id,
                'method'                   => $request->method,
                'amount'                   => 4000.00,
                'receipt_url'              => $path,
                'status'                   => 'pending',
                'paid_at'                  => now(),
            ]
        );

        return redirect()->route('enrollment.dashboard')
            ->with('success', 'Payment submitted! The Finance Office will verify it within 1–2 business days.');
    }

    public function showClosed()
    {
        return view('enrollment.closed');
    }

    private function handleFileUploads($applicant, Request $request): void
    {
        foreach (['photo_2x2', 'birth_cert', 'report_card', 'marriage_contract', 'medical_record'] as $key) {
            if ($request->hasFile($key)) {
                // Delete old file if replacing
                $oldPath = $applicant->{$key . '_url'};
                if ($oldPath) {
                    Storage::disk('public')->delete($oldPath);
                }
                $path = $request->file($key)->store('documents/' . $applicant->id, 'public');
                $applicant->update([$key . '_url' => $path]);
            }
        }
    }

    public function checkApplicationStatus()
    {
        $user      = Auth::user();
        $applicant = $user->enrollmentApplicant;

        return response()->json([
            'status'       => $applicant->status ?? 'not_found',
            'submitted_at' => $applicant->created_at ?? null,
            'percentage'   => $applicant?->completion_percentage ?? 0,
            'last_step'    => $applicant?->last_step ?? 1,
            'last_saved'   => $applicant?->updated_at?->diffForHumans() ?? null,
        ]);
    }
}
