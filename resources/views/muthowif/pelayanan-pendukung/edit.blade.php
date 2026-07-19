<x-app-layout>
    <x-ui.app-page compact>
        <div class="pointer-events-none absolute inset-0 bg-[radial-gradient(ellipse_80%_40%_at_50%_-10%,rgba(15,42,37,0.07),transparent)]"></div>
        <x-page-container class="relative ui-stack-compact">
            <div class="relative overflow-hidden rounded-2xl bg-gradient-to-br from-slate-900 via-emerald-950 to-baytgo p-5 text-white shadow-lg shadow-baytgo/25 ring-1 ring-white/10 sm:rounded-3xl sm:p-6">
                <div class="pointer-events-none absolute -right-12 top-0 h-40 w-40 rounded-full bg-emerald-400/20 blur-3xl"></div>
                <div class="relative flex flex-col gap-4 sm:flex-row sm:items-start sm:justify-between">
                    <div class="flex items-start gap-3">
                        <span class="flex h-11 w-11 shrink-0 items-center justify-center rounded-xl bg-white/15 ring-1 ring-white/20" aria-hidden="true">
                            <svg class="h-6 w-6 text-emerald-200" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor"><path d="M5.433 13.917l1.262-3.155A4 4 0 017.58 9.42l6.92-6.918a2.121 2.121 0 013 3l-6.92 6.918c-.383.383-.84.685-1.343.886l-3.154 1.262a.5.5 0 01-.65-.65z" /><path d="M3.5 5.75c0-.69.56-1.25 1.25-1.25H10A.75.75 0 0010 3H4.75A2.75 2.75 0 002 5.75v9.5A2.75 2.75 0 004.75 18h9.5A2.75 2.75 0 0017 15.25V10a.75.75 0 00-1.5 0v5.25c0 .69-.56 1.25-1.25 1.25h-9.5c-.69 0-1.25-.56-1.25-1.25v-9.5z" /></svg>
                        </span>
                        <div class="min-w-0">
                            <p class="text-[11px] font-semibold uppercase tracking-wider text-emerald-100/90">{{ __('layanan_pendukung.page_title') }}</p>
                            <h1 class="mt-1 text-xl font-bold tracking-tight text-white sm:text-2xl">{{ __('layanan_pendukung.edit_title') }}</h1>
                            <p class="mt-2 max-w-xl truncate text-sm leading-relaxed text-emerald-50/90">{{ $package->name }}</p>
                        </div>
                    </div>
                    <a href="{{ route('muthowif.pelayanan-pendukung.index') }}" class="inline-flex shrink-0 items-center gap-2 self-start rounded-xl border border-white/20 bg-white/10 px-4 py-2.5 text-sm font-semibold text-white backdrop-blur-sm transition hover:bg-white/20">
                        ← {{ __('layanan_pendukung.back_to_list') }}
                    </a>
                </div>
            </div>

            <div class="overflow-hidden rounded-2xl border border-slate-200/90 bg-white shadow-sm ring-1 ring-slate-100/80 sm:rounded-3xl">
                <div class="flex min-w-0">
                    <div class="w-1.5 shrink-0 bg-gradient-to-b from-baytgo to-emerald-400" aria-hidden="true"></div>
                    <div class="min-w-0 flex-1 p-5 sm:p-6">
                        <form method="POST" action="{{ route('muthowif.pelayanan-pendukung.update', $package) }}" class="space-y-6">
                            @csrf
                            @method('PUT')
                            @include('muthowif.pelayanan-pendukung.partials.form', [
                                'package' => $package,
                                'categories' => $categories,
                                'prefillCategory' => $package->category,
                            ])
                            <div class="flex flex-wrap gap-3 border-t border-slate-100 pt-5">
                                <x-submit-button class="rounded-xl bg-baytgo px-5 py-2.5 text-sm font-semibold text-white shadow-sm shadow-baytgo/20 hover:bg-baytgo-800">
                                    {{ __('layanan_pendukung.save_package') }}
                                </x-submit-button>
                                <a href="{{ route('muthowif.pelayanan-pendukung.index') }}" class="inline-flex items-center rounded-xl border border-slate-200 px-4 py-2.5 text-sm font-semibold text-slate-700 transition hover:bg-slate-50">
                                    {{ __('layanan_pendukung.cancel') }}
                                </a>
                            </div>
                        </form>
                    </div>
                </div>
            </div>

            <div class="overflow-hidden rounded-2xl border border-red-200/80 bg-red-50/40 shadow-sm ring-1 ring-red-100/60 sm:rounded-3xl">
                <div class="p-5 sm:p-6">
                    <h2 class="text-sm font-bold text-red-900">{{ __('layanan_pendukung.danger_zone') }}</h2>
                    <p class="mt-1 text-xs text-red-800/80">{{ __('layanan_pendukung.danger_zone_lead') }}</p>
                    <form method="POST" action="{{ route('muthowif.pelayanan-pendukung.destroy', $package) }}" class="mt-4" onsubmit="return confirm(@json(__('layanan_pendukung.delete_confirm')));">
                        @csrf
                        @method('DELETE')
                        <button type="submit" class="inline-flex items-center gap-2 rounded-xl border border-red-300 bg-white px-4 py-2.5 text-sm font-semibold text-red-700 transition hover:bg-red-50">
                            <svg class="h-4 w-4" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true"><path fill-rule="evenodd" d="M8.75 1A2.75 2.75 0 006 3.75v.443c-.795.077-1.584.176-2.365.298a.75.75 0 10.23 1.482l.226-.038 1.027 12.317A2.75 2.75 0 007.86 20h4.28a2.75 2.75 0 002.742-2.748l1.027-12.317.226.038a.75.75 0 00.23-1.482A41.03 41.03 0 0014 4.193V3.75A2.75 2.75 0 0011.25 1h-2.5zM10 4c.784 0 1.565.023 2.34.068v.343a41.56 41.56 0 00-4.68 0V4.068A41.4 41.4 0 0110 4zM8.58 7.72a.75.75 0 00-1.5.06l.6 9a.75.75 0 101.5-.06l-.6-9zm5.34.06a.75.75 0 10-1.5-.06l-.6 9a.75.75 0 001.5.06l.6-9z" clip-rule="evenodd" /></svg>
                            {{ __('layanan_pendukung.delete_package') }}
                        </button>
                    </form>
                </div>
            </div>
        </x-page-container>
    </x-ui.app-page>
</x-app-layout>
