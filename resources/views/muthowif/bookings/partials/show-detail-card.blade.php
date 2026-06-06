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
    $initial = mb_strtoupper(mb_substr((string) ($b->customer?->name ?? '?'), 0, 1));

    $statusBadge = match ($st) {
        BookingStatus::Cancelled => 'bg-red-50 text-red-800 ring-red-200/90',
        BookingStatus::Confirmed => 'bg-emerald-50 text-emerald-900 ring-emerald-200/80',
        BookingStatus::Completed => 'bg-brand-50 text-brand-900 ring-brand-200/80',
        BookingStatus::Pending => 'bg-amber-50 text-amber-950 ring-amber-200/80',
        default => 'bg-slate-100 text-slate-800 ring-slate-200/80',
    };
@endphp

<x-ui.card class="overflow-hidden p-0">
    <div class="border-b border-slate-100 px-5 py-4 sm:px-6">
        <h2 class="flex items-center gap-2 text-base font-bold text-slate-900">
            <span class="flex h-8 w-8 items-center justify-center rounded-lg bg-brand-50 text-brand-700">
                <svg class="h-4 w-4" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
                    <path fill-rule="evenodd" d="M10 9a3 3 0 100-6 3 3 0 000 6zm-7 9a7 7 0 1114 0h-1.79A5.21 5.21 0 0010 14.79 5.21 5.21 0 005.79 18H3z" clip-rule="evenodd" />
                </svg>
            </span>
            {{ __('muthowif.booking_show.page_title') }}
        </h2>
    </div>

    <div class="ui-card-pad">
        <div class="flex flex-col gap-5 lg:flex-row lg:items-start lg:gap-6">
            <div class="flex min-w-0 flex-1 gap-4">
                <span class="flex h-16 w-16 shrink-0 items-center justify-center rounded-full bg-brand-700 text-xl font-bold text-white shadow-md ring-2 ring-white sm:h-[4.5rem] sm:w-[4.5rem]">
                    {{ $initial }}
                </span>
                <div class="min-w-0 flex-1">
                    <p class="text-lg font-bold text-slate-900">{{ $b->customer?->name ?? '—' }}</p>
                    @if (filled($b->customer?->email))
                        <p class="mt-0.5 text-sm text-slate-600">{{ $b->customer->email }}</p>
                    @endif

                    @if (filled($b->booking_code))
                        <div class="mt-3 flex flex-wrap items-center gap-2">
                            <p class="text-[0.65rem] font-semibold uppercase tracking-wide text-slate-500">
                                {{ __('muthowif.booking_show.booking_code_label') }}
                                <span class="ml-1 font-mono text-sm normal-case tracking-tight text-slate-900" id="muthowif-booking-code-value">{{ $b->booking_code }}</span>
                            </p>
                            <button
                                type="button"
                                class="inline-flex h-7 w-7 items-center justify-center rounded-md text-slate-500 transition hover:bg-slate-100 hover:text-brand-700"
                                title="{{ __('bookings.show.copy_code') }}"
                                onclick="navigator.clipboard?.writeText(document.getElementById('muthowif-booking-code-value')?.textContent?.trim() || '')"
                            >
                                <svg class="h-4 w-4" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
                                    <path d="M7 3.5A1.5 1.5 0 018.5 2h3.879a1.5 1.5 0 011.06.44l3.122 3.12A1.5 1.5 0 0117 6.622V12.5a1.5 1.5 0 01-1.5 1.5h-1v-3.879a1.5 1.5 0 00-.44-1.06L10.939 5.44A1.5 1.5 0 009.879 5H7v11.5A1.5 1.5 0 005.5 17.5h-2A1.5 1.5 0 012 16V4.5A1.5 1.5 0 013.5 3h2.879A1.5 1.5 0 017 3.5z" />
                                </svg>
                            </button>
                        </div>
                    @endif

                    <div class="mt-2 flex flex-wrap gap-2">
                        <span class="inline-flex items-center rounded-full px-2.5 py-0.5 text-xs font-semibold ring-1 {{ $statusBadge }}">
                            {{ $st->label() }}
                        </span>
                        @if (in_array($st, [BookingStatus::Confirmed, BookingStatus::Completed], true))
                            <span class="inline-flex items-center rounded-full px-2.5 py-0.5 text-xs font-semibold ring-1 {{ match ($b->payment_status) {
                                PaymentStatus::Paid => 'bg-emerald-50 text-emerald-900 ring-emerald-200/80',
                                PaymentStatus::Refunded => 'bg-red-50 text-red-800 ring-red-200/80',
                                default => 'bg-orange-50 text-orange-950 ring-orange-200/80',
                            } }}">
                                {{ __('muthowif.bookings.payment_prefix') }} {{ $b->payment_status->label() }}
                            </span>
                        @endif
                    </div>
                </div>
            </div>

            <div class="grid w-full shrink-0 grid-cols-1 gap-3 sm:grid-cols-2 lg:w-[min(100%,22rem)] lg:grid-cols-1 xl:grid-cols-2">
                <div class="rounded-xl border border-slate-100 bg-slate-50/80 p-3.5">
                    <p class="text-[0.65rem] font-semibold uppercase tracking-wide text-slate-500">{{ __('muthowif.booking_show.period') }}</p>
                    <p class="mt-1 text-sm font-semibold tabular-nums text-slate-900">
                        {{ $fmtDate($b->starts_on) }}
                        <span class="font-normal text-slate-400">–</span>
                        {{ $fmtDate($b->ends_on) }}
                    </p>
                    <p class="mt-0.5 text-xs text-slate-600">
                        ({{ __('bookings.show.period_duration_line', ['days' => $nights, 'nights' => $sleepNights]) }})
                    </p>
                </div>
                <div class="rounded-xl border border-slate-100 bg-slate-50/80 p-3.5">
                    <p class="text-[0.65rem] font-semibold uppercase tracking-wide text-slate-500">{{ __('muthowif.booking_show.service') }}</p>
                    <p class="mt-1 text-sm font-semibold text-slate-900">{{ $b->service_type?->label() ?? '—' }}</p>
                    <p class="mt-0.5 text-xs text-slate-600">{{ __('muthowif.booking_show.pilgrim_count', ['count' => $b->pilgrim_count]) }}</p>
                </div>
            </div>
        </div>
    </div>
</x-ui.card>
