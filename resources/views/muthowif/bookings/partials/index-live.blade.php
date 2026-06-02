@php
    $peerRecommendByBooking = $peerRecommendByBooking ?? [];
    $pendingReplacementInvites = $pendingReplacementInvites ?? collect();
@endphp

<x-page-container class="space-y-6 py-2 sm:py-4">
    <div class="flex flex-col gap-4 sm:flex-row sm:items-start sm:justify-between">
        <div>
            <h1 class="text-2xl font-bold tracking-tight text-slate-900 sm:text-3xl">{{ __('muthowif.bookings.page_title') }}</h1>
            <p class="mt-2 max-w-2xl text-sm leading-relaxed text-slate-600">{{ __('muthowif.bookings.page_subtitle') }}</p>
        </div>
        <a href="{{ route('dashboard') }}" class="inline-flex shrink-0 items-center gap-2 self-start rounded-xl border border-slate-200 bg-white px-4 py-2.5 text-sm font-semibold text-slate-800 shadow-sm transition hover:border-slate-300 hover:bg-slate-50">
            <svg class="h-4 w-4 text-slate-500" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true"><path fill-rule="evenodd" d="M9.293 2.293a1 1 0 011.414 0l7 7A1 1 0 0117 11h-1v6a1 1 0 01-1 1h-2a1 1 0 01-1-1v-3a1 1 0 00-1-1H9a1 1 0 00-1 1v3a1 1 0 01-1 1H5a1 1 0 01-1-1v-6H3a1 1 0 01-.707-1.707l7-7z" clip-rule="evenodd" /></svg>
            {{ __('muthowif.bookings.back_dashboard') }}
        </a>
    </div>

    @if ($pendingReplacementInvites->isNotEmpty())
        <section class="space-y-4">
            <div class="flex flex-wrap items-center justify-between gap-2">
                <div>
                    <h2 class="text-lg font-bold text-slate-900 sm:text-xl">{{ __('incidents.muthowif.index_pending_invites_heading') }}</h2>
                    <p class="mt-1 text-sm text-slate-600">{{ __('muthowif.replacements.page_invites_subtitle') }}</p>
                </div>
                <a href="{{ route('muthowif.replacements.pending') }}" class="shrink-0 text-sm font-semibold text-brand-700 hover:text-brand-800">
                    {{ __('incidents.muthowif.pending_invites') }} →
                </a>
            </div>
            <ul class="space-y-4">
                @foreach ($pendingReplacementInvites as $replacement)
                    @include('muthowif.bookings.partials.replacement-invite-card', [
                        'replacement' => $replacement,
                        'addonsById' => $addonsById ?? collect(),
                        'defaultOpen' => $loop->first,
                    ])
                @endforeach
            </ul>
        </section>
    @endif

    @if ($bookings->isEmpty())
        <div class="rounded-2xl border border-dashed border-slate-300 bg-white px-6 py-12 text-center shadow-sm sm:py-14">
            <span class="mx-auto flex h-14 w-14 items-center justify-center rounded-2xl bg-slate-100 text-slate-500" aria-hidden="true">
                <svg class="h-7 w-7" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M19.5 14.25v-2.625a3.375 3.375 0 00-3.375-3.375h-1.5A1.125 1.125 0 0113.5 7.125v-1.5a3.375 3.375 0 00-3.375-3.375H8.25m0 12.75h7.5m-7.5 3H12M10.5 2.25H5.625c-.621 0-1.125.504-1.125 1.125v17.25c0 .621.504 1.125 1.125 1.125h12.75c.621 0 1.125-.504 1.125-1.125V11.25a9 9 0 00-9-9z" /></svg>
            </span>
            <p class="mt-4 text-base font-semibold text-slate-900">{{ __('muthowif.bookings.empty') }}</p>
            <p class="mx-auto mt-2 max-w-md text-sm text-slate-600">{{ __('muthowif.bookings.empty_hint') }}</p>
        </div>
    @else
        <ul class="space-y-4">
            @foreach ($bookings as $booking)
                @include('muthowif.bookings.partials.booking-request-card', [
                    'booking' => $booking,
                    'addonsById' => $addonsById,
                ])
            @endforeach
        </ul>

        <div class="flex justify-center rounded-2xl border border-slate-200 bg-white px-3 py-3 shadow-sm sm:justify-end">
            {{ $bookings->links() }}
        </div>
    @endif
</x-page-container>
