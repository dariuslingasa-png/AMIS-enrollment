@props([
    'label',
    'name',
    'required' => false,
    'value' => '',
    'hint' => 'Use the student birthdate from official records.',
    'col' => 1,
])

@php
    $model = $attributes->get('x-model');
    $initialValue = old($name, $value);
    $currentYear = (int) date('Y');
@endphp

<div
    class="form-group {{ $col > 1 ? 'col-' . $col : '' }}"
    x-data="{
        month: '',
        day: '',
        year: '',
        syncDate() {
            if (this.month && this.day && this.year) {
                const value = `${this.year}-${String(this.month).padStart(2, '0')}-${String(this.day).padStart(2, '0')}`;
                @if($model)
                    {{ $model }} = value;
                @endif
                this.$refs.dateInput.value = value;
                return;
            }

            @if($model)
                {{ $model }} = '';
            @endif
            this.$refs.dateInput.value = '';
        },
        loadDate() {
            const value = @if($model) {{ $model }} @else @js($initialValue) @endif;
            if (!value) return;

            const parts = value.split('-');
            if (parts.length !== 3) return;

            this.year = parts[0];
            this.month = String(Number(parts[1]));
            this.day = String(Number(parts[2]));
            this.$refs.dateInput.value = value;
        }
    }"
    x-init="loadDate()"
>
    <x-form-field-label :for="$name" :required="$required">{{ $label }}</x-form-field-label>

    <div class="date-select-grid">
        <select class="select-input date-select" x-model="month" @change="syncDate()" aria-label="Birth month">
            <option value="">Month</option>
            @foreach([
                1 => 'Jan', 2 => 'Feb', 3 => 'Mar', 4 => 'Apr',
                5 => 'May', 6 => 'Jun', 7 => 'Jul', 8 => 'Aug',
                9 => 'Sep', 10 => 'Oct', 11 => 'Nov', 12 => 'Dec',
            ] as $monthValue => $monthLabel)
                <option value="{{ $monthValue }}">{{ $monthLabel }}</option>
            @endforeach
        </select>

        <select class="select-input date-select" x-model="day" @change="syncDate()" aria-label="Birth day">
            <option value="">Day</option>
            @for($day = 1; $day <= 31; $day++)
                <option value="{{ $day }}">{{ $day }}</option>
            @endfor
        </select>

        <select class="select-input date-select" x-model="year" @change="syncDate()" aria-label="Birth year">
            <option value="">Year</option>
            @for($year = $currentYear; $year >= $currentYear - 80; $year--)
                <option value="{{ $year }}">{{ $year }}</option>
            @endfor
        </select>
    </div>

    <input type="hidden" id="{{ $name }}" name="{{ $name }}" x-ref="dateInput" value="{{ $initialValue }}">

    @if ($hint)
        <span class="field-hint">{{ $hint }}</span>
    @endif
    @error($name)
        <span class="field-error">{{ $message }}</span>
    @enderror
</div>
