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
    <link href="https://fonts.googleapis.com/css2?family=Amiri:ital,wght@0,400;0,700;1,400;1,700&family=Inter:wght@300;400;500;600;700;800&family=Tajawal:wght@300;400;500;700;800;900&display=swap" rel="stylesheet">

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
            if (form.closest('.enrollment-page')) return; // Let Alpine handle enrollment form loading

            // Show page load loader overlay instead of button spinner
            var loader = document.querySelector('.initial-loading-screen');
            if (loader) {
                loader.style.setProperty('display', 'flex', 'important');
                loader.style.opacity = '1';
            }
        });

        document.addEventListener('click', function (event) {
            var clickable = event.target.closest('a, button');
            if (!clickable) return;
            if (clickable.closest('.enrollment-page')) return; // Let Alpine handle enrollment form loading

            if (clickable.tagName === 'A' && clickable.getAttribute('href') && !clickable.getAttribute('href').startsWith('#') && !clickable.getAttribute('target')) {
                if (
                    clickable.classList.contains('btn-primary') ||
                    clickable.classList.contains('family-action-primary') ||
                    clickable.classList.contains('family-finalize') ||
                    clickable.classList.contains('family-action-payment') ||
                    clickable.classList.contains('family-add-yes') ||
                    clickable.classList.contains('logout-btn') ||
                    clickable.getAttribute('href').includes('/enroll') ||
                    clickable.getAttribute('href').includes('/payment') ||
                    clickable.getAttribute('href').includes('/finalize')
                ) {
                    // Show page load loader overlay instead of button spinner
                    var loader = document.querySelector('.initial-loading-screen');
                    if (loader) {
                        loader.style.setProperty('display', 'flex', 'important');
                        loader.style.opacity = '1';
                    }
                }
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

    {{-- Client-side inactivity auto-logout (authenticated users only) --}}
    @auth
    <form id="idle-logout-form" method="POST" action="{{ route('logout') }}" style="display:none;">
        @csrf
    </form>
    <div id="idle-warning" style="display:none; position:fixed; bottom:1.5rem; right:1.5rem; z-index:99999; background:#fef2f2; border:1.5px solid #fca5a5; border-radius:14px; padding:1rem 1.25rem; box-shadow:0 8px 30px rgba(0,0,0,0.12); max-width:340px; font-family:'Inter',sans-serif; animation: idleSlideIn 0.3s ease-out;">
        <div style="display:flex; align-items:flex-start; gap:0.75rem;">
            <svg style="width:22px;height:22px;flex-shrink:0;margin-top:2px;" viewBox="0 0 24 24" fill="none" stroke="#dc2626" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
                <circle cx="12" cy="12" r="10"></circle>
                <polyline points="12 6 12 12 16 14"></polyline>
            </svg>
            <div>
                <div style="font-weight:700; font-size:0.875rem; color:#991b1b; margin-bottom:0.25rem;">Session Expiring</div>
                <div style="font-size:0.8125rem; color:#b91c1c; line-height:1.4;">
                    You'll be logged out in <strong id="idle-countdown" style="font-family:monospace;font-size:0.9rem;">2:00</strong> due to inactivity.
                </div>
                <button onclick="window.amisIdleReset()" style="margin-top:0.65rem; background:#dc2626; color:#fff; border:none; border-radius:8px; padding:0.4rem 1rem; font-size:0.8rem; font-weight:700; cursor:pointer; font-family:inherit;">
                    Stay Logged In
                </button>
            </div>
        </div>
    </div>
    <style>
        @keyframes idleSlideIn {
            from { opacity:0; transform:translateY(20px); }
            to { opacity:1; transform:translateY(0); }
        }
    </style>
    <script>
        (function () {
            var IDLE_MINUTES = {{ (int) config('session.idle_timeout', 30) }};
            var WARNING_SECONDS = 120; // Show warning 2 minutes before logout
            var IDLE_MS = IDLE_MINUTES * 60 * 1000;
            var WARNING_MS = IDLE_MS - (WARNING_SECONDS * 1000);
            var STORAGE_KEY = 'amis_last_active';

            var idleTimer = null;
            var warningTimer = null;
            var countdownInterval = null;
            var warningEl = document.getElementById('idle-warning');
            var countdownEl = document.getElementById('idle-countdown');

            function now() { return Date.now(); }

            function updateStorage() {
                try { localStorage.setItem(STORAGE_KEY, String(now())); } catch (_) {}
            }

            function resetTimers() {
                clearTimeout(idleTimer);
                clearTimeout(warningTimer);
                clearInterval(countdownInterval);
                if (warningEl) warningEl.style.display = 'none';

                updateStorage();

                warningTimer = setTimeout(showWarning, WARNING_MS);
                idleTimer = setTimeout(doLogout, IDLE_MS);
            }

            function showWarning() {
                if (!warningEl || !countdownEl) return;
                warningEl.style.display = 'block';
                warningEl.style.animation = 'none';
                void warningEl.offsetHeight; // trigger reflow
                warningEl.style.animation = 'idleSlideIn 0.3s ease-out';

                var remaining = WARNING_SECONDS;
                countdownEl.textContent = formatTime(remaining);
                countdownInterval = setInterval(function () {
                    remaining--;
                    if (remaining <= 0) {
                        clearInterval(countdownInterval);
                        doLogout();
                    } else {
                        countdownEl.textContent = formatTime(remaining);
                    }
                }, 1000);
            }

            function formatTime(secs) {
                var m = Math.floor(secs / 60);
                var s = secs % 60;
                return m + ':' + String(s).padStart(2, '0');
            }

            function doLogout() {
                clearTimeout(idleTimer);
                clearTimeout(warningTimer);
                clearInterval(countdownInterval);
                try { localStorage.removeItem(STORAGE_KEY); } catch (_) {}
                var form = document.getElementById('idle-logout-form');
                if (form) form.submit();
            }

            // Expose reset for the "Stay Logged In" button
            window.amisIdleReset = resetTimers;

            // Track user activity
            var events = ['mousemove', 'mousedown', 'keydown', 'scroll', 'touchstart', 'click'];
            var throttled = false;
            function onActivity() {
                if (throttled) return;
                throttled = true;
                setTimeout(function () { throttled = false; }, 5000); // Throttle to once per 5s
                resetTimers();
            }
            events.forEach(function (ev) {
                document.addEventListener(ev, onActivity, { passive: true });
            });

            // Sync across tabs — if another tab logs out or resets, follow suit
            window.addEventListener('storage', function (e) {
                if (e.key === STORAGE_KEY) {
                    if (e.newValue === null) {
                        // Another tab logged out
                        doLogout();
                    } else {
                        // Another tab was active — reset our timers
                        resetTimers();
                    }
                }
            });

            // Start
            resetTimers();
        })();
    </script>
    @endauth

    @stack('scripts')
</body>
</html>
