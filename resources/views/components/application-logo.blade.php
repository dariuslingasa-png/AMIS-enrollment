@props(['class' => 'w-16 h-16'])

<img src="{{ asset('images/AMIS_Logo.png') }}" alt="AMIS Logo" {{ $attributes->merge(['class' => $class . ' object-contain']) }}>
