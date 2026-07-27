import { apiFetch } from './client';

export async function fetchAffiliateStatus(token) {
  return apiFetch('/affiliate', { token });
}

export async function registerAffiliate(token, code = '') {
  return apiFetch('/affiliate/register', {
    token,
    method: 'POST',
    body: { code: code || null },
  });
}

export async function fetchAffiliateDashboard(token) {
  return apiFetch('/affiliate/dashboard', { token });
}

export async function storeAffiliateBankAccount(token, payload) {
  return apiFetch('/affiliate/bank-accounts', {
    token,
    method: 'POST',
    body: payload,
  });
}

export async function deleteAffiliateBankAccount(token, id) {
  return apiFetch(`/affiliate/bank-accounts/${id}`, {
    token,
    method: 'DELETE',
  });
}

export async function storeAffiliateWithdrawal(token, payload) {
  return apiFetch('/affiliate/withdrawals', {
    token,
    method: 'POST',
    body: payload,
  });
}
