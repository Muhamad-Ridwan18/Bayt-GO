@php
    use App\Enums\BookingStatus;
    use App\Enums\MuthowifServiceType;
    use App\Enums\PaymentStatus;
    use App\Support\BookingPostPayRules;
    use App\Support\IndonesianNumber;
    use Carbon\Carbon;

    $b = $booking;
    $st = $b->status;
    $nights = $b->billingNightsInclusive();
    $b->loadMissing(['muthowifProfile.services']);
    $service = $b->muthowifProfile?->services->firstWhere('type', $b->service_type);
    $daily = $service && $service->daily_price !== null ? (float) $service->daily_price : null;
    $baseSubtotal = $daily !== null ? $nights * $daily : 0.0;
    $addonLines = collect();
    if ($b->service_type === MuthowifServiceType::PrivateJamaah && ! empty($b->selected_add_on_ids)) {
        foreach ($b->selected_add_on_ids as $aid) {
            if (isset($addonsById[$aid])) {
                $addonLines->push($addonsById[$aid]);
            }
        }
    }
    $addonsSum = $addonLines->sum(fn ($a) => (float) $a->price);
    $sameHotelLine = 0.0;
    if ($b->with_same_hotel && $service && $service->same_hotel_price_per_day !== null) {
        $sameHotelLine = $nights * (float) $service->same_hotel_price_per_day;
    }
    $transportLine = 0.0;
    if ($b->with_transport && $service && $service->transport_price_flat !== null) {
        $transportLine = (float) $service->transport_price_flat;
    }
    $baseTotal = $b->resolvedAmountDue();
    $split = \App\Support\PlatformFee::split((float) $baseTotal);
    $customerTotal = (float) ($split['customer_gross'] ?? $baseTotal);
    $customerPlatformFee = (float) ($split['customer_fee'] ?? 0.0);
    $muthowifNet = (float) ($split['muthowif_net'] ?? 0.0);
    $review = $b->review;
    $fmt = fn (float $n) => IndonesianNumber::formatThousands((string) (int) round($n));
    $dateLocale = app()->getLocale() === 'id' ? 'id-ID' : 'en-GB';

    $statusCardStyles = [
        BookingStatus::Pending->value => [
            'bar' => 'from-amber-400 via-amber-500 to-orange-500',
            'glow' => 'shadow-amber-500/10',
            'badge' => 'bg-amber-100 text-amber-950 ring-amber-200/80',
        ],
        BookingStatus::Confirmed->value => [
            'bar' => 'from-emerald-400 via-emerald-500 to-teal-600',
            'glow' => 'shadow-emerald-500/10',
            'badge' => 'bg-emerald-100 text-emerald-950 ring-emerald-200/80',
        ],
        BookingStatus::Completed->value => [
            'bar' => 'from-brand-400 via-brand-500 to-brand-700',
            'glow' => 'shadow-brand-500/15',
            'badge' => 'bg-brand-100 text-brand-950 ring-brand-200/80',
        ],
        BookingStatus::Cancelled->value => [
            'bar' => 'from-slate-300 via-slate-400 to-slate-500',
            'glow' => 'shadow-slate-500/10',
            'badge' => 'bg-slate-100 text-slate-800 ring-slate-200/80',
        ],
    ];
    $cardStyle = $statusCardStyles[$st->value] ?? $statusCardStyles[BookingStatus::Pending->value];
@endphp

