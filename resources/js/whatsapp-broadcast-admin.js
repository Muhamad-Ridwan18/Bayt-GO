function readBroadcastConfig() {
    const node = document.getElementById('wa-broadcast-config');
    if (! node) {
        return {};
    }

    try {
        return JSON.parse(node.textContent || '{}');
    } catch {
        return {};
    }
}

export function registerWhatsappBroadcastAdmin(Alpine) {
    Alpine.data('whatsappBroadcastAdmin', () => {
        const config = readBroadcastConfig();
        const labels = config.labels ?? {};

        return {
            freeNumbers: config.initialFreeNumbers ?? '',
            attachmentPreviewUrl: null,
            attachmentFileName: null,
            selectedCount: 0,
            allVisibleSelected: false,
            someVisibleSelected: false,
            init() {
                this.updateSelectedCount();
            },
            previewAttachment(event) {
                const file = event.target.files?.[0];
                if (! file) {
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
            recipientCheckboxes() {
                return this.$el.querySelectorAll('.broadcast-recipient-checkbox');
            },
            updateSelectedCount() {
                const boxes = this.recipientCheckboxes();
                const checked = this.$el.querySelectorAll('.broadcast-recipient-checkbox:checked').length;
                this.selectedCount = checked;
                this.allVisibleSelected = boxes.length > 0 && checked === boxes.length;
                this.someVisibleSelected = checked > 0 && checked < boxes.length;
                const master = this.$refs.selectAllCheckbox;
                if (master) {
                    master.indeterminate = this.someVisibleSelected;
                    master.checked = this.allVisibleSelected;
                }
            },
            toggleSelectAll(event) {
                const checked = event.target.checked;
                this.recipientCheckboxes().forEach((checkbox) => {
                    checkbox.checked = checked;
                });
                this.updateSelectedCount();
            },
            selectAllVisible() {
                this.recipientCheckboxes().forEach((checkbox) => {
                    checkbox.checked = true;
                });
                this.updateSelectedCount();
            },
            clearSelection() {
                this.recipientCheckboxes().forEach((checkbox) => {
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
                return (labels.selectedCount ?? ':count selected').replace(':count', String(this.selectedCount));
            },
            freeNumbersCountLabel() {
                const n = this.parseFreeNumbers().length;
                return (labels.freeCount ?? ':count free numbers').replace(':count', String(n));
            },
            totalRecipientsLabel() {
                const n = this.selectedCount + this.parseFreeNumbers().length;
                return (labels.totalEstimate ?? ':count recipients').replace(':count', String(n));
            },
            confirmSend(event) {
                this.updateSelectedCount();
                const total = this.selectedCount + this.parseFreeNumbers().length;
                if (total === 0) {
                    event.preventDefault();
                    alert(labels.recipientsRequired ?? 'Select at least one recipient.');
                    return;
                }
                const messageEl = document.getElementById('broadcast-message');
                const attachmentEl = document.getElementById('broadcast-attachment');
                const hasMessage = messageEl && messageEl.value.trim() !== '';
                const hasAttachment = attachmentEl && attachmentEl.files && attachmentEl.files.length > 0;
                if (! hasMessage && ! hasAttachment) {
                    event.preventDefault();
                    alert(labels.messageOrAttachmentRequired ?? 'Message or attachment required.');
                    return;
                }
                const msg = (labels.confirmSend ?? 'Send to :count recipients?').replace(':count', String(total));
                if (! window.confirm(msg)) {
                    event.preventDefault();
                }
            },
        };
    });
}
