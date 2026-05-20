<x-guest-layout>
@php
    $fatherName = trim(implode(' ', array_filter([
        $applicant->father_first_name,
        $applicant->father_middle_name,
        $applicant->father_last_name,
    ])));
    $motherName = trim(implode(' ', array_filter([
        $applicant->mother_first_name,
        $applicant->mother_middle_name,
        $applicant->mother_last_name,
    ])));
    $guardianName = $fatherName ?: ($motherName ?: ($user->name ?? ''));
    $studentName = $applicant->full_name ?: trim(($applicant->first_name ?? '') . ' ' . ($applicant->last_name ?? ''));
    $storedAffidavitData = is_array($storedAffidavitData ?? null) ? $storedAffidavitData : [];
    $affidavitFieldValues = array_merge([
        'guardian_name' => $guardianName,
        'guardian_relationship' => $fatherName ? 'Father' : ($motherName ? 'Mother' : 'Parent / Guardian'),
        'guardian_address' => $applicant->address ?: $applicant->street_address,
        'student_name' => $studentName,
        'missing_credential' => '',
        'grade_level' => $applicant->grade_level,
        'school_year' => $applicant->school_year ?: '2026-2027',
        'reason' => '',
        'commitment_date' => '',
        'attested_day' => now()->format('j'),
        'attested_month' => now()->format('F'),
        'attested_place' => '',
        'govt_id_type' => '',
        'govt_id_number' => '',
        'govt_id_date' => '',
        'govt_id_presented' => '',
        'id_number' => '',
        'date_issued' => '',
        'signature_data' => '',
    ], $storedAffidavitData);
@endphp

