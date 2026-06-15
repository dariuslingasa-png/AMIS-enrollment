@php
    $childStatus = $statusStyles[$child->status] ?? ['class' => 'is-neutral', 'label' => strtoupper(str_replace('_', ' ', $child->status ?? 'draft'))];
    $childName = trim(($child->first_name ?? '') . ' ' . ($child->middle_name ?? '') . ' ' . ($child->last_name ?? '')) ?: 'New applicant draft';
    $docStatuses = $child->document_statuses ?? [];
    $hasLocalPaymentProof = filled($child->payment?->receipt_url)
        || $child->payment?->status === 'verified'
        || ($docStatuses['payment_proof'] ?? '') === 'approved';
    $hasPaymentProof = $hasLocalPaymentProof || (!in_array($child->status, ['draft', 'ready_for_submission', 'rejected'], true) && $hasFamilyPayment);
    $isVerified = ($child->payment?->status ?? null) === 'verified'
        || (!in_array($child->status, ['draft', 'ready_for_submission', 'rejected'], true) && ($familyPayment?->status ?? null) === 'verified')
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
    $paymentLabel = $isVerified ? 'Paid Enrollment Fee' : ($hasPaymentProof ? 'Payment Proof' : 'Missing Payment Proof');
    
    // New/Old student affidavit vs report card logic
    $hasReportCard = filled($child->report_card_url);
    $hasAffidavit = filled($child->affidavit_url);
    $isNewStudent = strcasecmp((string) $child->student_type, 'Old') !== 0;

    if ($isNewStudent && !$hasReportCard && $hasAffidavit) {
        $documentsLabel = 'Affidavit Signed';
        $showDocsAsApproved = true;
    } else {
        $documentsLabel = $requiredDocsApproved ? 'Documents Approved' : 'Documents Pending';
        $showDocsAsApproved = $requiredDocsApproved;
    }
@endphp

