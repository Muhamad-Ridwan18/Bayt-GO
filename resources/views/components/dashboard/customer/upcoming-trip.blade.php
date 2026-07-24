@props([
    'trip' => null,
    'bookingsUrl',
    'layananUrl',
])

<section class="rounded-3xl border border-slate-100 bg-white p-5 shadow-sm ring-1 ring-slate-100/90" aria-labelledby="customer-up-heading">
    <div class="mb-4 flex flex-wrap items-center justify-between gap-2">
        <h2 id="customer-up-heading" class="text-base font-bold text-baytgo">{{ __('dashboard.customer_upcoming_title') }}</h2>
        <a href="{{ $bookingsUrl }}" class="text-xs font-semibold text-baytgo hover:text-baytgo-800">{{ __('dashboard.customer_upcoming_see_all') }}</a>
    </div>

    @if ($trip === null)
        <div class="rounded-2xl border border-dashed border-slate-200 bg-slate-50/80 py-8 text-center">
            <p class="text-sm font-medium text-slate-700">{{ __('dashboard.customer_upcoming_empty') }}</p>
            <a href="{{ $layananUrl }}" class="mt-4 inline-flex items-center justify-center rounded-xl bg-baytgo px-4 py-2.5 text-xs font-semibold text-white shadow-md shadow-baytgo/20 transition hover:bg-baytgo-800">
                {{ __('dashboard.customer_upcoming_cta') }}
            </a>
        </div>
    @else
        <div class="overflow-hidden rounded-2xl border border-slate-100 ring-1 ring-slate-100/90">
            <div class="grid grid-cols-[5rem_minmax(0,1fr)] gap-3 p-3.5">
                <div class="relative h-[5rem] overflow-hidden rounded-xl bg-slate-100">
                    @if ($trip['photo_url'])
                        <img src="{{ $trip['photo_url'] }}" alt="" class="h-full w-full object-cover object-top" loading="lazy" />
                    @endif
                </div>
                <div class="min-w-0">
                    <p class="font-bold leading-snug text-slate-900">{{ $trip['service_label'] }}</p>
                    <p class="mt-1 text-xs text-slate-600">{{ __('dashboard.customer_with_guide', ['name' => $trip['guide_name']]) }}</p>
                    <p class="mt-2 text-xs text-slate-600">{{ $trip['date_range'] }}</p>
                    <span @class([
                        'mt-2 inline-flex rounded-full px-2 py-0.5 text-[10px] font-bold uppercase',
                        'bg-gold-light/45 text-baytgo ring-1 ring-gold-muted/40' => $trip['payment_paid'],
                        'bg-welcomeCanvas text-baytgo ring-1 ring-slate-200' => ! $trip['payment_paid'],
                    ])>
                        {{ $trip['payment_label'] }}
                    </span>
                </div>
            </div>
            <div class="border-t border-slate-100 bg-welcomeCanvas/50 p-3">
                <a href="{{ $trip['href'] }}" class="flex w-full items-center justify-center rounded-xl border border-baytgo/25 bg-white py-2.5 text-sm font-semibold text-baytgo transition hover:border-baytgo hover:bg-baytgo hover:text-white">
                    {{ __('dashboard.customer_booking_detail_cta') }}
                </a>
            </div>
        </div>
    @endif
</section>
