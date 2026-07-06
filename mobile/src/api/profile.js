import { apiFetch } from './client';
import { appendFile } from '../utils/formData';

export async function fetchProfile(token) {
  return apiFetch('/profile', { token });
}

export async function updateProfile(token, { name, email, phone }) {
  return apiFetch('/profile', {
    token,
    method: 'PATCH',
    body: { name, email, phone: phone || null },
  });
}

export async function sendVerificationEmail(token) {
  return apiFetch('/profile/verification-notification', { token, method: 'POST' });
}

export async function deleteAccount(token, password) {
  return apiFetch('/profile', { token, method: 'DELETE', body: { password } });
}

export async function updatePassword(token, { currentPassword, password, passwordConfirmation }) {
  return apiFetch('/profile/password', {
    token,
    method: 'PUT',
    body: {
      current_password: currentPassword,
      password,
      password_confirmation: passwordConfirmation,
    },
  });
}

export async function updatePublicProfile(token, payload) {
  return apiFetch('/profile/public', { token, method: 'PATCH', body: payload });
}

export async function fetchCurrentUser(token) {
  return apiFetch('/user', { token });
}

export async function uploadProfilePhoto(token, image) {
  const formData = new FormData();
  appendFile(formData, 'photo', image);
  return apiFetch('/profile/photo', { token, method: 'POST', body: formData });
}

export async function uploadProfileKtp(token, image) {
  const formData = new FormData();
  appendFile(formData, 'ktp', image);
  return apiFetch('/profile/ktp', { token, method: 'POST', body: formData });
}

export async function uploadSupportingDocument(token, image) {
  const formData = new FormData();
  appendFile(formData, 'document', image);
  return apiFetch('/profile/documents', { token, method: 'POST', body: formData });
}

export async function deleteSupportingDocument(token, id) {
  return apiFetch(`/profile/documents/${id}`, { token, method: 'DELETE' });
}
