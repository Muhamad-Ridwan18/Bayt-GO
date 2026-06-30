import { useEffect } from 'react';
import * as Notifications from 'expo-notifications';
import { navigateToChatRoom } from './rootNavigation';
import { extractChatNavigationParams } from '../notifications/pushNotifications';

function openFromNotificationData(data) {
  const chatParams = extractChatNavigationParams(data);
  if (chatParams) {
    navigateToChatRoom(chatParams);
  }
}

export function useNotificationNavigation() {
  useEffect(() => {
    const openFromResponse = (response) => {
      const data = response?.notification?.request?.content?.data;
      openFromNotificationData(data);
    };

    Notifications.getLastNotificationResponseAsync().then((response) => {
      if (response) openFromResponse(response);
    });

    const subscription = Notifications.addNotificationResponseReceivedListener(openFromResponse);

    return () => subscription.remove();
  }, []);
}
