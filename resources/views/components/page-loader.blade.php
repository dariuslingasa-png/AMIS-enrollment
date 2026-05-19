@props(['logo' => true])

<div {{ $attributes->merge(['class' => 'initial-loading-screen']) }}>
    @if ($logo)
        <img src="{{ asset('images/AMIS_Logo.png') }}" alt="AMIS" class="initial-loading-logo">
    @endif

    <div class="three-dots-loading" aria-label="Loading">
        <div class="dot"></div>
        <div class="dot"></div>
        <div class="dot"></div>
    </div>
</div>
