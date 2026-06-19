@props([
    'title',
    'name',
    'description' => '',
    'required' => false,
    'accept' => 'image/jpeg,image/jpg,image/png',
    'maxSizeMB' => 5,
    'hint' => '',
    'uploaded' => null,
    'guideTitle' => 'Guide',
    'guide' => [],
    'guideNotice' => '',
    'guideNoticeGender' => null,
    'uploadNotice' => '',
    'uploadNoticeGender' => null,
    'supportLinks' => [],
    'supportLinksGender' => null,
    'supportPanels' => [],
    'supportPanelsGender' => null,
    'supportPanelGroups' => [],
    'guideImages' => [],
    'guideImageGroups' => [],
    'showPhotoSample' => false,
    'deferUpload' => false,
])

@php
    $uploadedBasename = $uploaded ? basename($uploaded) : null;
    $uploadedIsPdf = $uploaded ? str_ends_with(strtolower($uploaded), '.pdf') : false;

    // Parse accept string to dynamic friendly formats
    $acceptParts = explode(',', $accept);
    $friendlyAccepts = [];
    foreach ($acceptParts as $part) {
        $part = trim(strtolower($part));
        if ($part === 'application/pdf' || str_ends_with($part, '.pdf')) {
            $friendlyAccepts[] = 'PDF';
        } elseif (str_contains($part, 'jpeg') || str_contains($part, 'jpg') || str_ends_with($part, '.jpg') || str_ends_with($part, '.jpeg')) {
            $friendlyAccepts[] = 'JPG';
            $friendlyAccepts[] = 'JPEG';
        } elseif (str_contains($part, 'png') || str_ends_with($part, '.png')) {
            $friendlyAccepts[] = 'PNG';
        }
    }
    $friendlyAccepts = array_unique($friendlyAccepts);
    $formattedAccept = implode(', ', $friendlyAccepts);
@endphp

<script>
if (!window.AMIS_UploadUtils) {
    window.AMIS_UploadUtils = {
        // Validate file type format
        validateFile(file, acceptStr) {
            if (!acceptStr) return { valid: true };
            const allowed = acceptStr.split(',').map(s => s.trim().toLowerCase());
            const fileName = file.name.toLowerCase();
            const fileType = file.type.toLowerCase();

            let match = false;
            for (const item of allowed) {
                if (item.startsWith('.')) {
                    if (fileName.endsWith(item)) { match = true; break; }
                } else if (item.endsWith('/*')) {
                    const prefix = item.slice(0, -1); // e.g. "image/"
                    if (fileType.startsWith(prefix)) { match = true; break; }
                } else {
                    if (fileType === item) { match = true; break; }
                }
            }

            if (!match) {
                const readableTypes = allowed.map(item => {
                    if (item.startsWith('.')) return item.substring(1).toUpperCase();
                    if (item === 'application/pdf') return 'PDF';
                    if (item.startsWith('image/')) return item.substring(6).toUpperCase();
                    return item;
                });
                return {
                    valid: false,
                    error: `Unsupported file format. Supported: ${[...new Set(readableTypes)].join(', ')}.`
                };
            }
            return { valid: true };
        },

        // Client-side image compression using canvas
        compressImage(file, quality = 0.8) {
            return new Promise((resolve, reject) => {
                const reader = new FileReader();
                reader.readAsDataURL(file);
                reader.onload = (event) => {
                    const img = new Image();
                    img.src = event.target.result;
                    img.onload = () => {
                        try {
                            const canvas = document.createElement('canvas');
                            let width = img.width;
                            let height = img.height;

                            // High print resolution cap (2048px on the longest side)
                            const maxDim = 2048;
                            if (width > maxDim || height > maxDim) {
                                if (width > height) {
                                    height = Math.round((height * maxDim) / width);
                                    width = maxDim;
                                } else {
                                    width = Math.round((width * maxDim) / height);
                                    height = maxDim;
                                }
                            }

                            canvas.width = width;
                            canvas.height = height;

                            const ctx = canvas.getContext('2d');
                            // Paint canvas with a solid white background (converting transparent PNGs cleanly to JPEG)
                            ctx.fillStyle = '#FFFFFF';
                            ctx.fillRect(0, 0, width, height);

                            ctx.drawImage(img, 0, 0, width, height);

                            canvas.toBlob((blob) => {
                                if (!blob) {
                                    reject(new Error('Optimizing image canvas conversion failed.'));
                                    return;
                                }
                                const optimizedFile = new File([blob], file.name, {
                                    type: 'image/jpeg',
                                    lastModified: Date.now()
                                });
                                resolve(optimizedFile);
                            }, 'image/jpeg', quality);
                        } catch (e) {
                            reject(e);
                        }
                    };
                    img.onerror = () => reject(new Error('Selected file could not be read as an image.'));
                };
                reader.onerror = () => reject(new Error('Selected file reader error.'));
            });
        },

        cloneFile(file) {
            if (file.arrayBuffer) {
                return file.arrayBuffer().then((buffer) => new File([buffer], file.name, {
                    type: file.type || 'application/octet-stream',
                    lastModified: Date.now()
                }));
            }

            return new Promise((resolve, reject) => {
                const reader = new FileReader();
                reader.onload = () => resolve(new File([reader.result], file.name, {
                    type: file.type || 'application/octet-stream',
                    lastModified: Date.now()
                }));
                reader.onerror = () => reject(new Error('Selected file could not be read.'));
                reader.readAsArrayBuffer(file);
            });
        },

        replaceInputFile(input, file) {
            const dt = new DataTransfer();
            dt.items.add(file);
            input.files = dt.files;
        }
    };
}

