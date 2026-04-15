@php
    use Carbon\Carbon;
@endphp
<div id="muthowif-schedule-blocked" class="rounded-2xl border border-slate-200/80 bg-gradient-to-b from-amber-50/50 to-white p-4 shadow-sm ring-1 ring-amber-100/80">
    <div class="flex flex-wrap items-center justify-between gap-2 border-b border-amber-200/60 pb-2">
        <div class="flex items-center gap-2">
            <span class="h-8 w-1 rounded-full bg-gradient-to-b from-amber-400 to-orange-500" aria-hidden="true"></span>
            <h4 class="text-sm font-bold text-slate-900">{{ __('dashboard_muthowif.blocked_month', ['month' => $calendarMonth->translatedFormat('F Y')]) }}</h4>
        </div>
        <a href="{{ route('muthowif.jadwal.index') }}" class="shrink-0 rounded-full bg-amber-100/90 px-3 py-1.5 text-xs font-semibold text-amber-950 ring-1 ring-amber-200/80 transition hover:bg-amber-200/80">{{ __('dashboard_muthowif.nav_time_off') }}</a>
    </div>
    @if ($blockedDatesThisMonth->isEmpty())
        <p class="mt-3 text-sm text-slate-500">{{ __('dashboard_muthowif.no_blocked') }}</p>
    @else
        <ul class="mt-3 space-y-2 text-sm">
            @foreach ($blockedDatesThisMonth as $row)
                <li class="rounded-xl border border-amber-200/80 bg-white/90 px-3 py-2 shadow-sm ring-1 ring-amber-100/60">
                    <p class="font-semibold text-slate-900">{{ Carbon::parse($row->blocked_on)->format('d M Y') }}</p>
                    <p class="mt-0.5 text-xs text-slate-600">{{ $row->note ?: __('dashboard_muthowif.default_off_note') }}</p>
                </li>
            @endforeach
        </ul>
    @endif
</div>
