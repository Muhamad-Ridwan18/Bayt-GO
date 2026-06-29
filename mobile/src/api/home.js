import { API_BASE_URL } from '../config/api';

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
