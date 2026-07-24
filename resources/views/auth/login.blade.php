<x-guest-layout variant="login">
    <x-auth-session-status class="mb-4" :status="$status" />

    @if ($showApprovedNotice)
        <x-ui.notice tone="success">
            {{ __('guest.login.approved_notice') }}
        </x-ui.notice>
    @endif

    <x-ui.auth-header
        icon="user"
        :title="__('guest.login.title')"
        :subtitle="__('guest.login.subtitle')"
    />

    <form method="POST" action="{{ route('login') }}" class="space-y-5">
        @csrf

        <x-ui.field :label="__('Email')" name="email" for="email">
            <x-ui.input
                id="email"
                icon="email"
                type="email"
                name="email"
                :value="old('email')"
                :placeholder="__('guest.login.email_placeholder')"
                required
                autofocus
                autocomplete="username"
            />
        </x-ui.field>

        <x-ui.field :label="__('Password')" name="password" for="password">
            <x-ui.input
                id="password"
                icon="lock"
                type="password"
                name="password"
                :placeholder="__('guest.login.password_placeholder')"
                required
                autocomplete="current-password"
            />
        </x-ui.field>

        <div class="flex items-center justify-between gap-3">
            <label for="remember_me" class="inline-flex items-center">
                <input id="remember_me" type="checkbox" class="rounded border-slate-300 text-baytgo shadow-sm focus:ring-baytgo/30" name="remember">
                <span class="ms-2 text-sm text-slate-600">{{ __('guest.login.remember') }}</span>
            </label>
            <a class="text-sm font-semibold text-baytgo hover:text-baytgo-800" href="{{ route('password.request') }}">
                {{ __('guest.login.forgot') }}
            </a>
        </div>

        <x-ui.button>
            {{ __('guest.login.submit') }}
        </x-ui.button>
    </form>

    <x-ui.divider :label="__('guest.or')" />

    <x-ui.button variant="secondary" :href="route('register')">
        {{ __('guest.login.no_account') }}
        <span class="ms-1 font-bold text-baytgo">{{ __('guest.login.register_link') }}</span>
    </x-ui.button>
</x-guest-layout>
