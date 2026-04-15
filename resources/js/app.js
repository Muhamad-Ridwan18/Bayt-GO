import './bootstrap';

import Alpine from 'alpinejs';

document.addEventListener('alpine:init', () => {
    Alpine.data('indonesianDigitsInput', (initialDigits = '') => ({
        raw: String(initialDigits ?? '').replace(/\D/g, ''),
        formatDigits(d) {
            if (!d) {
                return '';
            }
            return d.replace(/\B(?=(\d{3})+(?!\d))/g, '.');
        },
        onInput(e) {
            const el = e.target;
            this.raw = el.value.replace(/\D/g, '');
            el.value = this.formatDigits(this.raw);
        },
    }));

    Alpine.data('muthowifPrivatePelayananForm', (addonRows) => ({
        rows: addonRows,
        formatDigits(d) {
            if (!d) {
                return '';
            }
            const digits = String(d).replace(/\D/g, '');
            return digits.replace(/\B(?=(\d{3})+(?!\d))/g, '.');
        },
        onAddonPriceInput(e, row) {
            const raw = e.target.value.replace(/\D/g, '');
            row.price = raw;
            e.target.value = this.formatDigits(raw);
        },
    }));

    Alpine.data('bookingChatPanel', (config) => ({
        messages: [],
        body: '',
        imageFile: null,
        imagePreviewUrl: null,
        chatOpen: config.initialOpen,
        loading: false,
        sending: false,
        error: '',
        fetchUrl: config.fetchUrl,
        storeUrl: config.storeUrl,
        locale: config.locale,
        introOpen: config.introOpen,
        introClosed: config.introClosed,
        labels: config.labels,
        pollTimer: null,
        pickImage(event) {
            const input = event.target;
            const file = input.files && input.files[0] ? input.files[0] : null;
            if (this.imagePreviewUrl) {
                URL.revokeObjectURL(this.imagePreviewUrl);
                this.imagePreviewUrl = null;
            }
            this.imageFile = file;
            this.imagePreviewUrl = file ? URL.createObjectURL(file) : null;
        },
        clearImage() {
            this.imageFile = null;
            if (this.imagePreviewUrl) {
                URL.revokeObjectURL(this.imagePreviewUrl);
                this.imagePreviewUrl = null;
            }
            const input = this.$refs.chatImageInput;
            if (input) {
                input.value = '';
            }
        },
        formatTime(iso) {
            if (!iso) {
                return '';
            }
            const d = new Date(iso);
            if (Number.isNaN(d.getTime())) {
                return '';
            }
            return d.toLocaleString(this.locale, {
                day: '2-digit',
                month: 'short',
                hour: '2-digit',
                minute: '2-digit',
            });
        },
        csrf() {
            return document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') ?? '';
        },
        scrollToLatest(options = {}) {
            const force = options.force === true;
            const el = this.$refs.chatScroll;
            if (!el || this.messages.length === 0) {
                return;
            }
            const threshold = 120;
            const atBottom = el.scrollHeight - el.scrollTop - el.clientHeight <= threshold;
            if (!force && !atBottom) {
                return;
            }
            this.$nextTick(() => {
                requestAnimationFrame(() => {
                    requestAnimationFrame(() => {
                        const box = this.$refs.chatScroll;
                        if (box) {
                            box.scrollTop = box.scrollHeight;
                        }
                    });
                });
            });
        },
        async load() {
            if (this.loading) {
                return;
            }
            this.loading = true;
            this.error = '';
            const prevLastId = this.messages.length ? this.messages[this.messages.length - 1].id : null;
            try {
                const r = await fetch(this.fetchUrl, {
                    headers: {
                        Accept: 'application/json',
                        'X-Requested-With': 'XMLHttpRequest',
                    },
                    credentials: 'same-origin',
                });
                if (!r.ok) {
                    throw new Error(this.labels.loadError);
                }
                const data = await r.json();
                this.messages = data.messages ?? [];
                this.chatOpen = !!data.chat_open;
                const nextLastId = this.messages.length ? this.messages[this.messages.length - 1].id : null;
                const hasNewMessage = nextLastId !== prevLastId && this.messages.length > 0;
                this.scrollToLatest({ force: hasNewMessage });
            } catch (e) {
                this.error = e instanceof Error ? e.message : this.labels.loadError;
            } finally {
                this.loading = false;
            }
        },
        async send() {
            const text = this.body.trim();
            const hasImage = !!this.imageFile;
            if ((!text && !hasImage) || !this.chatOpen || this.sending) {
                return;
            }
            this.sending = true;
            this.error = '';
            try {
                let r;
                if (hasImage) {
                    const fd = new FormData();
                    if (text) {
                        fd.append('body', text);
                    }
                    fd.append('image', this.imageFile);
                    r = await fetch(this.storeUrl, {
                        method: 'POST',
                        headers: {
                            Accept: 'application/json',
                            'X-CSRF-TOKEN': this.csrf(),
                            'X-Requested-With': 'XMLHttpRequest',
                        },
                        credentials: 'same-origin',
                        body: fd,
                    });
                } else {
                    r = await fetch(this.storeUrl, {
                        method: 'POST',
                        headers: {
                            Accept: 'application/json',
                            'Content-Type': 'application/json',
                            'X-CSRF-TOKEN': this.csrf(),
                            'X-Requested-With': 'XMLHttpRequest',
                        },
                        credentials: 'same-origin',
                        body: JSON.stringify({ body: text }),
                    });
                }
                const data = await r.json().catch(() => ({}));
                if (!r.ok) {
                    const msg =
                        data.message ??
                        data.errors?.image?.[0] ??
                        data.errors?.body?.[0] ??
                        this.labels.sendError;
                    throw new Error(typeof msg === 'string' ? msg : this.labels.sendError);
                }
                this.body = '';
                this.clearImage();
                this.chatOpen = !!data.chat_open;
                await this.load();
            } catch (e) {
                this.error = e instanceof Error ? e.message : this.labels.sendError;
            } finally {
                this.sending = false;
            }
        },
        init() {
            this.load();
            this.pollTimer = window.setInterval(() => this.load(), 6000);
        },
        destroy() {
            if (this.pollTimer) {
                window.clearInterval(this.pollTimer);
            }
        },
    }));

    Alpine.data('muthowifDashboardCalendar', (config) => ({
        url: config.url,
        dashboardUrl: config.dashboardUrl,
        month: config.month,
        loading: false,
        syncUrl(data) {
            const u = new URL(window.location.href);
            if (data.is_current_month) {
                u.searchParams.delete('month');
            } else {
                u.searchParams.set('month', data.month);
            }
            history.replaceState({}, '', u.pathname + u.search + u.hash);
        },
        async loadMonth(targetMonth) {
            if (this.loading) {
                return;
            }
            this.loading = true;
            const url = new URL(this.url, window.location.origin);
            if (targetMonth) {
                url.searchParams.set('month', targetMonth);
            } else {
                url.searchParams.delete('month');
            }
            try {
                const res = await fetch(url.toString(), {
                    credentials: 'same-origin',
                    headers: {
                        Accept: 'application/json',
                        'X-Requested-With': 'XMLHttpRequest',
                    },
                });
                if (!res.ok) {
                    throw new Error('calendar');
                }
                const data = await res.json();
                const cal = document.getElementById('muthowif-schedule-calendar');
                const blk = document.getElementById('muthowif-schedule-blocked');
                if (cal) {
                    cal.outerHTML = data.calendar;
                }
                if (blk) {
                    blk.outerHTML = data.blocked;
                }
                this.month = data.month;
                this.syncUrl(data);
            } catch {
                const fallback = new URL(this.dashboardUrl, window.location.origin);
                if (targetMonth) {
                    fallback.searchParams.set('month', targetMonth);
                }
                window.location.assign(fallback.toString());
            } finally {
                this.loading = false;
            }
        },
        onCalendarNavClick(e) {
            if (e.target.closest('a[data-cal-today]')) {
                e.preventDefault();
                this.loadMonth(null);
                return;
            }
            const nav = e.target.closest('a[data-cal-month]');
            if (nav?.dataset.calMonth) {
                e.preventDefault();
                this.loadMonth(nav.dataset.calMonth);
            }
        },
    }));
});

window.Alpine = Alpine;

Alpine.start();
