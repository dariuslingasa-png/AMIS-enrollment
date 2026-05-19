@props(['showLoader' => true])

<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>{{ $title ?? config('app.name', 'AMIS Enrollment') }}</title>

    <!-- Favicon -->
    <link rel="icon" type="image/png" href="{{ asset('images/AMIS_Logo.png') }}">
    <link rel="shortcut icon" href="{{ asset('images/AMIS_Logo.png') }}">

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
<body class="font-sans antialiased" x-data="{ pageLoaded: {{ $showLoader ? 'false' : 'true' }} }" x-init="
    if (!{{ $showLoader ? 'true' : 'false' }}) {
        document.querySelector('.page-content').classList.add('show');
    } else {
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
    }
">
    <!-- Initial Loading Screen (only on F5 / first visit) -->
    @if ($showLoader)
        <x-page-loader
            x-show="!pageLoaded"
            x-cloak
            x-transition:leave="transition ease-in duration-300"
            x-transition:leave-start="opacity-100"
            x-transition:leave-end="opacity-0"
        />
    @endif

    @php
        $toastError = session('error') ?: ($errors->any() ? $errors->first() : null);
    @endphp
    @if (session('success') || session('info') || session('warning') || $toastError)
        <div class="toast-stack">
            @if (session('success'))
                <x-toast type="success" :message="session('success')" />
            @endif
            @if (session('info'))
                <x-toast type="info" :message="session('info')" />
            @endif
            @if (session('warning'))
                <x-toast type="warning" :message="session('warning')" />
            @endif
            @if ($toastError)
                <x-toast type="error" :message="$toastError" />
            @endif
        </div>
    @endif

    <!-- Page Content -->
    <div class="page-content" x-show="pageLoaded" x-cloak 
         x-transition:enter="transition ease-out duration-200" 
         x-transition:enter-start="opacity-0" 
         x-transition:enter-end="opacity-100">
        {{ $slot }}
    </div>

    <script>
        document.addEventListener('submit', function (event) {
            var form = event.target;
            if (!form.matches('[data-loading-form]')) return;

            var button = event.submitter || form.querySelector('button[type="submit"]');
            if (!button || button.classList.contains('is-loading')) return;

            var label = button.querySelector('.loading-button-label');
            var loadingText = button.getAttribute('data-loading-text');

            button.classList.add('is-loading');
            button.setAttribute('aria-busy', 'true');
            button.disabled = true;

            if (label && loadingText) {
                label.textContent = loadingText;
            }
        });
    </script>

    <script>
        (function () {
            var userId = @json(auth()->id());
            var shouldClearDraftCache = @json((bool) session('clear_draft_cache'));
            var discardedDraftApplicantId = @json(session('discarded_draft_applicant_id'));

            function draftKeys(applicantId) {
                var keys = [];

                if (userId) {
                    keys.push('amis_enrollment_draft_user_' + userId + '_applicant_' + (applicantId || 'new'));
                }

                keys.push('amis_enrollment_draft');

                return keys;
            }

            window.amisEnrollmentDraftCache = {
                clear: function (applicantId) {
                    // A discarded child draft has two copies: the backend row and this browser cache.
                    // Clear only the matching child key so sibling drafts stay intact and cannot rehydrate stale data.
                    draftKeys(applicantId).forEach(function (key) {
                        try { localStorage.removeItem(key); } catch (_) {}
                        try { sessionStorage.removeItem(key); } catch (_) {}
                    });
                }
            };

            document.addEventListener('submit', function (event) {
                var form = event.target;
                if (!(form instanceof HTMLFormElement)) return;

                if (form.dataset.confirmMessage && !window.confirm(form.dataset.confirmMessage)) {
                    event.preventDefault();
                    return;
                }

                if (form.matches('[data-clear-draft-form]')) {
                    var applicantInput = form.querySelector('input[name="applicant_id"]');
                    window.amisEnrollmentDraftCache.clear(applicantInput ? applicantInput.value : null);
                }
            });

            if (shouldClearDraftCache && discardedDraftApplicantId) {
                window.amisEnrollmentDraftCache.clear(discardedDraftApplicantId);
            }
        })();
    </script>

    @stack('scripts')
</body>
</html>
