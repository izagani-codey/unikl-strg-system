@props(['fields', 'prefix' => 'dynamic_fields', 'values' => []])

@php
$getInputName = function($fieldName) use ($prefix) {
    return "{$prefix}[{$fieldName}]";
};

$getValue = function($fieldName, $default = null) use ($prefix, $values) {
    return old("{$prefix}.{$fieldName}", $values[$fieldName] ?? $default);
};

$errorBag = session('errors');
if (!$errorBag instanceof \Illuminate\Support\ViewErrorBag) {
    $errorBag = new \Illuminate\Support\ViewErrorBag();
}

$hasError = function($fieldName) use ($prefix, $errorBag) {
    return $errorBag->has("{$prefix}.{$fieldName}");
};

$getError = function($fieldName) use ($prefix, $errorBag) {
    return $errorBag->first("{$prefix}.{$fieldName}");
};
@endphp

<div class="dynamic-form-fields space-y-4">
    @foreach($fields as $field)
        @php
            $inputName = $getInputName($field['name']);
            $value = $getValue($field['name']);
            $error = $hasError($field['name']) ? $getError($field['name']) : null;
            $required = $field['required'] ?? false;
        @endphp

        <div class="form-field {{ $error ? 'has-error' : '' }}">
            <label for="{{ $field['name'] }}" class="block text-sm font-bold text-gray-700 mb-1">
                {{ $field['label'] }}
                @if($required)
                    <span class="text-red-500">*</span>
                @endif
            </label>

            @switch($field['type'])
                @case('text')
                    <input
                        type="text"
                        id="{{ $field['name'] }}"
                        name="{{ $inputName }}"
                        value="{{ $value }}"
                        placeholder="{{ $field['placeholder'] ?? '' }}"
                        @if($required) required @endif
                        class="w-full rounded border-gray-300 {{ $error ? 'border-red-500' : '' }}"
                    >
                    @break

                @case('textarea')
                    <textarea
                        id="{{ $field['name'] }}"
                        name="{{ $inputName }}"
                        rows="{{ $field['rows'] ?? 3 }}"
                        placeholder="{{ $field['placeholder'] ?? '' }}"
                        @if($required) required @endif
                        class="w-full rounded border-gray-300 {{ $error ? 'border-red-500' : '' }}"
                    >{{ $value }}</textarea>
                    @break

                @case('number')
                    <input
                        type="number"
                        id="{{ $field['name'] }}"
                        name="{{ $inputName }}"
                        value="{{ $value }}"
                        @if(isset($field['min'])) min="{{ $field['min'] }}" @endif
                        @if(isset($field['max'])) max="{{ $field['max'] }}" @endif
                        @if(isset($field['step'])) step="{{ $field['step'] }}" @endif
                        placeholder="{{ $field['placeholder'] ?? '' }}"
                        @if($required) required @endif
                        class="w-full rounded border-gray-300 {{ $error ? 'border-red-500' : '' }}"
                    >
                    @break

                @case('select')
                    <select
                        id="{{ $field['name'] }}"
                        name="{{ $inputName }}"
                        @if($required) required @endif
                        class="w-full rounded border-gray-300 {{ $error ? 'border-red-500' : '' }}"
                    >
                        <option value="">-- Select {{ $field['label'] }} --</option>
                        @foreach($field['options'] ?? [] as $option)
                            <option value="{{ $option }}" @selected($value === $option)>{{ $option }}</option>
                        @endforeach
                    </select>
                    @break

                @case('date')
                    <input
                        type="date"
                        id="{{ $field['name'] }}"
                        name="{{ $inputName }}"
                        value="{{ $value }}"
                        @if($required) required @endif
                        class="w-full rounded border-gray-300 {{ $error ? 'border-red-500' : '' }}"
                    >
                    @break

                @case('date_range')
                    <div class="grid grid-cols-2 gap-2">
                        <div>
                            <label class="block text-xs text-gray-500 mb-1">Start Date</label>
                            <input
                                type="date"
                                id="{{ $field['name'] }}_start"
                                name="{{ $getInputName($field['fields'][0] ?? 'start_date') }}"
                                value="{{ $getValue($field['fields'][0] ?? 'start_date') }}"
                                @if($required) required @endif
                                class="w-full rounded border-gray-300 {{ $error ? 'border-red-500' : '' }}"
                            >
                        </div>
                        <div>
                            <label class="block text-xs text-gray-500 mb-1">End Date</label>
                            <input
                                type="date"
                                id="{{ $field['name'] }}_end"
                                name="{{ $getInputName($field['fields'][1] ?? 'end_date') }}"
                                value="{{ $getValue($field['fields'][1] ?? 'end_date') }}"
                                @if($required) required @endif
                                class="w-full rounded border-gray-300 {{ $error ? 'border-red-500' : '' }}"
                            >
                        </div>
                    </div>
                    @break

                @case('checkbox')
                    <div class="flex items-center mt-2">
                        <input
                            type="checkbox"
                            id="{{ $field['name'] }}"
                            name="{{ $inputName }}"
                            value="1"
                            @checked($value)
                            class="rounded border-gray-300"
                        >
                        <label for="{{ $field['name'] }}" class="ml-2 text-sm text-gray-700">
                            Yes
                        </label>
                    </div>
                    @break

                @default
                    <input
                        type="text"
                        id="{{ $field['name'] }}"
                        name="{{ $inputName }}"
                        value="{{ $value }}"
                        placeholder="{{ $field['placeholder'] ?? '' }}"
                        @if($required) required @endif
                        class="w-full rounded border-gray-300 {{ $error ? 'border-red-500' : '' }}"
                    >
            @endswitch

            @if($field['help'] ?? false)
                <p class="mt-1 text-xs text-gray-500">{{ $field['help'] }}</p>
            @endif

            @if($error)
                <p class="mt-1 text-xs text-red-600">{{ $error }}</p>
            @endif
        </div>
    @endforeach
</div>
