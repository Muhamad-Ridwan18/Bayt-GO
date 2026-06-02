@php
    use App\Enums\MuthowifServiceType;
    use App\Enums\PaymentStatus;
    use App\Support\IndonesianNumber;
    use App\Support\PlatformFee;
    use Carbon\Carbon;

    /** @var \App\Models\MuthowifBooking $booking */
    /** @var \App\Models\BookingIncident $incident */
    $variant = $variant ?? 'invite';
    $replacement = $replacement ?? null;
    $addonsById = $addonsById ?? collect();
    $defaultOpen = $defaultOpen ?? false;

    $booking->loadMissing(['customer', 'muthowifProfile.services.addOns']);
    $incident->loadMissing([]);

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

    $documentCount = collect([
        $booking->ticket_outbound_path,
        $booking->ticket_return_path,
        $booking->passport_path,
        $booking->itinerary_path,
        $booking->visa_path,
    ])->filter(fn ($p) => filled($p))->count();
    $hasDocuments = $documentCount > 0;

    $customer = $booking->customer;
    $isCompany = $customer?->isCompanyCustomer() ?? false;

    $isInvite = $variant === 'invite';
    $accentClass = $isInvite ? 'bg-violet-500' : 'bg-amber-500';
    $badgeClass = $isInvite
        ? 'bg-violet-100 text-violet-950 ring-violet-200/90'
        : 'bg-amber-100 text-amber-950 ring-amber-200/90';
    $badgeLabel = $isInvite
        ? __('muthowif.replacements.badge_invite')
        : __('muthowif.replacements.badge_opportunity');
@endphp

