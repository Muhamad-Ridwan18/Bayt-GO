import { apiFetch } from './client';

export async function fetchWallet(token) {
  return apiFetch('/muthowif/wallet', { token });
}

export async function submitWithdrawal(token, payload) {
  return apiFetch('/muthowif/withdrawals', {
    token,
    method: 'POST',
    body: payload,
  });
}
