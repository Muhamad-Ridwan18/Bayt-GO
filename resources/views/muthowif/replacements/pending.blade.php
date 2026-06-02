<x-app-layout>
    <div class="mx-auto max-w-3xl px-4 py-8 sm:px-6">
        <h1 class="text-lg font-bold text-slate-900">Penugasan pengganti</h1>
        <p class="mt-1 text-sm text-slate-600">Konfirmasi ketersediaan sebelum penawaran ke jamaah (sesuai SOP BaytGo).</p>

        <div class="mt-6 space-y-4">
            @forelse ($replacements as $replacement)
                @php $booking = $replacement->incident->muthowifBooking; @endphp
                <article class="rounded-2xl border border-violet-200 bg-white p-5 shadow-sm">
                    <p class="font-semibold text-slate-900">{{ $booking?->customer?->name }}</p>
                    <p class="text-xs text-slate-500 font-mono">{{ $booking?->booking_code }}</p>
                    <div class="mt-4 flex flex-wrap gap-2">
                        <form method="POST" action="{{ route('muthowif.bookings.replacements.confirm', [$booking, $replacement]) }}">@csrf
                            <button class="rounded-xl bg-emerald-700 px-4 py-2 text-sm font-semibold text-white">{{ __('incidents.muthowif_confirm_replacement') }}</button>
                        </form>
                        <form method="POST" action="{{ route('muthowif.bookings.replacements.decline', [$booking, $replacement]) }}" class="flex gap-2">@csrf
                            <button class="rounded-xl border border-slate-300 px-4 py-2 text-sm font-semibold text-slate-800">{{ __('incidents.muthowif_decline_replacement') }}</button>
                        </form>
                    </div>
                </article>
            @empty
                <p class="text-sm text-slate-500">Tidak ada penugasan menunggu konfirmasi.</p>
            @endforelse
        </div>

        {{ $replacements->links() }}
    </div>
</x-app-layout>
