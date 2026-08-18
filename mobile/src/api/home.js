import { API_BASE_URL } from '../config/api';
import { apiFetch } from './client';

export async function fetchHomeData() {
  const response = await fetch(`${API_BASE_URL}/home`, {
    headers: { Accept: 'application/json' },
  });

  const data = await response.json();

  if (!response.ok) {
    throw new Error(data.message || 'Gagal memuat data beranda');
  }

  return data;
}

export function fetchArticles() {
  return apiFetch('/articles');
}

export function fetchArticle(slug) {
  return apiFetch(`/articles/${encodeURIComponent(slug)}`);
}

export function fetchCampaign(slug) {
  return apiFetch(`/campaigns/${encodeURIComponent(slug)}`);
}

