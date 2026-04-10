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
                        <h2 class="text-lg font-semibold text-slate-900">Booking saya</h2>
                        <p class="mt-1 text-sm text-slate-600 leading-relaxed">
                            Setelah muthowif <strong>menyetujui</strong>, lakukan pembayaran lewat <strong>Midtrans</strong> dari menu detail.
                            Permintaan <strong>Menunggu</strong> bisa dibatalkan sebelum disetujui.
                        </p>
                    </div>
                    <a href="{{ route('layanan.index') }}" class="inline-flex items-center justify-center rounded-xl bg-brand-600 px-4 py-2.5 text-sm font-semibold text-white hover:bg-brand-700">
                        Cari muthowif lagi
                    </a>
                </div>
            </div>

            @if ($bookings->isEmpty())
                <div class="rounded-2xl border border-dashed border-slate-300 bg-slate-50/80 p-10 text-center text-slate-600">
                    Belum ada booking. Cari muthowif dan ajukan dari halaman profil setelah memilih tanggal.
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
                                        alt="Foto {{ $booking->muthowifProfile->user->name }}"
                                        class="h-12 w-12 rounded-xl object-cover ring-1 ring-slate-200"
                                        loading="lazy"
                                    >
                                    <div class="min-w-0">
                                        <p class="font-semibold text-slate-900">{{ $booking->muthowifProfile->user->name }}</p>
                                    <p class="mt-1 text-sm text-slate-600">
                                        {{ Carbon::parse($booking->starts_on)->format('d/m/Y') }}
                                        –
                                        {{ Carbon::parse($booking->ends_on)->format('d/m/Y') }}
                                    </p>
                                    <p class="mt-1 text-sm text-slate-700">
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

                                    <div class="mt-3 flex flex-wrap items-center gap-2">
                                        <span class="inline-flex items-center rounded-full px-2.5 py-0.5 text-xs font-semibold ring-1 {{ $badgeClass }}">
                                            {{ $st->label() }}
                                        </span>
                                        @if (in_array($st, [BookingStatus::Confirmed, BookingStatus::Completed], true))
                                            <span class="inline-flex items-center rounded-full px-2.5 py-0.5 text-xs font-semibold ring-1 {{ $booking->payment_status === PaymentStatus::Paid ? 'bg-brand-50 text-brand-900 ring-brand-200' : 'bg-orange-50 text-orange-900 ring-orange-200' }}">
                                                {{ $booking->payment_status->label() }}
                                            </span>
                                        @endif
                                    </div>

                                    @if ($booking->payment_status === PaymentStatus::Paid && $booking->total_amount !== null)
                                        <p class="mt-2 text-xs text-slate-600">
                                            Total Rp {{ IndonesianNumber::formatThousands((string) (int) round((float) $booking->total_amount)) }}
                                        </p>
                                    @endif
                                    </div>
                                </div>

                                <div class="flex w-full flex-col gap-2 sm:w-56 sm:items-stretch">
                                    <a href="{{ route('bookings.show', $booking) }}" class="inline-flex justify-center items-center px-4 py-2.5 rounded-xl bg-slate-900 text-white text-sm font-semibold hover:bg-slate-800">
                                        Detail booking
                                    </a>

                                    @if ($st === BookingStatus::Confirmed && $booking->payment_status === PaymentStatus::Pending)
                                        <a href="{{ route('bookings.payment', $booking) }}" class="inline-flex justify-center items-center px-4 py-2.5 rounded-xl bg-brand-600 text-white text-sm font-semibold hover:bg-brand-700 text-center">
                                            Bayar Midtrans
                                        </a>
                                    @endif

                                    @if ($booking->payment_status === PaymentStatus::Paid)
                                        <a href="{{ route('bookings.invoice', $booking) }}" target="_blank" rel="noopener noreferrer" class="inline-flex justify-center items-center px-4 py-2.5 rounded-xl border border-slate-200 bg-white text-slate-700 text-sm font-semibold hover:bg-slate-50">
                                            Cetak invoice
                                        </a>
                                    @endif

                                    @if ($st === BookingStatus::Confirmed && $booking->payment_status === PaymentStatus::Paid)
                                        <a href="{{ route('bookings.show', $booking) }}" class="inline-flex justify-center items-center px-4 py-2.5 rounded-xl bg-emerald-600 text-white text-sm font-semibold hover:bg-emerald-700">
                                            Selesaikan + beri rating
                                        </a>
                                    @endif

                                    @if ($st === BookingStatus::Completed)
                                        <a href="{{ route('bookings.show', $booking) }}" class="inline-flex justify-center items-center px-4 py-2.5 rounded-xl border border-brand-200 bg-brand-50 text-brand-800 text-sm font-semibold hover:bg-brand-100">
                                            {{ $booking->review ? 'Lihat review Anda' : 'Beri rating & review' }}
                                        </a>
                                    @endif

                                    @if ($st === BookingStatus::Pending)
                                        <form method="POST" action="{{ route('bookings.cancel', $booking) }}" onsubmit="return confirm('Batalkan permintaan booking ini?');">
                                            @csrf
                                            <button type="submit" class="w-full text-sm font-semibold text-red-700 hover:text-red-800 px-3 py-2.5 rounded-xl border border-red-200 hover:bg-red-50">
                                                Batalkan
                                            </button>
                                        </form>
                                    @endif

                                    @if ($st === BookingStatus::Confirmed && $booking->payment_status === PaymentStatus::Pending)
                                        <form method="POST" action="{{ route('bookings.cancel', $booking) }}" onsubmit="return confirm('Batalkan booking yang sudah disetujui? (belum dibayar)');">
                                            @csrf
                                            <button type="submit" class="w-full text-sm font-semibold text-slate-600 hover:text-red-800 px-3 py-2.5 rounded-xl border border-slate-200 hover:bg-slate-50">
                                                Batalkan sebelum bayar
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
