<x-guest-layout>
    <div class="bg-white border border-slate-200 shadow-lg rounded-2xl px-6 py-7 sm:px-8">
        <div class="mb-6">
            <h1 class="text-2xl font-bold text-slate-900">Create your account</h1>
            <p class="mt-1 text-sm text-slate-500">Set up your profile to submit and track STRG grant requests.</p>
        </div>

        <form method="POST" action="{{ route('register') }}" class="space-y-4">
            @csrf

            <div>
                <x-input-label for="name" :value="__('Full name')" />
                <x-text-input id="name" class="block mt-1 w-full" type="text" name="name" :value="old('name')" required autofocus autocomplete="name" />
                <x-input-error :messages="$errors->get('name')" class="mt-2" />
            </div>

            <div>
                <x-input-label for="email" :value="__('Email address')" />
                <x-text-input id="email" class="block mt-1 w-full" type="email" name="email" :value="old('email')" required autocomplete="username" />
                <x-input-error :messages="$errors->get('email')" class="mt-2" />
            </div>

            <div>
                <x-input-label for="password" :value="__('Password')" />
                <x-text-input id="password" class="block mt-1 w-full"
                                type="password"
                                name="password"
                                required autocomplete="new-password" />
                <x-input-error :messages="$errors->get('password')" class="mt-2" />
            </div>

            <div>
                <x-input-label for="password_confirmation" :value="__('Confirm password')" />
                <x-text-input id="password_confirmation" class="block mt-1 w-full"
                                type="password"
                                name="password_confirmation" required autocomplete="new-password" />
                <x-input-error :messages="$errors->get('password_confirmation')" class="mt-2" />
            </div>

            <x-primary-button class="w-full justify-center">
                {{ __('Register') }}
            </x-primary-button>
        </form>

        <p class="mt-5 text-center text-sm text-slate-600">
            Already registered?
            <a class="font-semibold text-blue-600 hover:text-blue-700 hover:underline" href="{{ route('login') }}">Log in</a>
        </p>
    </div>
</x-guest-layout>
