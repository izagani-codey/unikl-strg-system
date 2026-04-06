@props([
    'type' => 'submit',
    'loading' => false,
    'loadingText' => 'Processing...',
    'disabled' => false,
    'class' => '',
    'spinnerClass' => 'w-4 h-4',
])

@php
    $baseClasses = 'inline-flex items-center justify-center px-4 py-2 border border-transparent text-sm font-medium rounded-md shadow-sm focus:outline-none focus:ring-2 focus:ring-offset-2 transition-all duration-200 disabled:opacity-50 disabled:cursor-not-allowed';
    $typeClasses = match($type) {
        'primary' => 'bg-indigo-600 text-white hover:bg-indigo-700 focus:ring-indigo-500',
        'success' => 'bg-green-600 text-white hover:bg-green-700 focus:ring-green-500',
        'danger' => 'bg-red-600 text-white hover:bg-red-700 focus:ring-red-500',
        'secondary' => 'bg-gray-600 text-white hover:bg-gray-700 focus:ring-gray-500',
        default => 'bg-indigo-600 text-white hover:bg-indigo-700 focus:ring-indigo-500'
    };
    $finalClasses = trim($baseClasses . ' ' . $typeClasses . ' ' . $class);
@endphp

<button {{ $attributes->merge(['class' => $finalClasses, 'disabled' => $disabled || $loading]) }}>
    @if($loading)
        <svg class="animate-spin -ml-1 mr-2 {{ $spinnerClass }}" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
        </svg>
        {{ $loadingText }}
    @else
        {{ $slot }}
    @endif
</button>
