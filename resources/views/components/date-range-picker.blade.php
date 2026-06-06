@props([
    'startDate' => '',
    'endDate' => '',
    'minDate' => null,
    'label' => null,
    'inputClass' => 'block w-full h-11 rounded-xl border border-slate-200 bg-white px-4 text-sm text-slate-900 shadow-sm transition duration-200 focus:border-emerald-500 focus:outline-none focus:ring-2 focus:ring-emerald-500/25',
    'nameStart' => 'start_date',
    'nameEnd' => 'end_date',
    'required' => true,
])

@php
    $locale = app()->getLocale();
    $weekdayLabels = $locale === 'id'
        ? ['Sen', 'Sel', 'Rab', 'Kam', 'Jum', 'Sab', 'Min']
        : ['Sun', 'Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat'];

    $pickerLabels = [
        'title' => __('layanan.date_picker_title'),
        'subtitle' => __('layanan.date_picker_subtitle'),
        'departure' => __('layanan.date_picker_departure'),
        'return' => __('layanan.date_picker_return'),
        'duration' => __('layanan.date_picker_duration'),
        'durationDays' => __('layanan.date_picker_duration_days'),
        'clear' => __('layanan.date_picker_clear'),
        'cancel' => __('layanan.date_picker_cancel'),
        'apply' => __('layanan.date_picker_apply'),
        'close' => __('layanan.date_picker_close'),
        'prevMonth' => __('layanan.date_picker_prev_month'),
        'nextMonth' => __('layanan.date_picker_next_month'),
        'unavailable' => __('layanan.date_picker_unavailable'),
        'today' => __('layanan.date_picker_today'),
    ];
@endphp

<div
    {{ $attributes->class('relative') }}
    x-data="travelDateRangePicker({
        start: @js($startDate),
        end: @js($endDate),
        min: @js($minDate ?? now()->toDateString()),
        locale: @js($locale),
        weekdayLabels: @js($weekdayLabels),
        labels: @js($pickerLabels),
    })"
    @keydown="onKeydown($event)"
