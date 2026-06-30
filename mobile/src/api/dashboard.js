import { apiFetch } from './client';

export async function fetchCustomerDashboard(token) {
  return apiFetch('/customer/dashboard', { token });
}

export async function fetchMuthowifDashboard(token) {
  return apiFetch('/muthowif/dashboard', { token });
}
