<x-app-layout>
    <x-ui.app-page compact>
        <div class="pointer-events-none absolute inset-0 bg-[radial-gradient(ellipse_80%_40%_at_50%_-10%,rgba(15,42,37,0.07),transparent)]"></div>
        <x-page-container class="relative ui-stack-compact">
            <div class="mb-2">
                <p class="text-[11px] font-semibold uppercase tracking-wider text-baytgo/70">{{ __('nav.manage_services') }}</p>
                <h1 class="mt-1 text-xl font-bold tracking-tight text-slate-900 sm:text-2xl">{{ __('nav.manage_services_title') }}</h1>
                <p class="mt-1.5 max-w-xl text-sm text-slate-600">{{ __('nav.manage_services_lead') }}</p>
            </div>

            <div class="grid grid-cols-1 gap-3 sm:grid-cols-2 lg:grid-cols-3">
                @foreach ([
                    [
                        'href' => route('muthowif.pelayanan-pendukung.index', ['category' => 'mobility']),
                        'label' => __('nav.manage_svc_wheelchair'),
                        'desc' => __('nav.manage_svc_wheelchair_desc'),
                        'bg' => 'bg-[#0EA5E9]',
                        'icon' => 'wheelchair',
                    ],
                    [
                        'href' => route('muthowif.pelayanan.edit'),
                        'label' => __('nav.manage_svc_umrah'),
                        'desc' => __('nav.manage_svc_umrah_desc'),
                        'bg' => 'bg-[#0F766E]',
                        'icon' => 'M12 6.042A8.967 8.967 0 006 3.75c-1.052 0-2.062.18-3 .512v14.25A8.987 8.987 0 016 18c2.305 0 4.408.867 6 2.292m0-14.25a8.966 8.966 0 016-2.292c1.052 0 2.062.18 3 .512v14.25A8.987 8.987 0 0018 18a8.967 8.967 0 00-6 2.292m0-14.25v14.25',
                        'badge' => __('dashboard.customer_cat_utama'),
                    ],
                    [
                        'href' => route('muthowif.pelayanan-pendukung.index', ['category' => 'umrah']),
                        'label' => __('nav.manage_svc_prayer'),
                        'desc' => __('nav.manage_svc_prayer_desc'),
                        'bg' => 'bg-[#4F46E5]',
                        'icon' => 'mosque',
                    ],
                    [
                        'href' => route('muthowif.pelayanan-pendukung.index', ['category' => 'other']),
                        'label' => __('nav.manage_svc_photo'),
                        'desc' => __('nav.manage_svc_photo_desc'),
                        'bg' => 'bg-[#F43F5E]',
                        'icon' => 'M6.827 6.175A2.31 2.31 0 015.186 7.23c-.38.054-.757.112-1.134.175C2.999 7.58 2.25 8.507 2.25 9.574V18a2.25 2.25 0 002.25 2.25h15A2.25 2.25 0 0021.75 18V9.574c0-1.067-.75-1.994-1.802-2.169a47.865 47.865 0 00-1.134-.175 2.31 2.31 0 01-1.64-1.055l-.822-1.316a2.192 2.192 0 00-1.736-1.039 48.774 48.774 0 00-5.232 0 2.192 2.192 0 00-1.736 1.039l-.821 1.316z',
                        'icon2' => 'M16.5 12.75a4.5 4.5 0 11-9 0 4.5 4.5 0 019 0z',
                    ],
                    [
                        'href' => route('muthowif.pelayanan-pendukung.index', ['category' => 'ziarah']),
                        'label' => __('nav.manage_svc_raudhah'),
                        'desc' => __('nav.manage_svc_raudhah_desc'),
                        'bg' => 'bg-[#F97316]',
                        'icon' => 'M12 21v-8.25M15.75 21v-8.25M8.25 21v-8.25M3 9l9-6 9 6m-1.5 12V10.332A48.36 48.36 0 0012 9.75c-2.551 0-5.056.2-7.5.582V21',
                    ],
                ] as $item)
                    <a href="{{ $item['href'] }}" class="group relative flex items-start gap-4 rounded-2xl border border-slate-200/90 bg-white p-4 shadow-sm ring-1 ring-slate-100/80 transition hover:-translate-y-0.5 hover:border-baytgo/25 hover:shadow-md sm:p-5">
                        @if (! empty($item['badge']))
                            <span class="absolute right-3 top-3 rounded-full bg-gold px-2 py-0.5 text-[9px] font-bold uppercase tracking-wide text-baytgo-950">{{ $item['badge'] }}</span>
                        @endif
                        <span class="flex h-12 w-12 shrink-0 items-center justify-center rounded-full {{ $item['bg'] }} text-white shadow-sm transition group-hover:scale-105">
                            @if (($item['icon'] ?? '') === 'wheelchair')
                                <x-icons.wheelchair class="h-6 w-6" />
                            @elseif (($item['icon'] ?? '') === 'mosque')
                                <x-icons.mosque class="h-6 w-6" />
                            @else
                                <svg class="h-6 w-6" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.7" stroke="currentColor" aria-hidden="true">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="{{ $item['icon'] }}" />
                                    @if (! empty($item['icon2']))
                                        <path stroke-linecap="round" stroke-linejoin="round" d="{{ $item['icon2'] }}" />
                                    @endif
                                </svg>
                            @endif
                        </span>
                        <span class="min-w-0 flex-1 pe-8">
                            <span class="block text-sm font-bold text-slate-900 group-hover:text-baytgo">{{ $item['label'] }}</span>
                            <span class="mt-1 block text-xs leading-relaxed text-slate-600">{{ $item['desc'] }}</span>
                        </span>
                        <svg class="absolute bottom-4 right-4 h-4 w-4 text-slate-300 transition group-hover:translate-x-0.5 group-hover:text-baytgo" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="m8.25 4.5 7.5 7.5-7.5 7.5"/></svg>
                    </a>
                @endforeach
            </div>
        </x-page-container>
    </x-ui.app-page>
</x-app-layout>
