<x-app-layout>
    <x-page-container class="py-8">
        <div class="flex flex-col gap-3 sm:flex-row sm:items-start sm:justify-between">
            <div>
                <h1 class="text-xl font-bold text-slate-900">{{ __('incidents.muthowif.pending_page_title') }}</h1>
                <p class="mt-1 text-sm text-slate-600">{{ __('incidents.muthowif.pending_page_subtitle') }}</p>
            </div>
            <a href="{{ route('muthowif.replacements.opportunities') }}" class="shrink-0 text-sm font-semibold text-brand-700 hover:text-brand-800">
                {{ __('incidents.muthowif.browse_opportunities') }}
            </a>
        </div>

        <div class="mt-6 space-y-4">
            @forelse ($replacements as $replacement)
                @include('muthowif.bookings.partials.replacement-invite-card', ['replacement' => $replacement])
            @empty
                <div class="rounded-2xl border border-dashed border-slate-300 bg-slate-50 px-6 py-10 text-center">
                    <p class="text-sm text-slate-600">{{ __('incidents.muthowif.pending_empty') }}</p>
                </div>
            @endforelse
        </div>

        {{ $replacements->links() }}
    </x-page-container>
</x-app-layout>
