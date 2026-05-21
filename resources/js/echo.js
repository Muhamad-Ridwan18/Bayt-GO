import Echo from 'laravel-echo';

import Pusher from 'pusher-js';
window.Pusher = Pusher;

// Use Pusher broadcaster on the client and point it to the Reverb/ws server
const reverbHost = import.meta.env.VITE_REVERB_HOST || window.location.hostname;
const reverbPort = import.meta.env.VITE_REVERB_PORT ? Number(import.meta.env.VITE_REVERB_PORT) : undefined;
const reverbScheme = (import.meta.env.VITE_REVERB_SCHEME || (window.location.protocol === 'https:' ? 'https' : 'http'));

window.Echo = new Echo({
    broadcaster: 'reverb',
    key: import.meta.env.VITE_REVERB_APP_KEY,
    // Reverb-specific connection options (client uses these to connect to the Reverb/ws server)
    host: reverbHost,
    wsHost: reverbHost,
    wsPort: reverbPort ?? (reverbScheme === 'https' ? 443 : 80),
    wssPort: import.meta.env.VITE_REVERB_WSS_PORT ? Number(import.meta.env.VITE_REVERB_WSS_PORT) : (import.meta.env.VITE_REVERB_PORT ? Number(import.meta.env.VITE_REVERB_PORT) : 443),
    forceTLS: reverbScheme === 'https',
    enabledTransports: ['ws', 'wss'],
});
