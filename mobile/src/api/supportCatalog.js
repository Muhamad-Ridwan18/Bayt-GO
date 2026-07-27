import { apiFetch } from './client';

function buildQuery(params) {
  const query = new URLSearchParams();
  Object.entries(params).forEach(([key, value]) => {
    if (value !== undefined && value !== null && value !== '') {
      query.set(key, String(value));
    }
  });
  const qs = query.toString();
  return qs ? `?${qs}` : '';
}

export async function fetchSupportPackages({ token, category, startsAt, q, page = 1 } = {}) {
  return apiFetch(
    `/support-packages${buildQuery({
      category,
      starts_at: startsAt,
      q,
      page,
    })}`,
    { token },
  );
}

export async function fetchSupportPackageDetail({ token, id, startsAt } = {}) {
  return apiFetch(
    `/support-packages/${id}${buildQuery({ starts_at: startsAt })}`,
    { token },
  );
}

export async function createSupportBooking(token, payload) {
  return apiFetch('/customer/support-bookings', {
    token,
    method: 'POST',
    body: payload,
  });
}
