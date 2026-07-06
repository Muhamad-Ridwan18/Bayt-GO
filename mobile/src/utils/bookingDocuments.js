import * as FileSystem from 'expo-file-system/legacy';
import * as Sharing from 'expo-sharing';
import { API_BASE_URL } from '../config/api';

const IMAGE_EXT = /\.(jpe?g|png|gif|webp)$/i;

export function bookingDocumentUrl(bookingId, type) {
  return `${API_BASE_URL}/bookings/${bookingId}/documents/${type}`;
}

function mimeIsImage(mime) {
  return typeof mime === 'string' && mime.toLowerCase().startsWith('image/');
}

export function guessDocumentIsImage(uri, mime) {
  if (mimeIsImage(mime)) return true;
  return IMAGE_EXT.test(uri || '');
}

export async function downloadBookingDocument(token, bookingId, type) {
  const url = bookingDocumentUrl(bookingId, type);
  const path = `${FileSystem.cacheDirectory}booking-${bookingId}-${type}`;
  const cached = await FileSystem.getInfoAsync(path);
  if (cached.exists) {
    return { uri: path, isImage: guessDocumentIsImage(path) };
  }

  const result = await FileSystem.downloadAsync(url, path, {
    headers: { Authorization: `Bearer ${token}`, Accept: '*/*' },
  });

  if (result.status !== 200) {
    throw new Error('Dokumen tidak tersedia');
  }

  const mime = result.headers?.['content-type'] || result.headers?.['Content-Type'];
  return {
    uri: result.uri,
    isImage: guessDocumentIsImage(result.uri, mime),
    mime,
  };
}

export async function shareBookingDocument(uri, label) {
  if (await Sharing.isAvailableAsync()) {
    await Sharing.shareAsync(uri, { dialogTitle: label || 'Dokumen jamaah' });
    return;
  }
  throw new Error('Tidak dapat membuka dokumen di perangkat ini');
}

export async function openBookingDocument(token, bookingId, type, label) {
  const file = await downloadBookingDocument(token, bookingId, type);
  await shareBookingDocument(file.uri, label);
  return file;
}
