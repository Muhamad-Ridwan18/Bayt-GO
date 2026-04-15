<x-app-layout>
    <div class="relative min-h-[calc(100vh-4rem)] overflow-hidden bg-gradient-to-b from-slate-100 via-slate-50 to-white py-6 sm:py-8">
        <div class="pointer-events-none absolute inset-0 bg-[radial-gradient(ellipse_80%_40%_at_50%_-10%,rgba(120,53,15,0.06),transparent)]"></div>
        <div class="relative mx-auto max-w-3xl space-y-6 px-4 sm:px-6 lg:px-8">
            {{-- Hero --}}
            <div class="relative overflow-hidden rounded-2xl bg-gradient-to-br from-slate-900 via-amber-950 to-orange-950 p-5 text-white shadow-lg shadow-amber-950/30 ring-1 ring-white/10 sm:rounded-3xl sm:p-6">
                <div class="pointer-events-none absolute inset-0 bg-[url('data:image/svg+xml,%3Csvg width=\'60\' height=\'60\' viewBox=\'0 0 60 60\' xmlns=\'http://www.w3.org/2000/svg\'%3E%3Cg fill=\'none\' fill-rule=\'evenodd\'%3E%3Cg fill=\'%23ffffff\' fill-opacity=\'0.05\'%3E%3Cpath d=\'M36 34v-4h-2v4h-4v2h4v4h2v-4h4v-2h-4zm0-30V0h-2v4h-4v2h4v4h2V6h4V4h-4zM6 34v-4H4v4H0v2h4v4h2v-4h4v-2H6zM6 4V0H4v4H0v2h4v4h2V6h4V4H6z\'/%3E%3C/g%3E%3C/g%3E%3C/svg%3E')] opacity-40"></div>
                <div class="pointer-events-none absolute -right-12 top-0 h-40 w-40 rounded-full bg-amber-500/15 blur-3xl"></div>
                <div class="relative flex flex-col gap-4 sm:flex-row sm:items-start sm:justify-between">
                    <div class="flex items-start gap-3">
                        <span class="flex h-11 w-11 shrink-0 items-center justify-center rounded-xl bg-white/15 ring-1 ring-white/20" aria-hidden="true">
                            <svg class="h-6 w-6" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor"><path fill-rule="evenodd" d="M5.75 2a.75.75 0 01.75.75V4h7V2.75a.75.75 0 011.5 0V4h.25A2.75 2.75 0 0118 6.75v8.5A2.75 2.75 0 0115.25 18H4.75A2.75 2.75 0 012 15.25v-8.5A2.75 2.75 0 014.75 4H5V2.75A.75.75 0 015.75 2zm-1 5.5c-.69 0-1.25.56-1.25 1.25v6.5c0 .69.56 1.25 1.25 1.25h10.5c.69 0 1.25-.56 1.25-1.25v-6.5c0-.69-.56-1.25-1.25-1.25H4.75z" clip-rule="evenodd" /></svg>
                        </span>
                        <div class="min-w-0">
                            <p class="text-[11px] font-semibold uppercase tracking-wider text-amber-100/90">{{ __('dashboard_muthowif.nav_time_off') }}</p>
                            <h1 class="mt-1 text-xl font-bold tracking-tight text-white sm:text-2xl">{{ __('muthowif.jadwal.page_title') }}</h1>
                            <p class="mt-2 max-w-xl text-sm leading-relaxed text-amber-50/90">{{ __('muthowif.jadwal.page_subtitle') }}</p>
                        </div>
                    </div>
                    <a href="{{ route('dashboard') }}" class="inline-flex shrink-0 items-center gap-2 self-start rounded-xl border border-white/20 bg-white/10 px-4 py-2.5 text-sm font-semibold text-white backdrop-blur-sm transition hover:bg-white/20">
                        <svg class="h-4 w-4" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true"><path fill-rule="evenodd" d="M9.293 2.293a1 1 0 011.414 0l7 7A1 1 0 0117 11h-1v6a1 1 0 01-1 1h-2a1 1 0 01-1-1v-3a1 1 0 00-1-1H9a1 1 0 00-1 1v3a1 1 0 01-1 1H5a1 1 0 01-1-1v-6H3a1 1 0 01-.707-1.707l7-7z" clip-rule="evenodd" /></svg>
                        {{ __('muthowif.jadwal.back_dashboard') }}
                    </a>
                </div>
            </div>

            {{-- Form --}}
            <div class="overflow-hidden rounded-2xl border border-slate-200/90 bg-gradient-to-b from-white to-slate-50/80 shadow-sm ring-1 ring-slate-100/80">
                <div class="flex min-w-0">
                    <div class="w-1 shrink-0 bg-amber-500" aria-hidden="true"></div>
                    <div class="min-w-0 flex-1 p-5 sm:p-6">
                        <h2 class="font-semibold text-slate-900">{{ __('muthowif.jadwal.add_title') }}</h2>
                        <form method="POST" action="{{ route('muthowif.jadwal.store') }}" class="mt-4 grid grid-cols-1 gap-4 sm:grid-cols-2">
                            @csrf
                            <div>
                                <x-input-label for="blocked_on" :value="__('muthowif.jadwal.date_label')" />
                                <x-text-input id="blocked_on" name="blocked_on" type="date" class="mt-1 block w-full" required
                                              :value="old('blocked_on')" min="{{ now()->toDateString() }}" />
                                <x-input-error class="mt-2" :messages="$errors->get('blocked_on')" />
                            </div>
                            <div>
                                <x-input-label for="note" :value="__('muthowif.jadwal.note_label')" />
                                <x-text-input id="note" name="note" type="text" class="mt-1 block w-full" :value="old('note')" :placeholder="__('muthowif.jadwal.note_placeholder')" />
                                <x-input-error class="mt-2" :messages="$errors->get('note')" />
                            </div>
                            <div class="sm:col-span-2">
                                <x-primary-button type="submit">{{ __('muthowif.jadwal.submit') }}</x-primary-button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>

            {{-- List --}}
            <div class="overflow-hidden rounded-2xl border border-slate-200/90 bg-gradient-to-b from-white to-slate-50/80 shadow-sm ring-1 ring-slate-100/80">
                <div class="border-b border-slate-100/90 bg-slate-50/90 px-5 py-4 sm:px-6">
                    <h2 class="font-semibold text-slate-900">{{ __('muthowif.jadwal.list_title') }}</h2>
                </div>
                @if ($blockedDates->isEmpty())
                    <div class="px-5 py-12 text-center sm:px-6 sm:py-14">
                        <span class="mx-auto flex h-14 w-14 items-center justify-center rounded-2xl bg-amber-50 text-amber-700 ring-1 ring-amber-200/80" aria-hidden="true">
                            <svg class="h-7 w-7" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M6.75 3v2.25M17.25 3v2.25M3 18.75V7.5a2.25 2.25 0 012.25-2.25h13.5A2.25 2.25 0 0121 7.5v11.25m-18 0A2.25 2.25 0 005.25 21h13.5a2.25 2.25 0 002.25-2.25m-18 0v-7.5A2.25 2.25 0 015.25 9h13.5a2.25 2.25 0 012.25 2.25v7.5" /></svg>
                        </span>
                        <p class="mt-4 text-base font-semibold text-slate-900">{{ __('muthowif.jadwal.empty') }}</p>
                        <p class="mx-auto mt-2 max-w-md text-sm text-slate-600">{{ __('muthowif.jadwal.empty_hint') }}</p>
                    </div>
                @else
                    <ul class="divide-y divide-slate-100">
                        @foreach ($blockedDates as $bd)
                            <li class="flex min-w-0">
                                <div class="w-1 shrink-0 bg-amber-400/90" aria-hidden="true"></div>
                                <div class="flex min-w-0 flex-1 flex-col gap-3 p-4 sm:flex-row sm:items-center sm:justify-between sm:p-5">
                                    <div class="min-w-0">
                                        <p class="flex items-center gap-2 font-semibold text-slate-900">
                                            <svg class="h-4 w-4 shrink-0 text-amber-600" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true"><path fill-rule="evenodd" d="M5.75 2a.75.75 0 01.75.75V4h7V2.75a.75.75 0 011.5 0V4h.25A2.75 2.75 0 0118 6.75v8.5A2.75 2.75 0 0115.25 18H4.75A2.75 2.75 0 012 15.25v-8.5A2.75 2.75 0 014.75 4H5V2.75A.75.75 0 015.75 2zm-1 5.5c-.69 0-1.25.56-1.25 1.25v6.5c0 .69.56 1.25 1.25 1.25h10.5c.69 0 1.25-.56 1.25-1.25v-6.5c0-.69-.56-1.25-1.25-1.25H4.75z" clip-rule="evenodd" /></svg>
                                            {{ $bd->blocked_on->format('d/m/Y') }}
                                        </p>
                                        @if (filled($bd->note))
                                            <p class="mt-1 text-sm text-slate-600">{{ $bd->note }}</p>
                                        @endif
                                    </div>
                                    <form action="{{ route('muthowif.jadwal.destroy', $bd) }}" method="post" class="shrink-0" onsubmit="return confirm(@json(__('muthowif.jadwal.delete_confirm')));">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="inline-flex items-center justify-center rounded-lg border border-red-200 bg-red-50 px-3 py-1.5 text-sm font-semibold text-red-800 transition hover:bg-red-100/80">
                                            {{ __('muthowif.jadwal.delete') }}
                                        </button>
                                    </form>
                                </div>
                            </li>
                        @endforeach
                    </ul>
                    <div class="flex justify-center border-t border-slate-100/90 bg-white/50 px-3 py-3 sm:justify-end">
                        {{ $blockedDates->links() }}
                    </div>
                @endif
            </div>
        </div>
    </div>
</x-app-layout>
