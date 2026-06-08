export function registerWhatsappBroadcastAdmin(Alpine) {
    Alpine.data('whatsappBroadcastAdmin', (config) => ({
        freeNumbers: config.initialFreeNumbers ?? '',
        attachmentPreviewUrl: null,
        attachmentFileName: null,
        search: config.initialSearch ?? '',
        selectedCount: 0,
        init() {
            this.$nextTick(() => this.updateSelectedCount());
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
        rowVisible(el) {
            const q = (this.search ?? '').trim().toLowerCase();
            if (q === '') {
                return true;
            }

            return (el?.dataset?.search ?? '').includes(q);
        },
        updateSelectedCount() {
            this.selectedCount = this.$el.querySelectorAll('.broadcast-recipient-checkbox:checked').length;
        },
        selectAllVisible() {
            this.$el.querySelectorAll('.broadcast-recipient-row').forEach((row) => {
                if (!this.rowVisible(row)) {
                    return;
                }
                const checkbox = row.querySelector('.broadcast-recipient-checkbox');
                if (checkbox) {
                    checkbox.checked = true;
                }
            });
            this.updateSelectedCount();
        },
        clearSelection() {
            this.$el.querySelectorAll('.broadcast-recipient-checkbox').forEach((checkbox) => {
                checkbox.checked = false;
            });
            this.updateSelectedCount();
        },
        parseFreeNumbers() {
            const raw = (this.freeNumbers ?? '').trim();
            if (raw === '') {
                return [];
            }

            return raw.split(/[\s,;\n\r]+/).map((v) => v.trim()).filter((v) => v !== '');
        },
        selectedCountLabel() {
            return (config.labels?.selectedCount ?? ':count selected').replace(':count', String(this.selectedCount));
        },
        freeNumbersCountLabel() {
            const n = this.parseFreeNumbers().length;
            return (config.labels?.freeCount ?? ':count free numbers').replace(':count', String(n));
        },
        totalRecipientsLabel() {
            const n = this.selectedCount + this.parseFreeNumbers().length;
            return (config.labels?.totalEstimate ?? ':count recipients').replace(':count', String(n));
        },
        confirmSend(event) {
            this.updateSelectedCount();
            const total = this.selectedCount + this.parseFreeNumbers().length;
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
