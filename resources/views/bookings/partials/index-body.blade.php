@php
    use App\Enums\BookingStatus;
    use App\Enums\MuthowifServiceType;
    use App\Enums\PaymentStatus;
    use App\Support\IndonesianNumber;
    use Carbon\Carbon;

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
            'bar' => 'from-red-300 via-red-400 to-red-500',
            'glow' => 'shadow-red-500/10',
            'badge' => 'bg-red-100 text-red-800 ring-red-200/80',
        ],
    ];
@endphp
        <div class="relative z-10 mx-auto max-w-5xl px-4 pb-16 pt-10 sm:px-6 lg:px-8">
            {{-- Hero --}}
            <div class="relative overflow-hidden rounded-3xl border border-white/60 bg-white/90 p-6 shadow-xl shadow-slate-900/5 backdrop-blur-sm sm:p-8">
                <div class="absolute -right-16 -top-16 h-48 w-48 rounded-full bg-gradient-to-br from-brand-200/50 to-transparent blur-2xl"></div>
                <div class="relative flex flex-col gap-6 lg:flex-row lg:items-end lg:justify-between">
                    <div class="max-w-2xl space-y-3">
                        <p class="inline-flex items-center gap-2 rounded-full bg-slate-900/5 px-3 py-1 text-xs font-semibold uppercase tracking-wider text-slate-600 ring-1 ring-slate-900/5">
                            <svg class="h-3.5 w-3.5 text-brand-600" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
                                <path fill-rule="evenodd" d="M5.75 2a.75.75 0 01.75.75V4h7V2.75a.75.75 0 011.5 0V4h.25A2.75 2.75 0 0118 6.75v8.5A2.75 2.75 0 0115.25 18H4.75A2.75 2.75 0 012 15.25v-8.5A2.75 2.75 0 014.75 4H5V2.75A.75.75 0 015.75 2zm-1 5.5c-.69 0-1.25.56-1.25 1.25v6.5c0 .69.56 1.25 1.25 1.25h10.5c.69 0 1.25-.56 1.25-1.25v-6.5c0-.69-.56-1.25-1.25-1.25H4.75z" clip-rule="evenodd" />
                            </svg>
                            {{ __('bookings.index_page.kicker') }}
                        </p>
                        <h1 class="text-2xl font-bold tracking-tight text-slate-900 sm:text-3xl">
                            {{ __('layanan.customer_bookings_heading') }}
                        </h1>
                        <p class="text-sm leading-relaxed text-slate-600 sm:text-base">
                            {!! __('layanan.customer_bookings_intro') !!}
                        </p>

                        @if ($bookings->total() > 0)
                            <div class="flex flex-wrap items-center gap-2 pt-1">
                                <span class="inline-flex items-center gap-1.5 rounded-full bg-slate-900 px-3 py-1 text-xs font-semibold text-white shadow-md shadow-slate-900/20">
                                    <span class="tabular-nums">{{ __('bookings.index_page.bookings_total', ['count' => $bookings->total()]) }}</span>
                                </span>
                                <div class="flex flex-wrap gap-2" role="status" aria-label="{{ __('bookings.index_page.stats_aria') }}">
                                    @foreach (BookingStatus::cases() as $bs)
                                        @if (($bookingStatusCounts[$bs->value] ?? 0) > 0)
                                            <span class="inline-flex items-center rounded-full bg-white px-2.5 py-0.5 text-xs font-medium text-slate-700 ring-1 ring-slate-200/80">
                                                <span class="max-w-[10rem] truncate">{{ $bs->label() }}</span>
                                                <span class="ml-1.5 tabular-nums text-slate-500">{{ $bookingStatusCounts[$bs->value] }}</span>
                                            </span>
                                        @endif
                                    @endforeach
                                </div>
                            </div>
                        @endif
                    </div>

                    <div class="flex shrink-0 flex-col gap-3 sm:flex-row sm:items-center lg:flex-col lg:items-stretch">
                        <a
                            href="{{ route('layanan.index') }}"
                            class="group inline-flex items-center justify-center gap-2 rounded-2xl bg-gradient-to-r from-brand-600 to-brand-700 px-5 py-3 text-sm font-semibold text-white shadow-lg shadow-brand-600/25 transition hover:from-brand-500 hover:to-brand-600 hover:shadow-xl hover:shadow-brand-600/30 focus:outline-none focus:ring-2 focus:ring-brand-500 focus:ring-offset-2"
                        >
                            <svg class="h-5 w-5 opacity-90 transition group-hover:translate-x-0.5" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
                                <path fill-rule="evenodd" d="M9 3.75a.75.75 0 01.75-.75h2.5a.75.75 0 010 1.5h-.78l3.5 3.5a.75.75 0 11-1.06 1.06l-3.5-3.5v.78a.75.75 0 01-1.5 0v-2.5z" clip-rule="evenodd" />
                                <path d="M4.75 9.75a.75.75 0 00-1.5 0v6.5A2.75 2.75 0 006.5 19h7a2.75 2.75 0 002.75-2.75v-6.5a.75.75 0 00-1.5 0v6.5c0 .69-.56 1.25-1.25 1.25h-7c-.69 0-1.25-.56-1.25-1.25v-6.5z" />
                            </svg>
                            {{ __('layanan.booking_search_again') }}
                        </a>
                    </div>
                </div>
            </div>

            @if ($bookings->isEmpty())
                <div class="mt-10 flex flex-col items-center justify-center rounded-3xl border-2 border-dashed border-slate-200/80 bg-white/70 px-6 py-16 text-center shadow-inner shadow-slate-900/5 backdrop-blur-sm sm:px-12">
                    <div class="flex h-16 w-16 items-center justify-center rounded-2xl bg-gradient-to-br from-brand-100 to-brand-50 ring-1 ring-brand-200/60">
                        <svg class="h-8 w-8 text-brand-700" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" aria-hidden="true">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M6.75 3v2.25M17.25 3v2.25M3 18.75V7.5a2.25 2.25 0 012.25-2.25h13.5A2.25 2.25 0 0121 7.5v11.25m-18 0A2.25 2.25 0 005.25 21h13.5a2.25 2.25 0 002.25-2.25m-18 0v-7.5A2.25 2.25 0 015.25 9h13.5a2.25 2.25 0 012.25 2.25v7.5" />
                        </svg>
                    </div>
                    <h2 class="mt-6 text-lg font-semibold text-slate-900">{{ __('bookings.index_page.empty_title') }}</h2>
                    <p class="mt-2 max-w-md text-sm text-slate-600">{{ __('bookings.index_page.empty_lead') }}</p>
                    <a
                        href="{{ route('layanan.index') }}"
                        class="mt-8 inline-flex items-center gap-2 rounded-2xl bg-slate-900 px-5 py-2.5 text-sm font-semibold text-white shadow-lg shadow-slate-900/20 transition hover:bg-slate-800"
                    >
                        {{ __('bookings.index_page.empty_cta') }}
                        <svg class="h-4 w-4" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
                            <path fill-rule="evenodd" d="M3 10a.75.75 0 01.75-.75h10.638L10.23 5.29a.75.75 0 111.04-1.08l5.5 5.25a.75.75 0 010 1.08l-5.5 5.25a.75.75 0 11-1.04-1.08l4.158-3.96H3.75A.75.75 0 013 10z" clip-rule="evenodd" />
                        </svg>
                    </a>
                </div>
            @else
                <ul class="mt-10 space-y-6">
                    @foreach ($bookings as $booking)
                        @php
                            $st = $booking->status;
                            $nights = $booking->billingNightsInclusive();
                            $service = $booking->muthowifProfile?->services?->firstWhere('type', $booking->service_type);
                            $sameHotelLine = 0.0;
                            if ($booking->with_same_hotel && $service && $service->same_hotel_price_per_day !== null) {
                                $sameHotelLine = $nights * (float) $service->same_hotel_price_per_day;
                            }
                            $transportLine = 0.0;
                            if ($booking->with_transport && $service && $service->transport_price_flat !== null) {
                                $transportLine = (float) $service->transport_price_flat;
                            }
                            $cardStyle = $statusCardStyles[$st->value] ?? $statusCardStyles[BookingStatus::Pending->value];
                        @endphp
                        <li class="group relative">
                            <div class="absolute -inset-px rounded-[1.35rem] bg-gradient-to-br from-white to-slate-100/80 opacity-0 blur transition duration-300 group-hover:opacity-100"></div>
                            <article
                                class="relative overflow-hidden rounded-3xl border border-slate-200/80 bg-white shadow-md shadow-slate-900/5 transition duration-300 hover:-translate-y-0.5 hover:border-slate-300/80 hover:shadow-lg hover:shadow-slate-900/10 {{ $cardStyle['glow'] }}"
                            >
                                <div class="flex flex-col lg:flex-row">
                                    {{-- status accent --}}
                                    <div class="relative w-full shrink-0 self-stretch lg:w-1.5">
                                        <div class="h-1.5 w-full bg-gradient-to-r lg:h-full lg:min-h-full lg:bg-gradient-to-b {{ $cardStyle['bar'] }}"></div>
                                    </div>

                                    <div class="flex min-w-0 flex-1 flex-col gap-6 p-5 sm:p-6 lg:flex-row lg:gap-8">
                                        <div class="flex min-w-0 flex-1 gap-4">
                                            <div class="relative shrink-0">
                                                <img
                                                    src="{{ route('layanan.photo', $booking->muthowifProfile) }}"
                                                    alt="{{ __('bookings.index.photo_alt', ['name' => $booking->muthowifProfile->user->name]) }}"
                                                    class="h-16 w-16 rounded-2xl object-cover shadow-md ring-2 ring-white sm:h-[4.5rem] sm:w-[4.5rem]"
                                                    loading="lazy"
                                                >
                                                <span class="absolute -bottom-1 -right-1 flex h-6 w-6 items-center justify-center rounded-full bg-white shadow ring-1 ring-slate-200" title="{{ $st->label() }}">
                                                    <span class="h-2.5 w-2.5 rounded-full bg-gradient-to-br {{ $cardStyle['bar'] }}"></span>
                                                </span>
                                            </div>

                                            <div class="min-w-0 flex-1 space-y-3">
                                                <div>
                                                    <h2 class="truncate text-lg font-bold text-slate-900">
                                                        {{ $booking->muthowifProfile->user->name }}
                                                    </h2>
                                                    @if (filled($booking->booking_code))
                                                        <p class="mt-0.5 font-mono text-xs font-medium text-slate-600">
                                                            {{ $booking->booking_code }}
                                                        </p>
                                                    @endif
                                                </div>

                                                <div class="flex flex-wrap gap-2">
                                                    <span class="inline-flex items-center rounded-full px-2.5 py-0.5 text-xs font-semibold ring-1 {{ $cardStyle['badge'] }}">
                                                        {{ $st->label() }}
                                                    </span>
                                                    @if (in_array($st, [BookingStatus::Confirmed, BookingStatus::Completed], true) || ($st === BookingStatus::Cancelled && in_array($booking->payment_status, [PaymentStatus::RefundPending, PaymentStatus::Refunded], true)))
                                                        <span class="inline-flex items-center rounded-full px-2.5 py-0.5 text-xs font-semibold ring-1 {{ match ($booking->payment_status) {
                                                            PaymentStatus::Paid => 'bg-brand-50 text-brand-950 ring-brand-200/80',
                                                            PaymentStatus::RefundPending => 'bg-amber-50 text-amber-950 ring-amber-200/80',
                                                            PaymentStatus::Refunded => 'bg-red-100 text-red-800 ring-red-200/80',
                                                            default => 'bg-orange-50 text-orange-950 ring-orange-200/80',
                                                        } }}">
                                                            {{ $booking->payment_status->label() }}
                                                        </span>
                                                    @endif
                                                </div>

                                                <div class="grid gap-3 sm:grid-cols-2">
                                                    <div class="rounded-2xl bg-slate-50/90 p-3 ring-1 ring-slate-200/60">
                                                        <p class="flex items-center gap-1.5 text-[0.65rem] font-semibold uppercase tracking-wide text-slate-500">
                                                            <svg class="h-3.5 w-3.5 text-slate-400" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
                                                                <path fill-rule="evenodd" d="M5.75 2a.75.75 0 01.75.75V4h7V2.75a.75.75 0 011.5 0V4h.25A2.75 2.75 0 0118 6.75v8.5A2.75 2.75 0 0115.25 18H4.75A2.75 2.75 0 012 15.25v-8.5A2.75 2.75 0 014.75 4H5V2.75A.75.75 0 015.75 2zm-1 5.5c-.69 0-1.25.56-1.25 1.25v6.5c0 .69.56 1.25 1.25 1.25h10.5c.69 0 1.25-.56 1.25-1.25v-6.5c0-.69-.56-1.25-1.25-1.25H4.75z" clip-rule="evenodd" />
                                                            </svg>
                                                            {{ __('bookings.index_page.card_period') }}
                                                        </p>
                                                        <p class="mt-1 text-sm font-semibold tabular-nums text-slate-900">
                                                            {{ Carbon::parse($booking->starts_on)->format('d/m/Y') }}
                                                            <span class="mx-1 font-normal text-slate-400">→</span>
                                                            {{ Carbon::parse($booking->ends_on)->format('d/m/Y') }}
                                                        </p>
                                                        <p class="mt-0.5 text-xs text-slate-500">
                                                            {{ $nights }} {{ __('common.days_service') }}
                                                        </p>
                                                    </div>

                                                    <div class="rounded-2xl bg-slate-50/90 p-3 ring-1 ring-slate-200/60">
                                                        <p class="flex items-center gap-1.5 text-[0.65rem] font-semibold uppercase tracking-wide text-slate-500">
                                                            <svg class="h-3.5 w-3.5 text-slate-400" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
                                                                <path fill-rule="evenodd" d="M6 6a1 1 0 011-1h8a1 1 0 011 1v8a1 1 0 01-1 1H7a1 1 0 01-1-1V6zm-2 1a3 3 0 013-3h8a3 3 0 013 3v8a3 3 0 01-3 3H7a3 3 0 01-3-3V7z" clip-rule="evenodd" />
                                                            </svg>
                                                            {{ __('bookings.index_page.card_service') }}
                                                        </p>
                                                        <p class="mt-1 text-sm font-semibold leading-snug text-slate-900">
                                                            {{ $booking->service_type?->label() ?? '—' }}
                                                        </p>
                                                        <p class="mt-0.5 text-xs text-slate-500">
                                                            {{ __('bookings.index.pilgrims_count', ['count' => $booking->pilgrim_count, 'pilgrims_word' => __('common.pilgrims')]) }}
                                                        </p>
                                                    </div>
                                                </div>

                                                @if ($booking->service_type === MuthowifServiceType::PrivateJamaah && ! empty($booking->selected_add_on_ids))
                                                    <div class="rounded-2xl border border-dashed border-slate-200 bg-white/60 p-3">
                                                        <p class="text-[0.65rem] font-semibold uppercase tracking-wide text-slate-500">{{ __('bookings.index_page.card_addons') }}</p>
                                                        <ul class="mt-2 space-y-1 text-xs text-slate-600">
                                                            @foreach ($booking->selected_add_on_ids as $aid)
                                                                @if (isset($addonsById[$aid]))
                                                                    @php $ad = $addonsById[$aid]; @endphp
                                                                    <li class="flex justify-between gap-2">
                                                                        <span class="min-w-0 truncate">+ {{ $ad->name }}</span>
                                                                        <span class="shrink-0 font-medium tabular-nums text-slate-700">Rp {{ IndonesianNumber::formatThousands((string) (int) $ad->price) }}</span>
                                                                    </li>
                                                                @endif
                                                            @endforeach
                                                        </ul>
                                                    </div>
                                                @endif

                                                @if ($sameHotelLine > 0 || $transportLine > 0)
                                                    <div class="rounded-2xl border border-slate-100 bg-gradient-to-br from-slate-50/80 to-white p-3 ring-1 ring-slate-100">
                                                        <p class="text-[0.65rem] font-semibold uppercase tracking-wide text-slate-500">{{ __('bookings.index_page.card_extras') }}</p>
                                                        <ul class="mt-2 space-y-1 text-xs text-slate-600">
                                                            @if ($sameHotelLine > 0)
                                                                <li>{{ __('bookings.index.line_same_hotel', ['nights' => $nights, 'days' => __('common.days'), 'amount' => IndonesianNumber::formatThousands((string) (int) round($sameHotelLine))]) }}</li>
                                                            @endif
                                                            @if ($transportLine > 0)
                                                                <li>{{ __('bookings.index.line_transport', ['amount' => IndonesianNumber::formatThousands((string) (int) round($transportLine))]) }}</li>
                                                            @endif
                                                        </ul>
                                                    </div>
                                                @endif

                                                @include('bookings.partials.booking-documents', ['booking' => $booking, 'routeName' => 'bookings.documents.show', 'compact' => true])

                                                @if ($booking->payment_status === PaymentStatus::Paid && $booking->total_amount !== null)
                                                    <p class="text-sm font-semibold tabular-nums text-slate-800">
                                                        {{ __('bookings.index.total_line', ['amount' => IndonesianNumber::formatThousands((string) (int) round((float) $booking->total_amount))]) }}
                                                    </p>
                                                @endif
                                            </div>
                                        </div>

                                        <div class="flex w-full shrink-0 flex-col gap-2 border-t border-slate-100 pt-4 lg:w-56 lg:border-l lg:border-t-0 lg:pl-6 lg:pt-0">
                                            <p class="text-[0.65rem] font-semibold uppercase tracking-wide text-slate-400 lg:hidden">{{ __('bookings.index_page.actions_heading') }}</p>
                                            <div class="flex flex-col gap-2">
                                                <a href="{{ route('bookings.show', $booking) }}" class="inline-flex items-center justify-center gap-2 rounded-xl bg-slate-900 px-4 py-2.5 text-sm font-semibold text-white shadow-md shadow-slate-900/15 transition hover:bg-slate-800">
                                                    {{ __('bookings.index.detail') }}
                                                    <svg class="h-4 w-4 opacity-80" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
                                                        <path fill-rule="evenodd" d="M3 10a.75.75 0 01.75-.75h10.638L10.23 5.29a.75.75 0 111.04-1.08l5.5 5.25a.75.75 0 010 1.08l-5.5 5.25a.75.75 0 11-1.04-1.08l4.158-3.96H3.75A.75.75 0 013 10z" clip-rule="evenodd" />
                                                    </svg>
                                                </a>

                                                @if ($st === BookingStatus::Confirmed && $booking->payment_status === PaymentStatus::Pending)
                                                    <a href="{{ route('bookings.payment', $booking) }}" class="inline-flex items-center justify-center rounded-xl bg-gradient-to-r from-brand-600 to-brand-700 px-4 py-2.5 text-center text-sm font-semibold text-white shadow-md shadow-brand-600/20 transition hover:from-brand-500 hover:to-brand-600">
                                                        {{ __('bookings.index.pay_online') }}
                                                    </a>
                                                @endif

                                                @if (in_array($booking->payment_status, [PaymentStatus::Paid, PaymentStatus::RefundPending, PaymentStatus::Refunded], true))
                                                    <a href="{{ route('bookings.invoice', $booking) }}" target="_blank" rel="noopener noreferrer" class="inline-flex items-center justify-center rounded-xl border border-slate-200 bg-white px-4 py-2.5 text-sm font-semibold text-slate-800 shadow-sm transition hover:bg-slate-50">
                                                        {{ __('bookings.index.print_invoice') }}
                                                    </a>
                                                @endif

                                                @if ($st === BookingStatus::Confirmed && $booking->payment_status === PaymentStatus::Paid)
                                                    <a href="{{ route('bookings.show', $booking) }}" class="inline-flex items-center justify-center rounded-xl bg-emerald-600 px-4 py-2.5 text-sm font-semibold text-white shadow-md shadow-emerald-600/20 transition hover:bg-emerald-700">
                                                        {{ __('bookings.index.complete_rate') }}
                                                    </a>
                                                @endif

                                                @if ($st === BookingStatus::Completed)
                                                    <a href="{{ route('bookings.show', $booking) }}" class="inline-flex items-center justify-center rounded-xl border border-brand-200 bg-brand-50 px-4 py-2.5 text-sm font-semibold text-brand-900 transition hover:bg-brand-100">
                                                        {{ $booking->review ? __('bookings.index.view_review') : __('bookings.index.give_review') }}
                                                    </a>
                                                @endif

                                                @if ($st === BookingStatus::Pending)
                                                    <form method="POST" action="{{ route('bookings.cancel', $booking) }}" onsubmit="return confirm(@json(__('bookings.index.cancel_confirm')));">
                                                        @csrf
                                                        <button type="submit" class="w-full rounded-xl border border-red-200 bg-white px-4 py-2.5 text-sm font-semibold text-red-700 transition hover:bg-red-50">
                                                            {{ __('bookings.index.cancel') }}
                                                        </button>
                                                    </form>
                                                @endif

                                                @if ($st === BookingStatus::Confirmed && $booking->payment_status === PaymentStatus::Pending)
                                                    <form method="POST" action="{{ route('bookings.cancel', $booking) }}" onsubmit="return confirm(@json(__('bookings.index.cancel_confirm_confirmed')));">
                                                        @csrf
                                                        <button type="submit" class="w-full rounded-xl border border-slate-200 bg-transparent px-4 py-2.5 text-sm font-semibold text-slate-600 transition hover:border-slate-300 hover:bg-slate-50">
                                                            {{ __('bookings.index.cancel_before_pay') }}
                                                        </button>
                                                    </form>
                                                @endif
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </article>
                        </li>
                    @endforeach
                </ul>

                <div class="mt-10 flex justify-center">
                    <div class="rounded-2xl border border-slate-200/80 bg-white/80 px-2 py-1 shadow-sm backdrop-blur-sm">
                        {{ $bookings->links() }}
                    </div>
                </div>
            @endif
        </div>
