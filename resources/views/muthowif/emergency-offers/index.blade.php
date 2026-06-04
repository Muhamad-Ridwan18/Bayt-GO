<x-app-layout>
    <x-page-container class="py-8">
        <h1 class="text-2xl font-bold text-slate-900">{{ __('emergency.muthowif.index_title') }}</h1>
        <p class="mt-1 max-w-2xl text-sm text-slate-600">{{ __('emergency.muthowif.accept_hint') }}</p>

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
            <div class="mt-6 space-y-6">
                @foreach ($offers as $offer)
                    @include('muthowif.emergency-offers.partials.offer-card', ['offer' => $offer])
                @endforeach
            </div>
            <div class="mt-4">{{ $offers->links() }}</div>
        @endif
        </div>
        </div>
    </x-page-container>
</x-app-layout>
