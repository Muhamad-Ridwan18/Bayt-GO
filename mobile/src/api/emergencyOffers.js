import { apiFetch } from './client';

export async function fetchEmergencyOffers(token, page = 1) {
  return apiFetch(`/muthowif/emergency-offers?page=${page}`, { token });
}

export async function acceptEmergencyOffer(token, offerId) {
  return apiFetch(`/muthowif/emergency-offers/${offerId}/accept`, { token, method: 'POST' });
}

export async function declineEmergencyOffer(token, offerId, note) {
  return apiFetch(`/muthowif/emergency-offers/${offerId}/decline`, {
    token,
    method: 'POST',
    body: note ? { decline_note: note } : {},
  });
}
