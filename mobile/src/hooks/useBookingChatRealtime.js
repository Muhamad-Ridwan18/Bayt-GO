import { useEffect, useRef } from 'react';
import { subscribeBookingChat } from '../realtime/pusherClient';

export function useBookingChatRealtime({ token, bookingId, onEvent, onConnected, onError, enabled = true }) {
  const onEventRef = useRef(onEvent);
  onEventRef.current = onEvent;
  const onConnectedRef = useRef(onConnected);
  onConnectedRef.current = onConnected;
  const onErrorRef = useRef(onError);
  onErrorRef.current = onError;

  useEffect(() => {
    if (!enabled || !token || !bookingId) return undefined;

    let cleanup = () => {};
    let cancelled = false;

    subscribeBookingChat(
      token,
      bookingId,
      (payload) => {
        onEventRef.current?.(payload);
      },
      {
        onConnected: () => onConnectedRef.current?.(),
        onError: (status) => onErrorRef.current?.(status),
      },
    ).then((unsub) => {
      if (cancelled) {
        unsub();
      } else {
        cleanup = unsub;
      }
    }).catch((error) => {
      onErrorRef.current?.(error);
    });

    return () => {
      cancelled = true;
      cleanup();
    };
  }, [token, bookingId, enabled]);
}
