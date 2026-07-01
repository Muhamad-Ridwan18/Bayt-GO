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
        <p class="mt-2 text-sm leading-relaxed text-slate-500">{{ __('guest.login_otp.subtitle') }}</p>
    </div>

    <div
        x-data="{
            email: @js(old('email', '')),
            otp: @js(old('otp', '')),
            otpSent: false,
            otpSendLoading: false,
            resendCooldown: 0,
            otpFeedback: '',
            otpWaitTpl: @js(__('guest.login_otp.wait')),
            cooldownTimer: null,
            async sendOtp() {
                if (!this.email.trim()) {
                    this.otpFeedback = @js(__('guest.login_otp.email_required'));
                    return;
                }
                this.otpSendLoading = true;
                this.otpFeedback = '';
                try {
                    const res = await fetch(@js(route('login.otp.send')), {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'Accept': 'application/json',
                            'X-CSRF-TOKEN': document.querySelector('meta[name=csrf-token]').content,
                        },
                        body: JSON.stringify({ email: this.email.trim() }),
                    });
                    const data = await res.json().catch(() => ({}));
                    if (!res.ok) {
                        this.otpFeedback = data.message || @js(__('guest.login_otp.send_failed'));
                        return;
                    }
                    this.otpSent = true;
                    this.otpFeedback = data.message || @js(__('guest.login_otp.sent'));
                    this.startCooldown(60);
                } catch (e) {
                    this.otpFeedback = @js(__('guest.login_otp.send_failed'));
                } finally {
                    this.otpSendLoading = false;
                }
            },
            startCooldown(seconds) {
                this.resendCooldown = seconds;
                if (this.cooldownTimer) clearInterval(this.cooldownTimer);
                this.cooldownTimer = setInterval(() => {
                    this.resendCooldown--;
                    if (this.resendCooldown <= 0) clearInterval(this.cooldownTimer);
                }, 1000);
            },
        }"
        class="space-y-5"
    >
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
                        x-model="email"
                        :placeholder="__('guest.login.email_placeholder')"
                        required
                        autofocus
                        autocomplete="username"
                    />
                </div>
                <x-input-error :messages="$errors->get('email')" class="mt-2" />
            </div>

            <div class="rounded-2xl border border-emerald-200/70 bg-emerald-50/40 p-4">
                <p class="text-sm font-medium text-slate-800">{{ __('guest.login_otp.title') }}</p>
                <p class="mt-1 text-xs leading-relaxed text-slate-600">{{ __('guest.login_otp.hint') }}</p>

                <button
                    type="button"
                    class="mt-3 inline-flex w-full items-center justify-center rounded-xl border border-baytgo bg-white px-4 py-2.5 text-sm font-semibold text-baytgo shadow-sm transition hover:bg-emerald-50 disabled:opacity-50 sm:w-auto"
                    @click="sendOtp()"
                    :disabled="otpSendLoading || resendCooldown > 0"
                >
                    <span x-show="!otpSendLoading && resendCooldown <= 0">{{ __('guest.login_otp.send') }}</span>
                    <span x-show="otpSendLoading" x-cloak>{{ __('guest.login_otp.sending') }}</span>
                    <span x-show="!otpSendLoading && resendCooldown > 0" x-cloak x-text="otpWaitTpl.replace(':n', resendCooldown)"></span>
                </button>

                <div class="mt-4">
                    <label for="otp" class="mb-2 block text-sm font-medium text-slate-700">{{ __('guest.login_otp.code_label') }}</label>
                    <input
                        id="otp"
                        type="text"
                        name="otp"
                        inputmode="numeric"
                        maxlength="6"
                        autocomplete="one-time-code"
                        x-model="otp"
                        value="{{ old('otp') }}"
                        required
                        class="block w-full rounded-xl border-slate-200 bg-white py-3 px-4 text-center text-lg font-mono tracking-[0.35em] shadow-sm focus:border-baytgo focus:ring-baytgo/20"
                        placeholder="••••••"
                    />
                    <x-input-error :messages="$errors->get('otp')" class="mt-2" />
                </div>

                <p
                    x-show="otpFeedback"
                    x-text="otpFeedback"
                    class="mt-3 text-xs text-slate-600"
                    x-cloak
                ></p>
            </div>

            <div class="flex items-center">
                <label for="remember_me" class="inline-flex items-center">
                    <input id="remember_me" type="checkbox" class="rounded border-slate-300 text-baytgo shadow-sm focus:ring-baytgo/30" name="remember">
                    <span class="ms-2 text-sm text-slate-600">{{ __('guest.login.remember') }}</span>
                </label>
            </div>

            <x-primary-button class="w-full justify-center rounded-xl bg-baytgo py-3.5 text-base font-semibold shadow-md shadow-baytgo/15 hover:bg-baytgo-800">
                {{ __('guest.login.submit') }}
            </x-primary-button>
        </form>
    </div>

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
