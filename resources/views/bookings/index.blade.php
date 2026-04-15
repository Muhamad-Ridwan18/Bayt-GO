@php
    use App\Enums\BookingStatus;
    use App\Enums\MuthowifServiceType;
    use App\Enums\PaymentStatus;
    use App\Support\IndonesianNumber;
    use Carbon\Carbon;
@endphp

<x-app-layout>
    <div class="py-8 sm:py-12 bg-slate-50/70">
        <div class="max-w-5xl mx-auto px-4 sm:px-6 lg:px-8 space-y-6">
            <div class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
                <div class="flex flex-col gap-3 sm:flex-row sm:items-start sm:justify-between">
                    <div>
                        <h2 class="text-lg font-semibold text-slate-900">{{ __('layanan.customer_bookings_heading') }}</h2>
                        <p class="mt-1 text-sm text-slate-600 leading-relaxed">
                            {!! __('layanan.customer_bookings_intro') !!}
                        </p>
                    </div>
                    <a href="{{ route('layanan.index') }}" class="inline-flex items-center justify-center rounded-xl bg-brand-600 px-4 py-2.5 text-sm font-semibold text-white hover:bg-brand-700">
                        {{ __('layanan.booking_search_again') }}
                    </a>
                </div>
            </div>

            @if ($bookings->isEmpty())
                <div class="rounded-2xl border border-dashed border-slate-300 bg-slate-50/80 p-10 text-center text-slate-600">
                    {{ __('layanan.booking_empty') }}
                </div>
            @else
                <ul class="space-y-4">
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
                                BookingStatus::Pending => 'bg-amber-100 text-amber-900 ring-amber-200',
                                BookingStatus::Confirmed => 'bg-emerald-100 text-emerald-900 ring-emerald-200',
                                BookingStatus::Completed => 'bg-brand-100 text-brand-900 ring-brand-200',
                                BookingStatus::Cancelled => 'bg-slate-100 text-slate-700 ring-slate-200',
                            };
                        @endphp
                        <li class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
                            <div class="flex flex-col gap-4 sm:flex-row sm:items-start sm:justify-between">
                                <div class="min-w-0 flex items-start gap-3">
                                    <img
                                        src="{{ route('layanan.photo', $booking->muthowifProfile) }}"
                                        alt="{{ __('bookings.index.photo_alt', ['name' => $booking->muthowifProfile->user->name]) }}"
                                        class="h-12 w-12 rounded-xl object-cover ring-1 ring-slate-200"
                                        loading="lazy"
                                    >
                                    <div class="min-w-0">
                                        <p class="font-semibold text-slate-900">{{ $booking->muthowifProfile->user->name }}</p>
                                        @if (filled($booking->booking_code))
                                            <p class="mt-0.5 font-mono text-xs font-medium text-slate-600">{{ $booking->booking_code }}</p>
                                        @endif
                                    <p class="mt-1 text-sm text-slate-600">
                                        {{ Carbon::parse($booking->starts_on)->format('d/m/Y') }}
                                        –
                                        {{ Carbon::parse($booking->ends_on)->format('d/m/Y') }}
                                    </p>
                                    <p class="mt-1 text-sm text-slate-700">
                                        {{ $booking->service_type?->label() ?? '—' }}
                                        · {{ __('bookings.index.pilgrims_count', ['count' => $booking->pilgrim_count, 'pilgrims_word' => __('common.pilgrims')]) }}
                                    </p>

                                    @if ($booking->service_type === MuthowifServiceType::PrivateJamaah && ! empty($booking->selected_add_on_ids))
                                        <ul class="mt-2 text-xs text-slate-600 space-y-0.5">
                                            @foreach ($booking->selected_add_on_ids as $aid)
                                                @if (isset($addonsById[$aid]))
                                                    @php $ad = $addonsById[$aid]; @endphp
                                                    <li>+ {{ $ad->name }} (Rp {{ IndonesianNumber::formatThousands((string) (int) $ad->price) }})</li>
                                                @endif
                                            @endforeach
                                        </ul>
                                    @endif
                                    @if ($sameHotelLine > 0 || $transportLine > 0)
                                        <ul class="mt-2 text-xs text-slate-600 space-y-0.5">
                                            @if ($sameHotelLine > 0)
                                                <li>{{ __('bookings.index.line_same_hotel', ['nights' => $nights, 'days' => __('common.days'), 'amount' => IndonesianNumber::formatThousands((string) (int) round($sameHotelLine))]) }}</li>
                                            @endif
                                            @if ($transportLine > 0)
                                                <li>{{ __('bookings.index.line_transport', ['amount' => IndonesianNumber::formatThousands((string) (int) round($transportLine))]) }}</li>
                                            @endif
                                        </ul>
                                    @endif

                                    <div class="mt-3 flex flex-wrap items-center gap-2">
                                        <span class="inline-flex items-center rounded-full px-2.5 py-0.5 text-xs font-semibold ring-1 {{ $badgeClass }}">
                                            {{ $st->label() }}
                                        </span>
                                        @if (in_array($st, [BookingStatus::Confirmed, BookingStatus::Completed], true) || ($st === BookingStatus::Cancelled && in_array($booking->payment_status, [PaymentStatus::RefundPending, PaymentStatus::Refunded], true)))
                                            <span class="inline-flex items-center rounded-full px-2.5 py-0.5 text-xs font-semibold ring-1 {{ match ($booking->payment_status) {
                                                PaymentStatus::Paid => 'bg-brand-50 text-brand-900 ring-brand-200',
                                                PaymentStatus::RefundPending => 'bg-amber-50 text-amber-900 ring-amber-200',
                                                PaymentStatus::Refunded => 'bg-slate-100 text-slate-700 ring-slate-200',
                                                default => 'bg-orange-50 text-orange-900 ring-orange-200',
                                            } }}">
                                                {{ $booking->payment_status->label() }}
                                            </span>
                                        @endif
                                    </div>

                                    @if ($booking->payment_status === PaymentStatus::Paid && $booking->total_amount !== null)
                                        <p class="mt-2 text-xs text-slate-600">
                                            {{ __('bookings.index.total_line', ['amount' => IndonesianNumber::formatThousands((string) (int) round((float) $booking->total_amount))]) }}
                                        </p>
                                    @endif
                                    </div>
                                </div>

                                <div class="flex w-full flex-col gap-2 sm:w-56 sm:items-stretch">
                                    <a href="{{ route('bookings.show', $booking) }}" class="inline-flex justify-center items-center px-4 py-2.5 rounded-xl bg-slate-900 text-white text-sm font-semibold hover:bg-slate-800">
                                        {{ __('bookings.index.detail') }}
                                    </a>

                                    @if ($st === BookingStatus::Confirmed && $booking->payment_status === PaymentStatus::Pending)
                                        <a href="{{ route('bookings.payment', $booking) }}" class="inline-flex justify-center items-center px-4 py-2.5 rounded-xl bg-brand-600 text-white text-sm font-semibold hover:bg-brand-700 text-center">
                                            {{ __('bookings.index.pay_midtrans') }}
                                        </a>
                                    @endif

                                    @if (in_array($booking->payment_status, [PaymentStatus::Paid, PaymentStatus::RefundPending, PaymentStatus::Refunded], true))
                                        <a href="{{ route('bookings.invoice', $booking) }}" target="_blank" rel="noopener noreferrer" class="inline-flex justify-center items-center px-4 py-2.5 rounded-xl border border-slate-200 bg-white text-slate-700 text-sm font-semibold hover:bg-slate-50">
                                            {{ __('bookings.index.print_invoice') }}
                                        </a>
                                    @endif

                                    @if ($st === BookingStatus::Confirmed && $booking->payment_status === PaymentStatus::Paid)
                                        <a href="{{ route('bookings.show', $booking) }}" class="inline-flex justify-center items-center px-4 py-2.5 rounded-xl bg-emerald-600 text-white text-sm font-semibold hover:bg-emerald-700">
                                            {{ __('bookings.index.complete_rate') }}
                                        </a>
                                    @endif

                                    @if ($st === BookingStatus::Completed)
                                        <a href="{{ route('bookings.show', $booking) }}" class="inline-flex justify-center items-center px-4 py-2.5 rounded-xl border border-brand-200 bg-brand-50 text-brand-800 text-sm font-semibold hover:bg-brand-100">
                                            {{ $booking->review ? __('bookings.index.view_review') : __('bookings.index.give_review') }}
                                        </a>
                                    @endif

                                    @if ($st === BookingStatus::Pending)
                                        <form method="POST" action="{{ route('bookings.cancel', $booking) }}" onsubmit="return confirm(@json(__('bookings.index.cancel_confirm')));">
                                            @csrf
                                            <button type="submit" class="w-full text-sm font-semibold text-red-700 hover:text-red-800 px-3 py-2.5 rounded-xl border border-red-200 hover:bg-red-50">
                                                {{ __('bookings.index.cancel') }}
                                            </button>
                                        </form>
                                    @endif

                                    @if ($st === BookingStatus::Confirmed && $booking->payment_status === PaymentStatus::Pending)
                                        <form method="POST" action="{{ route('bookings.cancel', $booking) }}" onsubmit="return confirm(@json(__('bookings.index.cancel_confirm_confirmed')));">
                                            @csrf
                                            <button type="submit" class="w-full text-sm font-semibold text-slate-600 hover:text-red-800 px-3 py-2.5 rounded-xl border border-slate-200 hover:bg-slate-50">
                                                {{ __('bookings.index.cancel_before_pay') }}
                                            </button>
                                        </form>
                                    @endif
                                </div>
                            </div>
                        </li>
                    @endforeach
                </ul>

                <div class="mt-6">
                    {{ $bookings->links() }}
                </div>
            @endif
        </div>
    </div>
</x-app-layout>
