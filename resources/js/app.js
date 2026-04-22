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

    Alpine.data('globalChatPanel', (config) => ({
        listUrl: config.listUrl,
        userId: config.userId,
        locale: config.locale,
        labels: config.labels,
        
        isPanelExpanded: false,
        view: 'list', // 'list' | 'chat'
        hasUnread: false,
        
        conversations: [],
        loadingList: false,
        
        activeConversation: null,
        messages: [],
        body: '',
        imageFile: null,
        imagePreviewUrl: null,
        loadingChat: false,
        sending: false,
        error: '',
        
        pollTimerList: null,

        get headerTitle() {
            if (this.view === 'chat' && this.activeConversation) {
                return this.activeConversation.other_name;
            }
            return this.labels.title;
        },
        
        get headerSubtitle() {
            if (this.view === 'chat' && this.activeConversation) {
                return `${this.activeConversation.booking_code} • ${this.activeConversation.service_type}`;
            }
            return '';
        },

        togglePanel() {
            this.isPanelExpanded = !this.isPanelExpanded;
            if (this.isPanelExpanded) {
                this.hasUnread = false;
                if (this.view === 'chat') {
                    this.scrollToLatest({ force: true });
                } else {
                    this.loadList();
                }
            }
        },
        
        openChat(conv) {
            this.activeConversation = conv;
            this.view = 'chat';
            this.messages = [];
            this.error = '';
            this.body = '';
            this.clearImage();
            this.loadChatMessages();
            this.subscribeToBooking(conv.id);
        },
        
        closeChat() {
            if (this.activeConversation) {
                this.unsubscribeFromBooking(this.activeConversation.id);
            }
            this.activeConversation = null;
            this.view = 'list';
            this.loadList();
        },

        async loadList() {
            this.loadingList = true;
            try {
                const r = await fetch(this.listUrl, { headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' }});
                if (r.ok) {
                    const data = await r.json();
                    this.conversations = data.conversations || [];
                }
            } catch (e) {
                console.error(e);
            } finally {
                this.loadingList = false;
            }
        },

        pickImage(event) {
            const input = event.target;
            const file = input.files && input.files[0] ? input.files[0] : null;
            if (this.imagePreviewUrl) URL.revokeObjectURL(this.imagePreviewUrl);
            this.imageFile = file;
            this.imagePreviewUrl = file ? URL.createObjectURL(file) : null;
            input.value = '';
        },
        
        clearImage() {
            this.imageFile = null;
            if (this.imagePreviewUrl) {
                URL.revokeObjectURL(this.imagePreviewUrl);
                this.imagePreviewUrl = null;
            }
            if (this.$refs.chatImageInput) this.$refs.chatImageInput.value = '';
        },

        formatTimeShort(iso) {
            if (!iso) return '';
            const d = new Date(iso);
            if (Number.isNaN(d.getTime())) return '';
            return d.toLocaleTimeString(this.locale, { hour: '2-digit', minute: '2-digit' });
        },

        csrf() {
            return document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') ?? '';
        },
        
        scrollToLatest(options = {}) {
            const force = options.force === true;
            const el = this.$refs.chatScroll;
            if (!el || this.messages.length === 0) return;
            const threshold = 150;
            const atBottom = el.scrollHeight - el.scrollTop - el.clientHeight <= threshold;
            if (!force && !atBottom) return;
            this.$nextTick(() => requestAnimationFrame(() => requestAnimationFrame(() => {
                if (this.$refs.chatScroll) this.$refs.chatScroll.scrollTop = this.$refs.chatScroll.scrollHeight;
            })));
        },

        async loadChatMessages(isBackground = false) {
            if (!this.activeConversation) return;
            if (!isBackground) this.loadingChat = true;
            this.error = '';
            const prevLastId = this.messages.length ? this.messages[this.messages.length - 1].id : null;
            
            try {
                const r = await fetch(this.activeConversation.fetchUrl, {
                    headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
                    credentials: 'same-origin',
                });
                if (!r.ok) throw new Error(this.labels.loadError);
                const data = await r.json();
                this.messages = data.messages ?? [];
                
                // Update open state just in case
                if (data.chat_open !== undefined) this.activeConversation.is_open = !!data.chat_open;
                
                const nextLastId = this.messages.length ? this.messages[this.messages.length - 1].id : null;
                const hasNewMessage = nextLastId !== prevLastId && this.messages.length > 0;
                
                if (hasNewMessage && prevLastId !== null && (!this.isPanelExpanded || this.view !== 'chat')) {
                    this.hasUnread = true;
                }
                if (this.isPanelExpanded && this.view === 'chat') {
                    this.scrollToLatest({ force: hasNewMessage });
                }
            } catch (e) {
                this.error = e instanceof Error ? e.message : this.labels.loadError;
            } finally {
                this.loadingChat = false;
            }
        },

        async send() {
            const text = this.body.trim();
            const hasImage = !!this.imageFile;
            if ((!text && !hasImage) || !this.activeConversation?.is_open || this.sending) return;
            
            this.sending = true;
            this.error = '';
            try {
                let r;
                if (hasImage) {
                    const fd = new FormData();
                    if (text) fd.append('body', text);
                    fd.append('image', this.imageFile);
                    r = await fetch(this.activeConversation.storeUrl, {
                        method: 'POST',
                        headers: { 'Accept': 'application/json', 'X-CSRF-TOKEN': this.csrf(), 'X-Requested-With': 'XMLHttpRequest' },
                        credentials: 'same-origin',
                        body: fd,
                    });
                } else {
                    r = await fetch(this.activeConversation.storeUrl, {
                        method: 'POST',
                        headers: { 'Accept': 'application/json', 'Content-Type': 'application/json', 'X-CSRF-TOKEN': this.csrf(), 'X-Requested-With': 'XMLHttpRequest' },
                        credentials: 'same-origin',
                        body: JSON.stringify({ body: text }),
                    });
                }
                const data = await r.json().catch(() => ({}));
                if (!r.ok) {
                    const msg = data.message ?? data.errors?.image?.[0] ?? data.errors?.body?.[0] ?? this.labels.sendError;
                    throw new Error(typeof msg === 'string' ? msg : this.labels.sendError);
                }
                
                this.body = '';
                this.clearImage();
                if (data.chat_open !== undefined) this.activeConversation.is_open = !!data.chat_open;
                await this.loadChatMessages(true);
            } catch (e) {
                this.error = e instanceof Error ? e.message : this.labels.sendError;
            } finally {
                this.sending = false;
            }
        },
        
        subscribeToBooking(bookingId) {
            if (window.Echo) {
                window.Echo.private(`booking.chat.${bookingId}`)
                    .listen('BookingChatUpdated', (e) => {
                        this.loadChatMessages(true);
                    });
            }
        },
        
        unsubscribeFromBooking(bookingId) {
            if (window.Echo) {
                window.Echo.leave(`booking.chat.${bookingId}`);
            }
        },

        init() {
            this.loadList();
            this.pollTimerList = window.setInterval(() => {
                if (!this.isPanelExpanded || this.view === 'list') {
                    this.loadList();
                } else if (this.view === 'chat' && !window.Echo) {
                    this.loadChatMessages(true);
                }
            }, 6000);
        },
        
        destroy() {
            if (this.pollTimerList) window.clearInterval(this.pollTimerList);
            if (this.activeConversation) this.unsubscribeFromBooking(this.activeConversation.id);
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
