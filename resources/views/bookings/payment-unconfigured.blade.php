<x-app-layout>

    <div class="py-8 sm:py-12">
        <div class="max-w-xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="rounded-2xl border border-amber-200 bg-amber-50 px-5 py-6 text-sm text-amber-950">
                <p class="font-semibold">{{ __('bookings.unconfigured.title') }}</p>
                <p class="mt-2 leading-relaxed">
                    {!! __('bookings.unconfigured.body') !!}
                </p>
                <p class="mt-3 font-mono text-xs break-all bg-white/60 rounded-lg px-3 py-2 border border-amber-200">
                    {{ url('/payments/midtrans/notification') }}
                </p>
                <a href="{{ route('bookings.show', $booking) }}" class="mt-4 inline-block text-sm font-semibold text-brand-800 hover:text-brand-900">
                    {{ __('bookings.unconfigured.back') }}
                </a>
            </div>
        </div>
    </div>
</x-app-layout>
