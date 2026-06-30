import { useEffect, useRef } from 'react';
import { subscribeUserBookings } from '../realtime/pusherClient';

export function useUserBookingRealtime({ token, userId, onEvent, enabled = true }) {
  const onEventRef = useRef(onEvent);
  onEventRef.current = onEvent;

  useEffect(() => {
    if (!enabled || !token || !userId) return undefined;

    let cleanup = () => {};
    let cancelled = false;

    subscribeUserBookings(token, userId, (payload) => {
      onEventRef.current?.(payload);
    }).then((unsub) => {
      if (cancelled) {
        unsub();
      } else {
        cleanup = unsub;
      }
    }).catch(() => {});

    return () => {
      cancelled = true;
      cleanup();
    };
  }, [token, userId, enabled]);
}
