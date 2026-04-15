<x-guest-layout>
    <div class="text-center space-y-4">
        <div class="mx-auto flex h-14 w-14 items-center justify-center rounded-full bg-amber-100 text-amber-700">
            <svg class="h-7 w-7" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
            </svg>
        </div>
        <h1 class="text-xl font-semibold text-slate-900">{{ __('auth_custom.muthowif_pending_title') }}</h1>
        <p class="text-sm text-slate-600 leading-relaxed">
            {!! __('auth_custom.muthowif_pending_body_html') !!}
        </p>
        <p class="text-xs text-slate-500">
            {{ __('auth_custom.muthowif_pending_hint') }}
        </p>
        <div class="pt-2">
            <a href="{{ route('login') }}" class="inline-flex items-center justify-center rounded-xl bg-brand-600 px-5 py-2.5 text-sm font-semibold text-white shadow-sm hover:bg-brand-700 transition">
                {{ __('auth_custom.muthowif_pending_login') }}
            </a>
        </div>
    </div>
</x-guest-layout>
