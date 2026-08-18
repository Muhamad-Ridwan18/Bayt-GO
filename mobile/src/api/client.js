import AsyncStorage from '@react-native-async-storage/async-storage';
import { API_BASE_URL } from '../config/api';

const LOCALE_KEY = '@baytgo_locale';
let cachedLocale = null;

async function getStoredLocale() {
  if (cachedLocale) return cachedLocale;
  const stored = await AsyncStorage.getItem(LOCALE_KEY);
  cachedLocale = stored || null;
  return cachedLocale;
}

export function clearApiLocaleCache() {
  cachedLocale = null;
}

export async function apiFetch(path, { token, method = 'GET', body, headers = {} } = {}) {
  const locale = await getStoredLocale();
  const requestHeaders = {
    Accept: 'application/json',
    ...(locale ? { 'Accept-Language': locale } : {}),
    ...headers,
  };

  if (token) {
    requestHeaders.Authorization = `Bearer ${token}`;
  }

  if (body && !(body instanceof FormData)) {
    requestHeaders['Content-Type'] = 'application/json';
  }

  const response = await fetch(`${API_BASE_URL}${path}`, {
    method,
    headers: requestHeaders,
    body: body instanceof FormData ? body : body ? JSON.stringify(body) : undefined,
  });

  const data = await response.json().catch(() => ({}));

  if (!response.ok) {
    let message = data.message;
    if (!message && data.errors) {
      message = Object.values(data.errors).flat().join('\n');
    }
    throw new Error(message || 'Permintaan gagal');
  }

  return data;
}
