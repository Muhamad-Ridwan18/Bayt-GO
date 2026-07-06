import { apiFetch } from './client';

export async function fetchBlockedDates(token) {
  return apiFetch('/muthowif/jadwal', { token });
}

export async function addBlockedDates(token, payload) {
  return apiFetch('/muthowif/jadwal', { token, method: 'POST', body: payload });
}

export async function removeBlockedDate(token, id) {
  return apiFetch(`/muthowif/jadwal/${id}`, { token, method: 'DELETE' });
}
