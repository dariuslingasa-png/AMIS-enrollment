@props([
    'number',
    'label',
    'currentStep',
    'completedSteps' => [],
])

@php
    $isActive = $currentStep == $number;
    $isDone = in_array($number, $completedSteps);
    $isClickable = $number == 1 || in_array($number - 1, $completedSteps);
    $classes = collect(['enrollment-step-item']);
    if ($isActive) $classes->push('active');
    if ($isDone) $classes->push('done');
    if (!$isClickable) $classes->push('disabled');
@endphp

<div class="{{ $classes->implode(' ') }}">
    <div class="enrollment-step-circle">
        @if($isDone)
            <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3">
                <path d="M20 6L9 17l-5-5"/>
            </svg>
        @else
            {{ $number }}
        @endif
    </div>
    <span class="enrollment-step-label">{{ $label }}</span>
</div>
