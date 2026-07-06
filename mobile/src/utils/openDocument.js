import * as FileSystem from 'expo-file-system';
import * as Sharing from 'expo-sharing';
import { API_BASE_URL } from '../config/api';

export async function openBookingDocument(token, bookingId, type, label) {
  const url = `${API_BASE_URL}/bookings/${bookingId}/documents/${type}`;
  const path = `${FileSystem.cacheDirectory}booking-${bookingId}-${type}`;
  const result = await FileSystem.downloadAsync(url, path, {
    headers: { Authorization: `Bearer ${token}`, Accept: '*/*' },
  });

  if (result.status !== 200) {
    throw new Error('Dokumen tidak tersedia');
  }

  if (await Sharing.isAvailableAsync()) {
    await Sharing.shareAsync(result.uri, { dialogTitle: label || 'Dokumen jamaah' });
    return;
  }

  throw new Error('Tidak dapat membuka dokumen di perangkat ini');
}
