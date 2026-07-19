<x-app-layout>
    @php
        $customerDashBg = Auth::user()->isCustomer();
        $adminDash = Auth::user()->isAdmin();
        $muthowifDash = Auth::user()->isVerifiedMuthowif();
    @endphp
    <div class="relative min-h-[calc(100vh-4rem)] {{ $customerDashBg ? 'overflow-x-hidden bg-gradient-to-b from-welcomeCanvas via-white to-slate-50/80' : ($muthowifDash || $adminDash ? 'overflow-x-hidden bg-slate-50' : 'overflow-hidden bg-gradient-to-b from-slate-100 via-slate-50 to-white') }} @if ($muthowifDash) !pt-0 !pb-0 @endif">
        @unless ($customerDashBg || $adminDash || $muthowifDash)
            <div class="pointer-events-none absolute inset-0 bg-[radial-gradient(ellipse_80%_50%_at_50%_-20%,rgba(120,53,15,0.08),transparent)]"></div>
            <div class="pointer-events-none absolute right-0 top-24 h-72 w-72 rounded-full bg-brand-400/5 blur-3xl"></div>
            <div class="pointer-events-none absolute -left-20 bottom-0 h-64 w-64 rounded-full bg-violet-400/5 blur-3xl"></div>
        @endunless

        <x-page-container class="ui-stack-tight relative">

            @unless($adminDash || $customerDashBg)
                <x-campaign-carousel :campaigns="$activeCampaigns ?? collect()" />
            @endunless

            @if (Auth::user()->isCustomer())
                @include('partials.dashboard-customer')
            @elseif (Auth::user()->isVerifiedMuthowif())
                @include('partials.dashboard-muthowif')
            @elseif (Auth::user()->isAdmin())
                @include('partials.dashboard-admin')
            @elseif (Auth::user()->isMuthowif())
                <div class="relative overflow-hidden rounded-3xl border border-amber-200/90 bg-gradient-to-br from-amber-50 via-white to-orange-50/50 p-6 shadow-lg shadow-amber-900/5 ring-1 ring-amber-100 sm:p-8">
                    <div class="pointer-events-none absolute right-0 top-0 h-40 w-40 rounded-full bg-amber-300/20 blur-3xl"></div>
                    <div class="relative flex flex-col gap-6 sm:flex-row sm:items-start sm:justify-between">
                        <div class="flex gap-4">
                            <span class="flex h-14 w-14 shrink-0 items-center justify-center rounded-2xl bg-gradient-to-br from-amber-400 to-amber-600 text-white shadow-md shadow-amber-600/25" aria-hidden="true">
                                <svg class="h-7 w-7" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor"><path fill-rule="evenodd" d="M8.485 2.495c.673-1.167 2.357-1.167 3.03 0l6.28 10.875c.673 1.167-.17 2.625-1.516 2.625H3.72c-1.347 0-2.189-1.458-1.515-2.625L8.485 2.495zM10 5a.75.75 0 01.75.75v3.5a.75.75 0 01-1.5 0v-3.5A.75.75 0 0110 5zm0 9a1 1 0 100-2 1 1 0 000 2z" clip-rule="evenodd" /></svg>
                            </span>
                            <div>
                                <p class="text-sm font-semibold text-amber-900">{{ __('dashboard.pending_review_title') }}</p>
                                <p class="mt-1 text-lg font-bold text-slate-900">{{ __('dashboard.hello') }} {{ Auth::user()->name }}</p>
                                <p class="mt-2 max-w-xl text-sm leading-relaxed text-slate-600">
                                    {{ __('dashboard.pending_review_body') }}
                                </p>
                            </div>
                        </div>
                        <div class="flex flex-wrap gap-3 sm:justify-end">
                            <a href="{{ route('profile.edit') }}" class="inline-flex items-center gap-2 rounded-2xl border border-slate-200/90 bg-white px-5 py-2.5 text-sm font-semibold text-slate-800 shadow-sm ring-1 ring-slate-100 transition hover:bg-slate-50">
                                <svg class="h-5 w-5 text-slate-600" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M15.75 6a3.75 3.75 0 11-7.5 0 3.75 3.75 0 017.5 0zM4.501 20.118a7.5 7.5 0 0114.998 0A17.933 17.933 0 0112 21.75c-2.676 0-5.216-.584-7.499-1.632z" /></svg>
                                {{ __('dashboard.profile') }}
                            </a>
                            <a href="{{ route('layanan.index') }}" class="inline-flex items-center gap-2 rounded-2xl bg-gradient-to-r from-brand-600 to-brand-700 px-5 py-2.5 text-sm font-semibold text-white shadow-md shadow-brand-600/25 transition hover:from-brand-700 hover:to-brand-800">
                                <svg class="h-5 w-5 opacity-95" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M2.25 12l8.954-8.955c.44-.439 1.152-.439 1.591 0L21.75 15M4.5 9.75v10.125c0 .621.504 1.125 1.125 1.125H9.75v-4.875c0-.621.504-1.125 1.125-1.125h2.25c.621 0 1.125.504 1.125 1.125V21h4.125c.621 0 1.125-.504 1.125-1.125V9.75M8.25 21h8.25" /></svg>
                                {{ __('dashboard.view_marketplace') }}
                            </a>
                        </div>
                    </div>
                </div>
            @else
                <div class="relative overflow-hidden rounded-3xl bg-gradient-to-br from-brand-600 via-brand-700 to-brand-900 p-6 text-white shadow-xl shadow-brand-900/25 ring-1 ring-white/10 sm:p-8">
                    <div class="pointer-events-none absolute -right-10 -top-10 h-48 w-48 rounded-full bg-white/10 blur-3xl"></div>
                    <div class="pointer-events-none absolute bottom-0 left-0 h-32 w-64 rounded-full bg-amber-500/10 blur-3xl"></div>
                    <div class="relative flex flex-col gap-6 sm:flex-row sm:items-center sm:justify-between">
                        <div>
                            <p class="text-sm font-medium text-brand-100">{{ __('dashboard.hello') }}</p>
                            <p class="mt-1 text-3xl font-bold tracking-tight">{{ Auth::user()->name }}</p>
                            <p class="mt-2 text-sm text-brand-100/90">
                                {{ __('dashboard.signed_in_as') }}
                                <span class="font-semibold text-white">
                                    {{ Auth::user()->role->label() }}
                                </span>
                            </p>
                        </div>
                        <a href="{{ route('profile.edit') }}" class="inline-flex items-center gap-2 self-start rounded-2xl bg-white/15 px-5 py-2.5 text-sm font-semibold text-white ring-1 ring-white/30 backdrop-blur-sm transition hover:bg-white/25">
                            <svg class="h-5 w-5" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M15.75 6a3.75 3.75 0 11-7.5 0 3.75 3.75 0 017.5 0zM4.501 20.118a7.5 7.5 0 0114.998 0A17.933 17.933 0 0112 21.75c-2.676 0-5.216-.584-7.499-1.632z" /></svg>
                            {{ __('dashboard.profile_settings') }}
                        </a>
                    </div>
                </div>
            @endif


            @unless (Auth::user()->isAdmin() || Auth::user()->isVerifiedMuthowif() || Auth::user()->isCustomer())
                @include('partials.dashboard-next-profile-row')
            @endunless
        </x-page-container>
    </div>
</x-app-layout>