<article class="family-child-card">
    <div class="family-child-photo" x-data="{ imgLoaded: false, imgError: false }" style="position: relative; overflow: hidden;">
        @if ($child->photo_2x2_url)
            <div x-show="!imgLoaded && !imgError" class="family-photo-placeholder animate-pulse" style="position: absolute; inset: 0; background: #e2e8f0;"></div>
            <img x-show="!imgError"
                 src="{{ asset('storage/' . \App\Support\ImageHelper::thumb($child->photo_2x2_url, 'medium')) }}" 
                 alt="{{ $childName }}"
                 style="width: 100%; height: 100%; object-fit: cover; transition: opacity 0.3s;"
                 :style="imgLoaded ? 'opacity: 1;' : 'opacity: 0;'"
                 @load="imgLoaded = true"
                 x-on:error="imgError = true"
                 loading="lazy">
            <span x-show="imgError" class="family-photo-placeholder" style="position: absolute; inset: 0; display: flex; align-items: center; justify-content: center; background: #f8fafc; color: #64748b; font-size: 11px; font-weight: bold;">No Photo</span>
        @else
            <span class="family-photo-placeholder">No Photo</span>
        @endif
    </div>

    <div class="family-child-main">
        <div class="family-child-top">
            <div>
                <span class="family-child-name">{{ $loop->iteration }}) {{ $childName }}</span>
                <span class="family-child-meta">{{ $child->student_type ? (strtoupper($child->student_type) . ' | ') : '' }}{{ strtoupper($child->grade_level ?? 'NO GRADE') }} | {{ $learningMode }} | SY {{ $child->school_year ?? '2026-2027' }}</span>
            </div>

             <span class="family-status-badge {{ $childStatus['class'] }}">
                 @if ($childStatus['class'] === 'is-review')
                     <svg class="magnifying-glass-anim" width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3"><circle cx="11" cy="11" r="8"/><line x1="21" y1="21" x2="16.65" y2="16.65"/></svg>
                 @elseif ($childStatus['class'] === 'is-complete')
                     <svg class="check-icon-anim" width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3.5"><polyline points="20 6 9 17 4 12"/></svg>
                 @endif
                 {{ $childStatus['label'] }}
             </span>
         </div>

         <div class="family-child-footer">
             <div class="family-review-items">
                 @php
                     $isApproved = $child->status === 'approved';
                     $isAdminReview = in_array($child->status, ['under_review', 'pending', 'submitted'], true);

                     // 1. Registration Form Chip details
                     if ($isApproved) {
                         $regChipClass = 'is-verified';
                         $regIcon = 'checkmark';
                     } elseif ($isAdminReview) {
                         $regChipClass = ''; // default yellow
                         $regIcon = 'dots';
                     } else {
                         $regChipClass = 'is-verified'; // ready/draft is complete
                         $regIcon = 'checkmark';
                     }

                     // 2. Documents Chip details
                     if ($isApproved) {
                         $docsChipClass = 'is-verified';
                         $docsIcon = 'checkmark';
                     } elseif ($isAdminReview) {
                         $docsChipClass = ''; // default yellow
                         $docsIcon = 'dots';
                     } else {
                         if ($showDocsAsApproved) {
                             $docsChipClass = 'is-verified';
                             $docsIcon = 'checkmark';
                         } else {
                             $docsChipClass = 'is-missing';
                             $docsIcon = 'cross';
                         }
                     }

                     // 3. Payment Chip details
                     if ($isApproved) {
                         $payChipClass = 'is-verified';
                         $payIcon = 'checkmark';
                     } elseif ($isAdminReview) {
                         $payChipClass = ''; // default yellow
                         $payIcon = 'dots';
                     } else {
                         if ($isVerified || $hasPaymentProof) {
                             $payChipClass = 'is-verified';
                             $payIcon = 'checkmark';
                         } else {
                             $payChipClass = 'is-missing';
                             $payIcon = 'cross';
                         }
                     }
                 @endphp

                 {{-- Chip 1: Registration Form --}}
                 <span class="family-chip {{ $regChipClass }}">
                     @if ($regIcon === 'checkmark')
                         <svg class="chip-icon is-verified check-icon-anim" width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3" style="display:inline-block;flex-shrink:0;vertical-align:middle;margin-right:0.3rem;"><polyline points="20 6 9 17 4 12"/></svg>
                     @elseif ($regIcon === 'dots')
                         <span class="family-dot-animation is-pending">
                             <span class="dot"></span><span class="dot"></span><span class="dot"></span>
                         </span>
                     @else
                         <svg class="chip-icon" width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" style="display:inline-block;flex-shrink:0;vertical-align:middle;margin-right:0.3rem;"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/></svg>
                     @endif
                     Registration Form
                 </span>

                 {{-- Chip 2: Documents (only for New Student) --}}
                 @if (strcasecmp((string) $child->student_type, 'Old') !== 0)
                 <span class="family-chip {{ $docsChipClass }}">
                     @if ($docsIcon === 'checkmark')
                         <svg class="chip-icon is-verified check-icon-anim" width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3" style="display:inline-block;flex-shrink:0;vertical-align:middle;margin-right:0.3rem;"><polyline points="20 6 9 17 4 12"/></svg>
                     @elseif ($docsIcon === 'cross')
                         <svg class="chip-icon is-missing" width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3" style="display:inline-block;flex-shrink:0;vertical-align:middle;margin-right:0.3rem;"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg>
                     @elseif ($docsIcon === 'dots')
                         <span class="family-dot-animation is-pending">
                             <span class="dot"></span><span class="dot"></span><span class="dot"></span>
                         </span>
                     @else
                         <svg class="chip-icon" width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" style="display:inline-block;flex-shrink:0;vertical-align:middle;margin-right:0.3rem;"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/></svg>
                     @endif
                     {{ $documentsLabel }}
                 </span>
                 @endif

                 @if ($child->status !== 'ready_for_submission')
                 {{-- Chip 3: Paid Enrollment Fee --}}
                 <span class="family-chip {{ $payChipClass }}">
                     @if ($payIcon === 'checkmark')
                         <svg class="chip-icon is-verified check-icon-anim" width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3" style="display:inline-block;flex-shrink:0;vertical-align:middle;margin-right:0.3rem;"><polyline points="20 6 9 17 4 12"/></svg>
                     @elseif ($payIcon === 'cross')
                         <svg class="chip-icon is-missing" width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3" style="display:inline-block;flex-shrink:0;vertical-align:middle;margin-right:0.3rem;"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg>
                     @elseif ($payIcon === 'dots')
                         <span class="family-dot-animation is-pending">
                             <span class="dot"></span><span class="dot"></span><span class="dot"></span>
                         </span>
                     @else
                         <svg class="chip-icon" width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" style="display:inline-block;flex-shrink:0;vertical-align:middle;margin-right:0.3rem;"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/></svg>
                     @endif
                     {{ $paymentLabel }}
                 </span>
                 @endif
             </div>

             <span class="family-muted">Updated {{ $child->updated_at?->diffForHumans() ?? 'not saved' }}</span>
         </div>

        @if ($child->status === 'ready_for_submission')
            <div class="family-child-footer" style="margin-top: 0.5rem; padding-top: 0.75rem; border-top: 1px solid #eef2f7;">
                <div class="family-card-actions" style="width: 100%; display: flex; justify-content: flex-end; gap: 0.75rem;">
                     <form method="POST" action="{{ route('enrollment.draft.discard') }}" data-clear-draft-form onsubmit="return confirm('Delete this enrollment? This cannot be undone.')">
                          @csrf
                          @method('DELETE')
                          <input type="hidden" name="applicant_id" value="{{ $child->id }}">
                          <button type="submit" class="family-action family-action-danger">DELETE APPLICATION FORM</button>
                     </form>
                     <a href="{{ route('enrollment.form.child', $child) }}" class="family-action family-action-primary">CHECK DETAILS</a>
                     <a href="{{ route('enrollment.payment', ['applicant' => $child->id]) }}" class="family-action family-action-primary" style="background:#059669; border:none; color:white; text-decoration:none; display:inline-flex; align-items:center; justify-content:center; cursor:pointer; font-weight:bold;">FINALIZE & SUBMIT</a>
                </div>
            </div>
        @endif

        @if ($isAdminReview)
            <div class="family-child-footer" style="margin-top: 0.5rem; padding-top: 0.75rem; border-top: 1px solid #eef2f7;">
                <div style="width: 100%; display: flex; align-items: center; gap: 0.5rem; background: #fffbeb; border: 1px solid #fde68a; border-radius: 8px; padding: 0.5rem 0.75rem; font-size: 0.75rem; color: #b45309; font-weight: 700;">
                    <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" style="flex-shrink:0;"><circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/></svg>
                    <span>Pending registrar review. Verification takes 1–2 banking/business days. No action is required.</span>
                </div>
            </div>
        @endif
    </div>
</article>
