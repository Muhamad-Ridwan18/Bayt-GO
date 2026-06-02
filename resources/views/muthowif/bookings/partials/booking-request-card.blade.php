@php
    use App\Enums\BookingStatus;
    use App\Enums\MuthowifServiceType;
    use App\Enums\PaymentStatus;
    use App\Support\IndonesianNumber;
    use App\Support\PlatformFee;
    use Carbon\Carbon;

    $st = $booking->status;
    $nights = $booking->billingNightsInclusive();
    $service = $booking->muthowifProfile?->services?->firstWhere('type', $booking->service_type);
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

    $totalGross = (float) ($serviceSubtotal + $addonsSum + $sameHotelLine + $transportLine);
    $priceSplit = PlatformFee::split($totalGross);
    $muthowifNetIdr = (float) ($priceSplit['muthowif_net'] ?? 0.0);
    $muthowifFeeIdr = (float) ($priceSplit['muthowif_fee'] ?? 0.0);

    $totalBillIdr = (float) $booking->resolvedAmountDue();
    $paidIdr = $booking->payment_status === PaymentStatus::Paid ? $totalBillIdr : 0.0;
    $remainingIdr = max(0.0, $totalBillIdr - $paidIdr);

    $documentCount = collect([
        $booking->ticket_outbound_path,
        $booking->ticket_return_path,
        $booking->passport_path,
        $booking->itinerary_path,
        $booking->visa_path,
    ])->filter(fn ($p) => filled($p))->count();
    $hasDocuments = $documentCount > 0;

    $badgeClass = match ($st) {
        BookingStatus::Pending => 'bg-amber-100 text-amber-900 ring-amber-200/90',
        BookingStatus::Confirmed => 'bg-emerald-100 text-emerald-900 ring-emerald-200/90',
        BookingStatus::Completed => 'bg-slate-100 text-slate-800 ring-slate-200/90',
        BookingStatus::Cancelled => 'bg-red-100 text-red-800 ring-red-200/80',
        default => 'bg-slate-100 text-slate-700 ring-slate-200/80',
    };
    $accentClass = match ($st) {
        BookingStatus::Pending => 'bg-amber-500',
        BookingStatus::Confirmed => $booking->payment_status === PaymentStatus::Paid ? 'bg-emerald-500' : 'bg-amber-500',
        BookingStatus::Completed => 'bg-emerald-500',
        BookingStatus::Cancelled => 'bg-red-400',
        default => 'bg-slate-400',
    };

    $paymentPillClass = match (true) {
        $st === BookingStatus::Confirmed && $booking->payment_status === PaymentStatus::Paid => 'bg-emerald-100 text-emerald-900 ring-emerald-200/90',
        $st === BookingStatus::Confirmed && $booking->payment_status === PaymentStatus::Pending => 'bg-orange-100 text-orange-900 ring-orange-200/90',
        $booking->payment_status === PaymentStatus::RefundPending => 'bg-amber-100 text-amber-900 ring-amber-200/90',
        $booking->payment_status === PaymentStatus::Refunded => 'bg-red-100 text-red-800 ring-red-200/80',
        default => 'bg-slate-100 text-slate-700 ring-slate-200/80',
    };

    $paymentPillLabel = match (true) {
        $st === BookingStatus::Confirmed && $booking->payment_status === PaymentStatus::Pending => __('muthowif.bookings.waiting_payment'),
        $st === BookingStatus::Confirmed => $booking->payment_status->label(),
        default => $booking->payment_status->label(),
    };

    $customer = $booking->customer;
    $isCompany = $customer?->isCompanyCustomer() ?? false;
    $canCancelUnpaid = $st === BookingStatus::Confirmed && $booking->payment_status === PaymentStatus::Pending;
@endphp

<li
    class="overflow-hidden rounded-2xl border border-slate-200/90 bg-white shadow-sm ring-1 ring-slate-100/80"
    x-data="{
        open: false,
        showBreakdown: true,
        showAllDocs: false,
        docModalOpen: false,
        docTitle: '',
        docPreviewUrl: '',
        docKind: 'image',
        openDocPreview(title, url, kind) {
            this.docTitle = title;
            this.docPreviewUrl = url;
            this.docKind = kind;
            this.docModalOpen = true;
            document.body.classList.add('overflow-y-hidden');
        },
        closeDocPreview() {
            this.docModalOpen = false;
            document.body.classList.remove('overflow-y-hidden');
        },
    }"
    @keydown.escape.window="docModalOpen && closeDocPreview()"
