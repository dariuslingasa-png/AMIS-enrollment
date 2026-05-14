@props([
    'label',
    'name',
    'required' => false,
    'accept'   => 'image/jpeg,image/jpg,image/png',
    'hint'     => 'JPG, PNG up to 5MB',
    'uploaded' => null,  // existing file path from draft
])

<div class="form-group full-width">
    <label>
        {{ $label }}
        @if($required && !$uploaded) <span class="required">*</span> @endif
    </label>

    <div x-data="{ fileName: '', preview: '' }" class="relative">

        {{-- Already uploaded indicator --}}
        @if($uploaded)
            <div style="display:flex;align-items:center;gap:0.75rem;padding:0.625rem 0.875rem;background:#f0fdf4;border:1.5px solid #86efac;border-radius:10px;margin-bottom:0.5rem;">
                <img src="{{ asset('storage/' . $uploaded) }}" alt="{{ $label }}"
                     style="width:40px;height:40px;object-fit:cover;border-radius:6px;flex-shrink:0;border:1px solid #bbf7d0;">
                <div style="flex:1;min-width:0;">
                    <div style="font-size:0.8125rem;font-weight:600;color:#065f46;">✓ Already uploaded</div>
                    <div style="font-size:0.75rem;color:#059669;">Upload a new file below to replace it</div>
                </div>
            </div>
        @endif

        <!-- Upload Area -->
        <label x-show="!fileName" class="doc-upload-area" for="{{ $name }}">
            <div class="doc-upload-inner">
                <svg class="w-8 h-8 text-gray-400 mb-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M15 13l-3-3m0 0l-3 3m3-3v12"/>
                </svg>
                <span class="doc-upload-text">{{ $uploaded ? 'Click to replace' : 'Click to upload' }}</span>
                <span class="doc-upload-hint">{{ $hint }}</span>
            </div>
        </label>

        <!-- Preview after selecting new file -->
        <div x-show="fileName" class="doc-preview">
            <img x-show="preview" :src="preview" class="doc-preview-img" alt="Preview">
            <span x-text="fileName" class="text-sm text-gray-700 font-medium truncate flex-1"></span>
            <button type="button" @click="fileName = ''; preview = ''; $refs.input.value = ''" class="doc-remove-btn">
                <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                </svg>
                Remove
            </button>
        </div>

        <input
            x-ref="input"
            type="file"
            id="{{ $name }}"
            name="{{ $name }}"
            accept="{{ $accept }}"
            class="hidden"
            @change="
                const file = $event.target.files[0];
                if (file) {
                    fileName = file.name;
                    if (file.type.startsWith('image/')) {
                        const reader = new FileReader();
                        reader.onload = (e) => preview = e.target.result;
                        reader.readAsDataURL(file);
                    } else {
                        preview = '';
                    }
                }
            "
        >
    </div>

    @error($name)
        <span class="text-xs text-red-600 mt-0.5">{{ $message }}</span>
    @enderror
</div>
