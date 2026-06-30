import { Platform } from 'react-native';
import * as Device from 'expo-device';
import * as Notifications from 'expo-notifications';
import Constants from 'expo-constants';
import { registerPushToken, unregisterPushToken } from '../api/pushTokens';

Notifications.setNotificationHandler({
  handleNotification: async () => ({
    shouldShowAlert: true,
    shouldPlaySound: true,
    shouldSetBadge: true,
  }),
});

let cachedExpoPushToken = null;

export function getCachedExpoPushToken() {
  return cachedExpoPushToken;
}

export async function registerForPushNotificationsAsync() {
  if (!Device.isDevice) {
    return null;
  }

  if (Platform.OS === 'android') {
    await Notifications.setNotificationChannelAsync('default', {
      name: 'default',
      importance: Notifications.AndroidImportance.MAX,
      vibrationPattern: [0, 250, 250, 250],
    });
  }

  const { status: existingStatus } = await Notifications.getPermissionsAsync();
  let finalStatus = existingStatus;
  if (existingStatus !== 'granted') {
    const { status } = await Notifications.requestPermissionsAsync();
    finalStatus = status;
  }

  if (finalStatus !== 'granted') {
    return null;
  }

  const projectId =
    Constants.expoConfig?.extra?.eas?.projectId
    ?? Constants.easConfig?.projectId;

  const tokenResponse = projectId
    ? await Notifications.getExpoPushTokenAsync({ projectId })
    : await Notifications.getExpoPushTokenAsync();

  cachedExpoPushToken = tokenResponse.data;
  return cachedExpoPushToken;
}

export async function syncPushTokenWithBackend(authToken) {
  if (!authToken) return null;

  const expoToken = await registerForPushNotificationsAsync();
  if (!expoToken) return null;

  await registerPushToken(authToken, {
    token: expoToken,
    platform: Platform.OS,
    device_name: Device.modelName || Platform.OS,
  });

  return expoToken;
}

export async function removePushTokenFromBackend(authToken) {
  const expoToken = cachedExpoPushToken;
  if (!authToken || !expoToken) return;

  try {
    await unregisterPushToken(authToken, expoToken);
  } catch {
    // ignore logout cleanup errors
  } finally {
    cachedExpoPushToken = null;
  }
}

export function extractChatNavigationParams(data) {
  if (!data || data.type !== 'chat' || !data.booking_id) {
    return null;
  }

  return {
    bookingId: data.booking_id,
    bookingCode: data.booking_code || '--',
    otherName: data.other_name || 'Chat',
  };
}
