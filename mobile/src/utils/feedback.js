import { showToast } from './toast';

export function notifySuccess(message) {
  showToast(message, 'success');
}

export function notifyError(message) {
  showToast(message, 'error', 3400);
}

export function notifyInfo(message) {
  showToast(message, 'info');
}

export function notifySuccessThen(navigation, message, routeName, params = {}) {
  notifySuccess(message);
  setTimeout(() => {
    if (typeof routeName === 'function') {
      routeName();
    } else if (routeName) {
      navigation.navigate(routeName, params);
    }
  }, 350);
}
