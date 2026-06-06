import axios from 'axios';
window.axios = axios;

window.axios.defaults.headers.common['X-Requested-With'] = 'XMLHttpRequest';

/**
 * Echo/Reverb dimuat lazy lewat ensureEcho() di reverb-live.js — hindari Pusher + WS di setiap halaman.
 */
