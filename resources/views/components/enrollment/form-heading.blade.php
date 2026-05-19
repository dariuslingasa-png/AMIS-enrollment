<div class="enrollment-form-header enrollment-form-header-with-action">
    <a href="{{ route('enrollment.dashboard') }}" title="Back to Dashboard" class="form-header-close">
        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5">
            <line x1="18" y1="6" x2="6" y2="18"/>
            <line x1="6" y1="6" x2="18" y2="18"/>
        </svg>
    </a>
    <h2 x-text="stepTitles[step - 1]"></h2>
    <p>Step <span x-text="step"></span> of <span x-text="totalSteps"></span></p>
</div>
