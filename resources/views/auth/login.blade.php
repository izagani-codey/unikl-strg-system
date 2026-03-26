<x-guest-layout>
    <div class="bg-white border border-slate-200 shadow-lg rounded-2xl px-6 py-7 sm:px-8">
        <div class="mb-6">
            <h1 class="text-2xl font-bold text-slate-900">Welcome back</h1>
            <p class="mt-1 text-sm text-slate-500">Sign in to continue managing STRG requests.</p>
        </div>

        <x-auth-session-status class="mb-4" :status="session('status')" />

        <form method="POST" action="{{ route('login') }}" class="space-y-4">
            @csrf

            <div>
                <x-input-label for="email" :value="__('Email address')" />
                <x-text-input id="email" class="block mt-1 w-full" type="email" name="email" :value="old('email')" required autofocus autocomplete="username" />
                <x-input-error :messages="$errors->get('email')" class="mt-2" />
            </div>

            <div>
                <x-input-label for="password" :value="__('Password')" />
                <x-text-input id="password" class="block mt-1 w-full"
                                type="password"
                                name="password"
                                required autocomplete="current-password" />
                <x-input-error :messages="$errors->get('password')" class="mt-2" />
            </div>

            <div class="flex items-center justify-between gap-4">
                <label for="remember_me" class="inline-flex items-center text-sm text-slate-600">
                    <input id="remember_me" type="checkbox" class="rounded border-slate-300 text-blue-600 shadow-sm focus:ring-blue-500" name="remember">
                    <span class="ms-2">{{ __('Remember me') }}</span>
                </label>

                @if (Route::has('password.request'))
                    <a class="text-sm text-blue-600 hover:text-blue-700 hover:underline" href="{{ route('password.request') }}">
                        {{ __('Forgot password?') }}
                    </a>
                @endif
            </div>

            <x-primary-button class="w-full justify-center">
                {{ __('Log in') }}
            </x-primary-button>
        </form>

        <p class="mt-5 text-center text-sm text-slate-600">
            Don’t have an account?
            <a class="font-semibold text-blue-600 hover:text-blue-700 hover:underline" href="{{ route('register') }}">Create one</a>
        </p>
    </div>
</x-guest-layout>
