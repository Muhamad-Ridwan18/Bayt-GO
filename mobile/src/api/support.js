import { apiFetch } from './client';
import { buildSupportTicketFormData, buildSupportReplyFormData } from '../utils/formData';

export async function fetchSupportMeta(token) {
  return apiFetch('/support/tickets/meta', { token });
}

export async function fetchSupportTickets(token, page = 1) {
  return apiFetch(`/support/tickets?page=${page}`, { token });
}

export async function fetchSupportTicket(token, id) {
  return apiFetch(`/support/tickets/${id}`, { token });
}

export async function createSupportTicket(token, payload) {
  const body = payload instanceof FormData
    ? payload
    : buildSupportTicketFormData(payload);
  return apiFetch('/support/tickets', { token, method: 'POST', body });
}

export async function replySupportTicket(token, id, payload) {
  const body = payload instanceof FormData
    ? payload
    : buildSupportReplyFormData(payload);
  return apiFetch(`/support/tickets/${id}/reply`, { token, method: 'POST', body });
}
