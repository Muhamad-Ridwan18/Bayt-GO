<x-guest-layout variant="login">
    <x-ui.auth-header
        icon="lock"
        title="Reset Password"
        :subtitle="__('auth_custom.forgot_intro')"
    />

    <form method="POST" action="{{ route('password.store') }}" class="space-y-5">
        @csrf

        <x-auth-session-status class="mb-4" :status="session('status')" />

        <input type="hidden" name="token" value="{{ $request->route('token') }}">

        <x-ui.field label="Kode OTP" name="otp" for="otp">
            <x-ui.input
                id="otp"
                type="text"
                name="otp"
                :value="old('otp')"
                required
                maxlength="6"
                inputmode="numeric"
                autofocus
                placeholder="6 digit kode"
            />
        </x-ui.field>
        <x-input-error :messages="$errors->get('token')" />

        <x-ui.field :label="__('Password')" name="password" for="password">
            <x-ui.input
                id="password"
                icon="lock"
                type="password"
                name="password"
                required
                autocomplete="new-password"
            />
        </x-ui.field>

        <x-ui.field :label="__('Confirm Password')" name="password_confirmation" for="password_confirmation">
            <x-ui.input
                id="password_confirmation"
                icon="lock"
                type="password"
                name="password_confirmation"
                required
                autocomplete="new-password"
            />
        </x-ui.field>

        <x-ui.button>
            Reset Password
        </x-ui.button>
    </form>
</x-guest-layout>
