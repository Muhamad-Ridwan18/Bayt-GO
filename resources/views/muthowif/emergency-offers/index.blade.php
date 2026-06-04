<x-app-layout>
    <x-page-container class="py-8">
        <h1 class="text-2xl font-bold text-slate-900">{{ __('emergency.muthowif.index_title') }}</h1>

        @if (session('status'))
            <p class="mt-4 rounded-xl bg-emerald-50 px-4 py-3 text-sm text-emerald-900">{{ session('status') }}</p>
        @endif
        @if (session('error'))
            <p class="mt-4 rounded-xl bg-rose-50 px-4 py-3 text-sm text-rose-900">{{ session('error') }}</p>
        @endif

        <div
            class="mt-6"
            x-data="reverbFragmentLive({
                fragmentUrl: @js(route('muthowif.emergency-offers.index.live-fragment')),
                appendQuery: true,
                listeners: [
                    { channel: @js('App.Models.User.'.auth()->id()), event: '.emergency.report.updated' },
                ],
            })"
        >
        <div x-ref="liveRoot">
        @if ($offers->isEmpty())
            <p class="rounded-2xl border border-dashed border-slate-300 bg-slate-50 p-10 text-center text-sm text-slate-600">{{ __('emergency.muthowif.empty') }}</p>
        @else
            <div class="mt-6 space-y-4">
                @foreach ($offers as $offer)
                    @php $booking = $offer->report?->muthowifBooking; @endphp
                    <article class="rounded-2xl border border-amber-200 bg-white p-5 shadow-sm">
                        <p class="text-xs font-semibold uppercase text-amber-800">{{ __('emergency.muthowif.booking') }} {{ $booking?->booking_code }}</p>
                        <p class="mt-1 text-sm text-slate-700">
                            {{ __('emergency.muthowif.dates') }}:
                            {{ $booking?->starts_on?->format('d/m/Y') }} – {{ $booking?->ends_on?->format('d/m/Y') }}
                        </p>
                        <p class="mt-1 text-sm text-slate-600">{{ $offer->report?->case_type?->label() }} · {{ $offer->status->label() }}</p>

                        @if ($offer->status === \App\Enums\ReplacementOfferStatus::Offered)
                            <div class="mt-4 flex flex-wrap gap-2">
                                <form method="POST" action="{{ route('muthowif.emergency-offers.accept', $offer) }}">
                                    @csrf
                                    <x-submit-button class="rounded-xl bg-emerald-700 px-4 py-2 text-sm font-semibold text-white">{{ __('emergency.muthowif.accept') }}</x-submit-button>
                                </form>
                                <form method="POST" action="{{ route('muthowif.emergency-offers.decline', $offer) }}" class="flex flex-wrap items-end gap-2">
                                    @csrf
                                    <input type="text" name="decline_note" placeholder="Catatan (opsional)" class="rounded-lg border-slate-200 text-xs">
                                    <x-submit-button class="rounded-xl border border-slate-300 px-4 py-2 text-sm font-semibold text-slate-700">{{ __('emergency.muthowif.decline') }}</x-submit-button>
                                </form>
                            </div>
                        @elseif ($offer->status === \App\Enums\ReplacementOfferStatus::Accepted)
                            <p class="mt-3 text-sm font-medium text-emerald-800">{{ __('emergency.flash.offer_accepted') }}</p>
                        @endif
                    </article>
                @endforeach
            </div>
            <div class="mt-4">{{ $offers->links() }}</div>
        @endif
        </div>
        </div>
    </x-page-container>
</x-app-layout>
