<x-app-layout>
    <x-ui.app-page>
        <div class="pointer-events-none absolute inset-0 bg-[radial-gradient(ellipse_80%_40%_at_50%_-10%,rgba(91,33,182,0.07),transparent)]"></div>
        <x-page-container class="ui-stack relative">
            <x-ui.page-hero :badge="__('admin.referrals.badge')" :title="$profile->user->name" :subtitle="__('admin.referrals.show_subtitle')">
                <x-slot:icon>
                    <svg class="h-6 w-6" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M18 18.72a9.09 9.09 0 003.741-.479 3 3 0 00-4.682-2.72m.94 3.198.001.031c0 .225-.012.447-.037.666A11.944 11.944 0 0112 21c-2.17 0-4.207-.576-5.963-1.584A6.062 6.062 0 016 18.719m12 0a5.971 5.971 0 00-.941-3.197m0 0A5.995 5.995 0 0012 12.75a5.995 5.995 0 00-5.058 2.772m0 0a3 3 0 00-4.681 2.72 8.986 8.986 0 003.74.477m.94-3.197a5.971 5.971 0 00-.94 3.197M15 6.75a3 3 0 11-6 0 3 3 0 016 0zm6 3a2.25 2.25 0 11-4.5 0 2.25 2.25 0 014.5 0zm-13.5 0a2.25 2.25 0 11-4.5 0 2.25 2.25 0 014.5 0z" /></svg>
                </x-slot:icon>
                <x-slot:actions>
                    <a href="{{ route('admin.referrals.index') }}" class="inline-flex shrink-0 items-center gap-2 rounded-2xl border border-white/20 bg-white/10 px-4 py-2.5 text-sm font-semibold text-white backdrop-blur-sm transition hover:bg-white/20">
                        {{ __('admin.referrals.back_list') }}
                    </a>
                    <a href="{{ route('admin.muthowif.show', $profile) }}" class="inline-flex shrink-0 items-center gap-2 rounded-2xl bg-white px-4 py-2.5 text-sm font-semibold text-slate-900 shadow-sm transition hover:bg-brand-50">
                        {{ __('admin.referrals.open_verification') }}
                    </a>
                </x-slot:actions>
                <x-slot:stats>
                    <div class="grid grid-cols-1 gap-3 sm:grid-cols-3">
                        <div class="rounded-2xl border border-white/10 bg-white/[0.07] p-4 backdrop-blur-sm">
                            <p class="text-[10px] font-semibold uppercase tracking-wider text-white/55">{{ __('admin.referrals.col_code') }}</p>
                            <p class="mt-2 font-mono text-lg font-bold text-white">
                                {{ $profile->referral_code ?: '—' }}
                            </p>
                        </div>
                        <div class="rounded-2xl border border-white/10 bg-white/[0.07] p-4 backdrop-blur-sm">
                            <p class="text-[10px] font-semibold uppercase tracking-wider text-white/55">{{ __('admin.referrals.col_count') }}</p>
                            <p class="mt-2 text-2xl font-bold tabular-nums text-white">{{ $referred->count() }}</p>
                        </div>
                        <div class="rounded-2xl border border-white/10 bg-white/[0.07] p-4 backdrop-blur-sm">
                            <p class="text-[10px] font-semibold uppercase tracking-wider text-white/55">{{ __('admin.referrals.total_rewards') }}</p>
                            <p class="mt-2 text-2xl font-bold tabular-nums text-white">Rp {{ $fmt($totalRewards) }}</p>
                        </div>
                    </div>
                </x-slot:stats>
            </x-ui.page-hero>

            @if ($profile->referredBy)
                <div class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
                    <p class="text-xs font-semibold uppercase tracking-wide text-slate-500">{{ __('admin.referrals.invited_by') }}</p>
                    <p class="mt-2 text-sm text-slate-700">
                        <a href="{{ route('admin.referrals.show', $profile->referredBy) }}" class="font-semibold text-violet-700 hover:text-violet-900">{{ $profile->referredBy->user->name }}</a>
                        @if ($profile->referredBy->referral_code)
                            <span class="text-slate-500"> · {{ __('admin.referrals.code_used', ['code' => $profile->referredBy->referral_code]) }}</span>
                        @endif
                    </p>
                </div>
            @endif

            <x-ui.data-table :empty="$referred->isEmpty() ? __('admin.referrals.show_empty') : null">
                <x-slot:toolbar>
                    <div class="px-5 py-4">
                        <h2 class="text-base font-bold text-slate-900">{{ __('admin.referrals.referred_list_title') }}</h2>
                        <p class="mt-1 text-sm text-slate-500">{{ __('admin.referrals.referred_list_sub') }}</p>
                    </div>
                </x-slot:toolbar>

                @if (! $referred->isEmpty())
                <table class="min-w-full divide-y divide-slate-100 text-sm">
                    <thead class="bg-slate-50/80">
                        <tr>
                            <th scope="col" class="px-5 py-3 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">{{ __('admin.referrals.col_muthowif') }}</th>
                            <th scope="col" class="px-5 py-3 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">{{ __('admin.referrals.col_registered') }}</th>
                            <th scope="col" class="px-5 py-3 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">{{ __('admin.referrals.col_status') }}</th>
                            <th scope="col" class="px-5 py-3 text-right text-xs font-semibold uppercase tracking-wide text-slate-500">{{ __('admin.referrals.col_action') }}</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100 bg-white">
                        @foreach ($referred as $row)
                            <tr class="transition hover:bg-slate-50/60">
                                <td class="px-5 py-4 align-top">
                                    <p class="font-semibold text-slate-900">{{ $row->user->name }}</p>
                                    <p class="mt-0.5 text-xs text-slate-500">{{ $row->user->email }}</p>
                                    @if ($row->phone)
                                        <p class="mt-0.5 text-xs text-slate-500">{{ $row->phone }}</p>
                                    @endif
                                </td>
                                <td class="px-5 py-4 align-top text-slate-700">
                                    {{ $row->created_at?->timezone(config('app.timezone'))->translatedFormat('d M Y H:i') }}
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
                                    <a href="{{ route('admin.muthowif.show', $row) }}" class="inline-flex items-center gap-1 rounded-xl border border-slate-200 bg-white px-3 py-1.5 text-xs font-semibold text-slate-700 shadow-sm transition hover:border-brand-200 hover:text-brand-800">
                                        {{ __('admin.referrals.open_verification') }}
                                    </a>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
                @endif
            </x-ui.data-table>
        </x-page-container>
    </x-ui.app-page>
</x-app-layout>
