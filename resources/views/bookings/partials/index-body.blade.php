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
        <x-page-container class="relative z-10 pb-16 pt-10">
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
                            $cardStyle = $statusCardStyles[$st->value] ?? $statusCardStyles[BookingStatus::Pending->value];
                            $nights = $booking->billingNightsInclusive();
                            $service = $booking->muthowifProfile?->services->firstWhere('type', $booking->service_type);
                            $daily = (float) ($booking->daily_price_snapshot ?? ($service ? $service->daily_price : 0.0));
                            $serviceSubtotal = (float) ($nights * $daily);

                            $addonLines = collect();
                            if ($booking->service_type === MuthowifServiceType::PrivateJamaah) {
                                if (! empty($booking->add_ons_snapshot)) {
                                    $addonLines = collect($booking->add_ons_snapshot)->map(fn ($a) => (object) $a);
                                } elseif (! empty($booking->selected_add_on_ids)) {
                                    foreach ($booking->selected_add_on_ids as $aid) {
                                        if (isset($addonsById[$aid])) {
                                            $addonLines->push($addonsById[$aid]);
                                        }
                                    }
                                }
                            }
                            $addonsSum = $addonLines->sum(fn ($a) => (float) $a->price);

                            $sameHotelPrice = (float) ($booking->same_hotel_price_snapshot ?? ($service ? $service->same_hotel_price_per_day : 0.0));
                            $sameHotelLine = $booking->with_same_hotel ? ($nights * $sameHotelPrice) : 0.0;

                            $transportLine = (float) ($booking->transport_price_snapshot ?? ($booking->with_transport && $service ? (float) $service->transport_price_flat : 0.0));

                            $totalDue = (float) ($serviceSubtotal + $addonsSum + $sameHotelLine + $transportLine);
                            $isCompany = $booking->customer?->isCompanyCustomer() ?? false;
                            $split = \App\Support\PlatformFee::split($totalDue, $isCompany);
                            $customerGross = (float) ($split['customer_gross'] ?? $totalDue);
                            $customerFee = (float) ($split['customer_fee'] ?? 0.0);
                        @endphp
                        <li class="group relative">
                            <div class="absolute -inset-px rounded-[1.35rem] bg-gradient-to-br from-white to-slate-100/80 opacity-0 blur transition duration-300 group-hover:opacity-100"></div>
                            <article
                                class="relative overflow-hidden rounded-3xl border border-slate-200/80 bg-white shadow-md shadow-slate-900/5 transition duration-300 hover:-translate-y-0.5 hover:border-slate-300/80 hover:shadow-lg hover:shadow-slate-900/10 {{ $cardStyle['glow'] }}"
                            >
                                <div class="p-4 sm:p-5">
                                    {{-- Row 1: Name & Price --}}
                                    <div class="flex items-center justify-between">
                                        <h2 class="truncate text-base font-bold text-slate-900 sm:text-lg">
                                            {{ $booking->muthowifProfile->user->name }}
                                        </h2>
                                        <p class="text-base font-bold text-brand-700 tabular-nums sm:text-lg">
                                            Rp {{ IndonesianNumber::formatThousands((string) (int) round($customerGross)) }}
                                        </p>
                                    </div>

                                    {{-- Row 2: Code & Status --}}
                                    <div class="mt-0.5 flex items-center justify-between text-[11px] font-medium sm:text-xs">
                                        <p class="font-mono text-slate-500">
                                            {{ $booking->booking_code ?? '-' }}
                                        </p>
                                        <div class="flex items-center gap-2">
                                            <span class="inline-flex max-w-[11rem] shrink-0 items-center truncate rounded-full px-2 py-0.5 text-[10px] font-bold ring-1 sm:text-xs {{ $cardStyle['badge'] }}">
                                                {{ $st->label() }}
                                            </span>
                                            <span class="flex min-w-0 items-center gap-1.5 {{ match ($booking->payment_status) {
                                                PaymentStatus::Paid => 'text-emerald-600',
                                                PaymentStatus::RefundPending => 'text-amber-600',
                                                PaymentStatus::Refunded => 'text-red-600',
                                                PaymentStatus::Pending => 'text-slate-500',
                                                default => 'text-slate-600',
                                            } }} font-bold">
                                                <span class="text-[8px]">●</span> {{ $booking->payment_status->label() }}
                                            </span>
                                        </div>
                                    </div>

                                    <div class="my-3 h-px bg-slate-100/80"></div>

                                    {{-- Info List --}}
                                    <div class="space-y-2">
                                        {{-- Date --}}
                                        <div class="flex items-center gap-2.5 text-xs text-slate-600 sm:text-sm">
                                            <span class="text-base">📅</span>
                                            <span class="font-semibold tabular-nums">
                                                {{ Carbon::parse($booking->starts_on)->format('d') }} – {{ Carbon::parse($booking->ends_on)->format('d M Y') }}
                                            </span>
                                        </div>

                                        {{-- Service & Pilgrims --}}
                                        <div class="flex items-center gap-2.5 text-xs text-slate-600 sm:text-sm">
                                            <span class="text-base">👥</span>
                                            <span class="font-semibold">
                                                {{ $booking->service_type?->label() ?? '—' }} · {{ $booking->pilgrim_count }} {{ __('common.pilgrims') }}
                                            </span>
                                        </div>

                                        {{-- Add-ons count --}}
                                        @if (! empty($booking->selected_add_on_ids))
                                            <div class="flex items-center gap-2.5 text-xs text-slate-600 sm:text-sm">
                                                <span class="text-base">➕</span>
                                                <span class="font-semibold">
                                                    {{ count($booking->selected_add_on_ids) }} {{ __('bookings.index_page.card_addons') }}
                                                </span>
                                            </div>
                                        @endif
                                    </div>

                                    <div class="mt-4 overflow-hidden rounded-xl border border-brand-100/60 bg-brand-50/20 ring-1 ring-brand-100/40">
                                        <dl class="divide-y divide-brand-100/40 text-[10px] sm:text-xs">
                                            <div class="flex justify-between gap-3 px-3 py-1.5">
                                                <dt class="text-slate-600">{{ __('bookings.show.subtotal_service') }}</dt>
                                                <dd class="font-medium tabular-nums text-slate-900">Rp {{ IndonesianNumber::formatThousands((string) (int) round($serviceSubtotal)) }}</dd>
                                            </div>
                                            @if ($addonLines->isNotEmpty())
                                                @foreach ($addonLines as $ad)
                                                    <div class="flex justify-between gap-3 px-3 py-1.5">
                                                        <dt class="text-slate-500">+ {{ $ad->name }}</dt>
                                                        <dd class="font-medium tabular-nums text-slate-800">Rp {{ IndonesianNumber::formatThousands((string) (int) round((float) $ad->price)) }}</dd>
                                                    </div>
                                                @endforeach
                                            @endif
                                            @if ($sameHotelLine > 0)
                                                <div class="flex justify-between gap-3 px-3 py-1.5">
                                                    <dt class="text-slate-500">{{ __('bookings.show.same_hotel_label', ['nights' => $nights, 'days' => __('common.days')]) }}</dt>
                                                    <dd class="font-medium tabular-nums text-slate-800">Rp {{ IndonesianNumber::formatThousands((string) (int) round($sameHotelLine)) }}</dd>
                                                </div>
                                            @endif
                                            @if ($transportLine > 0)
                                                <div class="flex justify-between gap-3 px-3 py-1.5">
                                                    <dt class="text-slate-500">{{ __('bookings.show.transport_label') }}</dt>
                                                    <dd class="font-medium tabular-nums text-slate-800">Rp {{ IndonesianNumber::formatThousands((string) (int) round($transportLine)) }}</dd>
                                                </div>
                                            @endif
                                            @if ($customerFee > 0)
                                                <div class="flex justify-between gap-3 px-3 py-1.5">
                                                    <dt class="text-slate-600">{{ __('bookings.show.platform_fee') }}</dt>
                                                    <dd class="font-medium tabular-nums text-slate-900">+ Rp {{ IndonesianNumber::formatThousands((string) (int) round($customerFee)) }}</dd>
                                                </div>
                                            @endif
                                            <div class="flex justify-between gap-3 bg-brand-50/60 px-3 py-2">
                                                <dt class="font-bold text-slate-900">{{ __('bookings.invoice.total') }}</dt>
                                                <dd class="font-bold tabular-nums text-brand-700 sm:text-sm">Rp {{ IndonesianNumber::formatThousands((string) (int) round($customerGross)) }}</dd>
                                            </div>
                                        </dl>
                                    </div>

                                    <div class="my-3 h-px bg-slate-100/80"></div>

                                    {{-- Actions --}}
                                    <div class="flex flex-wrap items-center gap-2">
                                        <a href="{{ route('bookings.show', $booking) }}" class="inline-flex items-center justify-center rounded-lg bg-slate-900 px-3.5 py-1.5 text-[11px] font-bold text-white transition hover:bg-slate-800 sm:text-xs">
                                            {{ __('bookings.index.detail') }}
                                        </a>

                                        @if ($st === BookingStatus::Confirmed && $booking->payment_status === PaymentStatus::Pending)
                                            <a href="{{ route('bookings.payment', $booking) }}" class="inline-flex items-center justify-center rounded-lg bg-brand-600 px-3.5 py-1.5 text-[11px] font-bold text-white transition hover:bg-brand-700 sm:text-xs">
                                                {{ __('bookings.index.pay_online') }}
                                            </a>
                                        @endif

                                        @if (in_array($booking->payment_status, [PaymentStatus::Paid, PaymentStatus::RefundPending, PaymentStatus::Refunded], true))
                                            <a href="{{ route('bookings.invoice', $booking) }}" target="_blank" rel="noopener noreferrer" class="inline-flex items-center justify-center rounded-lg border border-slate-200 bg-white px-3.5 py-1.5 text-[11px] font-bold text-slate-700 transition hover:bg-slate-50 sm:text-xs">
                                                {{ __('bookings.index.print_invoice') }}
                                            </a>
                                        @endif

                                        @if ($st === BookingStatus::Completed)
                                            <a href="{{ route('bookings.show', $booking) }}" class="inline-flex items-center justify-center rounded-lg border border-brand-200 bg-brand-50 px-3.5 py-1.5 text-[11px] font-bold text-brand-700 transition hover:bg-brand-100 sm:text-xs">
                                                {{ $booking->review ? __('bookings.index.view_review') : __('bookings.index.give_review') }}
                                            </a>
                                        @endif

                                        @if ($st === BookingStatus::Confirmed && $booking->isPaid())
                                            <a href="{{ route('bookings.refund', $booking) }}" class="inline-flex items-center justify-center rounded-lg border border-red-100 bg-red-50 px-3.5 py-1.5 text-[11px] font-bold text-red-600 transition hover:bg-red-100 sm:text-xs">
                                                Refund
                                            </a>
                                            <a href="{{ route('bookings.reschedule', $booking) }}" class="inline-flex items-center justify-center rounded-lg border border-brand-100 bg-brand-50 px-3.5 py-1.5 text-[11px] font-bold text-brand-700 transition hover:bg-brand-100 sm:text-xs">
                                                Reschedule
                                            </a>
                                        @endif

                                        @if ($st === BookingStatus::Pending)
                                            <form method="POST" action="{{ route('bookings.cancel', $booking) }}" onsubmit="return confirm(@json(__('bookings.index.cancel_confirm')));" class="inline">
                                                @csrf
                                                <button type="submit" class="rounded-lg border border-red-100 bg-red-50 px-3.5 py-1.5 text-[11px] font-bold text-red-600 transition hover:bg-red-100 sm:text-xs">
                                                    {{ __('bookings.index.cancel') }}
                                                </button>
                                            </form>
                                        @endif
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
        </x-page-container>