function registerUploadComponent(Alpine) {
    // Define globally on window so Alpine can find it even if initialization races with asset loading
    window.uploadRequirementCard = (config) => ({
        fileName: '',
        preview: '',
        hasUploaded: !!config.uploaded,
        uploadedUrl: config.uploadedPath ? '{{ asset('storage') }}/' + config.uploadedPath : '',
        uploadedIsPdf: config.uploadedPath ? {{ str_ends_with(strtolower($uploaded ?? ''), '.pdf') ? 'true' : 'false' }} : false,
        selectedIsPdf: false,
        showUpload: !config.deferUpload,
        removingUploaded: false,
        supportModal: null,
        uploadedName: config.uploadedName || 'Saved file',
        isProcessing: false,
        errorMsg: '',
        init() {
            window.addEventListener('enrollment:file-uploaded', (event) => {
                if (event.detail?.name === config.name) {
                    this.hasUploaded = true;
                    this.uploadedName = this.fileName || this.uploadedName;
                    this.uploadedIsPdf = this.selectedIsPdf;
                    if (!this.uploadedIsPdf) {
                        this.uploadedUrl = this.preview;
                    } else {
                        this.uploadedUrl = '';
                    }
                    this.fileName = '';
                    this.$refs.input.value = '';
                }
            });
        },
        get currentState() {
            if (this.fileName) return 'selected';
            if (this.hasUploaded) return 'uploaded';
            return 'empty';
        },
        async removeUploaded() {
            if (!this.hasUploaded || this.removingUploaded) return;
            this.removingUploaded = true;
            this.errorMsg = '';
            try {
                const applicantQuery = window.AMIS_CURRENT_APPLICANT_ID ? '?applicant=' + encodeURIComponent(window.AMIS_CURRENT_APPLICANT_ID) : '';
                const response = await fetch(config.removeUrl + applicantQuery, {
                    method: 'DELETE',
                    headers: {
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                        'Accept': 'application/json',
                    },
                });
                if (!response.ok) throw new Error('Unable to remove file');
                this.hasUploaded = false;
                this.uploadedUrl = '';
                this.uploadedIsPdf = false;
                this.preview = '';
                this.selectedIsPdf = false;
                this.uploadedName = 'Saved file';
                window.dispatchEvent(new CustomEvent('enrollment:file-removed', {
                    detail: { name: config.name }
                }));
            } catch (_) {
                window.dispatchEvent(new CustomEvent('enrollment:file-remove-failed', {
                    detail: { name: config.name }
                }));
            } finally {
                this.removingUploaded = false;
            }
        },
        clearSelected() {
            this.fileName = '';
            this.preview = '';
            this.selectedIsPdf = false;
            this.$refs.input.value = '';
            window.dispatchEvent(new CustomEvent('enrollment:file-removed', {
                detail: { name: config.name }
            }));
        },
        chooseFile() {
            this.$refs.input.click();
        },
        revealUpload(openPicker = false) {
            this.showUpload = true;
            if (openPicker) this.$nextTick(() => this.chooseFile());
        },
        showChoices() {
            this.showUpload = false;
        },
        openSupportModal(src, label, alt) {
            this.supportModal = { src, label, alt };
        },
        closeSupportModal() {
            this.supportModal = null;
        },
        async handleFileChange(event) {
            let file = event.target.files[0];
            if (!file) return;

            this.errorMsg = '';
            const name = config.name;

            this.isProcessing = true;
            window.dispatchEvent(new CustomEvent('enrollment:file-processing-started', {
                detail: { name: name }
            }));

            try {
                const validation = window.AMIS_UploadUtils.validateFile(file, config.accept);
                if (!validation.valid) {
                    this.errorMsg = validation.error;
                    this.clearSelected();
                    return;
                }

                const isPdf = file.type === 'application/pdf' || file.name.toLowerCase().endsWith('.pdf');
                this.selectedIsPdf = isPdf;

                try {
                    file = await window.AMIS_UploadUtils.cloneFile(file);
                    window.AMIS_UploadUtils.replaceInputFile(this.$refs.input, file);
                } catch (e) {
                    console.error('Stable upload copy failed:', e);
                    this.errorMsg = 'Selected file could not be accessed. Please choose it again from your gallery or file manager.';
                    this.clearSelected();
                    return;
                }

                const maxSizeMB = config.maxSizeMB;
                const fileSizeMB = file.size / (1024 * 1024);
                const isImage = file.type.startsWith('image/');

                if (!isImage) {
                    if (fileSizeMB > maxSizeMB) {
                        this.errorMsg = 'File size exceeds the maximum limit of ' + maxSizeMB + 'MB.';
                        this.clearSelected();
                        return;
                    }
                } else {
                    let needsCompression = false;
                    let quality = 0.82;

                    if (name === 'photo_2x2') {
                        if (fileSizeMB > 2) {
                            needsCompression = true;
                            quality = 0.80;
                        }
                    } else {
                        if (fileSizeMB > maxSizeMB) {
                            needsCompression = true;
                            quality = 0.88;
                        } else if (fileSizeMB > 3) {
                            needsCompression = true;
                            quality = 0.90;
                        }
                    }

                    if (needsCompression) {
                        try {
                            const originalSize = file.size;
                            const optimizedFile = await window.AMIS_UploadUtils.compressImage(file, quality);
                            if (optimizedFile.size < originalSize) {
                                file = optimizedFile;
                                window.AMIS_UploadUtils.replaceInputFile(this.$refs.input, optimizedFile);
                            }
                        } catch (e) {
                            console.error('Image compression error:', e);
                            if (fileSizeMB > maxSizeMB) {
                                this.errorMsg = 'Image optimization failed and the file exceeds the maximum limit of ' + maxSizeMB + 'MB.';
                                this.clearSelected();
                                return;
                            }
                        }
                    }
                }

                const finalSizeMB = file.size / (1024 * 1024);
                if (finalSizeMB > maxSizeMB) {
                    this.errorMsg = 'The selected file exceeds the maximum allowed limit of ' + maxSizeMB + 'MB.';
                    this.clearSelected();
                    return;
                }

                this.fileName = file.name;
                this.hasUploaded = false;

                if (file.type.startsWith('image/')) {
                    const reader = new FileReader();
                    reader.onload = (e) => this.preview = e.target.result;
                    reader.readAsDataURL(file);
                } else {
                    this.preview = '';
                }

                window.dispatchEvent(new CustomEvent('enrollment:file-selected', {
                    detail: { name: name }
                }));
            } finally {
                this.isProcessing = false;
                window.dispatchEvent(new CustomEvent('enrollment:file-processing-finished', {
                    detail: { name: name }
                }));
            }
        }
    });

    if (Alpine && Alpine.data) {
        try {
            Alpine.data('uploadRequirementCard', window.uploadRequirementCard);
        } catch(e) {
            console.error('Failed to register upload component data', e);
        }
    }
}