>
    <div class="flex min-w-0">
        <div class="w-1.5 shrink-0 {{ $accentClass }}" aria-hidden="true"></div>
        <div class="min-w-0 flex-1">
            {{-- Collapsed header --}}
            <div class="flex flex-col gap-4 p-4 sm:p-5 lg:flex-row lg:items-center lg:gap-6">
                <button
                    type="button"
                    class="flex min-w-0 flex-1 flex-col gap-3 text-left sm:flex-row sm:items-center sm:gap-4"
                    @click="open = !open"
                    :aria-expanded="open"
                >
                    <div class="flex min-w-0 flex-1 items-start gap-3">
                        <span class="flex h-11 w-11 shrink-0 items-center justify-center rounded-xl bg-slate-100 text-slate-600 ring-1 ring-slate-200/80" aria-hidden="true">
                            @if ($isCompany)
                                <svg class="h-5 w-5" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor">
                                    <path fill-rule="evenodd" d="M4.16 3.5a1.25 1.25 0 011.24-1.07l.04-.01h8.32c.62 0 1.17.37 1.4.94l2.24 5.54a1.25 1.25 0 01-1.2 1.72H4.16a1.25 1.25 0 01-1.24-1.44l.04-.18 1.2-5.5zM6 8.75a.75.75 0 00-1.5 0v4.5a.75.75 0 001.5 0v-4.5zm4.25 0a.75.75 0 00-1.5 0v4.5a.75.75 0 001.5 0v-4.5zm4.25 0a.75.75 0 00-1.5 0v4.5a.75.75 0 001.5 0v-4.5z" clip-rule="evenodd" />
                                </svg>
                            @else
                                <svg class="h-5 w-5" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor">
                                    <path d="M10 8a3 3 0 100-6 3 3 0 000 6zM3.465 14.493a1.23 1.23 0 00.41 1.412A9.957 9.957 0 0010 18c2.31 0 4.438-.784 6.131-2.1.43-.333.604-.903.408-1.41a7.002 7.002 0 00-13.254-.005z" />
                                </svg>
                            @endif
                        </span>
                        <div class="min-w-0 flex-1">
                            <div class="flex flex-wrap items-center gap-2">
                                <p class="truncate text-base font-semibold text-slate-900">{{ $customer?->name ?? '—' }}</p>
                                <span class="inline-flex shrink-0 items-center rounded-full px-2.5 py-0.5 text-xs font-semibold ring-1 {{ $badgeClass }}">
                                    {{ $st->label() }}
                                </span>
                            </div>
                            @if (filled($booking->booking_code))
                                <p class="mt-0.5 font-mono text-xs text-slate-500">{{ $booking->booking_code }}</p>
                            @endif
                            <div class="mt-2 flex flex-wrap gap-x-4 gap-y-1.5 text-xs text-slate-600">
                                <span class="inline-flex items-center gap-1">
                                    <svg class="h-3.5 w-3.5 text-slate-400" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true"><path fill-rule="evenodd" d="M5.75 2a.75.75 0 01.75.75V4h7V2.75a.75.75 0 011.5 0V4h.25A2.75 2.75 0 0118 6.75v8.5A2.75 2.75 0 0115.25 18H4.75A2.75 2.75 0 012 15.25v-8.5A2.75 2.75 0 014.75 4H5V2.75A.75.75 0 015.75 2zm-1 5.5c-.69 0-1.25.56-1.25 1.25v6.5c0 .69.56 1.25 1.25 1.25h10.5c.69 0 1.25-.56 1.25-1.25v-6.5c0-.69-.56-1.25-1.25-1.25H4.75z" clip-rule="evenodd" /></svg>
                                    {{ Carbon::parse($booking->starts_on)->format('d/m/Y') }} – {{ Carbon::parse($booking->ends_on)->format('d/m/Y') }}
                                </span>
                                <span class="inline-flex items-center gap-1">
                                    <svg class="h-3.5 w-3.5 text-slate-400" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true"><path d="M10 8a3 3 0 100-6 3 3 0 000 6zM3.465 14.493a1.23 1.23 0 00.41 1.412A9.957 9.957 0 0010 18c2.31 0 4.438-.784 6.131-2.1.43-.333.604-.903.408-1.41a7.002 7.002 0 00-13.254-.005z" /></svg>
                                    {{ __('muthowif.bookings.pilgrims_meta', ['count' => $booking->pilgrim_count]) }}
                                </span>
                                <span>{{ $booking->service_type?->label() ?? '—' }}</span>
                            </div>
                        </div>
                    </div>
                </button>

                <div class="flex shrink-0 flex-col items-end gap-1 sm:min-w-[9rem] lg:items-center lg:text-center">
                    <p class="text-[11px] font-medium uppercase tracking-wide text-slate-500">{{ __('muthowif.bookings.net_earning_short') }}</p>
                    <p class="text-xl font-bold tabular-nums text-emerald-700 sm:text-2xl">Rp {{ IndonesianNumber::formatThousands((string) (int) round($muthowifNetIdr)) }}</p>
                </div>

                <div class="flex shrink-0 items-center gap-2 self-end lg:self-center">
                    <div class="hidden items-center gap-2 sm:flex" x-show="open" x-cloak>
                        <a
                            href="{{ route('muthowif.bookings.show', $booking) }}"
                            class="inline-flex items-center gap-1.5 rounded-lg border border-slate-200 bg-white px-3 py-2 text-sm font-semibold text-slate-800 shadow-sm transition hover:border-slate-300 hover:bg-slate-50"
                            @click.stop
                        >
                            <svg class="h-4 w-4 text-slate-500" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true"><path d="M10 12a2 2 0 100-4 2 2 0 000 4z" /><path fill-rule="evenodd" d="M.458 10C1.732 5.943 5.522 3 10 3s8.268 2.943 9.542 7c-1.274 4.057-5.064 7-9.542 7-4.478 0-8.268-2.943-9.542-7zM14 10a4 4 0 11-8 0 4 4 0 018 0z" clip-rule="evenodd" /></svg>
                            {{ __('muthowif.bookings.view_detail') }}
                        </a>
                        <button
                            type="button"
                            class="inline-flex items-center gap-1.5 rounded-lg border border-slate-200 bg-white px-3 py-2 text-sm font-semibold text-slate-800 shadow-sm transition hover:border-slate-300 hover:bg-slate-50"
                            @click.stop="$dispatch('open-booking-chat', { bookingId: @js($booking->getKey()) })"
                        >
                            <svg class="h-4 w-4 text-slate-500" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true"><path d="M3.505 2.365A41.369 41.369 0 0110 2c2.89 0 5.66.7 8.03 1.96a.75.75 0 01.47.69v9.02a.75.75 0 01-.94.72C16.2 13.35 13.18 12.5 10 12.5c-3.18 0-6.2.85-8.56 2.27a.75.75 0 01-.94-.72V3.055a.75.75 0 01.47-.69zM10 4c-2.3 0-4.5.47-6.53 1.33v7.34C5.5 11.53 7.7 11 10 11s4.5.53 6.53 1.67V5.33C14.5 4.47 12.3 4 10 4z" /></svg>
                            {{ __('muthowif.bookings.chat') }}
                        </button>
                        @if ($canCancelUnpaid)
                            <form method="POST" action="{{ route('muthowif.bookings.cancel', $booking) }}" class="inline" onsubmit="return confirm(@json(__('muthowif.bookings.cancel_unpaid_confirm')));" @click.stop>
                                @csrf
                                <button type="submit" class="inline-flex items-center gap-1.5 rounded-lg border border-red-200 bg-white px-3 py-2 text-sm font-semibold text-red-700 transition hover:bg-red-50">
                                    <svg class="h-4 w-4" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true"><path fill-rule="evenodd" d="M8.75 1A2.75 2.75 0 006 3.75v.443c-.795.077-1.584.176-2.365.298a.75.75 0 00-.53 1.28l1.5 1.5a.75.75 0 001.28-.53l-.03-.18c.716-.12 1.44-.22 2.17-.3V6A1.25 1.25 0 0110 7.25h.5A1.25 1.25 0 0111.75 6v-.75c.716.08 1.424.18 2.12.3l-.03.18a.75.75 0 001.28.53l1.5-1.5a.75.75 0 00-.53-1.28 14.85 14.85 0 00-2.365-.298V3.75A2.75 2.75 0 0011.25 1h-2.5zM4.5 8.25a.75.75 0 00-.75.75v7.5c0 .414.336.75.75.75h11a.75.75 0 00.75-.75v-7.5a.75.75 0 00-.75-.75h-11z" clip-rule="evenodd" /></svg>
                                    {{ __('muthowif.bookings.cancel') }}
                                </button>
                            </form>
                        @endif
                    </div>
                    <button
                        type="button"
                        class="inline-flex h-10 w-10 items-center justify-center rounded-lg border border-slate-200 bg-white text-slate-600 transition hover:bg-slate-50"
                        @click="open = !open"
                        :aria-expanded="open"
                    >
                        <svg class="h-5 w-5 transition" :class="open && 'rotate-180'" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true"><path fill-rule="evenodd" d="M5.23 7.21a.75.75 0 011.06.02L10 11.168l3.71-3.94a.75.75 0 111.08 1.04l-4.24 4.5a.75.75 0 01-1.08 0l-4.24-4.5a.75.75 0 01.02-1.06z" clip-rule="evenodd" /></svg>
                    </button>
                </div>
            </div>

            {{-- Expanded panel --}}
            <div
                x-show="open"
                x-transition:enter="transition ease-out duration-200"
                x-transition:enter-start="opacity-0"
                x-transition:enter-end="opacity-100"
                x-transition:leave="transition ease-in duration-150"
                x-transition:leave-start="opacity-100"
                x-transition:leave-end="opacity-0"
                x-cloak
                class="border-t border-slate-100"
            >
                <div class="grid gap-6 p-4 sm:p-5 lg:grid-cols-3 lg:gap-5">
                    {{-- Rincian Layanan --}}
                    <div class="flex flex-col rounded-xl border border-slate-100 bg-slate-50/50 p-4">
                        <h3 class="text-sm font-bold text-slate-900">{{ __('muthowif.bookings.service_breakdown_heading') }}</h3>
                        <div x-show="showBreakdown" class="mt-3 space-y-0 divide-y divide-slate-100 text-sm">
                            <div class="flex justify-between gap-2 py-2">
                                <span class="text-slate-600">{{ __('muthowif.booking_show.subtotal_service') }}</span>
                                <span class="font-medium tabular-nums text-slate-900">Rp {{ IndonesianNumber::formatThousands((string) (int) round($serviceSubtotal)) }}</span>
                            </div>
                            @foreach ($addonLines as $ad)
                                <div class="flex justify-between gap-2 py-2">
                                    <span class="text-slate-600">{{ $ad->name }}</span>
                                    <span class="font-medium tabular-nums text-slate-900">Rp {{ IndonesianNumber::formatThousands((string) (int) round((float) $ad->price)) }}</span>
                                </div>
                            @endforeach
                            @if ($sameHotelLine > 0)
                                <div class="flex justify-between gap-2 py-2">
                                    <span class="text-slate-600">{{ __('bookings.show.same_hotel_label', ['nights' => $nights, 'days' => __('common.days')]) }}</span>
                                    <span class="font-medium tabular-nums text-slate-900">Rp {{ IndonesianNumber::formatThousands((string) (int) round($sameHotelLine)) }}</span>
                                </div>
                            @endif
                            @if ($transportLine > 0)
                                <div class="flex justify-between gap-2 py-2">
                                    <span class="text-slate-600">{{ __('bookings.show.transport_label') }}</span>
                                    <span class="font-medium tabular-nums text-slate-900">Rp {{ IndonesianNumber::formatThousands((string) (int) round($transportLine)) }}</span>
                                </div>
                            @endif
                            <div class="flex justify-between gap-2 py-2">
                                <span class="text-red-600">{{ __('muthowif.booking_show.platform_fee_muthowif') }}</span>
                                <span class="font-medium tabular-nums text-red-600">- Rp {{ IndonesianNumber::formatThousands((string) (int) round($muthowifFeeIdr)) }}</span>
                            </div>
                            <div class="flex justify-between gap-2 py-2.5 font-bold">
                                <span class="text-slate-900">{{ __('muthowif.booking_show.net_earning') }}</span>
                                <span class="tabular-nums text-emerald-700">Rp {{ IndonesianNumber::formatThousands((string) (int) round($muthowifNetIdr)) }}</span>
                            </div>
                        </div>
                        <button type="button" class="mt-auto pt-3 text-left text-xs font-semibold text-brand-700 hover:text-brand-800" @click="showBreakdown = !showBreakdown">
                            <span x-text="showBreakdown ? @js(__('muthowif.bookings.hide_breakdown')) : @js(__('muthowif.bookings.view_breakdown'))"></span>
                        </button>
                    </div>

                    {{-- Dokumen --}}
                    <div class="flex flex-col rounded-xl border border-slate-100 bg-slate-50/50 p-4">
                        <h3 class="text-sm font-bold text-slate-900">{{ __('muthowif.bookings.travel_documents_heading') }}</h3>
                        <div class="mt-3 min-h-[4rem] flex-1">
                            @if ($hasDocuments)
                                @include('bookings.partials.booking-documents', [
                                    'booking' => $booking,
                                    'routeName' => 'muthowif.bookings.documents.show',
                                    'variant' => 'list',
                                    'collapseLimit' => 3,
                                ])
                            @else
                                <p class="text-sm text-slate-500">{{ __('muthowif.bookings.no_documents') }}</p>
                            @endif
                        </div>
                        @if ($hasDocuments && $documentCount > 3)
                            <button type="button" class="mt-auto pt-3 text-left text-xs font-semibold text-brand-700 hover:text-brand-800" @click="showAllDocs = !showAllDocs">
                                <span x-text="showAllDocs ? @js(__('muthowif.bookings.hide_documents')) : @js(__('muthowif.bookings.view_all_documents'))"></span>
                            </button>
                        @endif
                    </div>

                    {{-- Pembayaran --}}
                    <div class="flex flex-col rounded-xl border border-slate-100 bg-slate-50/50 p-4">
                        <div class="flex flex-wrap items-center justify-between gap-2">
                            <h3 class="text-sm font-bold text-slate-900">{{ __('muthowif.bookings.payment_info_heading') }}</h3>
                            @if ($st === BookingStatus::Confirmed || in_array($booking->payment_status, [PaymentStatus::RefundPending, PaymentStatus::Refunded], true))
                                <span class="inline-flex items-center rounded-full px-2.5 py-0.5 text-xs font-semibold ring-1 {{ $paymentPillClass }}">
                                    {{ $paymentPillLabel }}
                                </span>
                            @endif
                        </div>
                        <dl class="mt-4 space-y-3 text-sm">
                            <div class="flex justify-between gap-2">
                                <dt class="text-slate-600">{{ __('muthowif.bookings.total_bill') }}</dt>
                                <dd class="font-semibold tabular-nums text-slate-900">Rp {{ IndonesianNumber::formatThousands((string) (int) round($totalBillIdr)) }}</dd>
                            </div>
                            <div class="flex justify-between gap-2">
                                <dt class="text-slate-600">{{ __('muthowif.bookings.paid') }}</dt>
                                <dd class="font-semibold tabular-nums text-slate-900">Rp {{ IndonesianNumber::formatThousands((string) (int) round($paidIdr)) }}</dd>
                            </div>
                            <div class="flex justify-between gap-2 border-t border-slate-100 pt-3">
                                <dt class="font-bold text-slate-900">{{ __('muthowif.bookings.remaining_balance') }}</dt>
                                <dd class="font-bold tabular-nums text-red-600">Rp {{ IndonesianNumber::formatThousands((string) (int) round($remainingIdr)) }}</dd>
                            </div>
                        </dl>
                        <a href="{{ route('muthowif.bookings.show', $booking) }}" class="mt-auto pt-3 text-xs font-semibold text-brand-700 hover:text-brand-800">
                            {{ __('muthowif.bookings.view_payment_details') }}
                        </a>
                    </div>
                </div>

                {{-- Mobile actions + pending --}}
                <div class="flex flex-col gap-3 border-t border-slate-100 px-4 py-4 sm:hidden">
                    <div class="flex flex-wrap gap-2">
                        <a href="{{ route('muthowif.bookings.show', $booking) }}" class="inline-flex flex-1 items-center justify-center gap-1.5 rounded-lg border border-slate-200 bg-white px-3 py-2 text-sm font-semibold text-slate-800">
                            {{ __('muthowif.bookings.view_detail') }}
                        </a>
                        <button type="button" class="inline-flex flex-1 items-center justify-center gap-1.5 rounded-lg border border-slate-200 bg-white px-3 py-2 text-sm font-semibold text-slate-800" @click="$dispatch('open-booking-chat', { bookingId: @js($booking->getKey()) })">
                            {{ __('muthowif.bookings.chat') }}
                        </button>
                    </div>
                </div>

                @if ($st === BookingStatus::Pending)
                    <div class="border-t border-slate-100 bg-slate-50/80 px-4 py-4 sm:px-5">
                        <p class="text-xs font-semibold uppercase tracking-wide text-slate-500">{{ __('muthowif.bookings.pending_actions') }}</p>
                        <div class="mt-3 flex flex-col gap-3 sm:flex-row sm:items-start">
                            <form method="POST" action="{{ route('muthowif.bookings.confirm', $booking) }}" class="sm:flex-1">
                                @csrf
                                <button type="submit" class="inline-flex w-full items-center justify-center gap-2 rounded-xl bg-brand-600 px-4 py-2.5 text-sm font-semibold text-white shadow-sm transition hover:bg-brand-700">
                                    {{ __('muthowif.bookings.approve') }}
                                </button>
                            </form>
                            <div class="sm:flex-1">
                                @include('muthowif.bookings.partials.reject-booking-form', ['booking' => $booking, 'compact' => true])
                            </div>
                        </div>
                    </div>
                @elseif (($booking->pending_reschedule_requests_count ?? 0) > 0)
                    <div class="border-t border-slate-100 px-4 py-3 sm:px-5">
                        <a href="{{ route('muthowif.bookings.show', $booking) }}" class="inline-flex items-center gap-2 rounded-full bg-amber-50 px-3 py-1.5 text-xs font-semibold text-amber-950 ring-1 ring-amber-200/90">
                            {{ __('muthowif.bookings.reschedule_badge') }}
                        </a>
                    </div>
                @endif
            </div>
        </div>
    </div>

    {{-- Modal pratinjau dokumen --}}
    <div
        x-show="docModalOpen"
        x-cloak
        class="fixed inset-0 z-[100] flex items-center justify-center p-4 sm:p-6"
        role="dialog"
        aria-modal="true"
        :aria-label="docTitle"
    >
        <div class="absolute inset-0 bg-slate-900/70 backdrop-blur-[2px]" @click="closeDocPreview()"></div>
        <div class="relative z-10 flex max-h-[92vh] w-full max-w-4xl flex-col overflow-hidden rounded-2xl bg-white shadow-2xl ring-1 ring-slate-200/90" @click.stop>
            <div class="flex shrink-0 items-center justify-between gap-3 border-b border-slate-100 px-4 py-3 sm:px-5">
                <h4 class="min-w-0 truncate text-sm font-bold text-slate-900" x-text="docTitle"></h4>
                <button
                    type="button"
                    class="shrink-0 rounded-lg p-1.5 text-slate-500 transition hover:bg-slate-100 hover:text-slate-800"
                    @click="closeDocPreview()"
                    aria-label="{{ __('bookings.show.doc_modal_close') }}"
                >
                    <svg class="h-5 w-5" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true"><path d="M6.28 5.22a.75.75 0 00-1.06 1.06L8.94 10l-3.72 3.72a.75.75 0 101.06 1.06L10 11.06l3.72 3.72a.75.75 0 101.06-1.06L11.06 10l3.72-3.72a.75.75 0 00-1.06-1.06L10 8.94 6.28 5.22z" /></svg>
                </button>
            </div>
            <div class="min-h-0 flex-1 overflow-auto bg-slate-50/80 p-4 sm:p-5">
                <img
                    x-show="docKind === 'image'"
                    x-cloak
                    :src="docPreviewUrl"
                    :alt="docTitle"
                    class="mx-auto block h-auto max-h-[min(75vh,36rem)] w-full object-contain"
                />
                <iframe
                    x-show="docKind === 'pdf'"
                    x-cloak
                    :src="docPreviewUrl"
                    :title="docTitle"
                    class="mx-auto block h-[min(75vh,36rem)] w-full rounded-xl border border-slate-200 bg-white shadow-sm"
                ></iframe>
                <div x-show="docKind !== 'image' && docKind !== 'pdf'" x-cloak class="rounded-xl border border-dashed border-slate-200 bg-white px-4 py-8 text-center text-sm text-slate-600">
                    <p>{{ __('bookings.show.doc_preview_unsupported') }}</p>
                    <a :href="docPreviewUrl" target="_blank" rel="noopener noreferrer" class="mt-3 inline-flex font-semibold text-brand-700 hover:underline">
                        {{ __('bookings.show.doc_open_tab') }}
                    </a>
                </div>
            </div>
            <div class="flex shrink-0 justify-end gap-2 border-t border-slate-100 px-4 py-3 sm:px-5">
                <a
                    x-show="docPreviewUrl"
                    :href="docPreviewUrl + (docPreviewUrl.includes('?') ? '&' : '?') + 'download=1'"
                    class="inline-flex items-center gap-1.5 rounded-lg border border-emerald-200 bg-emerald-50 px-3 py-2 text-xs font-semibold text-emerald-800 transition hover:bg-emerald-100"
                >
                    {{ __('bookings.show.doc_download') }}
                </a>
            </div>
        </div>
    </div>
</li>
