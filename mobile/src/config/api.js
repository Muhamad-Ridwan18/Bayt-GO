export const API_BASE_URL = process.env.EXPO_PUBLIC_API_URL ?? 'http://10.0.2.2:8001/api';
export const WEB_BASE_URL = process.env.EXPO_PUBLIC_WEB_URL ?? API_BASE_URL.replace(/\/api\/?$/, '');
