@php
    use App\Support\IndonesianNumber;
    use Carbon\Carbon;
    use App\Support\PlatformFee;

    $isWaitingConfirmation = $selectedMethod !== '' && is_array($instructions);
    $split = PlatformFee::split((float) $booking->resolvedAmountDue());
    $customerPlatformFee = (float) ($split['customer_fee'] ?? 0.0);
    $customerTotal = (float) ($split['customer_gross'] ?? 0.0);
    $fmt = fn (float $n) => IndonesianNumber::formatThousands((string) (int) round($n));

    $methodGroups = [
        'bank' => [
            'title' => __('bookings.payment.groups.bank.title'),
            'description' => __('bookings.payment.groups.bank.description'),
        ],
        'ewallet' => [
            'title' => __('bookings.payment.groups.ewallet.title'),
            'description' => __('bookings.payment.groups.ewallet.description'),
        ],
        'qris' => [
            'title' => __('bookings.payment.groups.qris.title'),
            'description' => __('bookings.payment.groups.qris.description'),
        ],
    ];

    $methodsUi = [
        [
            'id' => 'va_bca',
            'group' => 'bank',
            'name' => __('bookings.payment.method_va_bca.name'),
            'logo_path' => asset('images/payments/va_bca.svg'),
            'description' => __('bookings.payment.method_va_bca.description'),
            'enabled' => in_array('va_bca', $methods, true),
        ],
        [
            'id' => 'va_bni',
            'group' => 'bank',
            'name' => __('bookings.payment.method_va_bni.name'),
            'logo_path' => asset('images/payments/va_bni.svg'),
            'description' => __('bookings.payment.method_va_bni.description'),
            'enabled' => in_array('va_bni', $methods, true),
        ],
        [
            'id' => 'va_bri',
            'group' => 'bank',
            'name' => __('bookings.payment.method_va_bri.name'),
            'logo_path' => asset('images/payments/va_bri.svg'),
            'description' => __('bookings.payment.method_va_bri.description'),
            'enabled' => in_array('va_bri', $methods, true),
        ],
        [
            'id' => 'va_permata',
            'group' => 'bank',
            'name' => __('bookings.payment.method_va_permata.name'),
            'logo_path' => asset('images/payments/va_permata.svg'),
            'description' => __('bookings.payment.method_va_permata.description'),
            'enabled' => in_array('va_permata', $methods, true),
        ],
        [
            'id' => 'va_mandiri_bill',
            'group' => 'bank',
            'name' => __('bookings.payment.method_va_mandiri_bill.name'),
            'logo_path' => asset('images/payments/va_mandiri_bill.svg'),
            'description' => __('bookings.payment.method_va_mandiri_bill.description'),
            'enabled' => in_array('va_mandiri_bill', $methods, true),
        ],
        [
            'id' => 'qris',
            'group' => 'qris',
            'name' => __('bookings.payment.method_qris.name'),
            'logo_path' => asset('images/payments/qris.svg'),
            'description' => __('bookings.payment.method_qris.description'),
            'enabled' => in_array('qris', $methods, true),
        ],
        [
            'id' => 'gopay',
            'group' => 'ewallet',
            'name' => __('bookings.payment.method_gopay.name'),
            'logo_path' => asset('images/payments/gopay.svg'),
            'description' => __('bookings.payment.method_gopay.description'),
            'enabled' => in_array('gopay', $methods, true),
        ],
        [
            'id' => 'shopeepay',
            'group' => 'ewallet',
            'name' => __('bookings.payment.method_shopeepay.name'),
            'logo_path' => asset('images/payments/shopeepay.svg'),
            'description' => __('bookings.payment.method_shopeepay.description'),
            'enabled' => in_array('shopeepay', $methods, true),
        ],
    ];
@endphp

