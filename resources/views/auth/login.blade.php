<x-guest-layout variant="login">
    <x-auth-session-status class="mb-4" :status="session('status')" />

    @if(request()->has('approved'))
        <div class="mb-5 flex gap-3 rounded-2xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-800">
            <svg class="mt-0.5 h-5 w-5 shrink-0 text-emerald-600" fill="none" viewBox="0 0 24 24" stroke-width="1.75" stroke="currentColor" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75 11.25 15 15 9.75M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z" /></svg>
            <span>{{ __('guest.login.approved_notice') }}</span>
        </div>
    @endif

    <div class="mb-8">
        <span class="mb-5 inline-flex h-11 w-11 items-center justify-center rounded-xl bg-emerald-50 text-baytgo ring-1 ring-emerald-100">
            <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke-width="1.75" stroke="currentColor" aria-hidden="true">
                <path stroke-linecap="round" stroke-linejoin="round" d="M15.75 6a3.75 3.75 0 1 1-7.5 0 3.75 3.75 0 0 1 7.5 0ZM4.501 20.118a7.5 7.5 0 0 1 14.998 0A17.933 17.933 0 0 1 12 21.75c-2.676 0-5.216-.584-7.499-1.632Z" />
            </svg>
        </span>
        <h1 class="text-[1.75rem] font-bold tracking-tight text-slate-900 sm:text-3xl">{{ __('guest.login.title') }}</h1>
        <p class="mt-2 text-sm leading-relaxed text-slate-500">{{ __('guest.login.subtitle') }}</p>
    </div>

    <form method="POST" action="{{ route('login') }}" class="space-y-5">
        @csrf

        <div>
            <x-input-label for="email" :value="__('Email')" />
            <div class="relative mt-2">
                <span class="pointer-events-none absolute inset-y-0 start-0 flex items-center ps-3.5 text-slate-400" aria-hidden="true">
                    <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M21.75 6.75v10.5a2.25 2.25 0 0 1-2.25 2.25h-7.5a2.25 2.25 0 0 1-2.25-2.25V6.75m9 0A2.25 2.25 0 0 0 19.5 4.5h-15a2.25 2.25 0 0 0-2.25 2.25v10.5A2.25 2.25 0 0 0 4.5 19.5h15a2.25 2.25 0 0 0 2.25-2.25V6.75Z" /></svg>
                </span>
                <x-text-input
                    id="email"
                    class="block w-full rounded-xl border-slate-200 bg-slate-50/50 py-3 ps-11 shadow-sm focus:border-baytgo focus:bg-white focus:ring-baytgo/20"
                    type="email"
                    name="email"
                    :value="old('email')"
                    :placeholder="__('guest.login.email_placeholder')"
                    required
                    autofocus
                    autocomplete="username"
                />
            </div>
            <x-input-error :messages="$errors->get('email')" class="mt-2" />
        </div>

        <div>
            <x-input-label for="password" :value="__('Password')" />
            <div class="relative mt-2">
                <span class="pointer-events-none absolute inset-y-0 start-0 z-10 flex items-center ps-3.5 text-slate-400" aria-hidden="true">
                    <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M16.5 10.5V6.75a4.5 4.5 0 0 0-9 0v3.75m-.75 11.25h10.5a2.25 2.25 0 0 0 2.25-2.25v-6.75a2.25 2.25 0 0 0-2.25-2.25H6.75a2.25 2.25 0 0 0-2.25 2.25v6.75a2.25 2.25 0 0 0 2.25 2.25Z" /></svg>
                </span>
                <x-password-input
                    id="password"
                    class="block w-full rounded-xl border-slate-200 bg-slate-50/50 py-3 ps-11 pe-11 shadow-sm focus:border-baytgo focus:bg-white focus:ring-baytgo/20"
                    name="password"
                    :placeholder="__('guest.login.password_placeholder')"
                    required
                    autocomplete="current-password"
                />
            </div>
            <x-input-error :messages="$errors->get('password')" class="mt-2" />
        </div>

        <div class="flex items-center justify-between gap-3">
            <label for="remember_me" class="inline-flex items-center">
                <input id="remember_me" type="checkbox" class="rounded border-slate-300 text-baytgo shadow-sm focus:ring-baytgo/30" name="remember">
                <span class="ms-2 text-sm text-slate-600">{{ __('guest.login.remember') }}</span>
            </label>
            @if (Route::has('password.request'))
                <a class="text-sm font-semibold text-baytgo hover:text-baytgo-800" href="{{ route('password.request') }}">
                    {{ __('guest.login.forgot') }}
                </a>
            @endif
        </div>

        <x-primary-button class="w-full justify-center rounded-xl bg-baytgo py-3.5 text-base font-semibold shadow-md shadow-baytgo/15 hover:bg-baytgo-800">
            {{ __('guest.login.submit') }}
        </x-primary-button>
    </form>

    <div class="relative my-8">
        <div class="absolute inset-0 flex items-center" aria-hidden="true">
            <div class="w-full border-t border-slate-200"></div>
        </div>
        <div class="relative flex justify-center text-sm">
            <span class="bg-white px-3 text-slate-400">{{ __('guest.or') }}</span>
        </div>
    </div>

    <a href="{{ route('register') }}" class="flex w-full items-center justify-center rounded-xl bg-slate-100 px-4 py-3.5 text-sm font-medium text-slate-600 transition hover:bg-slate-200/80">
        {{ __('guest.login.no_account') }}
        <span class="ms-1 font-bold text-baytgo">{{ __('guest.login.register_link') }}</span>
    </a>
</x-guest-layout>
