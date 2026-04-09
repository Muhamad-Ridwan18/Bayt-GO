@php
    use App\Enums\BookingStatus;
    use App\Enums\MuthowifServiceType;
    use App\Enums\PaymentStatus;
    use App\Support\IndonesianNumber;
    use Carbon\Carbon;

    $b = $booking;
    $st = $b->status;
    $nights = $b->billingNightsInclusive();
    $b->loadMissing(['muthowifProfile.services']);
    $service = $b->muthowifProfile?->services->firstWhere('type', $b->service_type);
    $daily = $service && $service->daily_price !== null ? (float) $service->daily_price : null;
    $baseSubtotal = $daily !== null ? $nights * $daily : 0.0;
    $addonLines = collect();
    if ($b->service_type === MuthowifServiceType::PrivateJamaah && ! empty($b->selected_add_on_ids)) {
        foreach ($b->selected_add_on_ids as $aid) {
            if (isset($addonsById[$aid])) {
                $addonLines->push($addonsById[$aid]);
            }
        }
    }
    $addonsSum = $addonLines->sum(fn ($a) => (float) $a->price);
    $sameHotelLine = 0.0;
    if ($b->with_same_hotel && $service && $service->same_hotel_price_per_day !== null) {
        $sameHotelLine = $nights * (float) $service->same_hotel_price_per_day;
    }
    $transportLine = 0.0;
    if ($b->with_transport && $service && $service->transport_price_flat !== null) {
        $transportLine = (float) $service->transport_price_flat;
    }
    $baseTotal = $b->resolvedAmountDue();
    $split = \App\Support\PlatformFee::split((float) $baseTotal);
    $customerTotal = (float) ($split['customer_gross'] ?? $baseTotal);
    $customerPlatformFee = (float) ($split['customer_fee'] ?? 0.0);
    $muthowifNet = (float) ($split['muthowif_net'] ?? 0.0);
    $fmt = fn (float $n) => IndonesianNumber::formatThousands((string) (int) round($n));
@endphp

