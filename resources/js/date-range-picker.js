import flatpickr from 'flatpickr';
import { Indonesian } from 'flatpickr/dist/l10n/id.js';

function formatIso(date) {
    return flatpickr.formatDate(date, 'Y-m-d');
}

function isMobileViewport() {
    return window.matchMedia('(max-width: 639px)').matches;
}

function monthCount() {
    return isMobileViewport() ? 1 : 2;
}

function isSameDay(a, b) {
    return (
        a.getFullYear() === b.getFullYear()
        && a.getMonth() === b.getMonth()
        && a.getDate() === b.getDate()
    );
}

function positionModal(fp) {
    const cal = fp.calendarContainer;
    if (! cal) {
        return;
    }

    const mobile = isMobileViewport();

    cal.classList.toggle('date-range-picker-modal--mobile', mobile);
    cal.classList.toggle('date-range-picker-modal--desktop', ! mobile);

    Object.assign(cal.style, {
        position: 'fixed',
        zIndex: '9999',
        margin: '0',
        maxHeight: mobile ? 'min(88vh, 640px)' : 'min(90vh, 720px)',
        overflowY: 'auto',
    });

    if (mobile) {
        Object.assign(cal.style, {
            top: 'auto',
            left: '0',
            right: '0',
            bottom: '0',
            transform: 'none',
            width: '100%',
            maxWidth: '100%',
        });
    } else {
        const width = Math.min(window.innerWidth * 0.92, 704);

        Object.assign(cal.style, {
            top: '50%',
            left: '50%',
            right: 'auto',
            bottom: 'auto',
            transform: 'translate(-50%, -50%)',
            width: `${width}px`,
            maxWidth: '44rem',
        });
    }
}

function decorateCalendar(fp, pickerConfig) {
    const cal = fp.calendarContainer;
    if (cal.querySelector('.date-range-picker__header')) {
        return;
    }

    cal.classList.add('date-range-picker-modal');

    const header = document.createElement('div');
    header.className = 'date-range-picker__header';
    header.innerHTML = `
        <p class="date-range-picker__title">${pickerConfig.title}</p>
        <button type="button" class="date-range-picker__close" aria-label="${pickerConfig.closeLabel}">
            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true">
                <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" />
            </svg>
        </button>
    `;
    cal.insertBefore(header, cal.firstChild);
    header.querySelector('.date-range-picker__close')?.addEventListener('click', () => fp.close());

    const footer = document.createElement('div');
    footer.className = 'date-range-picker__footer';
    footer.textContent = pickerConfig.footerHint;
    cal.appendChild(footer);
}

export function registerDateRangePicker(Alpine) {
    Alpine.data('dateRangeSearch', (pickerConfig) => ({
        picker: null,
        resizeHandler: null,
        backdrop: null,

        init() {
            const locale = pickerConfig.locale === 'id' ? Indonesian : undefined;
            const defaultDate = [];

            if (pickerConfig.start) {
                defaultDate.push(pickerConfig.start);
            }
            if (pickerConfig.end && pickerConfig.end !== pickerConfig.start) {
                defaultDate.push(pickerConfig.end);
            }

            const localeConfig = locale
                ? { ...locale, rangeSeparator: ' — ' }
                : { rangeSeparator: ' — ' };

            const today = new Date();
            today.setHours(0, 0, 0, 0);

            this.picker = flatpickr(this.$refs.display, {
                mode: 'range',
                showMonths: monthCount(),
                minDate: pickerConfig.min || 'today',
                dateFormat: pickerConfig.displayFormat || 'j M Y',
                locale: localeConfig,
                defaultDate,
                disableMobile: true,
                animate: false,
                appendTo: document.body,
                monthSelectorType: 'static',
                position: (fp) => {
                    positionModal(fp);
                },
                onReady: (_, __, fp) => {
                    decorateCalendar(fp, pickerConfig);
                },
                onOpen: (_, __, fp) => {
                    decorateCalendar(fp, pickerConfig);
                    positionModal(fp);
                    this.openBackdrop();
                },
                onClose: () => {
                    this.closeBackdrop();
                },
                onChange: (selectedDates) => {
                    this.syncHiddenInputs(selectedDates);
                },
                onDayCreate: (dObj, _dStr, _fp, dayElem) => {
                    if (! dObj) {
                        return;
                    }

                    const date = dayElem.dateObj;
                    if (date.getDay() === 0) {
                        dayElem.classList.add('is-sunday');
                    }

                    if (isSameDay(date, today)) {
                        const badge = document.createElement('span');
                        badge.className = 'date-range-picker__today';
                        badge.textContent = pickerConfig.todayLabel;
                        dayElem.appendChild(badge);
                    }
                },
            });

            this.syncHiddenInputs(this.picker.selectedDates);

            this.resizeHandler = () => {
                const next = monthCount();
                if (this.picker.config.showMonths !== next) {
                    this.picker.set('showMonths', next);
                }
                if (this.picker.isOpen) {
                    positionModal(this.picker);
                }
            };

            window.addEventListener('resize', this.resizeHandler);
        },

        openBackdrop() {
            if (this.backdrop) {
                return;
            }

            this.backdrop = document.createElement('button');
            this.backdrop.type = 'button';
            this.backdrop.className = 'date-range-picker-backdrop';
            this.backdrop.setAttribute('aria-label', pickerConfig.closeLabel);
            this.backdrop.addEventListener('click', () => this.picker?.close());
            document.body.appendChild(this.backdrop);
            document.body.classList.add('date-range-picker-open');
        },

        closeBackdrop() {
            this.backdrop?.remove();
            this.backdrop = null;
            document.body.classList.remove('date-range-picker-open');
        },

        syncHiddenInputs(selectedDates) {
            const start = selectedDates[0] ? formatIso(selectedDates[0]) : '';
            const end = selectedDates[1] ? formatIso(selectedDates[1]) : '';

            if (this.$refs.startInput) {
                this.$refs.startInput.value = start;
            }
            if (this.$refs.endInput) {
                this.$refs.endInput.value = end;
            }
        },

        destroy() {
            if (this.resizeHandler) {
                window.removeEventListener('resize', this.resizeHandler);
            }
            this.closeBackdrop();
            this.picker?.destroy();
        },
    }));
}