if (!window.AMIS_UploadComponentRegistered) {
    window.AMIS_UploadComponentRegistered = true;
    if (window.Alpine) {
        registerUploadComponent(window.Alpine);
    } else {
        document.addEventListener('alpine:init', () => {
            registerUploadComponent(window.Alpine);
        });
    }
}
</script>

<div
    x-data="uploadRequirementCard({
        name: '{{ $name }}',
        uploaded: {{ $uploaded ? 'true' : 'false' }},
        uploadedPath: @js($uploaded),
        deferUpload: {{ $deferUpload ? 'true' : 'false' }},
        uploadedName: @js($uploadedBasename),
        removeUrl: '{{ route('enrollment.draft.document.remove', ['document' => $name]) }}',
        accept: '{{ $accept }}',
        maxSizeMB: {{ $maxSizeMB }}
    })"
    @keydown.escape.window="closeSupportModal()"
    class="!flex !h-full !flex-col !rounded-2xl !border !border-slate-200 !bg-white !p-5 sm:!p-6"
>
    <div class="!mb-4">
        <div class="!flex !items-start !justify-between !gap-3">
            <div class="!min-w-0">
                <h3 class="!m-0 !text-base !font-semibold !leading-6 !text-slate-900">{{ $title }}</h3>
                @if ($description !== '')
                    <p class="!mt-1 !text-sm !leading-6 !text-slate-600">{{ $description }}</p>
                @endif
            </div>
            <span class="!shrink-0 !rounded-full {{ $required ? '!bg-emerald-50 !text-emerald-700' : '!bg-sky-50 !text-sky-700' }} !px-2.5 !py-1 !text-xs !font-semibold">
                {{ $required ? 'Required' : 'Optional' }}
            </span>
        </div>
    </div>

    @if (trim($slot) !== '')
        <div class="!mb-4" @if($deferUpload) x-show="!showUpload" x-cloak @endif>
            {{ $slot }}
        </div>
    @endif

    @if ($uploadNotice !== '')
        <div
            @if ($uploadNoticeGender)
                x-show.important="($store.enrollmentGuide?.gender || @js(array_key_first($guideImageGroups))) === @js($uploadNoticeGender)"
                x-cloak
            @endif
            class="upload-privacy-notice"
        >
            <span class="upload-privacy-notice__icon">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10Z"/>
                    <path stroke-linecap="round" stroke-linejoin="round" d="m9 12 2 2 4-4"/>
                </svg>
            </span>
            <span class="upload-privacy-notice__body">
                <span class="upload-privacy-notice__title">Privacy-respecting review</span>
                <span class="upload-privacy-notice__text">{{ $uploadNotice }}</span>
            </span>
        </div>
    @endif

    <div x-show="showUpload" @if($deferUpload) x-cloak @endif>
        @if ($deferUpload)
            <button
                type="button"
                @click="showChoices()"
                class="upload-choice-back"
                aria-label="Back to report card options"
            >
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M15 18l-6-6 6-6"/>
                </svg>
                <span>Back</span>
            </button>
        @endif

        <div
            class="!relative !mx-auto !flex !aspect-square !w-full !max-w-[280px] !overflow-hidden !rounded-2xl !bg-slate-50"
            :class="{
                '!border-2 !border-dashed !border-slate-300 hover:!border-emerald-400 hover:!bg-emerald-50': currentState === 'empty',
                '!border !border-sky-100 !bg-sky-50': currentState === 'selected',
                '!border !border-emerald-100 !bg-emerald-50': currentState === 'uploaded'
            }"
        >
            <!-- Loading/Processing Overlay -->
            <div
                x-show="isProcessing"
                x-cloak
                style="position: absolute !important; inset: 0 !important; z-index: 50 !important; display: flex !important; align-items: center !important; justify-content: center !important; background: rgba(248, 250, 252, 0.75) !important; backdrop-filter: blur(4px) !important; border-radius: 1rem !important; box-sizing: border-box !important;"
            >
                <style>
                    @keyframes upload-card-spin {
                        to { transform: rotate(360deg); }
                    }
                    .upload-card-spinner {
                        animation: upload-card-spin 1s linear infinite !important;
                    }
                </style>
                <div style="background: #ffffff !important; border: 1px solid #e2e8f0 !important; border-radius: 16px !important; box-shadow: 0 10px 15px -3px rgba(0, 0, 0, 0.1), 0 4px 6px -4px rgba(0, 0, 0, 0.05) !important; padding: 1.25rem 1.5rem !important; width: 85% !important; max-width: 200px !important; display: flex !important; flex-direction: column !important; align-items: center !important; justify-content: center !important; text-align: center !important; box-sizing: border-box !important;">
                    <div style="display: flex !important; align-items: center !important; justify-content: center !important; margin-bottom: 0.5rem !important; width: 100% !important;">
                        <svg class="upload-card-spinner" style="width: 32px !important; height: 32px !important; display: block !important; margin: 0 auto !important; flex-shrink: 0 !important;" viewBox="0 0 24 24" fill="none">
                            <circle cx="12" cy="12" r="10" stroke="#e2e8f0" stroke-width="3"></circle>
                            <path d="M12 2a10 10 0 0 1 10 10" stroke="#10b981" stroke-width="3" stroke-linecap="round"></path>
                        </svg>
                    </div>
                    <span style="font-size: 0.85rem !important; font-weight: 700 !important; color: #0f172a !important; display: block !important; margin-bottom: 0.15rem !important; width: 100% !important; line-height: 1.25 !important;">Compressing Image...</span>
                    <span style="font-size: 0.72rem !important; font-weight: 500 !important; color: #64748b !important; display: block !important; width: 100% !important; line-height: 1.2 !important;">Optimizing for upload</span>
                </div>
            </div>

            <template x-if="currentState === 'empty'">
                <button
                    type="button"
                    @click="chooseFile()"
                    class="!flex !h-full !w-full !items-center !justify-center !p-5 !text-center focus-visible:!outline-none focus-visible:!ring-4 focus-visible:!ring-emerald-100"
                >
                    <div class="!flex !flex-col !items-center !justify-center !gap-2">
                        <div class="!flex !h-11 !w-11 !items-center !justify-center !rounded-full !bg-white !text-slate-400 !shadow-sm">
                            <svg class="!h-5 !w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M7 16a4 4 0 0 1-.88-7.903A5 5 0 1 1 15.9 6L16 6a5 5 0 0 1 1 9.9M15 13l-3-3m0 0l-3 3m3-3v12"/>
                            </svg>
                        </div>
                        <div class="!space-y-1">
                            <p class="!m-0 !text-sm !font-bold !leading-5 !text-emerald-700">Choose file</p>
                            <p class="!m-0 !text-[11px] !font-medium !leading-4 !text-slate-400">
                                Accepted: <span class="!font-semibold !text-slate-600">{{ $formattedAccept }}</span>
                            </p>

                            @if($name === 'photo_2x2')
                                <p class="!m-0 !text-[10px] !font-semibold !text-emerald-600 !mt-0.5">(Large images auto-compressed)</p>
                            @elseif(str_contains($accept, 'pdf'))
                                <p class="!m-0 !text-[10px] !font-semibold !text-emerald-600 !mt-0.5">(Images optimized automatically)</p>
                            @endif
                        </div>
                    </div>
                </button>
            </template>

            <template x-if="currentState === 'selected'">
                <div class="!relative !flex !h-full !w-full !items-center !justify-center !p-4">
                    <img x-show="preview" :src="preview" alt="Selected file preview" class="!absolute !inset-0 !h-full !w-full !object-contain !p-3">
                    <div x-show="!preview" class="!flex !flex-col !items-center !justify-center !gap-3 !text-sky-700">
                        <div class="!flex !h-16 !w-16 !items-center !justify-center !rounded-2xl !bg-white">
                            <svg class="!h-8 !w-8" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/>
                                <polyline points="14 2 14 8 20 8"/>
                                <line x1="8" y1="13" x2="16" y2="13"/>
                                <line x1="8" y1="17" x2="14" y2="17"/>
                            </svg>
                        </div>
                        <p class="!m-0 !text-sm !font-semibold">Document File</p>
                    </div>
                    <div class="!absolute !inset-x-0 !bottom-0 !bg-white/90 !px-3 !py-2 !backdrop-blur-sm">
                        <p class="!m-0 !truncate !text-sm !font-semibold !leading-5 !text-slate-900" x-text="fileName"></p>
                        <p class="!m-0 !text-xs !leading-4 !text-slate-500">Selected file</p>
                    </div>
                </div>
            </template>

            <template x-if="currentState === 'uploaded'">
                <div class="!relative !flex !h-full !w-full !items-center !justify-center !p-4">
                    <!-- Dynamic image display (from server path or local session preview) -->
                    <template x-if="uploadedUrl && !uploadedIsPdf">
                        <img :src="uploadedUrl" alt="{{ $title }}" class="!absolute !inset-0 !h-full !w-full !object-contain !p-3">
                    </template>
                    
                    <!-- File icon fallback for PDFs or missing images -->
                    <template x-if="uploadedIsPdf || !uploadedUrl">
                        <div class="!flex !flex-col !items-center !justify-center !gap-3 !text-emerald-700">
                            <div class="!flex !h-16 !w-16 !items-center !justify-center !rounded-2xl !bg-white">
                                <svg class="!h-8 !w-8" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                    <path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/>
                                    <polyline points="14 2 14 8 20 8"/>
                                    <line x1="8" y1="13" x2="16" y2="13"/>
                                    <line x1="8" y1="17" x2="14" y2="17"/>
                                </svg>
                            </div>
                            <p class="!m-0 !text-sm !font-semibold">Saved file</p>
                        </div>
                    </template>
                    <div class="!absolute !inset-x-0 !bottom-0 !bg-white/90 !px-3 !py-2 !backdrop-blur-sm">
                        <p class="!m-0 !truncate !text-sm !font-semibold !leading-5 !text-slate-900" x-text="uploadedName"></p>
                        <p class="!m-0 !text-xs !leading-4 !text-emerald-700">Uploaded</p>
                    </div>
                </div>
            </template>

            <template x-if="currentState !== 'empty'">
                <div class="!absolute !right-2 !top-2 !flex !gap-1.5">
                    <button
                        type="button"
                        @click.stop="chooseFile()"
                        class="!rounded-full !bg-white/95 !px-3 !py-1.5 !text-xs !font-semibold !text-slate-700 !shadow-sm !backdrop-blur hover:!bg-white focus-visible:!outline-none focus-visible:!ring-4 focus-visible:!ring-emerald-100"
                    >
                        Replace
                    </button>
                    <button
                        type="button"
                        @click.stop="currentState === 'selected' ? clearSelected() : removeUploaded()"
                        :disabled="currentState === 'uploaded' && removingUploaded"
                        class="!rounded-full !bg-white/95 !px-3 !py-1.5 !text-xs !font-semibold !text-rose-600 !shadow-sm !backdrop-blur hover:!bg-white focus-visible:!outline-none focus-visible:!ring-4 focus-visible:!ring-rose-100 disabled:!cursor-wait disabled:!opacity-70"
                    >
                        <span x-text="currentState === 'uploaded' && removingUploaded ? '...' : 'Delete'"></span>
                    </button>
                </div>
            </template>
        </div>

        <input
            x-ref="input"
            type="file"
            id="{{ $name }}"
            name="{{ $name }}"
            accept="{{ $accept }}"
            class="!sr-only"
            @change="handleFileChange($event)"
        >

        <div class="!mt-4 !rounded-xl !bg-slate-50 !p-4">
            <p class="!m-0 !text-sm !font-semibold !leading-6 !text-slate-800">{{ $guideTitle }}</p>
            <div class="!mt-2 !space-y-3">
                @if (count($guideImageGroups))
                    @foreach ($guideImageGroups as $groupName => $images)
                        <div
                            x-show.important="($store.enrollmentGuide?.gender || @js(array_key_first($guideImageGroups))) === @js($groupName)"
                            x-cloak
                            class="!grid {{ count($images) === 2 ? '!grid-cols-2' : '!grid-cols-3' }} !gap-2"
                        >
                            @foreach ($images as $image)
                                <figure class="!m-0 !overflow-hidden !rounded-xl !bg-white">
                                    <img
                                        src="{{ asset($image['src']) }}"
                                        alt="{{ $image['alt'] ?? 'Upload guide example' }}"
                                        class="!aspect-square !h-auto !w-full !object-cover"
                                    >
                                    @if (!empty($image['label']))
                                        <figcaption class="!px-2 !py-1.5 !text-center !text-[11px] !font-semibold !leading-4 {{ ($image['tone'] ?? '') === 'danger' ? '!text-rose-700' : (($image['tone'] ?? '') === 'success' ? '!text-emerald-700' : '!text-slate-600') }}">
                                            {{ $image['label'] }}
                                        </figcaption>
                                    @endif
                                </figure>
                            @endforeach
                        </div>
                    @endforeach
                @elseif (count($guideImages))
                    <div class="!grid {{ count($guideImages) === 2 ? '!grid-cols-2' : '!grid-cols-3' }} !gap-2">
                        @foreach ($guideImages as $image)
                            <figure class="!m-0 !overflow-hidden !rounded-xl !bg-white">
                                <img
                                    src="{{ asset($image['src']) }}"
                                    alt="{{ $image['alt'] ?? 'Upload guide example' }}"
                                    class="!aspect-square !h-auto !w-full !object-cover"
                                >
                                @if (!empty($image['label']))
                                    <figcaption class="!px-2 !py-1.5 !text-center !text-[11px] !font-semibold !leading-4 {{ ($image['tone'] ?? '') === 'danger' ? '!text-rose-700' : (($image['tone'] ?? '') === 'success' ? '!text-emerald-700' : '!text-slate-600') }}">
                                        {{ $image['label'] }}
                                    </figcaption>
                                @endif
                            </figure>
                        @endforeach
                    </div>
                @endif

                @if (count($supportPanels))
                    <div
                        @if ($supportPanelsGender)
                            x-show.important="($store.enrollmentGuide?.gender || @js(array_key_first($guideImageGroups))) === @js($supportPanelsGender)"
                            x-cloak
                        @endif
                        class="upload-support-panels"
                    >
                        @foreach ($supportPanels as $support)
                            <button
                                type="button"
                                class="upload-support-panel"
                                @click="openSupportModal(@js(asset($support['src'])), @js($support['label'] ?? 'Photo guide'), @js($support['alt'] ?? ($support['label'] ?? 'Photo guideline support')))"
                            >
                                <img
                                    src="{{ asset($support['src']) }}"
                                    alt="{{ $support['alt'] ?? ($support['label'] ?? 'Photo guideline support') }}"
                                >
                                @if (!empty($support['label']))
                                    <span>{{ $support['label'] }}</span>
                                @endif
                                <small>Click to view support</small>
                            </button>
                        @endforeach
                    </div>
                @endif

                @if (count($supportPanelGroups))
                    @foreach ($supportPanelGroups as $groupName => $panels)
                        <div
                            x-show.important="($store.enrollmentGuide?.gender || @js(array_key_first($supportPanelGroups))) === @js($groupName)"
                            x-cloak
                            class="upload-support-panels {{ count($panels) === 1 ? 'upload-support-panels--single' : '' }}"
                        >
                            @foreach ($panels as $support)
                                <button
                                    type="button"
                                    class="upload-support-panel"
                                    @click="openSupportModal(@js(asset($support['src'])), @js($support['label'] ?? 'Photo guide'), @js($support['alt'] ?? ($support['label'] ?? 'Photo guideline support')))"
                                >
                                    <img
                                        src="{{ asset($support['src']) }}"
                                        alt="{{ $support['alt'] ?? ($support['label'] ?? 'Photo guideline support') }}"
                                    >
                                    @if (!empty($support['label']))
                                        <span>{{ $support['label'] }}</span>
                                    @endif
                                    <small>Click to view support</small>
                                </button>
                            @endforeach
                        </div>
                    @endforeach
                @endif

                @if ($showPhotoSample)
                    <p class="!m-0 !text-sm !leading-6 !text-slate-600">Use a clear front-facing photo with a plain white background.</p>
                @endif

                @if (count($supportLinks))
                    <div
                        @if ($supportLinksGender)
                            x-show.important="($store.enrollmentGuide?.gender || @js(array_key_first($guideImageGroups))) === @js($supportLinksGender)"
                            x-cloak
                        @endif
                        class="upload-support-links"
                    >
                        @foreach ($supportLinks as $support)
                            <a href="{{ asset($support['src']) }}" target="_blank" rel="noopener" class="upload-support-link">
                                <span class="upload-support-link__icon">
                                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/>
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M14 2v6h6"/>
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M10 14h4"/>
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M10 18h4"/>
                                    </svg>
                                </span>
                                <span class="upload-support-link__body">
                                    <span class="upload-support-link__title">{{ $support['label'] ?? 'View support' }}</span>
                                    <span class="upload-support-link__text">{{ $support['hint'] ?? 'Click to view support' }}</span>
                                </span>
                            </a>
                        @endforeach
                    </div>
                @endif

                @if ($guideNotice !== '')
                    <div
                        @if ($guideNoticeGender)
                            x-show.important="($store.enrollmentGuide?.gender || @js(array_key_first($guideImageGroups))) === @js($guideNoticeGender)"
                            x-cloak
                        @endif
                        class="upload-guide-notice"
                    >
                        {{ $guideNotice }}
                    </div>
                @endif

                <ul class="!m-0 !list-disc !space-y-1.5 !pl-5 !text-sm !leading-6 !text-slate-600">
                    @foreach ($guide as $item)
                        <li>{{ $item }}</li>
                    @endforeach
                </ul>
            </div>
        </div>
    </div>

    <div
        x-show="supportModal"
        x-cloak
        class="upload-support-modal"
        role="dialog"
        aria-modal="true"
        :aria-label="supportModal?.label || 'Photo guide support'"
    >
        <button type="button" class="upload-support-modal__backdrop" @click="closeSupportModal()" aria-label="Close support guide"></button>
        <div class="upload-support-modal__panel">
            <div class="upload-support-modal__header">
                <h3 x-text="supportModal?.label || 'Photo guide support'"></h3>
                <button type="button" class="upload-support-modal__close" @click="closeSupportModal()" aria-label="Close support guide">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M18 6 6 18M6 6l12 12"/>
                    </svg>
                </button>
            </div>
            <div class="upload-support-modal__body">
                 <img :src="supportModal?.src" :alt="supportModal?.alt || supportModal?.label || 'Photo guide support'">
            </div>
        </div>
    </div>

    <!-- Client-side Validation Error -->
    <div
        x-show="errorMsg"
        x-cloak
        style="margin-top: 0.75rem; display: flex; align-items: start; gap: 0.5rem; border-radius: 0.75rem; border: 1px solid #fecaca; background: #fef2f2; padding: 0.875rem; font-size: 0.875rem; line-height: 1.25rem; color: #b91c1c; box-sizing: border-box;"
    >
        <svg style="margin-top: 0.125rem; flex-shrink: 0; display: block;" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5">
            <circle cx="12" cy="12" r="10"></circle>
            <line x1="12" y1="8" x2="12" y2="12"></line>
            <line x1="12" y1="16" x2="12.01" y2="16"></line>
        </svg>
        <div style="flex: 1; text-align: left;">
            <strong style="font-weight: 700;">Upload Error:</strong>
            <span x-text="errorMsg"></span>
        </div>
        <button type="button" @click="errorMsg = ''" style="background: none; border: none; color: #fca5a5; cursor: pointer; padding: 0; outline: none; display: flex; align-items: center; justify-content: center;">
            <svg style="display: block;" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5">
                <line x1="18" y1="6" x2="6" y2="18"></line>
                <line x1="6" y1="6" x2="18" y2="18"></line>
            </svg>
        </button>
    </div>

    @error($name)
        <p class="!mt-3 !text-sm !font-medium !leading-5 !text-rose-600">{{ $message }}</p>
    @enderror
</div>
