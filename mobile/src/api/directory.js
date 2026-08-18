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

export async function fetchDirectory({ token, q, startDate, endDate, sort, page = 1 } = {}) {
  return apiFetch(
    `/directory${buildQuery({
      q,
      start_date: startDate,
      end_date: endDate,
      sort,
      page,
    })}`,
    { token },
  );
}

export async function fetchMuthowifDetail({ token, id, startDate, endDate } = {}) {
  return apiFetch(
    `/directory/${encodeURIComponent(id)}${buildQuery({
      start_date: startDate,
      end_date: endDate,
    })}`,
    { token },
  );
}

export async function fetchMuthowifPortfolios({ token, id } = {}) {
  return apiFetch(`/directory/${encodeURIComponent(id)}/portfolios`, { token });
}
