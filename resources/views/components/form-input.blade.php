@props([
    'label',
    'name',
    'type' => 'text',
    'required' => false,
    'placeholder' => '',
    'value' => '',
    'hint' => '',
    'col' => 1,
])

<div class="form-group {{ $col > 1 ? 'col-' . $col : '' }}">
    <label for="{{ $name }}">
        {{ $label }}
        @if($required) <span class="required">*</span> @endif
    </label>
    <input
        type="{{ $type }}"
        id="{{ $name }}"
        name="{{ $name }}"
        class="plain-input"
        placeholder="{{ $placeholder ?: $label }}"
        value="{{ old($name, $value) }}"
        autocomplete="off"
        {{ $required ? 'required' : '' }}
        {{ $attributes }}
    >
    @if($hint)
        <span class="text-xs text-gray-500 mt-0.5">{{ $hint }}</span>
    @endif
    @error($name)
        <span class="text-xs text-red-600 mt-0.5">{{ $message }}</span>
    @enderror
</div>
