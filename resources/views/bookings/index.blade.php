@php
    use App\Enums\BookingStatus;
    use App\Enums\MuthowifServiceType;
    use App\Enums\PaymentStatus;
    use App\Support\IndonesianNumber;
    use Carbon\Carbon;
@endphp

<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-slate-800 leading-tight">
            Booking saya
        </h2>
    </x-slot>

    <div class="py-8 sm:py-12">
        <div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8 space-y-6">
    <div class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
                <p class="text-sm text-slate-600">
            Setelah muthowif <strong>menyetujui</strong>, lakukan pembayaran lewat <strong>Xendit</strong> dari menu detail / Bayar Xendit.
                    Permintaan <strong>Menunggu</strong> bisa dibatalkan kapan saja sebelum disetujui; setelah disetujui Anda masih bisa batalkan sebelum membayar.
                </p>
                <a href="{{ route('layanan.index') }}" class="mt-3 inline-flex text-sm font-semibold text-brand-700 hover:text-brand-800">
                    Cari muthowif lagi →
                </a>
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
                            $badgeClass = match ($st) {
                                BookingStatus::Pending => 'bg-amber-100 text-amber-900 ring-amber-200',
                                BookingStatus::Confirmed => 'bg-emerald-100 text-emerald-900 ring-emerald-200',
                                    BookingStatus::Completed => 'bg-emerald-100 text-emerald-900 ring-emerald-200',
                                BookingStatus::Cancelled => 'bg-slate-100 text-slate-700 ring-slate-200',
                            };
                        @endphp
                        <li class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm flex flex-col sm:flex-row sm:items-start sm:justify-between gap-4">
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
                                <span class="mt-2 inline-flex items-center rounded-full px-2.5 py-0.5 text-xs font-semibold ring-1 {{ $badgeClass }}">
                                    {{ $st->label() }}
                                </span>
                                @if (in_array($st, [BookingStatus::Confirmed, BookingStatus::Completed], true))
                                    <span class="mt-1.5 inline-flex items-center rounded-full px-2.5 py-0.5 text-xs font-semibold ring-1 {{ $booking->payment_status === PaymentStatus::Paid ? 'bg-brand-50 text-brand-900 ring-brand-200' : 'bg-orange-50 text-orange-900 ring-orange-200' }}">
                                        {{ $booking->payment_status->label() }}
                                    </span>
                                    @if ($booking->payment_status === PaymentStatus::Paid && $booking->total_amount !== null)
                                        <p class="mt-1 text-xs text-slate-600">Total Rp {{ IndonesianNumber::formatThousands((string) (int) round((float) $booking->total_amount)) }}</p>
                                    @endif
                                @endif
                            </div>
                            <div class="flex flex-col gap-2 shrink-0 sm:items-end">
                                <a href="{{ route('bookings.show', $booking) }}" class="inline-flex justify-center items-center px-4 py-2 rounded-xl bg-slate-900 text-white text-sm font-semibold hover:bg-slate-800">
                                    Detail
                                </a>
                                @if ($st === BookingStatus::Confirmed && $booking->payment_status === PaymentStatus::Pending)
                                    <a href="{{ route('bookings.payment', $booking) }}" class="inline-flex justify-center items-center px-4 py-2 rounded-xl bg-brand-600 text-white text-sm font-semibold hover:bg-brand-700 text-center">
                                    Bayar Xendit
                                    </a>
                                @endif
                                    @if ($st === BookingStatus::Confirmed && $booking->payment_status === PaymentStatus::Paid)
                                        <form method="POST" action="{{ route('bookings.complete', $booking) }}" onsubmit="return confirm('Apakah layanan sudah selesai? Saldo muthowif akan ditambahkan.');">
                                            @csrf
                                            <button type="submit" class="inline-flex justify-center items-center px-4 py-2 rounded-xl bg-emerald-600 text-white text-sm font-semibold hover:bg-emerald-700">
                                                Selesaikan layanan
                                            </button>
                                        </form>
                                    @endif
                                @if ($st === BookingStatus::Pending)
                                    <form method="POST" action="{{ route('bookings.cancel', $booking) }}" onsubmit="return confirm('Batalkan permintaan booking ini?');">
                                        @csrf
                                        <button type="submit" class="w-full text-sm font-semibold text-red-700 hover:text-red-800 px-3 py-2 rounded-xl border border-red-200 hover:bg-red-50">
                                            Batalkan
                                        </button>
                                    </form>
                                @endif
                                @if ($st === BookingStatus::Confirmed && $booking->payment_status === PaymentStatus::Pending)
                                    <form method="POST" action="{{ route('bookings.cancel', $booking) }}" onsubmit="return confirm('Batalkan booking yang sudah disetujui? (belum dibayar)');">
                                        @csrf
                                        <button type="submit" class="w-full text-sm font-semibold text-slate-600 hover:text-red-800 px-3 py-2 rounded-xl border border-slate-200 hover:bg-slate-50">
                                            Batalkan sebelum bayar
                                        </button>
                                    </form>
                                @endif
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
