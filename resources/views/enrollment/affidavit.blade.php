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
                        <p>Click the box to open the full signature area.</p>
                    </div>
                    <button type="button" class="signature-preview-trigger" data-open-signature>
                        <canvas data-signature-canvas class="signature-canvas"></canvas>
                        <span data-signature-placeholder class="signature-placeholder">Click to sign</span>
                    </button>
                    <div class="signature-actions">
                        <button type="button" class="btn-primary" data-open-signature>Open signature pad</button>
                        <button type="button" class="affidavit-secondary-btn" data-clear-signature>Clear signature</button>
                        <span data-signature-error class="signature-error" hidden>Please sign before saving.</span>
                    </div>
                </div>

                <div class="signature-modal" data-signature-modal hidden>
                    <div class="signature-modal-panel" role="dialog" aria-modal="true" aria-labelledby="signatureModalTitle">
                        <div class="signature-modal-header">
                            <div>
                                <h2 id="signatureModalTitle">Signature Over Printed Name</h2>
                                <p>Use the full area for your signature, then save it to the affidavit.</p>
                            </div>
                            <button type="button" class="signature-modal-close" data-signature-modal-close aria-label="Close signature pad">&times;</button>
                        </div>
                        <canvas data-signature-full-canvas class="signature-full-canvas" tabindex="0"></canvas>
                        <div class="signature-modal-actions">
                            <button type="button" class="affidavit-secondary-btn" data-signature-modal-clear>Clear</button>
                            <button type="button" class="affidavit-secondary-btn" data-signature-modal-reset>Reset</button>
                            <span data-signature-modal-error class="signature-error" hidden>Please sign before saving.</span>
                            <button type="button" class="btn-primary" data-signature-modal-save>Save signature</button>
                        </div>
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
                    <button type="submit" class="btn-primary">Save Signed Affidavit</button>
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
    const mobilePdfZoomQuery = window.matchMedia('(max-width: 640px) and (pointer: coarse)');
    const canUseMobilePdfZoom = () => mobilePdfZoomQuery.matches;

    const clampZoom = (value) => Math.min(2, Math.max(1, value));

    const applyZoom = () => {
        if (!canUseMobilePdfZoom()) return;
        zoomScale = clampZoom(zoomScale);
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

    zoomInBtn.addEventListener('click', () => {
        if (!canUseMobilePdfZoom()) return;
        zoomScale = clampZoom(zoomScale + 0.1);
        applyZoom();
    });
    zoomResetBtn.addEventListener('click', () => { zoomScale = 1; container.style.width = '100%'; renderPdf(); zoomLabel.textContent = '100%'; });

    let pinchStartDistance = 0;
    let pinchStartZoom = 1;

    const touchDistance = (touches) => {
        const dx = touches[0].clientX - touches[1].clientX;
        const dy = touches[0].clientY - touches[1].clientY;
        return Math.hypot(dx, dy);
    };

    const touchCenterInZoomContainer = (touches) => {
        const rect = zoomContainer.getBoundingClientRect();
        return {
            x: ((touches[0].clientX + touches[1].clientX) / 2) - rect.left,
            y: ((touches[0].clientY + touches[1].clientY) / 2) - rect.top,
        };
    };

    const applyZoomAtPoint = (nextZoom, center) => {
        const oldWidth = Math.max(container.offsetWidth, 1);
        const oldHeight = Math.max(container.offsetHeight, 1);
        const ratioX = (zoomContainer.scrollLeft + center.x) / oldWidth;
        const ratioY = (zoomContainer.scrollTop + center.y) / oldHeight;

        zoomScale = clampZoom(nextZoom);
        applyZoom();

        zoomContainer.scrollLeft = Math.max(0, (ratioX * container.offsetWidth) - center.x);
        zoomContainer.scrollTop = Math.max(0, (ratioY * container.offsetHeight) - center.y);
    };

    zoomContainer.addEventListener('touchstart', (event) => {
        if (!canUseMobilePdfZoom()) return;
        if (event.touches.length !== 2) return;
        pinchStartDistance = touchDistance(event.touches);
        pinchStartZoom = zoomScale;
        event.preventDefault();
    }, { passive: false });

    zoomContainer.addEventListener('touchmove', (event) => {
        if (!canUseMobilePdfZoom()) return;
        if (event.touches.length !== 2 || !pinchStartDistance) return;
        const center = touchCenterInZoomContainer(event.touches);
        const nextZoom = pinchStartZoom * (touchDistance(event.touches) / pinchStartDistance);
        applyZoomAtPoint(nextZoom, center);
        event.preventDefault();
        event.stopPropagation();
    }, { passive: false });

    zoomContainer.addEventListener('touchend', (event) => {
        if (event.touches.length < 2) {
            pinchStartDistance = 0;
        }
    });

    zoomContainer.addEventListener('gesturestart', (event) => {
        if (canUseMobilePdfZoom()) event.preventDefault();
    }, { passive: false });
    zoomContainer.addEventListener('gesturechange', (event) => {
        if (canUseMobilePdfZoom()) event.preventDefault();
    }, { passive: false });

    // Signature canvas
    const form = document.querySelector('[data-affidavit-form]');
    const sigCanvas = document.querySelector('[data-signature-canvas]');
    const fullSigCanvas = document.querySelector('[data-signature-full-canvas]');
    const sigInput = document.querySelector('[data-signature-input]');
    const clearBtn = document.querySelector('[data-clear-signature]');
    const openSigBtns = document.querySelectorAll('[data-open-signature]');
    const sigModal = document.querySelector('[data-signature-modal]');
    const sigModalPanel = sigModal.querySelector('.signature-modal-panel');
    const closeSigBtn = document.querySelector('[data-signature-modal-close]');
    const modalClearBtn = document.querySelector('[data-signature-modal-clear]');
    const modalResetBtn = document.querySelector('[data-signature-modal-reset]');
    const modalSaveBtn = document.querySelector('[data-signature-modal-save]');
    const modalSigError = document.querySelector('[data-signature-modal-error]');
    const sigPlaceholder = document.querySelector('[data-signature-placeholder]');
    const sigError = document.querySelector('[data-signature-error]');
    const sigPreview = document.getElementById('sigPreview');
    const sigNamePreview = document.getElementById('sigNamePreview');
    const guardianInput = document.querySelector('input[name="guardian_name"]');

    if (!sigCanvas || !fullSigCanvas) return;
    const sigCtx = sigCanvas.getContext('2d');
    const fullSigCtx = fullSigCanvas.getContext('2d');
    let drawing = false, hasSig = false, currentSignatureData = sigInput.value || '';
    let modalHasInk = Boolean(currentSignatureData);
    let lastBodyOverflow = '';
    let enteredSignatureFullscreen = false;
    let orientationLockActive = false;
    const mobileSignatureQuery = window.matchMedia('(max-width: 768px)');

    const setupCanvas = (canvas, ctx, height) => {
        const rect = canvas.getBoundingClientRect();
        const ratio = window.devicePixelRatio || 1;
        canvas.width = Math.max(rect.width, 1) * ratio;
        canvas.height = height * ratio;
        ctx.setTransform(ratio, 0, 0, ratio, 0, 0);
        ctx.lineCap = 'round';
        ctx.lineJoin = 'round';
        ctx.lineWidth = canvas === fullSigCanvas ? 3 : 2.5;
        ctx.strokeStyle = '#0f172a';
    };

    const canvasPoint = (canvas, e) => {
        const rect = canvas.getBoundingClientRect();
        const src = e.touches ? e.touches[0] : e;
        return { x: src.clientX - rect.left, y: src.clientY - rect.top };
    };

    const clearCanvas = (canvas, ctx) => {
        const rect = canvas.getBoundingClientRect();
        ctx.clearRect(0, 0, rect.width, rect.height);
    };

    const drawSignatureTo = (canvas, ctx, dataUrl, updateState = false) => {
        clearCanvas(canvas, ctx);
        if (!dataUrl) return;

        const img = new Image();
        img.onload = () => {
            const rect = canvas.getBoundingClientRect();
            clearCanvas(canvas, ctx);
            ctx.drawImage(img, 0, 0, rect.width, rect.height);
            if (updateState) {
                currentSignatureData = dataUrl;
                hasSig = true;
                sigInput.value = dataUrl;
                sigPreview.src = dataUrl;
                sigPreview.style.opacity = '1';
                sigPlaceholder.hidden = true;
            }
        };
        img.src = dataUrl;
    };

    const resizeSig = () => {
        setupCanvas(sigCanvas, sigCtx, sigCanvas.getBoundingClientRect().height || 240);
        if (currentSignatureData) drawSignatureTo(sigCanvas, sigCtx, currentSignatureData);
    };

    const resizeFullSig = () => {
        const rect = fullSigCanvas.getBoundingClientRect();
        setupCanvas(fullSigCanvas, fullSigCtx, Math.max(rect.height, 320));
        if (currentSignatureData) drawSignatureTo(fullSigCanvas, fullSigCtx, currentSignatureData);
    };

    const setSignatureData = (dataUrl) => {
        currentSignatureData = dataUrl;
        hasSig = Boolean(dataUrl);
        modalHasInk = Boolean(dataUrl);
        sigInput.value = currentSignatureData;
        sigPreview.src = currentSignatureData;
        sigPreview.style.opacity = currentSignatureData ? '1' : '0';
        sigPlaceholder.hidden = Boolean(currentSignatureData);
        resizeSig();
        if (currentSignatureData) {
            drawSignatureTo(sigCanvas, sigCtx, currentSignatureData);
        } else {
            clearCanvas(sigCanvas, sigCtx);
            sigPreview.removeAttribute('src');
        }
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

    const requestMobileLandscape = async () => {
        if (!mobileSignatureQuery.matches) return;

        try {
            if (!document.fullscreenElement && sigModalPanel.requestFullscreen) {
                await sigModalPanel.requestFullscreen({ navigationUI: 'hide' });
                enteredSignatureFullscreen = true;
            }
        } catch (error) {}

        try {
            if (screen.orientation && screen.orientation.lock) {
                await screen.orientation.lock('landscape');
                orientationLockActive = true;
            }
        } catch (error) {}

        requestAnimationFrame(resizeFullSig);
        setTimeout(resizeFullSig, 350);
    };

    const releaseMobileLandscape = async () => {
        if (orientationLockActive && screen.orientation && screen.orientation.unlock) {
            try { screen.orientation.unlock(); } catch (error) {}
        }
        orientationLockActive = false;

        if (enteredSignatureFullscreen && document.fullscreenElement) {
            try { await document.exitFullscreen(); } catch (error) {}
        }
        enteredSignatureFullscreen = false;
    };

    const openSignatureModal = async () => {
        sigError.hidden = true;
        modalSigError.hidden = true;
        sigModal.hidden = false;
        lastBodyOverflow = document.body.style.overflow;
        document.body.style.overflow = 'hidden';
        requestAnimationFrame(() => {
            resizeFullSig();
            modalHasInk = Boolean(currentSignatureData);
            fullSigCanvas.focus();
        });
        await requestMobileLandscape();
    };

    const closeSignatureModal = () => {
        sigModal.hidden = true;
        document.body.style.overflow = lastBodyOverflow;
        drawing = false;
        releaseMobileLandscape();
    };

    const start = (e) => {
        drawing = true;
        sigError.hidden = true;
        modalSigError.hidden = true;
        modalHasInk = true;
        const p = canvasPoint(fullSigCanvas, e);
        fullSigCtx.beginPath();
        fullSigCtx.moveTo(p.x, p.y);
        e.preventDefault();
    };

    const move = (e) => {
        if (!drawing) return;
        const p = canvasPoint(fullSigCanvas, e);
        fullSigCtx.lineTo(p.x, p.y);
        fullSigCtx.stroke();
        e.preventDefault();
    };

    const stop = () => {
        drawing = false;
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

    restoreLocalDraft();
    resizeSig();
    if (currentSignatureData) setSignatureData(currentSignatureData);
    window.addEventListener('resize', () => {
        resizeSig();
        if (!sigModal.hidden) resizeFullSig();
    });
    openSigBtns.forEach((button) => button.addEventListener('click', openSignatureModal));
    closeSigBtn.addEventListener('click', closeSignatureModal);
    sigModal.addEventListener('click', (e) => {
        if (e.target === sigModal) closeSignatureModal();
    });
    document.addEventListener('keydown', (e) => {
        if (e.key === 'Escape' && !sigModal.hidden) closeSignatureModal();
    });
    document.addEventListener('fullscreenchange', () => {
        if (!document.fullscreenElement) enteredSignatureFullscreen = false;
        if (!sigModal.hidden) requestAnimationFrame(resizeFullSig);
    });
    fullSigCanvas.addEventListener('mousedown', start);
    fullSigCanvas.addEventListener('mousemove', move);
    window.addEventListener('mouseup', stop);
    fullSigCanvas.addEventListener('touchstart', start, { passive: false });
    fullSigCanvas.addEventListener('touchmove', move, { passive: false });
    fullSigCanvas.addEventListener('touchend', stop);
    modalClearBtn.addEventListener('click', () => {
        clearCanvas(fullSigCanvas, fullSigCtx);
        modalHasInk = false;
    });
    modalResetBtn.addEventListener('click', () => {
        resizeFullSig();
        modalHasInk = Boolean(currentSignatureData);
        modalSigError.hidden = true;
    });
    modalSaveBtn.addEventListener('click', () => {
        if (!modalHasInk) {
            modalSigError.hidden = false;
            return;
        }
        const dataUrl = fullSigCanvas.toDataURL('image/png');
        setSignatureData(dataUrl);
        autosaveDraft();
        closeSignatureModal();
    });
    clearBtn.addEventListener('click', () => {
        setSignatureData('');
        clearCanvas(fullSigCanvas, fullSigCtx);
        autosaveDraft();
    });

    form.addEventListener('submit', (e) => {
        if (!hasSig || !currentSignatureData) { e.preventDefault(); sigError.hidden = false; openSignatureModal(); return; }
        sigInput.value = currentSignatureData;
        saveDraftLocally();
    });
});
</script>
@endpush

<style>
.signature-panel {
    background: #f8fafc;
    border: 1px solid #e2e8f0;
    border-radius: 12px;
    display: flex;
    flex-direction: column;
    gap: 0.75rem;
    padding: 1rem;
}

.signature-panel h2,
.signature-modal-header h2 {
    color: #020617;
    font-size: 1rem;
    font-weight: 800;
    margin: 0;
}

.signature-panel p,
.signature-modal-header p {
    color: #475569;
    font-size: 0.9rem;
    margin: 0.25rem 0 0;
}

.signature-preview-trigger {
    appearance: none;
    background: #ffffff;
    border: 1px dashed #94a3b8;
    border-radius: 10px;
    cursor: pointer;
    display: block;
    min-height: 240px;
    overflow: hidden;
    padding: 0;
    position: relative;
    width: 100%;
}

.signature-preview-trigger:hover,
.signature-preview-trigger:focus-visible {
    border-color: #059669;
    box-shadow: 0 0 0 3px rgba(16, 185, 129, 0.16);
    outline: none;
}

.signature-canvas {
    display: block;
    height: 240px;
    pointer-events: none;
    width: 100%;
}

.signature-placeholder {
    color: #64748b;
    font-size: 0.95rem;
    font-weight: 700;
    left: 50%;
    pointer-events: none;
    position: absolute;
    top: 50%;
    transform: translate(-50%, -50%);
}

.signature-actions,
.affidavit-actions {
    align-items: center;
    display: flex;
    flex-wrap: wrap;
    gap: 0.75rem;
    justify-content: space-between;
}

.signature-error {
    color: #dc2626;
    font-size: 0.85rem;
    font-weight: 700;
}

.signature-modal {
    align-items: center;
    background: rgba(15, 23, 42, 0.58);
    display: flex;
    inset: 0;
    justify-content: center;
    padding: 1rem;
    position: fixed;
    z-index: 80;
}

.signature-modal[hidden] {
    display: none;
}

.signature-modal-panel {
    background: #ffffff;
    border-radius: 14px;
    box-shadow: 0 24px 70px rgba(15, 23, 42, 0.28);
    display: flex;
    flex-direction: column;
    gap: 1rem;
    height: min(86vh, 660px);
    max-width: 1080px;
    padding: 1rem;
    width: min(96vw, 1080px);
}

.signature-modal-panel:fullscreen {
    border-radius: 0;
    height: 100dvh;
    max-width: none;
    width: 100vw;
}

.signature-modal-panel:-webkit-full-screen {
    border-radius: 0;
    height: 100dvh;
    max-width: none;
    width: 100vw;
}

.signature-modal-header {
    align-items: flex-start;
    display: flex;
    gap: 1rem;
    justify-content: space-between;
}

.signature-modal-close {
    align-items: center;
    background: #f8fafc;
    border: 1px solid #e2e8f0;
    border-radius: 8px;
    color: #0f172a;
    cursor: pointer;
    display: inline-flex;
    font-size: 1.5rem;
    font-weight: 700;
    height: 40px;
    justify-content: center;
    line-height: 1;
    width: 40px;
}

.signature-full-canvas {
    background: #ffffff;
    border: 1px dashed #94a3b8;
    border-radius: 10px;
    flex: 1;
    min-height: 360px;
    touch-action: none;
    width: 100%;
}

.signature-full-canvas:focus {
    outline: 3px solid rgba(16, 185, 129, 0.18);
}

.signature-modal-actions {
    align-items: center;
    display: flex;
    flex-wrap: wrap;
    gap: 0.75rem;
    justify-content: flex-end;
}

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

@media (max-width: 640px) and (pointer: coarse) {
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
    overflow: visible;
    border-radius: 8px;
    border: 1px solid #e2e8f0;
}

@media (max-width: 640px) and (pointer: coarse) {
    .pdf-scroll-container {
        overflow: auto;
        -webkit-overflow-scrolling: touch;
        overscroll-behavior: contain;
        touch-action: pan-x pan-y;
    }
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
    .signature-preview-trigger,
    .signature-canvas {
        min-height: 190px;
        height: 190px;
    }

    .signature-modal {
        padding: 0;
    }

    .signature-modal-panel {
        border-radius: 0;
        height: 100dvh;
        padding: 0.85rem;
        width: 100vw;
    }

    .signature-full-canvas {
        min-height: 56dvh;
    }

    .signature-modal-actions {
        justify-content: stretch;
    }

    .signature-modal-actions .btn-primary,
    .signature-modal-actions .affidavit-secondary-btn {
        flex: 1 1 120px;
    }

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

@media (max-height: 768px) and (orientation: landscape) {
    .signature-modal-panel,
    .signature-modal-panel:fullscreen,
    .signature-modal-panel:-webkit-full-screen {
        border-radius: 0;
        display: grid;
        gap: 0.75rem;
        grid-template-columns: minmax(170px, 230px) minmax(0, 1fr);
        grid-template-rows: minmax(0, 1fr) auto;
        height: 100dvh;
        max-width: none;
        padding: 0.75rem;
        width: 100vw;
    }

    .signature-modal-header {
        flex-direction: column;
        grid-row: 1 / 3;
    }

    .signature-full-canvas {
        grid-column: 2;
        min-height: 0;
    }

    .signature-modal-actions {
        grid-column: 2;
        justify-content: flex-end;
    }
}
</style>
</x-guest-layout>
