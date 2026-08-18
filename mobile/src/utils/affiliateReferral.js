import { useEffect, useState } from 'react';
import { Linking } from 'react-native';
import AsyncStorage from '@react-native-async-storage/async-storage';
import { captureAffiliateClick } from '../api/affiliate';

const REF_KEY = 'affiliate_ref';
const VISITOR_KEY = 'affiliate_visitor_key';

export function normalizeAffiliateCode(raw) {
  if (!raw) return null;
  const normalized = String(raw).toUpperCase().replace(/[^A-Z0-9]/g, '');
  if (normalized.length < 3 || normalized.length > 32) return null;
  return normalized;
}

export function parseAffiliateCodeFromUrl(url) {
  if (!url) return null;
  const text = String(url);

  const query = text.match(/[?&#]ref=([A-Za-z0-9]{3,32})/i);
  if (query) return normalizeAffiliateCode(query[1]);

  const path = text.match(/\/r\/([A-Za-z0-9]{3,32})(?:[/?#]|$)/i)
    || text.match(/:\/\/r\/([A-Za-z0-9]{3,32})(?:[/?#]|$)/i);
  if (path) return normalizeAffiliateCode(path[1]);

  return null;
}

export async function getAffiliateVisitorKey() {
  let key = await AsyncStorage.getItem(VISITOR_KEY);
  if (!key) {
    key = `${Date.now().toString(36)}${Math.random().toString(36).slice(2, 12)}`;
    await AsyncStorage.setItem(VISITOR_KEY, key);
  }
  return key;
}

export async function rememberAffiliateCode(code) {
  const normalized = normalizeAffiliateCode(code);
  if (!normalized) return null;
  await AsyncStorage.setItem(REF_KEY, normalized);
  return normalized;
}

export async function getStoredAffiliateCode() {
  return normalizeAffiliateCode(await AsyncStorage.getItem(REF_KEY));
}

export async function clearAffiliateCode() {
  await AsyncStorage.removeItem(REF_KEY);
}

export async function captureAffiliateFromUrl(url) {
  const code = parseAffiliateCodeFromUrl(url);
  if (!code) return null;
  await rememberAffiliateCode(code);
  try {
    const data = await captureAffiliateClick({
      code,
      visitor_key: await getAffiliateVisitorKey(),
      landing_path: String(url).slice(0, 512),
    });
    if (data?.code) await rememberAffiliateCode(data.code);
  } catch {
    // keep local code even if click tracking fails
  }
  return code;
}

export function listenAffiliateLinks() {
  Linking.getInitialURL().then((url) => {
    if (url) captureAffiliateFromUrl(url);
  }).catch(() => {});

  const sub = Linking.addEventListener('url', ({ url }) => {
    if (url) captureAffiliateFromUrl(url);
  });

  return () => sub.remove();
}

export function useAffiliateReferralCode(routeCode) {
  const [code, setCode] = useState(() => normalizeAffiliateCode(routeCode) || '');

  useEffect(() => {
    let cancelled = false;
    (async () => {
      const fromRoute = normalizeAffiliateCode(routeCode);
      if (fromRoute) {
        await rememberAffiliateCode(fromRoute);
        if (!cancelled) setCode(fromRoute);
        return;
      }
      const stored = await getStoredAffiliateCode();
      if (!cancelled && stored) setCode(stored);
    })();
    return () => { cancelled = true; };
  }, [routeCode]);

  const onChange = (value) => {
    const next = String(value || '').toUpperCase();
    setCode(next);
    if (!next.trim()) {
      clearAffiliateCode();
      return;
    }
    rememberAffiliateCode(next);
  };

  return [code, onChange];
}
