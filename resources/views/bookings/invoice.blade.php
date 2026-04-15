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
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ __('bookings.invoice.title', ['app' => config('app.name')]) }}</title>
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
                    <h1 class="mt-1 text-2xl font-bold text-slate-900">{{ __('bookings.invoice.heading') }}</h1>
                    <p class="mt-2 text-sm text-slate-500">{{ __('bookings.invoice.subtitle') }}</p>
                </div>
                <div class="text-right text-sm">
                    @if (filled($booking->booking_code))
                        <p class="text-slate-500">{{ __('bookings.invoice.booking_code') }}</p>
                        <p class="font-mono text-xs font-semibold text-slate-800">{{ $booking->booking_code }}</p>
                    @endif
                    @if ($payment)
                        <p class="{{ filled($booking->booking_code) ? 'mt-2' : '' }} text-slate-500">{{ __('bookings.invoice.order_no') }}</p>
                        <p class="font-mono text-xs text-slate-800 break-all">{{ $payment->order_id }}</p>
                    @endif
                    <p class="mt-2 text-slate-500">{{ __('bookings.invoice.date') }}</p>
                    <p class="font-medium text-slate-900">{{ $booking->paid_at?->timezone(config('app.timezone'))->format('d/m/Y H:i') ?? '—' }}</p>
                </div>
            </div>

            <div class="mt-6 grid grid-cols-1 sm:grid-cols-2 gap-6 text-sm">
                <div>
                    <p class="text-xs font-semibold uppercase text-slate-500">{{ __('bookings.invoice.pilgrim') }}</p>
                    <p class="mt-1 font-medium text-slate-900">{{ $booking->customer->name }}</p>
                    <p class="text-slate-600">{{ $booking->customer->email }}</p>
                </div>
                <div>
                    <p class="text-xs font-semibold uppercase text-slate-500">{{ __('bookings.invoice.muthowif') }}</p>
                    <p class="mt-1 font-medium text-slate-900">{{ $booking->muthowifProfile->user->name }}</p>
                </div>
            </div>

            <div class="mt-6 text-sm">
                <p class="text-xs font-semibold uppercase text-slate-500">{{ __('bookings.invoice.service_period') }}</p>
                <p class="mt-1 font-medium text-slate-900">
                    {{ Carbon::parse($booking->starts_on)->format('d/m/Y') }} – {{ Carbon::parse($booking->ends_on)->format('d/m/Y') }}
                </p>
                <p class="text-slate-600">{{ $booking->service_type?->label() ?? __('common.em_dash') }} · {{ __('bookings.index.pilgrims_count', ['count' => $booking->pilgrim_count, 'pilgrims_word' => __('common.pilgrims')]) }}</p>
            </div>

            <div class="mt-8 border-t border-slate-200 pt-6 space-y-2 text-sm">
                <div class="flex justify-between">
                    <span class="text-slate-600">{{ __('bookings.invoice.subtotal') }}</span>
                    <span class="font-medium text-slate-900">Rp {{ $fmt($base) }}</span>
                </div>
                <div class="flex justify-between">
                    <span class="text-slate-600">{{ __('bookings.invoice.platform_fee_pct') }}</span>
                    <span class="font-medium text-slate-900">Rp {{ $fmt($customerPlatformFee) }}</span>
                </div>
                <div class="flex justify-between border-t border-slate-200 pt-3 text-base">
                    <span class="font-semibold text-slate-900">{{ __('bookings.invoice.total') }}</span>
                    <span class="font-bold text-brand-700">Rp {{ $fmt($gross) }}</span>
                </div>
                @if ($payment)
                    <p class="mt-3 text-xs text-slate-500 leading-relaxed">
                        {{ __('bookings.invoice.midtrans_via') }}
                        @if ($payment->payment_type)
                            ({{ $payment->payment_type }})
                        @endif
                        . {{ __('bookings.invoice.midtrans_fee_note', ['pct' => (int) round(PlatformFee::RATE * 100)]) }}
                    </p>
                @endif
            </div>

            <p class="mt-8 text-center text-xs text-slate-400">{{ __('bookings.invoice.electronic_doc') }}</p>
        </div>
    </div>
</body>
</html>
