/**
 * Welcome / customer home feed interactions.
 */
export function registerHomeFeed(Alpine) {
    Alpine.data('homeSearch', (config = {}) => ({
        selected: config.selected ?? null,
        startsAt: config.startsAt ?? '',
        dateError: '',
        rangeRequired: config.rangeRequired ?? '',
        slotRequired: config.slotRequired ?? '',

        select(cat) {
            this.selected = cat;
            this.dateError = '';
        },

        search() {
            this.dateError = '';
            if (!this.selected) {
                return;
            }

            if (this.selected.type === 'layanan') {
                const start = this.$refs.umrohDates?.querySelector('[name=start_date]')?.value || '';
                const end = this.$refs.umrohDates?.querySelector('[name=end_date]')?.value || start;
                if (!start) {
                    this.dateError = this.rangeRequired;
                    this.$refs.umrohDates?.querySelector('button[aria-haspopup=\'dialog\']')?.click();
                    return;
                }
                let url = this.selected.url;
                url += (url.includes('?') ? '&' : '?') + 'start_date=' + encodeURIComponent(start) + '&end_date=' + encodeURIComponent(end);
                window.location.href = url;
                return;
            }

            if (!this.startsAt) {
                this.dateError = this.slotRequired;
                return;
            }
            let url = this.selected.url;
            url += (url.includes('?') ? '&' : '?') + 'starts_at=' + encodeURIComponent(this.startsAt);
            window.location.href = url;
        },
    }));

    Alpine.data('homeScrollTrack', () => ({
        scroll(dx) {
            const el = this.$refs.track;
            if (el) {
                el.scrollBy({ left: dx, behavior: 'smooth' });
            }
        },
    }));

    Alpine.data('homeGallery', () => ({
        open: false,
        url: '',
        title: '',
        href: null,
        show(u, t, h) {
            this.url = u;
            this.title = t;
            this.href = h;
            this.open = true;
        },
        close() {
            this.open = false;
        },
    }));

    Alpine.data('homeFaq', () => ({
        open: 0,
        toggle(i) {
            this.open = this.open === i ? null : i;
        },
    }));
}
