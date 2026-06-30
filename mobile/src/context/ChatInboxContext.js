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
import { subscribeBookingChat, subscribeUserBookings } from '../realtime/pusherClient';

const ChatInboxContext = createContext(null);
const MAX_BOOKING_CHANNELS = 10;

function bookingIdsToSubscribe(conversations) {
  const ids = new Set();
  conversations
    .filter((c) => (c.unread_count || 0) > 0)
    .slice(0, 8)
    .forEach((c) => ids.add(String(c.booking_id)));
  conversations.slice(0, 5).forEach((c) => ids.add(String(c.booking_id)));
  return [...ids].slice(0, MAX_BOOKING_CHANNELS);
}

export function ChatInboxProvider({ children }) {
  const { token, user, isAuthenticated } = useAuth();
  const [conversations, setConversations] = useState([]);
  const [loading, setLoading] = useState(false);
  const [refreshing, setRefreshing] = useState(false);
  const [error, setError] = useState(null);

  const activeBookingIdRef = useRef(null);
  const channelCleanupsRef = useRef(new Map());
  const userChannelCleanupRef = useRef(null);
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
      setConversations(data.conversations || []);
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

  const scheduleRefresh = useCallback(() => {
    if (refreshTimerRef.current) clearTimeout(refreshTimerRef.current);
    refreshTimerRef.current = setTimeout(() => {
      refreshSilent();
    }, 400);
  }, [refreshSilent]);

  const setActiveBookingId = useCallback((bookingId) => {
    activeBookingIdRef.current = bookingId != null ? String(bookingId) : null;
  }, []);

  const clearUnreadForBooking = useCallback((bookingId) => {
    const id = String(bookingId);
    setConversations((prev) =>
      prev.map((c) => (String(c.booking_id) === id ? { ...c, unread_count: 0 } : c)),
    );
  }, []);

  const handleChatEvent = useCallback((bookingId, payload = {}) => {
    const id = String(bookingId);
    const action = payload?.action ?? 'message';
    const senderId = payload?.sender_id;

    if (senderId && user?.id && String(senderId) === String(user.id)) {
      return;
    }

    if (action === 'read') {
      return;
    }

    setConversations((prev) => {
      const idx = prev.findIndex((c) => String(c.booking_id) === id);
      if (idx === -1) {
        scheduleRefresh();
        return prev;
      }

      const next = [...prev];
      const conv = { ...next[idx] };
      const viewing = activeBookingIdRef.current === id;

      if (viewing) {
        conv.unread_count = 0;
      } else {
        conv.unread_count = (Number(conv.unread_count) || 0) + 1;
        conv.last_message = 'Pesan baru';
        conv.last_message_time = new Date().toISOString();
      }

      next.splice(idx, 1);
      next.unshift(conv);
      return next;
    });
  }, [user?.id, scheduleRefresh]);

  const syncBookingChannels = useCallback(async (items) => {
    if (!token) return;

    const nextIds = new Set(bookingIdsToSubscribe(items));
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
          handleChatEvent(id, payload);
        });
        current.set(id, cleanup);
      } catch {
        // polling fallback via manual refresh
      }
    }
  }, [token, handleChatEvent]);

  useEffect(() => {
    if (!isAuthenticated || !token) {
      setConversations([]);
      channelCleanupsRef.current.forEach((cleanup) => cleanup());
      channelCleanupsRef.current.clear();
      if (userChannelCleanupRef.current) {
        userChannelCleanupRef.current();
        userChannelCleanupRef.current = null;
      }
      return undefined;
    }

    refresh();

    if (user?.id) {
      subscribeUserBookings(token, user.id, () => {
        scheduleRefresh();
      }).then((cleanup) => {
        userChannelCleanupRef.current = cleanup;
      }).catch(() => {});
    }

    return () => {
      if (refreshTimerRef.current) clearTimeout(refreshTimerRef.current);
      channelCleanupsRef.current.forEach((cleanup) => cleanup());
      channelCleanupsRef.current.clear();
      if (userChannelCleanupRef.current) {
        userChannelCleanupRef.current();
        userChannelCleanupRef.current = null;
      }
    };
  }, [isAuthenticated, token, user?.id, refresh, scheduleRefresh]);

  useEffect(() => {
    if (!isAuthenticated || !token) return undefined;
    if (conversations.length === 0) {
      channelCleanupsRef.current.forEach((cleanup) => cleanup());
      channelCleanupsRef.current.clear();
      return undefined;
    }
    syncBookingChannels(conversations);
    return undefined;
  }, [conversations, isAuthenticated, token, syncBookingChannels]);

  useEffect(() => {
    Notifications.setBadgeCountAsync(unreadTotal).catch(() => {});
  }, [unreadTotal]);

  useEffect(() => {
    if (!isAuthenticated) return undefined;

    const subscription = Notifications.addNotificationReceivedListener(() => {
      scheduleRefresh();
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
