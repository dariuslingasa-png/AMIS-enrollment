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
        'pending' => ['class' => 'is-submitted', 'label' => 'PENDING'],
        'submitted' => ['class' => 'is-submitted', 'label' => 'SUBMITTED'],
        'under_review' => ['class' => 'is-review', 'label' => 'ADMIN REVIEW'],
        'approved' => ['class' => 'is-complete', 'label' => 'APPROVED'],
        'rejected' => ['class' => 'is-rejected', 'label' => 'NEEDS FIXING'],
    ];

    $stepNames = [
        1 => 'Student information',
        2 => 'Parent or guardian details',
        3 => 'Emergency contact',
        4 => 'Health information',
        5 => 'Documents',
        6 => 'Review details',
        7 => 'Ready to complete',
    ];

    $learningModeLabels = [
        'face_to_face' => 'FACE TO FACE',
        'face-to-face' => 'FACE TO FACE',
        'face to face' => 'FACE TO FACE',
        'f2f' => 'FACE TO FACE',
        'flexible_learning_1st_shift' => 'FLEXIBLE LEARNING - 1ST SHIFT',
        'flexible_learning_2nd_shift' => 'FLEXIBLE LEARNING - 2ND SHIFT',
        'flexible_1st_shift' => 'FLEXIBLE LEARNING - 1ST SHIFT',
        'flexible_2nd_shift' => 'FLEXIBLE LEARNING - 2ND SHIFT',
    ];

    $submittedStatuses = ['pending', 'submitted', 'under_review', 'approved'];
    $submittedApplications = $applicants->filter(fn ($item) => in_array($item->status, $submittedStatuses, true))->values();
    $draftLikeApplications = $applicants->reject(fn ($item) => in_array($item->status, $submittedStatuses, true))->values();
    $paymentTarget = $submittedApplications->first(function ($item) {
        $docStatuses = $item->document_statuses ?? [];

        return !filled($item->payment?->receipt_url)
            && $item->payment?->status !== 'verified'
            && ($docStatuses['payment_proof'] ?? '') !== 'approved';
    });
    $hasDrafts = $draftLikeApplications->isNotEmpty();
    $canFinalize = $readyApplications->count() > 0 && !$hasDrafts;
@endphp

