@if ($offers->isEmpty())
    <p class="rounded-2xl border border-dashed border-slate-300 bg-slate-50 p-10 text-center text-sm text-slate-600">{{ __('emergency.muthowif.empty') }}</p>
@else
    <div class="ui-stack-compact">
        @foreach ($offers as $offer)
            @include('muthowif.emergency-offers.partials.offer-card', ['offer' => $offer])
        @endforeach
    </div>
    <div class="mt-4">{{ $offers->links() }}</div>
@endif
