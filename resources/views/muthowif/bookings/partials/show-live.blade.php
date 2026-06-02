@php
    use App\Enums\BookingChangeRequestStatus;
    use App\Enums\BookingStatus;
    use App\Enums\MuthowifServiceType;
    use App\Enums\PaymentStatus;
    use App\Models\BookingPayment;
    use App\Support\IndonesianNumber;
    use App\Support\PlatformFee;
    use Carbon\Carbon;

    $b = $booking;
    $st = $b->status;
    $nights = $b->billingNightsInclusive();
    $service = $b->muthowifProfile?->services->firstWhere('type', $b->service_type);
    $daily = (float) ($b->daily_price_snapshot ?? ($service ? $service->daily_price : 0.0));
    $serviceSubtotal = (float) ($nights * $daily);

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

    $totalGross = (float) ($serviceSubtotal + $addonsSum + $sameHotelLine + $transportLine);
    $split = PlatformFee::split($totalGross);
    $muthowifNet = (float) ($split['muthowif_net'] ?? 0.0);
    $muthowifFee = (float) ($split['muthowif_fee'] ?? 0.0);

    $settledPaymentForReferral = BookingPayment::query()
        ->where('muthowif_booking_id', $b->getKey())
        ->whereIn('status', ['settlement', 'capture'])
        ->orderByDesc('settled_at')
        ->first();
    $pendingPaymentForReferral = $settledPaymentForReferral === null
        ? BookingPayment::query()
            ->where('muthowif_booking_id', $b->getKey())
            ->where('status', 'pending')
            ->orderByDesc('created_at')
            ->first()
        : null;
    $payForReferral = $settledPaymentForReferral ?? $pendingPaymentForReferral;
    $referralRewardFromPay = $payForReferral !== null
        ? round((float) ($payForReferral->referral_reward_amount ?? 0), 2)
        : 0.0;
    $muthowifNetAfterReferral = round(max(0.0, $muthowifNet - $referralRewardFromPay), 2);
    $fmt = fn (float $n) => IndonesianNumber::formatThousands((string) (int) round($n));
@endphp

