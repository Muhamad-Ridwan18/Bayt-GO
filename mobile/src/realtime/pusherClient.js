import { API_BASE_URL } from '../config/api';

let cachedConfig = null;
let pusherInstance = null;
let pusherToken = null;
/** @type {Map<string, number>} */
const channelSubscriberCounts = new Map();

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

async function authorizeChannel(token, authEndpoint, socketId, channelName) {
  const response = await fetch(authEndpoint, {
    method: 'POST',
    headers: {
      Accept: 'application/json',
      'Content-Type': 'application/json',
      Authorization: `Bearer ${token}`,
    },
    body: JSON.stringify({
      socket_id: socketId,
      channel_name: channelName,
    }),
  });

  const data = await response.json().catch(() => ({}));
  if (!response.ok) {
    throw new Error(data.message || `Auth channel gagal (${response.status})`);
  }
  return data;
}

export async function getPusherClient(token) {
  if (!token) return null;

  if (pusherInstance && pusherToken === token) {
    return pusherInstance;
  }

  if (pusherInstance) {
    pusherInstance.disconnect();
    pusherInstance = null;
    channelSubscriberCounts.clear();
  }

  const config = await fetchRealtimeConfig(token);
  cachedConfig = config;

  if (!config.enabled || !config.key) {
    return null;
  }

  const PusherModule = await import('pusher-js/react-native');
  const PusherClient = PusherModule.default || PusherModule;
  const host = resolveHost(config);
  const port = resolvePort(config);
  const scheme = resolveScheme(config);
  const useTLS = scheme === 'https';
  const authEndpoint = config.auth_endpoint || `${API_BASE_URL}/broadcasting/auth`;

  pusherInstance = new PusherClient(config.key, {
    wsHost: host,
    wsPort: useTLS ? port : port,
    wssPort: port,
    forceTLS: useTLS,
    enabledTransports: useTLS ? ['wss'] : ['ws', 'wss'],
    disableStats: true,
    cluster: '',
    authorizer: (channel) => ({
      authorize: (socketId, callback) => {
        authorizeChannel(token, authEndpoint, socketId, channel.name)
          .then((data) => callback(false, data))
          .catch((error) => callback(true, error));
      },
    }),
  });

  pusherToken = token;
  return pusherInstance;
}

export function disconnectPusher() {
  channelSubscriberCounts.clear();
  if (!pusherInstance) {
    cachedConfig = null;
    return;
  }
  pusherInstance.disconnect();
  pusherInstance = null;
  pusherToken = null;
  cachedConfig = null;
}

export async function subscribePrivateChannel(token, channelName, eventName, callback, hooks = {}) {
  const pusher = await getPusherClient(token);
  if (!pusher) return () => {};

  const fullName = channelName.startsWith('private-') ? channelName : `private-${channelName}`;
  const channel = pusher.subscribe(fullName);
  channelSubscriberCounts.set(fullName, (channelSubscriberCounts.get(fullName) || 0) + 1);

  const handler = (payload) => callback(payload);
  channel.bind(eventName, handler);

  const onConnected = () => hooks.onConnected?.();
  const onError = (status) => hooks.onError?.(status);

  channel.bind('pusher:subscription_succeeded', onConnected);
  channel.bind('pusher:subscription_error', onError);

  if (channel.subscriptionPending === false && channel.subscribed) {
    onConnected();
  }

  let released = false;
  return () => {
    if (released) return;
    released = true;

    channel.unbind(eventName, handler);
    channel.unbind('pusher:subscription_succeeded', onConnected);
    channel.unbind('pusher:subscription_error', onError);

    const nextCount = (channelSubscriberCounts.get(fullName) || 1) - 1;
    if (nextCount <= 0) {
      channelSubscriberCounts.delete(fullName);
      pusher.unsubscribe(fullName);
    } else {
      channelSubscriberCounts.set(fullName, nextCount);
    }
  };
}

export async function subscribeBookingChat(token, bookingId, callback, hooks = {}) {
  return subscribePrivateChannel(token, `booking.chat.${bookingId}`, 'chat.updated', callback, hooks);
}

export async function subscribeUserBookings(token, userId, callback, hooks = {}) {
  return subscribePrivateChannel(token, `App.Models.User.${userId}`, 'booking.updated', callback, hooks);
}

export function isRealtimeEnabled() {
  return Boolean(cachedConfig?.enabled);
}
