import { Alert } from 'react-native';

let presenter = null;

export function registerAlertPresenter(fn) {
  presenter = fn;
}

export function unregisterAlertPresenter() {
  presenter = null;
}

function normalizeButtons(buttons) {
  if (!buttons?.length) {
    return [{ text: 'OK', style: 'default' }];
  }
  return buttons.map((btn) => ({
    text: btn.text ?? 'OK',
    style: btn.style ?? 'default',
    onPress: btn.onPress,
  }));
}

function inferVariant(title, message, buttons) {
  const hay = `${title || ''} ${message || ''}`.toLowerCase();
  if (buttons?.some((b) => b.style === 'destructive')) return 'danger';
  if (/berhasil|lunas|disimpan|dikirim|diperbarui|ditambahkan|diterima|selesai/.test(hay)) return 'success';
  if (/gagal|error|tidak dapat|tidak tersedia/.test(hay)) return 'error';
  if (/validasi|izin|perhatian|wajib|masuk diperlukan|akses terbatas/.test(hay)) return 'warning';
  if (buttons?.length > 1 && buttons.some((b) => b.style === 'cancel')) return 'confirm';
  if (/\?/.test(title || '')) return 'confirm';
  return 'info';
}

export function showAppAlert(title, message, buttons, options) {
  const normalized = normalizeButtons(buttons);
  const payload = {
    title: title ?? '',
    message: message ?? '',
    buttons: normalized,
    options: options ?? {},
    variant: inferVariant(title, message, normalized),
    layout: normalized.length > 2 ? 'actions' : 'dialog',
  };

  if (presenter) {
    presenter(payload);
    return;
  }

  Alert.alert(title, message, buttons, options);
}

export function installCustomAlert() {
  Alert.alert = showAppAlert;
}
