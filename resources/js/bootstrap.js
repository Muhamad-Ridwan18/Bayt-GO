import axios from 'axios';
window.axios = axios;

window.axios.defaults.headers.common['X-Requested-With'] = 'XMLHttpRequest';

const loginUrl = document.querySelector('meta[name="login-url"]')?.getAttribute('content') ?? '/login';

function redirectToLoginFromResponse(response) {
    if (![401, 419].includes(response.status)) {
        return false;
    }

    window.location.assign(loginUrl);

    return true;
}

const nativeFetch = window.fetch.bind(window);
window.fetch = async (...args) => {
    const response = await nativeFetch(...args);
    const init = args[1] ?? {};
    const headers = init.headers instanceof Headers
        ? init.headers
        : new Headers(init.headers ?? {});

    if (headers.get('X-Requested-With') === 'XMLHttpRequest') {
        redirectToLoginFromResponse(response);
    }

    return response;
};

window.axios.interceptors.response.use(
    (response) => response,
    (error) => {
        if (error.response && redirectToLoginFromResponse(error.response)) {
            return new Promise(() => {});
        }

        return Promise.reject(error);
    },
);

/**
 * Echo/Reverb dimuat lazy lewat ensureEcho() di reverb-live.js — hindari Pusher + WS di setiap halaman.
 */
