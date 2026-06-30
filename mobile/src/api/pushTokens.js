import { apiFetch } from './client';

export async function registerPushToken(token, payload) {
  return apiFetch('/push-tokens', {
    token,
    method: 'POST',
    body: JSON.stringify(payload),
  });
}

export async function unregisterPushToken(token, expoToken) {
  return apiFetch('/push-tokens', {
    token,
    method: 'DELETE',
    body: JSON.stringify({ token: expoToken }),
  });
}
