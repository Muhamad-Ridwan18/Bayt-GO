@php
    use App\Enums\BookingReplacementStatus;
    use App\Support\IndonesianNumber;
    $fmt = fn (float $n) => IndonesianNumber::formatThousands((string) (int) round($n));
    $autoMode = ! config('incident.require_admin_approval_for_candidates', false);
@endphp
<x-app-layout>
    <div
        class="py-8 sm:py-12"
        x-data="adminIncidentLive({ incidentId: @js($incident->getKey()) })"
    >
        <x-page-container class="space-y-6">
            <a href="{{ route('admin.incidents.index') }}" class="text-sm font-semibold text-brand-700">← {{ __('incidents.admin.index_title') }}</a>

            @if (session('status'))
                <div class="rounded-xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-900">{{ session('status') }}</div>
            @endif

            <div class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm space-y-4">
                <div class="flex items-start justify-between gap-4">
                    <div>
                        <h1 class="text-lg font-bold text-slate-900">{{ __('incidents.admin.monitor_title') }}</h1>
                        <p class="mt-1 text-sm text-slate-600">{{ $autoMode ? __('incidents.admin.monitor_auto_hint') : __('incidents.admin.monitor_manual_hint') }}</p>
                    </div>
                    <span class="rounded-full bg-slate-100 px-3 py-1 text-xs font-semibold text-slate-700">{{ __('incidents.admin.read_only_badge') }}</span>
                </div>
                <dl class="grid grid-cols-2 gap-3 text-sm sm:grid-cols-3">
                    <div><dt class="text-slate-500">Booking</dt><dd class="font-mono font-semibold">{{ $booking->booking_code }}</dd></div>
                    <div><dt class="text-slate-500">Kasus</dt><dd>{{ $incident->case_type->label() }}</dd></div>
                    <div><dt class="text-slate-500">Rekrutmen</dt><dd>{{ $incident->replacement_recruitment_open ? __('incidents.recruitment_open') : __('incidents.recruitment_closed') }}</dd></div>
                    <div><dt class="text-slate-500">{{ __('incidents.admin.pool_count') }}</dt><dd class="font-semibold">{{ $approvedPool->count() }}</dd></div>
                    <div><dt class="text-slate-500">{{ __('incidents.admin.pending_count') }}</dt><dd>{{ $pendingApprovals->count() }}</dd></div>
                    <div><dt class="text-slate-500">Jamaah pilih</dt><dd>{{ $incident->customer_choice_opened_at?->format('d/m H:i') ?? '—' }}</dd></div>
                </dl>
            </div>

            @if (! $autoMode)
                <details class="rounded-2xl border border-amber-200 bg-amber-50/50 p-4 text-sm">
                    <summary class="cursor-pointer font-semibold text-amber-950">{{ __('incidents.admin.override_panel') }}</summary>
                    <p class="mt-2 text-xs text-amber-900">{{ __('incidents.admin.override_hint') }}</p>
                    <div class="mt-4 flex flex-wrap gap-2">
                        @if (! $incident->replacement_recruitment_open)
                            <form method="POST" action="{{ route('admin.incidents.open_recruitment', $incident) }}">@csrf
                                <button class="rounded-lg bg-violet-700 px-3 py-1.5 text-xs font-semibold text-white">{{ __('incidents.admin.open_recruitment') }}</button>
                            </form>
                        @endif
                        @if ($approvedPool->count() > 0 && ! $incident->customer_choice_opened_at)
                            <form method="POST" action="{{ route('admin.incidents.open_customer_choice', $incident) }}">@csrf
                                <button class="rounded-lg bg-emerald-700 px-3 py-1.5 text-xs font-semibold text-white">{{ __('incidents.admin.open_customer_choice', ['count' => $approvedPool->count()]) }}</button>
                            </form>
                        @endif
                    </div>
                </details>
            @endif

            <div class="rounded-2xl border border-slate-200 bg-white p-5">
                <h2 class="text-sm font-bold">{{ __('incidents.admin.candidates_live') }}</h2>
                <ul class="mt-3 space-y-2 text-sm">
                    @forelse ($incident->replacements->sortBy('volunteered_at') as $row)
                        <li class="flex justify-between rounded-lg bg-slate-50 px-3 py-2">
                            <span>{{ $row->replacementProfile?->user?->name ?? '—' }}</span>
                            <span class="text-xs text-slate-500">{{ $row->status->label() }}</span>
                        </li>
                    @empty
                        <li class="text-slate-500">{{ __('incidents.admin.no_candidates_yet') }}</li>
                    @endforelse
                </ul>
            </div>

            @if ($draftSettlement)
                <div class="rounded-2xl border border-slate-200 bg-slate-50 p-5 text-sm">
                    <h2 class="font-bold">Settlement (override)</h2>
                    <ul class="mt-2 space-y-1">
                        @foreach ($draftSettlement->payoutAllocations as $alloc)
                            <li>{{ $alloc->muthowifProfile?->user?->name }} — Rp {{ $fmt((float) $alloc->amount) }}</li>
                        @endforeach
                    </ul>
                    @if ($draftSettlement->status->value !== 'released')
                        <form method="POST" action="{{ route('admin.incidents.release_settlement', [$incident, $draftSettlement]) }}" class="mt-3">@csrf
                            <button class="rounded-lg bg-brand-700 px-3 py-1.5 text-xs font-semibold text-white">{{ __('incidents.admin.release_settlement') }}</button>
                        </form>
                    @endif
                </div>
            @endif

            <details class="rounded-2xl border border-slate-100 bg-slate-50 p-4">
                <summary class="cursor-pointer text-sm font-semibold text-slate-700">{{ __('incidents.admin.emergency_override') }}</summary>
                <div class="mt-3 space-y-2">
                    <form method="POST" action="{{ route('admin.incidents.false_alarm', $incident) }}">@csrf
                        <button class="text-xs font-semibold text-red-700">{{ __('incidents.admin.false_alarm') }}</button>
                    </form>
                </div>
            </details>
        </x-page-container>
    </div>
</x-app-layout>
