<x-app-layout>
    <x-page-container class="ui-page-y ui-stack-compact">
        <h1 class="text-2xl font-bold text-slate-900">{{ __('emergency.muthowif.index_title') }}</h1>
        <p class="mt-1 max-w-2xl text-sm text-slate-600">{{ __('emergency.muthowif.accept_hint') }}</p>

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
            <div class="mt-6 ui-stack-compact">
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
