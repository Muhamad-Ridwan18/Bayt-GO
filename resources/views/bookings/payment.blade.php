<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-col gap-1 sm:flex-row sm:items-center sm:justify-between">
            <h2 class="font-semibold text-xl text-slate-800 leading-tight">Pembayaran Xendit</h2>
            <a href="{{ route('bookings.show', $booking) }}" class="text-sm font-semibold text-brand-700 hover:text-brand-800">Kembali ke detail</a>
        </div>
    </x-slot>

    <div class="py-8 sm:py-12">
        <div class="max-w-lg mx-auto px-4 sm:px-6 lg:px-8">
            <div class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm text-center space-y-4">
                <p class="text-sm text-slate-600">Total pembayaran (customer)</p>
                <p class="text-2xl font-bold text-slate-900">Rp {{ number_format($payment->gross_amount, 0, ',', '.') }}</p>
                <p class="text-xs text-slate-500">
                    Biaya platform total {{ \App\Support\PlatformFee::TOTAL_RATE * 100 }}% (7,5% dari customer + 7,5% dari muthowif).
                    Fee: Rp {{ number_format((float) $payment->platform_fee_amount, 0, ',', '.') }}.
                    Order: {{ $payment->order_id }}
                </p>
                <div class="space-y-3">
                    <a href="{{ $paymentUrl }}" target="_blank" rel="noopener noreferrer" class="inline-flex w-full items-center justify-center rounded-xl bg-brand-600 px-6 py-3 text-sm font-semibold text-white shadow-md hover:bg-brand-700">
                        Bayar dengan Xendit (buka invoice)
                    </a>
                    <a href="{{ route('bookings.show', $booking) }}" class="inline-flex w-full items-center justify-center rounded-xl border border-slate-200 bg-white px-6 py-3 text-sm font-semibold text-slate-700 hover:bg-slate-50">
                        Kembali ke detail booking (cek status)
                    </a>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
