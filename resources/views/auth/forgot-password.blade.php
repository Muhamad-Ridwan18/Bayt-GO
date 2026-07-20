<x-guest-layout variant="login">
    <x-ui.auth-header
        icon="lock"
        title="Reset Password"
        :subtitle="__('auth_custom.forgot_intro')"
    />

    <x-auth-session-status class="mb-4" :status="session('status')" />

    <form method="POST" action="{{ route('password.email') }}" class="space-y-5">
        @csrf

        <x-ui.field :label="__('auth_custom.whatsapp_label')" name="phone" for="phone">
            <x-ui.input
                id="phone"
                icon="phone"
                type="text"
                name="phone"
                :value="old('phone')"
                :placeholder="__('auth_custom.whatsapp_placeholder')"
                required
                autofocus
            />
        </x-ui.field>

        <x-ui.button>
            {{ __('auth_custom.send_otp') }}
        </x-ui.button>
    </form>

    <x-ui.divider :label="__('guest.or')" />

    <x-ui.button variant="secondary" :href="route('login')">
        {{ __('layanan.guest_header_login') }}
    </x-ui.button>
</x-guest-layout>
