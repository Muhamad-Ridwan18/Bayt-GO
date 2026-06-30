import { apiFetch } from './client';

export async function fetchProfile(token) {
  return apiFetch('/profile', { token });
}

export async function updateProfile(token, { name, email }) {
  return apiFetch('/profile', { token, method: 'PATCH', body: { name, email } });
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