<x-app-layout>
    <div class="relative min-h-[calc(100vh-4rem)] overflow-hidden bg-slate-50">
        <div class="pointer-events-none absolute inset-0 overflow-hidden" aria-hidden="true">
            <div class="absolute -right-24 -top-24 h-96 w-96 rounded-full bg-brand-400/15 blur-3xl"></div>
            <div class="absolute -left-20 top-32 h-80 w-80 rounded-full bg-emerald-400/10 blur-3xl"></div>
            <div class="absolute bottom-0 left-1/2 h-64 w-[120%] -translate-x-1/2 bg-gradient-to-t from-white to-transparent"></div>
        </div>

        <div class="relative z-10 mx-auto max-w-3xl px-4 pb-16 pt-8 sm:px-6 lg:px-8">
            <a href="{{ route('bookings.index') }}" class="mb-6 inline-flex items-center gap-2 text-sm font-semibold text-brand-700 transition hover:text-brand-800">
                <svg class="h-4 w-4" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
                    <path fill-rule="evenodd" d="M17 10a.75.75 0 01-.75.75H5.612l4.158 3.96a.75.75 0 11-1.04 1.08l-5.5-5.25a.75.75 0 010-1.08l5.5-5.25a.75.75 0 111.04 1.08L5.612 9.25H16.25A.75.75 0 0117 10z" clip-rule="evenodd" />
                </svg>
                {{ __('bookings.show.back_to_bookings') }}
            </a>

            {{-- Hero --}}
            <article class="relative overflow-hidden rounded-3xl border border-slate-200/80 bg-white shadow-lg shadow-slate-900/5 transition hover:shadow-xl hover:shadow-slate-900/10 {{ $cardStyle['glow'] }}">
                <div class="flex flex-col sm:flex-row">
                    <div class="relative h-1.5 w-full shrink-0 self-stretch sm:h-auto sm:w-1.5">
                        <div class="h-full min-h-[4px] w-full bg-gradient-to-r sm:bg-gradient-to-b {{ $cardStyle['bar'] }}"></div>
                    </div>
                    <div class="min-w-0 flex-1 p-6 sm:p-8">
                        <p class="inline-flex items-center gap-2 text-xs font-semibold uppercase tracking-wider text-slate-500">
                            <svg class="h-3.5 w-3.5 text-brand-600" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
                                <path fill-rule="evenodd" d="M4.75 2a.75.75 0 01.75.75V4h7V2.75a.75.75 0 011.5 0V4h.25A2.75 2.75 0 0118 6.75v8.5A2.75 2.75 0 0115.25 18H4.75A2.75 2.75 0 012 15.25v-8.5A2.75 2.75 0 014.75 4H5V2.75A.75.75 0 014.75 2zm-1 5.5c-.69 0-1.25.56-1.25 1.25v6.5c0 .69.56 1.25 1.25 1.25h10.5c.69 0 1.25-.56 1.25-1.25v-6.5c0-.69-.56-1.25-1.25-1.25H3.75z" clip-rule="evenodd" />
                            </svg>
                            {{ __('bookings.show.detail_kicker') }}
                        </p>

                        <div class="mt-4 flex flex-col gap-6 sm:flex-row sm:items-start sm:justify-between">
                            <div class="flex min-w-0 gap-4">
                                <img
                                    src="{{ route('layanan.photo', $b->muthowifProfile) }}"
                                    alt="{{ __('bookings.index.photo_alt', ['name' => $b->muthowifProfile->user->name]) }}"
                                    class="h-20 w-20 shrink-0 rounded-2xl object-cover shadow-md ring-2 ring-white sm:h-24 sm:w-24"
                                    loading="lazy"
                                >
                                <div class="min-w-0 flex-1 space-y-3">
                                    <div>
                                        <h1 class="text-xl font-bold tracking-tight text-slate-900 sm:text-2xl">
                                            {{ $b->muthowifProfile->user->name }}
                                        </h1>
                                        <a
                                            href="{{ route('layanan.show', $b->muthowifProfile) }}"
                                            class="mt-1 inline-flex items-center gap-1 text-sm font-semibold text-brand-700 hover:text-brand-800"
                                        >
                                            {{ __('marketplace.card.view_profile') }}
                                            <svg class="h-3.5 w-3.5" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
                                                <path fill-rule="evenodd" d="M3 10a.75.75 0 01.75-.75h10.638L10.23 5.29a.75.75 0 111.04-1.08l5.5 5.25a.75.75 0 010 1.08l-5.5 5.25a.75.75 0 11-1.04-1.08l4.158-3.96H3.75A.75.75 0 013 10z" clip-rule="evenodd" />
                                            </svg>
                                        </a>
                                    </div>
                                    @if (filled($b->booking_code))
                                        <div class="rounded-xl border border-slate-100 bg-slate-50/90 px-3 py-2 ring-1 ring-slate-100">
                                            <p class="text-[0.65rem] font-semibold uppercase tracking-wide text-slate-500">{{ __('bookings.show.booking_code') }}</p>
                                            <p class="font-mono text-sm font-semibold tracking-tight text-slate-900">{{ $b->booking_code }}</p>
                                        </div>
                                    @endif
                                    <div class="flex flex-wrap gap-2">
                                        <span class="inline-flex items-center rounded-full px-2.5 py-0.5 text-xs font-semibold ring-1 {{ $cardStyle['badge'] }}">
                                            {{ $st->label() }}
                                        </span>
                                        @if ($b->payment_status !== PaymentStatus::Pending)
                                            <span class="inline-flex items-center rounded-full px-2.5 py-0.5 text-xs font-semibold ring-1 {{ match ($b->payment_status) {
                                                PaymentStatus::Paid => 'bg-brand-50 text-brand-950 ring-brand-200/80',
                                                PaymentStatus::RefundPending => 'bg-amber-50 text-amber-950 ring-amber-200/80',
                                                PaymentStatus::Refunded => 'bg-slate-100 text-slate-800 ring-slate-200/80',
                                                default => 'bg-orange-50 text-orange-950 ring-orange-200/80',
                                            } }}">
                                                {{ $b->payment_status->label() }}
                                            </span>
                                        @endif
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="mt-6 grid grid-cols-1 gap-4 sm:grid-cols-2">
                            <div class="rounded-2xl bg-slate-50/90 p-4 ring-1 ring-slate-200/60">
                                <p class="text-[0.65rem] font-semibold uppercase tracking-wide text-slate-500">{{ __('bookings.show.period') }}</p>
                                <p class="mt-1 text-sm font-semibold tabular-nums text-slate-900">
                                    {{ Carbon::parse($b->starts_on)->format('d/m/Y') }}
                                    <span class="mx-1 font-normal text-slate-400">→</span>
                                    {{ Carbon::parse($b->ends_on)->format('d/m/Y') }}
                                </p>
                                <p class="mt-1 text-xs text-slate-500">{{ __('bookings.show.service_days_line', ['count' => $nights]) }}</p>
                            </div>
                            <div class="rounded-2xl bg-slate-50/90 p-4 ring-1 ring-slate-200/60">
                                <p class="text-[0.65rem] font-semibold uppercase tracking-wide text-slate-500">{{ __('bookings.show.service') }}</p>
                                <p class="mt-1 text-sm font-semibold text-slate-900">{{ $b->service_type?->label() ?? '—' }}</p>
                                <p class="mt-1 text-xs text-slate-500">{{ __('bookings.index.pilgrims_count', ['count' => $b->pilgrim_count, 'pilgrims_word' => __('common.pilgrims')]) }}</p>
                            </div>
                        </div>
                    </div>
                </div>
            </article>

            @if (in_array($st, [BookingStatus::Confirmed, BookingStatus::Completed], true) || ($st === BookingStatus::Cancelled && $b->paid_at))
                <div class="mt-8 overflow-hidden rounded-3xl border border-slate-200/80 bg-white shadow-md shadow-slate-900/5">
                    <div class="border-b border-slate-100 bg-gradient-to-r from-slate-50 to-white px-6 py-4 sm:px-8">
                        <h2 class="flex items-center gap-2 text-base font-bold text-slate-900">
                            <span class="flex h-8 w-8 items-center justify-center rounded-lg bg-brand-100 text-brand-700">
                                <svg class="h-4 w-4" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
                                    <path fill-rule="evenodd" d="M2.5 4A1.5 1.5 0 014 2.5h12A1.5 1.5 0 0117.5 4v12a1.5 1.5 0 01-1.5 1.5h-12A1.5 1.5 0 012.5 16V4zm2-1.5a.5.5 0 00-.5.5v12a.5.5 0 00.5.5h12a.5.5 0 00.5-.5v-12a.5.5 0 00-.5-.5h-12z" clip-rule="evenodd" />
                                    <path fill-rule="evenodd" d="M5 4.75A.75.75 0 015.75 4h8.5a.75.75 0 01.75.75v2.5a.75.75 0 01-.75.75h-8.5A.75.75 0 015 7.25v-2.5zm0 2.5A.75.75 0 015.75 6h8.5a.75.75 0 01.75.75v2.5a.75.75 0 01-.75.75h-8.5A.75.75 0 015 9.25v-2.5zm0 2.5A.75.75 0 015.75 8h8.5a.75.75 0 01.75.75v2.5a.75.75 0 01-.75.75h-8.5A.75.75 0 015 11.25v-2.5zm0 2.5A.75.75 0 015.75 10h8.5a.75.75 0 01.75.75v2.5a.75.75 0 01-.75.75h-8.5A.75.75 0 015 13.25v-2.5z" clip-rule="evenodd" />
                                </svg>
                            </span>
                            {{ __('bookings.show.payment_heading') }}
                        </h2>
                    </div>
                    <div class="p-6 sm:p-8">
                        <dl class="space-y-3 rounded-2xl bg-slate-50/80 p-4 text-sm ring-1 ring-slate-100 sm:p-5">
                            <div class="flex justify-between gap-4">
                                <dt class="text-slate-600">{{ __('bookings.show.rate_per_day') }}</dt>
                                <dd class="font-medium tabular-nums text-slate-900">
                                    @if ($daily !== null)
                                        Rp {{ $fmt($daily) }}
                                    @else
                                        —
                                    @endif
                                </dd>
                            </div>
                            <div class="flex justify-between gap-4">
                                <dt class="text-slate-600">{{ __('bookings.show.day_count') }}</dt>
                                <dd class="font-medium text-slate-900">{{ __('bookings.show.days_count', ['count' => $nights]) }}</dd>
                            </div>
                            <div class="flex justify-between gap-4 border-t border-slate-200/80 pt-3">
                                <dt class="text-slate-600">{{ __('bookings.show.subtotal_service') }}</dt>
                                <dd class="font-medium tabular-nums text-slate-900">Rp {{ $fmt($baseSubtotal) }}</dd>
                            </div>
                            @if ($addonLines->isNotEmpty())
                                @foreach ($addonLines as $ad)
                                    <div class="flex justify-between gap-4">
                                        <dt class="text-slate-600">+ {{ $ad->name }}</dt>
                                        <dd class="font-medium tabular-nums text-slate-900">Rp {{ $fmt((float) $ad->price) }}</dd>
                                    </div>
                                @endforeach
                            @endif
                            @if ($sameHotelLine > 0)
                                <div class="flex justify-between gap-4">
                                    <dt class="text-slate-600">{{ __('bookings.show.same_hotel_label', ['nights' => $nights, 'days' => __('common.days')]) }}</dt>
                                    <dd class="font-medium tabular-nums text-slate-900">Rp {{ $fmt($sameHotelLine) }}</dd>
                                </div>
                            @endif
                            @if ($transportLine > 0)
                                <div class="flex justify-between gap-4">
                                    <dt class="text-slate-600">{{ __('bookings.show.transport_label') }}</dt>
                                    <dd class="font-medium tabular-nums text-slate-900">Rp {{ $fmt($transportLine) }}</dd>
                                </div>
                            @endif
                            <div class="flex justify-between gap-4 border-t border-slate-200/80 pt-3">
                                <dt class="text-slate-600">{{ __('bookings.show.platform_fee') }}</dt>
                                <dd class="font-medium tabular-nums text-slate-900">Rp {{ $fmt($customerPlatformFee) }}</dd>
                            </div>
                            <div class="flex justify-between gap-4 border-t border-slate-200 pt-3 text-base">
                                <dt class="font-semibold text-slate-900">{{ __('bookings.show.total_customer') }}</dt>
                                <dd class="font-bold tabular-nums text-brand-700">Rp {{ $fmt($customerTotal) }}</dd>
                            </div>
                        </dl>

                        @if ($b->isAwaitingPayment())
                            @php $paymentQuery = request()->query('payment'); @endphp
                            @if ($paymentQuery === 'success')
                                <p class="mt-5 text-xs leading-relaxed text-slate-600">
                                    {!! __('bookings.show.midtrans_wait_html') !!}
                                </p>
                            @else
                                <p class="mt-5 text-xs leading-relaxed text-slate-600">
                                    {{ __('bookings.show.total_includes_fee') }}
                                </p>
                            @endif

                            <a href="{{ route('bookings.payment', $b) }}" class="mt-4 inline-flex w-full items-center justify-center gap-2 rounded-2xl bg-gradient-to-r from-brand-600 to-brand-700 px-6 py-3.5 text-sm font-semibold text-white shadow-lg shadow-brand-600/25 transition hover:from-brand-500 hover:to-brand-600 sm:w-auto">
                                {{ __('bookings.show.pay') }}
                                <svg class="h-4 w-4" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
                                    <path fill-rule="evenodd" d="M3 10a.75.75 0 01.75-.75h10.638L10.23 5.29a.75.75 0 111.04-1.08l5.5 5.25a.75.75 0 010 1.08l-5.5 5.25a.75.75 0 11-1.04-1.08l4.158-3.96H3.75A.75.75 0 013 10z" clip-rule="evenodd" />
                                </svg>
                            </a>
                        @elseif ($b->paid_at && ($b->isPaid() || $b->isRefundPending() || $b->isRefunded()))
                            <p class="mt-5 text-sm font-medium text-emerald-800">
                                {{ __('bookings.show.paid_at', ['datetime' => $b->paid_at->timezone(config('app.timezone'))->format('d/m/Y H:i')]) }}
                            </p>
                            <a href="{{ route('bookings.invoice', $b) }}" target="_blank" rel="noopener noreferrer" class="mt-3 inline-flex items-center gap-2 rounded-xl border border-brand-200 bg-brand-50 px-4 py-2.5 text-sm font-semibold text-brand-800 transition hover:bg-brand-100">
                                {{ __('bookings.show.print_invoice') }}
                                <svg class="h-4 w-4" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true"><path fill-rule="evenodd" d="M5 4v3H4a2 2 0 00-2 2v3a2 2 0 002 2h1v2a2 2 0 002 2h6a2 2 0 002-2v-2h1a2 2 0 002-2V9a2 2 0 00-2-2h-1V4a2 2 0 00-2-2H7a2 2 0 00-2 2zm8 0H7v3h6V4zm0 8H7v4h6v-4z" clip-rule="evenodd" /></svg>
                            </a>
                        @endif
                    </div>
                </div>
            @endif

            @if ($b->isBookingChatOpen() || ($st === BookingStatus::Completed && $b->isPaid()))
                @include('bookings.partials.booking-chat', [
                    'booking' => $b,
                    'fetchUrl' => route('bookings.chat.messages', $b),
                    'storeUrl' => route('bookings.chat.messages.store', $b),
                ])
            @endif

            @if ($st === BookingStatus::Confirmed && $b->isPaid())
                <div class="mt-8 space-y-6 rounded-3xl border border-slate-200/80 bg-white p-6 shadow-md shadow-slate-900/5 sm:p-8">
                    <div>
                        <h2 class="text-lg font-bold text-slate-900">{{ __('bookings.show.refund_reschedule_heading') }}</h2>
                        <p class="mt-2 text-xs leading-relaxed text-slate-600">
                            {!! __('bookings.show.refund_reschedule_intro_html', [
                                'refund_days' => BookingPostPayRules::refundMinDaysBeforeService(),
                                'reschedule_days' => BookingPostPayRules::rescheduleMinDaysBeforeService(),
                            ]) !!}
                        </p>
                    </div>

                    @if ($refundEligibilityError === null && $refundPreview)
                        <div class="rounded-2xl border border-slate-100 bg-slate-50/90 p-4 text-sm text-slate-700 ring-1 ring-slate-100 space-y-2">
                            <p>{!! __('bookings.show.refund_estimate_html', ['amount' => $fmt((float) $refundPreview['net_refund_customer'])]) !!}</p>
                            <p class="text-xs text-slate-600">{{ __('bookings.show.refund_breakdown_html', [
                                'paid' => $fmt((float) $refundPreview['customer_paid_amount']),
                                'platform' => $fmt((float) $refundPreview['refund_fee_platform']),
                                'muthowif' => $fmt((float) $refundPreview['refund_fee_muthowif']),
                            ]) }}</p>
                        </div>
                        <form method="POST" action="{{ route('bookings.refund_request.store', $b) }}" class="space-y-3" onsubmit="return confirm(@json(__('bookings.show.refund_confirm')));">
                            @csrf
                            <div>
                                <label for="refund_note" class="mb-1 block text-sm font-medium text-slate-700">{{ __('bookings.show.note_optional') }}</label>
                                <textarea id="refund_note" name="customer_note" rows="2" maxlength="2000" class="w-full rounded-xl border-slate-300 text-sm shadow-sm focus:border-brand-500 focus:ring-brand-500">{{ old('customer_note') }}</textarea>
                            </div>
                            <button type="submit" class="inline-flex items-center justify-center rounded-xl border border-slate-300 bg-white px-5 py-2.5 text-sm font-semibold text-slate-800 shadow-sm transition hover:bg-slate-50">
                                {{ __('bookings.show.process_refund') }}
                            </button>
                        </form>
                    @else
                        <p class="text-sm text-slate-600">{{ $refundEligibilityError }}</p>
                    @endif

                    @if ($b->pendingRescheduleRequest())
                        <div class="rounded-2xl border border-amber-200 bg-amber-50/80 px-4 py-3 text-sm text-amber-950 ring-1 ring-amber-200/60">
                            {{ __('bookings.show.reschedule_pending') }}
                        </div>
                    @elseif ($rescheduleEligibilityError === null)
                        <form method="POST" action="{{ route('bookings.reschedule_request.store', $b) }}" class="space-y-3 border-t border-slate-100 pt-6">
                            @csrf
                            <p class="text-sm font-semibold text-slate-800">{{ __('bookings.show.new_schedule', ['nights' => $nights]) }}</p>
                            <div
                                class="grid grid-cols-1 gap-3 sm:grid-cols-2"
                                x-data="{
                                    nights: {{ $nights }},
                                    endLabel: @json(__('common.em_dash')),
                                    dateLocale: @json($dateLocale),
                                    updateEnd() {
                                        const v = this.$refs.start?.value;
                                        if (!v) { this.endLabel = @json(__('common.em_dash')); return; }
                                        const d = new Date(v + 'T12:00:00');
                                        d.setDate(d.getDate() + (this.nights - 1));
                                        this.endLabel = d.toLocaleDateString(this.dateLocale, { day: '2-digit', month: 'long', year: 'numeric' });
                                    }
                                }"
                                x-init="$nextTick(() => updateEnd())"
                            >
                                <div>
                                    <label for="new_start_date" class="mb-1 block text-xs font-medium text-slate-600">{{ __('bookings.show.start_label') }}</label>
                                    <input
                                        type="date"
                                        id="new_start_date"
                                        name="new_start_date"
                                        x-ref="start"
                                        value="{{ old('new_start_date') }}"
                                        required
                                        class="w-full rounded-xl border-slate-300 text-sm shadow-sm focus:border-brand-500 focus:ring-brand-500"
                                        @input="updateEnd()"
                                        @change="updateEnd()"
                                    >
                                    @error('new_start_date')
                                        <p class="mt-1 text-xs text-red-700">{{ $message }}</p>
                                    @enderror
                                </div>
                                <div>
                                    <p class="mb-1 block text-xs font-medium text-slate-600">{{ __('bookings.show.end_auto') }}</p>
                                    <div class="flex min-h-[38px] items-center rounded-xl border border-slate-200 bg-slate-50 px-3 text-sm text-slate-800" x-text="endLabel"></div>
                                    <p class="mt-1 text-[11px] text-slate-500">{{ __('bookings.show.end_follows', ['nights' => $nights]) }}</p>
                                </div>
                            </div>
                            <div>
                                <label for="reschedule_note" class="mb-1 block text-xs font-medium text-slate-600">{{ __('bookings.show.note_optional') }}</label>
                                <textarea id="reschedule_note" name="reschedule_note" rows="2" maxlength="2000" class="w-full rounded-xl border-slate-300 text-sm shadow-sm focus:border-brand-500 focus:ring-brand-500">{{ old('reschedule_note') }}</textarea>
                                @error('reschedule_note')
                                    <p class="mt-1 text-xs text-red-700">{{ $message }}</p>
                                @enderror
                            </div>
                            <button type="submit" class="inline-flex items-center justify-center rounded-xl bg-brand-600 px-5 py-2.5 text-sm font-semibold text-white shadow-md shadow-brand-600/20 transition hover:bg-brand-700">
                                {{ __('bookings.show.submit_reschedule') }}
                            </button>
                        </form>
                    @else
                        <p class="border-t border-slate-100 pt-6 text-sm text-slate-600">{{ $rescheduleEligibilityError }}</p>
                    @endif

                    @if ($b->refundRequests->isNotEmpty() || $b->rescheduleRequests->isNotEmpty())
                        <div class="space-y-3 border-t border-slate-100 pt-4 text-xs text-slate-600">
                            @foreach ($b->refundRequests as $req)
                                <p class="rounded-lg bg-slate-50/80 px-3 py-2">
                                    {{ __('bookings.show.timeline_refund', ['status' => $req->status->label(), 'datetime' => $req->created_at?->timezone(config('app.timezone'))->format('d/m/Y H:i')]) }}
                                    @if ($req->customer_note)
                                        <br><span class="text-slate-500">{{ __('bookings.show.timeline_refund_note', ['note' => $req->customer_note]) }}</span>
                                    @endif
                                </p>
                            @endforeach
                            @foreach ($b->rescheduleRequests as $req)
                                <p class="rounded-lg bg-slate-50/80 px-3 py-2">
                                    {{ __('bookings.show.timeline_reschedule', [
                                        'status' => $req->status->label(),
                                        'range' => \Carbon\Carbon::parse($req->new_starts_on)->format('d/m/Y').' – '.\Carbon\Carbon::parse($req->new_ends_on)->format('d/m/Y'),
                                        'datetime' => $req->created_at?->timezone(config('app.timezone'))->format('d/m/Y H:i'),
                                    ]) }}
                                    @if ($req->muthowif_note)
                                        <br><span class="text-slate-500">{{ __('bookings.show.timeline_muthowif_note', ['note' => $req->muthowif_note]) }}</span>
                                    @endif
                                </p>
                            @endforeach
                        </div>
                    @endif
                </div>
            @endif

            @if ($st === BookingStatus::Cancelled && $b->isRefundPending())
                @php $pend = $b->pendingRefundRequest(); @endphp
                <div class="mt-8 rounded-3xl border border-amber-200 bg-gradient-to-br from-amber-50 to-amber-50/40 p-6 text-sm text-amber-950 shadow-md shadow-amber-900/5 ring-1 ring-amber-200/60">
                    <p class="font-bold">{{ __('bookings.show.refund_pending_title') }}</p>
                    <p class="mt-2 leading-relaxed">
                        {!! __('bookings.show.refund_pending_body_html', ['amount' => $pend ? $fmt((float) $pend->net_refund_customer) : __('common.em_dash')]) !!}
                    </p>
                </div>
            @endif

            @if ($st === BookingStatus::Cancelled && $b->isRefunded())
                <div class="mt-8 rounded-3xl border border-slate-200 bg-slate-50 p-6 text-sm text-slate-700 shadow-sm">
                    {{ __('bookings.show.refunded_done') }}
                </div>
            @endif

            @if ($st === BookingStatus::Confirmed && $b->payment_status === PaymentStatus::Paid)
                <div class="mt-8 overflow-hidden rounded-3xl border border-brand-200 bg-gradient-to-br from-brand-50/90 to-white p-6 shadow-md shadow-brand-900/5 ring-1 ring-brand-200/50 sm:p-8">
                    <h2 class="text-lg font-bold text-slate-900">{{ __('bookings.show.complete_service_heading') }}</h2>
                    <p class="mt-1 text-sm text-slate-600">
                        {{ __('bookings.show.complete_service_intro') }}
                    </p>

                    <form method="POST" action="{{ route('bookings.complete', $b) }}" class="mt-5 space-y-4" onsubmit="return confirm(@json(__('bookings.show.complete_confirm')));">
                        @csrf
                        <div>
                            <label class="mb-2 block text-sm font-medium text-slate-700">{{ __('bookings.show.rating_required') }} <span class="text-red-600">*</span></label>
                            <div class="flex flex-wrap gap-3">
                                @for ($i = 1; $i <= 5; $i++)
                                    <label class="inline-flex cursor-pointer items-center gap-2 rounded-lg border border-slate-200 bg-white px-3 py-2 text-sm text-slate-700 shadow-sm transition hover:border-brand-300 hover:bg-brand-50/50">
                                        <input type="radio" name="rating" value="{{ $i }}" class="border-slate-300 text-brand-600 focus:ring-brand-500" @checked((int) old('rating', 5) === $i) required>
                                        <span>{{ $i }} ★</span>
                                    </label>
                                @endfor
                            </div>
                            @error('rating')
                                <p class="mt-1 text-xs text-red-700">{{ $message }}</p>
                            @enderror
                        </div>

                        <div>
                            <label for="complete_review" class="mb-2 block text-sm font-medium text-slate-700">{{ __('bookings.show.review_optional') }}</label>
                            <textarea id="complete_review" name="review" rows="4" maxlength="2000" class="w-full rounded-xl border-slate-300 text-sm shadow-sm focus:border-brand-500 focus:ring-brand-500" placeholder="{{ __('bookings.show.review_placeholder') }}">{{ old('review') }}</textarea>
                            @error('review')
                                <p class="mt-1 text-xs text-red-700">{{ $message }}</p>
                            @enderror
                        </div>

                        <button type="submit" class="inline-flex items-center justify-center rounded-xl bg-emerald-600 px-5 py-2.5 text-sm font-semibold text-white shadow-md shadow-emerald-600/20 transition hover:bg-emerald-700">
                            {{ __('bookings.show.complete_submit') }}
                        </button>
                    </form>
                </div>
            @endif

            @if ($st === BookingStatus::Pending || $b->isAwaitingPayment())
                <form method="POST" action="{{ route('bookings.cancel', $b) }}" class="mt-8 rounded-3xl border border-red-200/80 bg-gradient-to-br from-red-50/90 to-white p-5 shadow-sm ring-1 ring-red-100/80" onsubmit="return confirm(@json(__('bookings.show.cancel_booking_confirm')));">
                    @csrf
                    <p class="text-sm font-bold text-red-900">{{ __('bookings.show.cancel_section_title') }}</p>
                    <p class="mt-1 text-xs text-red-800/90">{{ __('bookings.show.cancel_section_hint') }}</p>
                    <button type="submit" class="mt-4 rounded-xl border border-red-200 bg-white px-4 py-2.5 text-sm font-semibold text-red-700 shadow-sm transition hover:bg-red-50">
                        {{ __('bookings.show.cancel_yes') }}
                    </button>
                </form>
            @endif

            @if ($st === BookingStatus::Completed)
                <div class="mt-8 rounded-3xl border border-slate-200/80 bg-white p-6 shadow-md shadow-slate-900/5 sm:p-8">
                    <h2 class="text-lg font-bold text-slate-900">{{ __('bookings.show.completed_rating_heading') }}</h2>
                    <p class="mt-1 text-sm text-slate-600">{{ __('bookings.show.completed_rating_intro') }}</p>

                    <form method="POST" action="{{ route('bookings.review', $b) }}" class="mt-5 space-y-4">
                        @csrf
                        <div>
                            <label class="mb-2 block text-sm font-medium text-slate-700">{{ __('bookings.show.rating_required') }}</label>
                            <div class="flex flex-wrap gap-3">
                                @for ($i = 1; $i <= 5; $i++)
                                    <label class="inline-flex cursor-pointer items-center gap-2 rounded-lg border border-slate-200 bg-white px-3 py-2 text-sm text-slate-700 shadow-sm transition hover:border-brand-300 hover:bg-brand-50/50">
                                        <input type="radio" name="rating" value="{{ $i }}" class="border-slate-300 text-brand-600 focus:ring-brand-500" @checked((int) old('rating', $review?->rating ?? 5) === $i)>
                                        <span>{{ $i }} ★</span>
                                    </label>
                                @endfor
                            </div>
                            @error('rating')
                                <p class="mt-1 text-xs text-red-700">{{ $message }}</p>
                            @enderror
                        </div>

                        <div>
                            <label for="review" class="mb-2 block text-sm font-medium text-slate-700">{{ __('bookings.show.review_label') }}</label>
                            <textarea id="review" name="review" rows="4" maxlength="2000" class="w-full rounded-xl border-slate-300 text-sm shadow-sm focus:border-brand-500 focus:ring-brand-500" placeholder="{{ __('bookings.show.review_placeholder_edit') }}">{{ old('review', $review?->review) }}</textarea>
                            @error('review')
                                <p class="mt-1 text-xs text-red-700">{{ $message }}</p>
                            @enderror
                        </div>

                        <button type="submit" class="inline-flex items-center justify-center rounded-xl bg-brand-600 px-5 py-2.5 text-sm font-semibold text-white shadow-md shadow-brand-600/20 transition hover:bg-brand-700">
                            {{ $review ? __('bookings.show.update_review') : __('bookings.show.submit_review') }}
                        </button>
                    </form>
                </div>
            @endif
        </div>
    </div>

    @if ($b->isAwaitingPayment())
        <script>
            setInterval(() => {
                window.location.reload();
            }, 10000);
        </script>
    @endif
</x-app-layout>
