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
});

window.Alpine = Alpine;

Alpine.start();
