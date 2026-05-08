<x-app-layout>
    @php
        $customerDashBg = Auth::user()->isCustomer();
        $adminDash = Auth::user()->isAdmin();
    @endphp
    <div class="relative min-h-[calc(100vh-4rem)] {{ $customerDashBg ? 'overflow-x-hidden bg-gradient-to-b from-welcomeCanvas via-white to-slate-50 py-6 sm:py-8' : ($adminDash ? 'overflow-x-hidden bg-slate-50/95 py-0' : 'overflow-hidden bg-gradient-to-b from-slate-100 via-slate-50 to-white py-8 sm:py-12') }} @if (Auth::user()->isVerifiedMuthowif()) !py-5 sm:!py-6 @endif">
        @unless ($customerDashBg || $adminDash)
            <div class="pointer-events-none absolute inset-0 bg-[radial-gradient(ellipse_80%_50%_at_50%_-20%,rgba(120,53,15,0.08),transparent)]"></div>
            <div class="pointer-events-none absolute right-0 top-24 h-72 w-72 rounded-full bg-brand-400/5 blur-3xl"></div>
            <div class="pointer-events-none absolute -left-20 bottom-0 h-64 w-64 rounded-full bg-violet-400/5 blur-3xl"></div>
        @endunless

        <div class="relative mx-auto {{ $adminDash ? 'max-w-none' : 'max-w-7xl' }} space-y-10 {{ $adminDash ? 'px-0' : 'px-4 sm:px-6 lg:px-8' }}">

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


            <div class="grid grid-cols-1 gap-5 {{ Auth::user()->isCustomer() ? '' : 'lg:grid-cols-2' }}">
                <div class="group relative overflow-hidden rounded-3xl border border-slate-200/80 bg-gradient-to-br from-white to-amber-50/30 p-6 shadow-md shadow-slate-200/30 ring-1 ring-slate-100/90 transition hover:shadow-lg hover:shadow-amber-100/50">
                    <div class="pointer-events-none absolute -right-8 top-0 h-24 w-24 rounded-full bg-amber-200/30 blur-2xl transition group-hover:bg-amber-200/40"></div>
                    <div class="relative flex gap-4">
                        <span class="flex h-12 w-12 shrink-0 items-center justify-center rounded-2xl bg-gradient-to-br from-amber-400 to-amber-600 text-white shadow-md shadow-amber-600/25 ring-1 ring-white/30" aria-hidden="true">
                            <svg class="h-6 w-6" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor"><path fill-rule="evenodd" d="M6 2a1 1 0 00-1 1v1H4a2 2 0 00-2 2v10a2 2 0 002 2h12a2 2 0 002-2V6a2 2 0 00-2-2h-1V3a1 1 0 10-2 0v1H7V3a1 1 0 00-1-1zm0 5a1 1 0 000 2h8a1 1 0 100-2H6z" clip-rule="evenodd" /></svg>
                        </span>
                        <div class="min-w-0 flex-1">
                            <h3 class="font-semibold text-slate-900">{{ __('dashboard.next_steps') }}</h3>
                            <p class="mt-2 text-sm leading-relaxed text-slate-600">
                                @if(Auth::user()->isAdmin())
                                    {!! __('dashboard.next_steps_admin', ['menu' => '<strong class="text-slate-800">'.e(__('dashboard.next_steps_admin_menu')).'</strong>']) !!}
                                @elseif(Auth::user()->isCustomer())
                                    {{ __('dashboard.next_steps_customer') }}
                                @elseif(Auth::user()->isVerifiedMuthowif())
                                    {!! __('dashboard.next_steps_verified_muthowif', ['menu' => '<strong class="text-slate-800">'.e(__('dashboard.next_steps_verified_menu')).'</strong>']) !!}
                                @elseif(Auth::user()->isMuthowif())
                                    {{ __('dashboard.next_steps_pending_muthowif') }}
                                @else
                                    {{ __('dashboard.next_steps_default') }}
                                @endif
                            </p>
                        </div>
                    </div>
                </div>
                @unless(Auth::user()->isCustomer())
                    <div class="group relative overflow-hidden rounded-3xl border border-slate-200/80 bg-gradient-to-br from-white via-white to-brand-50/40 p-6 shadow-md shadow-slate-200/30 ring-1 ring-slate-100/90 transition hover:shadow-lg hover:shadow-brand-100/40">
                        <div class="pointer-events-none absolute -left-6 bottom-0 h-28 w-28 rounded-full bg-brand-300/20 blur-2xl transition group-hover:bg-brand-300/30"></div>
                        <div class="relative flex gap-4">
                            <span class="flex h-12 w-12 shrink-0 items-center justify-center rounded-2xl bg-gradient-to-br from-brand-500 to-brand-700 text-white shadow-md shadow-brand-600/25 ring-1 ring-white/25" aria-hidden="true">
                                <svg class="h-6 w-6" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor"><path fill-rule="evenodd" d="M10 9a3 3 0 100-6 3 3 0 000 6zm-7 9a7 7 0 1114 0H3z" clip-rule="evenodd" /></svg>
                            </span>
                            <div class="min-w-0 flex-1">
                                <h3 class="font-semibold text-slate-900">{{ __('dashboard.profile_card_title') }}</h3>
                                <p class="mt-2 text-sm leading-relaxed text-slate-600">
                                    {{ __('dashboard.profile_card_desc') }}
                                </p>
                                <a href="{{ route('profile.edit') }}" class="mt-5 inline-flex items-center gap-2 rounded-2xl bg-gradient-to-r from-brand-600 to-brand-700 px-5 py-2.5 text-sm font-semibold text-white shadow-lg shadow-brand-600/25 transition hover:from-brand-700 hover:to-brand-800">
                                    <svg class="h-4 w-4 opacity-95" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true"><path fill-rule="evenodd" d="M7.84 1.804A1 1 0 018.82 1h2.36a1 1 0 01.98.804l.331 1.652a6.993 6.993 0 011.929 1.115l1.598-.54a1 1 0 011.186.447l1.18 2.044a1 1 0 01-.205 1.251l-1.267 1.113a7.047 7.047 0 010 2.228l1.267 1.113a1 1 0 01.206 1.25l-1.18 2.045a1 1 0 01-1.187.447l-1.598-.54A6.993 6.993 0 0111.82 15.33l-.33 1.652a1 1 0 01-.98.804H8.82a1 1 0 01-.98-.804l-.331-1.652a6.993 6.993 0 01-1.929-1.115l-1.598.54a1 1 0 01-1.186-.447l-1.18-2.044a1 1 0 01.205-1.251l1.267-1.114a7.05 7.05 0 010-2.227L1.821 7.773a1 1 0 01-.206-1.25l1.18-2.045a1 1 0 011.187-.447l1.598.54A6.993 6.993 0 017.51 3.456l.33-1.652z" clip-rule="evenodd" /><path d="M10 13a3 3 0 100-6 3 3 0 000 6z" /></svg>
                                    {{ __('dashboard.profile_cta') }}
                                </a>
                            </div>
                        </div>
                    </div>
                @endunless
            </div>
        </div>
    </div>
</x-app-layout>
