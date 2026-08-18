import { useEffect, useState } from 'react';
import AsyncStorage from '@react-native-async-storage/async-storage';

export const LOCALE_KEY = '@baytgo_locale';
export const SUPPORTED_LOCALES = ['id', 'en'];
export const DEFAULT_LOCALE = 'id';

let cachedLocale = DEFAULT_LOCALE;
let hydrated = false;
const listeners = new Set();

export function normalizeLocale(raw) {
  const value = String(raw || '').toLowerCase().split('-')[0];
  return SUPPORTED_LOCALES.includes(value) ? value : null;
}

export async function getStoredLocale() {
  if (hydrated) return cachedLocale;
  const stored = normalizeLocale(await AsyncStorage.getItem(LOCALE_KEY));
  cachedLocale = stored || DEFAULT_LOCALE;
  hydrated = true;
  return cachedLocale;
}

export function getCachedLocale() {
  return cachedLocale || DEFAULT_LOCALE;
}

export async function setStoredLocale(raw) {
  const locale = normalizeLocale(raw) || DEFAULT_LOCALE;
  cachedLocale = locale;
  hydrated = true;
  await AsyncStorage.setItem(LOCALE_KEY, locale);
  listeners.forEach((fn) => fn(locale));
  return locale;
}

export function subscribeLocale(fn) {
  listeners.add(fn);
  return () => listeners.delete(fn);
}

export function useLocale() {
  const [locale, setLocale] = useState(getCachedLocale());

  useEffect(() => {
    getStoredLocale().then(setLocale);
    return subscribeLocale(setLocale);
  }, []);

  return locale;
}
