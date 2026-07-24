@php
    use App\Enums\BookingStatus;

    $b = $page->booking;
    $st = $b->status;
@endphp

<x-ui.card class="overflow-hidden p-0">
    <div class="border-b border-slate-100 px-5 py-4 sm:px-6">
        <h2 class="flex items-center gap-2 text-base font-bold text-slate-900">
            <span class="flex h-8 w-8 items-center justify-center rounded-lg bg-brand-50 text-brand-700">
                <svg class="h-4 w-4" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
                    <path fill-rule="evenodd" d="M4.75 2a.75.75 0 01.75.75V4h7V2.75a.75.75 0 011.5 0V4h.25A2.75 2.75 0 0118 6.75v8.5A2.75 2.75 0 0115.25 18H4.75A2.75 2.75 0 012 15.25v-8.5A2.75 2.75 0 014.75 4H5V2.75A.75.75 0 014.75 2zm-1 5.5c-.69 0-1.25.56-1.25 1.25v6.5c0 .69.56 1.25 1.25 1.25h10.5c.69 0 1.25-.56 1.25-1.25v-6.5c0-.69-.56-1.25-1.25-1.25H4.75z" clip-rule="evenodd" />
                </svg>
            </span>
            {{ __('bookings.show.detail_kicker') }}
        </h2>
    </div>

    <div class="ui-card-pad">
        <div class="flex flex-col gap-5 lg:flex-row lg:items-start lg:gap-6">
            {{-- Kiri: profil + kode + status --}}
            <div class="flex min-w-0 flex-1 gap-4">
                <img
                    src="{{ $b->muthowifProfile->photoUrl() }}"
                    alt="{{ __('bookings.index.photo_alt', ['name' => $page->muthowifName]) }}"
                    class="h-16 w-16 shrink-0 rounded-full object-cover ring-2 ring-white shadow-md sm:h-[4.5rem] sm:w-[4.5rem]"
                    loading="lazy"
                >
                <div class="min-w-0 flex-1">
                    <p class="text-lg font-bold text-slate-900">{{ $page->muthowifName }}</p>
                    <a
                        href="{{ route('layanan.show', $b->muthowifProfile) }}"
                        class="mt-0.5 inline-flex items-center gap-1 text-sm font-semibold text-brand-700 hover:text-brand-800"
                    >
                        {{ __('marketplace.card.view_profile') }}
                        <svg class="h-3.5 w-3.5" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
                            <path fill-rule="evenodd" d="M3 10a.75.75 0 01.75-.75h10.638L10.23 5.29a.75.75 0 111.04-1.08l5.5 5.25a.75.75 0 010 1.08l-5.5 5.25a.75.75 0 11-1.04-1.08l4.158-3.96H3.75A.75.75 0 013 10z" clip-rule="evenodd" />
                        </svg>
                    </a>

                    @if (filled($b->booking_code))
                        <div class="mt-3 flex flex-wrap items-center gap-2">
                            <p class="text-[0.65rem] font-semibold uppercase tracking-wide text-slate-500">
                                {{ __('bookings.show.booking_code') }}
                                <span class="ml-1 font-mono text-sm normal-case tracking-tight text-slate-900" id="booking-code-value">{{ $b->booking_code }}</span>
                            </p>
                            <button
                                type="button"
                                class="inline-flex h-7 w-7 items-center justify-center rounded-md text-slate-500 transition hover:bg-slate-100 hover:text-brand-700"
                                title="{{ __('bookings.show.copy_code') }}"
                                onclick="navigator.clipboard?.writeText(document.getElementById('booking-code-value')?.textContent?.trim() || '')"
                            >
                                <svg class="h-4 w-4" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.75" stroke="currentColor" aria-hidden="true">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M16.5 8.25V6a2.25 2.25 0 0 0-2.25-2.25H6A2.25 2.25 0 0 0 3.75 6v8.25A2.25 2.25 0 0 0 6 16.5h2.25m8.25-8.25H18A2.25 2.25 0 0 1 20.25 10.5v8.25A2.25 2.25 0 0 1 18 21h-8.25A2.25 2.25 0 0 1 7.5 18.75V16.5m8.25-8.25H12a2.25 2.25 0 0 0-2.25 2.25v8.25" />
                                </svg>
                            </button>
                        </div>
                    @endif

                    <div class="mt-2">
                        <span class="inline-flex items-center rounded-full px-2.5 py-0.5 text-xs font-semibold ring-1 {{ $page->statusBadgeClass }}">
                            {{ $st->label() }}
                        </span>
                    </div>
                </div>
            </div>

            {{-- Kanan: periode & layanan (seperti mockup) --}}
            <div class="grid w-full shrink-0 grid-cols-1 gap-3 sm:grid-cols-2 lg:w-[min(100%,22rem)] lg:grid-cols-1 xl:grid-cols-2">
                <div class="rounded-xl border border-slate-100 bg-slate-50/80 p-3.5">
                    <p class="text-[0.65rem] font-semibold uppercase tracking-wide text-slate-500">{{ __('bookings.show.period') }}</p>
                    @if ($b->isSupport() && $b->starts_at)
                        <p class="mt-1 text-sm font-semibold tabular-nums text-slate-900">
                            {{ $b->starts_at->timezone(config('app.timezone'))->translatedFormat('d M Y H:i') }}
                        </p>
                        <p class="mt-0.5 text-xs text-slate-600">{{ __('layanan_pendukung.starts_at_hint') }}</p>
                    @else
                        <p class="mt-1 text-sm font-semibold tabular-nums text-slate-900">
                            {{ $page->formatDate($b->starts_on) }}
                            <span class="font-normal text-slate-400">–</span>
                            {{ $page->formatDate($b->ends_on) }}
                        </p>
                        <p class="mt-0.5 text-xs text-slate-600">
                            ({{ __('bookings.show.period_duration_line', ['days' => $page->nights, 'nights' => $page->sleepNights]) }})
                        </p>
                    @endif
                </div>
                <div class="rounded-xl border border-slate-100 bg-slate-50/80 p-3.5">
                    <p class="text-[0.65rem] font-semibold uppercase tracking-wide text-slate-500">{{ __('bookings.show.service') }}</p>
                    <p class="mt-1 text-sm font-semibold text-slate-900">{{ $b->service_type?->label() ?? '—' }}</p>
                    @if ($b->isSupport() && filled($b->package_name_snapshot))
                        <p class="mt-0.5 text-xs text-slate-600">{{ $b->package_name_snapshot }}</p>
                    @endif
                    <p class="mt-0.5 text-xs text-slate-600">{{ __('bookings.index.pilgrims_count', ['count' => $b->pilgrim_count, 'pilgrims_word' => __('common.pilgrims')]) }}</p>
                </div>
            </div>
        </div>
    </div>
</x-ui.card>
