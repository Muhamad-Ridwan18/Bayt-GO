@php
    use App\Support\IndonesianNumber;
    use App\Support\PlatformFee;
    use Carbon\Carbon;

    $fmt = fn (float|int $n) => IndonesianNumber::formatThousands((string) (int) round((float) $n));
    $split = PlatformFee::split((float) $booking->resolvedAmountDue());
    $base = (float) ($split['base'] ?? 0.0);
    $customerPlatformFee = (float) ($split['customer_fee'] ?? 0.0);
    $gross = (float) ($split['customer_gross'] ?? 0.0);
@endphp
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Invoice — {{ config('app.name') }}</title>
    @vite(['resources/css/app.css'])
    <style>
        @media print {
            .no-print { display: none !important; }
            body { background: white !important; }
        }
    </style>
</head>
<body class="font-sans antialiased text-slate-800 bg-slate-100 min-h-screen">
    <div class="max-w-2xl mx-auto px-4 py-8 print:py-4">
        <div class="no-print mb-4 flex flex-wrap gap-3">
            <a href="{{ route('bookings.show', $booking) }}" class="text-sm font-semibold text-brand-700 hover:text-brand-800">← Detail booking</a>
            <button type="button" onclick="window.print()" class="text-sm font-semibold text-slate-700 hover:text-slate-900 underline">
                Cetak / PDF
            </button>
        </div>

        <div class="rounded-2xl border border-slate-200 bg-white p-8 shadow-sm print:shadow-none print:border-0">
            <div class="flex justify-between items-start gap-4 border-b border-slate-200 pb-6">
                <div>
                    <p class="text-xs font-bold uppercase tracking-wide text-brand-700">{{ config('app.name') }}</p>
                    <h1 class="mt-1 text-2xl font-bold text-slate-900">Invoice</h1>
                    <p class="mt-2 text-sm text-slate-500">Untuk pembayaran pendampingan umrah</p>
                </div>
                <div class="text-right text-sm">
                    @if (filled($booking->booking_code))
                        <p class="text-slate-500">Kode booking</p>
                        <p class="font-mono text-xs font-semibold text-slate-800">{{ $booking->booking_code }}</p>
                    @endif
                    @if ($payment)
                        <p class="{{ filled($booking->booking_code) ? 'mt-2' : '' }} text-slate-500">No. order pembayaran</p>
                        <p class="font-mono text-xs text-slate-800 break-all">{{ $payment->order_id }}</p>
                    @endif
                    <p class="mt-2 text-slate-500">Tanggal</p>
                    <p class="font-medium text-slate-900">{{ $booking->paid_at?->timezone(config('app.timezone'))->format('d/m/Y H:i') ?? '—' }}</p>
                </div>
            </div>

            <div class="mt-6 grid grid-cols-1 sm:grid-cols-2 gap-6 text-sm">
                <div>
                    <p class="text-xs font-semibold uppercase text-slate-500">Jamaah</p>
                    <p class="mt-1 font-medium text-slate-900">{{ $booking->customer->name }}</p>
                    <p class="text-slate-600">{{ $booking->customer->email }}</p>
                </div>
                <div>
                    <p class="text-xs font-semibold uppercase text-slate-500">Muthowif</p>
                    <p class="mt-1 font-medium text-slate-900">{{ $booking->muthowifProfile->user->name }}</p>
                </div>
            </div>

            <div class="mt-6 text-sm">
                <p class="text-xs font-semibold uppercase text-slate-500">Periode layanan</p>
                <p class="mt-1 font-medium text-slate-900">
                    {{ Carbon::parse($booking->starts_on)->format('d/m/Y') }} – {{ Carbon::parse($booking->ends_on)->format('d/m/Y') }}
                </p>
                <p class="text-slate-600">{{ $booking->service_type?->label() ?? '—' }} · {{ $booking->pilgrim_count }} jemaah</p>
            </div>

            <div class="mt-8 border-t border-slate-200 pt-6 space-y-2 text-sm">
                <div class="flex justify-between">
                    <span class="text-slate-600">Subtotal layanan</span>
                    <span class="font-medium text-slate-900">Rp {{ $fmt($base) }}</span>
                </div>
                <div class="flex justify-between">
                    <span class="text-slate-600">Biaya platform (7,5%)</span>
                    <span class="font-medium text-slate-900">Rp {{ $fmt($customerPlatformFee) }}</span>
                </div>
                <div class="flex justify-between border-t border-slate-200 pt-3 text-base">
                    <span class="font-semibold text-slate-900">Total dibayar</span>
                    <span class="font-bold text-brand-700">Rp {{ $fmt($gross) }}</span>
                </div>
                @if ($payment)
                    <p class="mt-3 text-xs text-slate-500 leading-relaxed">
                        Pembayaran melalui Midtrans
                        @if ($payment->payment_type)
                            ({{ $payment->payment_type }})
                        @endif
                        . Biaya platform untuk customer adalah {{ (int) round(PlatformFee::RATE * 100) }}% dari subtotal layanan.
                    </p>
                @endif
            </div>

            <p class="mt-8 text-center text-xs text-slate-400">Dokumen ini dibuat secara elektronik dan sah tanpa tanda tangan basah.</p>
        </div>
    </div>
</body>
</html>
