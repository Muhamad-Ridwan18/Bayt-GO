import React, {
  createContext,
  useCallback,
  useContext,
  useEffect,
  useMemo,
  useRef,
  useState,
} from 'react';
import * as Notifications from 'expo-notifications';
import { fetchConversations } from '../api/chat';
import { useAuth } from './AuthContext';
import {
  subscribeBookingChat,
  subscribePrivateChannel,
  subscribeUserBookings,
} from '../realtime/pusherClient';

const ChatInboxContext = createContext(null);
const MAX_BOOKING_CHANNELS = 25;
const OPTIMISTIC_UNREAD_HOLD_MS = 8000;

function bookingIdsToSubscribe(conversations, activeBookingId) {
  const ids = new Set();
  if (activeBookingId) {
    ids.add(String(activeBookingId));
  }
  conversations.forEach((c) => ids.add(String(c.booking_id)));
  return [...ids].slice(0, MAX_BOOKING_CHANNELS);
}

function mergeConversations(prev, fromApi, activeBookingId) {
  const prevById = new Map(prev.map((c) => [String(c.booking_id), c]));
  const now = Date.now();

  return fromApi.map((api) => {
    const id = String(api.booking_id);
    const local = prevById.get(id);
    if (!local) {
      return api;
    }

    if (activeBookingId && id === String(activeBookingId)) {
      return { ...api, unread_count: 0 };
    }

    const localUnread = Number(local.unread_count) || 0;
    const apiUnread = Number(api.unread_count) || 0;
    const localTs = local.last_message_time ? new Date(local.last_message_time).getTime() : 0;
    const holdOptimistic = localUnread > apiUnread
      && localTs > 0
      && (now - localTs) < OPTIMISTIC_UNREAD_HOLD_MS;

    if (holdOptimistic) {
      return {
        ...api,
        unread_count: localUnread,
        last_message: local.last_message || api.last_message,
        last_message_time: local.last_message_time || api.last_message_time,
      };
    }

    return api;
  });
}

