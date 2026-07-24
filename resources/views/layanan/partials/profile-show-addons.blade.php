@if ($page->addonCards !== [])
    <x-ui.card pad="lg" class="block">
        <div class="flex items-start gap-3">
            <span class="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl bg-amber-50 text-amber-800" aria-hidden="true">
                <svg class="h-5 w-5" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor"><path d="M10.75 4.75a.75.75 0 00-1.5 0v4.5h-4.5a.75.75 0 000 1.5h4.5v4.5a.75.75 0 001.5 0v-4.5h4.5a.75.75 0 000-1.5h-4.5v-4.5z" /></svg>
            </span>
            <div>
                <h2 class="text-xl font-bold text-slate-900">{{ __('marketplace.show.addons_section') }}</h2>
                <p class="mt-1 text-sm text-slate-600">{{ __('marketplace.show.addons_sub') }}</p>
            </div>
        </div>

        <ul class="ui-section-body grid grid-cols-2 gap-3 sm:grid-cols-3 lg:grid-cols-4">
            @foreach ($page->addonCards as $addon)
                <li class="flex flex-col items-center rounded-xl border border-slate-100 bg-slate-50/80 px-3 py-4 text-center ring-1 ring-slate-100/80">
                    <span class="flex h-10 w-10 items-center justify-center rounded-full bg-white text-lg shadow-sm" aria-hidden="true">✨</span>
                    <p class="mt-2 text-sm font-semibold text-slate-800 line-clamp-2">{{ $addon['name'] }}</p>
                    <p class="mt-1 text-xs font-bold text-brand-700">Rp {{ $addon['price'] }}</p>
                </li>
            @endforeach
        </ul>
    </x-ui.card>
@endif
