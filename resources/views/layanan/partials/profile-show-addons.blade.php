@if ($page->addonCards !== [])
    <x-ui.card pad="md" class="block">
        <div class="flex items-start gap-2.5">
            <span class="flex h-8 w-8 shrink-0 items-center justify-center rounded-lg bg-amber-50 text-amber-800" aria-hidden="true">
                <svg class="h-4 w-4" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor"><path d="M10.75 4.75a.75.75 0 00-1.5 0v4.5h-4.5a.75.75 0 000 1.5h4.5v4.5a.75.75 0 001.5 0v-4.5h4.5a.75.75 0 000-1.5h-4.5v-4.5z" /></svg>
            </span>
            <div>
                <h2 class="text-lg font-bold text-slate-900">{{ __('marketplace.show.addons_section') }}</h2>
                <p class="mt-0.5 text-xs text-slate-600">{{ __('marketplace.show.addons_sub') }}</p>
            </div>
        </div>

        <ul class="mt-4 grid grid-cols-2 gap-2 sm:grid-cols-3 lg:grid-cols-4 xl:grid-cols-5">
            @foreach ($page->addonCards as $addon)
                <li class="flex flex-col rounded-lg border border-slate-100 bg-slate-50/80 px-2.5 py-2.5 ring-1 ring-slate-100/80">
                    <p class="text-xs font-semibold leading-snug text-slate-800 line-clamp-2">{{ $addon['name'] }}</p>
                    <p class="mt-1.5 text-[11px] font-bold tabular-nums text-brand-700">Rp {{ $addon['price'] }}</p>
                </li>
            @endforeach
        </ul>
    </x-ui.card>
@endif