export function ChatInboxProvider({ children }) {
  const { token, user, isAuthenticated } = useAuth();
  const [conversations, setConversations] = useState([]);
  const [loading, setLoading] = useState(false);
  const [refreshing, setRefreshing] = useState(false);
  const [error, setError] = useState(null);

  const activeBookingIdRef = useRef(null);
  const [activeBookingId, setActiveBookingIdState] = useState(null);
  const channelCleanupsRef = useRef(new Map());
  const userChannelCleanupsRef = useRef([]);
  const refreshTimerRef = useRef(null);

  const unreadTotal = useMemo(
    () => conversations.reduce((sum, c) => sum + (Number(c.unread_count) || 0), 0),
    [conversations],
  );

  const refresh = useCallback(async (silent = false) => {
    if (!token) return;
    if (!silent) setLoading(true);
    try {
      const data = await fetchConversations(token);
      const fromApi = data.conversations || [];
      setConversations((prev) => mergeConversations(prev, fromApi, activeBookingIdRef.current));
      setError(null);
    } catch (err) {
      setError(err.message || 'Gagal memuat chat');
      if (!silent) setConversations([]);
    } finally {
      if (!silent) setLoading(false);
      setRefreshing(false);
    }
  }, [token]);

  const refreshSilent = useCallback(() => refresh(true), [refresh]);

  const scheduleRefresh = useCallback((delayMs = 1200) => {
    if (refreshTimerRef.current) clearTimeout(refreshTimerRef.current);
    refreshTimerRef.current = setTimeout(() => {
      refreshSilent();
    }, delayMs);
  }, [refreshSilent]);

  const setActiveBookingId = useCallback((bookingId) => {
    const next = bookingId != null ? String(bookingId) : null;
    activeBookingIdRef.current = next;
    setActiveBookingIdState(next);
  }, []);

  const clearUnreadForBooking = useCallback((bookingId) => {
    const id = String(bookingId);
    setConversations((prev) =>
      prev.map((c) => (String(c.booking_id) === id ? { ...c, unread_count: 0 } : c)),
    );
  }, []);

  const handleChatEvent = useCallback((bookingId, payload = {}) => {
    const id = String(bookingId || payload?.booking_id || '');
    if (!id) return;

    const action = payload?.action ?? 'message';
    const senderId = payload?.sender_id;

    if (senderId && user?.id && String(senderId) === String(user.id)) {
      return;
    }

    if (action === 'read') {
      return;
    }

    const viewing = activeBookingIdRef.current === id;
    const preview = typeof payload?.preview === 'string' && payload.preview.trim() !== ''
      ? payload.preview.trim()
      : 'Pesan baru';

    setConversations((prev) => {
      const idx = prev.findIndex((c) => String(c.booking_id) === id);
      if (idx === -1) {
        scheduleRefresh(300);
        return prev;
      }

      const next = [...prev];
      const conv = { ...next[idx] };

      if (viewing) {
        conv.unread_count = 0;
        conv.last_message = preview;
        conv.last_message_time = new Date().toISOString();
      } else {
        conv.unread_count = (Number(conv.unread_count) || 0) + 1;
        conv.last_message = preview;
        conv.last_message_time = new Date().toISOString();
      }

      next.splice(idx, 1);
      next.unshift(conv);
      return next;
    });

    // Soft sync only — mergeConversations keeps optimistic unread for a few seconds.
    if (!viewing) {
      scheduleRefresh(1500);
    }
  }, [user?.id, scheduleRefresh]);

  const handleChatEventRef = useRef(handleChatEvent);
  handleChatEventRef.current = handleChatEvent;

  const syncBookingChannels = useCallback(async (items, activeId) => {
    if (!token) return;

    const nextIds = new Set(bookingIdsToSubscribe(items, activeId));
    const current = channelCleanupsRef.current;

    for (const [id, cleanup] of [...current.entries()]) {
      if (!nextIds.has(id)) {
        cleanup();
        current.delete(id);
      }
    }

    for (const id of nextIds) {
      if (current.has(id)) continue;
      try {
        const cleanup = await subscribeBookingChat(token, id, (payload) => {
          handleChatEventRef.current(id, payload);
        });
        current.set(id, cleanup);
      } catch {
        // user channel covers inbox; booking channel is best-effort
      }
    }
  }, [token]);

  useEffect(() => {
    if (!isAuthenticated || !token) {
      setConversations([]);
      channelCleanupsRef.current.forEach((cleanup) => cleanup());
      channelCleanupsRef.current.clear();
      userChannelCleanupsRef.current.forEach((cleanup) => cleanup());
      userChannelCleanupsRef.current = [];
      return undefined;
    }

    refresh();

    if (user?.id) {
      userChannelCleanupsRef.current = [];

      subscribeUserBookings(token, user.id, () => {
        scheduleRefresh(400);
      }).then((cleanup) => {
        userChannelCleanupsRef.current.push(cleanup);
      }).catch(() => {});

      // Inbox should update even if booking.chat.* is not subscribed.
      subscribePrivateChannel(
        token,
        `App.Models.User.${user.id}`,
        'chat.updated',
        (payload) => {
          handleChatEventRef.current(payload?.booking_id, payload);
        },
      ).then((cleanup) => {
        userChannelCleanupsRef.current.push(cleanup);
      }).catch(() => {});
    }

    return () => {
      if (refreshTimerRef.current) clearTimeout(refreshTimerRef.current);
      channelCleanupsRef.current.forEach((cleanup) => cleanup());
      channelCleanupsRef.current.clear();
      userChannelCleanupsRef.current.forEach((cleanup) => cleanup());
      userChannelCleanupsRef.current = [];
    };
  }, [isAuthenticated, token, user?.id, refresh, scheduleRefresh]);

  useEffect(() => {
    if (!isAuthenticated || !token) return undefined;
    if (conversations.length === 0 && !activeBookingId) {
      channelCleanupsRef.current.forEach((cleanup) => cleanup());
      channelCleanupsRef.current.clear();
      return undefined;
    }
    syncBookingChannels(conversations, activeBookingId);
    return undefined;
  }, [conversations, activeBookingId, isAuthenticated, token, syncBookingChannels]);

  useEffect(() => {
    Notifications.setBadgeCountAsync(unreadTotal).catch(() => {});
  }, [unreadTotal]);

  useEffect(() => {
    if (!isAuthenticated) return undefined;

    const subscription = Notifications.addNotificationReceivedListener(() => {
      scheduleRefresh(400);
    });

    return () => subscription.remove();
  }, [isAuthenticated, scheduleRefresh]);

  const pullToRefresh = useCallback(async () => {
    setRefreshing(true);
    await refresh(true);
  }, [refresh]);

  const value = useMemo(
    () => ({
      conversations,
      loading,
      refreshing,
      error,
      unreadTotal,
      refresh,
      pullToRefresh,
      setActiveBookingId,
      clearUnreadForBooking,
    }),
    [
      conversations,
      loading,
      refreshing,
      error,
      unreadTotal,
      refresh,
      pullToRefresh,
      setActiveBookingId,
      clearUnreadForBooking,
    ],
  );

  return <ChatInboxContext.Provider value={value}>{children}</ChatInboxContext.Provider>;
}

export function useChatInbox() {
  const ctx = useContext(ChatInboxContext);
  if (!ctx) throw new Error('useChatInbox must be used within ChatInboxProvider');
  return ctx;
}
