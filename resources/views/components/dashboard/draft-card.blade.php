@php
    $childNumber = $submittedApplications->count() + $loop->iteration;
    $childStatus = $statusStyles[$child->status] ?? ['class' => 'is-neutral', 'label' => strtoupper(str_replace('_', ' ', $child->status ?? 'draft'))];
    $childName = trim(($child->first_name ?? '') . ' ' . ($child->middle_name ?? '') . ' ' . ($child->last_name ?? '')) ?: 'New applicant draft';
    $progress = in_array($child->status, ['ready_for_submission', 'approved'], true)
        ? 100
        : (int) ($child->completion_percentage ?? 0);
    $step = min(max((int) ($child->last_step ?? 1), 1), 6);
    $actionLabel = $child->status === 'rejected'
        ? 'Re-edit Form'
        : ($child->status === 'ready_for_submission' ? 'Check Details' : 'Continue Draft');
    $modeKey = strtolower((string) ($child->learning_mode ?? $child->enrollment_type ?? ''));
    $learningMode = $learningModeLabels[$modeKey] ?? strtoupper(str_replace('_', ' ', $modeKey ?: 'LEARNING MODE PENDING'));
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
                <span class="family-child-name">{{ $childNumber }}) {{ $childName }}</span>
                <span class="family-child-meta">{{ $child->student_type ? (strtoupper($child->student_type) . ' | ') : '' }}{{ strtoupper($child->grade_level ?? 'NO GRADE') }} | {{ $learningMode }} | SY {{ $child->school_year ?? '2026-2027' }}</span>
            </div>

            <span class="family-status-badge {{ $childStatus['class'] }}">
                @if ($childStatus['class'] === 'is-review')
                    <svg class="magnifying-glass-anim" width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3"><circle cx="11" cy="11" r="8"/><line x1="21" y1="21" x2="16.65" y2="16.65"/></svg>
                @elseif ($childStatus['class'] === 'is-complete')
                    <svg class="check-icon-anim" width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3.5"><polyline points="20 6 9 17 4 12"/></svg>
                @endif
                {{ $childStatus['label'] }}{{ $progress < 100 ? ' - ' . $progress . '%' : '' }}
            </span>
        </div>

        <div class="family-child-footer">
            <span class="family-muted">{{ $stepNames[$step] }}</span>

            <div class="family-card-actions">
                <form method="POST" action="{{ route('enrollment.draft.discard') }}" data-clear-draft-form onsubmit="return confirm('Delete this enrollment? This cannot be undone.')">
                    @csrf
                    @method('DELETE')
                    <input type="hidden" name="applicant_id" value="{{ $child->id }}">
                    <button type="submit" class="family-action family-action-danger">DELETE APPLICATION FORM</button>
                </form>

                <a href="{{ route('enrollment.form.child', $child) }}" class="family-action family-action-primary">{{ $actionLabel }}</a>
            </div>
        </div>
    </div>
</article>
