@props([
    'title',
    'name',
    'description' => '',
    'required' => false,
    'accept' => 'image/jpeg,image/jpg,image/png',
    'hint' => 'JPG or PNG up to 5MB',
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
@endphp

<div
    x-data="{
        fileName: '',
        preview: '',
        hasUploaded: {{ $uploaded ? 'true' : 'false' }},
        showUpload: {{ $deferUpload ? 'false' : 'true' }},
        removingUploaded: false,
        supportModal: null,
        uploadedName: @js($uploadedBasename ?? 'Saved file'),
        get currentState() {
            if (this.fileName) return 'selected';
            if (this.hasUploaded) return 'uploaded';
            return 'empty';
        },
        async removeUploaded() {
            if (!this.hasUploaded || this.removingUploaded) return;
            this.removingUploaded = true;
            try {
                const applicantQuery = window.AMIS_CURRENT_APPLICANT_ID ? '?applicant=' + encodeURIComponent(window.AMIS_CURRENT_APPLICANT_ID) : '';
                const response = await fetch('{{ route('enrollment.draft.document.remove', ['document' => $name]) }}' + applicantQuery, {
                    method: 'DELETE',
                    headers: {
                        'X-CSRF-TOKEN': document.querySelector('meta[name=&quot;csrf-token&quot;]').content,
                        'Accept': 'application/json',
                    },
                });
                if (!response.ok) throw new Error('Unable to remove file');
                this.hasUploaded = false;
                window.dispatchEvent(new CustomEvent('enrollment:file-removed', {
                    detail: { name: '{{ $name }}' }
                }));
            } catch (_) {
                window.dispatchEvent(new CustomEvent('enrollment:file-remove-failed', {
                    detail: { name: '{{ $name }}' }
                }));
            } finally {
                this.removingUploaded = false;
            }
        },
        clearSelected() {
            this.fileName = '';
            this.preview = '';
            this.$refs.input.value = '';
            window.dispatchEvent(new CustomEvent('enrollment:file-removed', {
                detail: { name: '{{ $name }}' }
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
    }"
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
            <span class="!shrink-0 !rounded-full {{ $required ? '!bg-emerald-50 !text-emerald-700' : '!bg-slate-100 !text-slate-600' }} !px-2.5 !py-1 !text-xs !font-semibold">
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
            <template x-if="currentState === 'empty'">
                <button
                    type="button"
                    @click="chooseFile()"
                    class="!flex !h-full !w-full !items-center !justify-center !p-5 !text-center focus-visible:!outline-none focus-visible:!ring-4 focus-visible:!ring-emerald-100"
                >
                    <div class="!flex !flex-col !items-center !justify-center !gap-3">
                        <div class="!flex !h-12 !w-12 !items-center !justify-center !rounded-full !bg-white !text-slate-400">
                            <svg class="!h-6 !w-6" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M7 16a4 4 0 0 1-.88-7.903A5 5 0 1 1 15.9 6L16 6a5 5 0 0 1 1 9.9M15 13l-3-3m0 0l-3 3m3-3v12"/>
                            </svg>
                        </div>
                        <div class="!space-y-1">
                            <p class="!m-0 !text-base !font-semibold !leading-6 !text-emerald-700">Choose file</p>
                            <p class="!m-0 !text-sm !leading-5 !text-slate-500">{{ $hint }}</p>
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
                        <p class="!m-0 !text-sm !font-semibold">Image file</p>
                    </div>
                    <div class="!absolute !inset-x-0 !bottom-0 !bg-white/90 !px-3 !py-2 !backdrop-blur-sm">
                        <p class="!m-0 !truncate !text-sm !font-semibold !leading-5 !text-slate-900" x-text="fileName"></p>
                        <p class="!m-0 !text-xs !leading-4 !text-slate-500">Selected file</p>
                    </div>
                </div>
            </template>

            <template x-if="currentState === 'uploaded'">
                <div class="!relative !flex !h-full !w-full !items-center !justify-center !p-4">
                    @if ($uploaded && !$uploadedIsPdf)
                        <img src="{{ asset('storage/' . $uploaded) }}" alt="{{ $title }}" class="!absolute !inset-0 !h-full !w-full !object-contain !p-3">
                    @else
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
                    @endif
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
            @change="
                const file = $event.target.files[0];
                if (file) {
                    fileName = file.name;
                    hasUploaded = false;
                    if (file.type.startsWith('image/')) {
                        const reader = new FileReader();
                        reader.onload = (e) => preview = e.target.result;
                        reader.readAsDataURL(file);
                    } else {
                        preview = '';
                    }
                    window.dispatchEvent(new CustomEvent('enrollment:file-selected', {
                        detail: { name: '{{ $name }}' }
                    }));
                }
            "
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
                                        loading="lazy"
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
                                    loading="lazy"
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
                                    loading="lazy"
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
                                        loading="lazy"
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

    @error($name)
        <p class="!mt-3 !text-sm !font-medium !leading-5 !text-rose-600">{{ $message }}</p>
    @enderror
</div>
