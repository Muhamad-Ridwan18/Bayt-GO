@php
    use App\Enums\BookingStatus;
    use App\Enums\MuthowifServiceType;
    use App\Enums\PaymentStatus;
    use App\Support\IndonesianNumber;
    use Carbon\Carbon;
@endphp
        <div class="relative mx-auto max-w-5xl space-y-6 px-4 sm:px-6 lg:px-8">
            {{-- Header --}}
            <div class="relative overflow-hidden rounded-2xl bg-gradient-to-br from-slate-900 via-violet-950 to-brand-900 p-5 text-white shadow-lg shadow-violet-900/25 ring-1 ring-white/10 sm:rounded-3xl sm:p-6">
                <div class="pointer-events-none absolute inset-0 bg-[url('data:image/svg+xml,%3Csvg width=\'60\' height=\'60\' viewBox=\'0 0 60 60\' xmlns=\'http://www.w3.org/2000/svg\'%3E%3Cg fill=\'none\' fill-rule=\'evenodd\'%3E%3Cg fill=\'%23ffffff\' fill-opacity=\'0.05\'%3E%3Cpath d=\'M36 34v-4h-2v4h-4v2h4v4h2v-4h4v-2h-4zm0-30V0h-2v4h-4v2h4v4h2V6h4V4h-4zM6 34v-4H4v4H0v2h4v4h2v-4h4v-2H6zM6 4V0H4v4H0v2h4v4h2V6h4V4H6z\'/%3E%3C/g%3E%3C/g%3E%3C/svg%3E')] opacity-40"></div>
                <div class="pointer-events-none absolute -right-12 top-0 h-40 w-40 rounded-full bg-violet-500/20 blur-3xl"></div>
                <div class="relative flex flex-col gap-4 sm:flex-row sm:items-start sm:justify-between">
                    <div class="flex items-start gap-3">
                        <span class="flex h-11 w-11 shrink-0 items-center justify-center rounded-xl bg-white/15 ring-1 ring-white/20" aria-hidden="true">
                            <svg class="h-6 w-6" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor"><path fill-rule="evenodd" d="M4.5 2A1.5 1.5 0 003 3.5v13A1.5 1.5 0 004.5 18h11a1.5 1.5 0 001.5-1.5V7.621a1.5 1.5 0 00-.44-1.06l-4.12-4.122A1.5 1.5 0 0011.378 2H4.5zm2.25 8.5a.75.75 0 000 1.5h6.75a.75.75 0 000-1.5H6.75zm0 2.5a.75.75 0 000 1.5h6.75a.75.75 0 000-1.5H6.75z" clip-rule="evenodd" /></svg>
                        </span>
                        <div class="min-w-0">
                            <p class="text-[11px] font-semibold uppercase tracking-wider text-violet-200/90">{{ __('dashboard_muthowif.nav_bookings') }}</p>
                            <h1 class="mt-1 text-xl font-bold tracking-tight text-white sm:text-2xl">{{ __('muthowif.bookings.page_title') }}</h1>
                            <p class="mt-2 max-w-xl text-sm leading-relaxed text-violet-100/85">{{ __('muthowif.bookings.page_subtitle') }}</p>
                        </div>
                    </div>
                    <a href="{{ route('dashboard') }}" class="inline-flex shrink-0 items-center gap-2 self-start rounded-xl border border-white/20 bg-white/10 px-4 py-2.5 text-sm font-semibold text-white backdrop-blur-sm transition hover:bg-white/20">
                        <svg class="h-4 w-4" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true"><path fill-rule="evenodd" d="M9.293 2.293a1 1 0 011.414 0l7 7A1 1 0 0117 11h-1v6a1 1 0 01-1 1h-2a1 1 0 01-1-1v-3a1 1 0 00-1-1H9a1 1 0 00-1 1v3a1 1 0 01-1 1H5a1 1 0 01-1-1v-6H3a1 1 0 01-.707-1.707l7-7z" clip-rule="evenodd" /></svg>
                        {{ __('muthowif.bookings.back_dashboard') }}
                    </a>
                </div>
            </div>

            @if ($bookings->isEmpty())
                <div class="rounded-2xl border border-dashed border-slate-300/90 bg-white/80 px-6 py-12 text-center shadow-sm ring-1 ring-slate-100 sm:py-14">
                    <span class="mx-auto flex h-14 w-14 items-center justify-center rounded-2xl bg-slate-100 text-slate-500 ring-1 ring-slate-200/80" aria-hidden="true">
                        <svg class="h-7 w-7" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M19.5 14.25v-2.625a3.375 3.375 0 00-3.375-3.375h-1.5A1.125 1.125 0 0113.5 7.125v-1.5a3.375 3.375 0 00-3.375-3.375H8.25m0 12.75h7.5m-7.5 3H12M10.5 2.25H5.625c-.621 0-1.125.504-1.125 1.125v17.25c0 .621.504 1.125 1.125 1.125h12.75c.621 0 1.125-.504 1.125-1.125V11.25a9 9 0 00-9-9z" /></svg>
                    </span>
                    <p class="mt-4 text-base font-semibold text-slate-900">{{ __('muthowif.bookings.empty') }}</p>
                    <p class="mx-auto mt-2 max-w-md text-sm text-slate-600">{{ __('muthowif.bookings.empty_hint') }}</p>
                    <a href="{{ route('dashboard') }}" class="mt-6 inline-flex items-center gap-2 rounded-xl bg-brand-600 px-5 py-2.5 text-sm font-semibold text-white shadow-md shadow-brand-600/25 transition hover:bg-brand-700">
                        {{ __('muthowif.bookings.back_dashboard') }}
                    </a>
                </div>
            @else
                <ul class="space-y-3 sm:space-y-4">
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
                        <li class="overflow-hidden rounded-2xl border border-slate-200/90 bg-gradient-to-b from-white to-slate-50/80 shadow-sm ring-1 ring-slate-100/80 transition hover:border-violet-200/60 hover:shadow-md">
                            <div class="flex min-w-0">
                                <div class="w-1 shrink-0 {{ $accentClass }}" aria-hidden="true"></div>
                                <div class="min-w-0 flex-1 p-4 sm:p-5">
                                    <div class="flex flex-col gap-4 lg:flex-row lg:items-start lg:justify-between">
                                        <div class="min-w-0 space-y-3">
                                            <div class="flex flex-wrap items-start justify-between gap-2">
                                                <div class="min-w-0">
                                                    <p class="font-semibold text-slate-900">{{ $booking->customer->name }}</p>
                                                    <p class="mt-0.5 truncate text-sm text-slate-600">{{ $booking->customer->email }}</p>
                                                </div>
                                                <span class="inline-flex shrink-0 items-center rounded-full px-2.5 py-1 text-xs font-semibold ring-1 {{ $badgeClass }}">
                                                    {{ $st->label() }}
                                                </span>
                                            </div>

                                            @if (filled($booking->booking_code))
                                                <p class="inline-flex items-center rounded-lg border border-slate-200/80 bg-slate-50 px-2.5 py-1 font-mono text-xs font-medium text-slate-700">{{ $booking->booking_code }}</p>
                                            @endif

                                            <div class="flex flex-wrap gap-x-5 gap-y-2 text-sm text-slate-700">
                                                <span class="inline-flex items-center gap-1.5">
                                                    <svg class="h-4 w-4 shrink-0 text-brand-600" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true"><path fill-rule="evenodd" d="M5.75 2a.75.75 0 01.75.75V4h7V2.75a.75.75 0 011.5 0V4h.25A2.75 2.75 0 0118 6.75v8.5A2.75 2.75 0 0115.25 18H4.75A2.75 2.75 0 012 15.25v-8.5A2.75 2.75 0 014.75 4H5V2.75A.75.75 0 015.75 2zm-1 5.5c-.69 0-1.25.56-1.25 1.25v6.5c0 .69.56 1.25 1.25 1.25h10.5c.69 0 1.25-.56 1.25-1.25v-6.5c0-.69-.56-1.25-1.25-1.25H4.75z" clip-rule="evenodd" /></svg>
                                                    {{ Carbon::parse($booking->starts_on)->format('d/m/Y') }}
                                                    <span class="text-slate-400">–</span>
                                                    {{ Carbon::parse($booking->ends_on)->format('d/m/Y') }}
                                                </span>
                                                <span class="inline-flex items-center gap-1.5 text-slate-600">
                                                    <svg class="h-4 w-4 shrink-0 text-violet-600" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true"><path d="M10 8a3 3 0 100-6 3 3 0 000 6zM3.465 14.493a1.23 1.23 0 00.41 1.412A9.957 9.957 0 0010 18c2.31 0 4.438-.784 6.131-2.1.43-.333.604-.903.408-1.41a7.002 7.002 0 00-13.254-.005z" /></svg>
                                                    {{ $booking->service_type?->label() ?? '—' }}
                                                    <span class="text-slate-400">·</span>
                                                    {{ __('bookings.index.pilgrims_count', ['count' => $booking->pilgrim_count, 'pilgrims_word' => __('common.pilgrims')]) }}
                                                </span>
                                            </div>

                                            @if ($booking->service_type === MuthowifServiceType::PrivateJamaah && ! empty($booking->selected_add_on_ids))
                                                <ul class="space-y-1 rounded-xl border border-slate-100 bg-white/90 px-3 py-2 text-xs text-slate-600">
                                                    @foreach ($booking->selected_add_on_ids as $aid)
                                                        @if (isset($addonsById[$aid]))
                                                            @php $ad = $addonsById[$aid]; @endphp
                                                            <li class="flex justify-between gap-2"><span>+ {{ $ad->name }}</span><span class="shrink-0 font-medium tabular-nums">Rp {{ IndonesianNumber::formatThousands((string) (int) $ad->price) }}</span></li>
                                                        @endif
                                                    @endforeach
                                                </ul>
                                            @endif
                                            @if ($sameHotelLine > 0 || $transportLine > 0)
                                                <ul class="space-y-1 text-xs text-slate-600">
                                                    @if ($sameHotelLine > 0)
                                                        <li>{{ __('bookings.index.line_same_hotel', ['nights' => $nights, 'days' => __('common.days'), 'amount' => IndonesianNumber::formatThousands((string) (int) round($sameHotelLine))]) }}</li>
                                                    @endif
                                                    @if ($transportLine > 0)
                                                        <li>{{ __('bookings.index.line_transport', ['amount' => IndonesianNumber::formatThousands((string) (int) round($transportLine))]) }}</li>
                                                    @endif
                                                </ul>
                                            @endif

                                            @include('bookings.partials.booking-documents', ['booking' => $booking, 'routeName' => 'muthowif.bookings.documents.show', 'compact' => true])

                                            <div class="flex flex-wrap items-center gap-2">
                                                @if ($st === BookingStatus::Confirmed)
                                                    <span class="inline-flex items-center rounded-full px-2.5 py-1 text-xs font-semibold ring-1 {{ $booking->payment_status === PaymentStatus::Paid ? 'bg-brand-50 text-brand-900 ring-brand-200/90' : 'bg-orange-50 text-orange-900 ring-orange-200/90' }}">
                                                        {{ __('muthowif.bookings.payment_prefix') }} {{ $booking->payment_status->label() }}
                                                    </span>
                                                @elseif ($st === BookingStatus::Cancelled && in_array($booking->payment_status, [PaymentStatus::RefundPending, PaymentStatus::Refunded], true))
                                                    <span class="inline-flex items-center rounded-full px-2.5 py-1 text-xs font-semibold ring-1 {{ match ($booking->payment_status) {
                                                        PaymentStatus::RefundPending => 'bg-amber-50 text-amber-950 ring-amber-200/90',
                                                        PaymentStatus::Refunded => 'bg-red-100 text-red-700 ring-red-200/80',
                                                        default => 'bg-orange-50 text-orange-900 ring-orange-200/90',
                                                    } }}">
                                                        {{ __('muthowif.bookings.payment_prefix') }} {{ $booking->payment_status->label() }}
                                                    </span>
                                                @elseif ($st === BookingStatus::Completed)
                                                    @if ($booking->payment_status === PaymentStatus::Paid)
                                                        <p class="text-sm font-medium text-emerald-800">{{ __('muthowif.bookings.completed_notice') }}</p>
                                                    @endif
                                                @endif
                                            </div>
                                        </div>

                                        <div class="flex shrink-0 flex-col gap-2 border-t border-slate-100 pt-4 lg:w-56 lg:border-l lg:border-t-0 lg:pl-4 lg:pt-0">
                                            @unless ($st === BookingStatus::Confirmed && $booking->payment_status === PaymentStatus::Paid)
                                                <a href="{{ route('muthowif.bookings.show', $booking) }}" class="inline-flex w-full items-center justify-center gap-2 rounded-xl border border-slate-200 bg-white px-4 py-2.5 text-sm font-semibold text-slate-800 shadow-sm ring-1 ring-slate-100 transition hover:border-brand-200 hover:bg-brand-50/50 hover:text-brand-900">
                                                    <svg class="h-4 w-4 text-brand-600" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true"><path d="M10 12a2 2 0 100-4 2 2 0 000 4z" /><path fill-rule="evenodd" d="M.458 10C1.732 5.943 5.522 3 10 3s8.268 2.943 9.542 7c-1.274 4.057-5.064 7-9.542 7-4.478 0-8.268-2.943-9.542-7zM14 10a4 4 0 11-8 0 4 4 0 018 0z" clip-rule="evenodd" /></svg>
                                                    {{ __('muthowif.bookings.view_detail') }}
                                                </a>
                                            @endunless
                                            @if ($st === BookingStatus::Pending)
                                                <div class="flex flex-col gap-2">
                                                    <form method="POST" action="{{ route('muthowif.bookings.confirm', $booking) }}">
                                                        @csrf
                                                        <button type="submit" class="inline-flex w-full items-center justify-center gap-2 rounded-xl bg-brand-600 px-4 py-2.5 text-sm font-semibold text-white shadow-md shadow-brand-600/20 transition hover:bg-brand-700">
                                                            <svg class="h-4 w-4" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true"><path fill-rule="evenodd" d="M16.704 4.153a.75.75 0 01.143 1.052l-8 10.5a.75.75 0 01-1.127.075l-4.5-4.5a.75.75 0 011.06-1.06l3.894 3.893 7.48-9.817a.75.75 0 011.05-.143z" clip-rule="evenodd" /></svg>
                                                            {{ __('muthowif.bookings.approve') }}
                                                        </button>
                                                    </form>
                                                    <form method="POST" action="{{ route('muthowif.bookings.cancel', $booking) }}" onsubmit="return confirm(@json(__('muthowif.bookings.reject_confirm')));">
                                                        @csrf
                                                        <button type="submit" class="inline-flex w-full items-center justify-center gap-2 rounded-xl border border-slate-300 bg-white px-4 py-2.5 text-sm font-semibold text-slate-800 transition hover:bg-slate-50">
                                                            <svg class="h-4 w-4 text-slate-600" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true"><path fill-rule="evenodd" d="M4.293 4.293a1 1 0 011.414 0L10 8.586l4.293-4.293a1 1 0 111.414 1.414L11.414 10l4.293 4.293a1 1 0 01-1.414 1.414L10 11.414l-4.293 4.293a1 1 0 01-1.414-1.414L8.586 10 4.293 5.707a1 1 0 010-1.414z" clip-rule="evenodd" /></svg>
                                                            {{ __('muthowif.bookings.reject') }}
                                                        </button>
                                                    </form>
                                                </div>
                                            @elseif ($st === BookingStatus::Confirmed && $booking->payment_status === PaymentStatus::Pending)
                                                <form method="POST" action="{{ route('muthowif.bookings.cancel', $booking) }}" onsubmit="return confirm(@json(__('muthowif.bookings.cancel_unpaid_confirm')));">
                                                    @csrf
                                                    <button type="submit" class="inline-flex w-full items-center justify-center gap-2 rounded-xl border border-red-200 bg-red-50 px-4 py-2.5 text-sm font-semibold text-red-800 transition hover:bg-red-100/80">
                                                        <svg class="h-4 w-4 shrink-0" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true"><path fill-rule="evenodd" d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.213 2.98-1.742 2.98H4.42c-1.53 0-2.493-1.646-1.743-2.98l5.58-9.92zM11 13a1 1 0 11-2 0 1 1 0 012 0zm-1-8a1 1 0 00-1 1v3a1 1 0 002 0V6a1 1 0 00-1-1z" clip-rule="evenodd" /></svg>
                                                        {{ __('muthowif.bookings.cancel_unpaid') }}
                                                    </button>
                                                </form>
                                            @elseif ($st === BookingStatus::Confirmed && $booking->payment_status === PaymentStatus::Paid)
                                                @if (($booking->pending_reschedule_requests_count ?? 0) > 0)
                                                    <span class="inline-flex w-full justify-center rounded-full bg-amber-50 px-3 py-1 text-center text-xs font-semibold text-amber-950 ring-1 ring-amber-200/90">
                                                        {{ __('muthowif.bookings.reschedule_badge') }}
                                                    </span>
                                                @endif
                                                <a href="{{ route('muthowif.bookings.show', $booking) }}" class="inline-flex w-full items-center justify-center gap-2 rounded-xl bg-brand-600 px-4 py-2.5 text-sm font-semibold text-white shadow-md shadow-brand-600/20 transition hover:bg-brand-700">
                                                    {{ __('muthowif.bookings.detail_cta') }}
                                                    <svg class="h-4 w-4" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true"><path fill-rule="evenodd" d="M3 10a.75.75 0 01.75-.75h10.638L10.23 5.29a.75.75 0 111.04-1.08l5.5 5.25a.75.75 0 010 1.08l-5.5 5.25a.75.75 0 11-1.04-1.08l4.158-3.96H3.75A.75.75 0 013 10z" clip-rule="evenodd" /></svg>
                                                </a>
                                            @elseif ($st === BookingStatus::Cancelled && $booking->payment_status === PaymentStatus::RefundPending)
                                                <a href="{{ route('muthowif.bookings.show', $booking) }}" class="inline-flex w-full items-center justify-center gap-2 rounded-xl border border-amber-200 bg-amber-50 px-4 py-2.5 text-sm font-semibold text-amber-950 transition hover:bg-amber-100/80">
                                                    {{ __('muthowif.bookings.refund_pending_cta') }}
                                                </a>
                                            @elseif ($st === BookingStatus::Cancelled && $booking->payment_status === PaymentStatus::Refunded)
                                                <a href="{{ route('muthowif.bookings.show', $booking) }}" class="inline-flex w-full items-center justify-center gap-2 rounded-xl border border-slate-300 bg-white px-4 py-2.5 text-sm font-semibold text-slate-800 transition hover:bg-slate-50">
                                                    {{ __('muthowif.bookings.refund_history') }}
                                                </a>
                                            @endif
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </li>
                    @endforeach
                </ul>

                <div class="flex justify-center rounded-2xl border border-slate-200/90 bg-white/90 px-3 py-3 shadow-sm ring-1 ring-slate-100 sm:justify-end">
                    {{ $bookings->links() }}
                </div>
            @endif
        </div>
