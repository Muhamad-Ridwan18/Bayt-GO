import { apiFetch } from './client';
import { appendFile, appendFiles } from '../utils/formData';

export async function fetchPortfolio(token) {
  return apiFetch('/muthowif/portfolio', { token });
}

export async function createPortfolio(token, formData) {
  return apiFetch('/muthowif/portfolio', { token, method: 'POST', body: formData });
}

export async function fetchPortfolioItem(token, id) {
  return apiFetch(`/muthowif/portfolio/${id}`, { token });
}

export async function updatePortfolio(token, id, { title, description, newImages, deleteImageIds }) {
  const formData = new FormData();
  formData.append('title', title);
  if (description !== undefined && description !== null) {
    formData.append('description', description);
  }
  (deleteImageIds || []).forEach((imageId, index) => {
    formData.append(`delete_image_ids[${index}]`, imageId);
  });
  appendFiles(formData, 'images', newImages);
  return apiFetch(`/muthowif/portfolio/${id}`, { token, method: 'POST', body: formData });
}

export async function deletePortfolio(token, id) {
  return apiFetch(`/muthowif/portfolio/${id}`, { token, method: 'DELETE' });
}
