<x-app-layout>
    <div
        class="min-h-[calc(100vh-4rem)] bg-slate-100 py-6 sm:py-8"
        x-data="muthowifRecruitmentLive()"
    >
        <x-page-container class="space-y-6 py-2 sm:py-4">
            <div class="flex flex-col gap-4 sm:flex-row sm:items-start sm:justify-between">
                <div>
                    <h1 class="text-2xl font-bold tracking-tight text-slate-900 sm:text-3xl">{{ __('muthowif.replacements.page_opportunities_title') }}</h1>
                    <p class="mt-2 max-w-2xl text-sm leading-relaxed text-slate-600">{{ __('muthowif.replacements.page_opportunities_subtitle') }}</p>
                </div>
                <div class="flex shrink-0 flex-col gap-2 self-start sm:items-end">
                    <a href="{{ route('muthowif.replacements.pending') }}" class="inline-flex items-center gap-2 rounded-xl border border-violet-200 bg-violet-50 px-4 py-2.5 text-sm font-semibold text-violet-900 shadow-sm transition hover:bg-violet-100">
                        {{ __('incidents.muthowif.pending_invites') }}
                    </a>
                    <a href="{{ route('muthowif.bookings.index') }}" class="text-sm font-semibold text-brand-700 hover:text-brand-800">
                        {{ __('muthowif.replacements.back_bookings') }} →
                    </a>
                </div>
            </div>

            @if (session('status'))
                <div class="rounded-xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-900">{{ session('status') }}</div>
            @endif
            @if (session('error'))
                <div class="rounded-xl border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-900">{{ session('error') }}</div>
            @endif

            @if ($incidents->isEmpty())
                <div class="rounded-2xl border border-dashed border-slate-300 bg-white px-6 py-12 text-center shadow-sm sm:py-14">
                    <p class="text-base font-semibold text-slate-900">{{ __('incidents.muthowif.opportunities_empty') }}</p>
                </div>
            @else
                <ul class="space-y-4">
                    @foreach ($incidents as $incident)
                        @php $booking = $incident->muthowifBooking; @endphp
                        @if ($booking)
                            @include('muthowif.bookings.partials.replacement-request-card', [
                                'variant' => 'opportunity',
                                'booking' => $booking,
                                'incident' => $incident,
                                'addonsById' => $addonsById ?? collect(),
                                'defaultOpen' => $loop->first,
                            ])
                        @endif
                    @endforeach
                </ul>
            @endif
        </x-page-container>
    </div>
</x-app-layout>
