import { apiFetch } from './client';

function appendFile(formData, key, file) {
  if (!file) return;
  formData.append(key, {
    uri: file.uri,
    name: file.name || `${key}.jpg`,
    type: file.mimeType || file.type || 'image/jpeg',
  });
}

export async function createBooking(token, payload) {
  const formData = new FormData();
  formData.append('muthowif_profile_id', payload.profileId);
  formData.append('start_date', payload.startDate);
  formData.append('end_date', payload.endDate || payload.startDate);
  formData.append('service_type', payload.serviceType);
  formData.append('pilgrim_count', String(payload.pilgrimCount));
  formData.append('with_same_hotel', payload.withSameHotel ? '1' : '0');
  formData.append('with_transport', payload.withTransport ? '1' : '0');

  (payload.addOnIds || []).forEach((id, i) => {
    formData.append(`add_on_ids[${i}]`, id);
  });

  appendFile(formData, 'ticket_outbound', payload.ticketOutbound);
  appendFile(formData, 'ticket_return', payload.ticketReturn);
  appendFile(formData, 'passport', payload.passport);
  appendFile(formData, 'itinerary', payload.itinerary);
  appendFile(formData, 'visa', payload.visa);

  return apiFetch('/customer/bookings', { token, method: 'POST', body: formData });
}

export async function fetchBookings(token) {
  return apiFetch('/customer/bookings', { token });
}

export async function fetchBooking(token, bookingId) {
  return apiFetch(`/customer/bookings/${bookingId}`, { token });
}

export async function fetchPaymentMethods(token, bookingId) {
  return apiFetch(`/customer/bookings/${bookingId}/pay`, {
    token,
    method: 'POST',
    body: { method: '' },
  });
}

export async function initiatePayment(token, bookingId, method) {
  return apiFetch(`/customer/bookings/${bookingId}/pay`, {
    token,
    method: 'POST',
    body: { method },
  });
}

export async function fetchInvoice(token, bookingId) {
  return apiFetch(`/customer/bookings/${bookingId}/invoice`, { token });
}

export async function submitReview(token, bookingId, { rating, comment }) {
  return apiFetch(`/customer/bookings/${bookingId}/review`, {
    token,
    method: 'POST',
    body: { rating, comment },
  });
}

export async function completeBooking(token, bookingId, { rating, comment }) {
  return apiFetch(`/customer/bookings/${bookingId}/complete`, {
    token,
    method: 'POST',
    body: { rating, review: comment },
  });
}

export async function cancelBooking(token, bookingId) {
  return apiFetch(`/customer/bookings/${bookingId}/cancel`, {
    token,
    method: 'POST',
  });
}

export async function submitRefundRequest(token, bookingId, payload) {
  return apiFetch(`/customer/bookings/${bookingId}/refund-request`, {
    token,
    method: 'POST',
    body: payload,
  });
}

export async function submitRescheduleRequest(token, bookingId, payload) {
  return apiFetch(`/customer/bookings/${bookingId}/reschedule-request`, {
    token,
    method: 'POST',
    body: payload,
  });
}

export async function requestSupportCompletion(token, bookingId) {
  return apiFetch(`/customer/bookings/${bookingId}/support-completion-request`, {
    token,
    method: 'POST',
  });
}
