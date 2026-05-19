@props(['title'])

<article class="guidance-note">
    <span class="guidance-note-icon">
        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.3" stroke-linecap="round" stroke-linejoin="round">
            {{ $icon }}
        </svg>
    </span>
    <div>
        <h3>{{ $title }}</h3>
        <p>{{ $slot }}</p>
    </div>
</article>
