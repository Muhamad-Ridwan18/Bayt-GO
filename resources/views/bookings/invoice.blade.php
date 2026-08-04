@php
    use App\Support\IndonesianNumber;
    use App\Support\PlatformFee;
    use Carbon\Carbon;

    $fmt = fn (float|int $n) => IndonesianNumber::formatThousands((string) (int) round((float) $n));
    $isCompany = $booking->customer?->isCompanyCustomer() ?? false;
    $split = PlatformFee::split((float) $booking->resolvedAmountDue(), $isCompany);
    $base = (float) ($split['base'] ?? 0.0);
    $customerPlatformFee = (float) ($split['customer_fee'] ?? 0.0);
    $gross = (float) ($split['customer_gross'] ?? 0.0);
    $isSupport = $booking->isSupport();
    $packageLabel = $booking->package_name_snapshot
        ?? $booking->supportPackage?->name
        ?? null;
    $scheduleLabel = $isSupport
        ? ($booking->starts_at
            ? $booking->starts_at->timezone(config('app.timezone'))->format('d/m/Y H:i')
            : ($booking->starts_on?->format('d/m/Y') ?? '—'))
        : (Carbon::parse($booking->starts_on)->format('d/m/Y').' – '.Carbon::parse($booking->ends_on)->format('d/m/Y'));
    $subtitle = $isSupport
        ? __('bookings.invoice.subtitle_support')
        : __('bookings.invoice.subtitle');
    $docRef = filled($booking->booking_code) ? $booking->booking_code : (string) $booking->getKey();
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
            .invoice-header,
            .invoice-paid,
            .invoice-total-row { -webkit-print-color-adjust: exact; print-color-adjust: exact; }
            @page { margin: 12mm; size: A4; }
        }
    </style>
