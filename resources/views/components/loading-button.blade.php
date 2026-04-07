@props(['type' => 'primary'])

@php
$classes = match($type) {
    'primary' => 'bg-blue-600 hover:bg-blue-700 text-white',
    'secondary' => 'bg-gray-200 hover:bg-gray-300 text-gray-800',
    'danger' => 'bg-red-600 hover:bg-red-700 text-white',
    default => 'bg-blue-600 hover:bg-blue-700 text-white',
};
@endphp

<button {{ $attributes->merge(['class' => "inline-flex items-center px-6 py-3 rounded-lg font-semibold text-sm transition-colors $classes"]) }}>
    {{ $slot }}
</button>
