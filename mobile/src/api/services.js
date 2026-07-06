import { apiFetch } from './client';

export async function fetchServices(token) {
  return apiFetch('/muthowif/services', { token });
}

export async function updateService(token, id, payload) {
  return apiFetch(`/muthowif/services/${id}`, { token, method: 'PUT', body: payload });
}
