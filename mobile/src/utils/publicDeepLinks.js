import { Linking } from 'react-native';
import AsyncStorage from '@react-native-async-storage/async-storage';
import { captureAffiliateFromUrl } from './affiliateReferral';
import { navigatePublicDeepLink } from '../navigation/rootNavigation';
import { clearApiLocaleCache } from '../api/client';

function parsedUrl(url) {
  if (!url) return null;
  const text = String(url);
  try {
    return new URL(text.replace(/^baytgo:\/\//i, 'https://app.baytgo.local/'));
  } catch {
    return null;
  }
}

function pathFromUrl(url) {
  const parsed = parsedUrl(url);
  if (parsed) return parsed.pathname.replace(/\/+$/, '') || '/';

  const text = String(url);
  const match = text.match(/https?:\/\/[^/]+(\/[^?#]*)/i)
    || text.match(/^baytgo:\/\/([^?#]*)/i);
  const raw = match ? match[1] : '';
  const path = raw.startsWith('/') ? raw : `/${raw}`;
  return path.replace(/\/+$/, '') || '/';
}

const LOCALE_KEY = '@baytgo_locale';

async function captureLocaleFromUrl(url) {
  if (!url) return null;
  const text = String(url);
  const match = text.match(/\/locale\/(en|id|ar)(?:[/?#]|$)/i);
  if (!match) return null;

  const locale = match[1].toLowerCase();
  await AsyncStorage.setItem(LOCALE_KEY, locale);
  clearApiLocaleCache();
  return locale;
}

export function parsePublicDeepLink(url) {
  const path = pathFromUrl(url);
  if (!path || path === '/') return null;

  const params = parsedUrl(url)?.searchParams;
  const startDate = params?.get('start_date') || undefined;
  const endDate = params?.get('end_date') || undefined;
  const startsAt = params?.get('starts_at') || undefined;

  const article = path.match(/^\/artikel\/([A-Za-z0-9-]+)$/);
  if (article) return { screen: 'ArticleDetail', params: { slug: article[1] } };

  if (path === '/artikel') return { screen: 'ArticlesList' };

  if (path === '/terms') return { screen: 'Terms' };

  const locale = path.match(/^\/locale\/(en|id|ar)$/i);
  if (locale) {
    return { screen: 'DashboardMain' };
  }

  const affiliateLanding = path.match(/^\/r\/([A-Za-z0-9]{3,32})$/);
  if (affiliateLanding) return { screen: 'DashboardMain' };

  const campaign = path.match(/^\/campaigns?\/([A-Za-z0-9-]+)$/);
  if (campaign) return { screen: 'CampaignDetail', params: { slug: campaign[1] } };

  if (path === '/login') return { root: 'Login' };
  if (path === '/register') {
    const role = params?.get('role') === 'muthowif' ? 'muthowif' : 'customer';
    return { root: 'Register', params: { role } };
  }
  if (path === '/forgot-password') return { root: 'ForgotPassword' };

  if (path === '/chat') return { tab: 'ChatTab', screen: 'ChatList' };

  if (path === '/affiliate') {
    return { tab: 'ProfileTab', screen: 'Affiliate' };
  }

  if (path === '/support') {
    return { screen: 'SupportList' };
  }

  if (path === '/support/baru') {
    return { screen: 'SupportCreate' };
  }

  const supportTicket = path.match(/^\/support\/([0-9a-f]{8}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{12})$/i);
  if (supportTicket) {
    return {
      screen: 'SupportDetail',
      params: { ticketId: supportTicket[1] },
    };
  }

  if (path === '/muthowif/emergency-offers') {
    return { screen: 'EmergencyOffers' };
  }

  if (path === '/bookings') return { tab: 'BookingsTab', screen: 'BookingsList' };

  const bookingPay = path.match(/^\/bookings\/(\d+)\/pembayaran$/);
  if (bookingPay) {
    return {
      tab: 'BookingsTab',
      screen: 'BookingPayment',
      params: { bookingId: Number(bookingPay[1]) },
    };
  }

  const bookingInvoice = path.match(/^\/bookings\/(\d+)\/invoice$/);
  if (bookingInvoice) {
    return {
      tab: 'BookingsTab',
      screen: 'BookingInvoice',
      params: { bookingId: Number(bookingInvoice[1]) },
    };
  }

  const booking = path.match(/^\/bookings\/(\d+)$/);
  if (booking) {
    return {
      tab: 'BookingsTab',
      screen: 'BookingDetail',
      params: { bookingId: Number(booking[1]) },
    };
  }

  const muthowifBooking = path.match(/^\/muthowif\/bookings\/(\d+)$/);
  if (muthowifBooking) {
    return {
      tab: 'MuthowifBookingsTab',
      screen: 'MuthowifBookingDetail',
      params: { bookingId: Number(muthowifBooking[1]) },
    };
  }

  if (path === '/muthowif/bookings') {
    return { tab: 'MuthowifBookingsTab', screen: 'MuthowifBookingsList' };
  }

  if (path === '/layanan') return { screen: 'Directory' };

  const layanan = path.match(/^\/layanan\/([A-Za-z0-9-]+)(\/booking)?$/);
  if (layanan) {
    const autoBook = Boolean(layanan[2]);
    return {
      screen: 'MuthowifDetail',
      params: {
        id: layanan[1],
        startDate,
        endDate,
        ...(autoBook ? { autoBook: true } : {}),
      },
    };
  }

  if (path === '/layanan-pendukung') return { screen: 'SupportCatalog' };

  const supportPkg = path.match(/^\/layanan-pendukung\/(\d+)(?:\/pesan)?$/);
  if (supportPkg) {
    const id = Number(supportPkg[1]);
    if (path.endsWith('/pesan')) {
      return {
        screen: 'SupportPackageBook',
        params: { id, startsAt },
      };
    }
    return {
      screen: 'SupportPackageDetail',
      params: { id, startsAt },
    };
  }

  return null;
}

async function handleIncomingUrl(url) {
  if (!url) return;
  await captureLocaleFromUrl(url);
  await captureAffiliateFromUrl(url);
  const target = parsePublicDeepLink(url);
  if (target) navigatePublicDeepLink(target);
}

export function listenAppLinks() {
  Linking.getInitialURL().then((url) => {
    if (url) handleIncomingUrl(url);
  }).catch(() => {});

  const sub = Linking.addEventListener('url', ({ url }) => {
    if (url) handleIncomingUrl(url);
  });

  return () => sub.remove();
}
