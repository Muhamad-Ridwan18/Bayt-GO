const DOC_FIELDS = ['ticket_outbound', 'ticket_return', 'passport', 'itinerary', 'visa'];

/**
 * Booking checkout — isolated stores + explicit sync to avoid whole-page re-renders.
 */
export function registerBookingForm(Alpine) {
    Alpine.store('bookingStep', { current: 1 });

    Alpine.store('bookingSummary', {
        serviceLabelDisplay: '',
        pilgrimCount: 1,
        docsCountDisplay: '',
    });

    Alpine.data('bookingStepper', () => ({
        get step() {
            return Alpine.store('bookingStep').current;
        },
        stepClass(num) {
            if (this.step === num) {
                return 'bg-brand-700 text-white shadow-md shadow-brand-700/30';
            }
            if (this.step > num) {
                return 'bg-brand-600 text-white';
            }

            return 'bg-slate-100 text-slate-500 ring-2 ring-slate-200/90';
        },
        labelClass(num) {
            if (this.step === num) {
                return 'text-brand-800';
            }
            if (this.step > num) {
                return 'text-brand-700';
            }

            return 'text-slate-700';
        },
        isDone(num) {
            return this.step > num;
        },
    }));

    Alpine.data('bookingSummaryAside', () => ({
        get summary() {
            return Alpine.store('bookingSummary');
        },
    }));

    Alpine.data('bookingForm', (config) => ({
        step: config.initialStep ?? 1,
        serviceType: config.serviceType ?? 'group',
        pilgrimCount: config.pilgrimCount ?? 1,
        pilgrimMin: 1,
        pilgrimMax: 50,
        serviceIsGroup: (config.serviceType ?? 'group') === 'group',
        bounds: config.bounds ?? { group: { min: 1, max: 50 }, private: { min: 1, max: 50 } },
        labels: config.labels ?? {},
        tempUploadUrl: config.tempUploadUrl ?? '',
        docs: config.docs ?? {},
        docFieldKeys: DOC_FIELDS,
        tcOpen: false,
        tcAgree: false,
        uploadPendingError: '',
        serviceTypeError: '',
        docUploading: false,
        docLabels: config.docLabels ?? {},
        messages: config.messages ?? {},
        csrfToken: '',

        serviceLabelDisplay: '',
        docsCountDisplay: '',

        init() {
            this.csrfToken = document.querySelector('meta[name=csrf-token]')?.getAttribute('content') ?? '';
            Alpine.store('bookingStep').current = this.step;

            this.$watch('step', (value) => {
                Alpine.store('bookingStep').current = value;
            });

            this.refreshBounds();
            this.syncSummary();
            this.refreshDisplayFields();

            this.$watch('serviceType', () => {
                this.serviceIsGroup = this.serviceType === 'group';
                this.clampPilgrim();
                this.refreshBounds();
                this.refreshDisplayFields();
                this.syncSummary();
            });

            this.$watch('pilgrimCount', () => {
                this.syncSummary();
            });
        },

        syncSummary() {
            const store = Alpine.store('bookingSummary');
            store.serviceLabelDisplay = this.serviceLabelDisplay;
            store.pilgrimCount = this.pilgrimCount;
            store.docsCountDisplay = this.docsCountDisplay;
        },

        refreshDisplayFields() {
            this.serviceLabelDisplay = this.serviceType === 'group'
                ? this.labels.group
                : this.labels.private;
            this.refreshDocsCount();
        },

        refreshBounds() {
            const b = this.serviceType === 'group' ? this.bounds.group : this.bounds.private;
            this.pilgrimMin = b.min;
            this.pilgrimMax = b.max;
        },

        requiredDocFields() {
            const fields = ['ticket_outbound', 'ticket_return', 'passport'];
            if (this.serviceIsGroup) {
                fields.push('itinerary');
            }

            return fields;
        },

        refreshDocsCount() {
            const fields = this.requiredDocFields();
            const uploaded = fields.filter((field) => this.docs[field]?.path).length;
            this.docsCountDisplay = (this.labels.docsCount ?? ':uploaded/:total')
                .replace(':uploaded', String(uploaded))
                .replace(':total', String(fields.length));
            this.syncSummary();
        },

        clampPilgrim() {
            if (this.pilgrimCount < this.pilgrimMin) {
                this.pilgrimCount = this.pilgrimMin;
            }
            if (this.pilgrimCount > this.pilgrimMax) {
                this.pilgrimCount = this.pilgrimMax;
            }
        },

        adjustPilgrim(delta) {
            this.pilgrimCount = Math.max(
                this.pilgrimMin,
                Math.min(this.pilgrimMax, this.pilgrimCount + delta),
            );
        },

        syncPilgrimFromInput() {
            this.clampPilgrim();
        },

        docUploadedLabel(name) {
            return (this.docLabels.docUploaded ?? '').replace(':name', name ?? '');
        },

        async uploadBookingDoc(field, event) {
            const input = event.target;
            const file = input.files?.[0] ?? null;
            if (!file) {
                return;
            }

            this.docs[field].uploading = true;
            this.docs[field].error = '';
            this.docUploading = true;

            const fd = new FormData();
            fd.append('field', field);
            fd.append('file', file);
            if (this.docs[field].path) {
                fd.append('previous_path', this.docs[field].path);
            }

            const controller = new AbortController();
            const timeoutId = setTimeout(() => controller.abort(), 120000);

            try {
                const r = await fetch(this.tempUploadUrl, {
                    method: 'POST',
                    headers: {
                        Accept: 'application/json',
                        'X-CSRF-TOKEN': this.csrfToken,
                        'X-Requested-With': 'XMLHttpRequest',
                    },
                    credentials: 'same-origin',
                    body: fd,
                    signal: controller.signal,
                });
                const data = await r.json().catch(() => ({}));
                if (!r.ok || !data.path) {
                    throw new Error(data.message || this.messages.docUploadFailed || 'Upload failed');
                }

                this.docs[field].path = data.path;
                this.docs[field].name = data.name ?? file.name;
                this.docs[field].error = '';
                this.uploadPendingError = '';
                input.value = '';
                input.removeAttribute('required');
                this.refreshDocsCount();
            } catch (e) {
                const fallback = this.messages.docUploadFailed || 'Upload failed';
                this.docs[field].error = e instanceof Error && e.name === 'AbortError'
                    ? (this.messages.docUploadTimeout || fallback)
                    : (e instanceof Error ? e.message : fallback);
                input.value = '';
            } finally {
                clearTimeout(timeoutId);
                this.docs[field].uploading = false;
                this.docUploading = DOC_FIELDS.some((f) => this.docs[f]?.uploading);
            }
        },

        openBookingTc() {
            if (!this.$refs.bookingForm.checkValidity()) {
                this.$refs.bookingForm.reportValidity();
                return;
            }
            this.tcAgree = false;
            this.tcOpen = true;
        },

        submitAfterTc() {
            if (!this.tcAgree) {
                return;
            }
            this.tcOpen = false;
            this.$nextTick(() => this.$refs.bookingForm.requestSubmit());
        },

        scrollToStep() {
            this.$nextTick(() => {
                document.getElementById('booking-step-' + this.step)?.scrollIntoView({ block: 'start' });
            });
        },

        prevStep() {
            if (this.step > 1) {
                this.step--;
                this.scrollToStep();
            }
        },

        nextStep() {
            if (this.step === 1) {
                if (!this.serviceType) {
                    this.serviceTypeError = this.messages.serviceRequired || '';
                    return;
                }
                this.serviceTypeError = '';
                this.clampPilgrim();
                const pc = this.$refs.bookingForm.querySelector('#pilgrim_count');
                if (pc && !pc.checkValidity()) {
                    pc.reportValidity();
                    return;
                }
                this.step = 2;
            } else if (this.step === 2) {
                this.uploadPendingError = '';

                if (this.docUploading) {
                    this.uploadPendingError = this.messages.uploadPending || '';
                    return;
                }

                const required = this.requiredDocFields();
                let blocked = false;
                for (const field of required) {
                    if (!this.docs[field].path) {
                        this.docs[field].error = this.messages.docRequired || '';
                        blocked = true;
                    } else {
                        this.docs[field].error = '';
                    }
                }
                if (blocked) {
                    return;
                }
                this.step = 3;
            }
            this.scrollToStep();
        },
    }));
}
