@php
    $isCustomer = Auth::check() && Auth::user()->isCustomer();
@endphp

@if ($isCustomer)
<nav
    class="fixed inset-x-0 bottom-0 z-[80] border-t border-slate-200/90 bg-white/95 pb-[env(safe-area-inset-bottom)] shadow-[0_-8px_30px_-12px_rgba(15,42,37,0.12)] backdrop-blur lg:hidden"
    aria-label="{{ __('dashboard.customer_bottom_home') }}"
>
    <div class="mx-auto grid max-w-lg grid-cols-4 gap-1 px-2 pt-1.5 pb-1.5">
        <a
            href="{{ route('dashboard') }}"
            @class([
                'flex flex-col items-center gap-0.5 rounded-xl px-2 py-2 text-[10px] font-semibold transition',
                'bg-emerald-50 text-baytgo' => request()->routeIs('dashboard'),
                'text-slate-500 hover:bg-slate-50 hover:text-slate-800' => ! request()->routeIs('dashboard'),
            ])
        >
            <svg class="h-5 w-5" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.7" stroke="currentColor" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M2.25 12l8.954-8.955c.44-.439 1.152-.439 1.591 0L21.75 12M4.5 9.75v10.125c0 .621.504 1.125 1.125 1.125H9.75v-4.875c0-.621.504-1.125 1.125-1.125h2.25c.621 0 1.125.504 1.125 1.125V21h4.125c.621 0 1.125-.504 1.125-1.125V9.75M8.25 21h8.25"/></svg>
            <span>{{ __('dashboard.customer_bottom_home') }}</span>
        </a>

        <a
            href="{{ route('bookings.index') }}"
            @class([
                'flex flex-col items-center gap-0.5 rounded-xl px-2 py-2 text-[10px] font-semibold transition',
                'bg-emerald-50 text-baytgo' => request()->routeIs('bookings.*'),
                'text-slate-500 hover:bg-slate-50 hover:text-slate-800' => ! request()->routeIs('bookings.*'),
            ])
        >
            <svg class="h-5 w-5" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.7" stroke="currentColor" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M6.75 3v2.25M17.25 3v2.25M3 18.75V7.5a2.25 2.25 0 012.25-2.25h13.5A2.25 2.25 0 0121 7.5v11.25m-18 0A2.25 2.25 0 005.25 21h13.5a2.25 2.25 0 002.25-2.25m-18 0v-7.5A2.25 2.25 0 015.25 9h13.5a2.25 2.25 0 012.25 2.25v7.5m-9-6h.008v.008H12v-.008zM12 15h.008v.008H12V15zm0 2.25h.008v.008H12v-.008zM9.75 15h.008v.008H9.75V15zm0 2.25h.008v.008H9.75v-.008zM7.5 15h.008v.008H7.5V15zm0 2.25h.008v.008H7.5v-.008zm6.75-4.5h.008v.008h-.008v-.008zm0 2.25h.008v.008h-.008V15zm0 2.25h.008v.008h-.008v-.008zm2.25-4.5h.008v.008H16.5v-.008zm0 2.25h.008v.008H16.5V15z"/></svg>
            <span>{{ __('dashboard.customer_bottom_bookings') }}</span>
        </a>

        <button
            type="button"
            onclick="window.dispatchEvent(new CustomEvent('open-global-chat-panel'))"
            class="flex flex-col items-center gap-0.5 rounded-xl px-2 py-2 text-[10px] font-semibold text-slate-500 transition hover:bg-slate-50 hover:text-slate-800"
        >
            <svg class="h-5 w-5" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.7" stroke="currentColor" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M20.25 8.511c.884.284 1.5 1.128 1.5 2.097v4.286c0 1.245-.986 2.31-2.236 2.436-.348.036-.695.06-1.043.079v3.24a.75.75 0 01-1.28.53l-3.244-3.243a8.955 8.955 0 01-1.236.084 8.91 8.91 0 01-5.033-1.55 8.91 8.91 0 01-2.915-3.33A8.91 8.91 0 013.75 9.75c0-1.63.437-3.157 1.2-4.47A8.955 8.955 0 019.75 2.25c2.485 0 4.71 1.006 6.33 2.63"/></svg>
            <span>{{ __('dashboard.customer_bottom_chat') }}</span>
        </button>

        <a
            href="{{ route('profile.edit') }}"
            @class([
                'flex flex-col items-center gap-0.5 rounded-xl px-2 py-2 text-[10px] font-semibold transition',
                'bg-emerald-50 text-baytgo' => request()->routeIs('profile.*'),
                'text-slate-500 hover:bg-slate-50 hover:text-slate-800' => ! request()->routeIs('profile.*'),
            ])
        >
            <svg class="h-5 w-5" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.7" stroke="currentColor" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M15.75 6a3.75 3.75 0 11-7.5 0 3.75 3.75 0 017.5 0zM4.501 20.118a7.5 7.5 0 0114.998 0A17.933 17.933 0 0112 21.75c-2.676 0-5.216-.584-7.499-1.632z"/></svg>
            <span>{{ __('dashboard.customer_bottom_account') }}</span>
        </a>
    </div>
</nav>
@endif