<li
    class="overflow-hidden rounded-2xl border border-slate-200/90 bg-white shadow-sm ring-1 ring-slate-100/80"
    x-data="{
        open: @js($defaultOpen),
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
            <div class="flex flex-col gap-4 p-4 sm:p-5 lg:flex-row lg:items-center lg:gap-6">
                <button
                    type="button"
                    class="flex min-w-0 flex-1 flex-col gap-3 text-left sm:flex-row sm:items-center sm:gap-4"
                    @click="open = !open"
                    :aria-expanded="open"
                >
                    <div class="flex min-w-0 flex-1 items-start gap-3">
                        <span class="flex h-11 w-11 shrink-0 items-center justify-center rounded-xl {{ $isInvite ? 'bg-violet-50 text-violet-700 ring-violet-100' : 'bg-amber-50 text-amber-800 ring-amber-100' }} ring-1" aria-hidden="true">
                            @if ($isCompany)
                                <svg class="h-5 w-5" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor"><path fill-rule="evenodd" d="M4.16 3.5a1.25 1.25 0 011.24-1.07l.04-.01h8.32c.62 0 1.17.37 1.4.94l2.24 5.54a1.25 1.25 0 01-1.2 1.72H4.16a1.25 1.25 0 01-1.24-1.44l.04-.18 1.2-5.5zM6 8.75a.75.75 0 00-1.5 0v4.5a.75.75 0 001.5 0v-4.5zm4.25 0a.75.75 0 00-1.5 0v4.5a.75.75 0 001.5 0v-4.5zm4.25 0a.75.75 0 00-1.5 0v4.5a.75.75 0 001.5 0v-4.5z" clip-rule="evenodd" /></svg>
                            @else
                                <svg class="h-5 w-5" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor"><path d="M10 8a3 3 0 100-6 3 3 0 000 6zM3.465 14.493a1.23 1.23 0 00.41 1.412A9.957 9.957 0 0010 18c2.31 0 4.438-.784 6.131-2.1.43-.333.604-.903.408-1.41a7.002 7.002 0 00-13.254-.005z" /></svg>
                            @endif
                        </span>
                        <div class="min-w-0 flex-1">
                            <div class="flex flex-wrap items-center gap-2">
                                <p class="truncate text-base font-semibold text-slate-900">{{ $customer?->name ?? '—' }}</p>
                                <span class="inline-flex shrink-0 items-center rounded-full px-2.5 py-0.5 text-xs font-semibold ring-1 {{ $badgeClass }}">
                                    {{ $badgeLabel }}
                                </span>
                                <span class="inline-flex shrink-0 items-center rounded-full bg-slate-100 px-2.5 py-0.5 text-xs font-semibold text-slate-700 ring-1 ring-slate-200/80">
                                    {{ $incident->case_type->label() }}
                                </span>
                            </div>
                            @if (filled($booking->booking_code))
                                <p class="mt-0.5 font-mono text-xs text-slate-500">{{ $booking->booking_code }}</p>
                            @endif
                            <div class="mt-2 flex flex-wrap gap-x-4 gap-y-1.5 text-xs text-slate-600">
                                <span class="inline-flex items-center gap-1">
                                    <svg class="h-3.5 w-3.5 text-slate-400" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true"><path fill-rule="evenodd" d="M5.75 2a.75.75 0 01.75.75V4h7V2.75a.75.75 0 011.5 0V4h.25A2.75 2.75 0 0118 6.75v8.5A2.75 2.75 0 0115.25 18H4.75A2.75 2.75 0 012 15.25v-8.5A2.25 2.25 0 014.75 4H5V2.75A.75.75 0 015.75 2zm-1 5.5c-.69 0-1.25.56-1.25 1.25v6.5c0 .69.56 1.25 1.25 1.25h10.5c.69 0 1.25-.56 1.25-1.25v-6.5c0-.69-.56-1.25-1.25-1.25H4.75z" clip-rule="evenodd" /></svg>
                                    {{ Carbon::parse($booking->starts_on)->format('d/m/Y') }} – {{ Carbon::parse($booking->ends_on)->format('d/m/Y') }}
                                </span>
                                <span class="inline-flex items-center gap-1">
                                    <svg class="h-3.5 w-3.5 text-slate-400" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true"><path d="M10 8a3 3 0 100-6 3 3 0 000 6zM3.465 14.493a1.23 1.23 0 00.41 1.412A9.957 9.957 0 0010 18c2.31 0 4.438-.784 6.131-2.1.43-.333.604-.903.408-1.41a7.002 7.002 0 00-13.254-.005z" /></svg>
                                    {{ __('muthowif.bookings.pilgrims_meta', ['count' => $booking->pilgrim_count]) }}
                                </span>
                                <span>{{ $booking->service_type?->label() ?? '—' }}</span>
                            </div>
                            <p class="mt-1.5 text-[11px] text-slate-500">{{ __('muthowif.replacements.original_guide', ['name' => $booking->muthowifProfile?->user?->name ?? '—']) }}</p>
                        </div>
                    </div>
                </button>

                <div class="flex shrink-0 flex-col items-end gap-1 sm:min-w-[9rem] lg:items-center lg:text-center">
                    <p class="text-[11px] font-medium uppercase tracking-wide text-slate-500">{{ __('muthowif.bookings.net_earning_short') }}</p>
                    <p class="text-xl font-bold tabular-nums text-emerald-700 sm:text-2xl">Rp {{ IndonesianNumber::formatThousands((string) (int) round($muthowifNetIdr)) }}</p>
                </div>

                <div class="flex shrink-0 items-center gap-2 self-end lg:self-center">
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
                    </div>

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
                    </div>

                    <div class="flex flex-col rounded-xl border border-slate-100 bg-slate-50/50 p-4">
                        <h3 class="text-sm font-bold text-slate-900">{{ __('muthowif.replacements.incident_heading') }}</h3>
                        <dl class="mt-3 space-y-2 text-sm">
                            <div>
                                <dt class="text-xs text-slate-500">{{ __('muthowif.replacements.incident_status') }}</dt>
                                <dd class="font-medium text-slate-900">{{ $incident->status->label() }}</dd>
                            </div>
                            @if ($replacement && filled($replacement->admin_note))
                                <div>
                                    <dt class="text-xs text-slate-500">{{ __('muthowif.replacements.admin_note') }}</dt>
                                    <dd class="text-slate-800">{{ $replacement->admin_note }}</dd>
                                </div>
                            @endif
                        </dl>
                        <a href="{{ route('muthowif.bookings.show', $booking) }}" class="mt-auto pt-3 text-xs font-semibold text-brand-700 hover:text-brand-800">
                            {{ __('muthowif.bookings.view_detail') }} →
                        </a>
                    </div>
                </div>

                <div class="border-t border-slate-100 bg-slate-50/80 px-4 py-4 sm:px-5">
                    <p class="text-xs font-semibold uppercase tracking-wide text-slate-500">{{ __('muthowif.replacements.pending_actions') }}</p>
                    @if ($isInvite && $replacement)
                        <div class="mt-3 flex flex-col gap-3 sm:flex-row sm:items-start">
                            <form method="POST" action="{{ route('muthowif.replacements.confirm', $replacement) }}" class="sm:flex-1">
                                @csrf
                                <button type="submit" class="inline-flex w-full items-center justify-center gap-2 rounded-xl bg-brand-600 px-4 py-2.5 text-sm font-semibold text-white shadow-sm transition hover:bg-brand-700">
                                    {{ __('incidents.muthowif_confirm_replacement') }}
                                </button>
                            </form>
                            <form method="POST" action="{{ route('muthowif.replacements.decline', $replacement) }}" class="flex min-w-0 flex-col gap-2 sm:flex-1">
                                @csrf
                                <input type="text" name="note" class="w-full rounded-xl border-slate-200 text-sm" placeholder="{{ __('incidents.muthowif.decline_note_placeholder') }}">
                                <button type="submit" class="inline-flex w-full items-center justify-center rounded-xl border border-slate-300 bg-white px-4 py-2.5 text-sm font-semibold text-slate-800 hover:bg-slate-50">
                                    {{ __('incidents.muthowif_decline_replacement') }}
                                </button>
                            </form>
                        </div>
                    @else
                        <div class="mt-3 flex flex-col gap-3 sm:flex-row sm:items-end">
                            <form method="POST" action="{{ route('muthowif.replacements.volunteer', $incident) }}" class="flex min-w-0 flex-1 flex-col gap-2">
                                @csrf
                                <input type="text" name="note" class="w-full rounded-xl border-slate-200 text-sm" placeholder="{{ __('incidents.muthowif.volunteer_note_placeholder') }}">
                                <button type="submit" class="inline-flex w-full items-center justify-center gap-2 rounded-xl bg-brand-600 px-4 py-2.5 text-sm font-semibold text-white shadow-sm transition hover:bg-brand-700">
                                    {{ __('incidents.muthowif.accept_offer') }}
                                </button>
                            </form>
                            <form method="POST" action="{{ route('muthowif.replacements.decline', $incident) }}" class="sm:shrink-0" onsubmit="return confirm(@json(__('incidents.muthowif.decline_confirm')));">
                                @csrf
                                <button type="submit" class="inline-flex w-full items-center justify-center rounded-xl border border-slate-300 bg-white px-4 py-2.5 text-sm font-semibold text-slate-700 hover:bg-slate-50 sm:w-auto">
                                    {{ __('incidents.muthowif.decline_offer') }}
                                </button>
                            </form>
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>

    @include('bookings.partials.booking-documents-preview-modal')
</li>
