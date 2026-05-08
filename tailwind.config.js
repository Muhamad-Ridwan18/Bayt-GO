import defaultTheme from 'tailwindcss/defaultTheme';
import forms from '@tailwindcss/forms';

/** @type {import('tailwindcss').Config} */
export default {
    content: [
        './vendor/laravel/framework/src/Illuminate/Pagination/resources/views/*.blade.php',
        './storage/framework/views/*.php',
        './resources/views/**/*.blade.php',
    ],

    theme: {
        extend: {
            fontFamily: {
                sans: ['Plus Jakarta Sans', 'Figtree', ...defaultTheme.fontFamily.sans],
                welcome: ['Inter', 'Plus Jakarta Sans', 'system-ui', ...defaultTheme.fontFamily.sans],
            },
            colors: {
                brand: {
                    50: '#f0fdfa',
                    100: '#ccfbf1',
                    200: '#99f6e4',
                    300: '#5eead4',
                    400: '#2dd4bf',
                    500: '#14b8a6',
                    600: '#0d9488',
                    700: '#0f766e',
                    800: '#115e59',
                    900: '#134e4a',
                    950: '#042f2e',
                },
                baytgo: {
                    DEFAULT: '#1A3D34',
                    50: '#e8eeec',
                    100: '#c5d4cf',
                    800: '#15332b',
                    900: '#1A3D34',
                    950: '#0f221d',
                },
                gold: {
                    DEFAULT: '#C5A059',
                    muted: '#b8954d',
                    light: '#e8dcb8',
                },
                welcomeCanvas: {
                    DEFAULT: '#F9F7F2',
                    soft: '#FBF9F5',
                },
            },
            boxShadow: {
                market: '0 4px 24px -4px rgba(15, 118, 110, 0.15), 0 8px 16px -8px rgba(0, 0, 0, 0.08)',
            },
        },
    },

    plugins: [forms],
};
