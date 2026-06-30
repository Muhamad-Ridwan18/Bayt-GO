import { createNavigationContainerRef } from '@react-navigation/native';

export const navigationRef = createNavigationContainerRef();

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

export function navigateToChatRoom(params) {
  if (!navigationRef.isReady()) return;

  navigationRef.navigate('Main', {
    screen: 'ChatTab',
    params: {
      screen: 'ChatRoom',
      params: {
        bookingId: params.bookingId,
        bookingCode: params.bookingCode || '--',
        otherName: params.otherName || 'Chat',
      },
    },
  });
}
