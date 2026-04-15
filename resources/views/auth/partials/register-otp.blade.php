@props(['roleGate'])

<div
    x-show="otpEnabled && role === '{{ $roleGate }}'"
    x-cloak
    class="space-y-3 rounded-xl border border-brand-200/60 bg-brand-50/50 p-4"
>
    <p class="text-sm font-medium text-slate-800">{{ __('auth_otp.title') }}</p>
    <p class="text-xs text-slate-600">{{ __('auth_otp.hint') }}</p>

    <div class="flex flex-col sm:flex-row gap-2 sm:items-center">
        <button
            type="button"
            class="inline-flex justify-center items-center rounded-lg border border-brand-600 bg-white px-4 py-2 text-sm font-medium text-brand-700 shadow-sm hover:bg-brand-50 disabled:opacity-50"
            @click="sendOtp()"
            :disabled="otpSendLoading || (resendCooldown > 0)"
        >
            <span x-show="!otpSendLoading && resendCooldown <= 0">{{ __('auth_otp.send') }}</span>
            <span x-show="otpSendLoading" x-cloak>{{ __('auth_otp.sending') }}</span>
            <span x-show="!otpSendLoading && resendCooldown > 0" x-cloak x-text="otpWaitTpl.replace(':n', resendCooldown)"></span>
        </button>
    </div>

    <div class="flex flex-col sm:flex-row gap-2 sm:items-end">
        <div class="flex-1 min-w-0">
            <label class="block text-xs font-medium text-slate-700 mb-1" for="otp_code_{{ $roleGate }}">Kode OTP (6 digit)</label>
            <input
                id="otp_code_{{ $roleGate }}"
                type="text"
                inputmode="numeric"
                maxlength="6"
                autocomplete="one-time-code"
                x-model="otpCode"
                class="block w-full rounded-lg border-slate-300 shadow-sm focus:border-brand-500 focus:ring-brand-500 text-sm tracking-widest font-mono"
                placeholder="••••••"
            />
        </div>
        <button
            type="button"
            class="inline-flex justify-center items-center rounded-lg bg-brand-600 px-4 py-2 text-sm font-medium text-white shadow-sm hover:bg-brand-700 disabled:opacity-50"
            @click="verifyOtp()"
            :disabled="otpVerifyLoading"
        >
            <span x-show="!otpVerifyLoading">{{ __('auth_otp.verify') }}</span>
            <span x-show="otpVerifyLoading" x-cloak>{{ __('auth_otp.verifying') }}</span>
        </button>
    </div>

    <p
        x-show="otpFeedback"
        x-text="otpFeedback"
        class="text-xs"
        x-bind:class="phoneVerified ? 'text-emerald-700 font-medium' : 'text-slate-600'"
    ></p>

    <div x-show="phoneVerified" x-cloak class="flex items-center gap-2 text-xs font-medium text-emerald-700">
        <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 shrink-0" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" aria-hidden="true">
            <path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75L11.25 15 15 9.75M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
        </svg>
        {{ __('auth_otp.verified') }}
    </div>
</div>
