<x-guest-layout variant="login">
    <x-ui.auth-header
        icon="lock"
        :title="__('Confirm Password')"
        :subtitle="__('This is a secure area of the application. Please confirm your password before continuing.')"
    />

    <form method="POST" action="{{ route('password.confirm') }}" class="space-y-5">
        @csrf

        <x-ui.field :label="__('Password')" name="password" for="password">
            <x-ui.input
                id="password"
                icon="lock"
                type="password"
                name="password"
                required
                autocomplete="current-password"
                autofocus
            />
        </x-ui.field>

        <x-ui.button>
            {{ __('Confirm') }}
        </x-ui.button>
    </form>
</x-guest-layout>
