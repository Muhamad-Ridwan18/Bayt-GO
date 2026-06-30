import { useEffect, useRef } from 'react';
import { subscribeBookingChat } from '../realtime/pusherClient';

export function useBookingChatRealtime({ token, bookingId, onEvent, enabled = true }) {
  const onEventRef = useRef(onEvent);
  onEventRef.current = onEvent;

  useEffect(() => {
    if (!enabled || !token || !bookingId) return undefined;

    let cleanup = () => {};
    let cancelled = false;

    subscribeBookingChat(token, bookingId, (payload) => {
      onEventRef.current?.(payload);
    }).then((unsub) => {
      if (cancelled) {
        unsub();
      } else {
        cleanup = unsub;
      }
    }).catch(() => {
      // fallback polling handles offline reverb
    });

    return () => {
      cancelled = true;
      cleanup();
    };
  }, [token, bookingId, enabled]);
}
