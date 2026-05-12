<x-guest-layout>
    @php
        $registerOtpRoutes = [
            'send' => route('register.otp.send'),
            'verify' => route('register.otp.verify'),
            'clear' => route('register.otp.clear'),
        ];
    @endphp

    <div class="mb-6">
        <h1 class="text-xl font-semibold text-slate-900">Verifikasi WhatsApp</h1>
        <p class="mt-1 text-sm text-slate-500">
            Data pendaftaran Anda sudah kami simpan sementara. Masukkan kode OTP yang kami kirim ke <span class="font-medium text-slate-800">{{ $maskedPhone }}</span> untuk menyelesaikan pendaftaran.
        </p>
    </div>

    @if (session('status'))
        <div class="mb-4 rounded-lg border border-emerald-200 bg-emerald-50 px-3 py-2 text-sm text-emerald-900">
            {{ session('status') }}
        </div>
    @endif

    <div class="mb-5 rounded-xl border border-slate-200 bg-slate-50/80 p-4">
        <p class="text-sm font-medium text-slate-800">{{ __('auth_otp.change_phone_title') }}</p>
        <p class="mt-1 text-xs text-slate-600">{{ __('auth_otp.change_phone_hint') }}</p>
        <form method="POST" action="{{ route('register.pending-phone') }}" class="mt-3 space-y-3">
            @csrf
            <x-phone-international-input
                name="phone"
                :value="old('phone', $pendingPhone)"
                :country="old('country', $pendingCountry)"
                :label="__('auth_otp.new_phone_label')"
                :hint="__('auth_custom.phone_national_hint')"
                :required="true"
                input-id="pending_phone_local"
                select-id="pending_phone_country"
                error-key="phone"
            />
            <button
                type="submit"
                class="inline-flex justify-center items-center rounded-lg border border-slate-300 bg-white px-4 py-2 text-sm font-medium text-slate-800 shadow-sm hover:bg-slate-50"
            >
                {{ __('auth_otp.save_new_phone') }}
            </button>
        </form>
    </div>

    @php
        $bannerErrorMessages = collect($errors->getMessages())
            ->except(['phone', 'country'])
            ->flatten()
            ->all();
    @endphp
    @if (count($bannerErrorMessages) > 0)
        <div class="mb-4 rounded-lg border border-red-200 bg-red-50 px-3 py-2 text-sm text-red-800">
            <ul class="list-disc list-inside space-y-0.5">
                @foreach ($bannerErrorMessages as $err)
                    <li>{{ $err }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <div
        class="space-y-5 rounded-xl border border-slate-200 bg-white p-5 shadow-sm"
        x-data="registerVerifyOtpData()"
    >
        <input type="hidden" id="pending_phone" value="{{ $pendingPhone }}" />

        <div class="space-y-3 rounded-xl border border-brand-200/60 bg-brand-50/50 p-4">
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
                    <label class="block text-xs font-medium text-slate-700 mb-1" for="otp_code_pending">{{ __('auth_otp.code_label') }}</label>
                    <input
                        id="otp_code_pending"
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

        <form method="POST" action="{{ route('register.complete') }}" class="pt-2 border-t border-slate-100">
            @csrf
            <div class="flex flex-col-reverse sm:flex-row sm:items-center sm:justify-between gap-3">
                <a class="text-sm text-slate-600 hover:text-brand-700 font-medium text-center sm:text-left" href="{{ route('register') }}">
                    {{ __('auth_otp.back_edit') }}
                </a>
                <x-primary-button class="w-full sm:w-auto justify-center" type="submit" x-bind:disabled="!phoneVerified">
                    {{ __('auth_otp.finish_register') }}
                </x-primary-button>
            </div>
        </form>
    </div>

    <script>
        function registerVerifyOtpData() {
            return {
                otpRoutes: @json($registerOtpRoutes),
                otpWaitTpl: @json(__('auth_otp.wait')),
                role: @json($role),
                otpJs: {
                    sendFailed: @json(__('auth_otp.js_send_failed_fallback')),
                    sendOk: @json(__('auth_otp.js_send_ok_fallback')),
                    codeDigits: @json(__('auth_otp.js_code_digits')),
                    verifyFailed: @json(__('auth_otp.js_verify_failed_fallback')),
                    verifyOk: @json(__('auth_otp.js_verify_ok_fallback')),
                },
                phoneVerified: @json($phoneVerifiedInitial),
                otpSendLoading: false,
                otpVerifyLoading: false,
                otpFeedback: '',
                otpCode: '',
                resendCooldown: 0,
                _resendTimer: null,
                phoneValue() {
                    const el = document.getElementById('pending_phone');
                    return el ? String(el.value).trim() : '';
                },
                async sendOtp() {
                    this.otpFeedback = '';
                    const phone = this.phoneValue();
                    this.otpSendLoading = true;
                    try {
                        const res = await fetch(this.otpRoutes.send, {
                            method: 'POST',
                            headers: {
                                'Content-Type': 'application/json',
                                Accept: 'application/json',
                                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                                'X-Requested-With': 'XMLHttpRequest',
                            },
                            credentials: 'same-origin',
                            body: JSON.stringify({ phone, role: this.role }),
                        });
                        const data = await res.json().catch(() => ({}));
                        if (!res.ok) {
                            const msg = data.errors?.phone?.[0] || data.message || this.otpJs.sendFailed;
                            throw new Error(msg);
                        }
                        this.otpFeedback = data.message || this.otpJs.sendOk;
                        this.phoneVerified = false;
                        this.startResendCooldown();
                    } catch (e) {
                        this.otpFeedback = e.message || this.otpJs.sendFailed;
                    } finally {
                        this.otpSendLoading = false;
                    }
                },
                startResendCooldown() {
                    if (this._resendTimer) {
                        clearInterval(this._resendTimer);
                        this._resendTimer = null;
                    }
                    this.resendCooldown = 60;
                    this._resendTimer = setInterval(() => {
                        this.resendCooldown--;
                        if (this.resendCooldown <= 0) {
                            clearInterval(this._resendTimer);
                            this._resendTimer = null;
                        }
                    }, 1000);
                },
                async verifyOtp() {
                    this.otpFeedback = '';
                    const phone = this.phoneValue();
                    const otp = String(this.otpCode).replace(/\D/g, '');
                    if (otp.length !== 6) {
                        this.otpFeedback = this.otpJs.codeDigits;
                        return;
                    }
                    this.otpVerifyLoading = true;
                    try {
                        const res = await fetch(this.otpRoutes.verify, {
                            method: 'POST',
                            headers: {
                                'Content-Type': 'application/json',
                                Accept: 'application/json',
                                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                                'X-Requested-With': 'XMLHttpRequest',
                            },
                            credentials: 'same-origin',
                            body: JSON.stringify({ phone, otp }),
                        });
                        const data = await res.json().catch(() => ({}));
                        if (!res.ok) {
                            const msg = data.errors?.otp?.[0] || data.message || this.otpJs.verifyFailed;
                            throw new Error(msg);
                        }
                        this.phoneVerified = true;
                        this.otpFeedback = data.message || this.otpJs.verifyOk;
                    } catch (e) {
                        this.otpFeedback = e.message || this.otpJs.verifyFailed;
                    } finally {
                        this.otpVerifyLoading = false;
                    }
                },
            };
        }
    </script>
</x-guest-layout>
