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
                    @if (Auth::user()->isAdmin())
                        {!! __('dashboard.next_steps_admin', ['menu' => '<strong class="text-slate-800">'.e(__('dashboard.next_steps_admin_menu')).'</strong>']) !!}
                    @elseif (Auth::user()->isCustomer())
                        {{ __('dashboard.next_steps_customer') }}
                    @elseif (Auth::user()->isVerifiedMuthowif())
                        {!! __('dashboard.next_steps_verified_muthowif', ['menu' => '<strong class="text-slate-800">'.e(__('dashboard.next_steps_verified_menu')).'</strong>']) !!}
                    @elseif (Auth::user()->isMuthowif())
                        {{ __('dashboard.next_steps_pending_muthowif') }}
                    @else
                        {{ __('dashboard.next_steps_default') }}
                    @endif
                </p>
            </div>
        </div>
    </div>
    @unless (Auth::user()->isCustomer())
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
