const MS_PER_DAY = 86_400_000;

function parseIso(value) {
    if (! value || typeof value !== 'string') {
        return null;
    }

    const parts = value.split('-').map(Number);
    if (parts.length !== 3 || parts.some(Number.isNaN)) {
        return null;
    }

    const date = new Date(parts[0], parts[1] - 1, parts[2]);
    date.setHours(0, 0, 0, 0);

    return Number.isNaN(date.getTime()) ? null : date;
}

function toIso(date) {
    if (! date) {
        return '';
    }

    const y = date.getFullYear();
    const m = String(date.getMonth() + 1).padStart(2, '0');
    const d = String(date.getDate()).padStart(2, '0');

    return `${y}-${m}-${d}`;
}

function sameDay(a, b) {
    return (
        a
        && b
        && a.getFullYear() === b.getFullYear()
        && a.getMonth() === b.getMonth()
        && a.getDate() === b.getDate()
    );
}

function addMonths(date, count) {
    const next = new Date(date);
    next.setDate(1);
    next.setMonth(next.getMonth() + count);

    return next;
}

function startOfMonth(date) {
    return new Date(date.getFullYear(), date.getMonth(), 1);
}

function isBeforeDay(a, b) {
    return a.getTime() < b.getTime();
}

function isAfterDay(a, b) {
    return a.getTime() > b.getTime();
}

function isBetweenDay(date, start, end) {
    return ! isBeforeDay(date, start) && ! isAfterDay(date, end);
}

/** Jumlah hari inklusif — tanggal mulai & selesai ikut dihitung (sama seperti MuthowifBooking::inclusiveSpanDays). */
function inclusiveDaysBetween(start, end) {
    if (! start || ! end) {
        return 0;
    }

    const [from, to] = isBeforeDay(start, end) ? [start, end] : [end, start];

    return Math.round((to.getTime() - from.getTime()) / MS_PER_DAY) + 1;
}

function formatDisplayDate(date, locale) {
    return new Intl.DateTimeFormat(locale === 'id' ? 'id-ID' : 'en-GB', {
        day: 'numeric',
        month: 'short',
        year: 'numeric',
    }).format(date);
}

function formatMonthYear(date, locale) {
    return new Intl.DateTimeFormat(locale === 'id' ? 'id-ID' : 'en-GB', {
        month: 'long',
        year: 'numeric',
    }).format(date);
}

function buildMonthGrid(year, month, weekStartsOn, minDate) {
    const firstOfMonth = new Date(year, month, 1);
    const startWeekday = firstOfMonth.getDay();
    const leading = (startWeekday - weekStartsOn + 7) % 7;
    const daysInMonth = new Date(year, month + 1, 0).getDate();
    const cells = [];

    for (let i = 0; i < leading; i += 1) {
        const date = new Date(year, month, -leading + i + 1);
        date.setHours(0, 0, 0, 0);
        cells.push({
            date,
            inMonth: false,
            disabled: minDate ? isBeforeDay(date, minDate) : false,
        });
    }

    for (let day = 1; day <= daysInMonth; day += 1) {
        const date = new Date(year, month, day);
        date.setHours(0, 0, 0, 0);
        cells.push({
            date,
            inMonth: true,
            disabled: minDate ? isBeforeDay(date, minDate) : false,
        });
    }

    while (cells.length % 7 !== 0) {
        const last = cells[cells.length - 1].date;
        const date = new Date(last);
        date.setDate(date.getDate() + 1);
        date.setHours(0, 0, 0, 0);
        cells.push({
            date,
            inMonth: false,
            disabled: minDate ? isBeforeDay(date, minDate) : false,
        });
    }

    const weeks = [];
    for (let i = 0; i < cells.length; i += 7) {
        weeks.push(cells.slice(i, i + 7));
    }

    return weeks;
}

