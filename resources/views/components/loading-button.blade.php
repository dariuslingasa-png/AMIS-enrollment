@props([
    'loading' => 'Processing...',
])

<button {{ $attributes->merge(['type' => 'submit', 'class' => 'loading-button']) }} data-loading-text="{{ $loading }}">
    <span class="loading-button-spinner" aria-hidden="true"></span>
    <span class="loading-button-label">{{ $slot }}</span>
</button>
