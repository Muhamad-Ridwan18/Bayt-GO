@php
    use Carbon\Carbon;
    use App\Support\PlatformFee;

    $isWaitingConfirmation = $selectedMethod !== '' && is_array($instructions);
    $split = PlatformFee::split((float) $booking->resolvedAmountDue());
    $customerPlatformFee = (float) ($split['customer_fee'] ?? 0.0);
    $customerTotal = (float) ($split['customer_gross'] ?? 0.0);
    $methodsUi = [
        [
            'id' => 'va_bca',
            'name' => 'BCA Virtual Account',
            'logo' => 'BCA',
            'description' => 'Transfer via m-BCA, klikBCA, atau ATM.',
            'enabled' => in_array('va_bca', $methods, true),
        ],
        [
            'id' => 'va_bni',
            'name' => 'BNI Virtual Account',
            'logo' => 'BNI',
            'description' => 'Transfer via Wondr BNI, ATM, atau iBanking.',
            'enabled' => in_array('va_bni', $methods, true),
        ],
        [
            'id' => 'va_bri',
            'name' => 'BRI Virtual Account',
            'logo' => 'BRI',
            'description' => 'Transfer via BRImo, ATM, atau internet banking.',
            'enabled' => in_array('va_bri', $methods, true),
        ],
        [
            'id' => 'va_permata',
            'name' => 'Permata Virtual Account',
            'logo' => 'PMT',
            'description' => 'Pembayaran via channel Permata Bank.',
            'enabled' => in_array('va_permata', $methods, true),
        ],
        [
            'id' => 'va_mandiri_bill',
            'name' => 'Mandiri Bill Payment',
            'logo' => 'MDR',
            'description' => 'Bayar via Livin atau ATM Mandiri.',
            'enabled' => in_array('va_mandiri_bill', $methods, true),
        ],
        [
            'id' => 'qris',
            'name' => 'QRIS',
            'logo' => 'QRIS',
            'description' => 'Scan QR pakai e-wallet atau mobile banking.',
            'enabled' => in_array('qris', $methods, true),
        ],
        [
            'id' => 'gopay',
            'name' => 'GoPay',
            'logo' => 'GP',
            'description' => 'Bayar langsung dari aplikasi GoPay.',
            'enabled' => in_array('gopay', $methods, true),
        ],
        [
            'id' => 'shopeepay',
            'name' => 'ShopeePay',
            'logo' => 'SP',
            'description' => 'Bayar langsung dari aplikasi ShopeePay.',
            'enabled' => in_array('shopeepay', $methods, true),
        ],
    ];
@endphp

