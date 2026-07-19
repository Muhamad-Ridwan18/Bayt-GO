<x-app-layout>
    <x-ui.app-page compact>
        <div class="pointer-events-none absolute inset-0 bg-[radial-gradient(ellipse_80%_40%_at_50%_-10%,rgba(15,42,37,0.07),transparent)]"></div>
        <x-page-container class="relative ui-stack-compact">
            <div class="relative overflow-hidden rounded-2xl bg-gradient-to-br from-slate-900 via-emerald-950 to-baytgo p-5 text-white shadow-lg shadow-baytgo/25 ring-1 ring-white/10 sm:rounded-3xl sm:p-6">
                <div class="pointer-events-none absolute -right-12 top-0 h-40 w-40 rounded-full bg-emerald-400/20 blur-3xl"></div>
                <div class="relative flex flex-col gap-4 sm:flex-row sm:items-start sm:justify-between">
                    <div class="flex items-start gap-3">
                        <span class="flex h-11 w-11 shrink-0 items-center justify-center rounded-xl bg-white/15 ring-1 ring-white/20" aria-hidden="true">
                            <svg class="h-6 w-6 text-emerald-200" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor"><path d="M10.75 4.75a.75.75 0 00-1.5 0v4.5h-4.5a.75.75 0 000 1.5h4.5v4.5a.75.75 0 001.5 0v-4.5h4.5a.75.75 0 000-1.5h-4.5v-4.5z" /></svg>
                        </span>
                        <div class="min-w-0">
                            <p class="text-[11px] font-semibold uppercase tracking-wider text-emerald-100/90">{{ __('layanan_pendukung.page_title') }}</p>
                            <h1 class="mt-1 text-xl font-bold tracking-tight text-white sm:text-2xl">{{ __('layanan_pendukung.create_title') }}</h1>
                            <p class="mt-2 max-w-xl text-sm leading-relaxed text-emerald-50/90">{{ __('layanan_pendukung.manage_lead') }}</p>
                        </div>
                    </div>
                    <a href="{{ ($prefillCategory ?? null) ? route('muthowif.pelayanan-pendukung.index', ['category' => $prefillCategory->value]) : route('muthowif.pelayanan-pendukung.index') }}" class="inline-flex shrink-0 items-center gap-2 self-start rounded-xl border border-white/20 bg-white/10 px-4 py-2.5 text-sm font-semibold text-white backdrop-blur-sm transition hover:bg-white/20">
                        ← {{ __('layanan_pendukung.back_to_list') }}
                    </a>
                </div>
            </div>

            <div class="overflow-hidden rounded-2xl border border-slate-200/90 bg-white shadow-sm ring-1 ring-slate-100/80 sm:rounded-3xl">
                <div class="flex min-w-0">
                    <div class="w-1.5 shrink-0 bg-gradient-to-b from-baytgo to-emerald-400" aria-hidden="true"></div>
                    <div class="min-w-0 flex-1 p-5 sm:p-6">
                        <form method="POST" action="{{ route('muthowif.pelayanan-pendukung.store') }}" class="space-y-6">
                            @csrf
                            @include('muthowif.pelayanan-pendukung.partials.form', [
                                'package' => null,
                                'categories' => $categories,
                                'prefillCategory' => $prefillCategory ?? null,
                            ])
                            <div class="flex flex-wrap gap-3 border-t border-slate-100 pt-5">
                                <x-submit-button class="rounded-xl bg-baytgo px-5 py-2.5 text-sm font-semibold text-white shadow-sm shadow-baytgo/20 hover:bg-baytgo-800">
                                    {{ __('layanan_pendukung.save_package') }}
                                </x-submit-button>
                                <a href="{{ ($prefillCategory ?? null) ? route('muthowif.pelayanan-pendukung.index', ['category' => $prefillCategory->value]) : route('muthowif.pelayanan-pendukung.index') }}" class="inline-flex items-center rounded-xl border border-slate-200 px-4 py-2.5 text-sm font-semibold text-slate-700 transition hover:bg-slate-50">
                                    {{ __('layanan_pendukung.cancel') }}
                                </a>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </x-page-container>
    </x-ui.app-page>
</x-app-layout>
