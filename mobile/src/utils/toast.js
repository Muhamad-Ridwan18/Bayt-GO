let presenter = null;

export function registerToastPresenter(fn) {
  presenter = fn;
}

export function unregisterToastPresenter() {
  presenter = null;
}

export function showToast(message, type = 'info', duration = 2800) {
  if (!message) return;
  presenter?.({ message, type, duration });
}
