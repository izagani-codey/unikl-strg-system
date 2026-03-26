<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">

        <title>{{ config('app.name', 'UniKL STRG') }}</title>

        <link rel="preconnect" href="https://fonts.bunny.net">
        <link href="https://fonts.bunny.net/css?family=figtree:400,500,600,700&display=swap" rel="stylesheet" />

        @vite(['resources/css/app.css', 'resources/js/app.js'])
    </head>
    <body class="font-sans text-gray-900 antialiased bg-gradient-to-b from-slate-50 to-blue-50 min-h-screen">
        <div class="min-h-screen flex flex-col">
            <header class="w-full border-b border-slate-200/80 bg-white/80 backdrop-blur">
                <div class="max-w-6xl mx-auto px-4 sm:px-6 lg:px-8 py-4 flex items-center justify-between">
                    <a href="{{ url('/') }}" class="flex items-center gap-3">
                        <x-application-logo class="w-9 h-9 fill-current text-blue-700" />
                        <div>
                            <p class="text-sm font-bold text-slate-900 leading-tight">UniKL STRG Portal</p>
                            <p class="text-xs text-slate-500">Grant Management System</p>
                        </div>
                    </a>

                    <nav class="flex items-center gap-2 text-sm font-medium">
                        <a href="{{ url('/') }}" class="px-3 py-2 rounded-md text-slate-600 hover:text-slate-900 hover:bg-slate-100">Home</a>
                        @guest
                            <a href="{{ route('login') }}" class="px-3 py-2 rounded-md text-slate-600 hover:text-slate-900 hover:bg-slate-100">Log in</a>
                            <a href="{{ route('register') }}" class="px-3 py-2 rounded-md bg-blue-600 text-white hover:bg-blue-700">Register</a>
                        @endguest
                    </nav>
                </div>
            </header>

            <main class="flex-1 flex items-center justify-center px-4 py-10 sm:px-6 lg:px-8">
                <div class="w-full max-w-md">
                    {{ $slot }}
                </div>
            </main>
        </div>
    </body>
</html>
