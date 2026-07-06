import { createNavigationContainerRef, CommonActions } from '@react-navigation/native';

export const navigationRef = createNavigationContainerRef();

let pendingChatNavigation = null;

export function getRootNavigation(navigation) {
  let current = navigation;
  while (current.getParent()) {
    current = current.getParent();
  }
  return current;
}

export function navigateRoot(navigation, name, params) {
  getRootNavigation(navigation).navigate(name, params);
}

export function resetRoot(navigation, routes) {
  getRootNavigation(navigation).reset({ index: 0, routes });
}

export function navigateToBookingDetail(navigation, bookingId) {
  getRootNavigation(navigation).navigate('Main', {
    screen: 'BookingsTab',
    params: {
      screen: 'BookingDetail',
      params: { bookingId },
    },
  });
}

function rootHasMainRoute() {
  const state = navigationRef.getRootState();
  return Boolean(state?.routes?.some((route) => route.name === 'Main'));
}

export function queueChatNavigation(params) {
  if (params?.bookingId) {
    pendingChatNavigation = params;
  }
}

export function flushPendingChatNavigation() {
  if (!pendingChatNavigation || !navigationRef.isReady() || !rootHasMainRoute()) {
    return;
  }

  const params = pendingChatNavigation;
  pendingChatNavigation = null;
  navigateToChatRoom(params);
}

export function navigateToChatRoom(params) {
  if (!params?.bookingId) return;

  if (!navigationRef.isReady() || !rootHasMainRoute()) {
    queueChatNavigation(params);
    return;
  }

  navigationRef.dispatch(
    CommonActions.navigate({
      name: 'Main',
      params: {
        screen: 'ChatTab',
        params: {
          screen: 'ChatRoom',
          params: {
            bookingId: params.bookingId,
            bookingCode: params.bookingCode || '--',
            otherName: params.otherName || 'Chat',
          },
        },
      },
    }),
  );
}
