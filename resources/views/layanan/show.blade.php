<x-marketplace-layout :title="$page->seoTitle" :meta-description="$page->seoDesc" :schema="$page->muthowifSchema" wide>
    <div class="ui-marketplace-page-sticky">

        <div class="ui-toolbar relative flex flex-wrap items-center justify-between gap-3">
            <nav class="flex min-w-0 flex-wrap items-center gap-x-2 gap-y-1" aria-label="Breadcrumb">
                <a href="{{ route('layanan.index', $page->indexQuery) }}" class="inline-flex items-center gap-1 font-semibold text-brand-700 hover:text-brand-800">
                    {{ __('layanan.breadcrumb_find') }}
                </a>
                <span class="text-slate-300" aria-hidden="true">/</span>
                <span class="min-w-0 truncate font-medium text-slate-800">{{ $page->muthowifName }}</span>
            </nav>
            @if ($page->searchRangeLabel)
                <a
                    href="{{ route('layanan.index', $page->indexQuery) }}"
                    class="inline-flex max-w-full items-center gap-2 rounded-xl border border-slate-200 bg-slate-50 px-3 py-2 text-xs font-semibold text-slate-800 transition hover:border-brand-300 hover:bg-brand-50"
                    title="{{ __('marketplace.show.change_dates') }}"
                >
                    <svg class="h-4 w-4 shrink-0 text-brand-600" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true"><path fill-rule="evenodd" d="M5.75 2a.75.75 0 01.75.75V4h7V2.75a.75.75 0 011.5 0V4h.25A2.75 2.75 0 0118 6.75v8.5A2.75 2.75 0 0115.25 18H4.75A2.75 2.75 0 012 15.25v-8.5A2.75 2.75 0 014.75 4H5V2.75A.75.75 0 015.75 2zm-1 5.5c-.69 0-1.25.56-1.25 1.25v6.5c0 .69.56 1.25 1.25 1.25h10.5c.69 0 1.25-.56 1.25-1.25v-6.5c0-.69-.56-1.25-1.25-1.25H4.75z" clip-rule="evenodd" /></svg>
                    <span class="truncate tabular-nums">{{ $page->searchRangeLabel }}</span>
                </a>
            @endif
        </div>

        @include('layanan.partials.profile-show-hero', ['page' => $page])
        @include('layanan.partials.profile-booking-cta', ['page' => $page])
        @include('layanan.partials.profile-show-packages', ['page' => $page])
        @include('layanan.partials.profile-show-reviews', ['page' => $page])

        <details class="group rounded-2xl border border-slate-200/90 bg-white shadow-sm ring-1 ring-slate-100/80 open:ring-brand-200/60">
            <summary class="cursor-pointer list-none px-5 py-4 text-sm font-semibold text-slate-900 marker:content-none [&::-webkit-details-marker]:hidden">
                <span class="flex items-center justify-between gap-3">
                    <span>{{ __('marketplace.show.more_about_heading') }}</span>
                    <svg class="h-5 w-5 shrink-0 text-slate-400 transition group-open:rotate-180" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true"><path fill-rule="evenodd" d="M5.23 7.21a.75.75 0 011.06.02L10 11.168l3.71-3.94a.75.75 0 111.08 1.04l-4.25 4.5a.75.75 0 01-1.08 0l-4.25-4.5a.75.75 0 01.02-1.06z" clip-rule="evenodd" /></svg>
                </span>
            </summary>
            <div class="ui-stack-compact border-t border-slate-100 px-5 py-5">
                @include('layanan.partials.profile-show-addons', ['page' => $page])
                @include('layanan.partials.profile-show-bottom', [
                    'profile' => $page->profile,
                    'group' => $page->group,
                    'private' => $page->private,
                ])
                @include('layanan.partials.profile-show-trust-bar')
            </div>
        </details>

        @if ($page->blockedCount > 0)
            <details class="rounded-2xl border border-amber-200/70 bg-white shadow-sm ring-1 ring-amber-100/60">
                <summary class="cursor-pointer px-5 py-4 text-sm font-semibold text-slate-900">
                    {{ __('marketplace.show.summary_blocked', ['count' => $page->blockedCount]) }}
                </summary>
                <div class="border-t border-amber-100/80 px-5 py-4">
                    <p class="mb-3 text-xs text-slate-600">{{ __('marketplace.show.blocked_sub') }}</p>
                    <ul class="grid gap-2 text-xs sm:grid-cols-2">
                        @foreach ($page->blockedDateRows as $row)
                            <li class="rounded-lg border border-amber-100 bg-amber-50/50 px-3 py-2">
                                <span class="font-semibold tabular-nums text-slate-900">{{ $row['date'] }}</span>
                                @if (filled($row['note']))
                                    <span class="mt-0.5 block text-slate-600">{{ $row['note'] }}</span>
                                @endif
                            </li>
                        @endforeach
                    </ul>
                </div>
            </details>
        @endif

        @include('layanan.partials.profile-show-sticky-cta', ['page' => $page])
    </div>
</x-marketplace-layout>
