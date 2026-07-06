export function appendFile(formData, key, file) {
  if (!file) return;
  formData.append(key, {
    uri: file.uri,
    name: file.name || file.fileName || `${key}.jpg`,
    type: file.mimeType || file.type || 'image/jpeg',
  });
}

export function appendFiles(formData, key, files) {
  (files || []).forEach((file, index) => {
    appendFile(formData, `${key}[${index}]`, file);
  });
}

export function buildSupportTicketFormData({ subject, category, priority, body, attachments }) {
  const formData = new FormData();
  formData.append('subject', subject);
  formData.append('category', category);
  formData.append('priority', priority);
  formData.append('body', body);
  appendFiles(formData, 'attachments', attachments);
  return formData;
}

export function buildSupportReplyFormData({ body, attachments }) {
  const formData = new FormData();
  formData.append('body', body);
  appendFiles(formData, 'attachments', attachments);
  return formData;
}
