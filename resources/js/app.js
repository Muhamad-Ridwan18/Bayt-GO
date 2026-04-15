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
        async load() {
            if (this.loading) {
                return;
            }
            this.loading = true;
            this.error = '';
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
            } catch (e) {
                this.error = e instanceof Error ? e.message : this.labels.loadError;
            } finally {
                this.loading = false;
            }
        },
        async send() {
            const text = this.body.trim();
            if (!text || !this.chatOpen || this.sending) {
                return;
            }
            this.sending = true;
            this.error = '';
            try {
                const r = await fetch(this.storeUrl, {
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
                const data = await r.json().catch(() => ({}));
                if (!r.ok) {
                    const msg = data.message ?? data.errors?.body?.[0] ?? this.labels.sendError;
                    throw new Error(typeof msg === 'string' ? msg : this.labels.sendError);
                }
                this.body = '';
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
});

window.Alpine = Alpine;

Alpine.start();
