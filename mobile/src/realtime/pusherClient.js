import { API_BASE_URL } from '../config/api';

let cachedConfig = null;
let pusherInstance = null;
let pusherToken = null;

export async function fetchRealtimeConfig(token) {
  const response = await fetch(`${API_BASE_URL}/realtime/config`, {
    headers: {
      Accept: 'application/json',
      Authorization: `Bearer ${token}`,
    },
  });
  const data = await response.json().catch(() => ({}));
  if (!response.ok) {
    throw new Error(data.message || 'Gagal memuat konfigurasi realtime');
  }
  return data;
}

function resolveHost(config) {
  const override = process.env.EXPO_PUBLIC_REVERB_HOST;
  if (override) return override;
  return config.host;
}

function resolvePort(config) {
  const override = process.env.EXPO_PUBLIC_REVERB_PORT;
  if (override) return Number(override);
  return config.port;
}

function resolveScheme(config) {
  const override = process.env.EXPO_PUBLIC_REVERB_SCHEME;
  if (override) return override;
  return config.scheme || 'http';
}

export async function getPusherClient(token) {
  if (!token) return null;

  if (pusherInstance && pusherToken === token) {
    return pusherInstance;
  }

  if (pusherInstance) {
    pusherInstance.disconnect();
    pusherInstance = null;
  }

  const config = await fetchRealtimeConfig(token);
  cachedConfig = config;

  if (!config.enabled || !config.key) {
    return null;
  }

  const PusherModule = await import('pusher-js');
  const PusherClient = PusherModule.default || PusherModule;
  const host = resolveHost(config);
  const port = resolvePort(config);
  const scheme = resolveScheme(config);
  const useTLS = scheme === 'https';

  pusherInstance = new PusherClient(config.key, {
    wsHost: host,
    wsPort: port,
    wssPort: port,
    forceTLS: useTLS,
    enabledTransports: ['ws', 'wss'],
    cluster: 'mt1',
    authEndpoint: config.auth_endpoint || `${API_BASE_URL}/broadcasting/auth`,
    auth: {
      headers: {
        Authorization: `Bearer ${token}`,
        Accept: 'application/json',
      },
    },
  });

  pusherToken = token;
  return pusherInstance;
}

export function disconnectPusher() {
  if (!pusherInstance) {
    cachedConfig = null;
    return;
  }
  pusherInstance.disconnect();
  pusherInstance = null;
  pusherToken = null;
  cachedConfig = null;
}

export async function subscribePrivateChannel(token, channelName, eventName, callback) {
  const pusher = await getPusherClient(token);
  if (!pusher) return () => {};

  const fullName = channelName.startsWith('private-') ? channelName : `private-${channelName}`;
  const channel = pusher.subscribe(fullName);

  const handler = (payload) => callback(payload);
  channel.bind(eventName, handler);

  return () => {
    channel.unbind(eventName, handler);
    pusher.unsubscribe(fullName);
  };
}

export async function subscribeBookingChat(token, bookingId, callback) {
  return subscribePrivateChannel(token, `booking.chat.${bookingId}`, 'chat.updated', callback);
}

export async function subscribeUserBookings(token, userId, callback) {
  return subscribePrivateChannel(token, `App.Models.User.${userId}`, 'booking.updated', callback);
}

export function isRealtimeEnabled() {
  return Boolean(cachedConfig?.enabled);
}
