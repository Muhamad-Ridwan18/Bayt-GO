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

export async function fetchDirectory({ token, q, startDate, endDate, page = 1 } = {}) {
  return apiFetch(
    `/directory${buildQuery({
      q,
      start_date: startDate,
      end_date: endDate,
      page,
    })}`,
    { token },
  );
}

export async function fetchMuthowifDetail({ token, id, startDate, endDate } = {}) {
  return apiFetch(
    `/directory/${id}${buildQuery({
      start_date: startDate,
      end_date: endDate,
    })}`,
    { token },
  );
}
