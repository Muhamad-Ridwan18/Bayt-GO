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

            @if ($replacements->isEmpty())
                <div class="rounded-2xl border border-dashed border-slate-300 bg-white px-6 py-12 text-center shadow-sm sm:py-14">
                    <p class="text-base font-semibold text-slate-900">{{ __('incidents.muthowif.pending_empty') }}</p>
                </div>
            @else
                <ul class="space-y-4">
                    @foreach ($replacements as $replacement)
                        @include('muthowif.bookings.partials.replacement-invite-card', [
                            'replacement' => $replacement,
                            'defaultOpen' => $loop->first,
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
