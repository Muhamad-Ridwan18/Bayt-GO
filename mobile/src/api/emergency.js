import { apiFetch } from './client';

function appendFiles(formData, files) {
  (files || []).forEach((file, index) => {
    if (!file?.uri) return;
    formData.append(`evidence[${index}]`, {
      uri: file.uri,
      name: file.name || `evidence-${index}.jpg`,
      type: file.mimeType || file.type || 'image/jpeg',
    });
  });
}

export async function submitEmergencyReport(token, bookingId, { caseType, description, evidence }) {
  const formData = new FormData();
  formData.append('case_type', caseType);
  if (description?.trim()) formData.append('description', description.trim());
  appendFiles(formData, evidence);

  return apiFetch(`/customer/bookings/${bookingId}/emergency-report`, {
    token,
    method: 'POST',
    body: formData,
  });
}

export async function selectEmergencyReplacement(token, bookingId, offerId) {
  return apiFetch(`/customer/bookings/${bookingId}/emergency-select/${offerId}`, {
    token,
    method: 'POST',
  });
}
