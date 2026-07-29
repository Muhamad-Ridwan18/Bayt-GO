import axios from 'axios';
window.axios = axios;

window.axios.defaults.headers.common['X-Requested-With'] = 'XMLHttpRequest';

const loginUrl = document.querySelector('meta[name="login-url"]')?.getAttribute('content') ?? '/login';
const homeUrl = document.querySelector('meta[name="home-url"]')?.getAttribute('content') ?? '/';

async function redirectFromSessionResponse(response) {
    if (response.redirected) {
        const target = response.url || loginUrl;
        if (target.includes('/login') || target.includes('/masuk')) {
            window.location.assign(target);

            return true;
        }
    }

    if (response.status === 401 || response.status === 419) {
        window.location.assign(loginUrl);

        return true;
    }

    if (response.status === 403) {
        try {
            const data = await response.clone().json();
            window.location.assign(data.redirect || homeUrl);
        } catch {
            window.location.assign(loginUrl);
        }

        return true;
    }

    return false;
}

const nativeFetch = window.fetch.bind(window);
window.fetch = async (...args) => {
    const response = await nativeFetch(...args);
    const init = args[1] ?? {};
    const headers = init.headers instanceof Headers
        ? init.headers
        : new Headers(init.headers ?? {});

    if (headers.get('X-Requested-With') === 'XMLHttpRequest') {
        await redirectFromSessionResponse(response);
    }

    return response;
};

window.axios.interceptors.response.use(
    (response) => response,
    async (error) => {
        if (error.response && await redirectFromSessionResponse(error.response)) {
            return new Promise(() => {});
        }

        return Promise.reject(error);
    },
);

/**
 * Echo/Reverb dimuat lazy lewat ensureEcho() di reverb-live.js — hindari Pusher + WS di setiap halaman.
 */
