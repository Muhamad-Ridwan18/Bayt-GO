export function registerWhatsappBroadcastAdmin(Alpine) {
    Alpine.data('whatsappBroadcastAdmin', (config) => ({
        muthowifs: config.muthowifs ?? [],
        selectedIds: Array.isArray(config.initialSelected) ? [...config.initialSelected] : [],
        freeNumbers: config.initialFreeNumbers ?? '',
        attachmentPreviewUrl: null,
        attachmentFileName: null,
        search: config.initialSearch ?? '',
        init() {
            this.$nextTick(() => this.syncCheckboxes());
        },
        previewAttachment(event) {
            const file = event.target.files?.[0];
            if (!file) {
                this.attachmentPreviewUrl = null;
                this.attachmentFileName = null;
                return;
            }
            this.attachmentFileName = file.name;
            if (file.type.startsWith('image/')) {
                this.attachmentPreviewUrl = URL.createObjectURL(file);
            } else {
                this.attachmentPreviewUrl = null;
            }
        },
        filteredMuthowifs() {
            const q = (this.search ?? '').trim().toLowerCase();
            if (q === '') {
                return this.muthowifs;
            }
            return this.muthowifs.filter((item) => {
                const hay = `${item.name} ${item.phone} ${item.status_label}`.toLowerCase();
                return hay.includes(q);
            });
        },
        toggleId(id) {
            const idx = this.selectedIds.indexOf(id);
            if (idx === -1) {
                this.selectedIds.push(id);
            } else {
                this.selectedIds.splice(idx, 1);
            }
        },
        selectAllVisible() {
            const visibleIds = this.filteredMuthowifs().map((item) => item.id);
            visibleIds.forEach((id) => {
                if (!this.selectedIds.includes(id)) {
                    this.selectedIds.push(id);
                }
            });
            this.$nextTick(() => this.syncCheckboxes());
        },
        clearSelection() {
            this.selectedIds = [];
            this.$nextTick(() => this.syncCheckboxes());
        },
        syncCheckboxes() {
            this.$el.querySelectorAll('input[name="muthowif_profile_ids[]"]').forEach((el) => {
                el.checked = this.selectedIds.includes(el.value);
            });
        },
        parseFreeNumbers() {
            const raw = (this.freeNumbers ?? '').trim();
            if (raw === '') {
                return [];
            }
            return raw.split(/[\s,;\n\r]+/).map((v) => v.trim()).filter((v) => v !== '');
        },
        selectedCountLabel() {
            const n = this.selectedIds.length;
            return (config.labels?.selectedCount ?? ':count selected').replace(':count', String(n));
        },
        freeNumbersCountLabel() {
            const n = this.parseFreeNumbers().length;
            return (config.labels?.freeCount ?? ':count free numbers').replace(':count', String(n));
        },
        totalRecipientsLabel() {
            const n = this.selectedIds.length + this.parseFreeNumbers().length;
            return (config.labels?.totalEstimate ?? ':count recipients').replace(':count', String(n));
        },
        confirmSend(event) {
            const total = this.selectedIds.length + this.parseFreeNumbers().length;
            if (total === 0) {
                event.preventDefault();
                alert(config.labels?.recipientsRequired ?? 'Select at least one recipient.');
                return false;
            }
            const messageEl = document.getElementById('broadcast-message');
            const attachmentEl = document.getElementById('broadcast-attachment');
            const hasMessage = messageEl && messageEl.value.trim() !== '';
            const hasAttachment = attachmentEl && attachmentEl.files && attachmentEl.files.length > 0;
            if (!hasMessage && !hasAttachment) {
                event.preventDefault();
                alert(config.labels?.messageOrAttachmentRequired ?? 'Message or attachment required.');
                return false;
            }
            const msg = (config.labels?.confirmSend ?? 'Send to :count recipients?').replace(':count', String(total));
            if (!window.confirm(msg)) {
                event.preventDefault();
                return false;
            }
            return true;
        },
    }));
}