</head>
<body class="min-h-screen bg-[#F4F7F6] font-sans antialiased text-slate-800">
    <div class="pointer-events-none fixed inset-0 print:hidden" aria-hidden="true">
        <div class="absolute inset-0 bg-[radial-gradient(ellipse_at_top,_rgba(13,148,136,0.12),_transparent_55%),radial-gradient(ellipse_at_bottom_right,_rgba(197,160,89,0.10),_transparent_45%)]"></div>
        <div class="absolute inset-0 opacity-[0.35]" style="background-image: linear-gradient(rgba(15,23,42,0.03) 1px, transparent 1px), linear-gradient(90deg, rgba(15,23,42,0.03) 1px, transparent 1px); background-size: 28px 28px;"></div>
    </div>

    <x-page-container class="relative py-8 print:max-w-none print:px-0 print:py-0 sm:py-10">
        <div class="no-print mb-6 flex flex-wrap items-center justify-between gap-3">
            <a
                href="{{ route('bookings.show', $booking) }}"
                class="inline-flex items-center gap-2 rounded-xl border border-slate-200/90 bg-white/90 px-4 py-2.5 text-sm font-semibold text-slate-700 shadow-sm backdrop-blur transition hover:border-slate-300 hover:bg-white"
            >
                <svg class="h-4 w-4 text-brand-600" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
                    <path fill-rule="evenodd" d="M17 10a.75.75 0 01-.75.75H5.612l4.158 3.96a.75.75 0 11-1.04 1.08l-5.5-5.25a.75.75 0 010-1.08l5.5-5.25a.75.75 0 111.04 1.08L5.612 9.25H16.25A.75.75 0 0117 10z" clip-rule="evenodd" />
                </svg>
                {{ __('bookings.invoice.back') }}
            </a>
            <button
                type="button"
                onclick="window.print()"
                class="inline-flex items-center gap-2 rounded-xl bg-baytgo px-4 py-2.5 text-sm font-semibold text-white shadow-lg shadow-baytgo/25 transition hover:bg-baytgo-800"
            >
                <svg class="h-4 w-4 text-gold-light" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
                    <path fill-rule="evenodd" d="M5 2a1 1 0 011-1h8a1 1 0 011 1v2h3a1 1 0 011 1v6a1 1 0 01-1 1h-1v2a1 1 0 01-1 1H5a1 1 0 01-1-1v-2H3a1 1 0 01-1-1V6a1 1 0 011-1h3V2zm6 0H9v2h2V2zM4 8h12v4H4V8zm2 6v2h8v-2H6z" clip-rule="evenodd" />
                </svg>
                {{ __('bookings.invoice.print') }}
            </button>
        </div>

        <div class="invoice-shell overflow-hidden rounded-[1.75rem] border border-slate-200/80 bg-white shadow-[0_25px_60px_-28px_rgba(26,61,52,0.45)]">
            <header class="invoice-header relative overflow-hidden bg-gradient-to-br from-baytgo via-[#1f4a40] to-brand-800 px-6 py-8 text-white sm:px-10 sm:py-10">
                <div class="pointer-events-none absolute -right-16 -top-20 h-56 w-56 rounded-full bg-gold/20 blur-2xl" aria-hidden="true"></div>
                <div class="pointer-events-none absolute -bottom-24 left-10 h-48 w-48 rounded-full bg-brand-400/20 blur-3xl" aria-hidden="true"></div>
                <div class="pointer-events-none absolute inset-x-0 bottom-0 h-px bg-gradient-to-r from-transparent via-gold/50 to-transparent" aria-hidden="true"></div>

                <div class="relative flex flex-col gap-8 lg:flex-row lg:items-start lg:justify-between">
                    <div class="min-w-0">
                        <div class="flex items-center gap-3">
                            @if (! empty($logoUrl))
                                <img src="{{ $logoUrl }}" alt="{{ config('app.name') }}" class="h-11 w-auto max-w-[140px] rounded-lg bg-white/10 object-contain p-1.5 ring-1 ring-white/15">
                            @else
                                <div class="flex h-11 w-11 items-center justify-center rounded-xl bg-white/10 text-sm font-bold tracking-wide text-gold-light ring-1 ring-white/15">
                                    {{ strtoupper(substr((string) config('app.name', 'BG'), 0, 2)) }}
                                </div>
                            @endif
                            <div>
                                <p class="text-[11px] font-semibold uppercase tracking-[0.22em] text-brand-100/90">{{ config('app.name') }}</p>
                                <p class="mt-0.5 text-xs text-white/70">{{ __('bookings.invoice.doc_id') }} · {{ $docRef }}</p>
                            </div>
                        </div>

                        <h1 class="mt-6 text-3xl font-bold tracking-tight sm:text-4xl">{{ __('bookings.invoice.heading') }}</h1>
                        <p class="mt-2 max-w-md text-sm leading-relaxed text-brand-100/95">{{ $subtitle }}</p>

                        <div class="mt-5 inline-flex items-center gap-2 rounded-full bg-emerald-400/15 px-3 py-1.5 text-xs font-semibold text-emerald-100 ring-1 ring-emerald-300/30 invoice-paid">
                            <span class="h-1.5 w-1.5 rounded-full bg-emerald-300"></span>
                            {{ __('bookings.invoice.paid_badge') }}
                        </div>
                    </div>

                    <div class="shrink-0 space-y-3 lg:w-64">
                        <div class="rounded-2xl bg-white/10 px-5 py-4 ring-1 ring-white/15 backdrop-blur-sm">
                            <p class="text-[11px] font-semibold uppercase tracking-wide text-brand-100/90">{{ __('bookings.invoice.date') }}</p>
                            <p class="mt-1.5 text-xl font-bold tabular-nums tracking-tight text-white">
                                {{ $booking->paid_at?->timezone(config('app.timezone'))->format('d/m/Y') ?? '—' }}
                            </p>
                            <p class="mt-0.5 text-sm tabular-nums text-white/70">
                                {{ $booking->paid_at?->timezone(config('app.timezone'))->format('H:i') ?? '' }}
                            </p>
                        </div>
                        <div class="rounded-2xl bg-black/15 px-5 py-3.5 ring-1 ring-white/10">
                            <p class="text-[11px] font-semibold uppercase tracking-wide text-gold-light/90">{{ __('bookings.invoice.total') }}</p>
                            <p class="mt-1 text-2xl font-bold tabular-nums text-white">Rp {{ $fmt($gross) }}</p>
                        </div>
                    </div>
                </div>
            </header>

            @if (filled($booking->booking_code) || $payment)
                <div class="border-b border-slate-100 bg-gradient-to-r from-slate-50 via-white to-brand-50/40 px-6 py-5 sm:px-10">
                    <div class="grid gap-3 sm:grid-cols-2">
                        @if (filled($booking->booking_code))
                            <div class="rounded-2xl border border-slate-200/70 bg-white px-4 py-3.5 shadow-sm">
                                <p class="text-[0.65rem] font-semibold uppercase tracking-wide text-slate-500">{{ __('bookings.invoice.booking_code') }}</p>
                                <p class="mt-1.5 font-mono text-base font-bold tracking-tight text-baytgo">{{ $booking->booking_code }}</p>
                            </div>
                        @endif
                        @if ($payment)
                            <div class="rounded-2xl border border-slate-200/70 bg-white px-4 py-3.5 shadow-sm">
                                <p class="text-[0.65rem] font-semibold uppercase tracking-wide text-slate-500">{{ __('bookings.invoice.order_no') }}</p>
                                <p class="mt-1.5 break-all font-mono text-xs font-medium leading-snug text-slate-700">{{ $payment->order_id }}</p>
                            </div>
                        @endif
                    </div>
                </div>
            @endif

            <div class="grid gap-0 border-b border-slate-100 sm:grid-cols-2">
                <div class="border-b border-slate-100 px-6 py-7 sm:border-b-0 sm:border-r sm:px-10">
                    <p class="text-[0.65rem] font-semibold uppercase tracking-wide text-brand-700">{{ __('bookings.invoice.billed_to') }}</p>
                    <p class="mt-3 text-lg font-semibold text-slate-900">{{ $booking->customer?->name }}</p>
                    <p class="mt-1 text-sm text-slate-600">{{ $booking->customer?->email }}</p>
                    @if (filled($booking->customer?->phone))
                        <p class="mt-1 text-sm text-slate-500">{{ $booking->customer->phone }}</p>
                    @endif
                </div>
                <div class="px-6 py-7 sm:px-10">
                    <p class="text-[0.65rem] font-semibold uppercase tracking-wide text-brand-700">{{ __('bookings.invoice.provider') }}</p>
                    <p class="mt-3 text-lg font-semibold text-slate-900">{{ $booking->muthowifProfile?->user?->name }}</p>
                    <p class="mt-1 text-sm text-slate-500">{{ __('bookings.invoice.muthowif') }}</p>
                </div>
            </div>

            <div class="border-b border-slate-100 px-6 py-7 sm:px-10">
                <p class="text-[0.65rem] font-semibold uppercase tracking-wide text-slate-500">{{ __('bookings.invoice.service') }}</p>
                <div class="mt-4 grid gap-4 sm:grid-cols-2">
                    <div class="rounded-2xl border border-slate-200/80 bg-slate-50/70 px-4 py-4">
                        <p class="text-[0.65rem] font-semibold uppercase tracking-wide text-slate-500">
                            {{ $isSupport ? __('bookings.invoice.package') : __('bookings.invoice.service') }}
                        </p>
                        <p class="mt-2 text-base font-semibold text-slate-900">
                            @if ($isSupport)
                                {{ $packageLabel ?? __('common.em_dash') }}
                            @else
                                {{ $booking->service_type?->label() ?? __('common.em_dash') }}
                            @endif
                        </p>
                        @unless ($isSupport)
                            <p class="mt-1 text-sm text-slate-500">
                                {{ __('bookings.index.pilgrims_count', ['count' => $booking->pilgrim_count, 'pilgrims_word' => __('common.pilgrims')]) }}
                            </p>
                        @endunless
                    </div>
                    <div class="rounded-2xl border border-slate-200/80 bg-slate-50/70 px-4 py-4">
                        <p class="text-[0.65rem] font-semibold uppercase tracking-wide text-slate-500">{{ __('bookings.invoice.schedule') }}</p>
                        <p class="mt-2 text-base font-semibold tabular-nums text-slate-900">{{ $scheduleLabel }}</p>
                        <p class="mt-1 text-sm text-slate-500">{{ __('bookings.invoice.service_period') }}</p>
                    </div>
                </div>
            </div>

            <div class="bg-gradient-to-b from-slate-50/90 to-white px-6 py-8 sm:px-10">
                <div class="flex items-end justify-between gap-3">
                    <p class="text-[0.65rem] font-semibold uppercase tracking-wide text-slate-500">{{ __('bookings.invoice.amount_summary') }}</p>
                    <div class="hidden h-px flex-1 bg-gradient-to-r from-slate-200 to-transparent sm:block"></div>
                </div>

                <dl class="mt-4 overflow-hidden rounded-2xl border border-slate-200/80 bg-white shadow-sm">
                    <div class="flex items-center justify-between gap-4 border-b border-slate-100 px-4 py-3.5 sm:px-5">
                        <dt class="text-sm text-slate-600">{{ __('bookings.invoice.subtotal') }}</dt>
                        <dd class="text-sm font-semibold tabular-nums text-slate-900">Rp {{ $fmt($base) }}</dd>
                    </div>
                    @if ($customerPlatformFee > 0)
                        <div class="flex items-center justify-between gap-4 border-b border-slate-100 px-4 py-3.5 sm:px-5">
                            <dt class="text-sm text-slate-600">{{ __('bookings.invoice.platform_fee_pct') }}</dt>
                            <dd class="text-sm font-semibold tabular-nums text-slate-900">Rp {{ $fmt($customerPlatformFee) }}</dd>
                        </div>
                    @endif
                    <div class="invoice-total-row flex items-center justify-between gap-4 bg-gradient-to-r from-baytgo to-brand-700 px-4 py-4 sm:px-5">
                        <dt class="text-base font-bold text-white">{{ __('bookings.invoice.total') }}</dt>
                        <dd class="text-xl font-bold tabular-nums text-gold-light sm:text-2xl">Rp {{ $fmt($gross) }}</dd>
                    </div>
                </dl>

                @if ($payment)
                    <div class="mt-5 flex items-start gap-3 rounded-2xl border border-brand-100 bg-brand-50/60 px-4 py-3.5">
                        <span class="mt-0.5 flex h-7 w-7 shrink-0 items-center justify-center rounded-full bg-brand-600/10 text-brand-700">
                            <svg class="h-3.5 w-3.5" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
                                <path fill-rule="evenodd" d="M10 1a4.5 4.5 0 00-4.5 4.5V9H5a2 2 0 00-2 2v6a2 2 0 002 2h10a2 2 0 002-2v-6a2 2 0 00-2-2h-.5V5.5A4.5 4.5 0 0010 1zm3 8V5.5a3 3 0 10-6 0V9h6z" clip-rule="evenodd" />
                            </svg>
                        </span>
                        <p class="text-xs leading-relaxed text-slate-600">
                            <span class="font-semibold text-slate-800">{{ __('bookings.invoice.payment_ref') }}</span>
                            — {{ __('bookings.invoice.gateway_via') }}
                            @if ($payment->payment_type)
                                <span class="font-medium text-slate-700">({{ $payment->payment_type }})</span>
                            @endif
                            . {{ __('bookings.invoice.gateway_fee_note') }}
                        </p>
                    </div>
                @endif
            </div>

            <footer class="border-t border-slate-100 bg-white px-6 py-8 text-center sm:px-10">
                <div class="mx-auto mb-4 h-px w-24 bg-gradient-to-r from-transparent via-gold to-transparent"></div>
                <p class="text-sm font-semibold text-baytgo">{{ __('bookings.invoice.thank_you', ['app' => config('app.name')]) }}</p>
                <p class="mx-auto mt-3 max-w-md text-xs leading-relaxed text-slate-400">{{ __('bookings.invoice.electronic_doc') }}</p>
            </footer>
        </div>
    </x-page-container>
</body>
</html>
