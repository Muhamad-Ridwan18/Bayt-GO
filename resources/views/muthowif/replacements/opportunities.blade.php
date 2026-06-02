<x-app-layout>
    <x-page-container class="py-8" x-data="muthowifRecruitmentLive()">
        <div class="flex flex-wrap items-center justify-between gap-3">
            <div>
                <h1 class="text-lg font-bold text-slate-900">{{ __('incidents.muthowif.opportunities_title') }}</h1>
                <p class="mt-1 text-sm text-slate-600">{{ __('incidents.muthowif.opportunities_subtitle_auto') }}</p>
            </div>
            <a href="{{ route('muthowif.replacements.pending') }}" class="text-sm font-semibold text-brand-700">{{ __('incidents.muthowif.pending_invites') }}</a>
        </div>

        @if (session('status'))
            <div class="mt-4 rounded-xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-900">{{ session('status') }}</div>
        @endif
        @if (session('error'))
            <div class="mt-4 rounded-xl border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-900">{{ session('error') }}</div>
        @endif

        <div class="mt-6 space-y-4">
            @forelse ($incidents as $incident)
                @php $booking = $incident->muthowifBooking; @endphp
                <article class="rounded-2xl border border-violet-200 bg-white p-5 shadow-sm">
                    <p class="font-mono text-xs text-slate-500">{{ $booking?->booking_code }}</p>
                    <p class="mt-1 font-semibold text-slate-900">{{ $booking?->customer?->name }}</p>
                    <p class="mt-1 text-xs text-slate-600">
                        {{ $booking?->starts_on?->format('d M Y') }} – {{ $booking?->ends_on?->format('d M Y') }}
                        · {{ $incident->case_type->label() }}
                    </p>
                    <div class="mt-4 flex flex-col gap-2 sm:flex-row sm:flex-wrap">
                        <form method="POST" action="{{ route('muthowif.replacements.volunteer', $incident) }}" class="flex-1 min-w-[12rem] space-y-2">
                            @csrf
                            <input type="text" name="note" class="w-full rounded-xl border-slate-200 text-sm" placeholder="{{ __('incidents.muthowif.volunteer_note_placeholder') }}">
                            <button type="submit" class="w-full rounded-xl bg-emerald-700 px-4 py-2.5 text-sm font-bold text-white hover:bg-emerald-800">
                                {{ __('incidents.muthowif.accept_offer') }}
                            </button>
                        </form>
                        <form method="POST" action="{{ route('muthowif.replacements.decline', $incident) }}" class="sm:self-end" onsubmit="return confirm(@json(__('incidents.muthowif.decline_confirm')));">
                            @csrf
                            <button type="submit" class="w-full rounded-xl border border-slate-300 bg-white px-4 py-2.5 text-sm font-semibold text-slate-700 hover:bg-slate-50 sm:w-auto">
                                {{ __('incidents.muthowif.decline_offer') }}
                            </button>
                        </form>
                    </div>
                </article>
            @empty
                <p class="text-sm text-slate-500">{{ __('incidents.muthowif.opportunities_empty') }}</p>
            @endforelse
        </x-page-container>
    </div>
</x-app-layout>
