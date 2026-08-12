<x-app-layout>
    <div class="ui-page-y">
        <x-page-container class="ui-stack-compact">
            <div class="relative overflow-hidden rounded-3xl border border-slate-200 bg-gradient-to-br from-slate-900 via-amber-950 to-brand-950 p-8 text-white shadow-xl ring-1 ring-white/10">
                <div class="relative">
                    <p class="text-[11px] font-semibold uppercase tracking-wider text-amber-200/90">{{ __('admin.booking_payment_deadline.badge') }}</p>
                    <h1 class="mt-2 text-2xl font-bold tracking-tight">{{ __('admin.booking_payment_deadline.title') }}</h1>
                    <p class="mt-2 max-w-xl text-sm leading-relaxed text-white/80">{{ __('admin.booking_payment_deadline.subtitle') }}</p>
                    <div class="mt-6 flex flex-wrap gap-3">
                        <a href="{{ route('admin.settings.index') }}" class="inline-flex items-center rounded-xl bg-white/10 px-5 py-2.5 text-sm font-semibold text-white ring-1 ring-white/20 hover:bg-white/20">
                            {{ __('admin.booking_payment_deadline.back_settings') }}
                        </a>
                    </div>
                </div>
            </div>

            @if (session('status'))
                <div class="rounded-xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm font-medium text-emerald-900">
                    {{ session('status') }}
                </div>
            @endif

            <form method="post" action="{{ route('admin.booking-payment-deadline-settings.update') }}" class="space-y-6">
                @csrf

                <div class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
                    <h2 class="text-sm font-bold text-slate-900">{{ __('admin.booking_payment_deadline.form_heading') }}</h2>
                    <p class="mt-1 text-xs text-slate-500">{{ __('admin.booking_payment_deadline.form_hint') }}</p>

                    <div class="mt-5 grid gap-5 sm:grid-cols-2">
                        <div>
                            <x-input-label for="regular_hours" :value="__('admin.booking_payment_deadline.regular_hours')" />
                            <x-text-input
                                id="regular_hours"
                                name="regular_hours"
                                type="number"
                                min="1"
                                max="168"
                                class="mt-1 block w-full"
                                :value="old('regular_hours', $values['regular_hours'])"
                                required
                            />
                            <p class="mt-1 text-xs text-slate-500">{{ __('admin.booking_payment_deadline.regular_hours_hint') }}</p>
                            <x-input-error :messages="$errors->get('regular_hours')" class="mt-2" />
                        </div>
                        <div>
                            <x-input-label for="support_minutes" :value="__('admin.booking_payment_deadline.support_minutes')" />
                            <x-text-input
                                id="support_minutes"
                                name="support_minutes"
                                type="number"
                                min="15"
                                max="1440"
                                class="mt-1 block w-full"
                                :value="old('support_minutes', $values['support_minutes'])"
                                required
                            />
                            <p class="mt-1 text-xs text-slate-500">{{ __('admin.booking_payment_deadline.support_minutes_hint') }}</p>
                            <x-input-error :messages="$errors->get('support_minutes')" class="mt-2" />
                        </div>
                    </div>
                </div>

                <div class="flex justify-end">
                    <x-primary-button>
                        {{ __('admin.booking_payment_deadline.save') }}
                    </x-primary-button>
                </div>
            </form>
        </x-page-container>
    </div>
</x-app-layout>