function travelDateRangePickerFactory(config) {
    return {
        locale: config.locale ?? 'id',
        weekStartsOn: config.locale === 'id' ? 1 : 0,
        weekdayLabels: config.weekdayLabels ?? [],
        labels: config.labels ?? {},
        minDate: parseIso(config.min) ?? new Date(new Date().setHours(0, 0, 0, 0)),

        open: false,
        isMobile: false,
        viewMonth: startOfMonth(new Date()),
        hoverDate: null,
        focusDate: null,

        committedStart: parseIso(config.start),
        committedEnd: parseIso(config.end),
        draftStart: null,
        draftEnd: null,

        resizeHandler: null,

        get displayValue() {
            const start = this.committedStart;
            const end = this.committedEnd ?? this.committedStart;

            if (! start) {
                return '';
            }

            if (! end || sameDay(start, end)) {
                return formatDisplayDate(start, this.locale);
            }

            return `${formatDisplayDate(start, this.locale)} - ${formatDisplayDate(end, this.locale)}`;
        },

        get monthPanels() {
            const count = this.isMobile ? 1 : 2;
            const panels = [];

            for (let offset = 0; offset < count; offset += 1) {
                const monthDate = addMonths(this.viewMonth, offset);
                panels.push({
                    key: `${monthDate.getFullYear()}-${monthDate.getMonth()}`,
                    label: formatMonthYear(monthDate, this.locale),
                    weeks: buildMonthGrid(
                        monthDate.getFullYear(),
                        monthDate.getMonth(),
                        this.weekStartsOn,
                        this.minDate,
                    ),
                });
            }

            return panels;
        },

        get summaryDeparture() {
            return this.draftStart ? formatDisplayDate(this.draftStart, this.locale) : '—';
        },

        get summaryReturn() {
            return this.draftEnd ? formatDisplayDate(this.draftEnd, this.locale) : '—';
        },

        get summaryDuration() {
            if (! this.draftStart || ! this.draftEnd) {
                return '—';
            }

            const count = inclusiveDaysBetween(this.draftStart, this.draftEnd);

            return this.labels.durationDays?.replace(':count', String(count)) ?? `${count}`;
        },

        get canApply() {
            return !! this.draftStart;
        },

        init() {
            if (this.committedStart && ! this.committedEnd) {
                this.committedEnd = this.committedStart;
            }

            this.syncHiddenInputs();
            this.updateViewport();
            this.resizeHandler = () => this.updateViewport();
            window.addEventListener('resize', this.resizeHandler);

            if (typeof this.$cleanup === 'function') {
                this.$cleanup(() => {
                    window.removeEventListener('resize', this.resizeHandler);
                    document.body.classList.remove('overflow-hidden');
                });
            }
        },

        updateViewport() {
            this.isMobile = window.matchMedia('(max-width: 639px)').matches;
        },

        openModal() {
            this.draftStart = this.committedStart ? new Date(this.committedStart) : null;
            this.draftEnd = this.committedEnd ? new Date(this.committedEnd) : null;
            this.hoverDate = null;
            this.focusDate = this.draftStart ? new Date(this.draftStart) : new Date(this.minDate);
            this.viewMonth = startOfMonth(this.focusDate);
            this.open = true;
            document.body.classList.add('overflow-hidden');

            this.$nextTick(() => {
                this.$refs.modal?.focus();
            });
        },

        closeModal(revert = true) {
            if (revert) {
                this.draftStart = this.committedStart ? new Date(this.committedStart) : null;
                this.draftEnd = this.committedEnd ? new Date(this.committedEnd) : null;
            }

            this.open = false;
            this.hoverDate = null;
            document.body.classList.remove('overflow-hidden');
            this.$refs.trigger?.focus();
        },

        applyDates() {
            if (! this.draftStart) {
                return;
            }

            this.committedStart = new Date(this.draftStart);
            this.committedEnd = this.draftEnd ? new Date(this.draftEnd) : new Date(this.draftStart);
            this.syncHiddenInputs();
            this.closeModal(false);
        },

        clearSelection() {
            this.draftStart = null;
            this.draftEnd = null;
            this.hoverDate = null;
            this.focusDate = new Date(this.minDate);
        },

        clearAll() {
            this.clearSelection();
            this.committedStart = null;
            this.committedEnd = null;
            this.syncHiddenInputs();
            this.closeModal(false);
        },

        syncHiddenInputs() {
            const start = toIso(this.committedStart);
            const end = toIso(this.committedEnd ?? this.committedStart);

            if (this.$refs.startInput) {
                this.$refs.startInput.value = start;
            }
            if (this.$refs.endInput) {
                this.$refs.endInput.value = end;
            }
        },

        prevMonth() {
            this.viewMonth = addMonths(this.viewMonth, -1);
        },

        nextMonth() {
            this.viewMonth = addMonths(this.viewMonth, 1);
        },

        selectDate(date) {
            if (isBeforeDay(date, this.minDate)) {
                return;
            }

            if (! this.draftStart || (this.draftStart && this.draftEnd)) {
                this.draftStart = new Date(date);
                this.draftEnd = null;
            } else if (isBeforeDay(date, this.draftStart)) {
                this.draftStart = new Date(date);
                this.draftEnd = null;
            } else {
                this.draftEnd = new Date(date);
            }

            this.focusDate = new Date(date);
        },

        dayClasses(cell) {
            const date = cell.date;
            const start = this.draftStart;
            const end = this.draftEnd;
            const hover = this.hoverDate;
            const previewEnd = start && ! end && hover && ! isBeforeDay(hover, start) ? hover : null;
            const rangeEnd = end ?? previewEnd;
            const inRange = start && rangeEnd && isBetweenDay(date, start, rangeEnd) && ! sameDay(date, start) && ! sameDay(date, rangeEnd);
            const isStart = start && sameDay(date, start);
            const isEnd = rangeEnd && sameDay(date, rangeEnd);
            const isToday = sameDay(date, new Date(new Date().setHours(0, 0, 0, 0)));
            const isWeekend = date.getDay() === 0 || date.getDay() === 6;

            const classes = [
                'relative flex h-10 w-full items-center justify-center text-sm font-medium transition-all duration-200 sm:h-11',
                'focus:outline-none focus-visible:ring-2 focus-visible:ring-emerald-500 focus-visible:ring-offset-1',
            ];

            if (cell.disabled) {
                classes.push('cursor-not-allowed rounded-full text-gray-300');
            } else if (! cell.inMonth) {
                classes.push('rounded-full text-gray-300 hover:bg-emerald-50/60');
            } else if (isStart || isEnd) {
                classes.push('z-10 bg-emerald-600 text-white shadow-sm shadow-emerald-600/30');
                if (isStart && rangeEnd && ! sameDay(date, start)) {
                    classes.push('rounded-l-full rounded-r-md');
                } else if (isEnd && start && ! sameDay(date, rangeEnd)) {
                    classes.push('rounded-r-full rounded-l-md');
                } else {
                    classes.push('rounded-full');
                }
            } else if (inRange) {
                classes.push('rounded-none bg-emerald-50 text-emerald-900');
            } else if (isToday) {
                classes.push('rounded-full');
                classes.push('font-semibold text-emerald-700 ring-1 ring-emerald-200');
            } else if (isWeekend) {
                classes.push('rounded-full text-gray-500 hover:bg-emerald-50/80 hover:text-emerald-800');
            } else {
                classes.push('rounded-full text-gray-800 hover:bg-emerald-50/80 hover:text-emerald-800');
            }

            if (sameDay(date, this.focusDate)) {
                classes.push('ring-2 ring-emerald-400 ring-offset-1');
            }

            return classes.join(' ');
        },

        dayAriaLabel(cell) {
            const formatted = formatDisplayDate(cell.date, this.locale);

            if (cell.disabled) {
                return `${formatted} (${this.labels.unavailable ?? 'Unavailable'})`;
            }

            return formatted;
        },

        onDayHover(date) {
            if (! this.draftStart || this.draftEnd) {
                return;
            }

            this.hoverDate = new Date(date);
        },

        onDayLeave() {
            this.hoverDate = null;
        },

        onKeydown(event) {
            if (! this.open) {
                return;
            }

            if (event.key === 'Escape') {
                event.preventDefault();
                this.closeModal(true);

                return;
            }

            if (! this.focusDate) {
                this.focusDate = new Date(this.minDate);
            }

            const next = new Date(this.focusDate);

            if (event.key === 'ArrowLeft') {
                event.preventDefault();
                next.setDate(next.getDate() - 1);
            } else if (event.key === 'ArrowRight') {
                event.preventDefault();
                next.setDate(next.getDate() + 1);
            } else if (event.key === 'ArrowUp') {
                event.preventDefault();
                next.setDate(next.getDate() - 7);
            } else if (event.key === 'ArrowDown') {
                event.preventDefault();
                next.setDate(next.getDate() + 7);
            } else if (event.key === 'Enter' || event.key === ' ') {
                event.preventDefault();
                this.selectDate(this.focusDate);

                return;
            } else {
                return;
            }

            if (! isBeforeDay(next, this.minDate)) {
                this.focusDate = next;
                this.viewMonth = startOfMonth(next);
            }
        },

    };
}

export function registerDateRangePicker(Alpine) {
    Alpine.data('travelDateRangePicker', travelDateRangePickerFactory);
    Alpine.data('dateRangeSearch', travelDateRangePickerFactory);
}
