@props([
    'label',
    'name',
    'options' => [],
    'required' => false,
    'value' => '',
    'col' => 1,
    'placeholder' => 'Select',
])

<div class="form-group {{ $col > 1 ? 'col-' . $col : '' }}">
    <label for="{{ $name }}">
        {{ $label }}
        @if($required) <span class="required">*</span> @endif
    </label>
    <select
        id="{{ $name }}"
        name="{{ $name }}"
        class="select-input @error($name) border-red-500 @enderror"
        {{ $required ? 'required' : '' }}
        {{ $attributes }}
    >
        <option value="">{{ $placeholder }}</option>
        @foreach($options as $optionValue => $optionLabel)
            @if(is_numeric($optionValue))
                <option value="{{ $optionLabel }}" {{ old($name, $value) == $optionLabel ? 'selected' : '' }}>
                    {{ $optionLabel }}
                </option>
            @else
                <option value="{{ $optionValue }}" {{ old($name, $value) == $optionValue ? 'selected' : '' }}>
                    {{ $optionLabel }}
                </option>
            @endif
        @endforeach
    </select>
    @error($name)
        <span class="text-xs text-red-600 mt-0.5">{{ $message }}</span>
    @enderror
</div>
