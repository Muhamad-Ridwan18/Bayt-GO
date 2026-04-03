<x-guest-layout>
    <x-auth-session-status class="mb-4" :status="session('status')" />

    <div class="mb-6">
        <h1 class="text-xl font-semibold text-slate-900">Masuk</h1>
        <p class="mt-1 text-sm text-slate-500">Lanjutkan ke akun BaytGo Anda.</p>
    </div>

    <form method="POST" action="{{ route('login') }}" class="space-y-5">
        @csrf

        <div>
            <x-input-label for="email" :value="__('Email')" />
            <x-text-input id="email" class="block mt-1 w-full border-slate-300" type="email" name="email" :value="old('email')" required autofocus autocomplete="username" />
            <x-input-error :messages="$errors->get('email')" class="mt-2" />
        </div>

        <div>
            <x-input-label for="password" :value="__('Password')" />
            <x-password-input id="password" class="block mt-1 w-full border-slate-300" name="password" required autocomplete="current-password" />
            <x-input-error :messages="$errors->get('password')" class="mt-2" />
        </div>

        <div class="flex items-center justify-between gap-3">
            <label for="remember_me" class="inline-flex items-center">
                <input id="remember_me" type="checkbox" class="rounded border-slate-300 text-brand-600 shadow-sm focus:ring-brand-500" name="remember">
                <span class="ms-2 text-sm text-slate-600">Ingat saya</span>
            </label>
            @if (Route::has('password.request'))
                <a class="text-sm font-medium text-brand-700 hover:text-brand-800" href="{{ route('password.request') }}">
                    Lupa password?
                </a>
            @endif
        </div>

        <div class="flex flex-col-reverse sm:flex-row sm:items-center sm:justify-between gap-3 pt-2">
            <a class="text-sm text-slate-600 hover:text-brand-700 font-medium text-center sm:text-left" href="{{ route('register') }}">
                Belum punya akun? Daftar
            </a>
            <x-primary-button class="w-full sm:w-auto justify-center">
                Masuk
            </x-primary-button>
        </div>
    </form>
</x-guest-layout>