<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-col gap-1 sm:flex-row sm:items-center sm:justify-between">
            <h2 class="font-semibold text-xl text-slate-800 leading-tight">Pembayaran Midtrans</h2>
            <a href="{{ route('bookings.show', $booking) }}" class="text-sm font-semibold text-brand-700 hover:text-brand-800">Kembali ke detail</a>
        </div>
    </x-slot>

    <div class="py-8 sm:py-12 bg-slate-50/70">
        <div class="max-w-6xl mx-auto px-4 sm:px-6 lg:px-8 grid grid-cols-1 lg:grid-cols-3 gap-5">
            <section class="lg:col-span-2 space-y-5">
                <div class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
                    <div class="flex items-center justify-between">
                        <h3 class="font-semibold text-slate-900">Metode pembayaran</h3>
                        <span class="text-xs font-semibold text-slate-500">Secure payment by Midtrans</span>
                    </div>

                    <div class="mt-4 space-y-4">
                        <div class="rounded-xl border border-slate-200 p-4">
                            <p class="text-sm font-semibold text-slate-900">1) Pilih kategori pembayaran</p>
                            <div class="mt-3 space-y-2">
                                <label class="flex items-center justify-between rounded-lg border border-brand-300 bg-brand-50 px-3 py-2">
                                    <span class="text-sm font-semibold text-slate-900">Transfer Bank</span>
                                    <span class="text-xs font-semibold text-brand-700">Aktif</span>
                                </label>
                                <div class="flex items-center justify-between rounded-lg border border-slate-200 bg-slate-50 px-3 py-2 opacity-75">
                                    <span class="text-sm font-semibold text-slate-700">E-Wallet</span>
                                    <span class="text-xs font-semibold text-slate-500">Segera hadir</span>
                                </div>
                                <div class="flex items-center justify-between rounded-lg border border-slate-200 bg-slate-50 px-3 py-2 opacity-75">
                                    <span class="text-sm font-semibold text-slate-700">QRIS</span>
                                    <span class="text-xs font-semibold text-slate-500">Segera hadir</span>
                                </div>
                            </div>
                        </div>

                        <form method="GET" action="{{ route('bookings.payment', $booking) }}" class="rounded-xl border border-slate-200 p-4">
                            <p class="text-sm font-semibold text-slate-900">2) Pilih metode transfer bank</p>
                            <div class="mt-3 space-y-2">
                                @foreach ($methodsUi as $method)
                                    @if ($method['enabled'])
                                        <label class="group flex cursor-pointer items-center justify-between rounded-xl border px-4 py-3 transition {{ $selectedMethod === $method['id'] ? 'border-brand-300 bg-brand-50' : 'border-slate-200 hover:bg-slate-50' }}">
                                            <span class="flex items-center gap-3 min-w-0">
                                                <span class="inline-flex h-10 w-10 items-center justify-center rounded-lg bg-slate-900 text-[11px] font-bold text-white">
                                                    {{ $method['logo'] }}
                                                </span>
                                                <span class="min-w-0">
                                                    <span class="block text-sm font-semibold text-slate-900">{{ $method['name'] }}</span>
                                                    <span class="block truncate text-xs text-slate-500">{{ $method['description'] }}</span>
                                                </span>
                                            </span>
                                            <input type="radio" name="method" value="{{ $method['id'] }}" class="h-4 w-4 border-slate-300 text-brand-600 focus:ring-brand-500" {{ $selectedMethod === $method['id'] ? 'checked' : '' }}>
                                        </label>
                                    @endif
                                @endforeach
                            </div>
                            <button type="submit" class="mt-4 inline-flex w-full items-center justify-center rounded-xl bg-brand-600 px-4 py-2.5 text-sm font-semibold text-white hover:bg-brand-700">
                                Bayar sekarang
                            </button>
                            <p class="mt-2 text-[11px] text-slate-500">
                                VA akan dibuat setelah kamu klik tombol Bayar sekarang.
                            </p>
                        </form>
                    </div>
                </div>

                @if ($selectedMethod !== '' && is_array($instructions))
                    <div class="rounded-2xl border border-brand-200 bg-brand-50 p-6 shadow-sm">
                        <div class="flex items-center justify-between">
                            <p class="text-xs font-semibold uppercase tracking-wide text-brand-800">Instruksi pembayaran {{ strtoupper(str_replace('_', ' ', $selectedMethod)) }}</p>
                            <span class="rounded-full bg-white px-2.5 py-1 text-[10px] font-semibold text-brand-700">Menunggu pembayaran</span>
                        </div>
                        @if (! empty($instructions['va_number']))
                            <p class="mt-3 text-sm text-slate-700">Nomor Virtual Account</p>
                            <p class="mt-1 rounded-lg bg-white px-4 py-3 font-mono text-lg font-bold text-slate-900">
                                {{ $instructions['va_number'] }}
                            </p>
                        @endif
                        @if (! empty($instructions['bill_key']) && ! empty($instructions['biller_code']))
                            <div class="mt-3 grid grid-cols-1 sm:grid-cols-2 gap-2">
                                <div class="rounded-lg bg-white px-4 py-3">
                                    <p class="text-xs text-slate-500">Bill Key</p>
                                    <p class="font-mono text-base font-bold text-slate-900">{{ $instructions['bill_key'] }}</p>
                                </div>
                                <div class="rounded-lg bg-white px-4 py-3">
                                    <p class="text-xs text-slate-500">Biller Code</p>
                                    <p class="font-mono text-base font-bold text-slate-900">{{ $instructions['biller_code'] }}</p>
                                </div>
                            </div>
                        @endif
                        @if (! empty($instructions['checkout_url']))
                            <a href="{{ $instructions['checkout_url'] }}" target="_blank" rel="noopener noreferrer" class="mt-3 inline-flex w-full items-center justify-center rounded-xl bg-slate-900 px-4 py-2.5 text-sm font-semibold text-white hover:bg-slate-800">
                                Buka halaman pembayaran
                            </a>
                        @endif
                        @if (! empty($instructions['deeplink_url']))
                            <a href="{{ $instructions['deeplink_url'] }}" target="_blank" rel="noopener noreferrer" class="mt-2 inline-flex w-full items-center justify-center rounded-xl bg-slate-900 px-4 py-2.5 text-sm font-semibold text-white hover:bg-slate-800">
                                Buka aplikasi e-wallet
                            </a>
                        @endif
                        <p class="mt-3 text-sm text-slate-700">
                            Batas waktu:
                            <span id="expiry-text" class="font-medium text-slate-900">
                                {{ ! empty($instructions['expiry_time']) ? Carbon::parse($instructions['expiry_time'])->timezone(config('app.timezone'))->format('d M Y, H:i') : '-' }} WIB
                            </span>
                        </p>
                        <p class="mt-1 text-sm text-slate-700">
                            Sisa waktu:
                            <span id="countdown-text" class="font-semibold text-slate-900">--:--:--</span>
                        </p>
                        <ol class="mt-3 list-decimal pl-5 text-xs text-slate-600 space-y-1">
                            <li>Pilih channel sesuai metode pembayaran yang dipilih.</li>
                            <li>Masukkan nomor referensi/VA sesuai instruksi.</li>
                            <li>Konfirmasi nominal pembayaran sampai berhasil.</li>
                            <li>Status booking akan update otomatis setelah webhook Midtrans diterima.</li>
                        </ol>
                        <div class="mt-4 flex flex-col gap-2 sm:flex-row">
                            <span class="inline-flex w-full items-center justify-center rounded-xl bg-emerald-600 px-4 py-2.5 text-sm font-semibold text-white">
                                Menunggu konfirmasi pembayaran otomatis...
                            </span>
                            <a href="{{ route('bookings.show', $booking) }}" class="inline-flex w-full items-center justify-center rounded-xl border border-slate-200 bg-white px-4 py-2.5 text-sm font-semibold text-slate-700 hover:bg-slate-50">
                                Lihat detail booking
                            </a>
                        </div>
                    </div>
                @endif
            </section>

            <aside class="space-y-5">
                <div class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
                    <h3 class="font-semibold text-slate-900">Ringkasan pesanan</h3>
                    <dl class="mt-4 space-y-3 text-sm">
                        <div class="flex justify-between gap-3">
                            <dt class="text-slate-500">Order ID</dt>
                            <dd class="font-mono text-xs text-slate-700 text-right">{{ $payment->order_id }}</dd>
                        </div>
                        <div class="flex justify-between gap-3">
                            <dt class="text-slate-500">Muthowif</dt>
                            <dd class="font-medium text-slate-900 text-right">{{ $booking->muthowifProfile->user->name ?? '-' }}</dd>
                        </div>
                        <div class="flex justify-between gap-3">
                            <dt class="text-slate-500">Periode</dt>
                            <dd class="font-medium text-slate-900 text-right">
                                {{ Carbon::parse($booking->starts_on)->format('d/m/Y') }} - {{ Carbon::parse($booking->ends_on)->format('d/m/Y') }}
                            </dd>
                        </div>
                        <div class="flex justify-between gap-3 border-t border-slate-100 pt-3">
                            <dt class="text-slate-500">Biaya platform (7,5%)</dt>
                            <dd class="font-medium text-slate-900 text-right">Rp {{ number_format($customerPlatformFee, 0, ',', '.') }}</dd>
                        </div>
                        <div class="flex justify-between gap-3">
                            <dt class="font-semibold text-slate-900">Total dibayar</dt>
                            <dd class="text-lg font-bold text-brand-700 text-right">Rp {{ number_format($customerTotal, 0, ',', '.') }}</dd>
                        </div>
                    </dl>
                </div>

                <div class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
                    <h3 class="font-semibold text-slate-900">Bantuan</h3>
                    <p class="mt-2 text-xs text-slate-600 leading-relaxed">
                        Setelah transfer berhasil, halaman ini akan redirect otomatis ke detail booking saat webhook Midtrans diterima.
                    </p>
                    <a href="{{ route('bookings.show', $booking) }}" class="mt-3 inline-flex items-center text-sm font-semibold text-brand-700 hover:text-brand-800">
                        Kembali ke detail booking
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
