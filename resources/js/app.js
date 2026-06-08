import './bootstrap';

import { initFormSubmitLock } from './form-submit-lock';
import { registerDateRangePicker } from './date-range-picker';
import { registerWhatsappBroadcastAdmin } from './whatsapp-broadcast-admin';
import Alpine from 'alpinejs';

registerDateRangePicker(Alpine);

initFormSubmitLock();
import {
    appendChatMessages,
    chatMessagesUrl,
    csrfToken,
    debounce,
    deferRealtimeInit,
    ensureEcho,
    fetchHtmlFragment,
    fetchJson,
    leavePrivateChannels,
    markChatRead,
    payloadMatches,
    subscribePrivateListeners,
    swapAlpineHtml,
    swapLiveParts,
} from './reverb-live';

document.addEventListener('alpine:init', () => {
    registerWhatsappBroadcastAdmin(Alpine);

    Alpine.store('toasts', {
        items: [],
        nextId: 1,

        add(type, message, duration = 5500) {
            const text = String(message ?? '').trim();
            if (text === '') {
                return null;
            }

            const id = this.nextId++;
            this.items.push({
                id,
                type: type ?? 'info',
                message: text,
                visible: true,
            });

            if (duration > 0) {
                window.setTimeout(() => this.dismiss(id), duration);
            }

            return id;
        },

        dismiss(id) {
            const item = this.items.find((t) => t.id === id);
            if (!item) {
                return;
            }

            item.visible = false;
            window.setTimeout(() => {
                this.items = this.items.filter((t) => t.id !== id);
            }, 220);
        },
    });

    window.showAppToast = (type, message, duration = 5500) => Alpine.store('toasts').add(type, message, duration);

    window.addEventListener('app:toast', (event) => {
        const detail = event?.detail ?? {};
        Alpine.store('toasts').add(detail.type ?? 'info', detail.message ?? '', detail.duration ?? 5500);
    });

    Alpine.data('reverbFragmentLive', (config) => ({
        fragmentUrl: config.fragmentUrl ?? null,
        listeners: config.listeners ?? [],
        appendQuery: config.appendQuery ?? false,
        subscribedChannels: [],
        refreshing: false,
        debouncedRefresh: null,

        init() {
            this.debouncedRefresh = debounce(() => void this.refreshFragment(), 450);

            deferRealtimeInit('reverbFragmentLive', () => {
                this.subscribedChannels = subscribePrivateListeners(this.listeners, () => {
                    this.debouncedRefresh();
                });
            });
        },

        async refreshFragment() {
            if (!this.fragmentUrl || this.refreshing) {
                return;
            }

            this.refreshing = true;
            try {
                let url = this.fragmentUrl;
                if (this.appendQuery) {
                    url += window.location.search || '';
                }

                const html = await fetchHtmlFragment(url);
                if (html === null) {
                    return;
                }

                swapAlpineHtml(this.$refs.liveRoot, html);
            } catch (err) {
                console.error(err);
            } finally {
                this.refreshing = false;
            }
        },

        destroy() {
            leavePrivateChannels(this.subscribedChannels);
        },
    }));

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

    Alpine.data('profileForm', () => ({
        loading: false,
        errors: {},
        errorMessage: '',

        csrf() {
            return document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') ?? '';
        },

        async submit(e) {
            e.preventDefault();
            if (this.loading) return;
            this.loading = true;
            this.errors = {};
            this.errorMessage = '';

            const form = e.target;
            const formData = new FormData(form);

            try {
                const response = await fetch(form.action, {
                    method: 'POST',
                    body: formData,
                    headers: {
                        'Accept': 'application/json',
                        'X-Requested-With': 'XMLHttpRequest',
                        'X-CSRF-TOKEN': this.csrf(),
                    },
                });

                if (response.status === 422) {
                    const data = await response.json();
                    this.errors = data.errors || {};
                    this.errorMessage = data.message || 'Silakan periksa kembali input Anda.';
                    
                    // Focus ke input pertama yang error
                    const firstErrorField = Object.keys(this.errors)[0];
                    if (firstErrorField) {
                        const el = form.querySelector(`[name="${firstErrorField}"], [name="${firstErrorField}[]"]`);
                        if (el) el.focus();
                    }
                    return;
                }

                if (!response.ok) {
                    throw new Error('Terjadi kesalahan saat menyimpan profil.');
                }

                const html = await response.text();
                const parser = new DOMParser();
                const doc = parser.parseFromString(html, 'text/html');

                const currentContainer = document.getElementById('profile-container');
                const newContainer = doc.getElementById('profile-container');

                if (currentContainer && newContainer) {
                    // Tambahkan cache buster ke gambar profil dan KTP yang baru
                    const ts = new Date().getTime();
                    newContainer.querySelectorAll('img').forEach(img => {
                        const src = img.getAttribute('src');
                        if (src && (src.includes('profile/public/photo') || src.includes('profile/public/ktp'))) {
                            img.setAttribute('src', `${src.split('?')[0]}?t=${ts}`);
                        }
                    });

                    if (typeof Alpine.destroyTree === 'function') {
                        Alpine.destroyTree(currentContainer);
                    }
                    currentContainer.innerHTML = newContainer.innerHTML;
                    Alpine.initTree(currentContainer);
                } else {
                    window.location.reload();
                }
            } catch (err) {
                console.error(err);
                this.errorMessage = err.message || 'Terjadi kesalahan sistem.';
            } finally {
                this.loading = false;
            }
        }
    }));


    Alpine.data('adminWithdrawalsLive', (config) => ({
        fragmentUrl: config.fragmentUrl ?? null,
        toastLabel: config.toastLabel ?? 'Ada permintaan withdraw baru!',
        refreshing: false,
        subscribedChannels: [],

        init() {
            deferRealtimeInit('adminWithdrawalsLive', () => {
                const onEvent = (e) => {
                    if (e?.pending_count !== undefined) {
                        this.showToast(e);
                    }
                    void this.refreshFragment();
                };

                window.Echo.private('admin.withdrawals')
                    .listen('.withdrawal.requested', onEvent)
                    .listen('.withdrawal.updated', onEvent);

                this.subscribedChannels = ['admin.withdrawals'];
            });
        },

        showToast(e) {
            const msg = `${this.toastLabel} dari ${e.muthowif_name || 'Muthowif'}`;
            // Optional: you can implement a better toast UI if available in your stack, 
            // but for now we'll use a simple alert or just rely on the badge/table update.
            // A simple console log to prevent obtrusive alerts in production if no toast UI exists:
            console.log(msg);
            
            // If there's a global Alpine store for toasts:
            if (Alpine.store('toasts')) {
                Alpine.store('toasts').add('success', msg);
            } else {
                alert(msg);
            }
        },

        async refreshFragment() {
            if (!this.fragmentUrl || this.refreshing) {
                return;
            }

            this.refreshing = true;
            try {
                const html = await fetchHtmlFragment(this.fragmentUrl + (window.location.search || ''));
                if (html !== null) {
                    swapAlpineHtml(this.$refs.liveRoot, html);
                }
            } catch (err) {
                console.error(err);
            } finally {
                this.refreshing = false;
            }
        },

        destroy() {
            leavePrivateChannels(this.subscribedChannels);
        },
    }));

    Alpine.data('adminEmergencyReportsBadge', (config) => ({
        countUrl: config.countUrl ?? '',
        toastLabel: config.toastLabel ?? '',
        count: Number(config.initialCount ?? 0) || 0,
        subscribedChannels: [],

        get displayLabel() {
            if (this.count > 99) {
                return '99+';
            }
            return String(this.count);
        },

        init() {
            deferRealtimeInit('adminEmergencyReportsBadge', () => {
                window.Echo.private('admin.emergency-reports')
                    .listen('.emergency.report.updated', (e) => {
                        void this.refresh();
                        if (e?.action === 'submitted') {
                            this.notifyNewReport();
                        }
                    });

                this.subscribedChannels = ['admin.emergency-reports'];
            });
        },

        notifyNewReport() {
            const msg = this.toastLabel || 'New emergency incident report';
            if (typeof Alpine !== 'undefined' && Alpine.store('toasts')) {
                Alpine.store('toasts').add('info', msg);
            }
        },

        async refresh() {
            if (!this.countUrl) {
                return;
            }
            try {
                const r = await fetch(this.countUrl, {
                    headers: { Accept: 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
                    credentials: 'same-origin',
                });
                if (!r.ok) {
                    return;
                }
                const data = await r.json();
                if (typeof data.open_count === 'number') {
                    this.count = data.open_count;
                }
            } catch {
                /* ignore */
            }
        },

        destroy() {
            leavePrivateChannels(this.subscribedChannels);
        },
    }));

    Alpine.data('adminWithdrawalsBadgeLive', (initialCount) => ({
        count: Number(initialCount) || 0,
        subscribedChannels: [],

        get displayLabel() {
            if (this.count > 99) {
                return '99+';
            }
            return String(this.count);
        },

        init() {
            deferRealtimeInit('adminWithdrawalsBadgeLive', () => {
                const apply = (e) => {
                    if (typeof e?.pending_count === 'number') {
                        this.count = e.pending_count;
                    } else {
                        this.count++;
                    }
                };

                window.Echo.private('admin.withdrawals')
                    .listen('.withdrawal.requested', apply)
                    .listen('.withdrawal.updated', apply);

                this.subscribedChannels = ['admin.withdrawals'];
            });
        },

        destroy() {
            leavePrivateChannels(this.subscribedChannels);
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

    Alpine.data('muthowifPendingBookingsBadge', (config) => ({
        userId: config.userId != null ? String(config.userId) : '',
        countUrl: config.countUrl ?? '',
        count: Number(config.initialCount ?? 0) || 0,
        subscribedChannels: [],

        get displayLabel() {
            if (this.count > 99) {
                return '99+';
            }
            return String(this.count);
        },

        init() {
            if (!this.userId) {
                return;
            }

            deferRealtimeInit('muthowifPendingBookingsBadge', () => {
                const channel = `App.Models.User.${this.userId}`;
                window.Echo.private(channel)
                    .listen('.booking.updated', () => void this.refresh());

                this.subscribedChannels = [channel];
            });
        },

        async refresh() {
            if (!this.countUrl) {
                return;
            }
            try {
                const r = await fetch(this.countUrl, {
                    headers: { Accept: 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
                    credentials: 'same-origin',
                });
                if (!r.ok) {
                    return;
                }
                const data = await r.json();
                if (typeof data.pending_count === 'number') {
                    this.count = data.pending_count;
                }
            } catch {
                /* ignore */
            }
        },

        destroy() {
            leavePrivateChannels(this.subscribedChannels);
        },
    }));

    Alpine.data('muthowifEmergencyOffersBadge', (config) => ({
        userId: config.userId != null ? String(config.userId) : '',
        countUrl: config.countUrl ?? '',
        toastLabel: config.toastLabel ?? '',
        count: Number(config.initialCount ?? 0) || 0,
        subscribedChannels: [],

        get displayLabel() {
            if (this.count > 99) {
                return '99+';
            }
            return String(this.count);
        },

        init() {
            if (!this.userId) {
                return;
            }

            deferRealtimeInit('muthowifEmergencyOffersBadge', () => {
                const channel = `App.Models.User.${this.userId}`;
                window.Echo.private(channel).listen('.emergency.report.updated', (e) => {
                    void this.refresh();
                    if (e?.action === 'batch_offered' || e?.action === 'admin_invite') {
                        this.notifyNewOffer();
                    }
                });

                this.subscribedChannels = [channel];
            });
        },

        notifyNewOffer() {
            const msg = this.toastLabel || 'New emergency replacement offer';
            if (typeof Alpine !== 'undefined' && Alpine.store('toasts')) {
                Alpine.store('toasts').add('info', msg);
            }
        },

        async refresh() {
            if (!this.countUrl) {
                return;
            }
            try {
                const r = await fetch(this.countUrl, {
                    headers: { Accept: 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
                    credentials: 'same-origin',
                });
                if (!r.ok) {
                    return;
                }
                const data = await r.json();
                if (typeof data.pending_count === 'number') {
                    this.count = data.pending_count;
                }
            } catch {
                /* ignore */
            }
        },

        destroy() {
            leavePrivateChannels(this.subscribedChannels);
        },
    }));

    Alpine.data('globalChatPanel', (config) => ({
        listUrl: config.listUrl,
        userId: config.userId,
        locale: config.locale,
        labels: config.labels,
        
        isPanelExpanded: false,
        view: 'list', // 'list' | 'chat'
        
        conversations: [],
        loadingList: false,
        bookingChannelIds: /** @type {Set<string>} */ (new Set()),
        
        activeConversation: null,
        messages: [],
        body: '',
        imageFile: null,
        imagePreviewUrl: null,
        loadingChat: false,
        sending: false,
        error: '',
        
        userChannel: null,
        subscribedChannels: [],
        debouncedListRefresh: null,
        realtimeConnected: false,

        get unreadTotal() {
            return this.conversations.reduce((sum, c) => sum + (Number(c.unread_count) || 0), 0);
        },

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

        connectRealtime() {
            if (this.realtimeConnected) {
                return;
            }

            this.realtimeConnected = true;
            void this.loadList();

            if (this.userId) {
                this.userChannel = `App.Models.User.${this.userId}`;
                window.Echo.private(this.userChannel).listen('.booking.updated', () => {
                    void this.loadList();
                });
                this.subscribedChannels.push(this.userChannel);
            }
        },

        togglePanel() {
            this.isPanelExpanded = !this.isPanelExpanded;
            if (!this.isPanelExpanded) {
                return;
            }

            if (this.realtimeConnected) {
                if (this.view === 'chat') {
                    this.scrollToLatest({ force: true });
                } else {
                    this.loadList();
                }

                return;
            }

            void ensureEcho().then((ok) => {
                if (ok) {
                    this.connectRealtime();
                } else {
                    void this.loadList();
                }

                if (this.view === 'chat') {
                    this.scrollToLatest({ force: true });
                }
            });
        },
        
        openChat(conv) {
            this.activeConversation = conv;
            this.view = 'chat';
            this.messages = [];
            this.error = '';
            this.body = '';
            this.clearImage();
            this.syncBookingChannels();
            this.loadChatMessages();
        },

        async openBookingById(bookingId) {
            if (bookingId == null || bookingId === '') {
                return;
            }
            this.isPanelExpanded = true;
            const id = String(bookingId);
            if (!this.conversations.length) {
                await this.loadList();
            }
            let conv = this.conversations.find((c) => String(c.id) === id);
            if (!conv) {
                await this.loadList();
                conv = this.conversations.find((c) => String(c.id) === id);
            }
            if (conv) {
                this.openChat(conv);
            } else {
                this.view = 'list';
            }
        },
        
        closeChat() {
            this.activeConversation = null;
            this.view = 'list';
            this.syncBookingChannels();
            this.loadList();
        },

        async loadList() {
            this.loadingList = true;
            try {
                const r = await fetch(this.listUrl, { headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' }});
                if (r.ok) {
                    const data = await r.json();
                    this.conversations = data.conversations || [];
                    this.syncBookingChannels();
                }
            } catch (e) {
                console.error(e);
            } finally {
                this.loadingList = false;
            }
        },

        bookingChannelSubscribeLimit: 10,

        bookingIdsToSubscribe() {
            const ids = new Set();
            const activeId = this.activeConversation && this.view === 'chat'
                ? String(this.activeConversation.id)
                : null;
            if (activeId) {
                ids.add(activeId);
            }

            this.conversations
                .filter((c) => (Number(c.unread_count) || 0) > 0)
                .slice(0, 8)
                .forEach((c) => ids.add(String(c.id)));

            this.conversations
                .slice(0, 5)
                .forEach((c) => ids.add(String(c.id)));

            return [...ids].slice(0, this.bookingChannelSubscribeLimit);
        },

        syncBookingChannels() {
            if (!window.Echo) return;
            const nextIds = new Set(this.bookingIdsToSubscribe());
            for (const id of [...this.bookingChannelIds]) {
                if (!nextIds.has(id)) {
                    this.leaveBookingChannel(id);
                }
            }
            nextIds.forEach((id) => this.ensureBookingChannel(id));
        },

        ensureBookingChannel(bookingId) {
            if (!window.Echo || this.bookingChannelIds.has(bookingId)) return;
            window.Echo.private(`booking.chat.${bookingId}`).listen('.chat.updated', (payload) => {
                this.onBookingChatEvent(bookingId, payload);
            });
            this.bookingChannelIds.add(bookingId);
        },

        leaveBookingChannel(bookingId) {
            if (!window.Echo || !this.bookingChannelIds.has(bookingId)) return;
            window.Echo.leave(`booking.chat.${bookingId}`);
            this.bookingChannelIds.delete(bookingId);
        },

        onBookingChatEvent(bookingId, payload = {}) {
            const id = String(bookingId);
            const action = payload?.action ?? 'message';

            if (action === 'read') {
                if (this.activeConversation && String(this.activeConversation.id) === id && this.view === 'chat') {
                    this.messages.forEach((m) => {
                        if (m.is_me) {
                            m.is_read = true;
                        }
                    });
                }

                return;
            }

            if (payload?.message_id && this.messages.some((m) => m.id === payload.message_id)) {
                return;
            }

            if (payload?.sender_id && String(payload.sender_id) === String(this.userId)) {
                return;
            }

            const conv = this.conversations.find((c) => String(c.id) === id);
            const viewing = this.activeConversation && String(this.activeConversation.id) === id && this.view === 'chat';

            if (conv) {
                if (viewing) {
                    conv.unread_count = 0;
                    void this.fetchNewMessages();
                } else {
                    conv.unread_count = (Number(conv.unread_count) || 0) + 1;
                    conv.last_message = 'Pesan baru';
                }
            } else if (viewing) {
                void this.fetchNewMessages();
            }

            if (!viewing && this.isPanelExpanded && this.view === 'list') {
                this.debouncedListRefresh?.();
            }
        },

        appendMessage(message) {
            if (!message?.id) {
                return;
            }

            this.messages = appendChatMessages(this.messages, [message]);

            const conv = this.conversations.find((c) => String(c.id) === String(this.activeConversation?.id));
            if (conv) {
                conv.last_message = message.body?.trim() || '📷 Gambar';
                conv.last_message_time = message.created_at || new Date().toISOString();
                conv.unread_count = 0;
            }
        },

        async fetchNewMessages() {
            if (!this.activeConversation) {
                return;
            }

            const lastId = this.messages.length ? this.messages[this.messages.length - 1].id : null;
            const url = chatMessagesUrl(this.activeConversation.fetchUrl, lastId);

            try {
                const r = await fetch(url, {
                    headers: { Accept: 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
                    credentials: 'same-origin',
                });
                if (!r.ok) {
                    return;
                }

                const data = await r.json();
                const incoming = data.messages ?? [];
                if (!incoming.length) {
                    return;
                }

                this.messages = appendChatMessages(this.messages, incoming);
                if (data.chat_open !== undefined) {
                    this.activeConversation.is_open = !!data.chat_open;
                }

                const conv = this.conversations.find((c) => String(c.id) === String(this.activeConversation.id));
                if (conv) {
                    conv.unread_count = Number(data.unread_for_me) || 0;
                    const last = incoming[incoming.length - 1];
                    conv.last_message = last.body?.trim() || '📷 Gambar';
                    conv.last_message_time = last.created_at || conv.last_message_time;
                }

                if (this.isPanelExpanded && this.view === 'chat') {
                    this.scrollToLatest({ force: true });
                }
            } catch (e) {
                console.error(e);
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

                const conv = this.conversations.find((c) => String(c.id) === String(this.activeConversation.id));
                if (conv) conv.unread_count = data.unread_for_me ?? 0;

                if (this.isPanelExpanded && this.view === 'chat') {
                    this.scrollToLatest({ force: hasNewMessage });
                }

                if ((Number(data.unread_for_me) || 0) > 0) {
                    await markChatRead(this.activeConversation?.readUrl);
                    if (conv) {
                        conv.unread_count = 0;
                    }
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
                if (data.chat_open !== undefined) {
                    this.activeConversation.is_open = !!data.chat_open;
                }
                if (data.message) {
                    this.appendMessage(data.message);
                    this.scrollToLatest({ force: true });
                }
            } catch (e) {
                this.error = e instanceof Error ? e.message : this.labels.sendError;
            } finally {
                this.sending = false;
            }
        },
        
        init() {
            this.debouncedListRefresh = debounce(() => void this.loadList(), 1200);
        },

        destroy() {
            leavePrivateChannels(this.subscribedChannels);
            for (const id of [...this.bookingChannelIds]) {
                this.leaveBookingChannel(id);
            }
        },
    }));

    Alpine.data('bookingChatPanel', (config) => ({
        bookingId: String(config.bookingId),
        fetchUrl: config.fetchUrl,
        storeUrl: config.storeUrl,
        unreadUrl: config.unreadUrl,
        initialOpen: config.initialOpen,
        locale: config.locale,
        introOpen: config.introOpen,
        introClosed: config.introClosed,
        labels: config.labels,

        isPanelExpanded: false,
        chatOpen: config.initialOpen,
        unreadForMe: 0,
        messages: [],
        body: '',
        imageFile: null,
        imagePreviewUrl: null,
        loading: false,
        sending: false,
        error: '',
        subscribedChannels: [],

        get unreadTotal() {
            return this.unreadForMe;
        },

        csrf() {
            return csrfToken();
        },

        formatTime(iso) {
            if (!iso) return '';
            const d = new Date(iso);
            if (Number.isNaN(d.getTime())) return '';
            return d.toLocaleTimeString(this.locale, { hour: '2-digit', minute: '2-digit' });
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

        async refreshUnreadOnly() {
            if (!this.unreadUrl) return;
            try {
                const r = await fetch(this.unreadUrl, {
                    headers: { Accept: 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
                    credentials: 'same-origin',
                });
                if (r.ok) {
                    const data = await r.json();
                    this.unreadForMe = Number(data.unread_for_me) || 0;
                }
            } catch (e) {
                console.error(e);
            }
        },

        togglePanel() {
            this.isPanelExpanded = !this.isPanelExpanded;
            if (this.isPanelExpanded) {
                this.loadMessages();
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

        async loadMessages(isBackground = false) {
            if (!isBackground) this.loading = true;
            this.error = '';
            const prevLastId = this.messages.length ? this.messages[this.messages.length - 1].id : null;
            try {
                const r = await fetch(this.fetchUrl, {
                    headers: { Accept: 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
                    credentials: 'same-origin',
                });
                if (!r.ok) throw new Error(this.labels.loadError);
                const data = await r.json();
                this.messages = data.messages ?? [];
                if (data.chat_open !== undefined) this.chatOpen = !!data.chat_open;
                this.unreadForMe = Number(data.unread_for_me) || 0;
                const nextLastId = this.messages.length ? this.messages[this.messages.length - 1].id : null;
                const hasNew = nextLastId !== prevLastId && this.messages.length > 0;
                if (this.isPanelExpanded) {
                    this.scrollToLatest({ force: hasNew });
                }
            } catch (e) {
                this.error = e instanceof Error ? e.message : this.labels.loadError;
            } finally {
                this.loading = false;
            }
        },

        async send() {
            const text = this.body.trim();
            const hasImage = !!this.imageFile;
            if ((!text && !hasImage) || !this.chatOpen || this.sending) return;
            this.sending = true;
            this.error = '';
            try {
                let r;
                if (hasImage) {
                    const fd = new FormData();
                    if (text) fd.append('body', text);
                    fd.append('image', this.imageFile);
                    r = await fetch(this.storeUrl, {
                        method: 'POST',
                        headers: { Accept: 'application/json', 'X-CSRF-TOKEN': this.csrf(), 'X-Requested-With': 'XMLHttpRequest' },
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
                    const msg = data.message ?? data.errors?.image?.[0] ?? data.errors?.body?.[0] ?? this.labels.sendError;
                    throw new Error(typeof msg === 'string' ? msg : this.labels.sendError);
                }
                this.body = '';
                this.clearImage();
                if (data.chat_open !== undefined) {
                    this.chatOpen = !!data.chat_open;
                }
                if (data.message) {
                    this.messages = appendChatMessages(this.messages, [data.message]);
                    this.scrollToLatest({ force: true });
                }
            } catch (e) {
                this.error = e instanceof Error ? e.message : this.labels.sendError;
            } finally {
                this.sending = false;
            }
        },

        async fetchNewMessages() {
            const lastId = this.messages.length ? this.messages[this.messages.length - 1].id : null;
            const url = chatMessagesUrl(this.fetchUrl, lastId);

            try {
                const r = await fetch(url, {
                    headers: { Accept: 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
                    credentials: 'same-origin',
                });
                if (!r.ok) {
                    return;
                }

                const data = await r.json();
                const incoming = data.messages ?? [];
                if (!incoming.length) {
                    return;
                }

                this.messages = appendChatMessages(this.messages, incoming);
                if (data.chat_open !== undefined) {
                    this.chatOpen = !!data.chat_open;
                }
                this.unreadForMe = Number(data.unread_for_me) || 0;
                if (this.isPanelExpanded) {
                    this.scrollToLatest({ force: true });
                }
            } catch (e) {
                console.error(e);
            }
        },

        onChatSocket(payload = {}) {
            const action = payload?.action ?? 'message';

            if (action === 'read') {
                this.messages.forEach((m) => {
                    if (m.is_me) {
                        m.is_read = true;
                    }
                });

                return;
            }

            if (payload?.message_id && this.messages.some((m) => m.id === payload.message_id)) {
                return;
            }

            if (this.isPanelExpanded) {
                void this.fetchNewMessages();
            } else {
                this.refreshUnreadOnly();
            }
        },

        init() {
            deferRealtimeInit('bookingChatPanel', () => {
                this.refreshUnreadOnly();

                const chatChannel = `booking.chat.${this.bookingId}`;
                window.Echo.private(chatChannel).listen('.chat.updated', (payload) => {
                    this.onChatSocket(payload);
                });
                this.subscribedChannels = [chatChannel];
            }, { timeout: 1500 });
        },

        destroy() {
            if (this.imagePreviewUrl) {
                URL.revokeObjectURL(this.imagePreviewUrl);
            }
            leavePrivateChannels(this.subscribedChannels);
        },
    }));

    Alpine.data('customerBookingLive', (config) => ({
        userId: config.userId != null ? String(config.userId) : '',
        bookingId: config.bookingId != null ? String(config.bookingId) : null,
        liveMode: config.liveMode ?? 'customer_show',
        fragmentUrl: config.fragmentUrl ?? null,
        liveStateUrl: config.liveStateUrl ?? null,
        showUrl: config.showUrl ?? null,
        paymentStatusUrl: config.paymentStatusUrl ?? null,
        clientStatus: config.initialStatus ?? null,
        clientPaymentStatus: config.initialPaymentStatus ?? null,
        paymentReturnPending: config.paymentReturnPending ?? false,
        paymentPollTimer: null,
        emergencyEventPending: false,
        subscribedChannels: [],
        refreshing: false,
        debouncedRefresh: null,

        init() {
            this.debouncedRefresh = debounce(() => void this.refreshFragment(), 450);

            const subscribe = () => {
                if (!this.userId) {
                    return;
                }

                const channel = `App.Models.User.${this.userId}`;
                const bookingMatch = this.bookingId
                    ? { field: 'booking_id', value: this.bookingId }
                    : null;

                window.Echo.private(channel)
                    .listen('.booking.updated', (e) => {
                        if (bookingMatch && !payloadMatches({ match: bookingMatch }, e)) {
                            return;
                        }
                        this.onBookingSocket(e);
                    })
                    .listen('.emergency.report.updated', (e) => {
                        if (bookingMatch && !payloadMatches({ match: bookingMatch }, e)) {
                            return;
                        }
                        this.onEmergencySocket(e);
                    });

                this.subscribedChannels = [channel];
            };

            if (this.paymentReturnPending && this.liveMode === 'customer_show') {
                void ensureEcho().then((ok) => {
                    this.startPaymentReturnPolling(ok);
                    if (ok) {
                        subscribe();
                    }
                });
            } else {
                deferRealtimeInit('customerBookingLive', subscribe, { timeout: 2000 });
            }
        },

        onEmergencySocket() {
            if (this.liveMode === 'customer_payment') {
                return;
            }
            this.emergencyEventPending = true;
            this.debouncedRefresh();
        },

        onBookingSocket(e) {
            if (!e?.booking_id) return;
            const isIndex = this.liveMode === 'customer_index' || this.liveMode === 'muthowif_index';
            if (!isIndex && this.bookingId && String(e.booking_id) !== String(this.bookingId)) {
                return;
            }
            if (this.liveMode === 'customer_payment' || (this.paymentReturnPending && e.payment_status === 'paid')) {
                if (this.showUrl && (e.payment_status === 'paid' || e.status === 'cancelled')) {
                    this.paymentReturnPending = false;
                    if (this.paymentPollTimer !== null) {
                        window.clearTimeout(this.paymentPollTimer);
                        this.paymentPollTimer = null;
                    }
                    window.location.replace(this.showUrl);
                }
                return;
            }
            this.debouncedRefresh();
        },

        usesTieredShowRefresh() {
            return (
                (this.liveMode === 'customer_show' || this.liveMode === 'muthowif_show')
                && this.liveStateUrl
                && this.$refs.liveGrid
            );
        },

        liveStateQuery() {
            const params = new URLSearchParams();
            if (this.clientStatus) {
                params.set('status', this.clientStatus);
            }
            if (this.clientPaymentStatus) {
                params.set('payment_status', this.clientPaymentStatus);
            }
            if (this.emergencyEventPending) {
                params.set('emergency_event', '1');
            }
            if (this.paymentReturnPending) {
                params.set('payment_return', '1');
            }

            const qs = params.toString();

            return qs ? `?${qs}` : '';
        },

        startPaymentReturnPolling(echoReady = false) {
            if (!this.paymentStatusUrl || this.paymentPollTimer !== null) {
                return;
            }

            const intervalMs = echoReady ? 8000 : 3000;

            const poll = async () => {
                try {
                    const data = await fetchJson(this.paymentStatusUrl);
                    if (data?.is_paid) {
                        this.paymentReturnPending = false;
                        if (this.showUrl) {
                            window.location.assign(this.showUrl);
                        } else {
                            window.location.reload();
                        }
                        return;
                    }
                } catch (err) {
                    console.error(err);
                }

                this.paymentPollTimer = window.setTimeout(poll, intervalMs);
            };

            void poll();
        },

        async refreshFragment() {
            if (!this.fragmentUrl || this.refreshing) {
                return;
            }

            this.refreshing = true;
            try {
                if (this.usesTieredShowRefresh()) {
                    await this.refreshTieredShowFragment();
                    return;
                }

                let url = this.fragmentUrl;
                if (this.liveMode === 'customer_index' || this.liveMode === 'muthowif_index') {
                    url += window.location.search || '';
                }

                const root = this.$refs.liveRoot ?? this.$refs.liveGrid;
                const html = await fetchHtmlFragment(url);
                if (html !== null && root) {
                    if (
                        (this.liveMode === 'customer_index' || this.liveMode === 'muthowif_index')
                        && html.includes('data-live-part')
                    ) {
                        swapLiveParts(root, html);
                    } else {
                        swapAlpineHtml(root, html);
                    }
                }
            } catch (err) {
                console.error(err);
            } finally {
                this.refreshing = false;
            }
        },

        async refreshTieredShowFragment() {
            const grid = this.$refs.liveGrid;
            if (!grid) {
                return;
            }

            if (this.liveMode === 'customer_show') {
                const html = await fetchHtmlFragment(this.fragmentUrl);
                if (html !== null && html.includes('data-live-part')) {
                    swapLiveParts(grid, html);
                }
                return;
            }

            const state = await fetchJson(`${this.liveStateUrl}${this.liveStateQuery()}`);
            if (!state?.tier) {
                return;
            }

            const tier = state.tier;
            const fragmentUrl = `${this.fragmentUrl}${this.fragmentUrl.includes('?') ? '&' : '?'}tier=${encodeURIComponent(tier)}`;
            const html = await fetchHtmlFragment(fragmentUrl);
            if (html === null) {
                return;
            }

            if (tier === 'dynamic') {
                swapLiveParts(grid, html);
            } else {
                swapAlpineHtml(grid, html);
            }

            this.clientStatus = state.status ?? this.clientStatus;
            this.clientPaymentStatus = state.payment_status ?? this.clientPaymentStatus;
            this.emergencyEventPending = false;
        },

        destroy() {
            if (this.paymentPollTimer !== null) {
                window.clearTimeout(this.paymentPollTimer);
                this.paymentPollTimer = null;
            }
            leavePrivateChannels(this.subscribedChannels);
        },
    }));

    Alpine.data('muthowifWithdrawalsLive', (config) => ({
        userId: config.userId != null ? String(config.userId) : '',
        fragmentUrl: config.fragmentUrl ?? null,
        subscribedChannels: [],
        refreshing: false,

        init() {
            if (!this.userId || !this.fragmentUrl) {
                return;
            }

            deferRealtimeInit('muthowifWithdrawalsLive', () => {
                const channel = `App.Models.User.${this.userId}`;
                window.Echo.private(channel).listen('.withdrawal.updated', () => {
                    void this.refreshFragment();
                });
                this.subscribedChannels = [channel];
            });
        },

        async refreshFragment() {
            if (!this.fragmentUrl || this.refreshing) {
                return;
            }

            this.refreshing = true;
            try {
                const html = await fetchHtmlFragment(this.fragmentUrl + (window.location.search || ''));
                if (html !== null) {
                    swapAlpineHtml(this.$refs.liveRoot, html);
                }
            } catch (err) {
                console.error(err);
            } finally {
                this.refreshing = false;
            }
        },

        destroy() {
            leavePrivateChannels(this.subscribedChannels);
        },
    }));

    Alpine.data('muthowifVerificationLive', (config) => ({
        userId: config.userId != null ? String(config.userId) : '',
        fragmentUrl: config.fragmentUrl ?? null,
        reloadOnApproved: config.reloadOnApproved ?? false,
        subscribedChannels: [],
        refreshing: false,

        init() {
            deferRealtimeInit('muthowifVerificationLive', () => {
                const channels = [];

                if (this.userId) {
                    const userChannel = `App.Models.User.${this.userId}`;
                    window.Echo.private(userChannel).listen('.muthowif.verification.updated', (e) => {
                        this.onVerificationEvent(e);
                    });
                    channels.push(userChannel);
                }

                if (config.listenAdmin) {
                    window.Echo.private('admin.muthowif-profiles').listen('.muthowif.verification.updated', () => {
                        void this.refreshFragment();
                    });
                    channels.push('admin.muthowif-profiles');
                }

                this.subscribedChannels = channels;
            });
        },

        onVerificationEvent(e) {
            if (this.reloadOnApproved && e?.verification_status === 'approved') {
                window.location.reload();
                return;
            }
            void this.refreshFragment();
        },

        async refreshFragment() {
            if (!this.fragmentUrl || this.refreshing) {
                return;
            }

            this.refreshing = true;
            try {
                const html = await fetchHtmlFragment(this.fragmentUrl + (window.location.search || ''));
                if (html !== null) {
                    swapAlpineHtml(this.$refs.liveRoot, html);
                }
            } catch (err) {
                console.error(err);
            } finally {
                this.refreshing = false;
            }
        },

        destroy() {
            leavePrivateChannels(this.subscribedChannels);
        },
    }));

    Alpine.data('adminServiceMonitorLive', (config) => ({
        fragmentUrl: config.fragmentUrl ?? null,
        filter: config.filter ?? 'active',
        realtimeEnabled: config.realtimeEnabled ?? false,
        channelName: 'admin.service-monitor',
        refreshing: false,

        init() {
            if (!this.realtimeEnabled) {
                return;
            }

            deferRealtimeInit('adminServiceMonitorLive', () => {
                window.Echo.private(this.channelName).listen('.service_monitor.updated', () => {
                    this.refreshFragment();
                });
            });
        },

        fragmentUrlWithFilter() {
            const url = new URL(this.fragmentUrl, window.location.origin);
            url.searchParams.set('filter', this.filter);
            return url.toString();
        },

        async refreshFragment() {
            if (!this.fragmentUrl || this.refreshing) {
                return;
            }
            this.refreshing = true;
            try {
                const r = await fetch(this.fragmentUrlWithFilter(), {
                    headers: {
                        Accept: 'text/html',
                        'X-Requested-With': 'XMLHttpRequest',
                    },
                    credentials: 'same-origin',
                });
                if (!r.ok) {
                    return;
                }
                const html = await r.text();
                const root = this.$refs.liveRoot;
                if (!root) {
                    return;
                }
                if (typeof Alpine.destroyTree === 'function') {
                    Alpine.destroyTree(root);
                }
                root.innerHTML = html;
                Alpine.initTree(root);
            } catch (err) {
                console.error(err);
            } finally {
                this.refreshing = false;
            }
        },

        destroy() {
            if (window.Echo) {
                window.Echo.leave(this.channelName);
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

    Alpine.data('articleAdminEditor', (config) => ({
        activeLocale: config.activeLocale ?? 'id',
        previewDevice: 'desktop',
        slug: config.slug ?? '',
        publishedAtValue: config.publishedAt ?? '',
        dateFormatLocale: config.dateFormatLocale ?? 'id-ID',
        labels: config.labels ?? {
            readingMinutes: '{n} min',
            byAuthor: '{name}',
            previewTitleFallback: '…',
        },
        locales: config.locales ?? {
            id: { title: '', excerpt: '', category: '', author: '', bodyHtml: '', bodyJson: '' },
            en: { title: '', excerpt: '', category: '', author: '', bodyHtml: '', bodyJson: '' },
            ar: { title: '', excerpt: '', category: '', author: '', bodyHtml: '', bodyJson: '' },
        },
        _editorjsHandler: null,
        init() {
            this._editorjsHandler = (e) => {
                const d = e.detail;
                if (! d || ! d.locale || ! this.locales[d.locale]) {
                    return;
                }
                this.locales[d.locale].bodyHtml = typeof d.html === 'string' ? d.html : '';
                this.locales[d.locale].bodyJson = typeof d.json === 'object' ? JSON.stringify(d.json) : '';
            };
            window.addEventListener('article-admin-editorjs', this._editorjsHandler);
        },
        destroy() {
            if (this._editorjsHandler) {
                window.removeEventListener('article-admin-editorjs', this._editorjsHandler);
            }
        },
        setPublished(published) {
            const form = document.getElementById('article-admin-form');
            const cb = form?.querySelector('input[name="is_published"][type="checkbox"]');
            if (cb) {
                cb.checked = published;
            }
            form?.requestSubmit();
        },
        activeBlock() {
            return this.locales[this.activeLocale] ?? this.locales.id;
        },
        firstImageSrc(html) {
            if (! html) {
                return null;
            }
            const m = html.match(/<img[^>]+src=["']([^"']+)["']/i);

            return m ? m[1] : null;
        },
        readingMinutes(html) {
            const t = typeof html === 'string' ? html.replace(/<[^>]+>/g, ' ').trim() : '';
            const words = t.split(/\s+/).filter(Boolean).length;

            return Math.max(1, Math.ceil(words / 200));
        },
        readingLabel(n) {
            return this.labels.readingMinutes.replace('{n}', String(n));
        },
        authorLine(name) {
            if (! name) {
                return '';
            }

            return this.labels.byAuthor.replace('{name}', name);
        },
        formatPublished() {
            const raw = this.publishedAtValue;
            if (! raw) {
                return '—';
            }
            const d = new Date(raw);
            if (Number.isNaN(d.getTime())) {
                return '—';
            }

            return d.toLocaleDateString(this.dateFormatLocale, {
                day: 'numeric',
                month: 'short',
                year: 'numeric',
            });
        },
        previewFrameClass() {
            if (this.previewDevice === 'tablet') {
                return 'max-w-xl';
            }
            if (this.previewDevice === 'mobile') {
                return 'max-w-sm';
            }

            return 'max-w-3xl';
        },
    }));

    Alpine.data('mootaWebhookLiveDashboard', (initialRows = [], realtimeEnabled = false, payloadSourceLabels = {}) => ({
        rows: Array.isArray(initialRows) ? initialRows : [],
        realtimeEnabled,
        payloadSourceLabels,
        expandedId: null,
        toggleRow(id) {
            this.expandedId = this.expandedId === id ? null : id;
        },
        payloadSourceLabel(key) {
            if (!key) {
                return '—';
            }

            return this.payloadSourceLabels[key] || key;
        },
        init() {
            if (!this.realtimeEnabled) {
                return;
            }

            deferRealtimeInit('mootaWebhookLiveDashboard', () => {
                try {
                    window.Echo.private('admin.moota-webhooks').listen('.moota.webhook.recorded', (payload) => {
                        const webhook = payload?.webhook;
                        if (!webhook?.id) {
                            return;
                        }
                        if (this.rows.some((row) => String(row.id) === String(webhook.id))) {
                            return;
                        }
                        this.rows.unshift(webhook);
                        if (this.rows.length > 100) {
                            this.rows.pop();
                        }
                    });
                } catch (e) {
                    console.warn('[Moota webhooks] Echo subscribe failed (feed tetap dari server):', e);
                }
            });
        },
    }));
});

window.Alpine = Alpine;

async function startApp() {
    if (document.getElementById('booking-panel')) {
        const { registerBookingForm } = await import('./booking-form');
        registerBookingForm(Alpine);
    }

    Alpine.start();
}

void startApp();
