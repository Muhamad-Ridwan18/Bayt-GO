import { apiFetch } from './client';

export async function fetchConversations(token) {
  return apiFetch('/chat/conversations', { token });
}

export async function fetchChatMessages(token, bookingId, afterId) {
  const query = afterId ? `?after_id=${encodeURIComponent(afterId)}` : '';
  return apiFetch(`/bookings/${bookingId}/chat${query}`, { token });
}

export async function sendChatMessage(token, bookingId, { body, image }) {
  const formData = new FormData();
  if (body?.trim()) formData.append('body', body.trim());
  if (image) {
    formData.append('image', {
      uri: image.uri,
      name: image.name || 'chat.jpg',
      type: image.mimeType || 'image/jpeg',
    });
  }
  return apiFetch(`/bookings/${bookingId}/chat`, { token, method: 'POST', body: formData });
}
