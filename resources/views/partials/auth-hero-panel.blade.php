@php
    $isRegister = ($variant ?? 'default') === 'register';
    $panelTitle = $isRegister ? __('guest.panel_register_title') : __('guest.panel_login_title');
    $panelSub = $isRegister ? __('guest.panel_register_sub') : __('guest.panel_login_sub');
@endphp

<aside class="relative hidden overflow-hidden bg-welcomeCanvas lg:flex lg:flex-col">
    <div class="pointer-events-none absolute inset-0" aria-hidden="true">
        <img
            src="{{ $heroImage }}"
            alt=""
            class="h-full w-full object-cover object-[72%_28%]"
            loading="lazy"
            decoding="async"
        />
    </div>
    <div class="pointer-events-none absolute inset-0 bg-gradient-to-r from-welcomeCanvas from-[18%] via-welcomeCanvas/95 via-[42%] to-welcomeCanvas/10" aria-hidden="true"></div>
    <div class="pointer-events-none absolute inset-y-0 left-0 w-[58%] bg-gradient-to-r from-welcomeCanvas/98 to-transparent" aria-hidden="true"></div>

    <div class="relative z-10 flex min-h-full flex-col justify-between p-10 xl:p-14">
        <div class="max-w-lg">
            <a href="{{ url('/') }}" class="inline-flex items-center gap-3 text-baytgo transition hover:opacity-90">
                <x-site-logo variant="guest" />
                <span class="text-xl font-bold tracking-tight">Bayt<span class="text-gold-muted">Go</span></span>
            </a>
            <p class="mt-8 inline-flex items-center gap-2 rounded-full border border-emerald-200/70 bg-white/70 px-4 py-1.5 text-[10px] font-bold uppercase tracking-[0.16em] text-baytgo">
                <span class="h-1.5 w-1.5 rounded-full bg-emerald-500"></span>
                {{ __('guest.panel_kicker') }}
            </p>
            <h2 class="mt-6 text-[2rem] font-bold leading-[1.15] tracking-tight text-baytgo xl:text-[2.35rem]">
                {{ $panelTitle }}
            </h2>
            <p class="mt-4 text-base leading-relaxed text-slate-600">
                {{ $panelSub }}
            </p>

            <ul class="mt-9 space-y-3.5">
                @foreach ([
                    ['icon' => 'M9 12.75 11.25 15 15 9.75M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z', 'label' => __('guest.trust_verified')],
                    ['icon' => 'M6.75 3v2.25M17.25 3v2.25M3 18.75V7.5a2.25 2.25 0 0 1 2.25-2.25h13.5A2.25 2.25 0 0 1 21 7.5v11.25m-18 0A2.25 2.25 0 0 0 5.25 21h13.5A2.25 2.25 0 0 0 21 18.75m-18 0v-7.5A2.25 2.25 0 0 1 5.25 9h13.5A2.25 2.25 0 0 1 21 11.25v7.5', 'label' => __('guest.trust_schedule')],
                    ['icon' => 'M16.5 10.5V6.75a4.5 4.5 0 1 0-9 0v3.75m-.75 11.25h10.5a2.25 2.25 0 0 0 2.25-2.25v-6.75a2.25 2.25 0 0 0-2.25-2.25H6.75a2.25 2.25 0 0 0-2.25 2.25v6.75a2.25 2.25 0 0 0 2.25 2.25Z', 'label' => __('guest.trust_secure')],
                ] as $item)
                    <li class="flex items-center gap-3 text-sm font-semibold text-slate-700">
                        <span class="flex h-9 w-9 shrink-0 items-center justify-center rounded-full bg-emerald-50 text-baytgo ring-1 ring-emerald-100">
                            <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="1.75" stroke="currentColor" aria-hidden="true">
                                <path stroke-linecap="round" stroke-linejoin="round" d="{{ $item['icon'] }}" />
                            </svg>
                        </span>
                        {{ $item['label'] }}
                    </li>
                @endforeach
            </ul>
        </div>

        <blockquote class="max-w-lg rounded-2xl border border-white/80 bg-white/75 p-5 shadow-sm backdrop-blur-sm">
            <p class="text-sm leading-relaxed text-slate-700">{{ __('guest.panel_quote') }}</p>
            <footer class="mt-3 text-[11px] font-bold uppercase tracking-[0.12em] text-slate-500">— {{ __('guest.panel_quote_by') }}</footer>
        </blockquote>
    </div>
</aside>