<x-app-layout>
    <div
        class="relative min-h-[calc(100vh-4rem)] overflow-x-hidden bg-slate-50"
        x-data="customerBookingLive({
            userId: @js(auth()->id()),
            bookingId: @js($booking->getKey()),
            liveMode: 'customer_payment',
            fragmentUrl: null,
            showUrl: @js(route('bookings.show', $booking)),
            paymentStatusUrl: @js(route('bookings.payment.status', $booking)),
        })"
    >
        <div class="pointer-events-none absolute inset-0 overflow-hidden" aria-hidden="true">
            <div class="absolute -right-24 -top-24 h-96 w-96 rounded-full bg-brand-400/12 blur-3xl"></div>
            <div class="absolute -left-20 top-40 h-80 w-80 rounded-full bg-emerald-400/10 blur-3xl"></div>
            <div class="absolute bottom-0 left-1/2 h-48 w-[120%] -translate-x-1/2 bg-gradient-to-t from-white to-transparent"></div>
        </div>

        <div class="relative z-10 mx-auto max-w-6xl px-4 pb-16 pt-8 sm:px-6 lg:px-8">
            <a href="{{ route('bookings.show', $booking) }}" class="mb-6 inline-flex items-center gap-2 rounded-xl px-3 py-2 text-sm font-semibold text-brand-800 ring-1 ring-brand-200/50 transition hover:bg-white/80 hover:ring-brand-300/80">
                <svg class="h-4 w-4 shrink-0" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
                    <path fill-rule="evenodd" d="M17 10a.75.75 0 01-.75.75H5.612l4.158 3.96a.75.75 0 11-1.04 1.08l-5.5-5.25a.75.75 0 010-1.08l5.5-5.25a.75.75 0 111.04 1.08L5.612 9.25H16.25A.75.75 0 0117 10z" clip-rule="evenodd" />
                </svg>
                {{ __('bookings.payment.back_to_detail') }}
            </a>

            <header class="relative mb-10 overflow-hidden rounded-3xl border border-slate-200/80 bg-white shadow-market ring-1 ring-slate-100/80">
                <div class="pointer-events-none absolute inset-0 opacity-[0.35]" style="background-image: radial-gradient(circle at 1px 1px, rgb(148 163 184 / 0.35) 1px, transparent 0); background-size: 20px 20px;" aria-hidden="true"></div>
                <div class="pointer-events-none absolute -right-20 -top-20 h-48 w-48 rounded-full bg-brand-300/20 blur-3xl" aria-hidden="true"></div>
                <div class="relative px-6 py-8 sm:px-10 sm:py-10">
                    <div class="flex flex-col gap-6 lg:flex-row lg:items-end lg:justify-between">
                        <div class="max-w-2xl">
                            <p class="text-xs font-bold uppercase tracking-wider text-brand-700">{{ __('bookings.payment.page_kicker') }}</p>
                            <h1 class="mt-2 text-2xl font-bold tracking-tight text-slate-900 sm:text-3xl lg:text-[2rem] lg:leading-tight">{{ __('bookings.payment.page_title') }}</h1>
                            <p class="mt-3 text-sm leading-relaxed text-slate-600 sm:text-base">{{ __('bookings.payment.page_lead') }}</p>
                        </div>
                        <div class="shrink-0 rounded-2xl border border-brand-200/80 bg-gradient-to-br from-brand-50 to-emerald-50/50 px-5 py-4 text-right shadow-inner ring-1 ring-brand-100/60">
                            <p class="text-[11px] font-semibold uppercase tracking-wide text-brand-800">{{ __('bookings.invoice.total') }}</p>
                            <p class="mt-1 text-2xl font-bold tabular-nums tracking-tight text-brand-700 sm:text-3xl">Rp {{ $fmt($customerTotal) }}</p>
                        </div>
                    </div>
                </div>
            </header>

            <div class="grid grid-cols-1 gap-6 lg:grid-cols-12 lg:items-start lg:gap-8">
                {{-- Ringkasan: di atas di HP, kanan di desktop --}}
                <aside class="order-1 space-y-5 lg:sticky lg:order-2 lg:col-span-4 lg:top-24">
                    <div class="relative overflow-hidden rounded-2xl border border-slate-200/90 bg-white shadow-lg shadow-slate-900/5 ring-1 ring-slate-100/80">
                        <span class="absolute inset-x-0 top-0 z-10 h-1 bg-gradient-to-r from-brand-500 via-emerald-500 to-amber-400" aria-hidden="true"></span>
                        <div class="pointer-events-none absolute inset-0 opacity-[0.2]" style="background-image: radial-gradient(circle at 1px 1px, rgb(148 163 184 / 0.4) 1px, transparent 0); background-size: 18px 18px;" aria-hidden="true"></div>
                        <div class="relative p-5 sm:p-6">
                            <div class="flex items-start gap-3">
                                <span class="flex h-11 w-11 shrink-0 items-center justify-center rounded-2xl bg-gradient-to-br from-brand-500 to-brand-700 text-white shadow-lg shadow-brand-600/20">
                                    <svg class="h-5 w-5" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M19.5 14.25v-2.625a3.375 3.375 0 00-3.375-3.375h-1.5A1.125 1.125 0 0113.5 7.125v-1.5a3.375 3.375 0 00-3.375-3.375H8.25m0 12.75h7.5m-7.5 3H12M10.5 2.25H5.625c-.621 0-1.125.504-1.125 1.125v17.25c0 .621.504 1.125 1.125 1.125h12.75c.621 0 1.125-.504 1.125-1.125V11.25a9 9 0 00-9-9z" /></svg>
                                </span>
                                <div>
                                    <h2 class="text-sm font-bold text-slate-900">{{ __('bookings.payment.order_summary') }}</h2>
                                    <p class="mt-0.5 text-[11px] text-slate-500">{{ __('bookings.payment.midtrans_badge') }}</p>
                                </div>
                            </div>
                            <dl class="mt-4 space-y-3 text-sm">
                                @if (filled($booking->booking_code))
                                    <div class="flex justify-between gap-3 border-b border-slate-100 pb-3">
                                        <dt class="text-slate-500">{{ __('bookings.show.booking_code') }}</dt>
                                        <dd class="text-right font-mono text-xs font-semibold text-slate-800">{{ $booking->booking_code }}</dd>
                                    </div>
                                @endif
                                <div class="flex justify-between gap-3">
                                    <dt class="text-slate-500">{{ __('bookings.payment.order_id') }}</dt>
                                    <dd class="max-w-[55%] break-all text-right font-mono text-xs text-slate-700">{{ $payment->order_id }}</dd>
                                </div>
                                <div class="flex justify-between gap-3">
                                    <dt class="text-slate-500">{{ __('bookings.show.muthowif') }}</dt>
                                    <dd class="text-right font-medium text-slate-900">{{ $booking->muthowifProfile->user->name ?? '—' }}</dd>
                                </div>
                                <div class="flex justify-between gap-3">
                                    <dt class="text-slate-500">{{ __('bookings.show.period') }}</dt>
                                    <dd class="text-right font-medium tabular-nums text-slate-900">
                                        {{ Carbon::parse($booking->starts_on)->format('d/m/Y') }} – {{ Carbon::parse($booking->ends_on)->format('d/m/Y') }}
                                    </dd>
                                </div>
                                <div class="flex justify-between gap-3 border-t border-slate-100 pt-3">
                                    <dt class="text-slate-500">{{ __('bookings.show.platform_fee') }}</dt>
                                    <dd class="font-medium tabular-nums text-slate-900">Rp {{ $fmt($customerPlatformFee) }}</dd>
                                </div>
                                <div class="flex items-end justify-between gap-3 rounded-xl bg-gradient-to-br from-brand-600/10 via-brand-50/90 to-emerald-50/50 px-3 py-3 ring-1 ring-brand-200/60">
                                    <dt class="text-sm font-bold text-slate-900">{{ __('bookings.invoice.total') }}</dt>
                                    <dd class="text-xl font-bold tabular-nums text-brand-700 sm:text-2xl">Rp {{ $fmt($customerTotal) }}</dd>
                                </div>
                            </dl>
                        </div>
                    </div>

                    <div class="rounded-2xl border border-slate-200/90 bg-gradient-to-br from-slate-50/80 to-white p-5 shadow-sm ring-1 ring-slate-100/80">
                        <div class="flex gap-3">
                            <span class="flex h-9 w-9 shrink-0 items-center justify-center rounded-xl bg-brand-100 text-brand-700 ring-1 ring-brand-200/60">
                                <svg class="h-4 w-4" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true"><path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zM9 9a.75.75 0 000 1.5h.253a.25.25 0 01.244.304l-.459 2.066A1.75 1.75 0 0010.747 15H11a.75.75 0 000-1.5h-.253a.25.25 0 01-.244-.304l.459-2.066A1.75 1.75 0 009.253 9H9z" clip-rule="evenodd" /></svg>
                            </span>
                            <div>
                                <h2 class="text-sm font-bold text-slate-900">{{ __('bookings.payment.help_title') }}</h2>
                                <p class="mt-1.5 text-xs leading-relaxed text-slate-600">{{ __('bookings.payment.help_body') }}</p>
                            </div>
                        </div>
                    </div>
                </aside>

                {{-- Metode & instruksi --}}
                <div class="order-2 min-w-0 space-y-6 lg:order-1 lg:col-span-8">
                    <section class="relative overflow-hidden rounded-2xl border border-slate-200/90 bg-white shadow-lg shadow-slate-900/5 ring-1 ring-slate-100/80">
                        <div class="relative bg-gradient-to-r from-slate-900 via-brand-900 to-amber-950 px-5 py-5 sm:px-6 sm:py-6">
                            <div class="flex flex-wrap items-start justify-between gap-4">
                                <div class="flex items-start gap-3">
                                    <span class="flex h-12 w-12 shrink-0 items-center justify-center rounded-2xl bg-white/10 text-amber-200 ring-1 ring-white/20">
                                        <svg class="h-6 w-6" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M2.25 8.25h19.5M2.25 9h19.5m-16.5 5.25h6m-6 2.25h3m-3.75 3h15a2.25 2.25 0 002.25-2.25V6.75A2.25 2.25 0 0019.5 4.5h-15a2.25 2.25 0 00-2.25 2.25v10.5A2.25 2.25 0 004.5 19.5z" /></svg>
                                    </span>
                                    <div>
                                        <h2 class="text-lg font-bold text-white sm:text-xl">{{ __('bookings.payment.methods_heading') }}</h2>
                                        <p class="mt-1 text-sm text-brand-100/85">{{ __('bookings.payment.grouped_hint') }}</p>
                                    </div>
                                </div>
                                <span class="inline-flex items-center gap-1.5 rounded-full bg-white/10 px-3 py-1.5 text-[11px] font-semibold text-white ring-1 ring-white/20">
                                    <svg class="h-3.5 w-3.5 text-emerald-300" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true"><path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.857-9.809a.75.75 0 00-1.214-.882l-3.483 4.79-1.88-1.88a.75.75 0 10-1.06 1.061l2.5 2.5a.75.75 0 001.137-.089l4-5.5z" clip-rule="evenodd" /></svg>
                                    {{ __('bookings.payment.midtrans_badge') }}
                                </span>
                            </div>
                        </div>

                        <form method="GET" action="{{ route('bookings.payment', $booking) }}" class="bg-gradient-to-b from-slate-50/40 to-white p-5 sm:p-6">
                            <p class="text-sm font-bold text-slate-900">{{ __('bookings.payment.choose_method') }}</p>

                            <div class="mt-4 space-y-3">
                                @foreach ($methodGroups as $groupId => $groupMeta)
                                    @php
                                        $groupMethods = array_values(array_filter(
                                            $methodsUi,
                                            fn (array $item): bool => $item['enabled'] && $item['group'] === $groupId
                                        ));
                                        $shouldOpen = $selectedMethod !== '' && in_array($selectedMethod, array_map(fn ($m) => $m['id'], $groupMethods), true);
                                    @endphp
                                    @if ($groupMethods !== [])
                                        <details class="group rounded-2xl border border-slate-200/90 bg-white open:shadow-md open:ring-1 open:ring-slate-200/80" {{ $shouldOpen ? 'open' : '' }}>
                                            <summary class="flex cursor-pointer list-none items-center justify-between gap-3 rounded-2xl px-4 py-3.5 text-left transition hover:bg-slate-50/90 [&::-webkit-details-marker]:hidden">
                                                <span class="flex min-w-0 items-center gap-3">
                                                    @if ($groupId === 'bank')
                                                        <span class="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl bg-slate-100 text-slate-700 ring-1 ring-slate-200/80" aria-hidden="true">
                                                            <svg class="h-5 w-5" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M2.25 18h19.5M2.25 9h19.5M2.25 4.5h19.5M9 4.5V18M15 4.5V18" /></svg>
                                                        </span>
                                                    @elseif ($groupId === 'ewallet')
                                                        <span class="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl bg-amber-50 text-amber-800 ring-1 ring-amber-200/70" aria-hidden="true">
                                                            <svg class="h-5 w-5" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M21 12a2.25 2.25 0 00-2.25-2.25H15a3 3 0 11-6 0H5.25A2.25 2.25 0 003 12m18 0v1.5a2.25 2.25 0 01-2.25 2.25H5.25A2.25 2.25 0 013 13.5V12m18 0a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>
                                                        </span>
                                                    @else
                                                        <span class="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl bg-emerald-50 text-emerald-800 ring-1 ring-emerald-200/70" aria-hidden="true">
                                                            <svg class="h-5 w-5" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M3.75 4.875c0-.621.504-1.125 1.125-1.125h4.5c.621 0 1.125.504 1.125 1.125v4.5c0 .621-.504 1.125-1.125 1.125h-4.5A1.125 1.125 0 013.75 9.375v-4.5zM3.75 14.625c0-.621.504-1.125 1.125-1.125h4.5c.621 0 1.125.504 1.125 1.125v4.5c0 .621-.504 1.125-1.125 1.125h-4.5a1.125 1.125 0 01-1.125-1.125v-4.5zM13.5 4.875c0-.621.504-1.125 1.125-1.125h4.5c.621 0 1.125.504 1.125 1.125v4.5c0 .621-.504 1.125-1.125 1.125h-4.5A1.125 1.125 0 0113.5 9.375v-4.5zM13.5 14.625c0-.621.504-1.125 1.125-1.125h4.5c.621 0 1.125.504 1.125 1.125v4.5c0 .621-.504 1.125-1.125 1.125h-4.5a1.125 1.125 0 01-1.125-1.125v-4.5z" /></svg>
                                                        </span>
                                                    @endif
                                                    <span class="min-w-0">
                                                        <span class="block text-sm font-semibold text-slate-900">{{ $groupMeta['title'] }}</span>
                                                        <span class="mt-0.5 block text-xs text-slate-500">{{ $groupMeta['description'] }}</span>
                                                    </span>
                                                </span>
                                                <span class="inline-flex shrink-0 items-center gap-2 text-xs font-semibold text-slate-600">
                                                    <span class="rounded-full bg-white px-2 py-0.5 ring-1 ring-slate-200/80">{{ __('bookings.payment.options_count', ['count' => count($groupMethods)]) }}</span>
                                                    <svg class="h-4 w-4 text-slate-400 transition group-open:rotate-180" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
                                                        <path fill-rule="evenodd" d="M5.23 7.21a.75.75 0 011.06.02L10 11.168l3.71-3.938a.75.75 0 111.08 1.04l-4.25 4.5a.75.75 0 01-1.08 0l-4.25-4.5a.75.75 0 01.02-1.06z" clip-rule="evenodd" />
                                                    </svg>
                                                </span>
                                            </summary>
                                            <div class="border-t border-slate-100 px-3 pb-3 pt-1">
                                                <div class="space-y-2">
                                                    @foreach ($groupMethods as $method)
                                                        <label class="group/m flex min-h-[4.25rem] cursor-pointer touch-manipulation items-center justify-between gap-3 rounded-xl border px-4 py-3 transition active:scale-[0.99] {{ $selectedMethod === $method['id'] ? 'border-brand-400 bg-gradient-to-br from-brand-50 to-white ring-2 ring-brand-200/60' : 'border-slate-200 bg-white hover:border-slate-300 hover:bg-slate-50/80' }}">
                                                            <span class="flex min-w-0 items-center gap-3">
                                                                <img src="{{ $method['logo_path'] }}" alt="" class="h-10 w-16 shrink-0 rounded-lg border border-slate-200/80 bg-white object-contain p-1" width="64" height="40">
                                                                <span class="min-w-0">
                                                                    <span class="block text-sm font-semibold text-slate-900">{{ $method['name'] }}</span>
                                                                    <span class="block truncate text-xs text-slate-500">{{ $method['description'] }}</span>
                                                                </span>
                                                            </span>
                                                            <input type="radio" name="method" value="{{ $method['id'] }}" class="h-5 w-5 shrink-0 border-slate-300 text-brand-600 focus:ring-brand-500" {{ $selectedMethod === $method['id'] ? 'checked' : '' }}>
                                                        </label>
                                                    @endforeach
                                                </div>
                                            </div>
                                        </details>
                                    @endif
                                @endforeach
                            </div>

                            <button type="submit" class="group/btn mt-6 inline-flex w-full items-center justify-center gap-2 rounded-2xl bg-gradient-to-r from-brand-600 to-brand-700 px-4 py-4 text-base font-bold text-white shadow-lg shadow-brand-900/20 transition hover:from-brand-500 hover:to-brand-600 hover:shadow-xl focus:outline-none focus:ring-2 focus:ring-brand-500 focus:ring-offset-2 sm:text-sm sm:py-3.5">
                                <span>{{ __('bookings.payment.pay_now') }}</span>
                                <svg class="h-5 w-5 transition group-hover/btn:translate-x-0.5" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true"><path fill-rule="evenodd" d="M3 10a.75.75 0 01.75-.75h10.638L10.23 5.29a.75.75 0 111.04-1.08l5.5 5.25a.75.75 0 010 1.08l-5.5 5.25a.75.75 0 11-1.04-1.08l4.158-3.96H3.75A.75.75 0 013 10z" clip-rule="evenodd" /></svg>
                            </button>
                            <p class="mt-2 text-center text-[11px] text-slate-500 sm:text-left">{{ __('bookings.payment.instructions_after') }}</p>
                        </form>
                    </section>

                    @if ($selectedMethod !== '' && is_array($instructions))
                        <section class="relative overflow-hidden rounded-2xl border border-emerald-200/80 bg-gradient-to-br from-emerald-50/95 via-white to-amber-50/30 p-5 shadow-lg shadow-emerald-900/5 ring-1 ring-emerald-100/70 sm:p-6">
                            <div class="pointer-events-none absolute -right-12 top-0 h-40 w-40 rounded-full bg-emerald-300/20 blur-3xl" aria-hidden="true"></div>
                            <div class="relative flex flex-wrap items-start justify-between gap-3">
                                <div class="flex items-start gap-3">
                                    <span class="flex h-11 w-11 shrink-0 items-center justify-center rounded-2xl bg-emerald-600 text-white shadow-lg shadow-emerald-600/25">
                                        <svg class="h-6 w-6" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75L11.25 15 15 9.75M21 12c0 1.268-.63 2.39-1.593 3.068a3.745 3.745 0 01-1.043 3.296 3.745 3.745 0 01-3.296 1.043A3.745 3.745 0 0112 21c-1.268 0-2.39-.63-3.068-1.593a3.746 3.746 0 01-3.296-1.043 3.745 3.745 0 01-1.043-3.296A3.745 3.745 0 013 12c0-1.268.63-2.39 1.593-3.068a3.745 3.745 0 011.043-3.296 3.746 3.746 0 013.296-1.043A3.746 3.746 0 0112 3c1.268 0 2.39.63 3.068 1.593a3.746 3.746 0 013.296 1.043 3.746 3.746 0 011.043 3.296A3.745 3.745 0 0121 12z" /></svg>
                                    </span>
                                    <div>
                                        <p class="text-xs font-bold uppercase tracking-wide text-emerald-900">{{ __('bookings.payment.instructions_heading') }}</p>
                                        <p class="mt-0.5 font-mono text-sm font-semibold text-slate-800">{{ strtoupper(str_replace('_', ' ', $selectedMethod)) }}</p>
                                    </div>
                                </div>
                                <span class="inline-flex items-center rounded-full bg-amber-100 px-3 py-1 text-[10px] font-bold uppercase tracking-wide text-amber-950 ring-1 ring-amber-200/80">{{ __('bookings.payment.status_pending') }}</span>
                            </div>

                            @if (! empty($instructions['va_number']))
                                <p class="mt-6 text-sm font-semibold text-slate-800">{{ __('bookings.payment.va_number') }}</p>
                                <div class="mt-2 flex flex-col gap-2 sm:flex-row sm:items-stretch">
                                    <p id="va-number-text" class="flex-1 select-all rounded-2xl border-2 border-emerald-200/90 bg-white px-4 py-4 font-mono text-xl font-bold tracking-wider text-slate-900 shadow-inner sm:text-2xl">
                                        {{ $instructions['va_number'] }}
                                    </p>
                                    <button
                                        type="button"
                                        class="inline-flex shrink-0 items-center justify-center gap-2 rounded-2xl bg-emerald-600 px-5 py-4 text-sm font-bold text-white shadow-md transition hover:bg-emerald-700 focus:outline-none focus:ring-2 focus:ring-emerald-500 focus:ring-offset-2 sm:min-w-[7rem]"
                                        data-copy-target="va-number-text"
                                        onclick="(function(btn){ const el=document.getElementById(btn.dataset.copyTarget); const t=el ? el.textContent.trim() : ''; if(!t) return; navigator.clipboard.writeText(t).then(function(){ var o=btn.textContent; btn.textContent={{ json_encode(__('bookings.payment.copy_done')) }}; btn.disabled=true; setTimeout(function(){ btn.textContent=o; btn.disabled=false; }, 2000); }).catch(function(){}); })(this)"
                                    >
                                        <svg class="h-4 w-4 shrink-0" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M15.666 3.888A2.25 2.25 0 0013.5 2.25h-3c-1.03 0-1.9.693-2.166 1.638m7.332 0c.055.194.084.4.084.612v0a.75.75 0 01-.75.75H9a.75.75 0 01-.75-.75v0c0-.212.03-.418.084-.612m7.332 0c.646.049 1.288.11 1.927.184 1.1.128 1.907 1.077 1.907 2.185V19.5a2.25 2.25 0 01-2.25 2.25H6.75A2.25 2.25 0 014.5 19.5V6.257c0-1.108.806-2.057 1.907-2.185a48.208 48.208 0 011.927-.184" /></svg>
                                        {{ __('bookings.payment.copy_number') }}
                                    </button>
                                </div>
                            @endif

                            @if (! empty($instructions['bill_key']) && ! empty($instructions['biller_code']))
                                <div class="mt-4 grid grid-cols-1 gap-3 sm:grid-cols-2">
                                    <div class="rounded-xl border border-emerald-100/90 bg-white/95 px-4 py-3 shadow-sm">
                                        <p class="text-xs text-slate-500">{{ __('bookings.payment.bill_key') }}</p>
                                        <p class="mt-1 font-mono text-base font-bold text-slate-900">{{ $instructions['bill_key'] }}</p>
                                    </div>
                                    <div class="rounded-xl border border-emerald-100/90 bg-white/95 px-4 py-3 shadow-sm">
                                        <p class="text-xs text-slate-500">{{ __('bookings.payment.biller_code') }}</p>
                                        <p class="mt-1 font-mono text-base font-bold text-slate-900">{{ $instructions['biller_code'] }}</p>
                                    </div>
                                </div>
                            @endif

                            @if (! empty($instructions['checkout_url']))
                                <a href="{{ $instructions['checkout_url'] }}" target="_blank" rel="noopener noreferrer" class="mt-6 inline-flex w-full items-center justify-center gap-2 rounded-2xl bg-gradient-to-r from-slate-900 to-slate-800 px-4 py-4 text-sm font-bold text-white shadow-lg transition hover:from-slate-800 hover:to-slate-700">
                                    {{ __('bookings.payment.open_payment_page') }}
                                    <svg class="h-4 w-4 opacity-90" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true"><path fill-rule="evenodd" d="M4.25 5.5a.75.75 0 00-.75.75v8.5c0 .414.336.75.75.75h8.5a.75.75 0 00.75-.75v-4a.75.75 0 011.5 0v4A2.25 2.25 0 0112.75 17h-8.5A2.25 2.25 0 012 14.75v-8.5A2.25 2.25 0 014.25 4h5a.75.75 0 010 1.5h-5z" clip-rule="evenodd" /><path fill-rule="evenodd" d="M6.194 12.753a.75.75 0 001.06.053L16.5 4.44v2.84a.75.75 0 001.5 0v-4.5a.75.75 0 00-.75-.75h-4.5a.75.75 0 000 1.5h2.553l-9.056 8.194a.75.75 0 00-.053 1.06z" clip-rule="evenodd" /></svg>
                                </a>
                            @endif
                            @if (! empty($instructions['deeplink_url']))
                                <a href="{{ $instructions['deeplink_url'] }}" target="_blank" rel="noopener noreferrer" class="mt-2 inline-flex w-full items-center justify-center gap-2 rounded-2xl bg-gradient-to-r from-slate-900 to-slate-800 px-4 py-4 text-sm font-bold text-white shadow-lg transition hover:from-slate-800 hover:to-slate-700">
                                    {{ __('bookings.payment.open_wallet_app') }}
                                    <svg class="h-4 w-4 opacity-90" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true"><path fill-rule="evenodd" d="M4.25 5.5a.75.75 0 00-.75.75v8.5c0 .414.336.75.75.75h8.5a.75.75 0 00.75-.75v-4a.75.75 0 011.5 0v4A2.25 2.25 0 0112.75 17h-8.5A2.25 2.25 0 012 14.75v-8.5A2.25 2.25 0 014.25 4h5a.75.75 0 010 1.5h-5z" clip-rule="evenodd" /><path fill-rule="evenodd" d="M6.194 12.753a.75.75 0 001.06.053L16.5 4.44v2.84a.75.75 0 001.5 0v-4.5a.75.75 0 00-.75-.75h-4.5a.75.75 0 000 1.5h2.553l-9.056 8.194a.75.75 0 00-.053 1.06z" clip-rule="evenodd" /></svg>
                                </a>
                            @endif

                            <div class="mt-6 grid gap-3 rounded-2xl border border-emerald-200/80 bg-white/90 p-4 shadow-inner ring-1 ring-emerald-100/60 sm:grid-cols-2">
                                <div class="flex gap-3">
                                    <span class="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl bg-slate-100 text-slate-600 ring-1 ring-slate-200/80" aria-hidden="true">
                                        <svg class="h-5 w-5" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M6.75 3v2.25M17.25 3v2.25M3 18.75V7.5a2.25 2.25 0 012.25-2.25h13.5A2.25 2.25 0 0121 7.5v11.25m-18 0A2.25 2.25 0 005.25 21h13.5a2.25 2.25 0 002.25-2.25m-18 0v-7.5A2.25 2.25 0 015.25 9h13.5a2.25 2.25 0 012.25 2.25v7.5" /></svg>
                                    </span>
                                    <div>
                                        <p class="text-[11px] font-semibold uppercase tracking-wide text-slate-500">{{ __('bookings.payment.deadline') }}</p>
                                        <p id="expiry-text" class="mt-0.5 text-sm font-semibold tabular-nums text-slate-900">{{ ! empty($instructions['expiry_time']) ? Carbon::parse($instructions['expiry_time'])->timezone(config('app.timezone'))->format('d M Y, H:i') : '—' }}{{ __('common.timezone_suffix') }}</p>
                                    </div>
                                </div>
                                <div class="flex gap-3 sm:border-l sm:border-emerald-100 sm:pl-5">
                                    <span class="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl bg-emerald-100 text-emerald-800 ring-1 ring-emerald-200/80" aria-hidden="true">
                                        <svg class="h-5 w-5" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M12 6v6h4.5m4.5 0a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>
                                    </span>
                                    <div>
                                        <p class="text-[11px] font-semibold uppercase tracking-wide text-slate-500">{{ __('bookings.payment.time_left') }}</p>
                                        <p id="countdown-text" class="mt-0.5 text-lg font-bold tabular-nums text-emerald-800">--:--:--</p>
                                    </div>
                                </div>
                            </div>

                            <ul class="mt-6 space-y-3">
                                @foreach ([__('bookings.payment.step_1'), __('bookings.payment.step_2'), __('bookings.payment.step_3'), __('bookings.payment.step_4')] as $idx => $stepText)
                                    <li class="flex gap-3 text-sm leading-relaxed text-slate-700">
                                        <span class="flex h-7 w-7 shrink-0 items-center justify-center rounded-full bg-emerald-100 text-xs font-bold text-emerald-900 ring-1 ring-emerald-200/80">{{ $idx + 1 }}</span>
                                        <span class="pt-0.5">{{ $stepText }}</span>
                                    </li>
                                @endforeach
                            </ul>

                            <div class="mt-8 flex flex-col gap-3 border-t border-emerald-100/80 pt-6 sm:flex-row">
                                <span class="inline-flex w-full items-center justify-center gap-2 rounded-2xl bg-emerald-600 px-4 py-3.5 text-sm font-semibold text-white shadow-md shadow-emerald-900/10">
                                    <svg class="h-4 w-4 opacity-95" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true"><path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm.75-13a.75.75 0 00-1.5 0v5c0 .414.336.75.75.75h4a.75.75 0 000-1.5h-3.25V5z" clip-rule="evenodd" /></svg>
                                    {{ __('bookings.payment.waiting_auto') }}
                                </span>
                                <a href="{{ route('bookings.show', $booking) }}" class="inline-flex w-full items-center justify-center rounded-2xl border-2 border-slate-200 bg-white px-4 py-3.5 text-sm font-bold text-slate-800 shadow-sm transition hover:border-slate-300 hover:bg-slate-50">
                                    {{ __('bookings.payment.view_detail') }}
                                </a>
                            </div>
                        </section>
                    @endif
                </div>
            </div>
        </div>

        @if ($isWaitingConfirmation)
            <script>
                (function () {
                    const statusUrl = @json(route('bookings.payment.status', $booking));
                    const showUrl = @json(route('bookings.show', $booking));
                    const expiryRaw = @json($instructions['expiry_time'] ?? null);
                    const countdownEl = document.getElementById('countdown-text');

                    function parseExpiry(input) {
                        if (!input || typeof input !== 'string') return null;
                        const normalized = input.replace(' ', 'T').replace(/([+-]\d{2})(\d{2})$/, '$1:$2');
                        const date = new Date(normalized);
                        return Number.isNaN(date.getTime()) ? null : date;
                    }

                    const expiryDate = parseExpiry(expiryRaw);
                    function pad(num) { return String(num).padStart(2, '0'); }

                    function tickCountdown() {
                        if (!countdownEl || !expiryDate) return;
                        const now = new Date();
                        let diff = Math.floor((expiryDate.getTime() - now.getTime()) / 1000);
                        if (diff < 0) diff = 0;
                        const h = Math.floor(diff / 3600);
                        const m = Math.floor((diff % 3600) / 60);
                        const s = diff % 60;
                        countdownEl.textContent = pad(h) + ':' + pad(m) + ':' + pad(s);
                    }

                    async function pollStatus() {
                        try {
                            const response = await fetch(statusUrl, { headers: { 'Accept': 'application/json' } });
                            if (!response.ok) return;
                            const data = await response.json();
                            if (data && data.is_paid === true) {
                                window.location.replace(showUrl);
                            }
                        } catch (e) {}
                    }

                    tickCountdown();
                    pollStatus();
                    setInterval(tickCountdown, 1000);
                    setInterval(pollStatus, 3000);
                    window.addEventListener('focus', pollStatus);
                    document.addEventListener('visibilitychange', function () {
                        if (!document.hidden) pollStatus();
                    });
                })();
            </script>
        @endif
    </div>
</x-app-layout>
