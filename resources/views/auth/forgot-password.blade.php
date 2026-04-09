<x-guest-layout>
    <div class="mb-4 text-sm text-gray-600">
        Lupa password? Masukkan nomor WhatsApp Anda, kami akan kirim kode OTP untuk reset password.
    </div>

    <!-- Session Status -->
    <x-auth-session-status class="mb-4" :status="session('status')" />

    <form method="POST" action="{{ route('password.email') }}">
        @csrf

        <!-- WhatsApp Number -->
        <div>
            <x-input-label for="phone" value="Nomor WhatsApp" />
            <x-text-input id="phone" class="block mt-1 w-full" type="text" name="phone" :value="old('phone')" required autofocus placeholder="Contoh: 081234567890" />
            <x-input-error :messages="$errors->get('phone')" class="mt-2" />
        </div>

        <div class="flex items-center justify-end mt-4">
            <x-primary-button>
                Kirim Kode OTP
            </x-primary-button>
        </div>
    </form>
</x-guest-layout>
