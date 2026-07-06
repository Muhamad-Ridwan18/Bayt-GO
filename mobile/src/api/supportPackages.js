import { apiFetch } from './client';
import { appendFile, appendFiles } from '../utils/formData';

export async function fetchSupportPackages(token) {
  return apiFetch('/muthowif/support-packages', { token });
}

export async function updateSupportPackages(token, packages) {
  return apiFetch('/muthowif/support-packages', {
    token,
    method: 'PUT',
    body: { packages },
  });
}