<x-page-container class="pb-8">
    <a href="{{ route('muthowif.bookings.index') }}" class="mb-5 inline-flex items-center gap-2 text-sm font-semibold text-brand-700 transition hover:text-brand-800">
        <svg class="h-4 w-4" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
            <path fill-rule="evenodd" d="M17 10a.75.75 0 01-.75.75H5.612l4.158 3.96a.75.75 0 11-1.04 1.08l-5.5-5.25a.75.75 0 010-1.08l5.5-5.25a.75.75 0 111.04 1.08L5.612 9.25H16.25A.75.75 0 0117 10z" clip-rule="evenodd" />
        </svg>
        {{ __('muthowif.booking_show.back_list') }}
    </a>

    <div class="grid grid-cols-1 gap-6 lg:grid-cols-[minmax(0,1fr)_minmax(280px,360px)] lg:items-start lg:gap-8">
        <div class="min-w-0 space-y-6">
            @include('muthowif.bookings.partials.show-detail-card', ['booking' => $b])

            @include('muthowif.bookings.partials.incident-panel', [
                'booking' => $b,
                'openIncident' => $openIncident ?? null,
                'incomingReplacement' => $incomingReplacement ?? null,
                'peerReplacementsAwaitingConfirm' => $peerReplacementsAwaitingConfirm ?? collect(),
                'customerChoicePool' => $customerChoicePool ?? collect(),
            ])

            {{-- Sidebar ringkas di mobile (status + pendapatan) --}}
            <div class="lg:hidden">
                @include('muthowif.bookings.partials.show-sidebar', [
                    'booking' => $b,
                    'daily' => $daily,
                    'nights' => $nights,
                    'serviceSubtotal' => $serviceSubtotal,
                    'addonLines' => $addonLines,
                    'sameHotelLine' => $sameHotelLine,
                    'transportLine' => $transportLine,
                    'muthowifFee' => $muthowifFee,
                    'referralRewardFromPay' => $referralRewardFromPay,
                    'muthowifNetAfterReferral' => $muthowifNetAfterReferral,
                    'fmt' => $fmt,
                ])
            </div>

            @include('bookings.partials.booking-documents', [
                'booking' => $b,
                'routeName' => 'muthowif.bookings.documents.show',
                'variant' => 'cards',
            ])

            @include('muthowif.bookings.partials.show-actions', [
                'booking' => $b,
                'peerRecommendTargets' => $peerRecommendTargets ?? collect(),
            ])
        </div>

        <div class="hidden min-w-0 lg:block lg:col-start-2 lg:row-start-1 lg:row-span-2">
            @include('muthowif.bookings.partials.show-sidebar', [
                'booking' => $b,
                'daily' => $daily,
                'nights' => $nights,
                'serviceSubtotal' => $serviceSubtotal,
                'addonLines' => $addonLines,
                'sameHotelLine' => $sameHotelLine,
                'transportLine' => $transportLine,
                'muthowifFee' => $muthowifFee,
                'referralRewardFromPay' => $referralRewardFromPay,
                'muthowifNetAfterReferral' => $muthowifNetAfterReferral,
                'fmt' => $fmt,
            ])
        </div>

        <div class="min-w-0 space-y-6 lg:col-start-1">
            @if ($b->refundRequests->isNotEmpty())
                <section class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm sm:p-6">
                    <h3 class="text-sm font-bold text-slate-900">{{ __('muthowif.booking_show.refund_title') }}</h3>
                    <p class="mt-1 text-xs leading-relaxed text-slate-600">{{ __('muthowif.booking_show.refund_intro') }}</p>
                    <ul class="mt-4 space-y-4 text-sm">
                        @foreach ($b->refundRequests as $req)
                            <li class="rounded-xl border border-slate-100 bg-slate-50/80 p-4 space-y-2">
                                <div class="flex flex-wrap items-center gap-2">
                                    <span class="inline-flex rounded-full px-2 py-0.5 text-xs font-semibold ring-1 {{ match ($req->status) {
                                        BookingChangeRequestStatus::Pending => 'bg-amber-50 text-amber-900 ring-amber-200',
                                        BookingChangeRequestStatus::Approved => 'bg-emerald-50 text-emerald-900 ring-emerald-200',
                                        BookingChangeRequestStatus::Rejected => 'bg-rose-50 text-rose-900 ring-rose-200',
                                    } }}">{{ $req->status->label() }}</span>
                                    <span class="text-xs text-slate-500">{{ $req->created_at?->timezone(config('app.timezone'))->format('d/m/Y H:i') }}</span>
                                </div>
                                <p class="text-slate-700">{{ __('muthowif.booking_show.refund_net_prefix') }} <strong>Rp {{ $fmt((float) $req->net_refund_customer) }}</strong></p>
                                <p class="text-xs text-slate-600">{{ __('muthowif.booking_show.refund_fees', [
                                    'platform' => $fmt((float) $req->refund_fee_platform),
                                    'muthowif' => $fmt((float) $req->refund_fee_muthowif),
                                ]) }}</p>
                                @if ($req->refund_bank_name || $req->refund_account_holder || $req->refund_account_number)
                                    <div class="rounded-lg border border-slate-100 bg-white px-3 py-2 text-xs text-slate-700">
                                        <p class="font-semibold text-slate-800">{{ __('muthowif.booking_show.refund_bank_label') }}</p>
                                        <p>{{ $req->refund_bank_name ?: '—' }} · {{ $req->refund_account_holder ?: '—' }}</p>
                                        <p class="font-mono tabular-nums">{{ $req->refund_account_number ?: '—' }}</p>
                                    </div>
                                @endif
                                @if ($req->customer_note)
                                    <p class="text-slate-600"><span class="font-medium">{{ __('muthowif.booking_show.role_pilgrim') }}</span> {{ $req->customer_note }}</p>
                                @endif
                                @if ($req->muthowif_note)
                                    <p class="text-slate-600"><span class="font-medium">{{ __('muthowif.booking_show.role_muthowif') }}</span> {{ $req->muthowif_note }}</p>
                                @endif
                                @if ($req->gateway_refunded_at)
                                    <p class="text-xs text-emerald-800">{{ __('muthowif.booking_show.gateway_refund', ['datetime' => $req->gateway_refunded_at->timezone(config('app.timezone'))->format('d/m/Y H:i')]) }}</p>
                                @endif
                            </li>
                        @endforeach
                    </ul>
                </section>
            @endif

            @if ($b->rescheduleRequests->isNotEmpty())
                <section class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm sm:p-6">
                    <h3 class="text-sm font-bold text-slate-900">{{ __('muthowif.booking_show.reschedule_title') }}</h3>
                    <ul class="mt-4 space-y-4 text-sm">
                        @foreach ($b->rescheduleRequests as $req)
                            <li class="rounded-xl border border-slate-100 bg-slate-50/80 p-4 space-y-2">
                                <div class="flex flex-wrap items-center gap-2">
                                    <span class="inline-flex rounded-full px-2 py-0.5 text-xs font-semibold ring-1 {{ match ($req->status) {
                                        BookingChangeRequestStatus::Pending => 'bg-amber-50 text-amber-900 ring-amber-200',
                                        BookingChangeRequestStatus::Approved => 'bg-emerald-50 text-emerald-900 ring-emerald-200',
                                        BookingChangeRequestStatus::Rejected => 'bg-rose-50 text-rose-900 ring-rose-200',
                                    } }}">{{ $req->status->label() }}</span>
                                    <span class="text-xs text-slate-500">{{ $req->created_at?->timezone(config('app.timezone'))->format('d/m/Y H:i') }}</span>
                                </div>
                                @php
                                    $prevStart = Carbon::parse($req->previous_starts_on)->format('d/m/Y');
                                    $prevEnd = Carbon::parse($req->previous_ends_on)->format('d/m/Y');
                                    $newStart = Carbon::parse($req->new_starts_on)->format('d/m/Y');
                                    $newEnd = Carbon::parse($req->new_ends_on)->format('d/m/Y');
                                @endphp
                                <p class="text-slate-700">
                                    {{ __('muthowif.booking_show.reschedule_from', ['old_start' => $prevStart, 'old_end' => $prevEnd]) }}
                                    → <strong>{{ __('muthowif.booking_show.reschedule_to', ['new_start' => $newStart, 'new_end' => $newEnd]) }}</strong>
                                </p>
                                @if ($req->customer_note)
                                    <p class="text-slate-600"><span class="font-medium">{{ __('muthowif.booking_show.role_pilgrim') }}</span> {{ $req->customer_note }}</p>
                                @endif
                                @if ($req->muthowif_note)
                                    <p class="text-slate-600"><span class="font-medium">{{ __('muthowif.booking_show.role_muthowif') }}</span> {{ $req->muthowif_note }}</p>
                                @endif

                                @if ($req->isPending() && $st === BookingStatus::Confirmed && $b->isPaid())
                                    <div class="flex flex-col gap-3 border-t border-slate-200 pt-3 sm:flex-row sm:flex-wrap">
                                        <form method="POST" action="{{ route('muthowif.bookings.reschedule_requests.approve', [$b, $req]) }}" class="min-w-0 flex-1 space-y-2 sm:min-w-[14rem]">
                                            @csrf
                                            <input type="text" name="muthowif_note" placeholder="{{ __('muthowif.booking_show.note_optional') }}" class="w-full rounded-lg border-slate-300 text-sm">
                                            <button type="submit" class="inline-flex w-full items-center justify-center rounded-lg bg-emerald-600 px-4 py-2 text-xs font-semibold text-white hover:bg-emerald-700" onclick="return confirm(@json(__('muthowif.booking_show.approve_reschedule_confirm')));">
                                                {{ __('muthowif.booking_show.approve_reschedule') }}
                                            </button>
                                        </form>
                                        <form method="POST" action="{{ route('muthowif.bookings.reschedule_requests.reject', [$b, $req]) }}" class="min-w-0 flex-1 space-y-2 sm:min-w-[14rem]">
                                            @csrf
                                            <input type="text" name="muthowif_note" placeholder="{{ __('muthowif.booking_show.reject_reason_optional') }}" class="w-full rounded-lg border-slate-300 text-sm">
                                            <button type="submit" class="inline-flex w-full items-center justify-center rounded-lg border border-red-200 bg-white px-4 py-2 text-xs font-semibold text-red-800 hover:bg-red-50">
                                                {{ __('muthowif.booking_show.reject_short') }}
                                            </button>
                                        </form>
                                    </div>
                                @endif
                            </li>
                        @endforeach
                    </ul>
                </section>
            @endif
        </div>
    </div>
</x-page-container>
