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
        'ready_for_submission' => ['class' => 'is-complete', 'label' => 'COMPLETED'],
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

    $hasDrafts = $draftApplications->count() > 0;
    $canFinalize = $readyApplications->count() > 0 && !$hasDrafts;
    $allSubmitted = $applicants->every(fn ($item) => in_array($item->status, ['pending', 'submitted', 'under_review', 'approved'], true));
@endphp

<style>
    .family-group-card {
        border-radius: 18px;
        padding: 1.15rem;
    }

    .family-child-card.is-open {
        position: relative;
        display: grid;
        grid-template-columns: 132px minmax(0, 1fr) minmax(170px, auto);
        gap: 1rem;
        min-height: 0;
        padding: 1rem;
        border-color: #dbe3ee;
        border-radius: 16px;
        box-shadow: 0 14px 35px rgba(15, 23, 42, 0.08);
        overflow: hidden;
    }

    .family-child-card.is-open .sibling-card-header {
        display: contents;
        padding: 0;
    }

    .family-child-card.is-open .sibling-card-avatar {
        position: static;
        inset: auto;
        grid-column: 1;
        grid-row: 1;
        width: 132px;
        min-width: 132px;
        height: 132px;
        border: 1px solid #dbe3ee;
        border-radius: 14px;
        background: #f8fafc;
    }

    .family-child-card.is-open .sibling-card-info {
        grid-column: 2;
        grid-row: 1;
        align-self: start;
        gap: 0.35rem;
        padding-top: 0.15rem;
    }

    .family-child-card.is-open .sibling-card-name {
        color: #0f172a;
        font-size: 1.05rem;
        letter-spacing: 0;
    }

    .family-child-card.is-open .sibling-card-meta {
        color: #64748b;
        font-size: 0.82rem;
        font-weight: 700;
    }

    .family-child-card.is-open .sibling-card-right {
        position: absolute;
        top: 1rem;
        right: 1rem;
        justify-content: flex-end;
        padding: 0;
    }

    .family-child-card.is-open .sibling-card-body {
        grid-column: 1 / -1;
        grid-row: 2;
        gap: 0.8rem;
        padding: 0;
    }

    .family-child-card.is-open .sibling-card-badge {
        text-transform: uppercase;
        letter-spacing: 0.035em;
    }

    .family-child-card.is-open .sibling-progress-meta {
        justify-content: space-between;
    }

    .family-child-card.is-open .sibling-review-checklist {
        display: grid;
        gap: 0.55rem;
        padding: 0.85rem 1rem;
        border: 1px solid #fed7aa;
        border-radius: 12px;
        background: linear-gradient(180deg, #fffaf0, #fff);
    }

    .family-child-card.is-open .review-checklist-title {
        margin: 0;
        color: #92400e;
        font-size: 0.78rem;
        letter-spacing: 0.02em;
        text-transform: uppercase;
    }

    .family-child-card.is-open .review-checklist-copy {
        color: #64748b;
        font-size: 0.78rem;
        font-weight: 600;
        line-height: 1.45;
        margin: -0.2rem 0 0;
    }

    .family-child-card.is-open .review-checklist-items {
        gap: 0.45rem;
    }

    .family-child-card.is-open .review-checklist-item {
        min-height: 30px;
        padding: 0.32rem 0.62rem;
        border: 1px solid #fde68a;
        border-radius: 999px;
        background: #ffffff;
        color: #78350f;
        font-size: 0.76rem;
        font-weight: 800;
    }

    .family-child-card.is-open .review-checklist-item.is-missing {
        border-color: #fecaca;
        background: #fff1f2;
        color: #991b1b;
    }

    .family-child-card.is-open .review-x-mark {
        background: #fecaca;
        color: #991b1b;
    }

    .family-child-card.is-open .review-dot-pulse {
        background: #f59e0b;
    }

    .family-child-card.is-open .sibling-card-actions {
        justify-content: flex-end;
        padding-top: 0.05rem;
    }

    .family-child-card.is-open .sibling-btn-payment {
        min-height: 42px;
        padding-inline: 1.15rem;
        border-color: #f59e0b;
        border-radius: 10px;
        background: linear-gradient(135deg, #f59e0b, #d97706);
        box-shadow: 0 10px 18px rgba(245, 158, 11, 0.18);
    }

    @media (max-width: 760px) {
        .family-child-card.is-open {
            grid-template-columns: 76px minmax(0, 1fr);
            gap: 0.85rem;
            padding: 0.85rem;
        }

        .family-child-card.is-open .sibling-card-avatar {
            width: 76px;
            min-width: 76px;
            height: 76px;
            grid-row: 1;
        }

        .family-child-card.is-open .sibling-card-right {
            top: 0.85rem;
            right: 0.85rem;
            align-items: flex-end;
        }

        .family-child-card.is-open .sibling-card-body {
            grid-column: 1 / -1;
            grid-row: 3;
        }
    }
</style>

<section class="family-group-card">
    <div class="family-group-header">
        <div class="family-group-title">
            <span class="family-section-kicker">Enrollment Applications</span>
            <h3>Enrollment Applications <span>SY 2026-2027</span></h3>
            <p>Review each applicant below. Open a card to continue, check progress, or see what happens next.</p>
        </div>

        <div class="family-group-actions">
            <span class="family-group-count">{{ $applicants->count() }} Enrollment {{ \Illuminate\Support\Str::plural('Application', $applicants->count()) }}</span>

            @if ($canAddAnotherChild)
                <a href="{{ route('enrollment.new') }}" class="family-add-yes">
                    <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path d="M12 5v14"/><path d="M5 12h14"/></svg>
                    Add Another Child
                </a>
            @endif

            @if ($canFinalize)
                <a href="{{ route('enrollment.finalize.preview') }}" class="family-finalize">Finalize Enrollment</a>
            @endif
        </div>
    </div>

    <div class="family-child-list">
        @foreach ($applicants as $child)
            @php
                $childStatus = $statusStyles[$child->status] ?? ['class' => 'is-neutral', 'label' => strtoupper(str_replace('_', ' ', $child->status ?? 'draft'))];
                $childName = trim(($child->first_name ?? '') . ' ' . ($child->middle_name ?? '') . ' ' . ($child->last_name ?? '')) ?: 'New applicant draft';
                $isSelectedChild = $applicant && (int) $applicant->id === (int) $child->id;
                $isEditableChild = in_array($child->status, ['draft', 'ready_for_submission', 'rejected'], true);
                $isDraftChild = $child->status === 'draft';
                $progress = in_array($child->status, ['ready_for_submission', 'pending', 'submitted', 'under_review', 'approved'], true)
                    ? 100
                    : (int) ($child->completion_percentage ?? 0);
                $step = min(max((int) ($child->last_step ?? 1), 1), 7);
                $docStatuses = $child->document_statuses ?? [];
                $hasPaymentProof = filled($child->payment?->receipt_url)
                    || $child->payment?->status === 'verified'
                    || ($docStatuses['payment_proof'] ?? '') === 'approved';
                $rejectedItems = collect([
                    'registration_form' => 'Registration form',
                    'documents' => 'Documents',
                    'photo_2x2' => '2x2 picture',
                    'birth_cert' => 'Birth certificate',
                    'report_card' => 'Report card',
                    'marriage_contract' => 'Marriage contract',
                    'medical_record' => 'Medical record',
                    'affidavit' => 'Affidavit',
                    'payment_proof' => 'Payment proof',
                ])->filter(fn ($label, $key) => ($docStatuses[$key] ?? '') === 'rejected')->values();
            @endphp

            <div class="family-child-card is-open {{ $isSelectedChild ? 'is-selected' : '' }} {{ $child->status === 'approved' ? 'is-approved' : '' }}">
                <div class="sibling-card-header">
                    <div class="sibling-card-avatar">
                        @if ($child->photo_2x2_url)
                            <img src="{{ asset('storage/' . $child->photo_2x2_url) }}" alt="{{ $childName }}">
                        @else
                            <svg width="34" height="34" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path d="M20 21v-2a4 4 0 00-4-4H8a4 4 0 00-4 4v2"/><circle cx="12" cy="7" r="4"/></svg>
                        @endif
                    </div>

                    <div class="sibling-card-info">
                        <span class="sibling-card-name">{{ $loop->iteration }}) {{ $childName }}</span>
                        <span class="sibling-card-meta">{{ $child->student_type ? strtoupper($child->student_type) : '' }}{{ $child->student_type ? ' • ' : '' }}{{ strtoupper($child->grade_level ?? 'NO GRADE') }} • SY {{ $child->school_year ?? '2026-2027' }}</span>
                    </div>

                    <div class="sibling-card-right">
                        @if ($progress >= 100 && $child->status === 'ready_for_submission')
                            <span class="sibling-card-badge {{ $childStatus['class'] }}">COMPLETED - 100%</span>
                        @elseif ($progress >= 100)
                            <span class="sibling-card-badge {{ $childStatus['class'] }}">{{ $childStatus['label'] }}</span>
                        @else
                            <span class="sibling-card-badge {{ $childStatus['class'] }}">{{ $childStatus['label'] }} - {{ $progress }}%</span>
                        @endif
                    </div>

                </div>

                <div class="sibling-card-body">
                    <div class="sibling-card-progress">
                        @if ($progress < 100)
                            <div class="sibling-progress-bar">
                                <div class="sibling-progress-fill" style="width:{{ $progress }}%;"></div>
                            </div>
                        @endif
                        <div class="sibling-progress-meta">
                            @if ($child->status !== 'approved')
                                <span>{{ $stepNames[$step] }}</span>
                            @else
                                <span></span>
                            @endif
                            <span>{{ $child->updated_at?->diffForHumans() ?? 'Not saved' }}</span>
                        </div>
                    </div>

                    @if ($child->status === 'approved')
                        <div class="sibling-approved-message">
                            <div class="sibling-approved-title">Assalamu Alaikum!</div>
                            <p>Congratulations and welcome to Al Munawwara Islamic School.</p>
                            <p>Your enrollment application for SY {{ $child->school_year ?? '2026-2027' }} has been successfully approved.</p>
                            <p>Please check your personal email inbox for important enrollment details, class schedule, and further school updates.</p>
                            <p>Thank you and welcome to the AMIS family.</p>
                        </div>
                    @endif

                    @if (in_array($child->status, ['pending', 'submitted', 'under_review'], true))
                        <div class="sibling-review-checklist">
                            <div class="review-checklist-title">Admin is reviewing<span class="review-dots"><span>.</span><span>.</span><span>.</span></span></div>
                            <p class="review-checklist-copy">We are checking the registration form, documents, and payment proof for this applicant.</p>
                            <div class="review-checklist-items">
                                <div class="review-checklist-item"><span class="review-dot-pulse"></span>Registration Form</div>
                                <div class="review-checklist-item"><span class="review-dot-pulse"></span>Documents</div>
                                <div class="review-checklist-item {{ $hasPaymentProof ? '' : 'is-missing' }}">
                                    @if ($hasPaymentProof)
                                        <span class="review-dot-pulse"></span>
                                    @else
                                        <span class="review-x-mark">x</span>
                                    @endif
                                    {{ $hasPaymentProof ? 'Payment Proof' : 'Missing Payment Proof' }}
                                </div>
                            </div>
                        </div>
                    @endif

                    @if ($child->status === 'rejected')
                        <div class="sibling-review-checklist" style="background:#fff1f2;border-color:#fecdd3;">
                            <div class="review-checklist-title" style="color:#991b1b;">Why this needs fixing</div>
                            @if ($child->review_remarks)
                                <p style="color:#7f1d1d;font-size:0.82rem;font-weight:650;line-height:1.5;margin:0 0 0.65rem;">{{ $child->review_remarks }}</p>
                            @endif
                            @if ($rejectedItems->isNotEmpty())
                                <div class="review-checklist-items">
                                    @foreach ($rejectedItems as $item)
                                        <div class="review-checklist-item is-missing"><span class="review-x-mark">x</span>{{ $item }}</div>
                                    @endforeach
                                </div>
                            @elseif (!$child->review_remarks)
                                <p style="color:#7f1d1d;font-size:0.82rem;font-weight:650;line-height:1.5;margin:0;">Please review your application details and resubmit.</p>
                            @endif
                        </div>
                    @endif

                    <div class="sibling-card-actions {{ $child->status === 'ready_for_submission' ? 'has-three-actions' : '' }}">
                        @if ($isEditableChild)
                            <form method="POST" action="{{ route('enrollment.draft.discard') }}" data-clear-draft-form
                                onsubmit="return confirm('Delete this enrollment? This cannot be undone.')">
                                @csrf
                                @method('DELETE')
                                <input type="hidden" name="applicant_id" value="{{ $child->id }}">
                                <button type="submit" class="sibling-btn-danger">Delete</button>
                            </form>

                            @if ($child->status === 'ready_for_submission')
                                <a href="{{ route('enrollment.form.child', $child) }}" class="sibling-btn-edit">Edit</a>
                            @endif

                            <a href="{{ route('enrollment.form.child', $child) }}" class="sibling-btn-primary">
                                {{ $isDraftChild ? 'Continue Draft' : ($child->status === 'rejected' ? 'Re-edit Form' : 'Review') }}
                                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><polyline points="9 18 15 12 9 6"/></svg>
                            </a>
                        @endif

                        @if (!$hasPaymentProof && in_array($child->status, ['pending', 'submitted', 'under_review', 'approved'], true))
                            <a href="{{ route('enrollment.payment', ['applicant' => $child->id]) }}" class="sibling-btn-payment">
                                Upload Payment Proof
                            </a>
                        @endif
                    </div>
                </div>
            </div>
        @endforeach
    </div>

    @if (!$allSubmitted || !$canAddAnotherChild)
        <div class="family-draft-progress">
            <div class="family-draft-copy">
                <span class="family-section-kicker">Next Step</span>
                <h4>Draft Progress</h4>
                <p>
                    {{ $readyApplications->count() }} enrollment {{ \Illuminate\Support\Str::plural('application', $readyApplications->count()) }} completed.
                    @if ($hasDrafts)
                        @php
                            $draftNames = $draftApplications->pluck('first_name')->filter()->toArray();
                        @endphp
                        Complete {{ implode(', ', $draftNames) ?: $draftApplications->count() . ' draft(s)' }} first.
                    @elseif ($canFinalize)
                        You can finalize once you are ready.
                    @else
                        Continue the current application to unlock the next step.
                    @endif
                </p>

                @if ($hasDrafts)
                    <div class="family-finalize-helper">
                        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" aria-hidden="true"><rect x="4" y="11" width="16" height="9" rx="2"/><path d="M8 11V8a4 4 0 018 0v3"/></svg>
                        Complete all applications to unlock finalize.
                    </div>
                @endif
            </div>

            @if (!$canAddAnotherChild && !$canFinalize)
                <div class="family-add-actions">
                    <span class="family-add-disabled">Complete current application first</span>
                </div>
            @endif
        </div>
    @endif
</section>
