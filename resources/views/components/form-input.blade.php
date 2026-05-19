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
    <x-form-field-label :for="$name" :required="$required">{{ $label }}</x-form-field-label>
    <input
        type="{{ $type }}"
        id="{{ $name }}"
        name="{{ $name }}"
        class="plain-input"
        placeholder="{{ $placeholder ?: $label }}"
        value="{{ old($name, $value) }}"
        autocomplete="off"
        {{ $attributes }}
    >
    @if($hint)
        <span class="field-hint">{{ $hint }}</span>
    @endif
    @error($name)
        <span class="field-error">{{ $message }}</span>
    @enderror
</div>
