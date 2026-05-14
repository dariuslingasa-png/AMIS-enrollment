<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>{{ $title ?? 'Enrollment - AMIS' }}</title>

    <!-- Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">

    <!-- Scripts & Styles -->
    @vite(['resources/css/app.css', 'resources/js/app.js'])

    @stack('styles')
</head>
<body class="font-sans antialiased">
    <div class="enrollment-page">
        <!-- Left Panel -->
        <div class="enrollment-left-panel">
            <div class="enrollment-logo-section">
                <img src="{{ asset('images/AMIS_Logo.png') }}" alt="AMIS Logo" class="enrollment-logo">
                <p class="enrollment-arabic" dir="rtl">المدرسة المنورة الإسلامية</p>
                <h2 class="enrollment-school-name">Al Munawwara Islamic School</h2>
                <h1 class="enrollment-title">Online Pre-Enrollment</h1>
                <p class="enrollment-tagline">School Year 2026–2027</p>
            </div>

            <!-- Step Progress -->
            @isset($steps)
                <div class="enrollment-steps-panel">
                    {{ $steps }}
                </div>
            @endisset

            <div class="enrollment-powered-by">
                <p class="enrollment-powered-text">Powered by</p>
                <img src="{{ asset('images/MA_Logo.png') }}" alt="MA Logo" class="enrollment-powered-logo">
            </div>
        </div>

        <!-- Right Panel -->
        <div class="enrollment-right-panel">
            <!-- Mobile Header -->
            <div class="enrollment-mobile-header">
                <img src="{{ asset('images/AMIS_Logo.png') }}" alt="AMIS Logo">
                <p dir="rtl">المدرسة المنورة الإسلامية</p>
                <p>Al Munawwara Islamic School</p>
                <p>Online Enrollment 2026–2027</p>
            </div>

            <div class="enrollment-form-container">
                <!-- Mobile Steps -->
                @isset($mobileSteps)
                    <div class="enrollment-mobile-steps">
                        {{ $mobileSteps }}
                    </div>
                @endisset

                {{ $slot }}
            </div>

            <div class="enrollment-footer">
                &copy; {{ date('Y') }} Al Munawwara Islamic School. All rights reserved.
            </div>
        </div>
    </div>

    @stack('scripts')
</body>
</html>
