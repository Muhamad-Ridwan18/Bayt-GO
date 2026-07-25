@php
    $user = Auth::user();
    $isMuthowif = $user && $user->isMuthowif();
    $isVerified = $isMuthowif && $user->isVerifiedMuthowif();
    $pendingIncoming = (int) ($muthowifPendingIncomingCount ?? 0);
@endphp

@if ($isMuthowif)
<nav
    class="pointer-events-none fixed inset-x-0 bottom-0 z-[80] px-4 pb-[max(0.75rem,env(safe-area-inset-bottom))] lg:hidden"
    aria-label="{{ __('dashboard.muthowif_bottom_home') }}"
>
    <div class="pointer-events-auto mx-auto flex max-w-[24rem] items-stretch gap-0.5 rounded-2xl border border-slate-200/90 bg-white/95 px-1 py-1.5 shadow-[0_10px_32px_-10px_rgba(15,42,37,0.28)] backdrop-blur">
        <a
            href="{{ route('dashboard') }}"
            @class([
                'flex min-w-0 flex-1 flex-col items-center gap-0.5 rounded-xl px-1 py-2 text-[10px] font-semibold transition',
                'bg-emerald-50 text-baytgo' => request()->routeIs('dashboard'),
                'text-slate-500 hover:bg-slate-50 hover:text-slate-800' => ! request()->routeIs('dashboard'),
            ])
        >
            <svg class="h-5 w-5 shrink-0" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.7" stroke="currentColor" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M2.25 12l8.954-8.955c.44-.439 1.152-.439 1.591 0L21.75 12M4.5 9.75v10.125c0 .621.504 1.125 1.125 1.125H9.75v-4.875c0-.621.504-1.125 1.125-1.125h2.25c.621 0 1.125.504 1.125 1.125V21h4.125c.621 0 1.125-.504 1.125-1.125V9.75M8.25 21h8.25"/></svg>
            <span class="truncate">{{ __('dashboard.muthowif_bottom_home') }}</span>
        </a>

        <a
            href="{{ $isVerified ? route('muthowif.bookings.index') : route('support.index') }}"
            @class([
                'relative flex min-w-0 flex-1 flex-col items-center gap-0.5 rounded-xl px-1 py-2 text-[10px] font-semibold transition',
                'bg-emerald-50 text-baytgo' => $isVerified ? request()->routeIs('muthowif.bookings.*') : request()->routeIs('support.*'),
                'text-slate-500 hover:bg-slate-50 hover:text-slate-800' => $isVerified ? ! request()->routeIs('muthowif.bookings.*') : ! request()->routeIs('support.*'),
            ])
        >
            <span class="relative inline-flex">
                <svg class="h-5 w-5 shrink-0" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.7" stroke="currentColor" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M6.75 3v2.25M17.25 3v2.25M3 18.75V7.5a2.25 2.25 0 012.25-2.25h13.5A2.25 2.25 0 0121 7.5v11.25m-18 0A2.25 2.25 0 005.25 21h13.5a2.25 2.25 0 002.25-2.25m-18 0v-7.5A2.25 2.25 0 015.25 9h13.5a2.25 2.25 0 012.25 2.25v7.5m-9-6h.008v.008H12v-.008zM12 15h.008v.008H12V15zm0 2.25h.008v.008H12v-.008zM9.75 15h.008v.008H9.75V15zm0 2.25h.008v.008H9.75v-.008zM7.5 15h.008v.008H7.5V15zm0 2.25h.008v.008H7.5v-.008zm6.75-4.5h.008v.008h-.008v-.008zm0 2.25h.008v.008h-.008V15zm0 2.25h.008v.008h-.008v-.008zm2.25-4.5h.008v.008H16.5v-.008zm0 2.25h.008v.008H16.5V15z"/></svg>
                @if ($isVerified)
                    <span
                        x-data="muthowifPendingBookingsBadge({
                            userId: @js(auth()->id()),
                            countUrl: @js(route('muthowif.bookings.pending-incoming-count')),
                            initialCount: @js($pendingIncoming),
                        })"
                        x-show="count > 0"
                        x-cloak
                        class="absolute -end-1.5 -top-1 inline-flex min-h-[1rem] min-w-[1rem] items-center justify-center rounded-full bg-red-600 px-0.5 text-[9px] font-bold leading-none text-white"
                        x-text="displayLabel"
                    ></span>
                @endif
            </span>
            <span class="truncate">{{ __('dashboard.muthowif_bottom_bookings') }}</span>
        </a>

        <a
            href="{{ $isVerified ? route('muthowif.kelola-layanan') : route('affiliate.index') }}"
            @class([
                'flex min-w-0 flex-1 flex-col items-center gap-0.5 rounded-xl px-1 py-2 text-[10px] font-semibold transition',
                'bg-emerald-50 text-baytgo' => $isVerified
                    ? request()->routeIs(['muthowif.kelola-layanan', 'muthowif.pelayanan.*', 'muthowif.pelayanan-pendukung.*'])
                    : request()->routeIs('affiliate.*'),
                'text-slate-500 hover:bg-slate-50 hover:text-slate-800' => $isVerified
                    ? ! request()->routeIs(['muthowif.kelola-layanan', 'muthowif.pelayanan.*', 'muthowif.pelayanan-pendukung.*'])
                    : ! request()->routeIs('affiliate.*'),
            ])
        >
            <svg class="h-5 w-5 shrink-0" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.7" stroke="currentColor" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M20.25 14.15v4.25c0 1.094-.787 2.036-1.872 2.18-2.087.277-4.216.42-6.378.42s-4.291-.143-6.378-.42c-1.085-.144-1.872-1.086-1.872-2.18v-4.25m16.5 0a2.18 2.18 0 00.75-1.661V8.706c0-1.081-.768-2.015-1.837-2.175a48.114 48.114 0 00-3.413-.387m4.5 2.707a48.667 48.667 0 00-1.837-.387m0 0A48.667 48.667 0 0012 7.5c-2.291 0-4.545.16-6.75.468m0 0a48.667 48.667 0 00-1.837.387m8.25 0c.18.064.368.125.554.184a48.11 48.11 0 013.413.387m-12.75 0a48.114 48.114 0 013.413-.387m0 0c.186-.059.374-.12.554-.184M12 12.75h.008v.008H12v-.008z"/></svg>
            <span class="truncate">{{ __('dashboard.muthowif_bottom_services') }}</span>
        </a>

        <a
            href="{{ route('chat.index') }}"
            @class([
                'flex min-w-0 flex-1 flex-col items-center gap-0.5 rounded-xl px-1 py-2 text-[10px] font-semibold transition',
                'bg-emerald-50 text-baytgo' => request()->routeIs('chat.*'),
                'text-slate-500 hover:bg-slate-50 hover:text-slate-800' => ! request()->routeIs('chat.*'),
            ])
        >
            <svg class="h-5 w-5 shrink-0" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.7" stroke="currentColor" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M20.25 8.511c.884.284 1.5 1.128 1.5 2.097v4.286c0 1.245-.986 2.31-2.236 2.436-.348.036-.695.06-1.043.079v3.24a.75.75 0 01-1.28.53l-3.244-3.243a8.955 8.955 0 01-1.236.084 8.91 8.91 0 01-5.033-1.55 8.91 8.91 0 01-2.915-3.33A8.91 8.91 0 013.75 9.75c0-1.63.437-3.157 1.2-4.47A8.955 8.955 0 019.75 2.25c2.485 0 4.71 1.006 6.33 2.63"/></svg>
            <span class="truncate">{{ __('dashboard.muthowif_bottom_chat') }}</span>
        </a>

        <a
            href="{{ route('profile.edit') }}"
            @class([
                'flex min-w-0 flex-1 flex-col items-center gap-0.5 rounded-xl px-1 py-2 text-[10px] font-semibold transition',
                'bg-emerald-50 text-baytgo' => request()->routeIs('profile.*'),
                'text-slate-500 hover:bg-slate-50 hover:text-slate-800' => ! request()->routeIs('profile.*'),
            ])
        >
            <svg class="h-5 w-5 shrink-0" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.7" stroke="currentColor" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M15.75 6a3.75 3.75 0 11-7.5 0 3.75 3.75 0 017.5 0zM4.501 20.118a7.5 7.5 0 0114.998 0A17.933 17.933 0 0112 21.75c-2.676 0-5.216-.584-7.499-1.632z"/></svg>
            <span class="truncate">{{ __('dashboard.muthowif_bottom_account') }}</span>
        </a>
    </div>
</nav>
@endif
