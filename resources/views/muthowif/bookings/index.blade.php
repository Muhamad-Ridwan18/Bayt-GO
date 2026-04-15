@php
    use App\Enums\BookingStatus;
    use App\Enums\MuthowifServiceType;
    use App\Enums\PaymentStatus;
    use App\Support\IndonesianNumber;
    use Carbon\Carbon;
@endphp

<x-app-layout>
    <div class="py-8 sm:py-12">
        <div class="max-w-5xl mx-auto px-4 sm:px-6 lg:px-8 space-y-6">
            @if ($bookings->isEmpty())
                <div class="rounded-2xl border border-dashed border-slate-300 bg-slate-50/80 p-10 text-center text-slate-600">
                    Belum ada permintaan booking dari jamaah.
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
                                BookingStatus::Completed => 'bg-emerald-100 text-emerald-900 ring-emerald-200',
                                BookingStatus::Cancelled => 'bg-slate-100 text-slate-700 ring-slate-200',
                            };
                        @endphp
                        <li class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
                            <div class="flex flex-col lg:flex-row lg:items-start lg:justify-between gap-4">
                                <div class="min-w-0">
                                    <p class="font-semibold text-slate-900">{{ $booking->customer->name }}</p>
                                    <p class="mt-0.5 text-sm text-slate-500">{{ $booking->customer->email }}</p>
                                    <p class="mt-2 text-sm text-slate-700">
                                        {{ Carbon::parse($booking->starts_on)->format('d/m/Y') }}
                                        –
                                        {{ Carbon::parse($booking->ends_on)->format('d/m/Y') }}
                                    </p>
                                    <p class="mt-1 text-sm text-slate-600">
                                        {{ $booking->service_type?->label() ?? '—' }}
                                        · {{ $booking->pilgrim_count }} jemaah
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
                                                <li>+ Hotel sama ({{ $nights }} hari) (Rp {{ IndonesianNumber::formatThousands((string) (int) round($sameHotelLine)) }})</li>
                                            @endif
                                            @if ($transportLine > 0)
                                                <li>+ Transportasi (Rp {{ IndonesianNumber::formatThousands((string) (int) round($transportLine)) }})</li>
                                            @endif
                                        </ul>
                                    @endif
                                    <span class="mt-2 inline-flex items-center rounded-full px-2.5 py-0.5 text-xs font-semibold ring-1 {{ $badgeClass }}">
                                        {{ $st->label() }}
                                    </span>
                                    @if ($st === BookingStatus::Confirmed)
                                        <span class="mt-1.5 inline-flex items-center rounded-full px-2.5 py-0.5 text-xs font-semibold ring-1 {{ $booking->payment_status === PaymentStatus::Paid ? 'bg-brand-50 text-brand-900 ring-brand-200' : 'bg-orange-50 text-orange-900 ring-orange-200' }}">
                                            Pembayaran: {{ $booking->payment_status->label() }}
                                        </span>
                                    @elseif ($st === BookingStatus::Cancelled && in_array($booking->payment_status, [PaymentStatus::RefundPending, PaymentStatus::Refunded], true))
                                        <span class="mt-1.5 inline-flex items-center rounded-full px-2.5 py-0.5 text-xs font-semibold ring-1 {{ match ($booking->payment_status) {
                                            PaymentStatus::RefundPending => 'bg-amber-50 text-amber-900 ring-amber-200',
                                            PaymentStatus::Refunded => 'bg-slate-100 text-slate-700 ring-slate-200',
                                            default => 'bg-orange-50 text-orange-900 ring-orange-200',
                                        } }}">
                                            Pembayaran: {{ $booking->payment_status->label() }}
                                        </span>
                                    @elseif ($st === BookingStatus::Completed)
                                        @if ($booking->payment_status === PaymentStatus::Paid)
                                            <p class="mt-1 text-sm font-semibold text-emerald-800">Booking selesai. Invoice sudah dapat dicetak customer.</p>
                                        @endif
                                    @endif
                                </div>
                                <div class="flex flex-wrap gap-2 shrink-0">
                                    @if ($st === BookingStatus::Pending)
                                        <form method="POST" action="{{ route('muthowif.bookings.confirm', $booking) }}" class="inline">
                                            @csrf
                                            <button type="submit" class="inline-flex justify-center items-center px-4 py-2 rounded-xl bg-brand-600 text-white text-sm font-semibold shadow-sm hover:bg-brand-700">
                                                Setujui
                                            </button>
                                        </form>
                                        <form method="POST" action="{{ route('muthowif.bookings.cancel', $booking) }}" class="inline" onsubmit="return confirm('Tolak permintaan ini? Slot akan kembali kosong.');">
                                            @csrf
                                            <button type="submit" class="inline-flex justify-center items-center px-4 py-2 rounded-xl border border-slate-300 text-slate-800 text-sm font-semibold hover:bg-slate-50">
                                                Tolak
                                            </button>
                                        </form>
                                    @elseif ($st === BookingStatus::Confirmed && $booking->payment_status === PaymentStatus::Pending)
                                        <form method="POST" action="{{ route('muthowif.bookings.cancel', $booking) }}" class="inline" onsubmit="return confirm('Batalkan booking yang sudah dikonfirmasi (belum dibayar)? Slot akan dibuka kembali.');">
                                            @csrf
                                            <button type="submit" class="inline-flex justify-center items-center px-4 py-2 rounded-xl border border-red-200 text-red-800 text-sm font-semibold hover:bg-red-50">
                                                Batalkan (belum lunas)
                                            </button>
                                        </form>
                                    @elseif ($st === BookingStatus::Confirmed && $booking->payment_status === PaymentStatus::Paid)
                                        <div class="flex flex-col items-start gap-2">
                                            @if (($booking->pending_reschedule_requests_count ?? 0) > 0)
                                                <span class="inline-flex rounded-full px-2 py-0.5 text-xs font-semibold bg-amber-50 text-amber-900 ring-1 ring-amber-200">
                                                    Reschedule menunggu
                                                </span>
                                            @endif
                                            <a href="{{ route('muthowif.bookings.show', $booking) }}" class="inline-flex justify-center items-center px-4 py-2 rounded-xl bg-brand-600 text-white text-sm font-semibold shadow-sm hover:bg-brand-700">
                                                Detail & pengajuan
                                            </a>
                                        </div>
                                    @elseif ($st === BookingStatus::Cancelled && $booking->payment_status === PaymentStatus::RefundPending)
                                        <a href="{{ route('muthowif.bookings.show', $booking) }}" class="inline-flex justify-center items-center px-4 py-2 rounded-xl border border-amber-200 bg-amber-50 text-amber-900 text-sm font-semibold hover:bg-amber-100">
                                            Refund menunggu admin
                                        </a>
                                    @elseif ($st === BookingStatus::Cancelled && $booking->payment_status === PaymentStatus::Refunded)
                                        <a href="{{ route('muthowif.bookings.show', $booking) }}" class="inline-flex justify-center items-center px-4 py-2 rounded-xl border border-slate-300 text-slate-800 text-sm font-semibold hover:bg-slate-50">
                                            Riwayat refund
                                        </a>
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
