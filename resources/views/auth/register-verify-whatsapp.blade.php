<x-guest-layout>
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

    <div class="space-y-5 rounded-xl border border-slate-200 bg-white p-5 shadow-sm">
        <div class="space-y-3 rounded-xl border border-brand-200/60 bg-brand-50/50 p-4">
            <p class="text-sm font-medium text-slate-800">{{ __('auth_otp.title') }}</p>
            <p class="text-xs text-slate-600">{{ __('auth_otp.hint') }}</p>
            <p class="text-sm text-slate-700">
                Kode OTP telah dikirim ke WhatsApp Anda di
                <span class="font-semibold">{{ $maskedPhone }}</span>.
            </p>
            <div class="pt-2 border-t border-brand-200/60 flex items-center justify-between gap-2">
                <span class="text-xs text-slate-500">{{ __('auth_otp.resend_label') }}</span>
                <form method="POST" action="{{ route('register.pending-phone') }}" class="inline">
                    @csrf
                    <input type="hidden" name="phone" value="{{ $pendingPhone }}" />
                    <input type="hidden" name="country" value="{{ $pendingCountry }}" />
                    <button type="submit" class="text-xs font-semibold text-brand-700 hover:text-brand-800 transition focus:outline-none">
                        {{ __('auth_otp.resend_btn') }}
                    </button>
                </form>
            </div>
        </div>

        <form method="POST" action="{{ route('register.complete') }}" class="pt-2 border-t border-slate-100 space-y-4">
            @csrf
            <div>
                <x-input-label for="otp" value="Kode OTP" />
                <x-text-input id="otp" name="otp" type="text" inputmode="numeric" maxlength="6" autocomplete="one-time-code" class="block w-full border-slate-300" placeholder="••••••" :value="old('otp')" />
                <x-input-error :messages="$errors->get('otp')" class="mt-2" />
            </div>

            <div class="flex flex-col-reverse sm:flex-row sm:items-center sm:justify-between gap-3">
                <a class="text-sm text-slate-600 hover:text-brand-700 font-medium text-center sm:text-left" href="{{ route('register') }}">
                    {{ __('auth_otp.back_edit') }}
                </a>
                <x-primary-button class="w-full sm:w-auto justify-center" type="submit">
                    {{ __('auth_otp.finish_register') }}
                </x-primary-button>
            </div>
        </form>
    </div>
</x-guest-layout>
