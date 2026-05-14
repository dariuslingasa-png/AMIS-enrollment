@props([
    'size' => 'default', // sm, default, lg
    'color' => 'primary', // primary, white
    'class' => ''
])

@php
$sizeClass = match($size) {
    'sm' => 'spinner-sm',
    'lg' => 'spinner-lg',
    default => ''
};

$colorClass = match($color) {
    'white' => 'spinner-white',
    default => ''
};
@endphp

<div class="spinner {{ $sizeClass }} {{ $colorClass }} {{ $class }}"></div>