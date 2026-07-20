@props(['tiles'])

<div class="relative overflow-hidden rounded-3xl bg-baytgo p-5 text-white shadow-[0_20px_40px_-14px_rgba(26,61,52,0.35)] ring-1 ring-white/10 sm:p-6">
    <div class="relative">
        <p class="text-sm font-bold text-white">{{ __('dashboard.customer_status_title') }}</p>
        <p class="mt-1 text-xs text-white/75">{{ __('dashboard.customer_status_sub') }}</p>
    </div>
    <div class="relative mt-5 grid grid-cols-2 gap-3">
        @foreach ($tiles as $tile)
            @if (! empty($tile['href']))
                <a href="{{ $tile['href'] }}" class="rounded-2xl bg-white/10 p-3.5 ring-1 ring-white/15 transition hover:bg-white/15">
                    <p class="text-2xl font-bold tabular-nums">{{ $tile['value'] }}</p>
                    <p class="mt-0.5 text-[11px] font-medium leading-tight text-white/85">{{ $tile['label'] }}</p>
                </a>
            @else
                <div class="rounded-2xl bg-white/10 p-3.5 ring-1 ring-white/15">
                    <p class="text-2xl font-bold tabular-nums">{{ $tile['value'] }}</p>
                    <p class="mt-0.5 text-[11px] font-medium leading-tight text-white/85">{{ $tile['label'] }}</p>
                </div>
            @endif
        @endforeach
    </div>
</div>
