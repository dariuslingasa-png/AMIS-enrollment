@props([
    'label',
    'name',
    'required' => false,
    'placeholder' => '',
    'value' => '',
    'rows' => 3,
    'col' => 1,
])

<div class="form-group {{ $col > 1 ? 'col-' . $col : '' }}">
    <label for="{{ $name }}">
        {{ $label }}
        @if($required) <span class="required">*</span> @endif
    </label>
    <textarea
        id="{{ $name }}"
        name="{{ $name }}"
        class="textarea-input @error($name) border-red-500 @enderror"
        placeholder="{{ $placeholder ?: $label }}"
        rows="{{ $rows }}"
        {{ $required ? 'required' : '' }}
        {{ $attributes }}
    >{{ old($name, $value) }}</textarea>
    @error($name)
        <span class="text-xs text-red-600 mt-0.5">{{ $message }}</span>
    @enderror
</div>
