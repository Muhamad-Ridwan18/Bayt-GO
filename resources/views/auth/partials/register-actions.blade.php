<div class="space-y-4 border-t border-slate-100 pt-6">
    <x-ui.button>
        {{ __('guest.register.submit') }}
    </x-ui.button>

    <x-ui.divider :label="__('guest.or')" />

    <x-ui.button variant="secondary" :href="route('login')">
        {{ __('guest.register.has_account') }}
        <span class="ms-1 font-bold text-baytgo">{{ __('guest.register.login_link') }}</span>
    </x-ui.button>
</div>
