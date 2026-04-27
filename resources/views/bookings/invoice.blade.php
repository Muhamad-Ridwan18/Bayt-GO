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
    <meta name="color-scheme" content="light">
    <title>{{ __('bookings.invoice.title', ['app' => config('app.name')]) }}</title>
    @vite(['resources/css/app.css'])
    <style>
        @media print {
            .no-print { display: none !important; }
            body { background: white !important; }
            .invoice-shell { box-shadow: none !important; border-radius: 0 !important; border: none !important; }
            .invoice-header { -webkit-print-color-adjust: exact; print-color-adjust: exact; }
            @page { margin: 14mm; size: A4; }
        }
    </style>
</head>
<body class="min-h-screen bg-slate-100 font-sans antialiased text-slate-800">
    <div class="pointer-events-none fixed inset-0 bg-gradient-to-br from-brand-100/30 via-slate-50 to-slate-200/80 print:hidden" aria-hidden="true"></div>

    <div class="relative mx-auto max-w-3xl px-4 py-8 print:max-w-none print:px-0 print:py-0">
        <div class="no-print mb-6 flex flex-wrap items-center justify-between gap-3">
            <a
                href="{{ route('bookings.show', $booking) }}"
                class="inline-flex items-center gap-2 rounded-xl border border-slate-200 bg-white px-4 py-2.5 text-sm font-semibold text-slate-700 shadow-sm transition hover:border-slate-300 hover:bg-slate-50"
            >
                <svg class="h-4 w-4 text-brand-600" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
                    <path fill-rule="evenodd" d="M17 10a.75.75 0 01-.75.75H5.612l4.158 3.96a.75.75 0 11-1.04 1.08l-5.5-5.25a.75.75 0 010-1.08l5.5-5.25a.75.75 0 111.04 1.08L5.612 9.25H16.25A.75.75 0 0117 10z" clip-rule="evenodd" />
                </svg>
                {{ __('bookings.invoice.back') }}
            </a>
            <button
                type="button"
                onclick="window.print()"
                class="inline-flex items-center gap-2 rounded-xl bg-slate-900 px-4 py-2.5 text-sm font-semibold text-white shadow-lg shadow-slate-900/20 transition hover:bg-slate-800"
            >
                <svg class="h-4 w-4" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
                    <path fill-rule="evenodd" d="M5 2a1 1 0 011-1h8a1 1 0 011 1v2h3a1 1 0 011 1v6a1 1 0 01-1 1h-1v2a1 1 0 01-1 1H5a1 1 0 01-1-1v-2H3a1 1 0 01-1-1V6a1 1 0 011-1h3V2zm6 0H9v2h2V2zM4 8h12v4H4V8zm2 6v2h8v-2H6z" clip-rule="evenodd" />
                </svg>
                {{ __('bookings.invoice.print') }}
            </button>
        </div>

        <div class="invoice-shell overflow-hidden rounded-3xl border border-slate-200/90 bg-white shadow-2xl shadow-slate-900/10">
            {{-- Header --}}
            <header class="invoice-header bg-gradient-to-br from-brand-600 via-brand-600 to-brand-800 px-6 py-8 text-white sm:px-10 sm:py-10">
                <div class="flex flex-col gap-6 sm:flex-row sm:items-start sm:justify-between">
                    <div class="min-w-0">
                        <p class="text-xs font-semibold uppercase tracking-[0.2em] text-brand-100/90">{{ config('app.name') }}</p>
                        <h1 class="mt-2 text-3xl font-bold tracking-tight sm:text-4xl">{{ __('bookings.invoice.heading') }}</h1>
                        <p class="mt-2 max-w-md text-sm leading-relaxed text-brand-100/95">{{ __('bookings.invoice.subtitle') }}</p>
                    </div>
                    <div class="shrink-0 rounded-2xl bg-white/10 px-5 py-4 text-right ring-1 ring-white/20 backdrop-blur-sm">
                        <p class="text-xs font-semibold uppercase tracking-wide text-brand-100">{{ __('bookings.invoice.date') }}</p>
                        <p class="mt-1 text-xl font-bold tabular-nums text-white sm:text-2xl">
                            {{ $booking->paid_at?->timezone(config('app.timezone'))->format('d/m/Y H:i') ?? '—' }}
                        </p>
                    </div>
                </div>
            </header>

            {{-- Reference strip --}}
            @if (filled($booking->booking_code) || $payment)
                <div class="border-b border-slate-100 bg-slate-50/90 px-6 py-5 sm:px-10">
                    <div class="grid gap-4 sm:grid-cols-2">
                        @if (filled($booking->booking_code))
                            <div class="rounded-2xl border border-slate-200/80 bg-white p-4 shadow-sm">
                                <p class="text-[0.65rem] font-semibold uppercase tracking-wide text-slate-500">{{ __('bookings.invoice.booking_code') }}</p>
                                <p class="mt-1.5 font-mono text-base font-bold tracking-tight text-slate-900">{{ $booking->booking_code }}</p>
                            </div>
                        @endif
                        @if ($payment)
                            <div class="rounded-2xl border border-slate-200/80 bg-white p-4 shadow-sm">
                                <p class="text-[0.65rem] font-semibold uppercase tracking-wide text-slate-500">{{ __('bookings.invoice.order_no') }}</p>
                                <p class="mt-1.5 break-all font-mono text-xs font-medium leading-snug text-slate-800">{{ $payment->order_id }}</p>
                            </div>
                        @endif
                    </div>
                </div>
            @endif

            {{-- Parties --}}
            <div class="grid gap-8 border-b border-slate-100 px-6 py-8 sm:grid-cols-2 sm:px-10">
                <div>
                    <p class="text-[0.65rem] font-semibold uppercase tracking-wide text-slate-500">{{ __('bookings.invoice.pilgrim') }}</p>
                    <p class="mt-2 text-base font-semibold text-slate-900">{{ $booking->customer->name }}</p>
                    <p class="mt-1 text-sm text-slate-600">{{ $booking->customer->email }}</p>
                </div>
                <div>
                    <p class="text-[0.65rem] font-semibold uppercase tracking-wide text-slate-500">{{ __('bookings.invoice.muthowif') }}</p>
                    <p class="mt-2 text-base font-semibold text-slate-900">{{ $booking->muthowifProfile->user->name }}</p>
                </div>
            </div>

            {{-- Service --}}
            <div class="border-b border-slate-100 px-6 py-8 sm:px-10">
                <p class="text-[0.65rem] font-semibold uppercase tracking-wide text-slate-500">{{ __('bookings.invoice.service_period') }}</p>
                <p class="mt-2 text-lg font-semibold tabular-nums text-slate-900">
                    {{ Carbon::parse($booking->starts_on)->format('d/m/Y') }}
                    <span class="mx-1 font-normal text-slate-400">–</span>
                    {{ Carbon::parse($booking->ends_on)->format('d/m/Y') }}
                </p>
                <p class="mt-2 text-sm text-slate-600">
                    {{ $booking->service_type?->label() ?? __('common.em_dash') }}
                    <span class="text-slate-300">·</span>
                    {{ __('bookings.index.pilgrims_count', ['count' => $booking->pilgrim_count, 'pilgrims_word' => __('common.pilgrims')]) }}
                </p>
            </div>

            {{-- Amounts --}}
            <div class="bg-gradient-to-b from-slate-50/80 to-white px-6 py-8 sm:px-10">
                <p class="text-[0.65rem] font-semibold uppercase tracking-wide text-slate-500">{{ __('bookings.invoice.heading') }}</p>
                <dl class="mt-4 space-y-0 overflow-hidden rounded-2xl border border-slate-200/80 bg-white shadow-sm">
                    <div class="flex items-center justify-between gap-4 border-b border-slate-100 px-4 py-3.5 sm:px-5">
                        <dt class="text-sm text-slate-600">{{ __('bookings.invoice.subtotal') }}</dt>
                        <dd class="text-sm font-semibold tabular-nums text-slate-900">Rp {{ $fmt($base) }}</dd>
                    </div>
                    <div class="flex items-center justify-between gap-4 border-b border-slate-100 px-4 py-3.5 sm:px-5">
                        <dt class="text-sm text-slate-600">{{ __('bookings.invoice.platform_fee_pct') }}</dt>
                        <dd class="text-sm font-semibold tabular-nums text-slate-900">Rp {{ $fmt($customerPlatformFee) }}</dd>
                    </div>
                    <div class="flex items-center justify-between gap-4 bg-gradient-to-r from-brand-50/90 to-brand-50/40 px-4 py-4 sm:px-5">
                        <dt class="text-base font-bold text-slate-900">{{ __('bookings.invoice.total') }}</dt>
                        <dd class="text-xl font-bold tabular-nums text-brand-700 sm:text-2xl">Rp {{ $fmt($gross) }}</dd>
                    </div>
                </dl>

                @if ($payment)
                    <p class="mt-5 text-xs leading-relaxed text-slate-500">
                        {{ __('bookings.invoice.gateway_via') }}
                        @if ($payment->payment_type)
                            <span class="font-medium text-slate-600">({{ $payment->payment_type }})</span>
                        @endif
                        . {{ __('bookings.invoice.gateway_fee_note', ['pct' => (int) round(PlatformFee::RATE * 100)]) }}
                    </p>
                @endif
            </div>

            {{-- Footer --}}
            <footer class="border-t border-slate-100 bg-white px-6 py-8 text-center sm:px-10">
                <p class="text-sm font-medium text-slate-700">{{ __('bookings.invoice.thank_you', ['app' => config('app.name')]) }}</p>
                <p class="mx-auto mt-4 max-w-md text-xs leading-relaxed text-slate-400">{{ __('bookings.invoice.electronic_doc') }}</p>
            </footer>
        </div>
    </div>
</body>
</html>
