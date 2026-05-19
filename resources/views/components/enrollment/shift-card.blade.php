@props([
    'shift',
    'phTime',
    'start',
    'end',
    'capacity' => 40,
    'available' => 0,
])

@php
    $available = max(0, (int) $available);
    $capacity = max(1, (int) $capacity);
    $filled = min(100, (int) round((($capacity - $available) / $capacity) * 100));
    $fillClass = 'fill-' . (int) (round($filled / 10) * 10);
    $isFull = $available <= 0;
    $isLimited = !$isFull && $available <= 5;
    $slotLabel = $isFull ? 'Full' : ($isLimited ? 'Limited slots' : 'Open slots');
@endphp

<button
    type="button"
    @if ($isFull) disabled @else @click="toggleLearningShift('{{ $shift }}')" @endif
    class="shift-card {{ $isFull ? 'is-full' : '' }} {{ $isLimited ? 'is-limited' : '' }}"
    :class="{ 'is-selected': form.learning_mode_shift === '{{ $shift }}' }"
>
    <div class="shift-card-topline">
        <div class="shift-card-title">{{ $shift }}</div>
        <span class="shift-slot-badge">{{ $slotLabel }}</span>
    </div>

    <div class="shift-slot-meter" aria-label="{{ $available }} slots available out of {{ $capacity }}">
        <span class="shift-slot-meter-fill {{ $fillClass }}"></span>
    </div>
    <div class="shift-slot-copy">
        <strong>{{ $available }}</strong> of {{ $capacity }} slots available
    </div>

    <div class="shift-card-primary-time">
        <img src="https://flagcdn.com/16x12/ph.png" width="16" height="12" alt="PH" class="flag-icon">
        {{ $phTime }} <span>(PHT / UTC+8)</span>
    </div>

    <div class="shift-card-local-time">
        <span>Local time guide</span>
        <strong x-text="formatShiftTime('{{ $start }}', '{{ $end }}')"></strong>
    </div>
</button>
