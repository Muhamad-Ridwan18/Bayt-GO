<x-guest-layout>
    <div class="mb-4 text-sm text-gray-600">
        {{ __('auth_custom.forgot_intro') }}
    </div>

    <!-- Session Status -->
    <x-auth-session-status class="mb-4" :status="session('status')" />

    <form method="POST" action="{{ route('password.email') }}">
        @csrf

        <!-- WhatsApp Number -->
        <div>
            <x-input-label for="phone" :value="__('auth_custom.whatsapp_label')" />
            <x-text-input id="phone" class="block mt-1 w-full" type="text" name="phone" :value="old('phone')" required autofocus :placeholder="__('auth_custom.whatsapp_placeholder')" />
            <x-input-error :messages="$errors->get('phone')" class="mt-2" />
        </div>

        <div class="flex items-center justify-end mt-4">
            <x-primary-button>
                {{ __('auth_custom.send_otp') }}
            </x-primary-button>
        </div>
    </form>
</x-guest-layout>
