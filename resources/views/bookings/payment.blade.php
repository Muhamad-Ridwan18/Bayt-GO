@php
    use Carbon\Carbon;
    use App\Support\PlatformFee;

    $isWaitingConfirmation = $selectedMethod !== '' && is_array($instructions);
    $split = PlatformFee::split((float) $booking->resolvedAmountDue());
    $customerPlatformFee = (float) ($split['customer_fee'] ?? 0.0);
    $customerTotal = (float) ($split['customer_gross'] ?? 0.0);
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
    <div class="py-8 sm:py-12 bg-slate-50/70">
        <div class="max-w-6xl mx-auto px-4 sm:px-6 lg:px-8 grid grid-cols-1 lg:grid-cols-3 gap-5">
            <section class="lg:col-span-2 space-y-5">
                <div class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
                    <div class="flex items-center justify-between">
                        <h3 class="font-semibold text-slate-900">{{ __('bookings.payment.methods_heading') }}</h3>
                        <span class="text-xs font-semibold text-slate-500">{{ __('bookings.payment.midtrans_badge') }}</span>
                    </div>

                    <form method="GET" action="{{ route('bookings.payment', $booking) }}" class="mt-4 rounded-xl border border-slate-200 p-4">
                        <p class="text-sm font-semibold text-slate-900">{{ __('bookings.payment.choose_method') }}</p>
                        <p class="mt-1 text-xs text-slate-500">{{ __('bookings.payment.grouped_hint') }}</p>

                        <div class="mt-4 space-y-4">
                            @foreach ($methodGroups as $groupId => $groupMeta)
                                @php
                                    $groupMethods = array_values(array_filter(
                                        $methodsUi,
                                        fn (array $item): bool => $item['enabled'] && $item['group'] === $groupId
                                    ));
                                    $shouldOpen = $selectedMethod !== '' && in_array($selectedMethod, array_map(fn ($m) => $m['id'], $groupMethods), true);
                                @endphp
                                @if ($groupMethods !== [])
                                    <details class="group rounded-xl border border-slate-200 bg-slate-50/40" {{ $shouldOpen ? 'open' : '' }}>
                                        <summary class="flex cursor-pointer list-none items-center justify-between gap-3 rounded-xl px-4 py-3 hover:bg-slate-50">
                                            <span class="min-w-0">
                                                <span class="block text-sm font-semibold text-slate-900">{{ $groupMeta['title'] }}</span>
                                                <span class="block text-xs text-slate-500">{{ $groupMeta['description'] }}</span>
                                            </span>
                                            <span class="inline-flex items-center gap-2 text-xs font-semibold text-slate-600">
                                                <span class="rounded-full bg-white px-2 py-0.5 ring-1 ring-slate-200">{{ __('bookings.payment.options_count', ['count' => count($groupMethods)]) }}</span>
                                                <svg class="h-4 w-4 text-slate-500 transition-transform group-open:rotate-180" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
                                                    <path fill-rule="evenodd" d="M5.23 7.21a.75.75 0 011.06.02L10 10.94l3.71-3.71a.75.75 0 111.06 1.06l-4.24 4.24a.75.75 0 01-1.06 0L5.21 8.29a.75.75 0 01.02-1.08z" clip-rule="evenodd" />
                                                </svg>
                                            </span>
                                        </summary>
                                        <div class="px-3 pb-3">
                                            <div class="space-y-2">
                                                @foreach ($groupMethods as $method)
                                                    <label class="group flex cursor-pointer items-center justify-between rounded-xl border px-4 py-3 transition {{ $selectedMethod === $method['id'] ? 'border-brand-300 bg-brand-50' : 'border-slate-200 bg-white hover:bg-slate-50' }}">
                                                        <span class="flex items-center gap-3 min-w-0">
                                                            <img src="{{ $method['logo_path'] }}" alt="{{ $method['name'] }}" class="h-10 w-16 rounded-md border border-slate-200 bg-white object-contain p-1">
                                                            <span class="min-w-0">
                                                                <span class="block text-sm font-semibold text-slate-900">{{ $method['name'] }}</span>
                                                                <span class="block truncate text-xs text-slate-500">{{ $method['description'] }}</span>
                                                            </span>
                                                        </span>
                                                        <input type="radio" name="method" value="{{ $method['id'] }}" class="h-4 w-4 border-slate-300 text-brand-600 focus:ring-brand-500" {{ $selectedMethod === $method['id'] ? 'checked' : '' }}>
                                                    </label>
                                                @endforeach
                                            </div>
                                        </div>
                                    </details>
                                @endif
                            @endforeach
                        </div>

                        <button type="submit" class="mt-4 inline-flex w-full items-center justify-center rounded-xl bg-brand-600 px-4 py-2.5 text-sm font-semibold text-white hover:bg-brand-700">
                            {{ __('bookings.payment.pay_now') }}
                        </button>
                        <p class="mt-2 text-[11px] text-slate-500">
                            {{ __('bookings.payment.instructions_after') }}
                        </p>
                    </form>
                </div>

                @if ($selectedMethod !== '' && is_array($instructions))
                    <div class="rounded-2xl border border-brand-200 bg-brand-50 p-6 shadow-sm">
                        <div class="flex items-center justify-between">
                            <p class="text-xs font-semibold uppercase tracking-wide text-brand-800">{{ __('bookings.payment.instructions_heading') }} {{ strtoupper(str_replace('_', ' ', $selectedMethod)) }}</p>
                            <span class="rounded-full bg-white px-2.5 py-1 text-[10px] font-semibold text-brand-700">{{ __('bookings.payment.status_pending') }}</span>
                        </div>
                        @if (! empty($instructions['va_number']))
                            <p class="mt-3 text-sm text-slate-700">{{ __('bookings.payment.va_number') }}</p>
                            <p class="mt-1 rounded-lg bg-white px-4 py-3 font-mono text-lg font-bold text-slate-900">
                                {{ $instructions['va_number'] }}
                            </p>
                        @endif
                        @if (! empty($instructions['bill_key']) && ! empty($instructions['biller_code']))
                            <div class="mt-3 grid grid-cols-1 sm:grid-cols-2 gap-2">
                                <div class="rounded-lg bg-white px-4 py-3">
                                    <p class="text-xs text-slate-500">{{ __('bookings.payment.bill_key') }}</p>
                                    <p class="font-mono text-base font-bold text-slate-900">{{ $instructions['bill_key'] }}</p>
                                </div>
                                <div class="rounded-lg bg-white px-4 py-3">
                                    <p class="text-xs text-slate-500">{{ __('bookings.payment.biller_code') }}</p>
                                    <p class="font-mono text-base font-bold text-slate-900">{{ $instructions['biller_code'] }}</p>
                                </div>
                            </div>
                        @endif
                        @if (! empty($instructions['checkout_url']))
                            <a href="{{ $instructions['checkout_url'] }}" target="_blank" rel="noopener noreferrer" class="mt-3 inline-flex w-full items-center justify-center rounded-xl bg-slate-900 px-4 py-2.5 text-sm font-semibold text-white hover:bg-slate-800">
                                {{ __('bookings.payment.open_payment_page') }}
                            </a>
                        @endif
                        @if (! empty($instructions['deeplink_url']))
                            <a href="{{ $instructions['deeplink_url'] }}" target="_blank" rel="noopener noreferrer" class="mt-2 inline-flex w-full items-center justify-center rounded-xl bg-slate-900 px-4 py-2.5 text-sm font-semibold text-white hover:bg-slate-800">
                                {{ __('bookings.payment.open_wallet_app') }}
                            </a>
                        @endif
                        <p class="mt-3 text-sm text-slate-700">
                            {{ __('bookings.payment.deadline') }}
                            <span id="expiry-text" class="font-medium text-slate-900">
                                {{ ! empty($instructions['expiry_time']) ? Carbon::parse($instructions['expiry_time'])->timezone(config('app.timezone'))->format('d M Y, H:i') : '-' }}{{ __('common.timezone_suffix') }}
                            </span>
                        </p>
                        <p class="mt-1 text-sm text-slate-700">
                            {{ __('bookings.payment.time_left') }}
                            <span id="countdown-text" class="font-semibold text-slate-900">--:--:--</span>
                        </p>
                        <ol class="mt-3 list-decimal pl-5 text-xs text-slate-600 space-y-1">
                            <li>{{ __('bookings.payment.step_1') }}</li>
                            <li>{{ __('bookings.payment.step_2') }}</li>
                            <li>{{ __('bookings.payment.step_3') }}</li>
                            <li>{{ __('bookings.payment.step_4') }}</li>
                        </ol>
                        <div class="mt-4 flex flex-col gap-2 sm:flex-row">
                            <span class="inline-flex w-full items-center justify-center rounded-xl bg-emerald-600 px-4 py-2.5 text-sm font-semibold text-white">
                                {{ __('bookings.payment.waiting_auto') }}
                            </span>
                            <a href="{{ route('bookings.show', $booking) }}" class="inline-flex w-full items-center justify-center rounded-xl border border-slate-200 bg-white px-4 py-2.5 text-sm font-semibold text-slate-700 hover:bg-slate-50">
                                {{ __('bookings.payment.view_detail') }}
                            </a>
                        </div>
                    </div>
                @endif
            </section>

            <aside class="space-y-5">
                <div class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
                    <h3 class="font-semibold text-slate-900">{{ __('bookings.payment.order_summary') }}</h3>
                    <dl class="mt-4 space-y-3 text-sm">
                        @if (filled($booking->booking_code))
                            <div class="flex justify-between gap-3">
                                <dt class="text-slate-500">{{ __('bookings.show.booking_code') }}</dt>
                                <dd class="font-mono text-xs font-semibold text-slate-800 text-right">{{ $booking->booking_code }}</dd>
                            </div>
                        @endif
                        <div class="flex justify-between gap-3">
                            <dt class="text-slate-500">{{ __('bookings.payment.order_id') }}</dt>
                            <dd class="font-mono text-xs text-slate-700 text-right">{{ $payment->order_id }}</dd>
                        </div>
                        <div class="flex justify-between gap-3">
                            <dt class="text-slate-500">{{ __('bookings.show.muthowif') }}</dt>
                            <dd class="font-medium text-slate-900 text-right">{{ $booking->muthowifProfile->user->name ?? '-' }}</dd>
                        </div>
                        <div class="flex justify-between gap-3">
                            <dt class="text-slate-500">{{ __('bookings.show.period') }}</dt>
                            <dd class="font-medium text-slate-900 text-right">
                                {{ Carbon::parse($booking->starts_on)->format('d/m/Y') }} - {{ Carbon::parse($booking->ends_on)->format('d/m/Y') }}
                            </dd>
                        </div>
                        <div class="flex justify-between gap-3 border-t border-slate-100 pt-3">
                            <dt class="text-slate-500">{{ __('bookings.show.platform_fee') }}</dt>
                            <dd class="font-medium text-slate-900 text-right">Rp {{ number_format($customerPlatformFee, 0, ',', '.') }}</dd>
                        </div>
                        <div class="flex justify-between gap-3">
                            <dt class="font-semibold text-slate-900">{{ __('bookings.invoice.total') }}</dt>
                            <dd class="text-lg font-bold text-brand-700 text-right">Rp {{ number_format($customerTotal, 0, ',', '.') }}</dd>
                        </div>
                    </dl>
                </div>

                <div class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
                    <h3 class="font-semibold text-slate-900">{{ __('bookings.payment.help_title') }}</h3>
                    <p class="mt-2 text-xs text-slate-600 leading-relaxed">
                        {{ __('bookings.payment.help_body') }}
                    </p>
                    <a href="{{ route('bookings.show', $booking) }}" class="mt-3 inline-flex items-center text-sm font-semibold text-brand-700 hover:text-brand-800">
                        {{ __('bookings.payment.back_to_detail') }}
                    </a>
                </div>
            </aside>
        </div>
    </div>
</x-app-layout>

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
                countdownEl.textContent = `${pad(h)}:${pad(m)}:${pad(s)}`;
            }

            async function pollStatus() {
                try {
                    const response = await fetch(statusUrl, { headers: { 'Accept': 'application/json' } });
                    if (!response.ok) return;
                    const data = await response.json();
                    if (data && data.is_paid === true) {
                        window.location.replace(showUrl);
                    }
                } catch (e) {
                    // noop
                }
            }

            tickCountdown();
            pollStatus();
            setInterval(tickCountdown, 1000);
            setInterval(pollStatus, 3000);
            window.addEventListener('focus', pollStatus);
            document.addEventListener('visibilitychange', () => {
                if (!document.hidden) {
                    pollStatus();
                }
            });
        })();
    </script>
@endif
