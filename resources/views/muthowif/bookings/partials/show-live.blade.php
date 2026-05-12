@php
    use App\Enums\BookingChangeRequestStatus;
    use App\Enums\BookingStatus;
    use App\Enums\MuthowifServiceType;
    use App\Enums\PaymentStatus;
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
    $customerGross = (float) ($split['customer_gross'] ?? 0.0);
    $customerFee = (float) ($split['customer_fee'] ?? 0.0);
    $fmt = fn (float $n) => IndonesianNumber::formatThousands((string) (int) round($n));

    $badgeClass = match ($st) {
        BookingStatus::Pending => 'bg-amber-100 text-amber-950 ring-amber-200/90',
        BookingStatus::Confirmed => 'bg-emerald-100 text-emerald-950 ring-emerald-200/90',
        BookingStatus::Completed => 'bg-slate-100 text-slate-800 ring-slate-200/90',
        BookingStatus::Cancelled => 'bg-red-100 text-red-700 ring-red-200/80',
        default => 'bg-slate-100 text-slate-700 ring-slate-200/80',
    };
    $accentClass = match ($st) {
        BookingStatus::Pending => 'bg-amber-500',
        BookingStatus::Confirmed => 'bg-emerald-500',
        BookingStatus::Completed => 'bg-brand-500',
        BookingStatus::Cancelled => 'bg-red-400',
        default => 'bg-slate-400',
    };
