/**
 * Register form — role toggle, terms gate, cached-file removal.
 */
export function registerRegisterForm(Alpine) {
    Alpine.data('registerForm', (config = {}) => ({
        selectedRole: config.selectedRole ?? 'customer',
        customerType: config.customerType ?? 'personal',
        removeFileUrl: config.removeFileUrl ?? '',
        termsModalOpen: false,
        termsAccepted: false,

        handleRegisterSubmit() {
            if (this.termsAccepted) {
                this.$refs.registerForm.submit();
                return;
            }

            this.termsModalOpen = true;
        },

        agreeAndSubmit() {
            this.termsAccepted = true;
            this.termsModalOpen = false;
            this.$nextTick(() => {
                this.$refs.registerForm.submit();
            });
        },

        removeCachedFile(payload) {
            const form = document.createElement('form');
            form.method = 'POST';
            form.action = this.removeFileUrl;

            const append = (name, value) => {
                if (value === null || value === undefined || value === '') {
                    return;
                }
                const input = document.createElement('input');
                input.type = 'hidden';
                input.name = name;
                input.value = String(value);
                form.appendChild(input);
            };

            const csrf = document.querySelector('#register-form input[name=_token]');
            append('_token', csrf ? csrf.value : '');
            append('type', payload?.type);
            append('file_id', payload?.file_id);
            append('path', payload?.path);

            document.body.appendChild(form);
            form.submit();
        },
    }));
}
