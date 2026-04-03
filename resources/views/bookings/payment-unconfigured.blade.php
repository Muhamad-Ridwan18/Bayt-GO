<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-slate-800 leading-tight">
            Pembayaran
        </h2>
    </x-slot>

    <div class="py-8 sm:py-12">
        <div class="max-w-xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="rounded-2xl border border-amber-200 bg-amber-50 px-5 py-6 text-sm text-amber-950">
                <p class="font-semibold">Xendit belum dikonfigurasi</p>
                <p class="mt-2 leading-relaxed">
                    Isi <code>XENDIT_API_KEY</code> dan <code>XENDIT_WEBHOOK_TOKEN</code> di file .env, lalu set URL webhook di dashboard Xendit:
                </p>
                <p class="mt-3 font-mono text-xs break-all bg-white/60 rounded-lg px-3 py-2 border border-amber-200">
                    {{ url('/payments/xendit/notification') }}
                </p>
                <a href="{{ route('bookings.show', $booking) }}" class="mt-4 inline-block text-sm font-semibold text-brand-800 hover:text-brand-900">
                    ← Kembali ke detail booking
                </a>
            </div>
        </div>
    </div>
</x-app-layout>
