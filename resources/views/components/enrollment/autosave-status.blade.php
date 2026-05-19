<div x-show="draftSaving || draftSaved" x-cloak x-transition class="autosave-status">
    <template x-if="draftSaving">
        <span class="autosave-status-row">
            <svg class="spin-icon" width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="#9ca3af" stroke-width="2.5"><path d="M21 12a9 9 0 11-6.219-8.56"/></svg>
            Saving...
        </span>
    </template>
    <template x-if="!draftSaving && draftSaved">
        <span class="autosave-status-row autosave-status-saved">
            <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><polyline points="20 6 9 17 4 12"/></svg>
            Draft saved
        </span>
    </template>
</div>
