@props([
    'user',
    'applicants',
    'applicant' => null,
    'canAddAnotherChild' => false,
    'readyApplications' => collect(),
    'draftApplications' => collect(),
])

@php
    $statusStyles = [
        'draft' => ['class' => 'is-draft', 'label' => 'DRAFT'],
        'ready_for_submission' => ['class' => 'is-complete', 'label' => 'READY TO COMPLETE'],
        'pending' => ['class' => 'is-review', 'label' => 'PENDING'],
        'submitted' => ['class' => 'is-review', 'label' => 'PENDING'],
        'under_review' => ['class' => 'is-review', 'label' => 'PENDING'],
        'approved' => ['class' => 'is-complete', 'label' => 'APPROVED BY ADMIN'],
        'rejected' => ['class' => 'is-rejected', 'label' => 'NEEDS FIXING'],
    ];

    $stepNames = [
        1 => 'Enrollment setup',
        2 => 'Student information',
        3 => 'Address & contact',
        4 => 'Parent or guardian details',
        5 => 'Medical & emergency',
        6 => 'Documents',
    ];

    $learningModeLabels = [
        'face_to_face' => 'FACE TO FACE',
        'face-to-face' => 'FACE TO FACE',
        'face to face' => 'FACE TO FACE',
        'f2f' => 'FACE TO FACE',
        'flexible_learning_1st_shift' => 'FLEXIBLE LEARNING - 1ST SHIFT',
        'flexible_learning_2nd_shift' => 'FLEXIBLE LEARNING - 2ND SHIFT',
        'flexible_learning_3rd_shift' => 'FLEXIBLE LEARNING - 3RD SHIFT',
        'flexible_learning_4th_shift' => 'FLEXIBLE LEARNING - 4TH SHIFT',
        'flexible_1st_shift' => 'FLEXIBLE LEARNING - 1ST SHIFT',
        'flexible_2nd_shift' => 'FLEXIBLE LEARNING - 2ND SHIFT',
        'flexible_3rd_shift' => 'FLEXIBLE LEARNING - 3RD SHIFT',
        'flexible_4th_shift' => 'FLEXIBLE LEARNING - 4TH SHIFT',
        '3rd_shift' => 'FLEXIBLE LEARNING - 3RD SHIFT',
        '4th_shift' => 'FLEXIBLE LEARNING - 4TH SHIFT',
    ];

    $submittedStatuses = ['ready_for_submission', 'pending', 'submitted', 'under_review', 'approved'];
    $submittedApplications = $applicants->filter(fn ($item) => in_array($item->status, $submittedStatuses, true))->values();
    $draftLikeApplications = $applicants->reject(fn ($item) => in_array($item->status, $submittedStatuses, true))->values();

    // Find family-wide payment status
    $familyPayment = $applicants->map(fn($item) => $item->payment)->filter()->first();
    $hasFamilyPayment = $familyPayment && filled($familyPayment->receipt_url);

    $paymentTarget = !$hasFamilyPayment ? $submittedApplications->first(function ($item) {
        $docStatuses = $item->document_statuses ?? [];

        return !filled($item->payment?->receipt_url)
            && $item->payment?->status !== 'verified'
            && ($docStatuses['payment_proof'] ?? '') !== 'approved';
    }) : null;

    $hasDrafts = $applicants->contains(fn($item) => in_array($item->status, ['draft', 'rejected'], true));
    $canFinalize = $readyApplications->count() > 0 && !$hasDrafts;

    // Detect sibling to duplicate from
    $siblingSource = $applicants->first(fn ($item) => in_array($item->status, $submittedStatuses, true));
    $siblingName = $siblingSource ? strtoupper(trim(($siblingSource->first_name ?? '') . ' ' . ($siblingSource->last_name ?? ''))) : null;
    $siblingModeKey = $siblingSource ? strtolower((string) ($siblingSource->learning_mode ?? $siblingSource->enrollment_type ?? '')) : null;
    $siblingLearningMode = $siblingSource ? ($learningModeLabels[$siblingModeKey] ?? strtoupper(str_replace('_', ' ', $siblingModeKey ?: 'LEARNING MODE PENDING'))) : null;
    
    $siblingAddress = null;
    if ($siblingSource) {
        $parts = array_filter(array_map('trim', [
            $siblingSource->street_address ?? '',
            $siblingSource->city ?? '',
            $siblingSource->state_province ?? '',
            $siblingSource->country ?? '',
        ]));
        $siblingAddress = strtoupper(implode(', ', $parts));
    }
    
    $siblingFather = $siblingSource && trim(($siblingSource->father_first_name ?? '') . ' ' . ($siblingSource->father_last_name ?? '')) ? strtoupper(trim(($siblingSource->father_first_name ?? '') . ' ' . ($siblingSource->father_last_name ?? ''))) : null;
    $siblingMother = $siblingSource && trim(($siblingSource->mother_first_name ?? '') . ' ' . ($siblingSource->mother_last_name ?? '')) ? strtoupper(trim(($siblingSource->mother_first_name ?? '') . ' ' . ($siblingSource->mother_last_name ?? ''))) : null;
    $siblingEmergency = $siblingSource ? strtoupper(trim(($siblingSource->emergency_name ?? '') . ' (' . ($siblingSource->emergency_relationship ?? '') . ') · ' . ($siblingSource->emergency_phone ?? ''))) : null;
@endphp

@include('components.dashboard.family-group-styles')

