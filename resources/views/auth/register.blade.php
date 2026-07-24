<x-guest-layout variant="register" :wide="true">
    <x-ui.auth-header
        icon="user-plus"
        :title="__('guest.register.title')"
        :subtitle="__('guest.register.subtitle')"
    >
        <p class="text-xs text-slate-500">{{ __('guest.register.required_hint') }}</p>
    </x-ui.auth-header>

    @if ($page->otpEnabled)
        <x-ui.notice tone="info" class="mb-6">
            {{ __('guest.register.otp_notice') }}
        </x-ui.notice>
    @endif

    <form
        id="register-form"
        x-ref="registerForm"
        method="POST"
        action="{{ route('register') }}"
        enctype="multipart/form-data"
        class="space-y-5"
        x-data="registerForm(@js($page->alpineConfig()))"
        data-submit-lock="off"
        @submit.prevent="handleRegisterSubmit"
    >
        @csrf

        @include('auth.partials.register-role')
        @include('auth.partials.register-customer-type')
        @include('auth.partials.register-shared-fields')
        @include('auth.partials.register-customer-fields')
        @include('auth.partials.register-password-fields')
        @include('auth.partials.register-muthowif-fields')
        @include('auth.partials.register-actions')

        <x-auth.terms-modal />
    </form>
</x-guest-layout>
