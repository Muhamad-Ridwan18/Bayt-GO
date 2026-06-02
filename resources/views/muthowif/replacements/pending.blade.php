@php
    $tab = $tab ?? 'pending';
    $counts = $counts ?? ['pending' => 0, 'active' => 0, 'history' => 0];
    $emptyKey = match ($tab) {
        'active' => 'muthowif.replacements.empty_active',
        'history' => 'muthowif.replacements.empty_history',
        default => 'incidents.muthowif.pending_empty',
    };
@endphp

<x-app-layout>
    <div class="min-h-[calc(100vh-4rem)] bg-slate-100 py-6 sm:py-8">
        <x-page-container class="space-y-6 py-2 sm:py-4">
            <div class="flex flex-col gap-4 sm:flex-row sm:items-start sm:justify-between">
                <div>
                    <h1 class="text-2xl font-bold tracking-tight text-slate-900 sm:text-3xl">{{ __('muthowif.replacements.page_invites_title') }}</h1>
                    <p class="mt-2 max-w-2xl text-sm leading-relaxed text-slate-600">{{ __('muthowif.replacements.page_invites_subtitle') }}</p>
                </div>
                <div class="flex shrink-0 flex-col gap-2 self-start sm:items-end">
                    <a href="{{ route('muthowif.bookings.index') }}" class="inline-flex items-center gap-2 rounded-xl border border-slate-200 bg-white px-4 py-2.5 text-sm font-semibold text-slate-800 shadow-sm transition hover:border-slate-300 hover:bg-slate-50">
                        {{ __('muthowif.replacements.back_bookings') }}
                    </a>
                    <a href="{{ route('muthowif.replacements.opportunities') }}" class="text-sm font-semibold text-brand-700 hover:text-brand-800">
                        {{ __('incidents.muthowif.browse_opportunities') }} →
                    </a>
                </div>
            </div>

            @if (session('status'))
                <div class="rounded-xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-900">{{ session('status') }}</div>
            @endif
            @if (session('error'))
                <div class="rounded-xl border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-900">{{ session('error') }}</div>
            @endif

            <nav class="flex flex-wrap gap-2 rounded-2xl border border-slate-200 bg-white p-1.5 shadow-sm" aria-label="{{ __('muthowif.replacements.tabs_aria') }}">
                @foreach (['pending' => __('muthowif.replacements.tab_pending'), 'active' => __('muthowif.replacements.tab_active'), 'history' => __('muthowif.replacements.tab_history')] as $tabKey => $tabLabel)
                    <a
                        href="{{ route('muthowif.replacements.pending', ['tab' => $tabKey]) }}"
                        class="inline-flex items-center gap-2 rounded-xl px-4 py-2.5 text-sm font-semibold transition {{ $tab === $tabKey ? 'bg-baytgo text-white shadow-sm' : 'text-slate-700 hover:bg-slate-50' }}"
                    >
                        {{ $tabLabel }}
                        @if (($counts[$tabKey] ?? 0) > 0)
                            <span class="inline-flex min-h-[1.25rem] min-w-[1.25rem] items-center justify-center rounded-full px-1.5 text-[11px] font-bold tabular-nums {{ $tab === $tabKey ? 'bg-white/20 text-white' : 'bg-slate-100 text-slate-700' }}">
                                {{ $counts[$tabKey] }}
                            </span>
                        @endif
                    </a>
                @endforeach
            </nav>

            @if ($replacements->isEmpty())
                <div class="rounded-2xl border border-dashed border-slate-300 bg-white px-6 py-12 text-center shadow-sm sm:py-14">
                    <p class="text-base font-semibold text-slate-900">{{ __($emptyKey) }}</p>
                </div>
            @else
                <ul class="space-y-4">
                    @foreach ($replacements as $replacement)
                        @include('muthowif.bookings.partials.replacement-invite-card', [
                            'replacement' => $replacement,
                            'defaultOpen' => $loop->first && $tab === 'pending',
                        ])
                    @endforeach
                </ul>

                <div class="flex justify-center rounded-2xl border border-slate-200 bg-white px-3 py-3 shadow-sm sm:justify-end">
                    {{ $replacements->links() }}
                </div>
            @endif
        </x-page-container>
    </div>
</x-app-layout>
