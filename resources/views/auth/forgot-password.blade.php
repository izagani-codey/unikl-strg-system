<x-guest-layout>
    <div class="mb-6">
        <div class="text-center mb-6">
            <h2 class="text-3xl font-bold bg-gradient-to-r from-blue-600 to-indigo-600 bg-clip-text text-transparent mb-2">
                Forgot Your Password?
            </h2>
            <p class="text-gray-600">
                No problem. Just let us know your email address and we'll email you a password reset link.
            </p>
        </div>

        <!-- Session Status -->
        <x-auth-session-status class="mb-4" :status="session('status')" />

        <form method="POST" action="{{ route('password.email') }}" class="space-y-6">
            @csrf

            <!-- Email Address -->
            <div>
                <x-input-label for="email" :value="__('Email Address')" />
                <x-text-input id="email" class="block mt-1 w-full" type="email" name="email" :value="old('email')" required autofocus placeholder="Enter your email address" />
                <x-input-error :messages="$errors->get('email')" class="mt-2" />
                <p class="mt-2 text-sm text-gray-500">
                    Make sure to use your UniKL email address (@unikl.edu.my)
                </p>
            </div>

            <div class="flex items-center justify-between">
                <a href="{{ route('login') }}" class="text-sm text-blue-600 hover:text-blue-700 font-medium">
                    ← Back to login
                </a>
                <x-primary-button class="bg-gradient-to-r from-blue-600 to-indigo-600 hover:from-blue-700 hover:to-indigo-700">
                    <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/>
                    </svg>
                    Email Password Reset Link
                </x-primary-button>
            </div>
        </form>
    </div>
</x-guest-layout>