<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-col gap-1 sm:flex-row sm:items-center sm:justify-between">
            <h2 class="font-semibold text-xl text-slate-800 leading-tight">
                Detail booking
            </h2>
            <a href="{{ route('bookings.index') }}" class="text-sm font-semibold text-brand-700 hover:text-brand-800">
                ← Kembali ke daftar
            </a>
        </div>
    </x-slot>

    <div class="py-8 sm:py-12">
        <div class="max-w-2xl mx-auto px-4 sm:px-6 lg:px-8 space-y-6">
            @if (session('status'))
                <div class="rounded-xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-900">
                    {{ session('status') }}
                </div>
            @endif
            @if (session('error'))
                <div class="rounded-xl border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-900">
                    {{ session('error') }}
                </div>
            @endif

            <div class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm space-y-4">
                <div>
                    <p class="text-sm text-slate-500">Muthowif</p>
                    <p class="text-lg font-semibold text-slate-900">{{ $b->muthowifProfile->user->name }}</p>
                </div>
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 text-sm">
                    <div>
                        <p class="text-slate-500">Periode</p>
                        <p class="font-medium text-slate-900 mt-0.5">
                            {{ Carbon::parse($b->starts_on)->format('d/m/Y') }} – {{ Carbon::parse($b->ends_on)->format('d/m/Y') }}
                        </p>
                        <p class="text-xs text-slate-500 mt-1">{{ $nights }} hari layanan</p>
                    </div>
                    <div>
                        <p class="text-slate-500">Layanan</p>
                        <p class="font-medium text-slate-900 mt-0.5">{{ $b->service_type?->label() ?? '—' }}</p>
                        <p class="text-xs text-slate-500 mt-1">{{ $b->pilgrim_count }} jemaah</p>
                    </div>
                </div>
                <div class="flex flex-wrap gap-2">
                    <span class="inline-flex rounded-full px-2.5 py-0.5 text-xs font-semibold ring-1 {{ match ($st) {
                        BookingStatus::Pending => 'bg-amber-100 text-amber-900 ring-amber-200',
                        BookingStatus::Confirmed => 'bg-emerald-100 text-emerald-900 ring-emerald-200',
                        BookingStatus::Completed => 'bg-emerald-100 text-emerald-900 ring-emerald-200',
                        BookingStatus::Cancelled => 'bg-slate-100 text-slate-700 ring-slate-200',
                    } }}">{{ $st->label() }}</span>
                    @if (in_array($st, [BookingStatus::Confirmed, BookingStatus::Completed], true))
                        <span class="inline-flex rounded-full px-2.5 py-0.5 text-xs font-semibold ring-1 {{ $b->payment_status === PaymentStatus::Paid ? 'bg-brand-100 text-brand-900 ring-brand-200' : 'bg-orange-50 text-orange-900 ring-orange-200' }}">
                            {{ $b->payment_status->label() }}
                        </span>
                    @endif
                </div>
            </div>

            @if (in_array($st, [BookingStatus::Confirmed, BookingStatus::Completed], true))
                <div class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
                    <h3 class="font-semibold text-slate-900">Rincian pembayaran</h3>
                    <p class="mt-1 text-xs text-slate-500">Total dihitung dari tarif harian × jumlah hari + add-on + opsi tambahan.</p>
                    <dl class="mt-4 space-y-3 text-sm">
                        <div class="flex justify-between gap-4">
                            <dt class="text-slate-600">Tarif / hari</dt>
                            <dd class="font-medium text-slate-900 text-right">
                                @if ($daily !== null)
                                    Rp {{ $fmt($daily) }}
                                @else
                                    —
                                @endif
                            </dd>
                        </div>
                        <div class="flex justify-between gap-4">
                            <dt class="text-slate-600">Jumlah hari</dt>
                            <dd class="font-medium text-slate-900">{{ $nights }} hari</dd>
                        </div>
                        <div class="flex justify-between gap-4 border-t border-slate-100 pt-3">
                            <dt class="text-slate-600">Subtotal layanan</dt>
                            <dd class="font-medium text-slate-900">Rp {{ $fmt($baseSubtotal) }}</dd>
                        </div>
                        @if ($addonLines->isNotEmpty())
                            @foreach ($addonLines as $ad)
                                <div class="flex justify-between gap-4">
                                    <dt class="text-slate-600">+ {{ $ad->name }}</dt>
                                    <dd class="font-medium text-slate-900">Rp {{ $fmt((float) $ad->price) }}</dd>
                                </div>
                            @endforeach
                        @endif
                        @if ($sameHotelLine > 0)
                            <div class="flex justify-between gap-4">
                                <dt class="text-slate-600">+ Hotel sama ({{ $nights }} hari)</dt>
                                <dd class="font-medium text-slate-900">Rp {{ $fmt($sameHotelLine) }}</dd>
                            </div>
                        @endif
                        @if ($transportLine > 0)
                            <div class="flex justify-between gap-4">
                                <dt class="text-slate-600">+ Transportasi</dt>
                                <dd class="font-medium text-slate-900">Rp {{ $fmt($transportLine) }}</dd>
                            </div>
                        @endif
                        <div class="flex justify-between gap-4 border-t border-slate-100 pt-3">
                            <dt class="text-slate-600">Biaya platform (7,5%)</dt>
                            <dd class="font-medium text-slate-900">Rp {{ $fmt($customerPlatformFee) }}</dd>
                        </div>
                        <div class="flex justify-between gap-4 border-t border-slate-200 pt-3 text-base">
                            <dt class="font-semibold text-slate-900">Total dibayar (customer)</dt>
                            <dd class="font-bold text-brand-700">Rp {{ $fmt($customerTotal) }}</dd>
                        </div>
                        {{-- <div class="flex justify-between gap-4 pt-2 text-sm">
                            <dt class="text-slate-600">Diterima muthowif (net)</dt>
                            <dd class="font-medium text-slate-900">Rp {{ $fmt($muthowifNet) }}</dd>
                        </div> --}}
                    </dl>

                    @if ($b->isAwaitingPayment())
                        @php $paymentQuery = request()->query('payment'); @endphp
                        @if ($paymentQuery === 'success')
                            <p class="mt-4 text-xs text-slate-600 leading-relaxed">
                                Terima kasih! Kami sedang menunggu konfirmasi pembayaran dari <strong>Midtrans</strong>.
                                Jika invoice sudah berstatus lunas, tombol <em>cetak invoice</em> akan muncul otomatis.
                            </p>
                        @else
                            <p class="mt-4 text-xs text-slate-600 leading-relaxed">
                                Pembayaran aman lewat <strong>Midtrans</strong>. Total yang Anda bayarkan sudah termasuk biaya platform.
                                Biaya platform untuk customer adalah {{ (int) round(\App\Support\PlatformFee::RATE * 100) }}% dari subtotal layanan.
                            </p>
                        @endif

                        <a href="{{ route('bookings.payment', $b) }}" class="mt-4 inline-flex justify-center items-center gap-2 rounded-xl bg-brand-600 px-6 py-3 text-sm font-semibold text-white shadow-md hover:bg-brand-700">
                            Bayar dengan Midtrans
                        </a>
                    @elseif ($b->isPaid() && $b->paid_at)
                        <p class="mt-4 text-sm text-emerald-800">
                            Dibayar pada {{ $b->paid_at->timezone(config('app.timezone'))->format('d/m/Y H:i') }}.
                        </p>
                        <a href="{{ route('bookings.invoice', $b) }}" target="_blank" rel="noopener noreferrer" class="mt-3 inline-flex items-center gap-2 text-sm font-semibold text-brand-700 hover:text-brand-800">
                            Cetak invoice
                            <svg class="h-4 w-4" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true"><path fill-rule="evenodd" d="M5 4v3H4a2 2 0 00-2 2v3a2 2 0 002 2h1v2a2 2 0 002 2h6a2 2 0 002-2v-2h1a2 2 0 002-2V9a2 2 0 00-2-2h-1V4a2 2 0 00-2-2H7a2 2 0 00-2 2zm8 0H7v3h6V4zm0 8H7v4h6v-4z" clip-rule="evenodd" /></svg>
                        </a>
                    @endif
                </div>
            @endif

            @if ($st === BookingStatus::Pending || $b->isAwaitingPayment())
                <form method="POST" action="{{ route('bookings.cancel', $b) }}" class="rounded-2xl border border-red-100 bg-red-50/50 p-4" onsubmit="return confirm('Batalkan booking ini?');">
                    @csrf
                    <p class="text-sm text-red-900 font-medium">Batalkan booking</p>
                    <p class="mt-1 text-xs text-red-800/90">Slot akan dibuka kembali untuk muthowif lain jika belum dibayar.</p>
                    <button type="submit" class="mt-3 text-sm font-semibold text-red-700 hover:text-red-900 underline">
                        Ya, batalkan
                    </button>
                </form>
            @endif
        </div>
    </div>
</x-app-layout>

@if ($b->isAwaitingPayment())
    <script>
        setInterval(() => {
            window.location.reload();
        }, 10000);
    </script>
@endif
