import { useEffect, useRef } from 'react';
import { InteractionManager } from 'react-native';
import * as Notifications from 'expo-notifications';
import { useAuth } from '../context/AuthContext';
import {
  navigateToChatRoom,
  flushPendingChatNavigation,
  queueChatNavigation,
} from '../navigation/rootNavigation';
import { extractChatNavigationParams } from './pushNotifications';

function scheduleChatNavigation(chatParams) {
  InteractionManager.runAfterInteractions(() => {
    navigateToChatRoom(chatParams);
  });
}

export function useNotificationNavigation() {
  const { booting, isAuthenticated } = useAuth();
  const handledColdStartRef = useRef(false);

  useEffect(() => {
    if (booting) return undefined;

    const openFromResponse = (response) => {
      const data = response?.notification?.request?.content?.data;
      const chatParams = extractChatNavigationParams(data);
      if (!chatParams) return;

      if (!isAuthenticated) {
        queueChatNavigation(chatParams);
        return;
      }

      scheduleChatNavigation(chatParams);
    };

    if (!handledColdStartRef.current) {
      handledColdStartRef.current = true;
      Notifications.getLastNotificationResponseAsync().then((response) => {
        if (response) openFromResponse(response);
      });
    }

    const subscription = Notifications.addNotificationResponseReceivedListener(openFromResponse);

    return () => subscription.remove();
  }, [booting, isAuthenticated]);

  useEffect(() => {
    if (booting || !isAuthenticated) return undefined;

    const task = InteractionManager.runAfterInteractions(() => {
      flushPendingChatNavigation();
    });

    return () => task.cancel();
  }, [booting, isAuthenticated]);
}
