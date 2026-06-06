import { defineConfig } from 'vite';
import laravel from 'laravel-vite-plugin';

export default defineConfig({
    plugins: [
        laravel({
            input: ['resources/css/app.css', 'resources/js/app.js'],
            refresh: true,
        }),
    ],
    build: {
        rollupOptions: {
            output: {
                manualChunks(id) {
                    if (id.includes('node_modules/pusher-js') || id.includes('node_modules/laravel-echo')) {
                        return 'echo-vendor';
                    }
                    if (id.includes('/resources/js/echo.js')) {
                        return 'echo';
                    }
                    if (id.includes('/resources/js/booking-form.js')) {
                        return 'booking';
                    }
                },
            },
        },
    },
});
