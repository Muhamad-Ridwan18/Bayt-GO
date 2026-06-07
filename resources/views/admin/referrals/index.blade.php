@php
    $filterParams = function (string $f) use ($q): array {
        $p = ['filter' => $f];
        if ($q !== '') {
            $p['q'] = $q;
        }

        return $p;
    };
@endphp

<x-app-layout>
    <x-ui.app-page>
        <div class="pointer-events-none absolute inset-0 bg-[radial-gradient(ellipse_80%_40%_at_50%_-10%,rgba(91,33,182,0.07),transparent)]"></div>
        <x-page-container class="ui-stack relative">
            <x-ui.page-hero :badge="__('admin.referrals.badge')" :title="__('admin.referrals.title')" :subtitle="__('admin.referrals.subtitle')">
                <x-slot:icon>
                    <svg class="h-6 w-6" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M18 18.72a9.09 9.09 0 003.741-.479 3 3 0 00-4.682-2.72m.94 3.198.001.031c0 .225-.012.447-.037.666A11.944 11.944 0 0112 21c-2.17 0-4.207-.576-5.963-1.584A6.062 6.062 0 016 18.719m12 0a5.971 5.971 0 00-.941-3.197m0 0A5.995 5.995 0 0012 12.75a5.995 5.995 0 00-5.058 2.772m0 0a3 3 0 00-4.681 2.72 8.986 8.986 0 003.74.477m.94-3.197a5.971 5.971 0 00-.94 3.197M15 6.75a3 3 0 11-6 0 3 3 0 016 0zm6 3a2.25 2.25 0 11-4.5 0 2.25 2.25 0 014.5 0zm-13.5 0a2.25 2.25 0 11-4.5 0 2.25 2.25 0 014.5 0z" /></svg>
                </x-slot:icon>
                <x-slot:actions>
                    <a href="{{ route('dashboard') }}" class="inline-flex shrink-0 items-center gap-2 rounded-2xl border border-white/20 bg-white/10 px-4 py-2.5 text-sm font-semibold text-white backdrop-blur-sm transition hover:bg-white/20">
                        <svg class="h-4 w-4" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true"><path fill-rule="evenodd" d="M9.293 2.293a1 1 0 011.414 0l7 7A1 1 0 0117 11h-1v6a1 1 0 01-1 1h-2a1 1 0 01-1-1v-3a1 1 0 00-1-1H9a1 1 0 00-1 1v3a1 1 0 01-1 1H5a1 1 0 01-1-1v-6H3a1 1 0 01-.707-1.707l7-7z" clip-rule="evenodd" /></svg>
                        {{ __('admin.referrals.back_dashboard') }}
                    </a>
                </x-slot:actions>
                <x-slot:stats>
                    <div class="grid grid-cols-1 gap-3 sm:grid-cols-3">
                        <div class="rounded-2xl border border-white/10 bg-white/[0.07] p-4 backdrop-blur-sm">
                            <p class="text-[10px] font-semibold uppercase tracking-wider text-white/55">{{ __('admin.referrals.stat_referrers') }}</p>
                            <p class="mt-2 text-2xl font-bold tabular-nums text-white">{{ $stats['referrers'] }}</p>
                        </div>
                        <div class="rounded-2xl border border-white/10 bg-white/[0.07] p-4 backdrop-blur-sm">
                            <p class="text-[10px] font-semibold uppercase tracking-wider text-white/55">{{ __('admin.referrals.stat_referred') }}</p>
                            <p class="mt-2 text-2xl font-bold tabular-nums text-white">{{ $stats['referred_total'] }}</p>
                        </div>
                        <div class="rounded-2xl border border-white/10 bg-white/[0.07] p-4 backdrop-blur-sm">
                            <p class="text-[10px] font-semibold uppercase tracking-wider text-white/55">{{ __('admin.referrals.stat_rewards') }}</p>
                            <p class="mt-2 text-2xl font-bold tabular-nums text-white">Rp {{ $fmt($stats['rewards_total']) }}</p>
                        </div>
                    </div>
                </x-slot:stats>
            </x-ui.page-hero>

            <x-ui.data-table :empty="$profiles->isEmpty() ? __('admin.referrals.empty') : null">
                <x-slot:toolbar>
                    <div class="border-b border-slate-100 px-5 py-3">
                        <div class="flex w-max gap-2 rounded-2xl border border-slate-200 bg-slate-50 p-1">
                            <a href="{{ route('admin.referrals.index', $filterParams('has_referrals')) }}"
                                class="inline-flex items-center rounded-xl px-4 py-2 text-sm font-medium transition {{ $filter === 'has_referrals' ? 'bg-violet-600 text-white shadow-sm' : 'text-slate-600 hover:bg-white hover:text-slate-900' }}">
                                {{ __('admin.referrals.filter_has_referrals') }}
                            </a>
                            <a href="{{ route('admin.referrals.index', $filterParams('all')) }}"
                                class="inline-flex items-center rounded-xl px-4 py-2 text-sm font-medium transition {{ $filter === 'all' ? 'bg-violet-600 text-white shadow-sm' : 'text-slate-600 hover:bg-white hover:text-slate-900' }}">
                                {{ __('admin.referrals.filter_all') }}
                            </a>
                        </div>
                    </div>
                    <form method="get" action="{{ route('admin.referrals.index') }}" class="px-5 py-4">
                        <input type="hidden" name="filter" value="{{ $filter }}" />
                        <div class="flex flex-col gap-3 sm:flex-row sm:items-end">
                            <div class="min-w-0 flex-1">
                                <label for="referral-q" class="block text-xs font-semibold uppercase tracking-wide text-slate-500">{{ __('admin.referrals.search_label') }}</label>
                                <input id="referral-q" type="search" name="q" value="{{ $q }}" placeholder="{{ __('admin.referrals.search_placeholder') }}" class="mt-1.5 w-full rounded-xl border-slate-200 text-sm shadow-sm focus:border-brand-500 focus:ring-brand-500" />
                            </div>
                            <div class="flex gap-2">
                                <x-submit-button class="flex-1 rounded-xl bg-brand-600 px-4 py-2.5 text-sm font-semibold text-white shadow-sm transition hover:bg-brand-700 sm:flex-none">
                                    {{ __('admin.referrals.apply') }}
                                </x-submit-button>
                                <a href="{{ route('admin.referrals.index', ['filter' => $filter]) }}" class="inline-flex items-center justify-center rounded-xl border border-slate-200 bg-white px-4 py-2.5 text-sm font-semibold text-slate-700 shadow-sm transition hover:bg-slate-50">
                                    {{ __('admin.referrals.reset') }}
                                </a>
                            </div>
                        </div>
                    </form>
                </x-slot:toolbar>

                @if (! $profiles->isEmpty())
                <table class="min-w-full divide-y divide-slate-100 text-sm">
                    <thead class="bg-slate-50/80">
                        <tr>
                            <th scope="col" class="px-5 py-3 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">{{ __('admin.referrals.col_muthowif') }}</th>
                            <th scope="col" class="px-5 py-3 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">{{ __('admin.referrals.col_code') }}</th>
                            <th scope="col" class="px-5 py-3 text-center text-xs font-semibold uppercase tracking-wide text-slate-500">{{ __('admin.referrals.col_count') }}</th>
                            <th scope="col" class="px-5 py-3 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">{{ __('admin.referrals.col_status') }}</th>
                            <th scope="col" class="px-5 py-3 text-right text-xs font-semibold uppercase tracking-wide text-slate-500">{{ __('admin.referrals.col_action') }}</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100 bg-white">
                        @foreach ($profiles as $row)
                            <tr class="transition hover:bg-slate-50/60">
                                <td class="px-5 py-4 align-top">
                                    <p class="font-semibold text-slate-900">{{ $row->user->name }}</p>
                                    <p class="mt-0.5 text-xs text-slate-500">{{ $row->user->email }}</p>
                                </td>
                                <td class="px-5 py-4 align-top">
                                    @if ($row->referral_code)
                                        <code class="rounded-lg bg-violet-50 px-2 py-1 text-xs font-semibold text-violet-800 ring-1 ring-violet-200">{{ $row->referral_code }}</code>
                                    @else
                                        <span class="text-xs text-slate-400">—</span>
                                    @endif
                                </td>
                                <td class="px-5 py-4 align-top text-center">
                                    <span class="inline-flex min-w-[2rem] items-center justify-center rounded-full bg-violet-100 px-2.5 py-0.5 text-sm font-bold tabular-nums text-violet-900">{{ $row->referred_muthowifs_count }}</span>
                                </td>
                                <td class="px-5 py-4 align-top">
                                    <span class="inline-flex rounded-full px-2.5 py-0.5 text-xs font-semibold
                                        @if ($row->isApproved()) bg-emerald-50 text-emerald-800 ring-1 ring-emerald-200
                                        @elseif ($row->isPending()) bg-amber-50 text-amber-800 ring-1 ring-amber-200
                                        @else bg-red-50 text-red-800 ring-1 ring-red-200 @endif">
                                        {{ $row->verification_status->label() }}
                                    </span>
                                </td>
                                <td class="px-5 py-4 align-top text-right">
                                    <a href="{{ route('admin.referrals.show', $row) }}" class="inline-flex items-center gap-1 rounded-xl border border-slate-200 bg-white px-3 py-1.5 text-xs font-semibold text-slate-700 shadow-sm transition hover:border-violet-200 hover:text-violet-800">
                                        {{ __('admin.referrals.view_detail') }}
                                        <svg class="h-3.5 w-3.5" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="m8.25 4.5 7.5 7.5-7.5 7.5" /></svg>
                                    </a>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
                @endif
            </x-ui.data-table>
            @if ($profiles->hasPages())
                <div class="rounded-2xl border border-slate-200/90 bg-white px-5 py-4 shadow-sm ring-1 ring-slate-100/80">
                    {{ $profiles->links() }}
                </div>
            @endif
        </x-page-container>
    </x-ui.app-page>
</x-app-layout>
