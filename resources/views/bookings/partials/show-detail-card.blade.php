@php
    use App\Enums\BookingStatus;
    use App\Enums\PaymentStatus;
    use Carbon\Carbon;

    $b = $booking;
    $st = $b->status;
    $nights = $b->billingNightsInclusive();
    $sleepNights = max(0, $nights - 1);
    $dateLocale = app()->getLocale() === 'id' ? 'id-ID' : 'en-GB';
    $fmtDate = fn ($d) => Carbon::parse($d)->locale($dateLocale)->translatedFormat('d M Y');

    $statusBadge = match ($st) {
        BookingStatus::Cancelled => 'bg-red-50 text-red-800 ring-red-200/80',
        BookingStatus::Confirmed => 'bg-emerald-50 text-emerald-900 ring-emerald-200/80',
        BookingStatus::Completed => 'bg-brand-50 text-brand-900 ring-brand-200/80',
        BookingStatus::Pending => 'bg-amber-50 text-amber-950 ring-amber-200/80',
        default => 'bg-slate-100 text-slate-800 ring-slate-200/80',
    };
@endphp

<article class="overflow-hidden rounded-2xl border border-slate-200/90 bg-white shadow-sm ring-1 ring-slate-100/80">
    <div class="border-b border-slate-100 px-5 py-4 sm:px-6">
        <h2 class="flex items-center gap-2 text-base font-bold text-slate-900">
            <span class="flex h-8 w-8 items-center justify-center rounded-lg bg-brand-50 text-brand-700">
                <svg class="h-4 w-4" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
                    <path fill-rule="evenodd" d="M4.75 2a.75.75 0 01.75.75V4h7V2.75a.75.75 0 011.5 0V4h.25A2.75 2.75 0 0118 6.75v8.5A2.75 2.75 0 0115.25 18H4.75A2.75 2.75 0 012 15.25v-8.5A2.75 2.75 0 014.75 4H5V2.75A.75.75 0 014.75 2zm-1 5.5c-.69 0-1.25.56-1.25 1.25v6.5c0 .69.56 1.25 1.25 1.25h10.5c.69 0 1.25-.56 1.25-1.25v-6.5c0-.69-.56-1.25-1.25-1.25H4.75z" clip-rule="evenodd" />
                </svg>
            </span>
            {{ __('bookings.show.detail_kicker') }}
        </h2>
    </div>

    <div class="p-5 sm:p-6">
        <div class="flex flex-col gap-5 sm:flex-row sm:items-start sm:justify-between">
            <div class="flex min-w-0 gap-4">
                <img
                    src="{{ route('layanan.photo', $b->muthowifProfile) }}"
                    alt="{{ __('bookings.index.photo_alt', ['name' => $b->muthowifProfile->user->name]) }}"
                    class="h-16 w-16 shrink-0 rounded-2xl object-cover ring-2 ring-white shadow-md sm:h-20 sm:w-20"
                    loading="lazy"
                >
                <div class="min-w-0 flex-1">
                    <p class="text-lg font-bold text-slate-900 sm:text-xl">{{ $b->muthowifProfile->user->name }}</p>
                    <a
                        href="{{ route('layanan.show', $b->muthowifProfile) }}"
                        class="mt-1 inline-flex items-center gap-1 text-sm font-semibold text-brand-700 hover:text-brand-800"
                    >
                        {{ __('marketplace.card.view_profile') }}
                        <svg class="h-3.5 w-3.5" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
                            <path fill-rule="evenodd" d="M3 10a.75.75 0 01.75-.75h10.638L10.23 5.29a.75.75 0 111.04-1.08l5.5 5.25a.75.75 0 010 1.08l-5.5 5.25a.75.75 0 11-1.04-1.08l4.158-3.96H3.75A.75.75 0 013 10z" clip-rule="evenodd" />
                        </svg>
                    </a>

                    @if (filled($b->booking_code))
                        <div class="mt-3 flex flex-wrap items-center gap-2">
                            <div class="rounded-lg border border-slate-100 bg-slate-50/90 px-2.5 py-1.5">
                                <p class="text-[0.65rem] font-semibold uppercase tracking-wide text-slate-500">{{ __('bookings.show.booking_code') }}</p>
                                <p class="font-mono text-sm font-semibold text-slate-900" id="booking-code-value">{{ $b->booking_code }}</p>
                            </div>
                            <button
                                type="button"
                                class="inline-flex h-9 w-9 items-center justify-center rounded-lg border border-slate-200 bg-white text-slate-600 shadow-sm transition hover:border-brand-200 hover:text-brand-700"
                                title="{{ __('bookings.show.copy_code') }}"
                                data-copy-target="booking-code-value"
                                onclick="navigator.clipboard?.writeText(document.getElementById('booking-code-value')?.textContent?.trim() || ''); this.dataset.copied='1';"
                            >
                                <svg class="h-4 w-4" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
                                    <path d="M7 3.5A1.5 1.5 0 018.5 2h3.879a1.5 1.5 0 011.06.44l3.122 3.12A1.5 1.5 0 0117 6.622V12.5a1.5 1.5 0 01-1.5 1.5h-1v-3.879a1.5 1.5 0 00-.44-1.06L10.939 5.44A1.5 1.5 0 009.879 5H7v11.5A1.5 1.5 0 005.5 17.5h-2A1.5 1.5 0 012 16V4.5A1.5 1.5 0 013.5 3h2.879A1.5 1.5 0 017 3.5z" />
                                </svg>
                            </button>
                        </div>
                    @endif

                    <div class="mt-3 flex flex-wrap gap-2">
                        <span class="inline-flex items-center rounded-full px-2.5 py-0.5 text-xs font-semibold ring-1 {{ $statusBadge }}">
                            {{ $st->label() }}
                        </span>
                        @if ($b->payment_status !== PaymentStatus::Pending)
                            <span class="inline-flex items-center rounded-full px-2.5 py-0.5 text-xs font-semibold ring-1 {{ match ($b->payment_status) {
                                PaymentStatus::Paid => 'bg-emerald-50 text-emerald-900 ring-emerald-200/80',
                                PaymentStatus::RefundPending => 'bg-amber-50 text-amber-950 ring-amber-200/80',
                                PaymentStatus::Refunded => 'bg-red-50 text-red-800 ring-red-200/80',
                                default => 'bg-orange-50 text-orange-950 ring-orange-200/80',
                            } }}">
                                {{ $b->payment_status->label() }}
                            </span>
                        @endif
                    </div>
                </div>
            </div>
        </div>

        <div class="mt-6 grid grid-cols-1 gap-3 sm:grid-cols-2">
            <div class="rounded-xl border border-slate-100 bg-slate-50/60 p-4">
                <p class="flex items-center gap-1.5 text-[0.65rem] font-semibold uppercase tracking-wide text-slate-500">
                    <svg class="h-3.5 w-3.5 text-brand-600" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
                        <path fill-rule="evenodd" d="M4.75 2a.75.75 0 01.75.75V4h7V2.75a.75.75 0 011.5 0V4h.25A2.75 2.75 0 0118 6.75v8.5A2.75 2.75 0 0115.25 18H4.75A2.75 2.75 0 012 15.25v-8.5A2.75 2.75 0 014.75 4H5V2.75A.75.75 0 014.75 2zm-1 5.5c-.69 0-1.25.56-1.25 1.25v6.5c0 .69.56 1.25 1.25 1.25h10.5c.69 0 1.25-.56 1.25-1.25v-6.5c0-.69-.56-1.25-1.25-1.25H4.75z" clip-rule="evenodd" />
                    </svg>
                    {{ __('bookings.show.period') }}
                </p>
                <p class="mt-1.5 text-sm font-semibold tabular-nums text-slate-900">
                    {{ $fmtDate($b->starts_on) }}
                    <span class="mx-1 font-normal text-slate-400">–</span>
                    {{ $fmtDate($b->ends_on) }}
                </p>
                <p class="mt-0.5 text-xs text-slate-600">
                    {{ __('bookings.show.period_duration_line', ['days' => $nights, 'nights' => $sleepNights]) }}
                </p>
            </div>
            <div class="rounded-xl border border-slate-100 bg-slate-50/60 p-4">
                <p class="flex items-center gap-1.5 text-[0.65rem] font-semibold uppercase tracking-wide text-slate-500">
                    <svg class="h-3.5 w-3.5 text-brand-600" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
                        <path d="M10 8.25a.75.75 0 01.75.75v1.5a.75.75 0 01-1.5 0v-1.5A.75.75 0 0110 8.25zM10 6a1 1 0 100-2 1 1 0 000 2zm-3.75 2.25a.75.75 0 00-1.5 0v4.5a.75.75 0 001.5 0v-4.5zm7.5 0a.75.75 0 00-1.5 0v4.5a.75.75 0 001.5 0v-4.5z" />
                        <path fill-rule="evenodd" d="M3.5 3A1.5 1.5 0 002 4.5v11A1.5 1.5 0 003.5 17h13a1.5 1.5 0 001.5-1.5v-11A1.5 1.5 0 0016.5 3h-13zm0 1.5h13v11h-13v-11z" clip-rule="evenodd" />
                    </svg>
                    {{ __('bookings.show.service') }}
                </p>
                <p class="mt-1.5 text-sm font-semibold text-slate-900">{{ $b->service_type?->label() ?? '—' }}</p>
                <p class="mt-0.5 text-xs text-slate-600">{{ __('bookings.index.pilgrims_count', ['count' => $b->pilgrim_count, 'pilgrims_word' => __('common.pilgrims')]) }}</p>
            </div>
        </div>
    </div>
</article>
