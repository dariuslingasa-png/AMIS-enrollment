@props([
    'for' => null,
    'required' => false,
    'optional' => false,
])

<label @if($for) for="{{ $for }}" @endif class="field-label">
    <span>{{ $slot }}</span>
    @if($optional)
        <span class="field-optional-badge">Optional</span>
    @endif
</label>