<section class="family-group-card" x-data="{ showDuplicateModal: false }">
    <div class="family-group-header">
        <div class="family-group-title">
            <span class="family-section-kicker">Enrollment Applications</span>
            <h3>Enrollment Applications <span>SY 2026-2027</span></h3>
            <p>Submitted applications are shown first. Drafts stay simple at the bottom.</p>
        </div>

        <div class="family-group-actions">
            <span class="family-group-count">{{ $applicants->count() }} ENROLLMENT {{ strtoupper(\Illuminate\Support\Str::plural('Application', $applicants->count())) }}</span>
        </div>
    </div>

    @if ($submittedApplications->isNotEmpty())
        <div class="family-list">
            @foreach ($submittedApplications as $child)
                @include('components.dashboard.submitted-card', [
                    'child' => $child,
                    'statusStyles' => $statusStyles,
                    'learningModeLabels' => $learningModeLabels,
                    'hasFamilyPayment' => $hasFamilyPayment,
                    'familyPayment' => $familyPayment,
                    'applicantsCount' => $applicants->count(),
                ])
            @endforeach
        </div>

        @if ($paymentTarget)
            <div class="family-payment-action-row">
                <a href="{{ route('enrollment.payment', ['applicant' => $paymentTarget->id]) }}" class="family-action family-action-payment">Upload Payment Proof</a>
            </div>
        @endif
    @endif

    @if ($draftLikeApplications->isNotEmpty())
        <div class="family-draft-section">
            <span class="family-draft-title">Draft Applications</span>

            <div class="family-list">
                @foreach ($draftLikeApplications as $child)
                    @include('components.dashboard.draft-card', [
                        'child' => $child,
                        'submittedApplications' => $submittedApplications,
                        'statusStyles' => $statusStyles,
                        'learningModeLabels' => $learningModeLabels,
                        'stepNames' => $stepNames,
                    ])
                @endforeach
            </div>
        </div>
    @endif

    @if ($submittedApplications->isEmpty() && $canAddAnotherChild)
        <div class="family-draft-section">
            <a href="{{ route('enrollment.new') }}" @if ($siblingSource) @click.prevent="showDuplicateModal = true" @endif class="family-add-yes">Create New Enrollment</a>
        </div>
    @elseif ($canAddAnotherChild)
        <div class="family-draft-section">
            <a href="{{ route('enrollment.new') }}" @if ($siblingSource) @click.prevent="showDuplicateModal = true" @endif class="family-add-yes">Add Another Child</a>
        </div>
    @endif

    @if ($siblingSource)
    <!-- Sibling Duplication Modal Overlay -->
    <div x-show="showDuplicateModal" x-cloak class="duplicate-modal-overlay" style="display: none;" x-transition:enter="transition ease-out duration-300" x-transition:enter-start="opacity-0" x-transition:enter-end="opacity-100" x-transition:leave="transition ease-in duration-200" x-transition:leave-start="opacity-100" x-transition:leave-end="opacity-0">
        <div class="duplicate-modal-container" @click.away="showDuplicateModal = false" x-transition:enter="transition ease-out duration-300" x-transition:enter-start="opacity-0 scale-95" x-transition:enter-end="opacity-100 scale-100" x-transition:leave="transition ease-in duration-200" x-transition:leave-start="opacity-100 scale-100" x-transition:leave-end="opacity-0 scale-95">
            <div class="duplicate-modal-header">
                <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="#059669" stroke-width="2.5"><path d="M17 21v-2a4 4 0 00-4-4H5a4 4 0 00-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M23 21v-2a4 4 0 00-3-3.87"/><path d="M16 3.13a4 4 0 010 7.75"/></svg>
                <h3>Duplicate Sibling Details?</h3>
            </div>
            
            <p class="duplicate-modal-description">We detected an existing application for <strong>{{ $siblingName }}</strong>. Would you like to automatically copy their details to start this new application faster?</p>
            
            <div class="duplicate-modal-linked-data">
                <div class="linked-data-title">Linked Data to Copy:</div>
                <ul class="linked-data-list">
                    <li>
                        <strong>Learning Modality:</strong>
                        <span>{{ $siblingLearningMode }}</span>
                    </li>
                    @if ($siblingAddress)
                    <li>
                        <strong>Address:</strong>
                        <span>{{ $siblingAddress }}</span>
                    </li>
                    @endif
                    @if ($siblingFather || $siblingMother)
                    <li>
                        <strong>Parents:</strong>
                        <span>
                            @if ($siblingFather) FATHER: {{ $siblingFather }} @endif
                            @if ($siblingFather && $siblingMother) · @endif
                            @if ($siblingMother) MOTHER: {{ $siblingMother }} @endif
                        </span>
                    </li>
                    @endif
                    @if ($siblingEmergency)
                    <li>
                        <strong>Emergency Contact:</strong>
                        <span>{{ $siblingEmergency }}</span>
                    </li>
                    @endif
                </ul>
            </div>
            
            <div class="duplicate-modal-actions">
                <button type="button" class="duplicate-btn-cancel" @click="showDuplicateModal = false">CANCEL</button>
                <a href="{{ route('enrollment.new') }}" class="duplicate-btn-fresh">NO, START FRESH</a>
                <a href="{{ route('enrollment.new', ['duplicate' => 1]) }}" class="duplicate-btn-confirm">YES, COPY SIBLING DETAILS</a>
            </div>
        </div>
    </div>
    @endif
</section>
