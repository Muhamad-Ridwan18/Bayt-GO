<x-app-layout>
    <div class="relative min-h-[calc(100vh-4rem)] overflow-hidden bg-gradient-to-b from-slate-100 via-slate-50 to-white py-6 sm:py-8">
        <div class="pointer-events-none absolute inset-0 bg-[radial-gradient(ellipse_80%_40%_at_50%_-10%,rgba(120,53,15,0.06),transparent)]"></div>
        <div class="relative mx-auto max-w-4xl space-y-6 px-4 sm:px-6 lg:px-8">
            {{-- Hero --}}
            <div class="relative overflow-hidden rounded-2xl bg-gradient-to-br from-slate-900 via-violet-950 to-brand-900 p-5 text-white shadow-lg shadow-violet-900/25 ring-1 ring-white/10 sm:rounded-3xl sm:p-6">
                <div class="pointer-events-none absolute inset-0 bg-[url('data:image/svg+xml,%3Csvg width=\'60\' height=\'60\' viewBox=\'0 0 60 60\' xmlns=\'http://www.w3.org/2000/svg\'%3E%3Cg fill=\'none\' fill-rule=\'evenodd\'%3E%3Cg fill=\'%23ffffff\' fill-opacity=\'0.05\'%3E%3Cpath d=\'M36 34v-4h-2v4h-4v2h4v4h2v-4h4v-2h-4zm0-30V0h-2v4h-4v2h4v4h2V6h4V4h-4zM6 34v-4H4v4H0v2h4v4h2v-4h4v-2H6zM6 4V0H4v4H0v2h4v4h2V6h4V4H6z\'/%3E%3C/g%3E%3C/g%3E%3C/svg%3E')] opacity-40"></div>
                <div class="pointer-events-none absolute -right-12 top-0 h-40 w-40 rounded-full bg-violet-500/20 blur-3xl"></div>
                <div class="relative flex flex-col gap-4 sm:flex-row sm:items-start sm:justify-between">
                    <div class="flex items-start gap-3">
                        <span class="flex h-11 w-11 shrink-0 items-center justify-center rounded-xl bg-white/15 ring-1 ring-white/20" aria-hidden="true">
                            <svg class="h-6 w-6" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor"><path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-6-3a2 2 0 11-4 0 2 2 0 014 0zm-2 4a5 5 0 00-4.546 2.916A5.986 5.986 0 0010 16a5.986 5.986 0 004.546-2.084A5 5 0 0010 11z" clip-rule="evenodd" /></svg>
                        </span>
                        <div class="min-w-0">
                            <p class="text-[11px] font-semibold uppercase tracking-wider text-violet-200/90">{{ __('profile.page_eyebrow') }}</p>
                            <h1 class="mt-1 text-xl font-bold tracking-tight text-white sm:text-2xl">{{ __('profile.page_title') }}</h1>
                            <p class="mt-2 max-w-xl text-sm leading-relaxed text-violet-100/85">{{ __('profile.page_subtitle') }}</p>
                        </div>
                    </div>
                    <a href="{{ route('dashboard') }}" class="inline-flex shrink-0 items-center gap-2 self-start rounded-xl border border-white/20 bg-white/10 px-4 py-2.5 text-sm font-semibold text-white backdrop-blur-sm transition hover:bg-white/20">
                        <svg class="h-4 w-4" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true"><path fill-rule="evenodd" d="M9.293 2.293a1 1 0 011.414 0l7 7A1 1 0 0117 11h-1v6a1 1 0 01-1 1h-2a1 1 0 01-1-1v-3a1 1 0 00-1-1H9a1 1 0 00-1 1v3a1 1 0 01-1 1H5a1 1 0 01-1-1v-6H3a1 1 0 01-.707-1.707l7-7z" clip-rule="evenodd" /></svg>
                        {{ __('profile.back_dashboard') }}
                    </a>
                </div>
            </div>

            <div class="overflow-hidden rounded-2xl border border-slate-200/90 bg-gradient-to-b from-white to-slate-50/80 shadow-sm ring-1 ring-slate-100/80">
                <div class="flex min-w-0">
                    <div class="w-1 shrink-0 bg-brand-500" aria-hidden="true"></div>
                    <div class="min-w-0 flex-1 p-5 sm:p-6">
                        <div class="max-w-xl">
                            @include('profile.partials.update-profile-information-form')
                        </div>
                    </div>
                </div>
            </div>

            <div class="overflow-hidden rounded-2xl border border-slate-200/90 bg-gradient-to-b from-white to-slate-50/80 shadow-sm ring-1 ring-slate-100/80">
                <div class="flex min-w-0">
                    <div class="w-1 shrink-0 bg-violet-500" aria-hidden="true"></div>
                    <div class="min-w-0 flex-1 p-5 sm:p-6">
                        <div class="max-w-xl">
                            @include('profile.partials.update-password-form')
                        </div>
                    </div>
                </div>
            </div>

            @if ($muthowifProfile)
                <div class="overflow-hidden rounded-2xl border border-slate-200/90 bg-gradient-to-b from-white to-slate-50/80 shadow-sm ring-1 ring-slate-100/80">
                    <div class="flex min-w-0">
                        <div class="w-1 shrink-0 bg-emerald-500" aria-hidden="true"></div>
                        <div class="min-w-0 flex-1 p-5 sm:p-6">
                            <div class="max-w-3xl">
                                @include('profile.partials.update-public-profile-form')
                            </div>
                        </div>
                    </div>
                </div>
            @endif

            <div class="overflow-hidden rounded-2xl border border-slate-200/90 bg-gradient-to-b from-white to-slate-50/80 shadow-sm ring-1 ring-slate-100/80">
                <div class="flex min-w-0">
                    <div class="w-1 shrink-0 bg-rose-500" aria-hidden="true"></div>
                    <div class="min-w-0 flex-1 p-5 sm:p-6">
                        <div class="max-w-xl">
                            @include('profile.partials.delete-user-form')
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
