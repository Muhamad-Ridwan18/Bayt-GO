import { Platform } from 'react-native';
import * as Device from 'expo-device';
import * as Notifications from 'expo-notifications';
import Constants, { ExecutionEnvironment } from 'expo-constants';
import { registerPushToken, unregisterPushToken } from '../api/pushTokens';

const isExpoGo = Constants.executionEnvironment === ExecutionEnvironment.StoreClient;

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

export function getPushEnvironmentInfo() {
  return {
    isDevice: Device.isDevice,
    isExpoGo,
    executionEnvironment: Constants.executionEnvironment,
    platform: Platform.OS,
    modelName: Device.modelName || null,
    projectId:
      Constants.expoConfig?.extra?.eas?.projectId
      ?? Constants.easConfig?.projectId
      ?? null,
    appOwnership: Constants.appOwnership,
  };
}

function logPushDebug(message, extra) {
  if (__DEV__) {
    console.warn(`[push] ${message}`, extra ?? '');
  }
}

export async function ensureAndroidDefaultChannel() {
  if (Platform.OS !== 'android') return;
  await Notifications.setNotificationChannelAsync('default', {
    name: 'default',
    importance: Notifications.AndroidImportance.MAX,
    vibrationPattern: [0, 250, 250, 250],
  });
}

export async function requestNotificationPermissionAsync() {
  const { status: existingStatus } = await Notifications.getPermissionsAsync();
  if (existingStatus === 'granted') return existingStatus;
  const { status } = await Notifications.requestPermissionsAsync();
  return status;
}

/**
 * Local notification — works on emulator & Expo Go (no remote/FCM).
 * Use this to test banner UI + tap → chat navigation.
 */
export async function scheduleLocalTestNotification({
  title = 'BaytGo Test',
  body = 'Ini notifikasi lokal untuk testing.',
  seconds = 2,
  data = null,
} = {}) {
  await ensureAndroidDefaultChannel();
  const permission = await requestNotificationPermissionAsync();
  if (permission !== 'granted') {
    throw new Error(`Izin notifikasi: ${permission}`);
  }

  const id = await Notifications.scheduleNotificationAsync({
    content: {
      title,
      body,
      sound: true,
      data: data || {
        type: 'chat',
        booking_id: '0',
        booking_code: 'TEST-LOCAL',
        other_name: 'Push Test',
      },
    },
    trigger: {
      type: Notifications.SchedulableTriggerInputTypes.TIME_INTERVAL,
      seconds: Math.max(1, Number(seconds) || 2),
      channelId: 'default',
    },
  });

  return id;
}

export async function registerForPushNotificationsAsync({ allowEmulator = __DEV__ } = {}) {
  if (!Device.isDevice && !allowEmulator) {
    logPushDebug('skipped: simulator/emulator');
    return null;
  }

  if (isExpoGo) {
    logPushDebug('Expo Go SDK 53+ tidak mendukung remote push — gunakan development build (eas build)');
    return null;
  }

  await ensureAndroidDefaultChannel();

  const finalStatus = await requestNotificationPermissionAsync();
  if (finalStatus !== 'granted') {
    logPushDebug('skipped: notification permission not granted', finalStatus);
    return null;
  }

  const projectId =
    Constants.expoConfig?.extra?.eas?.projectId
    ?? Constants.easConfig?.projectId;

  if (!projectId) {
    logPushDebug('skipped: EAS projectId missing in app.json');
    return null;
  }

  try {
    const tokenResponse = await Notifications.getExpoPushTokenAsync({ projectId });
    cachedExpoPushToken = tokenResponse.data;
    logPushDebug('token obtained', cachedExpoPushToken);
    return cachedExpoPushToken;
  } catch (error) {
    logPushDebug('getExpoPushTokenAsync failed', error?.message ?? error);
    return null;
  }
}

export async function syncPushTokenWithBackend(authToken) {
  if (!authToken) return null;

  const expoToken = await registerForPushNotificationsAsync();
  if (!expoToken) return null;

  try {
    await registerPushToken(authToken, {
      token: expoToken,
      platform: Platform.OS,
      device_name: Device.modelName || Platform.OS,
    });
    logPushDebug('token registered to backend');
  } catch (error) {
    logPushDebug('backend registration failed', error?.message ?? error);
    throw error;
  }

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
