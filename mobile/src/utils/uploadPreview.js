export function getUploadUri(file) {
  return file?.uri || file?.url || null;
}

export function getUploadName(file, index = 0) {
  return file?.name || file?.fileName || file?.original_name || `File ${index + 1}`;
}

export function isImageUpload(file) {
  if (!file) return false;
  const mime = String(file.mimeType || file.type || '').toLowerCase();
  if (mime.startsWith('image/')) return true;
  const name = getUploadName(file).toLowerCase();
  return /\.(jpe?g|png|gif|webp|heic|heif|bmp)$/.test(name);
}

export function isPdfUpload(file) {
  if (!file) return false;
  const mime = String(file.mimeType || file.type || '').toLowerCase();
  if (mime === 'application/pdf') return true;
  return getUploadName(file).toLowerCase().endsWith('.pdf');
}

export function getImageUploadUris(files = []) {
  return files.filter(isImageUpload).map(getUploadUri).filter(Boolean);
}
