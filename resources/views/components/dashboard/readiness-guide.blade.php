@props([
    'requiredItems' => [],
    'optionalItems' => [],
])

<section class="dashboard-guidance" aria-labelledby="dashboard-guidance-title">
    <div class="guidance-header">
        <div>
            <p class="guidance-eyebrow">Teacher feedback applied</p>
            <h2 id="dashboard-guidance-title">Enrollment Readiness Guide</h2>
        </div>
        <span class="guidance-badge">SY 2026-2027</span>
    </div>

    <div class="guidance-grid">
        <x-dashboard.readiness-panel
            title="Required before submission"
            tone="green"
            :items="$requiredItems"
        />

        <x-dashboard.readiness-panel
            title="Optional but helpful"
            tone="blue"
            :items="$optionalItems"
        />
    </div>

    <div class="guidance-notes">
        <x-dashboard.guidance-note title="Document not available?">
            <x-slot:icon>
                <path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8Z"/>
                <path d="M14 2v6h6"/>
            </x-slot:icon>
            Upload an affidavit or temporary proof for review, then prepare the original document when admissions requests it.
        </x-dashboard.guidance-note>

        <x-dashboard.guidance-note title="Multiple students">
            <x-slot:icon>
                <path d="M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2"/>
                <circle cx="9" cy="7" r="4"/>
                <path d="M22 21v-2a4 4 0 0 0-3-3.87"/>
                <path d="M16 3.13a4 4 0 0 1 0 7.75"/>
            </x-slot:icon>
            Use one parent email for coordination. For another child under the same parent, contact admissions so the records are grouped correctly.
        </x-dashboard.guidance-note>

        <x-dashboard.guidance-note title="Schedule and slots">
            <x-slot:icon>
                <circle cx="12" cy="12" r="10"/>
                <path d="M12 6v6l4 2"/>
            </x-slot:icon>
            Online classes follow Philippine Standard Time. If a section is full, admissions may assign the student to the next available shift.
        </x-dashboard.guidance-note>

        <x-dashboard.guidance-note title="Privacy and payment agreement">
            <x-slot:icon>
                <path d="M20 13c0 5-3.5 7.5-7.66 8.95a1 1 0 0 1-.67-.01C7.5 20.5 4 18 4 13V6a1 1 0 0 1 1-1c2 0 4.5-1.2 6.24-2.72a1.17 1.17 0 0 1 1.52 0C14.5 3.8 17 5 19 5a1 1 0 0 1 1 1Z"/>
                <path d="m9 12 2 2 4-4"/>
            </x-slot:icon>
            Submitting confirms the data privacy agreement. Enrollment fees are non-refundable once paid, even if requirements are later rejected.
        </x-dashboard.guidance-note>
    </div>
</section>
