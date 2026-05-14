<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>{{ $title ?? config('app.name', 'AMIS Enrollment') }}</title>

    <!-- Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">

    <!-- Prevent FOUC -->
    <style>
        [x-cloak] { display: none !important; }
        .page-content { opacity: 0; transition: opacity 0.2s; }
        .page-content.show { opacity: 1; }
    </style>

    <!-- Scripts & Styles -->
    @vite(['resources/css/app.css', 'resources/js/app.js'])

    @stack('styles')
</head>
<body class="font-sans antialiased" x-data="{ pageLoaded: false }" x-init="
    const shown = sessionStorage.getItem('amis_loaded');
    if (shown) { 
        pageLoaded = true; 
        document.querySelector('.page-content').classList.add('show');
    } else { 
        setTimeout(() => { 
            pageLoaded = true; 
            sessionStorage.setItem('amis_loaded', '1');
            document.querySelector('.page-content').classList.add('show');
        }, 800); 
    }
">
    <!-- Initial Loading Screen (only on F5 / first visit) -->
    <div x-show="!pageLoaded" x-cloak 
         x-transition:leave="transition ease-in duration-300" 
         x-transition:leave-start="opacity-100" 
         x-transition:leave-end="opacity-0" 
         class="initial-loading-screen">
        <img src="{{ asset('images/AMIS_Logo.png') }}" alt="AMIS" class="initial-loading-logo">
        <div class="three-dots-loading">
            <div class="dot"></div>
            <div class="dot"></div>
            <div class="dot"></div>
        </div>
    </div>

    <!-- Page Content -->
    <div class="page-content min-h-screen bg-gray-100" x-show="pageLoaded" x-cloak 
         x-transition:enter="transition ease-out duration-200" 
         x-transition:enter-start="opacity-0" 
         x-transition:enter-end="opacity-100">
        @auth
            @include('layouts.navigation')
        @endauth

        @isset($header)
            <header class="bg-white shadow">
                <div class="max-w-7xl mx-auto py-6 px-4 sm:px-6 lg:px-8">
                    {{ $header }}
                </div>
            </header>
        @endisset

        @if (session('success'))
            <x-alert type="success" :message="session('success')" />
        @endif
        @if (session('error'))
            <x-alert type="error" :message="session('error')" />
        @endif
        @if (session('info'))
            <x-alert type="info" :message="session('info')" />
        @endif

        <main>
            {{ $slot }}
        </main>
    </div>

    @stack('scripts')
</body>
</html>
