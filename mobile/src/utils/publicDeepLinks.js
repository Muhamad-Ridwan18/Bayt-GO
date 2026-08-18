import { Linking } from 'react-native';
import { captureAffiliateFromUrl } from './affiliateReferral';
import { navigatePublicDeepLink } from '../navigation/rootNavigation';

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

  const campaign = path.match(/^\/campaigns?\/([A-Za-z0-9-]+)$/);
  if (campaign) return { screen: 'CampaignDetail', params: { slug: campaign[1] } };

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
