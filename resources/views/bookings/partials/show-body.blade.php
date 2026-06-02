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
    $daily = (float) ($b->daily_price_snapshot ?? ($service ? $service->daily_price : 0.0));
    $baseSubtotal = (float) $nights * $daily;

    $addonLines = collect();
    if ($b->service_type === MuthowifServiceType::PrivateJamaah) {
        if (! empty($b->add_ons_snapshot)) {
            $addonLines = collect($b->add_ons_snapshot)->map(fn ($a) => (object) $a);
        } elseif (! empty($b->selected_add_on_ids)) {
            foreach ($b->selected_add_on_ids as $aid) {
                if (isset($addonsById[$aid])) {
                    $addonLines->push($addonsById[$aid]);
                }
            }
        }
    }
    $addonsSum = $addonLines->sum(fn ($a) => (float) $a->price);

    $sameHotelPrice = (float) ($b->same_hotel_price_snapshot ?? ($service ? $service->same_hotel_price_per_day : 0.0));
    $sameHotelLine = $b->with_same_hotel ? ($nights * $sameHotelPrice) : 0.0;

    $transportLine = (float) ($b->transport_price_snapshot ?? ($b->with_transport && $service ? (float) $service->transport_price_flat : 0.0));

    $baseTotal = (float) ($baseSubtotal + $addonsSum + $sameHotelLine + $transportLine);
    $isCompany = $b->customer?->isCompanyCustomer() ?? false;
    $split = \App\Support\PlatformFee::split($baseTotal, $isCompany);
    $customerTotal = (float) ($split['customer_gross'] ?? $baseTotal);
    $customerPlatformFee = (float) ($split['customer_fee'] ?? 0.0);
    $muthowifNet = (float) ($split['muthowif_net'] ?? 0.0);
    $review = $b->review;
    $fmt = fn (float $n) => IndonesianNumber::formatThousands((string) (int) round($n));
@endphp

