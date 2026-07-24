@php
    use App\Enums\BookingStatus;
    use App\Enums\PaymentStatus;

    /** @var callable(float): string $fmt */
    $b = $booking;
    $st = $b->status;

    $statusBadge = match ($st) {
        BookingStatus::Cancelled => 'bg-red-50 text-red-800 ring-red-200/90',
        BookingStatus::Confirmed => 'bg-emerald-50 text-emerald-900 ring-emerald-200/80',
        BookingStatus::Completed => 'bg-brand-50 text-brand-900 ring-brand-200/80',
        BookingStatus::Pending => 'bg-amber-50 text-amber-950 ring-amber-200/80',
        default => 'bg-slate-100 text-slate-800 ring-slate-200/80',
    };
@endphp

<aside class="flex flex-col gap-6 lg:sticky lg:top-24 lg:self-start">
    <section class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm sm:p-6">
        <div class="flex items-start justify-between gap-3">
            <h2 class="text-sm font-bold text-slate-900">{{ __('bookings.show.status_card_title') }}</h2>
            <span class="inline-flex shrink-0 items-center rounded-full px-2.5 py-0.5 text-xs font-semibold ring-1 {{ $statusBadge }}">
                {{ $st->label() }}
            </span>
        </div>

        <p class="mt-3 text-sm leading-relaxed text-slate-600">
            @if ($st === BookingStatus::Pending)
                {{ __('muthowif.booking_show.status_pending') }}
            @elseif ($st === BookingStatus::Confirmed && $b->payment_status === PaymentStatus::Pending)
                {{ __('muthowif.booking_show.status_awaiting_payment') }}
            @elseif ($st === BookingStatus::Confirmed && $b->isPaid())
                {{ __('muthowif.booking_show.status_confirmed_paid') }}
            @elseif ($st === BookingStatus::Completed)
                {{ __('muthowif.booking_show.status_completed') }}
            @elseif ($st === BookingStatus::Cancelled)
                {{ __('muthowif.booking_show.status_cancelled') }}
            @else
                {{ __('muthowif.booking_show.page_subtitle') }}
            @endif
        </p>

        @if ($b->isPaid() || $b->paid_at)
            <p class="mt-2 text-xs text-slate-500">
                {{ __('bookings.show.paid_at', ['datetime' => $b->paid_at?->timezone(config('app.timezone'))->format('d/m/Y H:i') ?? '—']) }}
            </p>
        @endif
    </section>

    <section class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm sm:p-6">
        <h2 class="text-sm font-bold text-slate-900">{{ __('muthowif.booking_show.payment_heading') }}</h2>
        <dl class="mt-4 space-y-2 text-xs">
            @if ($b->isSupport())
                <div class="flex justify-between gap-3">
                    <dt class="text-slate-600">{{ __('layanan_pendukung.package_price') }}</dt>
                    <dd class="font-medium tabular-nums text-slate-900">Rp {{ $fmt($serviceSubtotal) }}</dd>
                </div>
            @else
                <div class="flex justify-between gap-3">
                    <dt class="text-slate-600">{{ __('muthowif.booking_show.rate_per_day') }}</dt>
                    <dd class="font-medium tabular-nums text-slate-900">Rp {{ $fmt($daily) }}</dd>
                </div>
                <div class="flex justify-between gap-3">
                    <dt class="text-slate-600">{{ __('bookings.show.day_count') }}</dt>
                    <dd class="font-medium text-slate-900">{{ __('bookings.show.days_count', ['count' => $nights]) }}</dd>
                </div>
                <div class="flex justify-between gap-3 border-t border-slate-100 pt-2">
                    <dt class="text-slate-600">{{ __('muthowif.booking_show.subtotal_service') }}</dt>
                    <dd class="font-medium tabular-nums text-slate-900">Rp {{ $fmt($serviceSubtotal) }}</dd>
                </div>
            @endif
            @if ($addonLines->isNotEmpty())
                @foreach ($addonLines as $ad)
                    <div class="flex justify-between gap-3">
                        <dt class="text-slate-500">+ {{ $ad->name }}</dt>
                        <dd class="font-medium tabular-nums text-slate-800">Rp {{ $fmt((float) $ad->price) }}</dd>
                    </div>
                @endforeach
            @endif
            @if ($sameHotelLine > 0)
                <div class="flex justify-between gap-3">
                    <dt class="text-slate-500">{{ __('bookings.show.same_hotel_label', ['nights' => $nights, 'days' => __('common.days')]) }}</dt>
                    <dd class="font-medium tabular-nums text-slate-800">Rp {{ $fmt($sameHotelLine) }}</dd>
                </div>
            @endif
            @if ($transportLine > 0)
                <div class="flex justify-between gap-3">
                    <dt class="text-slate-500">{{ __('bookings.show.transport_label') }}</dt>
                    <dd class="font-medium tabular-nums text-slate-800">Rp {{ $fmt($transportLine) }}</dd>
                </div>
            @endif
            <div class="flex justify-between gap-3 border-t border-slate-100 pt-2">
                <dt class="text-red-600/90">{{ __('muthowif.booking_show.platform_fee_muthowif') }}</dt>
                <dd class="font-medium tabular-nums text-red-700">- Rp {{ $fmt($muthowifFee) }}</dd>
            </div>
            @if ($referralRewardFromPay > 0)
                <div class="flex justify-between gap-3">
                    <dt class="text-violet-700/90">{{ __('muthowif.booking_show.peer_referral_deduction') }}</dt>
                    <dd class="font-medium tabular-nums text-violet-800">- Rp {{ $fmt($referralRewardFromPay) }}</dd>
                </div>
            @endif
            <div class="flex justify-between gap-3 border-t border-slate-200 pt-3 text-sm">
                <dt class="font-bold text-slate-900">{{ __('muthowif.booking_show.net_earning') }}</dt>
                <dd class="font-bold tabular-nums text-brand-700">Rp {{ $fmt($muthowifNetAfterReferral) }}</dd>
            </div>
        </dl>
        <p class="mt-3 text-[10px] leading-relaxed text-slate-500 italic">
            * {{ __('bookings.invoice.gateway_fee_note') }}
        </p>
    </section>
</aside>
