import { apiFetch } from './client';

export async function fetchMuthowifBookings(token) {
  return apiFetch('/muthowif/bookings', { token });
}

export async function fetchMuthowifBooking(token, bookingId) {
  return apiFetch(`/muthowif/bookings/${bookingId}`, { token });
}

export async function confirmMuthowifBooking(token, bookingId) {
  return apiFetch(`/muthowif/bookings/${bookingId}/confirm`, { token, method: 'POST' });
}

export async function cancelMuthowifBooking(token, bookingId, payload = {}) {
  return apiFetch(`/muthowif/bookings/${bookingId}/cancel`, {
    token,
    method: 'POST',
    body: payload,
  });
}

export async function approveReschedule(token, bookingId, rescheduleId, note) {
  return apiFetch(`/muthowif/bookings/${bookingId}/reschedule-requests/${rescheduleId}/approve`, {
    token,
    method: 'POST',
    body: note ? { muthowif_note: note } : {},
  });
}

export async function rejectReschedule(token, bookingId, rescheduleId, note) {
  return apiFetch(`/muthowif/bookings/${bookingId}/reschedule-requests/${rescheduleId}/reject`, {
    token,
    method: 'POST',
    body: note ? { muthowif_note: note } : {},
  });
}

export async function completeSupportWithCode(token, bookingId, code) {
  return apiFetch(`/muthowif/bookings/${bookingId}/support-completion/code`, {
    token,
    method: 'POST',
    body: { code },
  });
}

export async function resendSupportCompletionCode(token, bookingId) {
  return apiFetch(`/muthowif/bookings/${bookingId}/support-completion/resend-code`, {
    token,
    method: 'POST',
  });
}
