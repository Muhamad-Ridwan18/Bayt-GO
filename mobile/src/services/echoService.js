/**
 * Reverb WebSocket service untuk React Native.
 * Menggunakan native WebSocket API (tanpa pusher-js / laravel-echo)
 * karena library tersebut bermasalah dengan Metro bundler Expo.
 */

const REVERB_KEY   = '4bf06e9d51fcc619ec69';
const REVERB_HOST  = '192.168.1.44';
const REVERB_PORT  = 8081;
const API_BASE_URL = `http://${REVERB_HOST}:8001/api`;

let socketInstance = null;
let pingTimer      = null;
let _socketId      = null;
const _socketIdWaiters = [];
const subscribers  = {}; 

function sendRaw(data) {
  if (socketInstance?.readyState === WebSocket.OPEN) {
    socketInstance.send(JSON.stringify(data));
  }
}

async function getChannelAuth(token, channelName, socketId) {
  const resp = await fetch(`${API_BASE_URL}/broadcasting/auth`, {
    method: 'POST',
    headers: {
      'Content-Type': 'application/json',
      'Accept':        'application/json',
      'Authorization': `Bearer ${token}`,
    },
    body: JSON.stringify({ channel_name: channelName, socket_id: socketId }),
  });
  if (!resp.ok) throw new Error('Channel auth failed');
  return resp.json();
}

function resolveSocketId(id) {
  _socketId = id;
  while (_socketIdWaiters.length > 0) {
    const resolve = _socketIdWaiters.shift();
    resolve(id);
  }
}

function getSocketId() {
  if (_socketId) return Promise.resolve(_socketId);
  return new Promise((resolve) => _socketIdWaiters.push(resolve));
}

async function subscribeChannel(token, channelName, socketId) {
  if (!socketId) return;
  const isPrivate = channelName.startsWith('private-');

  let authData = {};
  if (isPrivate) {
    try {
      const auth = await getChannelAuth(token, channelName, socketId);
      authData = { auth: auth.auth };
    } catch (err) {
      console.warn('[Reverb] Auth gagal:', channelName, err.message);
      return;
    }
  }

  sendRaw({ event: 'pusher:subscribe', data: { channel: channelName, ...authData } });
}

// ─── Exported API ──────────────────────────────────────────────────────────

/**
 * Menghubungkan ke Reverb.
 */
export function connectPusher(token, { onConnected, onDisconnected, onError } = {}) {
  disconnectPusher();

  const url = `ws://${REVERB_HOST}:${REVERB_PORT}/app/${REVERB_KEY}?protocol=7&client=react-native&version=8.0.0&flash=false`;
  const ws  = new WebSocket(url);
  socketInstance = ws;

  ws.onopen = () => {
    pingTimer = setInterval(() => sendRaw({ event: 'pusher:ping', data: {} }), 30000);
  };

  ws.onmessage = async (e) => {
    let msg;
    try { msg = JSON.parse(e.data); } catch { return; }

    const { event, channel, data } = msg;

    if (event === 'pusher:connection_established') {
      const d = typeof data === 'string' ? JSON.parse(data) : data;
      resolveSocketId(d.socket_id);
      onConnected?.();

      // Re-subscribe existing channels
      for (const ch of Object.keys(subscribers)) {
        await subscribeChannel(token, ch, d.socket_id);
      }
      return;
    }

    if (event === 'pusher:pong') return;
    if (event === 'pusher:error') {
      onError?.(data);
      return;
    }

    // Handle broadcast events
    if (channel && subscribers[channel]) {
      subscribers[channel].forEach((cb) => cb(msg));
    }
  };

  ws.onerror = (err) => {
    console.warn('[Reverb] WebSocket Error:', err);
    onError?.(err);
  };

  ws.onclose = () => {
    clearInterval(pingTimer);
    onDisconnected?.();
  };

  // Mock object to match pusher-js API expected in ChatScreen
  return {
    connection: {
      bind: (evt, cb) => {
        if (evt === 'connected') onConnected = cb;
        if (evt === 'disconnected') onDisconnected = cb;
        if (evt === 'error') onError = cb;
      }
    }
  };
}

/**
 * Subscribe ke chat.
 */
export function subscribeToBookingChat(token, bookingId, onUpdate) {
  const channelName = `private-booking.chat.${bookingId}`;
  const eventName   = 'App\\Events\\BookingChatUpdated';

  const callback = (msg) => {
    if (msg.event === eventName || msg.event === 'BookingChatUpdated') {
      onUpdate(msg.data);
    }
  };

  if (!subscribers[channelName]) {
    subscribers[channelName] = [];
  }
  subscribers[channelName].push(callback);

  // Jika sudah connected, langsung subscribe
  getSocketId().then((sid) => {
    subscribeChannel(token, channelName, sid);
  });

  return {
    unsubscribe: () => {
      if (subscribers[channelName]) {
        subscribers[channelName] = subscribers[channelName].filter((c) => c !== callback);
        if (subscribers[channelName].length === 0) {
          sendRaw({ event: 'pusher:unsubscribe', data: { channel: channelName } });
          delete subscribers[channelName];
        }
      }
    },
  };
}

export function disconnectPusher() {
  clearInterval(pingTimer);
  pingTimer = null;
  _socketId = null;
  if (socketInstance) {
    try { socketInstance.close(); } catch (_) {}
    socketInstance = null;
  }
}