@endphp
        <div class="relative mx-auto max-w-3xl space-y-6 px-4 sm:px-6 lg:px-8">
            {{-- Hero --}}
            <div class="relative overflow-hidden rounded-2xl bg-gradient-to-br from-slate-900 via-violet-950 to-brand-900 p-5 text-white shadow-lg shadow-violet-900/25 ring-1 ring-white/10 sm:rounded-3xl sm:p-6">
                <div class="pointer-events-none absolute inset-0 bg-[url('data:image/svg+xml,%3Csvg width=\'60\' height=\'60\' viewBox=\'0 0 60 60\' xmlns=\'http://www.w3.org/2000/svg\'%3E%3Cg fill=\'none\' fill-rule=\'evenodd\'%3E%3Cg fill=\'%23ffffff\' fill-opacity=\'0.05\'%3E%3Cpath d=\'M36 34v-4h-2v4h-4v2h4v4h2v-4h4v-2h-4zm0-30V0h-2v4h-4v2h4v4h2V6h4V4h-4zM6 34v-4H4v4H0v2h4v4h2v-4h4v-2H6zM6 4V0H4v4H0v2h4v4h2V6h4V4H6z\'/%3E%3C/g%3E%3C/g%3E%3C/svg%3E')] opacity-40"></div>
                <div class="pointer-events-none absolute -right-12 top-0 h-40 w-40 rounded-full bg-violet-500/20 blur-3xl"></div>
                <div class="relative flex flex-col gap-4 sm:flex-row sm:items-start sm:justify-between">
                    <div class="flex min-w-0 items-start gap-3">
                        <span class="flex h-11 w-11 shrink-0 items-center justify-center rounded-xl bg-white/15 ring-1 ring-white/20" aria-hidden="true">
                            <svg class="h-6 w-6" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor"><path fill-rule="evenodd" d="M10 9a3 3 0 100-6 3 3 0 000 6zm-7 9a7 7 0 1114 0h-1.79A5.21 5.21 0 0010 14.79 5.21 5.21 0 005.79 18H3z" clip-rule="evenodd" /></svg>
                        </span>
                        <div class="min-w-0">
                            <p class="text-[11px] font-semibold uppercase tracking-wider text-violet-200/90">{{ __('dashboard_muthowif.nav_bookings') }}</p>
                            <h1 class="mt-1 truncate text-xl font-bold tracking-tight text-white sm:text-2xl">{{ $b->customer->name }}</h1>
                            <p class="mt-2 max-w-xl text-sm leading-relaxed text-violet-100/85">{{ __('muthowif.booking_show.page_subtitle') }}</p>
                            <p class="sr-only">{{ __('muthowif.booking_show.page_title') }}</p>
                            @if (filled($b->booking_code))
                                <p class="mt-3 inline-flex items-center rounded-lg border border-white/20 bg-white/10 px-2.5 py-1 font-mono text-xs font-medium text-white/95">{{ $b->booking_code }}</p>
                            @endif
                        </div>
                    </div>
                    <div class="flex shrink-0 flex-col gap-2 sm:items-end">
                        <a href="{{ route('muthowif.bookings.index') }}" class="inline-flex items-center gap-2 self-start rounded-xl border border-white/20 bg-white/10 px-4 py-2.5 text-sm font-semibold text-white backdrop-blur-sm transition hover:bg-white/20 sm:self-end">
                            <svg class="h-4 w-4" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true"><path fill-rule="evenodd" d="M12.79 5.23a.75.75 0 01-.02 1.06L8.832 10l3.938 3.71a.75.75 0 11-1.04 1.08l-4.5-4.25a.75.75 0 010-1.08l4.5-4.25a.75.75 0 011.06.02z" clip-rule="evenodd" /></svg>
                            {{ __('muthowif.booking_show.back_list') }}
                        </a>
                        <a href="{{ route('dashboard') }}" class="text-center text-xs font-semibold text-violet-200/90 underline-offset-2 hover:text-white hover:underline sm:text-right">{{ __('muthowif.bookings.back_dashboard') }}</a>
                    </div>
                </div>
            </div>

            {{-- Main booking card --}}
            <div class="overflow-hidden rounded-2xl border border-slate-200/90 bg-gradient-to-b from-white to-slate-50/80 shadow-sm ring-1 ring-slate-100/80">
                <div class="flex min-w-0">
                    <div class="w-1 shrink-0 {{ $accentClass }}" aria-hidden="true"></div>
                    <div class="min-w-0 flex-1 space-y-5 p-5 sm:p-6">
                        <div class="flex flex-wrap items-start justify-between gap-3">
                            <div class="min-w-0 space-y-1">
                                <p class="text-sm text-slate-500">{{ __('muthowif.booking_show.pilgrim') }}</p>
                                <p class="text-lg font-semibold text-slate-900">{{ $b->customer->name }}</p>
                                <p class="text-sm text-slate-600">{{ $b->customer->email }}</p>
                            </div>
                            <div class="flex flex-wrap items-center gap-2">
                                <span class="inline-flex rounded-full px-2.5 py-1 text-xs font-semibold ring-1 {{ $badgeClass }}">{{ $st->label() }}</span>
                                @if (in_array($st, [BookingStatus::Confirmed, BookingStatus::Completed], true))
                                    <span class="inline-flex rounded-full px-2.5 py-1 text-xs font-semibold ring-1 {{ match ($b->payment_status) {
                                        PaymentStatus::Paid => 'bg-emerald-100 text-emerald-950 ring-emerald-200/90',
                                        PaymentStatus::Refunded => 'bg-red-100 text-red-700 ring-red-200/80',
                                        default => 'bg-orange-50 text-orange-900 ring-orange-200/90',
                                    } }}">{{ __('muthowif.bookings.payment_prefix') }} {{ $b->payment_status->label() }}</span>
                                @endif
                            </div>
                        </div>

                        <div class="grid grid-cols-1 gap-4 text-sm sm:grid-cols-2">
                            <div class="rounded-xl border border-slate-100 bg-white/90 p-4">
                                <p class="flex items-center gap-1.5 text-slate-500">
                                    <svg class="h-4 w-4 shrink-0 text-brand-600" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true"><path fill-rule="evenodd" d="M5.75 2a.75.75 0 01.75.75V4h7V2.75a.75.75 0 011.5 0V4h.25A2.75 2.75 0 0118 6.75v8.5A2.75 2.75 0 0115.25 18H4.75A2.75 2.75 0 012 15.25v-8.5A2.75 2.75 0 014.75 4H5V2.75A.75.75 0 015.75 2zm-1 5.5c-.69 0-1.25.56-1.25 1.25v6.5c0 .69.56 1.25 1.25 1.25h10.5c.69 0 1.25-.56 1.25-1.25v-6.5c0-.69-.56-1.25-1.25-1.25H4.75z" clip-rule="evenodd" /></svg>
                                    {{ __('muthowif.booking_show.period') }}
                                </p>
                                <p class="mt-1.5 font-medium text-slate-900">
                                    {{ Carbon::parse($b->starts_on)->format('d/m/Y') }} – {{ Carbon::parse($b->ends_on)->format('d/m/Y') }}
                                </p>
                                <p class="mt-1 text-xs text-slate-500">{{ __('muthowif.booking_show.service_days', ['count' => $nights]) }}</p>
                            </div>
                            <div class="rounded-xl border border-slate-100 bg-white/90 p-4">
                                <p class="flex items-center gap-1.5 text-slate-500">
                                    <svg class="h-4 w-4 shrink-0 text-violet-600" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true"><path d="M10 8a3 3 0 100-6 3 3 0 000 6zM3.465 14.493a1.23 1.23 0 00.41 1.412A9.957 9.957 0 0010 18c2.31 0 4.438-.784 6.131-2.1.43-.333.604-.903.408-1.41a7.002 7.002 0 00-13.254-.005z" /></svg>
                                    {{ __('muthowif.booking_show.service') }}
                                </p>
                                <p class="mt-1.5 font-medium text-slate-900">{{ $b->service_type?->label() ?? '—' }}</p>
                                <p class="mt-1 text-xs text-slate-500">{{ __('muthowif.booking_show.pilgrim_count', ['count' => $b->pilgrim_count]) }}</p>
                            </div>
                        </div>

                        <div class="space-y-4 border-t border-slate-100 pt-5">
                            <h3 class="text-sm font-bold text-slate-900">{{ __('muthowif.booking_show.payment_heading') }}</h3>
                            <dl class="space-y-2 rounded-xl bg-slate-50/80 p-4 text-xs ring-1 ring-slate-100/80">
                                <div class="flex justify-between gap-4">
                                    <dt class="text-slate-600">{{ __('muthowif.booking_show.rate_per_day') }}</dt>
                                    <dd class="font-medium tabular-nums text-slate-900">Rp {{ $fmt($daily) }}</dd>
                                </div>
                                <div class="flex justify-between gap-4">
                                    <dt class="text-slate-600">{{ __('bookings.show.day_count') }}</dt>
                                    <dd class="font-medium text-slate-900">{{ __('bookings.show.days_count', ['count' => $nights]) }}</dd>
                                </div>
                                <div class="flex justify-between gap-4 border-t border-slate-200/60 pt-2">
                                    <dt class="text-slate-600">{{ __('muthowif.booking_show.subtotal_service') }}</dt>
                                    <dd class="font-medium tabular-nums text-slate-900">Rp {{ $fmt($serviceSubtotal) }}</dd>
                                </div>
                                @if ($addonLines->isNotEmpty())
                                    @foreach ($addonLines as $ad)
                                        <div class="flex justify-between gap-4">
                                            <dt class="text-slate-500">+ {{ $ad->name }}</dt>
                                            <dd class="font-medium tabular-nums text-slate-800">Rp {{ $fmt((float) $ad->price) }}</dd>
                                        </div>
                                    @endforeach
                                @endif
                                @if ($sameHotelLine > 0)
                                    <div class="flex justify-between gap-4">
                                        <dt class="text-slate-500">{{ __('bookings.show.same_hotel_label', ['nights' => $nights, 'days' => __('common.days')]) }}</dt>
                                        <dd class="font-medium tabular-nums text-slate-800">Rp {{ $fmt($sameHotelLine) }}</dd>
                                    </div>
                                @endif
                                @if ($transportLine > 0)
                                    <div class="flex justify-between gap-4">
                                        <dt class="text-slate-500">{{ __('bookings.show.transport_label') }}</dt>
                                        <dd class="font-medium tabular-nums text-slate-800">Rp {{ $fmt($transportLine) }}</dd>
                                    </div>
                                @endif
                                <div class="flex justify-between gap-4 border-t border-slate-200/60 pt-2">
                                    <dt class="text-red-600/80">{{ __('muthowif.booking_show.platform_fee_muthowif') }}</dt>
                                    <dd class="font-medium tabular-nums text-red-700/90">- Rp {{ $fmt($muthowifFee) }}</dd>
                                </div>
                                <div class="flex justify-between gap-4 border-t border-slate-200 pt-2.5 text-sm">
                                    <dt class="font-bold text-slate-900">{{ __('muthowif.booking_show.net_earning') }}</dt>
                                    <dd class="font-bold tabular-nums text-brand-700">Rp {{ $fmt($muthowifNet) }}</dd>
                                </div>
                            </dl>
                            <p class="text-[10px] leading-relaxed text-slate-500 italic">
                                * {{ __('bookings.invoice.gateway_fee_note') }} {{ __('muthowif.booking_show.refund_intro') }}
                            </p>
                        </div>

                        @include('bookings.partials.booking-documents', ['booking' => $b, 'routeName' => 'muthowif.bookings.documents.show'])

                        @if ($st === BookingStatus::Completed && $b->payment_status === PaymentStatus::Paid)
                            <p class="text-sm font-medium text-emerald-800">{{ __('muthowif.bookings.completed_notice') }}</p>
                        @endif

                        @if ($st === BookingStatus::Pending)
                            <div class="flex flex-col gap-2 border-t border-slate-100 pt-4 sm:flex-row sm:flex-wrap">
                                <form method="POST" action="{{ route('muthowif.bookings.confirm', $b) }}" class="flex-1 min-w-[10rem]">
                                    @csrf
                                    <button type="submit" class="inline-flex w-full items-center justify-center gap-2 rounded-xl bg-brand-600 px-4 py-2.5 text-sm font-semibold text-white shadow-md shadow-brand-600/20 transition hover:bg-brand-700">
                                        <svg class="h-4 w-4" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true"><path fill-rule="evenodd" d="M16.704 4.153a.75.75 0 01.143 1.052l-8 10.5a.75.75 0 01-1.127.075l-4.5-4.5a.75.75 0 011.06-1.06l3.894 3.893 7.48-9.817a.75.75 0 011.05-.143z" clip-rule="evenodd" /></svg>
                                        {{ __('muthowif.bookings.approve') }}
                                    </button>
                                </form>
                                <div class="flex-1 min-w-[10rem]">
                                    @include('muthowif.bookings.partials.reject-booking-form', ['booking' => $b, 'compact' => false])
                                </div>
                            </div>
                            @if (isset($peerRecommendTargets) && $peerRecommendTargets->isNotEmpty())
                                <div class="mt-4 rounded-xl border border-violet-200/90 bg-violet-50/40 p-4 ring-1 ring-violet-100/80">
                                    <p class="text-sm font-semibold text-slate-900">{{ __('muthowif.bookings.refer_heading') }}</p>
                                    <p class="mt-1 text-xs leading-relaxed text-slate-600">{{ __('muthowif.bookings.refer_hint') }}</p>
                                    <form method="POST" action="{{ route('muthowif.bookings.recommend-peer', $b) }}" class="mt-3 flex flex-col gap-2 sm:flex-row sm:items-stretch" onsubmit="return confirm(@json(__('muthowif.bookings.refer_confirm')));">
                                        @csrf
                                        <label class="min-w-0 flex-1">
                                            <span class="sr-only">{{ __('muthowif.bookings.refer_select_label') }}</span>
                                            <select
                                                name="target_muthowif_profile_id"
                                                required
                                                class="h-11 w-full rounded-xl border border-slate-200 bg-white px-3 text-sm font-medium text-slate-900 shadow-sm focus:border-brand-400 focus:outline-none focus:ring-2 focus:ring-brand-500/20"
                                            >
                                                <option value="">{{ __('muthowif.bookings.refer_select_placeholder') }}</option>
                                                @foreach ($peerRecommendTargets as $tp)
                                                    <option value="{{ $tp->id }}">{{ $tp->user?->name ?? '—' }}</option>
                                                @endforeach
                                            </select>
                                        </label>
                                        <button type="submit" class="inline-flex h-11 shrink-0 items-center justify-center gap-2 rounded-xl bg-violet-700 px-4 text-sm font-semibold text-white shadow-sm transition hover:bg-violet-800 sm:px-5">
                                            {{ __('muthowif.bookings.refer_submit') }}
                                        </button>
                                    </form>
                                </div>
                            @elseif (isset($peerRecommendTargets))
                                <p class="mt-3 text-xs text-slate-500">{{ __('muthowif.bookings.refer_no_candidates') }}</p>
                            @endif
                        @elseif ($st === BookingStatus::Confirmed && $b->payment_status === PaymentStatus::Pending)
                            <div class="border-t border-slate-100 pt-4">
                                <form method="POST" action="{{ route('muthowif.bookings.cancel', $b) }}" onsubmit="return confirm(@json(__('muthowif.bookings.cancel_unpaid_confirm')));">
                                    @csrf
                                    <button type="submit" class="inline-flex w-full items-center justify-center gap-2 rounded-xl border border-red-200 bg-red-50 px-4 py-2.5 text-sm font-semibold text-red-800 transition hover:bg-red-100/80 sm:w-auto">
                                        {{ __('muthowif.bookings.cancel_unpaid') }}
                                    </button>
                                </form>
                            </div>
                        @endif
                    </div>
                </div>
            </div>

            @if ($b->refundRequests->isNotEmpty())
                <div class="rounded-2xl border border-slate-200/90 bg-gradient-to-b from-white to-slate-50/80 p-5 shadow-sm ring-1 ring-slate-100 sm:p-6">
                    <h3 class="font-semibold text-slate-900">{{ __('muthowif.booking_show.refund_title') }}</h3>
                    <p class="mt-1 text-xs text-slate-600 leading-relaxed">{{ __('muthowif.booking_show.refund_intro') }}</p>
                    <ul class="mt-4 space-y-4 text-sm">
                        @foreach ($b->refundRequests as $req)
                            <li class="rounded-xl border border-slate-100 bg-white/90 p-4 space-y-2">
                                <div class="flex flex-wrap items-center gap-2">
                                    <span class="inline-flex rounded-full px-2 py-0.5 text-xs font-semibold ring-1 {{ match ($req->status) {
                                        BookingChangeRequestStatus::Pending => 'bg-amber-50 text-amber-900 ring-amber-200',
                                        BookingChangeRequestStatus::Approved => 'bg-emerald-50 text-emerald-900 ring-emerald-200',
                                        BookingChangeRequestStatus::Rejected => 'bg-red-50 text-red-900 ring-red-200',
                                    } }}">{{ $req->status->label() }}</span>
                                    <span class="text-xs text-slate-500">{{ $req->created_at?->timezone(config('app.timezone'))->format('d/m/Y H:i') }}</span>
                                </div>
                                <p class="text-slate-700">{{ __('muthowif.booking_show.refund_net_prefix') }} <strong>Rp {{ $fmt((float) $req->net_refund_customer) }}</strong></p>
                                <p class="text-xs text-slate-600">{{ __('muthowif.booking_show.refund_fees', [
                                    'platform' => $fmt((float) $req->refund_fee_platform),
                                    'muthowif' => $fmt((float) $req->refund_fee_muthowif),
                                ]) }}</p>
                                @if ($req->refund_bank_name || $req->refund_account_holder || $req->refund_account_number)
                                    <div class="rounded-lg border border-slate-100 bg-slate-50/80 px-3 py-2 text-xs text-slate-700">
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
                </div>
            @endif

            @if ($b->rescheduleRequests->isNotEmpty())
                <div class="rounded-2xl border border-slate-200/90 bg-gradient-to-b from-white to-slate-50/80 p-5 shadow-sm ring-1 ring-slate-100 sm:p-6">
                    <h3 class="font-semibold text-slate-900">{{ __('muthowif.booking_show.reschedule_title') }}</h3>
                    <ul class="mt-4 space-y-4 text-sm">
                        @foreach ($b->rescheduleRequests as $req)
                            <li class="rounded-xl border border-slate-100 bg-white/90 p-4 space-y-2">
                                <div class="flex flex-wrap items-center gap-2">
                                    <span class="inline-flex rounded-full px-2 py-0.5 text-xs font-semibold ring-1 {{ match ($req->status) {
                                        BookingChangeRequestStatus::Pending => 'bg-amber-50 text-amber-900 ring-amber-200',
                                        BookingChangeRequestStatus::Approved => 'bg-emerald-50 text-emerald-900 ring-emerald-200',
                                        BookingChangeRequestStatus::Rejected => 'bg-red-50 text-red-900 ring-red-200',
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
                                            <button type="submit" class="inline-flex w-full items-center justify-center rounded-lg bg-emerald-600 px-4 py-2 text-xs font-semibold text-white hover:bg-emerald-700" onclick="return confirm('{{ e(__('muthowif.booking_show.approve_reschedule_confirm')) }}');">
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
                </div>
            @endif
        </div>