<style>
    .family-group-card {
        background: #fff;
        border: 1px solid #dbe3ee;
        border-radius: 18px;
        box-shadow: 0 12px 35px rgba(15, 23, 42, 0.06);
        padding: 1.15rem;
    }

    .family-group-header,
    .family-group-actions,
    .family-child-card,
    .family-child-main,
    .family-child-footer,
    .family-review-items,
    .family-card-actions,
    .family-draft-card {
        display: flex;
        gap: 0.75rem;
    }

    .family-group-header {
        align-items: flex-start;
        justify-content: space-between;
        margin-bottom: 1rem;
    }

    .family-group-title h3 {
        margin: 0.45rem 0 0;
        color: #0f172a;
        font-size: 1.15rem;
        font-weight: 900;
    }

    .family-group-title h3 span,
    .family-group-title p,
    .family-muted {
        color: #64748b;
    }

    .family-group-title p {
        margin: 0.45rem 0 0;
        font-size: 0.9rem;
        line-height: 1.45;
    }

    .family-section-kicker {
        display: inline-flex;
        width: fit-content;
        padding: 0.25rem 0.65rem;
        border-radius: 999px;
        background: #ecfdf5;
        color: #047857;
        font-size: 0.68rem;
        font-weight: 900;
        letter-spacing: 0.08em;
        text-transform: uppercase;
    }

    .family-group-actions,
    .family-review-items,
    .family-card-actions {
        align-items: center;
        flex-wrap: wrap;
    }

    .family-group-actions,
    .family-card-actions {
        justify-content: flex-end;
    }

    .family-list {
        display: grid;
        gap: 0.85rem;
    }

    .family-child-card {
        align-items: stretch;
        padding: 0.95rem;
        border: 1px solid #dbe3ee;
        border-radius: 18px;
        background: #fff;
    }

    .family-child-photo {
        width: 118px;
        height: 118px;
        flex: 0 0 118px;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        overflow: hidden;
        border: 1px solid #dbe3ee;
        border-radius: 16px;
        background: #f8fafc;
    }

    .family-child-photo img {
        width: 100%;
        height: 100%;
        object-fit: cover;
    }

    .family-photo-placeholder {
        color: #64748b;
        font-size: 0.68rem;
        font-weight: 900;
        letter-spacing: 0.08em;
        text-transform: uppercase;
    }

    .family-child-main {
        min-width: 0;
        flex: 1;
        flex-direction: column;
        justify-content: space-between;
    }

    .family-child-top {
        display: grid;
        grid-template-columns: minmax(0, 1fr) auto;
        gap: 0.75rem;
        align-items: flex-start;
    }

    .family-child-name {
        display: block;
        color: #0f172a;
        font-size: 1rem;
        font-weight: 950;
        line-height: 1.25;
        text-transform: uppercase;
    }

    .family-child-meta,
    .family-muted,
    .family-draft-title {
        font-size: 0.73rem;
        font-weight: 850;
        letter-spacing: 0.04em;
        text-transform: uppercase;
    }

    .family-child-meta {
        display: block;
        margin-top: 0.35rem;
        color: #64748b;
    }

    .family-child-footer {
        align-items: center;
        justify-content: space-between;
        flex-wrap: wrap;
        padding-top: 0.75rem;
        border-top: 1px solid #eef2f7;
    }

    .family-group-count,
    .family-add-yes,
    .family-finalize,
    .family-status-badge,
    .family-chip,
    .family-action {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        min-height: 34px;
        border-radius: 999px;
        font-size: 0.72rem;
        font-weight: 900;
        text-decoration: none;
        white-space: nowrap;
    }

    .family-group-count {
        background: #f0fdf4;
        border: 1px solid #bbf7d0;
        color: #047857;
        padding: 0.35rem 0.8rem;
    }

    .family-add-yes {
        border: 1px solid #bbf7d0;
        color: #047857;
        padding: 0.35rem 0.8rem;
    }

    .family-finalize,
    .family-action-primary {
        background: #059669;
        color: #fff;
        padding: 0.45rem 0.9rem;
    }

    .family-action-danger {
        border: 1px solid #fecaca;
        background: #fff;
        color: #dc2626;
        padding: 0.45rem 0.85rem;
    }

    .family-action-payment {
        background: #f59e0b;
        color: #fff;
        padding: 0.45rem 0.9rem;
    }

    .family-status-badge {
        padding: 0.32rem 0.75rem;
        text-transform: uppercase;
    }

    .family-status-badge.is-draft { background: #f1f5f9; color: #475569; }
    .family-status-badge.is-submitted { background: #dbeafe; color: #1d4ed8; }
    .family-status-badge.is-review { background: #fef3c7; color: #92400e; }
    .family-status-badge.is-complete { background: #dcfce7; color: #166534; }
    .family-status-badge.is-rejected { background: #fee2e2; color: #991b1b; }
    .family-status-badge.is-neutral { background: #f1f5f9; color: #334155; }

    .family-chip {
        min-height: 29px;
        gap: 0.35rem;
        padding: 0.3rem 0.7rem;
        border: 1px solid #fde68a;
        background: #fffaf0;
        color: #78350f;
    }

    .family-chip.is-missing {
        border-color: #fecaca;
        background: #fff1f2;
        color: #991b1b;
    }

    .family-dot {
        width: 7px;
        height: 7px;
        border-radius: 50%;
        background: #f59e0b;
    }

    .family-x {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        width: 14px;
        height: 14px;
        border-radius: 50%;
        background: #fee2e2;
        color: #991b1b;
        font-size: 0.68rem;
        font-weight: 900;
        line-height: 1;
    }

    .family-draft-section {
        margin-top: 1rem;
        padding-top: 1rem;
        border-top: 1px solid #e5e7eb;
    }

    .family-payment-action-row {
        display: flex;
        justify-content: flex-end;
        margin-top: 1rem;
    }

    .family-draft-title {
        display: block;
        margin-bottom: 0.7rem;
        color: #64748b;
    }

    .family-draft-card {
        align-items: center;
        justify-content: space-between;
        flex-wrap: wrap;
        padding: 0.85rem 0.95rem;
        border: 1px dashed #cbd5e1;
        border-radius: 16px;
        background: #f8fafc;
    }

    .family-draft-name {
        color: #0f172a;
        font-size: 0.92rem;
        font-weight: 950;
        text-transform: uppercase;
    }

    .family-card-actions form {
        margin: 0;
    }

    .family-card-actions button {
        border: 0;
        cursor: pointer;
        font-family: inherit;
    }

    @media (max-width: 760px) {
        .family-group-header,
        .family-child-card,
        .family-child-footer,
        .family-draft-card {
            flex-direction: column;
        }

        .family-child-top {
            grid-template-columns: 1fr;
        }

        .family-child-photo,
        .family-group-actions,
        .family-card-actions,
        .family-add-yes,
        .family-finalize {
            width: 100%;
        }

        .family-child-photo {
            height: 190px;
            flex-basis: auto;
        }

        .family-card-actions {
            justify-content: flex-start;
        }
    }
</style>

<section class="family-group-card">
    <div class="family-group-header">
        <div class="family-group-title">
            <span class="family-section-kicker">Enrollment Applications</span>
            <h3>Enrollment Applications <span>SY 2026-2027</span></h3>
            <p>Submitted applications are shown first. Drafts stay simple at the bottom.</p>
        </div>

        <div class="family-group-actions">
            <span class="family-group-count">{{ $applicants->count() }} Enrollment {{ \Illuminate\Support\Str::plural('Application', $applicants->count()) }}</span>

            @if ($canFinalize)
                <a href="{{ route('enrollment.finalize.preview') }}" class="family-finalize">Finalize Enrollment</a>
            @endif
        </div>
    </div>

    @if ($submittedApplications->isNotEmpty())
        <div class="family-list">
            @foreach ($submittedApplications as $child)
                @php
                    $childStatus = $statusStyles[$child->status] ?? ['class' => 'is-neutral', 'label' => strtoupper(str_replace('_', ' ', $child->status ?? 'draft'))];
                    $childName = trim(($child->first_name ?? '') . ' ' . ($child->middle_name ?? '') . ' ' . ($child->last_name ?? '')) ?: 'New applicant draft';
                    $docStatuses = $child->document_statuses ?? [];
                    $hasPaymentProof = filled($child->payment?->receipt_url)
                        || $child->payment?->status === 'verified'
                        || ($docStatuses['payment_proof'] ?? '') === 'approved';
                    $requiredDocsApproved = ($docStatuses['photo_2x2'] ?? '') === 'approved'
                        && (
                            strcasecmp((string) $child->student_type, 'Old') === 0
                            || (
                                ($docStatuses['birth_cert'] ?? '') === 'approved'
                                && (($docStatuses['report_card'] ?? '') === 'approved' || ($docStatuses['affidavit'] ?? '') === 'approved')
                            )
                        );
                    $modeKey = strtolower((string) ($child->learning_mode ?? $child->enrollment_type ?? ''));
                    $learningMode = $learningModeLabels[$modeKey] ?? strtoupper(str_replace('_', ' ', $modeKey ?: 'LEARNING MODE PENDING'));
                    $paymentLabel = ($child->payment?->status ?? null) === 'verified' ? 'Paid Enrollment Fee' : ($hasPaymentProof ? 'Payment Proof' : 'Missing Payment Proof');
                    $documentsLabel = $requiredDocsApproved ? 'Documents Approved' : 'Documents Pending';
                @endphp

                <article class="family-child-card">
                    <div class="family-child-photo">
                        @if ($child->photo_2x2_url)
                            <img src="{{ asset('storage/' . $child->photo_2x2_url) }}" alt="{{ $childName }}">
                        @else
                            <span class="family-photo-placeholder">No Photo</span>
                        @endif
                    </div>

                    <div class="family-child-main">
                        <div class="family-child-top">
                            <div>
                                <span class="family-child-name">{{ $loop->iteration }}) {{ $childName }}</span>
                                <span class="family-child-meta">{{ strtoupper($child->grade_level ?? 'NO GRADE') }} | {{ $learningMode }} | SY {{ $child->school_year ?? '2026-2027' }}</span>
                            </div>

                            <span class="family-status-badge {{ $childStatus['class'] }}">{{ $childStatus['label'] }}</span>
                        </div>

                        <div class="family-child-footer">
                            <div class="family-review-items">
                                <span class="family-chip"><span class="family-dot"></span>Registration Form</span>
                                <span class="family-chip {{ $requiredDocsApproved ? '' : 'is-missing' }}">
                                    @if ($requiredDocsApproved)
                                        <span class="family-dot"></span>
                                    @else
                                        <span class="family-x">x</span>
                                    @endif
                                    {{ $documentsLabel }}
                                </span>
                                <span class="family-chip {{ $hasPaymentProof ? '' : 'is-missing' }}">
                                    @if ($hasPaymentProof)
                                        <span class="family-dot"></span>
                                    @else
                                        <span class="family-x">x</span>
                                    @endif
                                    {{ $paymentLabel }}
                                </span>
                            </div>

                            <span class="family-muted">Updated {{ $child->updated_at?->diffForHumans() ?? 'not saved' }}</span>
                        </div>
                    </div>
                </article>
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
                    @php
                        $childNumber = $submittedApplications->count() + $loop->iteration;
                        $childStatus = $statusStyles[$child->status] ?? ['class' => 'is-neutral', 'label' => strtoupper(str_replace('_', ' ', $child->status ?? 'draft'))];
                        $childName = trim(($child->first_name ?? '') . ' ' . ($child->middle_name ?? '') . ' ' . ($child->last_name ?? '')) ?: 'New applicant draft';
                        $progress = in_array($child->status, ['ready_for_submission', 'approved'], true)
                            ? 100
                            : (int) ($child->completion_percentage ?? 0);
                        $step = min(max((int) ($child->last_step ?? 1), 1), 7);
                        $actionLabel = $child->status === 'rejected'
                            ? 'Re-edit Form'
                            : ($child->status === 'ready_for_submission' ? 'Review' : 'Continue Draft');
                        $modeKey = strtolower((string) ($child->learning_mode ?? $child->enrollment_type ?? ''));
                        $learningMode = $learningModeLabels[$modeKey] ?? strtoupper(str_replace('_', ' ', $modeKey ?: 'LEARNING MODE PENDING'));
                    @endphp

                    <article class="family-child-card">
                        <div class="family-child-photo">
                            @if ($child->photo_2x2_url)
                                <img src="{{ asset('storage/' . $child->photo_2x2_url) }}" alt="{{ $childName }}">
                            @else
                                <span class="family-photo-placeholder">No Photo</span>
                            @endif
                        </div>

                        <div class="family-child-main">
                            <div class="family-child-top">
                                <div>
                                    <span class="family-child-name">{{ $childNumber }}) {{ $childName }}</span>
                                    <span class="family-child-meta">{{ strtoupper($child->grade_level ?? 'NO GRADE') }} | {{ $learningMode }} | SY {{ $child->school_year ?? '2026-2027' }}</span>
                                </div>

                                <span class="family-status-badge {{ $childStatus['class'] }}">{{ $childStatus['label'] }}{{ $progress < 100 ? ' - ' . $progress . '%' : '' }}</span>
                            </div>

                            <div class="family-child-footer">
                                <span class="family-muted">{{ $stepNames[$step] }}</span>

                                <div class="family-card-actions">
                                    <form method="POST" action="{{ route('enrollment.draft.discard') }}" data-clear-draft-form onsubmit="return confirm('Delete this enrollment? This cannot be undone.')">
                                        @csrf
                                        @method('DELETE')
                                        <input type="hidden" name="applicant_id" value="{{ $child->id }}">
                                        <button type="submit" class="family-action family-action-danger">Delete</button>
                                    </form>

                                    <a href="{{ route('enrollment.form.child', $child) }}" class="family-action family-action-primary">{{ $actionLabel }}</a>
                                </div>
                            </div>
                        </div>
                    </article>
                @endforeach
            </div>
        </div>
    @endif

    @if ($submittedApplications->isEmpty() && $canAddAnotherChild)
        <div class="family-draft-section">
            <a href="{{ route('enrollment.new') }}" class="family-add-yes">Create New Enrollment</a>
        </div>
    @elseif ($canAddAnotherChild)
        <div class="family-draft-section">
            <a href="{{ route('enrollment.new') }}" class="family-add-yes">Add Another Child</a>
        </div>
    @endif
</section>
