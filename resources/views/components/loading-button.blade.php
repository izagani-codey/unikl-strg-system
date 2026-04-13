@props(['type' => 'primary', 'loading' => false])

@php
$baseClasses = 'inline-flex items-center px-6 py-3 rounded-lg font-semibold text-sm transition-all duration-200 disabled:opacity-50 disabled:cursor-not-allowed focus:outline-none focus:ring-2 focus:ring-offset-2';

$classes = match($type) {
    'primary' => $baseClasses . ' bg-blue-600 hover:bg-blue-700 text-white focus:ring-blue-500',
    'secondary' => $baseClasses . ' bg-gray-200 hover:bg-gray-300 text-gray-800 focus:ring-gray-500',
    'danger' => $baseClasses . ' bg-red-600 hover:bg-red-700 text-white focus:ring-red-500',
    'success' => $baseClasses . ' bg-green-600 hover:bg-green-700 text-white focus:ring-green-500',
    'warning' => $baseClasses . ' bg-yellow-600 hover:bg-yellow-700 text-white focus:ring-yellow-500',
    default => $baseClasses . ' bg-blue-600 hover:bg-blue-700 text-white focus:ring-blue-500',
};
@endphp

<button {{ $attributes->merge(['class' => $classes, 'disabled' => $loading]) }}>
    @if($loading)
        <svg class="animate-spin -ml-1 mr-3 h-4 w-4 text-current" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
        </svg>
        Loading...
    @else
        {{ $slot }}
    @endif
</button>