<div class="grid grid-cols-1 gap-6 lg:grid-cols-12 lg:gap-8">
    <div class="min-w-0 space-y-6 lg:col-span-8">
        @include('bookings.partials.show-detail-card', ['booking' => $b])

        @include('bookings.partials.show-cancellation-alert', [
            'booking' => $b,
            'showReferralNetworkPanel' => $showReferralNetworkPanel ?? false,
            'referralNetworkAlternatives' => $referralNetworkAlternatives ?? collect(),
        ])

        @if (! empty($showReferralNetworkPanel) && $showReferralNetworkPanel)
            <section id="booking-recommendations-mobile" class="scroll-mt-24 rounded-2xl border border-slate-200/90 bg-white p-5 shadow-sm ring-1 ring-slate-100/80 sm:p-6 lg:hidden">
                <h2 class="text-sm font-bold text-slate-900">{{ __('bookings.show.recommendations_title') }}</h2>
                <p class="mt-1 text-xs leading-relaxed text-slate-600">{{ __('bookings.show.recommendations_subtitle') }}</p>
                <div class="mt-4">
                    @include('bookings.partials.show-recommendations-list', [
                        'booking' => $b,
                        'referralNetworkAlternatives' => $referralNetworkAlternatives ?? collect(),
                    ])
                </div>
            </section>
        @endif

        @include('bookings.partials.incident-panel', [
            'booking' => $b,
            'openIncident' => $openIncident ?? null,
            'selectableReplacements' => $selectableReplacements ?? collect(),
        ])

        @include('bookings.partials.booking-documents', [
            'booking' => $b,
            'routeName' => 'bookings.documents.show',
            'variant' => 'cards',
        ])

            @if (in_array($st, [BookingStatus::Confirmed, BookingStatus::Completed], true) || ($st === BookingStatus::Cancelled && $b->paid_at))
                <div class="overflow-hidden rounded-2xl border border-slate-200/90 bg-white shadow-sm ring-1 ring-slate-100/80">
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
                                        â€”
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
                            @if ($customerPlatformFee > 0)
                                <div class="flex justify-between gap-4 border-t border-slate-200/80 pt-3">
                                    <dt class="text-slate-600">{{ __('bookings.show.platform_fee') }}</dt>
                                    <dd class="font-medium tabular-nums text-slate-900">Rp {{ $fmt($customerPlatformFee) }}</dd>
                                </div>
                            @endif
                            <div class="flex justify-between gap-4 border-t border-slate-200 pt-3 text-base">
                                <dt class="font-semibold text-slate-900">{{ __('bookings.show.total_customer') }}</dt>
                                <dd class="font-bold tabular-nums text-brand-700">Rp {{ $fmt($customerTotal) }}</dd>
                            </div>
                        </dl>

                        @if ($b->isAwaitingPayment())
                            @php $paymentQuery = request()->query('payment'); @endphp
                            @if ($paymentQuery === 'success')
                                <p class="mt-5 text-xs leading-relaxed text-slate-600">
                                    {!! __('bookings.show.payment_wait_html') !!}
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

            @if ($st === BookingStatus::Confirmed)
                <div class="space-y-6 rounded-2xl border border-slate-200/90 bg-white p-6 shadow-sm ring-1 ring-slate-100/80 sm:p-8">
                    <div>
                        <h2 class="text-lg font-bold text-slate-900">{{ __('bookings.show.refund_reschedule_heading') }}</h2>
                        <p class="mt-2 text-xs leading-relaxed text-slate-600">
                            {!! __('bookings.show.refund_reschedule_intro_html', [
                                'refund_days' => BookingPostPayRules::refundMinDaysBeforeService(),
                                'reschedule_days' => BookingPostPayRules::rescheduleMinDaysBeforeService(),
                            ]) !!}
                        </p>
                    </div>

                    <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                        @if ($b->isPaid())
                            <a href="{{ route('bookings.refund', $b) }}" class="flex flex-col items-start gap-1 rounded-2xl border border-slate-100 bg-slate-50/50 p-4 transition hover:bg-slate-50 hover:ring-1 hover:ring-slate-200">
                                <span class="text-sm font-bold text-slate-900">{{ __('bookings.show.process_refund') }}</span>
                                <span class="text-[10px] text-slate-500">Ajukan pengembalian dana jika batal berangkat.</span>
                            </a>
                            @if ($b->pendingRescheduleRequest())
                                <div class="flex flex-col items-start gap-1 rounded-2xl border border-amber-100 bg-amber-50/50 p-4 ring-1 ring-amber-100/60">
                                    <span class="text-sm font-bold text-amber-900">{{ __('bookings.show.reschedule_pending') }}</span>
                                    <span class="text-[10px] text-amber-700">Menunggu keputusan muthowif.</span>
                                </div>
                            @else
                                <a href="{{ route('bookings.reschedule', $b) }}" class="flex flex-col items-start gap-1 rounded-2xl border border-slate-100 bg-slate-50/50 p-4 transition hover:bg-slate-50 hover:ring-1 hover:ring-slate-200">
                                    <span class="text-sm font-bold text-slate-900">{{ __('bookings.show.submit_reschedule') }}</span>
                                    <span class="text-[10px] text-slate-500">Ganti tanggal layanan (perlu persetujuan).</span>
                                </a>
                            @endif
                        @else
                             <div class="sm:col-span-2 rounded-2xl border border-amber-100 bg-amber-50/50 p-4 text-xs text-amber-800">
                                {{ __('bookings.reschedule_eligibility.not_paid') }}
                             </div>
                        @endif
                    </div>

                    @if ($b->refundRequests->isNotEmpty() || $b->rescheduleRequests->isNotEmpty())
                        <div class="space-y-3 border-t border-slate-100 pt-6 text-xs text-slate-600">
                            <h3 class="font-bold text-slate-900 uppercase tracking-wider text-[10px]">Riwayat Pengajuan</h3>
                            @foreach ($b->refundRequests as $req)
                                <p class="rounded-lg bg-slate-50/80 px-3 py-2">
                                    {{ __('bookings.show.timeline_refund', ['status' => $req->status->label(), 'datetime' => $req->created_at?->timezone(config('app.timezone'))->format('d/m/Y H:i')]) }}
                                    @if ($req->refund_bank_name || $req->refund_account_holder || $req->refund_account_number)
                                        <br><span class="text-slate-600">{{ __('bookings.show.timeline_refund_bank', [
                                            'bank' => $req->refund_bank_name ?: '—',
                                            'holder' => $req->refund_account_holder ?: '—',
                                            'number' => $req->refund_account_number ?: '—',
                                        ]) }}</span>
                                    @endif
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
                <div class="rounded-2xl border border-amber-200 bg-gradient-to-br from-amber-50 to-amber-50/40 p-6 text-sm text-amber-950 shadow-sm ring-1 ring-amber-200/60">
                    <p class="font-bold">{{ __('bookings.show.refund_pending_title') }}</p>
                    <p class="mt-2 leading-relaxed">
                        {!! __('bookings.show.refund_pending_body_html', ['amount' => $pend ? $fmt((float) $pend->net_refund_customer) : __('common.em_dash')]) !!}
                    </p>
                </div>
            @endif

            @if ($st === BookingStatus::Cancelled && $b->isRefunded())
                <div class="rounded-2xl border border-slate-200 bg-slate-50 p-6 text-sm text-slate-700 shadow-sm">
                    {{ __('bookings.show.refunded_done') }}
                </div>
            @endif

            @if ($st === BookingStatus::Confirmed && $b->payment_status === PaymentStatus::Paid && empty($openIncident))
                <div class="overflow-hidden rounded-2xl border border-brand-200 bg-gradient-to-br from-brand-50/90 to-white p-6 shadow-sm ring-1 ring-brand-200/50 sm:p-8">
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
                <form method="POST" action="{{ route('bookings.cancel', $b) }}" class="rounded-2xl border border-red-200/80 bg-gradient-to-br from-red-50/90 to-white p-5 shadow-sm ring-1 ring-red-100/80" onsubmit="return confirm(@json(__('bookings.show.cancel_booking_confirm')));">
                    @csrf
                    <p class="text-sm font-bold text-red-900">{{ __('bookings.show.cancel_section_title') }}</p>
                    <p class="mt-1 text-xs text-red-800/90">{{ __('bookings.show.cancel_section_hint') }}</p>
                    <button type="submit" class="mt-4 rounded-xl border border-red-200 bg-white px-4 py-2.5 text-sm font-semibold text-red-700 shadow-sm transition hover:bg-red-50">
                        {{ __('bookings.show.cancel_yes') }}
                    </button>
                </form>
            @endif

            @if ($st === BookingStatus::Completed)
                <div class="rounded-2xl border border-slate-200/90 bg-white p-6 shadow-sm ring-1 ring-slate-100/80 sm:p-8">
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

    @include('bookings.partials.show-sidebar', [
        'booking' => $b,
        'showReferralNetworkPanel' => $showReferralNetworkPanel ?? false,
        'referralNetworkAlternatives' => $referralNetworkAlternatives ?? collect(),
    ])
</div>