>
    @if ($label)
        <label for="travel_date_range_{{ $nameStart }}" class="block text-sm font-medium text-slate-700">
            {{ $label }}
        </label>
    @endif

    <div class="{{ $label ? 'mt-2' : '' }} relative">
        <button
            type="button"
            id="travel_date_range_{{ $nameStart }}"
            x-ref="trigger"
            @click="openModal()"
            :aria-expanded="open"
            aria-haspopup="dialog"
            aria-controls="travel-date-range-modal"
            class="{{ $inputClass }} flex w-full cursor-pointer items-center justify-between gap-3 text-left"
        >
            <span
                class="truncate"
                :class="displayValue ? 'text-slate-900' : 'text-slate-400'"
                x-text="displayValue || @js(__('layanan.date_range_placeholder'))"
            ></span>
            <span class="pointer-events-none shrink-0 text-slate-400" aria-hidden="true">
                <svg class="h-5 w-5" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor">
                    <path fill-rule="evenodd" d="M6.75 2.25A.75.75 0 017.5 3v1.5h9V3A.75.75 0 0118 3v1.5h.75a3 3 0 013 3v11.25a3 3 0 01-3 3H5.25a3 3 0 01-3-3V7.5a3 3 0 013-3H6V3a.75.75 0 01.75-.75zm13.5 9a1.5 1.5 0 00-1.5-1.5H5.25a1.5 1.5 0 00-1.5 1.5v7.5a1.5 1.5 0 001.5 1.5h13.5a1.5 1.5 0 001.5-1.5v-7.5z" clip-rule="evenodd" />
                </svg>
            </span>
        </button>

        <input type="hidden" name="{{ $nameStart }}" x-ref="startInput" value="{{ $startDate }}" @if($required) required @endif />
        <input type="hidden" name="{{ $nameEnd }}" x-ref="endInput" value="{{ $endDate }}" />
    </div>

    <template x-teleport="body">
        <div
            x-show="open"
            x-cloak
            class="fixed inset-0 z-[100] flex items-end justify-center sm:items-center sm:p-4"
            role="presentation"
        >
            <button
                type="button"
                class="absolute inset-0 bg-slate-900/40 transition-opacity duration-200"
                :class="open ? 'opacity-100' : 'opacity-0'"
                @click="closeModal(true)"
                :aria-label="labels.close"
            ></button>

            <div
                id="travel-date-range-modal"
                x-ref="modal"
                tabindex="-1"
                role="dialog"
                aria-modal="true"
                :aria-label="labels.title"
                x-show="open"
                x-transition:enter="transition ease-out duration-200"
                x-transition:enter-start="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95"
                x-transition:enter-end="opacity-100 translate-y-0 sm:scale-100"
                x-transition:leave="transition ease-in duration-200"
                x-transition:leave-start="opacity-100 translate-y-0 sm:scale-100"
                x-transition:leave-end="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95"
                class="relative z-10 flex max-h-[92vh] w-full max-w-3xl flex-col overflow-hidden rounded-t-[24px] border border-slate-200 bg-white shadow-2xl shadow-slate-900/10 sm:rounded-[24px]"
            >
                <div class="border-b border-slate-100 px-6 py-5 sm:px-8">
                    <div class="flex items-start justify-between gap-4">
                        <div>
                            <h2 class="text-lg font-semibold tracking-tight text-slate-900 sm:text-xl" x-text="labels.title"></h2>
                            <p class="mt-1 text-sm text-slate-500" x-text="labels.subtitle"></p>
                        </div>
                        <button
                            type="button"
                            class="inline-flex h-9 w-9 shrink-0 items-center justify-center rounded-full text-slate-400 transition duration-200 hover:bg-slate-100 hover:text-slate-700 focus:outline-none focus-visible:ring-2 focus-visible:ring-emerald-500"
                            @click="closeModal(true)"
                            :aria-label="labels.close"
                        >
                            <svg class="h-5 w-5" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2" aria-hidden="true">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" />
                            </svg>
                        </button>
                    </div>

                    <div class="mt-5 grid grid-cols-3 gap-3 rounded-2xl border border-slate-100 bg-slate-50/80 p-4 sm:gap-4">
                        <div>
                            <p class="text-[11px] font-semibold uppercase tracking-wide text-slate-500" x-text="labels.departure"></p>
                            <p class="mt-1 text-sm font-semibold text-slate-900" x-text="summaryDeparture"></p>
                        </div>
                        <div>
                            <p class="text-[11px] font-semibold uppercase tracking-wide text-slate-500" x-text="labels.return"></p>
                            <p class="mt-1 text-sm font-semibold text-slate-900" x-text="summaryReturn"></p>
                        </div>
                        <div>
                            <p class="text-[11px] font-semibold uppercase tracking-wide text-slate-500" x-text="labels.duration"></p>
                            <p class="mt-1 text-sm font-semibold text-emerald-700" x-text="summaryDuration"></p>
                        </div>
                    </div>
                </div>

                <div class="overflow-y-auto px-4 py-5 sm:px-6">
                    <div class="mb-4 flex items-center justify-between gap-3">
                        <button
                            type="button"
                            class="inline-flex h-10 w-10 items-center justify-center rounded-full border border-slate-200 text-slate-600 transition duration-200 hover:border-emerald-200 hover:bg-emerald-50 hover:text-emerald-700 focus:outline-none focus-visible:ring-2 focus-visible:ring-emerald-500"
                            @click="prevMonth()"
                            :aria-label="labels.prevMonth"
                        >
                            <svg class="h-5 w-5" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2" aria-hidden="true">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M15 19l-7-7 7-7" />
                            </svg>
                        </button>

                        <div class="grid flex-1 gap-6" :class="isMobile ? 'grid-cols-1' : 'grid-cols-2'">
                            <template x-for="panel in monthPanels" :key="panel.key">
                                <div>
                                    <p class="mb-3 text-center text-sm font-semibold text-slate-900" x-text="panel.label"></p>
                                    <div class="grid grid-cols-7 gap-1 text-center">
                                        <template x-for="(weekday, index) in weekdayLabels" :key="`${panel.key}-wd-${index}`">
                                            <div class="py-1 text-[11px] font-semibold uppercase tracking-wide text-slate-400" x-text="weekday"></div>
                                        </template>
                                        <template x-for="(week, weekIndex) in panel.weeks" :key="`${panel.key}-week-${weekIndex}`">
                                            <template x-for="(cell, dayIndex) in week" :key="`${panel.key}-day-${weekIndex}-${dayIndex}`">
                                                <button
                                                    type="button"
                                                    :disabled="cell.disabled"
                                                    :class="dayClasses(cell)"
                                                    :aria-label="dayAriaLabel(cell)"
                                                    @click="selectDate(cell.date)"
                                                    @mouseenter="onDayHover(cell.date)"
                                                    @mouseleave="onDayLeave()"
                                                    @focus="focusDate = cell.date"
                                                >
                                                    <span x-text="cell.date.getDate()"></span>
                                                </button>
                                            </template>
                                        </template>
                                    </div>
                                </div>
                            </template>
                        </div>

                        <button
                            type="button"
                            class="inline-flex h-10 w-10 items-center justify-center rounded-full border border-slate-200 text-slate-600 transition duration-200 hover:border-emerald-200 hover:bg-emerald-50 hover:text-emerald-700 focus:outline-none focus-visible:ring-2 focus-visible:ring-emerald-500"
                            @click="nextMonth()"
                            :aria-label="labels.nextMonth"
                        >
                            <svg class="h-5 w-5" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2" aria-hidden="true">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7" />
                            </svg>
                        </button>
                    </div>
                </div>

                <div class="flex flex-col-reverse gap-2 border-t border-slate-100 px-6 py-4 sm:flex-row sm:items-center sm:justify-between sm:px-8">
                    <button
                        type="button"
                        class="inline-flex items-center justify-center rounded-xl px-4 py-2.5 text-sm font-semibold text-slate-600 transition duration-200 hover:bg-slate-100 hover:text-slate-900 focus:outline-none focus-visible:ring-2 focus-visible:ring-emerald-500"
                        @click="clearSelection()"
                        x-text="labels.clear"
                    ></button>

                    <div class="flex flex-col gap-2 sm:flex-row">
                        <button
                            type="button"
                            class="inline-flex items-center justify-center rounded-xl border border-slate-200 px-5 py-2.5 text-sm font-semibold text-slate-700 transition duration-200 hover:bg-slate-50 focus:outline-none focus-visible:ring-2 focus-visible:ring-emerald-500"
                            @click="closeModal(true)"
                            x-text="labels.cancel"
                        ></button>
                        <button
                            type="button"
                            class="inline-flex items-center justify-center rounded-xl bg-emerald-600 px-5 py-2.5 text-sm font-semibold text-white shadow-sm shadow-emerald-600/25 transition duration-200 hover:bg-emerald-700 focus:outline-none focus-visible:ring-2 focus-visible:ring-emerald-500 focus-visible:ring-offset-2 disabled:cursor-not-allowed disabled:opacity-50"
                            :disabled="!canApply"
                            @click="applyDates()"
                            x-text="labels.apply"
                        ></button>
                    </div>
                </div>
            </div>
        </div>
    </template>
</div>
