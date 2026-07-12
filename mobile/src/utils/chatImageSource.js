export function isLocalImageUri(uri) {
  return Boolean(uri?.startsWith('file://') || uri?.startsWith('content://'));
}

export function buildChatImageSource(uri, token) {
  if (!uri) return null;
  if (isLocalImageUri(uri)) return { uri };
  if (!token) return { uri };
  return {
    uri,
    headers: {
      Authorization: `Bearer ${token}`,
      Accept: 'image/*',
    },
  };
}
