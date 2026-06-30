import { API_BASE_URL } from '../config/api';

export async function apiFetch(path, { token, method = 'GET', body, headers = {} } = {}) {
  const requestHeaders = {
    Accept: 'application/json',
    ...headers,
  };

  if (token) {
    requestHeaders.Authorization = `Bearer ${token}`;
  }

  if (body && !(body instanceof FormData)) {
    requestHeaders['Content-Type'] = 'application/json';
  }

  const response = await fetch(`${API_BASE_URL}${path}`, {
    method,
    headers: requestHeaders,
    body: body instanceof FormData ? body : body ? JSON.stringify(body) : undefined,
  });

  const data = await response.json().catch(() => ({}));

  if (!response.ok) {
    let message = data.message;
    if (!message && data.errors) {
      message = Object.values(data.errors).flat().join('\n');
    }
    throw new Error(message || 'Permintaan gagal');
  }

  return data;
}
