<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=2.0, user-scalable=yes">
        <meta name="csrf-token" content="{{ csrf_token() }}">

        <title>{{ config('app.name', 'UniKL STRG') }}</title>

        <!-- Fonts -->
        <link rel="preconnect" href="https://fonts.bunny.net">
        <link href="https://fonts.bunny.net/css?family=figtree:400,500,600,700&display=swap" rel="stylesheet" />

        <!-- Scripts -->
        @vite(['resources/css/app.css', 'resources/js/app.js'])
        
        <!-- iPad Optimization -->
        <style>
            /* iPad-specific optimizations */
            @media (min-width: 768px) and (max-width: 1024px) {
                body { font-size: 16px; }
                .touch-target { min-height: 44px; min-width: 44px; }
            }
            
            /* Touch-friendly interactions */
            .touch-target {
                min-height: 44px;
                min-width: 44px;
                padding: 8px;
            }
            
            /* Smooth scrolling for iPad */
            @media (min-width: 768px) {
                html { scroll-behavior: smooth; }
            }
        </style>
    </head>
    <body class="font-sans antialiased overflow-x-hidden bg-gray-50">
        <div class="min-h-screen">
            @include('layouts.navigation')

            <!-- Page Heading -->
            @isset($header)
                <header class="bg-white shadow-sm border-b border-gray-200">
                    <div class="max-w-7xl mx-auto py-4 sm:py-6 px-4 sm:px-6 lg:px-8">
                        {{ $header }}
                    </div>
                </header>
            @endisset

            <!-- Page Content -->
            <main class="pb-8">
                {{ $slot }}
            </main>
        </div>

        @stack('scripts')
    </body>
</html>
