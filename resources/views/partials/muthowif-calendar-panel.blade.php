@php
    use Carbon\Carbon;
@endphp
<div id="muthowif-schedule-calendar" class="min-w-0 rounded-2xl border border-slate-200/80 bg-gradient-to-b from-white to-slate-50/80 p-4 shadow-sm ring-1 ring-slate-100">
    <div class="flex flex-col gap-2 border-b border-slate-200/80 pb-3 sm:flex-row sm:items-center sm:justify-between">
        <div class="flex min-w-0 flex-1 items-start gap-2">
            <span class="mt-0.5 flex h-9 w-9 shrink-0 items-center justify-center rounded-lg bg-brand-100 text-brand-800 ring-1 ring-brand-200/60" aria-hidden="true">
                <svg class="h-4 w-4" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor"><path fill-rule="evenodd" d="M6 2a1 1 0 00-1 1v1H4a2 2 0 00-2 2v10a2 2 0 002 2h12a2 2 0 002-2V6a2 2 0 00-2-2h-1V3a1 1 0 10-2 0v1H7V3a1 1 0 00-1-1zm0 5a1 1 0 000 2h8a1 1 0 100-2H6z" clip-rule="evenodd" /></svg>
            </span>
            <div class="min-w-0 flex-1">
                <div class="flex flex-wrap items-center gap-2">
                    <a
                        href="{{ route('dashboard', ['month' => $calendarMonth->copy()->subMonth()->format('Y-m')]) }}"
                        data-cal-month="{{ $calendarMonth->copy()->subMonth()->format('Y-m') }}"
                        class="inline-flex h-9 w-9 shrink-0 items-center justify-center rounded-lg border border-slate-200 bg-white text-slate-700 shadow-sm ring-1 ring-slate-100 transition hover:border-brand-200 hover:bg-brand-50/80 hover:text-brand-900"
                        title="{{ __('dashboard_muthowif.calendar_prev') }}"
                        aria-label="{{ __('dashboard_muthowif.calendar_prev') }}"
                    >
                        <svg class="h-5 w-5" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true"><path fill-rule="evenodd" d="M12.79 5.23a.75.75 0 01-.02 1.06L8.832 10l3.938 3.71a.75.75 0 11-1.04 1.08l-4.5-4.25a.75.75 0 010-1.08l4.5-4.25a.75.75 0 011.06.02z" clip-rule="evenodd" /></svg>
                    </a>
                    <h3 class="min-w-0 flex-1 text-sm font-bold tracking-tight text-slate-900 sm:text-base">{{ __('dashboard_muthowif.calendar_title', ['month' => $calendarMonth->translatedFormat('F Y')]) }}</h3>
                    <a
                        href="{{ route('dashboard', ['month' => $calendarMonth->copy()->addMonth()->format('Y-m')]) }}"
                        data-cal-month="{{ $calendarMonth->copy()->addMonth()->format('Y-m') }}"
                        class="inline-flex h-9 w-9 shrink-0 items-center justify-center rounded-lg border border-slate-200 bg-white text-slate-700 shadow-sm ring-1 ring-slate-100 transition hover:border-brand-200 hover:bg-brand-50/80 hover:text-brand-900"
                        title="{{ __('dashboard_muthowif.calendar_next') }}"
                        aria-label="{{ __('dashboard_muthowif.calendar_next') }}"
                    >
                        <svg class="h-5 w-5" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true"><path fill-rule="evenodd" d="M7.21 14.77a.75.75 0 01.02-1.06L11.168 10 7.23 6.29a.75.75 0 111.04-1.08l4.5 4.25a.75.75 0 010 1.08l-4.5 4.25a.75.75 0 01-1.06-.02z" clip-rule="evenodd" /></svg>
                    </a>
                </div>
                <div class="mt-1 flex flex-wrap items-center gap-x-3 gap-y-1">
                    @if (! $calendarMonth->isSameMonth(now()))
                        <a href="{{ route('dashboard') }}" data-cal-today class="text-[11px] font-semibold text-brand-700 hover:text-brand-800 hover:underline">{{ __('dashboard_muthowif.calendar_today') }}</a>
                    @endif
                    <p class="text-[11px] text-slate-500">{{ __('dashboard_muthowif.tooltip_hint') }}</p>
                </div>
            </div>
        </div>
        <div class="flex flex-wrap items-center gap-3 text-xs font-medium">
            <span class="inline-flex items-center gap-1.5 rounded-full bg-brand-50 px-2.5 py-1 text-brand-800 ring-1 ring-brand-200/60"><span class="h-2 w-2 rounded-full bg-brand-500"></span> {{ __('dashboard_muthowif.legend_booking') }}</span>
            <span class="inline-flex items-center gap-1.5 rounded-full bg-amber-50 px-2.5 py-1 text-amber-900 ring-1 ring-amber-200/60"><span class="h-2 w-2 rounded-full bg-amber-500"></span> {{ __('dashboard_muthowif.legend_off') }}</span>
        </div>
    </div>

    <div class="-mx-1 overflow-x-auto pb-1 pt-3 sm:mx-0">
        <div class="min-w-[280px] sm:min-w-0">
            <div class="grid grid-cols-7 gap-1 text-center text-[10px] font-bold uppercase tracking-wide text-slate-500 sm:text-xs">
                @foreach (__('dashboard_muthowif.calendar_weekdays') as $dow)
                    <div class="py-1.5">{{ $dow }}</div>
                @endforeach
            </div>

            <div class="mt-1 grid grid-cols-7 gap-1">
                @for ($day = $calendarStart->copy(); $day->lte($calendarEnd); $day->addDay())
                    @php
                        $dateKey = $day->toDateString();
                        $isCurrentMonth = $day->month === $calendarMonth->month;
                        $isToday = $day->isToday();
                        $hasBooking = $bookingSet->has($dateKey);
                        $isBlocked = $blockedSet->has($dateKey);
                        $bookingsOnDay = collect($calendarDetails[$dateKey]['bookings'] ?? [])->unique(fn ($row) => ($row['name'] ?? '').'|'.($row['service_short'] ?? $row['service'] ?? ''))->values();
                        $blockedOnDay = collect($calendarDetails[$dateKey]['blocked'] ?? [])->unique()->values();
                        $dayCardClass = match (true) {
                            $hasBooking && $isBlocked => 'border-violet-200/90 bg-violet-50',
                            $hasBooking => 'border-brand-200/90 bg-brand-50/90',
                            $isBlocked => 'border-amber-200/90 bg-amber-50/80',
                            default => $isCurrentMonth ? 'border-slate-200/80 bg-white' : 'border-slate-100 bg-slate-50/90 text-slate-400',
                        };
                    @endphp
                    <div class="group relative flex flex-col rounded-lg border px-0.5 py-0.5 shadow-sm transition {{ $isToday ? 'ring-2 ring-brand-400 ring-offset-1' : '' }} {{ $hasBooking && $bookingsOnDay->isNotEmpty() ? 'min-h-[4.5rem]' : 'min-h-14' }} {{ $dayCardClass }}">
                        <div class="shrink-0 text-[11px] font-bold {{ $isToday ? 'text-brand-700' : 'text-slate-700' }}">{{ $day->day }}</div>
                        @if ($hasBooking && $bookingsOnDay->isNotEmpty())
                            <div class="mt-0.5 min-h-0 flex-1 space-y-0.5 overflow-hidden text-left">
                                @foreach ($bookingsOnDay->take(2) as $row)
                                    <div class="truncate rounded-md bg-white/90 px-1 py-0.5 leading-tight shadow-sm ring-1 ring-slate-200/60">
                                        <p class="truncate text-[9px] font-semibold text-brand-900" title="{{ $row['name'] }} ({{ $row['service'] }})">{{ \Illuminate\Support\Str::limit($row['name'], 16) }}</p>
                                        <p class="truncate text-[8px] font-medium text-brand-700">{{ $row['service_short'] ?? $row['service'] }}</p>
                                    </div>
                                @endforeach
                                @if ($bookingsOnDay->count() > 2)
                                    <p class="truncate pl-0.5 text-[8px] font-semibold text-brand-800">{{ __('dashboard_muthowif.more_others', ['count' => $bookingsOnDay->count() - 2]) }}</p>
                                @endif
                            </div>
                        @endif
                        @if ($isBlocked)
                            <div class="mt-auto shrink-0 pt-0.5">
                                <span class="inline-block max-w-full truncate rounded-md bg-white/90 px-1 py-0.5 text-[9px] font-semibold text-amber-900 ring-1 ring-amber-200/60">{{ __('dashboard_muthowif.day_off_label') }}</span>
                            </div>
                        @endif

                        @if (($hasBooking || $isBlocked) && $isCurrentMonth)
                            <div class="absolute left-1/2 top-full z-20 mt-1 hidden w-52 -translate-x-1/2 rounded-xl border border-slate-200 bg-white p-3 text-left shadow-xl ring-1 ring-slate-100 group-hover:block group-focus-within:block">
                                <p class="text-[11px] font-semibold text-slate-900">{{ $day->translatedFormat('d M Y') }}</p>
                                @if ($bookingsOnDay->isNotEmpty())
                                    <p class="mt-2 text-[10px] font-semibold uppercase tracking-wide text-brand-700">{{ __('dashboard_muthowif.tooltip_booking') }}</p>
                                    <ul class="mt-0.5 space-y-0.5 text-[11px] text-slate-700">
                                        @foreach ($bookingsOnDay as $row)
                                            <li>• {{ $row['name'] }} ({{ $row['service'] }})</li>
                                        @endforeach
                                    </ul>
                                @endif
                                @if ($blockedOnDay->isNotEmpty())
                                    <p class="mt-2 text-[10px] font-semibold uppercase tracking-wide text-amber-700">{{ __('dashboard_muthowif.tooltip_off') }}</p>
                                    <ul class="mt-0.5 space-y-0.5 text-[11px] text-slate-700">
                                        @foreach ($blockedOnDay as $note)
                                            <li>• {{ $note }}</li>
                                        @endforeach
                                    </ul>
                                @endif
                                <p class="mt-2 text-[10px] text-slate-400">{{ __('dashboard_muthowif.tooltip_hint') }}</p>
                            </div>
                        @endif
                    </div>
                @endfor
            </div>
        </div>
    </div>
</div>
