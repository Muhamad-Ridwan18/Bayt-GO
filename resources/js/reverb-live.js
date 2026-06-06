/**
 * Utilitas Reverb — tanpa polling; halaman live wajib Echo aktif.
 */

export function csrfToken() {
    return document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') ?? '';
}

export function requireEcho(componentName) {
    if (!window.Echo) {
        console.error(
            `[Reverb] Echo tidak tersedia (${componentName}). ` +
                'Set BROADCAST_CONNECTION=reverb, jalankan `php artisan reverb:start`, dan build assets (VITE_REVERB_*).',
        );

        return false;
    }

    return true;
}

/**
 * @param {string} url
 * @returns {Promise<string|null>}
 */
/**
 * @param {string} url
 * @returns {Promise<object|null>}
 */
export async function fetchJson(url) {
    const r = await fetch(url, {
        headers: {
            Accept: 'application/json',
            'X-Requested-With': 'XMLHttpRequest',
            'X-CSRF-TOKEN': csrfToken(),
        },
        credentials: 'same-origin',
    });

    if (!r.ok) {
        return null;
    }

    return r.json();
}

export async function fetchHtmlFragment(url) {
    const r = await fetch(url, {
        headers: {
            Accept: 'text/html',
            'X-Requested-With': 'XMLHttpRequest',
            'X-CSRF-TOKEN': csrfToken(),
        },
        credentials: 'same-origin',
    });

    if (!r.ok) {
        return null;
    }

    return r.text();
}

/**
 * @param {HTMLElement|null} root
 * @param {string} html
 */
/**
 * @param {HTMLElement|null} grid
 * @param {string} html
 */
export function swapLiveParts(grid, html) {
    if (!grid) {
        return;
    }

    const wrapper = document.createElement('div');
    wrapper.innerHTML = html.trim();

    for (const part of wrapper.querySelectorAll('[data-live-part]')) {
        const name = part.getAttribute('data-live-part');
        if (!name) {
            continue;
        }

        const target = grid.querySelector(`[data-live-part="${name}"]`);
        if (!target) {
            continue;
        }

        if (typeof Alpine.destroyTree === 'function') {
            Alpine.destroyTree(target);
        }

        target.replaceWith(part);

        if (typeof Alpine.initTree === 'function') {
            Alpine.initTree(part);
        }
    }
}

export function swapAlpineHtml(root, html) {
    if (!root) {
        return;
    }

    if (typeof Alpine.destroyTree === 'function') {
        Alpine.destroyTree(root);
    }

    root.innerHTML = html;

    if (typeof Alpine.initTree === 'function') {
        Alpine.initTree(root);
    }
}

/**
 * @param {object} listener
 * @param {object} payload
 */
export function payloadMatches(listener, payload) {
    const match = listener.match;
    if (!match?.field || match.value === undefined || match.value === null) {
        return true;
    }

    const actual = payload?.[match.field];
    if (actual === undefined || actual === null) {
        return true;
    }

    return String(actual) === String(match.value);
}

/**
 * @param {Array<{ channel: string, event: string, match?: { field: string, value: string|number|null } }>} listeners
 * @param {(payload: object) => void} onMatch
 * @returns {string[]} channel names subscribed
 */
export function subscribePrivateListeners(listeners, onMatch) {
    if (!window.Echo) {
        return [];
    }

    const channels = [];

    for (const listener of listeners) {
        if (!listener?.channel || !listener?.event) {
            continue;
        }

        window.Echo.private(listener.channel).listen(listener.event, (payload) => {
            if (payloadMatches(listener, payload)) {
                onMatch(payload, listener);
            }
        });

        channels.push(listener.channel);
    }

    return channels;
}

/**
 * @param {() => void} fn
 * @param {number} waitMs
 * @returns {() => void}
 */
export function debounce(fn, waitMs = 500) {
    let timer = null;

    return () => {
        if (timer !== null) {
            window.clearTimeout(timer);
        }
        timer = window.setTimeout(() => {
            timer = null;
            fn();
        }, waitMs);
    };
}

/**
 * @param {string[]} channels
 */
export function leavePrivateChannels(channels) {
    if (!window.Echo) {
        return;
    }

    for (const name of channels) {
        if (name) {
            window.Echo.leave(name);
        }
    }
}

/**
 * @param {string} baseUrl
 * @param {string|null|undefined} afterId
 */
export function chatMessagesUrl(baseUrl, afterId) {
    if (!afterId) {
        return baseUrl;
    }

    const sep = baseUrl.includes('?') ? '&' : '?';

    return `${baseUrl}${sep}after_id=${encodeURIComponent(afterId)}`;
}

/**
 * @param {Array<{ id: string }>} existing
 * @param {Array<{ id: string }>} incoming
 */
/**
 * @param {string|null|undefined} readUrl
 */
export async function markChatRead(readUrl) {
    if (!readUrl) {
        return;
    }

    try {
        await fetch(readUrl, {
            method: 'POST',
            headers: {
                Accept: 'application/json',
                'X-CSRF-TOKEN': csrfToken(),
                'X-Requested-With': 'XMLHttpRequest',
            },
            credentials: 'same-origin',
        });
    } catch (error) {
        console.error(error);
    }
}

export function appendChatMessages(existing, incoming) {
    if (!incoming?.length) {
        return existing;
    }

    const known = new Set(existing.map((m) => m.id));
    const merged = [...existing];

    for (const message of incoming) {
        if (!known.has(message.id)) {
            merged.push(message);
            known.add(message.id);
        }
    }

    return merged;
}
