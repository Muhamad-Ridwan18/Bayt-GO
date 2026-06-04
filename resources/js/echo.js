import Echo from 'laravel-echo';

import Pusher from 'pusher-js';
window.Pusher = Pusher;

// Use Pusher broadcaster on the client and point it to the Reverb/ws server
const reverbHost = import.meta.env.VITE_REVERB_HOST || window.location.hostname;
const reverbPort = import.meta.env.VITE_REVERB_PORT ? Number(import.meta.env.VITE_REVERB_PORT) : undefined;
const reverbScheme = (import.meta.env.VITE_REVERB_SCHEME || (window.location.protocol === 'https:' ? 'https' : 'http'));

const reverbKey = import.meta.env.VITE_REVERB_APP_KEY;

if (reverbKey) {
    window.Echo = new Echo({
        broadcaster: 'reverb',
        key: reverbKey,
        host: reverbHost,
        wsHost: reverbHost,
        wsPort: reverbPort ?? (reverbScheme === 'https' ? 443 : 80),
        wssPort: import.meta.env.VITE_REVERB_WSS_PORT
            ? Number(import.meta.env.VITE_REVERB_WSS_PORT)
            : (import.meta.env.VITE_REVERB_PORT ? Number(import.meta.env.VITE_REVERB_PORT) : 443),
        forceTLS: reverbScheme === 'https',
        enabledTransports: ['ws', 'wss'],
    });
} else {
    console.error(
        '[Reverb] VITE_REVERB_APP_KEY tidak diset — realtime nonaktif. Set REVERB_* di .env lalu `npm run build`.',
    );
}