<div class="enrollment-page">
    <x-enrollment.brand-header />

    <div class="enrollment-main">
        <div class="enrollment-form-container affidavit-page">
            <div class="affidavit-page-header">
                <a href="{{ route('enrollment.form.child', $applicant) }}" class="affidavit-back-link">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M15 18l-6-6 6-6"/>
                    </svg>
                    Back to documents
                </a>
                <span class="affidavit-page-badge">Temporary proof</span>
            </div>

            @if ($errors->any())
                <div class="enrollment-error" style="display:flex;">
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/>
                    </svg>
                    <span>{{ $errors->first() }}</span>
                </div>
            @endif

            <form method="POST" action="{{ route('enrollment.affidavit.store', ['applicant' => $applicant->id]) }}" data-affidavit-form>
                @csrf
                <input type="hidden" name="applicant_id" value="{{ $applicant->id }}">
                <input type="hidden" name="signature_data" data-signature-input value="{{ old('signature_data', $affidavitFieldValues['signature_data'] ?? '') }}">
                <input type="hidden" name="guardian_relationship" value="{{ old('guardian_relationship', $affidavitFieldValues['guardian_relationship'] ?? '') }}">
                <input type="hidden" name="school_year" value="{{ old('school_year', $affidavitFieldValues['school_year'] ?? '') }}">

                {{-- PDF Preview with overlay inputs --}}
                <div class="pdf-form-wrapper" id="pdfFormWrapper">
                    <div class="pdf-scroll-container" id="pdfScrollContainer">
                        {{-- Zoom toolbar sticky inside scroll area --}}
                        <div class="pdf-zoom-toolbar">
                            <button type="button" id="zoomIn" class="pdf-zoom-btn" aria-label="Zoom in">+</button>
                            <span id="zoomLevel" class="pdf-zoom-label">100%</span>
                            <button type="button" id="zoomReset" class="pdf-zoom-btn pdf-zoom-reset" aria-label="Fit">Fit</button>
                        </div>
                        <div class="pdf-form-container" id="pdfContainer">
                            <canvas id="pdfCanvas"></canvas>

                            {{-- Overlay inputs positioned on the PDF blanks --}}
                            <div class="pdf-overlay-fields" id="pdfOverlay">
                                @foreach ($affidavitFields as $fieldName => $field)
                                    <input
                                        type="text"
                                        name="{{ $fieldName }}"
                                        class="pdf-field{{ !empty($field['no_caps']) ? ' no-caps' : '' }}"
                                        data-top="{{ $field['top'] }}"
                                        data-left="{{ $field['left'] }}"
                                        data-width="{{ $field['width'] }}"
                                        data-fontsize="{{ $field['font_size'] }}"
                                        data-bold="{{ !empty($field['bold']) ? '1' : '0' }}"
                                        data-align="{{ $field['align'] }}"
                                        value="{{ old($fieldName, $affidavitFieldValues[$fieldName] ?? '') }}"
                                        placeholder="{{ $field['placeholder'] ?? '' }}"
                                        @if(!empty($field['required'])) required @endif
                                    >
                                @endforeach

                            {{-- Signature preview on PDF --}}
                            <img id="sigPreview" style="position:absolute;top:{{ $signatureField['top'] }}%;left:{{ $signatureField['left'] }}%;width:{{ $signatureField['width'] }}%;height:{{ $signatureField['height'] }}%;object-fit:contain;pointer-events:none;opacity:0;" alt="">
                            {{-- Guardian name mirror below signature --}}
                            <span id="sigNamePreview" data-fontsize="{{ $signatureNameField['font_size'] }}" style="position:absolute;top:{{ $signatureNameField['top'] }}%;left:{{ $signatureNameField['left'] }}%;width:{{ $signatureNameField['width'] }}%;text-align:center;font-size:{{ $signatureNameField['font_size'] }}px;font-weight:700;color:#0f172a;pointer-events:none;text-transform:uppercase;"></span>
                        </div>
                    </div>
                    </div>
                </div>

                {{-- Signature section --}}
                <div class="signature-panel" style="margin-top:1.5rem;">
                    <div>
                        <h2>Signature Over Printed Name of Parent/Guardian</h2>
                        <p>Sign inside the box using mouse, trackpad, or touch.</p>
                    </div>
                    <canvas data-signature-canvas class="signature-canvas"></canvas>
                    <div class="signature-actions">
                        <button type="button" class="affidavit-secondary-btn" data-clear-signature>Clear signature</button>
                        <span data-signature-error class="signature-error" hidden>Please sign before saving.</span>
                    </div>
                </div>

                {{-- Gov't ID fields --}}
                <div class="form-grid" style="margin-top:1.25rem;">
                    <div class="form-group">
                        <label>Gov't ID Presented</label>
                        <input type="text" name="govt_id_presented" class="plain-input" value="{{ old('govt_id_presented', $affidavitFieldValues['govt_id_presented'] ?: ($affidavitFieldValues['govt_id_type'] ?? '')) }}" placeholder="e.g. Passport, Driver's License">
                    </div>
                    <div class="form-group">
                        <label>ID Number</label>
                        <input type="text" name="id_number" class="plain-input" value="{{ old('id_number', $affidavitFieldValues['id_number'] ?: ($affidavitFieldValues['govt_id_number'] ?? '')) }}" placeholder="ID Number">
                    </div>
                    <div class="form-group">
                        <label>Date Issued</label>
                        <input type="text" name="date_issued" class="plain-input" value="{{ old('date_issued', $affidavitFieldValues['date_issued'] ?: ($affidavitFieldValues['govt_id_date'] ?? '')) }}" placeholder="e.g. January 15, 2024">
                    </div>
                </div>

                <label class="affidavit-agreement" style="margin-top:1rem;">
                    <input type="checkbox" name="agreement" value="1" required @checked(old('agreement'))>
                    <span>I certify that this undertaking is completely filled out, true, correct, and signed for admissions review.</span>
                </label>

                <div class="affidavit-actions" style="margin-top:1rem;">
                    <a href="{{ route('enrollment.form.child', $applicant) }}" class="affidavit-secondary-btn">Cancel</a>
                    <button type="submit" class="btn-primary">Save signed affidavit</button>
                </div>
            </form>
        </div>
    </div>
</div>

@push('scripts')
<script src="https://cdnjs.cloudflare.com/ajax/libs/pdf.js/3.11.174/pdf.min.js"></script>
<script>
document.addEventListener('DOMContentLoaded', async () => {
    pdfjsLib.GlobalWorkerOptions.workerSrc = 'https://cdnjs.cloudflare.com/ajax/libs/pdf.js/3.11.174/pdf.worker.min.js';

    const pdfUrl = '{{ asset("docs/Affidavit_enrollee.pdf") }}';
    const applicantId = '{{ $applicant->id }}';
    const draftKey = `enrollment_affidavit_draft_${applicantId}`;
    const draftUrl = '{{ route('enrollment.affidavit.draft', ['applicant' => $applicant->id]) }}';
    const csrfToken = '{{ csrf_token() }}';
    const canvas = document.getElementById('pdfCanvas');
    const container = document.getElementById('pdfContainer');
    const overlay = document.getElementById('pdfOverlay');
    const ctx = canvas.getContext('2d');

    // Load and render PDF
    const pdf = await pdfjsLib.getDocument(pdfUrl).promise;
    const page = await pdf.getPage(1);

    // Position overlay fields first
    const fields = overlay.querySelectorAll('.pdf-field');
    fields.forEach(field => {
        field.style.top = field.dataset.top + '%';
        field.style.left = field.dataset.left + '%';
        field.style.width = field.dataset.width + '%';
        field.style.fontSize = (field.dataset.fontsize || '12') + 'px';
        field.style.fontWeight = field.dataset.bold === '1' ? '700' : '600';
        field.style.textAlign = field.dataset.align || 'left';
    });
    overlay.style.display = 'block';

    const renderPdf = () => {
        const containerWidth = container.clientWidth;
        const viewport = page.getViewport({ scale: 1 });
        const scale = containerWidth / viewport.width;
        const scaledViewport = page.getViewport({ scale });

        canvas.width = scaledViewport.width;
        canvas.height = scaledViewport.height;
        container.style.height = scaledViewport.height + 'px';

        page.render({ canvasContext: ctx, viewport: scaledViewport });

        // Scale font sizes proportionally (base: 800px width = 1x)
        const fontScale = containerWidth / 800;
        fields.forEach(field => {
            const baseFontSize = parseFloat(field.dataset.fontsize || '12');
            field.style.fontSize = Math.max(8, baseFontSize * fontScale) + 'px';
        });
        const sigNameEl = document.getElementById('sigNamePreview');
        if (sigNameEl) sigNameEl.style.fontSize = Math.max(7, parseFloat(sigNameEl.dataset.fontsize || '12') * fontScale) + 'px';
    };

    renderPdf();
    window.addEventListener('resize', renderPdf);

    // Zoom controls — mobile only, zoom in + fit (no zoom out below 100%)
    let zoomScale = 1;
    const zoomContainer = document.getElementById('pdfScrollContainer');
    const zoomInBtn = document.getElementById('zoomIn');
    const zoomResetBtn = document.getElementById('zoomReset');
    const zoomLabel = document.getElementById('zoomLevel');

    const applyZoom = () => {
        const baseWidth = zoomContainer.clientWidth;
        const newWidth = baseWidth * zoomScale;
        container.style.width = newWidth + 'px';
        zoomLabel.textContent = Math.round(zoomScale * 100) + '%';

        // Re-render PDF at new size
        const viewport = page.getViewport({ scale: 1 });
        const scale = newWidth / viewport.width;
        const scaledViewport = page.getViewport({ scale });
        canvas.width = scaledViewport.width;
        canvas.height = scaledViewport.height;
        container.style.height = scaledViewport.height + 'px';
        page.render({ canvasContext: ctx, viewport: scaledViewport });

        // Re-scale fonts
        const fontScale = newWidth / 800;
        fields.forEach(field => {
            const baseFontSize = parseFloat(field.dataset.fontsize || '12');
            field.style.fontSize = Math.max(8, baseFontSize * fontScale) + 'px';
        });
        const sigNameEl = document.getElementById('sigNamePreview');
        if (sigNameEl) sigNameEl.style.fontSize = Math.max(7, parseFloat(sigNameEl.dataset.fontsize || '12') * fontScale) + 'px';
    };

    zoomInBtn.addEventListener('click', () => { zoomScale = Math.min(2, zoomScale + 0.1); applyZoom(); });
    zoomResetBtn.addEventListener('click', () => { zoomScale = 1; container.style.width = '100%'; renderPdf(); zoomLabel.textContent = '100%'; });

    // Signature canvas
    const form = document.querySelector('[data-affidavit-form]');
    const sigCanvas = document.querySelector('[data-signature-canvas]');
    const sigInput = document.querySelector('[data-signature-input]');
    const clearBtn = document.querySelector('[data-clear-signature]');
    const sigError = document.querySelector('[data-signature-error]');
    const sigPreview = document.getElementById('sigPreview');
    const sigNamePreview = document.getElementById('sigNamePreview');
    const guardianInput = document.querySelector('input[name="guardian_name"]');

    if (!sigCanvas) return;
    const sigCtx = sigCanvas.getContext('2d');
    let drawing = false, hasSig = false;

    const resizeSig = () => {
        const rect = sigCanvas.getBoundingClientRect();
        const ratio = window.devicePixelRatio || 1;
        sigCanvas.width = rect.width * ratio;
        sigCanvas.height = 220 * ratio;
        sigCtx.setTransform(ratio, 0, 0, ratio, 0, 0);
        sigCtx.lineCap = 'round';
        sigCtx.lineJoin = 'round';
        sigCtx.lineWidth = 2.5;
        sigCtx.strokeStyle = '#0f172a';
    };

    const pt = (e) => {
        const rect = sigCanvas.getBoundingClientRect();
        const src = e.touches ? e.touches[0] : e;
        return { x: src.clientX - rect.left, y: src.clientY - rect.top };
    };

    const syncPreview = () => {
        currentSignatureData = sigCanvas.toDataURL('image/png');
        sigInput.value = currentSignatureData;
        sigPreview.src = currentSignatureData;
        sigPreview.style.opacity = '1';
    };
    const syncName = () => { sigNamePreview.textContent = guardianInput.value; };
    syncName();
    guardianInput.addEventListener('input', syncName);

    // Mirror Gov't ID fields to PDF overlay
    const idTypeInput = document.querySelector('input[name="govt_id_presented"]');
    const idNumInput = document.querySelector('input[name="id_number"]');
    const idDateInput = document.querySelector('input[name="date_issued"]');
    const idTypeOverlay = document.querySelector('input[name="govt_id_type"]');
    const idNumOverlay = document.querySelector('input[name="govt_id_number"]');
    const idDateOverlay = document.querySelector('input[name="govt_id_date"]');

    const syncIdFields = () => {
        if (idTypeInput && idTypeOverlay) idTypeOverlay.value = idTypeInput.value;
        if (idNumInput && idNumOverlay) idNumOverlay.value = idNumInput.value;
        if (idDateInput && idDateOverlay) idDateOverlay.value = idDateInput.value;
    };
    if (idTypeInput && idTypeOverlay) idTypeInput.addEventListener('input', syncIdFields);
    if (idNumInput && idNumOverlay) idNumInput.addEventListener('input', syncIdFields);
    if (idDateInput && idDateOverlay) idDateInput.addEventListener('input', syncIdFields);
    syncIdFields();

    let autosaveTimer = null;
    const serializeDraft = () => {
        syncIdFields();
        const data = {};
        new FormData(form).forEach((value, key) => {
            if (key !== '_token' && key !== 'agreement') data[key] = value;
        });
        return data;
    };

    const saveDraftLocally = () => {
        localStorage.setItem(draftKey, JSON.stringify({
            savedAt: Date.now(),
            values: serializeDraft(),
        }));
    };

    const autosaveDraft = () => {
        saveDraftLocally();
        clearTimeout(autosaveTimer);
        autosaveTimer = setTimeout(() => {
            fetch(draftUrl, {
                method: 'POST',
                credentials: 'same-origin',
                headers: {
                    'Accept': 'application/json',
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': csrfToken,
                },
                body: JSON.stringify(serializeDraft()),
            }).catch(() => {});
        }, 700);
    };

    const start = (e) => { drawing = true; hasSig = true; sigError.hidden = true; const p = pt(e); sigCtx.beginPath(); sigCtx.moveTo(p.x, p.y); e.preventDefault(); };
    const move = (e) => { if (!drawing) return; const p = pt(e); sigCtx.lineTo(p.x, p.y); sigCtx.stroke(); e.preventDefault(); };
    const stop = () => { if (drawing) { drawing = false; syncPreview(); autosaveDraft(); } else { drawing = false; } };

    let currentSignatureData = sigInput.value || '';
    const drawSignatureData = (dataUrl) => {
        if (!dataUrl) return;
        const img = new Image();
        img.onload = () => {
            const rect = sigCanvas.getBoundingClientRect();
            sigCtx.clearRect(0, 0, sigCanvas.width, sigCanvas.height);
            sigCtx.drawImage(img, 0, 0, rect.width, 220);
            currentSignatureData = dataUrl;
            hasSig = true;
            sigInput.value = dataUrl;
            sigPreview.src = dataUrl;
            sigPreview.style.opacity = '1';
        };
        img.src = dataUrl;
    };

    const originalResizeSig = resizeSig;
    const resizeAndRestoreSignature = () => {
        originalResizeSig();
        if (currentSignatureData) drawSignatureData(currentSignatureData);
    };

    const restoreLocalDraft = () => {
        try {
            const draft = JSON.parse(localStorage.getItem(draftKey) || '{}');
            const values = draft.values || {};
            Object.entries(values).forEach(([name, value]) => {
                const input = form.elements[name];
                if (!input || name === 'applicant_id') return;
                input.value = value;
            });
            syncIdFields();
            syncName();
            if (values.signature_data) {
                currentSignatureData = values.signature_data;
            }
        } catch (error) {}
    };

    form.addEventListener('input', autosaveDraft);
    form.addEventListener('change', autosaveDraft);

    resizeAndRestoreSignature();
    restoreLocalDraft();
    if (currentSignatureData) drawSignatureData(currentSignatureData);
    window.addEventListener('resize', resizeAndRestoreSignature);
    sigCanvas.addEventListener('mousedown', start);
    sigCanvas.addEventListener('mousemove', move);
    window.addEventListener('mouseup', stop);
    sigCanvas.addEventListener('touchstart', start, { passive: false });
    sigCanvas.addEventListener('touchmove', move, { passive: false });
    sigCanvas.addEventListener('touchend', stop);
    clearBtn.addEventListener('click', () => {
        sigCtx.clearRect(0, 0, sigCanvas.width, sigCanvas.height);
        hasSig = false;
        currentSignatureData = '';
        sigInput.value = '';
        sigPreview.style.opacity = '0';
        sigPreview.src = '';
        autosaveDraft();
    });

    form.addEventListener('submit', (e) => {
        if (!hasSig) { e.preventDefault(); sigError.hidden = false; return; }
        sigInput.value = sigCanvas.toDataURL('image/png');
        saveDraftLocally();
    });
});
</script>
@endpush

<style>
.pdf-form-wrapper {
    display: flex;
    flex-direction: column;
    gap: 1rem;
}

.pdf-zoom-toolbar {
    position: sticky;
    top: 0;
    z-index: 10;
    display: none;
    align-items: center;
    justify-content: flex-end;
    gap: 0.4rem;
    padding: 0.5rem 0.75rem;
    background: rgba(255,255,255,0.92);
    border-bottom: 1px solid #e2e8f0;
    backdrop-filter: blur(4px);
}

@media (max-width: 768px) {
    .pdf-zoom-toolbar {
        display: flex;
    }
}

.pdf-zoom-btn {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    width: 32px;
    height: 32px;
    border: 1px solid #e2e8f0;
    border-radius: 6px;
    background: #fff;
    color: #374151;
    font-size: 1.1rem;
    font-weight: 700;
    cursor: pointer;
    transition: background 0.15s, border-color 0.15s;
}

.pdf-zoom-btn:hover {
    background: #f0fdf4;
    border-color: #86efac;
}

.pdf-zoom-reset {
    width: auto;
    padding: 0 0.75rem;
    font-size: 0.75rem;
    font-weight: 600;
}

.pdf-zoom-label {
    font-size: 0.8rem;
    font-weight: 600;
    color: #475569;
    min-width: 40px;
    text-align: center;
}

.pdf-scroll-container {
    overflow: auto;
    border-radius: 8px;
    border: 1px solid #e2e8f0;
    -webkit-overflow-scrolling: touch;
}

.pdf-form-container {
    position: relative;
    width: 100%;
    background: #fff;
    border: 1px solid #e2e8f0;
    border-radius: 8px;
    overflow: hidden;
    box-shadow: 0 4px 20px rgba(0,0,0,0.08);
}

#pdfCanvas {
    display: block;
    width: 100%;
    height: auto;
}

.pdf-overlay-fields {
    position: absolute;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    display: none;
}

.pdf-field {
    position: absolute;
    height: 1.85%;
    border: none;
    border-bottom: 1.5px solid rgba(37, 99, 235, 0.35);
    background: rgba(219, 234, 254, 0.62);
    color: #0f172a;
    font-family: 'Inter', sans-serif;
    font-size: 12px;
    font-weight: 600;
    line-height: 1;
    padding: 0 2px;
    outline: none;
    transition: border-color 0.15s, background 0.15s;
    text-transform: uppercase;
}

.pdf-field.no-caps {
    text-transform: none;
}

.pdf-field:hover {
    background: rgba(191, 219, 254, 0.78);
    border-bottom-color: #2563eb;
}

.pdf-field:focus {
    background: rgba(147, 197, 253, 0.42);
    border-bottom-color: #1d4ed8;
    box-shadow: 0 2px 5px rgba(37, 99, 235, 0.18);
}

.pdf-field::placeholder {
    color: #94a3b8;
    font-weight: 400;
    font-style: italic;
    font-size: 90%;
}

.pdf-signature-section {
    max-width: 500px;
}

@media (max-width: 768px) {
    .pdf-field {
        height: 2.05%;
        padding: 0 1px;
    }

    .pdf-form-wrapper .signature-panel {
        max-width: 100%;
    }

    .pdf-signature-section {
        max-width: 100%;
    }
}
</style>
</x-guest-layout>
