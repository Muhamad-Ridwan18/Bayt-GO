import React, { useCallback, useEffect, useMemo, useState } from 'react';
import { View, Text, StyleSheet, ScrollView, StatusBar, RefreshControl } from 'react-native';
import { Image } from 'expo-image';
import { SafeAreaView } from 'react-native-safe-area-context';
import { Bell, ChevronRight, Menu } from 'lucide-react-native';
import { fetchHomeData } from '../api/home';
import { fetchCustomerDashboard } from '../api/dashboard';
import { useAuth } from '../context/AuthContext';
import { useBrand } from '../context/BrandContext';
import { navigateRoot } from '../navigation/rootNavigation';
import AppLogo from '../components/AppLogo';
import MuthowifSpotlightCard from '../components/MuthowifSpotlightCard';
import { parseIsoDate, toIsoDateTime } from '../components/DatePickerField';
import HeroCarousel from '../features/home/HeroCarousel';
import SearchPanel from '../features/home/SearchPanel';
import CustomerActivitySection from '../features/home/CustomerActivitySection';
import HelpTicketCta from '../features/home/HelpTicketCta';
import CampaignCarousel from '../features/home/CampaignCarousel';
import { HOME_CATEGORIES } from '../features/home/FeatureChips';
import HomeGallery from '../features/home/HomeGallery';
import ArticleCards from '../features/home/ArticleCards';
import HowItWorks from '../features/home/HowItWorks';
import FaqSection from '../features/home/FaqSection';
import TrustSection from '../features/home/TrustSection';
import GuestCta from '../features/home/GuestCta';
import { AppImage, EmptyState, ErrorState, PressableScale, SkeletonList } from '../ui';
import { colors, layout, radius, spacing, typography } from '../theme/tokens';
import { resolveMediaUrl } from '../utils/mediaUrl';
import { useLocale } from '../utils/locale';

export default function HomeScreen({ navigation }) {
  const { isAuthenticated, user, token } = useAuth();
  const { logoUrl, appName } = useBrand();
  const locale = useLocale();
  const isEn = locale === 'en';
  const isCustomer = isAuthenticated && user?.role !== 'muthowif';

  const [loading, setLoading] = useState(true);
  const [refreshing, setRefreshing] = useState(false);
  const [muthowifs, setMuthowifs] = useState([]);
  const [error, setError] = useState(null);
  const [startDate, setStartDate] = useState('');
  const [endDate, setEndDate] = useState('');
  const [startsAt, setStartsAt] = useState(() => {
    const d = new Date();
    d.setDate(d.getDate() + 1);
    d.setHours(9, 0, 0, 0);
    return toIsoDateTime(d);
  });
  const [selectedCategoryKey, setSelectedCategoryKey] = useState('umroh');
  const [dateError, setDateError] = useState('');
  const [nextBooking, setNextBooking] = useState(null);
  const [dashStats, setDashStats] = useState([]);
  const [unreadMessages, setUnreadMessages] = useState(0);
  const [campaigns, setCampaigns] = useState([]);
  const [articles, setArticles] = useState([]);
  const [gallery, setGallery] = useState([]);
  const [work, setWork] = useState(null);
  const [faq, setFaq] = useState(null);

  const firstName = useMemo(() => user?.name?.split(' ')[0] || (isEn ? 'Pilgrim' : 'Jamaah'), [user?.name, isEn]);
  const today = useMemo(() => { const d = new Date(); d.setHours(0, 0, 0, 0); return d; }, []);
  const endMinDate = startDate ? parseIsoDate(startDate) : today;
  const endMaxDate = useMemo(() => {
    if (!startDate) return undefined;
    const max = parseIsoDate(startDate);
    max.setDate(max.getDate() + 90);
    return max;
  }, [startDate]);

  const heroFaces = useMemo(
    () => muthowifs.slice(0, 3).map((m) => resolveMediaUrl(m.avatar)).filter(Boolean),
    [muthowifs],
  );

  const heroCountLabel = useMemo(() => {
    const suffix = isEn ? 'Active Muthowif' : 'Muthowif Aktif';
    if (muthowifs.length >= 10) return `${muthowifs.length}+ ${suffix}`;
    return `1.200+ ${suffix}`;
  }, [muthowifs.length, isEn]);

  const heroRatingLabel = useMemo(() => {
    const rated = muthowifs.filter((m) => m.rating);
    if (!rated.length) return '4.9/5 (1.200 review)';
    const avg = rated.reduce((sum, m) => sum + Number(m.rating), 0) / rated.length;
    const reviews = rated.reduce((sum, m) => sum + (m.reviews || 0), 0);
    return `${avg.toFixed(1)}/5 (${reviews || '1.200'} review)`;
  }, [muthowifs]);

  const homeCategories = HOME_CATEGORIES[locale] || HOME_CATEGORIES.id;
  const selectedCategory = homeCategories.find((c) => c.key === selectedCategoryKey) || homeCategories[0];

  const openDirectory = useCallback((params = {}) => {
    navigation.navigate('Directory', {
      q: params.q ?? '',
      startDate: params.startDate ?? startDate.trim(),
      endDate: params.endDate ?? endDate.trim(),
      sort: params.sort,
      minRating: params.minRating,
    });
  }, [navigation, startDate, endDate]);

  const selectCategory = useCallback((cat) => {
    if (!cat) return;
    setSelectedCategoryKey(cat.key);
    setDateError('');
  }, []);

  const runHomeSearch = useCallback(() => {
    const cat = selectedCategory;
    if (!cat) return;
    setDateError('');

    if (cat.type === 'layanan') {
      const start = startDate.trim();
      if (!start) {
        setDateError(isEn
          ? 'Please choose departure and return dates first.'
          : 'Pilih tanggal berangkat dan pulang terlebih dahulu.');
        return;
      }
      openDirectory({ startDate: start, endDate: endDate.trim() || start });
      return;
    }

    const slot = startsAt.trim();
    if (!slot) {
      setDateError(isEn
        ? 'Please choose a start date and time first.'
        : 'Pilih tanggal dan jam mulai terlebih dahulu.');
      return;
    }
    navigation.navigate('SupportCatalog', {
      category: cat.category || cat.key,
      startsAt: slot,
    });
  }, [selectedCategory, startDate, endDate, startsAt, isEn, openDirectory, navigation]);

  const openMuthowifDetail = (item) => navigation.navigate('MuthowifDetail', {
    id: item.id, startDate: startDate.trim() || undefined, endDate: endDate.trim() || undefined,
  });

  const loadData = useCallback(async (isRefresh = false) => {
    if (isRefresh) setRefreshing(true);
    else setLoading(true);
    try {
      const homeData = await fetchHomeData();
      let list = homeData.featured_muthowifs || [];
      setCampaigns(homeData.campaigns || []);
      setArticles(homeData.articles || []);
      setGallery(homeData.gallery || []);
      setWork(homeData.work || null);
      setFaq(homeData.faq || null);
      if (isCustomer && token) {
        try {
          const dash = await fetchCustomerDashboard(token);
          if (dash.top_muthowifs?.length) list = dash.top_muthowifs;
          setNextBooking(dash.next_booking || null);
          setDashStats(dash.stats || []);
          setUnreadMessages(dash.unread_messages || 0);
        } catch { setNextBooking(null); setDashStats([]); }
      } else { setNextBooking(null); setDashStats([]); setUnreadMessages(0); }
      setMuthowifs(list);
      setError(null);
    } catch (err) {
      setError(err.message || (isEn ? 'Failed to load data' : 'Gagal memuat data'));
    } finally { setLoading(false); setRefreshing(false); }
  }, [isCustomer, token]);

  useEffect(() => { loadData(); }, [loadData]);

  const goChat = () => navigation.getParent()?.navigate('ChatTab', { screen: 'ChatList' });
  const goBookings = () => navigation.getParent()?.navigate('BookingsTab', { screen: 'BookingsList' });
  const openNextBooking = () => nextBooking?.id && navigation.getParent()?.navigate('BookingsTab', {
    screen: 'BookingDetail', params: { bookingId: nextBooking.id },
  });
  const openNextPayment = () => nextBooking?.id && navigation.navigate('BookingPayment', { bookingId: nextBooking.id });
  const openNextChat = () => nextBooking?.id && navigation.getParent()?.navigate('ChatTab', {
    screen: 'ChatRoom',
    params: { bookingId: nextBooking.id, bookingCode: nextBooking.booking_code, otherName: nextBooking.muthowif_name || 'Muthowif' },
  });
  const openStat = (stat) => {
    const statKey = String(stat?.key || '').toLowerCase();
    const statLabel = String(stat?.label || '').toLowerCase();
    if (
      statKey === 'support_tickets'
      || statLabel === 'tiket bantuan'
      || statLabel === 'support tickets'
    ) {
      navigation.getParent()?.navigate('SupportTab', { screen: 'SupportList' });
      return;
    }
    goBookings();
  };

  return (
    <View style={styles.container}>
      <StatusBar barStyle="dark-content" />
      <SafeAreaView edges={['top']} style={styles.safeTop}>
        <View style={styles.header}>
          <View style={styles.headerBrand}>
            <AppLogo url={logoUrl} name={appName} size={40} showName />
            <Text style={styles.tagline}>{isEn ? 'Your Worship Companion' : 'Teman Ibadahmu'}</Text>
          </View>
          <View style={styles.headerActions}>
            {isCustomer ? (
              <PressableScale onPress={goChat} haptic="light" style={styles.iconBtn}>
                <Bell size={20} color={colors.baytgo} strokeWidth={2} />
                {unreadMessages > 0 ? (
                  <View style={styles.notifBadge}>
                    <Text style={styles.notifBadgeText}>{unreadMessages > 9 ? '9+' : unreadMessages}</Text>
                  </View>
                ) : null}
              </PressableScale>
            ) : null}
            <PressableScale
              onPress={() => isAuthenticated ? navigation.getParent()?.navigate('ProfileTab') : navigateRoot(navigation, 'Login')}
              haptic="light"
            >
              {isAuthenticated ? (
                <AppImage name={user?.name} size={40} rounded={radius.full} />
              ) : (
                <View style={styles.iconBtn}>
                  <Menu size={22} color={colors.baytgo} strokeWidth={2} />
                </View>
              )}
            </PressableScale>
          </View>
        </View>
      </SafeAreaView>

      <ScrollView
        showsVerticalScrollIndicator={false}
        contentContainerStyle={{ paddingBottom: spacing.lg }}
        refreshControl={<RefreshControl refreshing={refreshing} onRefresh={() => loadData(true)} tintColor={colors.baytgo} />}
      >
        <View style={styles.hero}>
          <Image
            source={require('../../assets/hero/hero-welcome.png')}
            style={styles.heroBg}
            contentFit="cover"
            contentPosition="center"
          />
          <View style={styles.heroOverlay} />
          <Text style={styles.heroIntro}>
            {isCustomer
              ? `${isEn ? 'Assalamu alaikum,' : "Assalamu'alaikum,"} ${firstName}`
              : (isEn ? 'Assalamu alaikum,' : "Assalamu'alaikum,")}
          </Text>
          <Text style={styles.heroTitle}>
            {isEn ? 'Book a Trusted ' : 'Pesan Pendamping Ibadah '}
            <Text style={styles.heroAccent}>{isEn ? 'Umrah Companion.' : 'Umroh Terpercaya.'}</Text>
          </Text>
          <Text style={styles.heroSub}>
            {isEn
              ? 'Find verified muthowif, compare prices, and book for your travel dates.'
              : 'Temukan muthowif terverifikasi, bandingkan harga, dan pesan sesuai tanggal perjalanan Anda.'}
          </Text>

          <SearchPanel
            selectedKey={selectedCategoryKey}
            onSelectCategory={selectCategory}
            startDate={startDate}
            endDate={endDate}
            onStartDateChange={(iso) => {
              setStartDate(iso);
              setDateError('');
              if (endDate && iso && endDate < iso) setEndDate('');
            }}
            onEndDateChange={(iso) => { setEndDate(iso); setDateError(''); }}
            onClearEndDate={() => setEndDate('')}
            startsAt={startsAt}
            onStartsAtChange={(iso) => { setStartsAt(iso); setDateError(''); }}
            today={today}
            endMinDate={endMinDate}
            endMaxDate={endMaxDate}
            dateError={dateError}
            onSearch={runHomeSearch}
          />
        </View>

        <CampaignCarousel
          campaigns={campaigns}
          onPress={(item) => navigation.navigate('CampaignDetail', { slug: item.slug, preview: item })}
        />

        <HeroCarousel
          onCta={() => openDirectory()}
          faces={heroFaces}
          countLabel={heroCountLabel}
          ratingLabel={heroRatingLabel}
        />

        {isCustomer ? (
          <CustomerActivitySection
            stats={dashStats}
            booking={nextBooking}
            onStatPress={openStat}
            onSeeAll={goBookings}
            onBookingPress={openNextBooking}
            onPay={openNextPayment}
            onChat={openNextChat}
            onExplore={() => openDirectory()}
          />
        ) : null}

        {isCustomer ? (
          <HelpTicketCta
            onPress={() => navigation.getParent()?.navigate('SupportTab', { screen: 'SupportList' })}
          />
        ) : null}

        <View style={styles.sectionPad}>
          <View style={styles.sectionHead}>
            <Text style={styles.sectionTitle}>Muthowif Top Rated</Text>
            <PressableScale onPress={() => openDirectory({ sort: 'rating' })} haptic="light">
              <View style={styles.seeAllRow}>
                <Text style={styles.seeAll}>{isEn ? 'See all' : 'Lihat semua'}</Text>
                <ChevronRight size={14} color={colors.goldMuted} strokeWidth={2.5} />
              </View>
            </PressableScale>
          </View>
          {loading && !refreshing ? (
            <View style={styles.spotlightRow}>
              <SkeletonList count={2} />
            </View>
          ) : null}
          {error ? <ErrorState description={error} onRetry={() => loadData()} /> : null}
          {!loading && !error && muthowifs.length === 0 ? (
            <EmptyState variant="search" title={isEn ? 'No muthowif yet' : 'Belum ada muthowif'} description={isEn ? 'Verified muthowif will appear here.' : 'Muthowif terverifikasi akan muncul di sini.'} />
          ) : null}
          {!loading && !error && muthowifs.length > 0 ? (
            <ScrollView
              horizontal
              showsHorizontalScrollIndicator={false}
              contentContainerStyle={styles.spotlightRow}
              nestedScrollEnabled
            >
              {muthowifs.map((item) => (
                <MuthowifSpotlightCard
                  key={item.id}
                  item={item}
                  onPress={() => openMuthowifDetail(item)}
                />
              ))}
            </ScrollView>
          ) : null}
        </View>

        <HomeGallery
          items={gallery}
          onPress={(item) => item.muthowif_id && navigation.navigate('MuthowifDetail', { id: item.muthowif_id })}
        />

        <HowItWorks work={work} />

        <ArticleCards
          articles={articles}
          onPress={(item) => navigation.navigate('ArticleDetail', { slug: item.slug, preview: item })}
          onSeeAll={() => navigation.navigate('ArticlesList')}
        />

        <FaqSection faq={faq} />

        <TrustSection />

        {!isAuthenticated ? (
          <GuestCta
            onRegister={() => navigateRoot(navigation, 'Register', { role: 'customer' })}
            onLogin={() => navigateRoot(navigation, 'Login')}
          />
        ) : null}
      </ScrollView>
    </View>
  );
}

const styles = StyleSheet.create({
  container: { flex: 1, backgroundColor: colors.background },
  safeTop: { backgroundColor: colors.card },
  header: {
    flexDirection: 'row',
    alignItems: 'center',
    justifyContent: 'space-between',
    paddingHorizontal: layout.screenPadding,
    paddingVertical: spacing.md - 2,
  },
  headerBrand: { flex: 1, gap: 2 },
  tagline: { marginLeft: 50, ...typography.small, color: colors.textSecondary, fontStyle: 'italic' },
  headerActions: { flexDirection: 'row', alignItems: 'center', gap: spacing.sm },
  iconBtn: {
    width: 40,
    height: 40,
    borderRadius: radius.full,
    backgroundColor: colors.background,
    alignItems: 'center',
    justifyContent: 'center',
    borderWidth: 1,
    borderColor: colors.border,
  },
  notifBadge: {
    position: 'absolute',
    top: 4,
    right: 4,
    minWidth: 16,
    height: 16,
    borderRadius: 8,
    backgroundColor: colors.error,
    alignItems: 'center',
    justifyContent: 'center',
    paddingHorizontal: spacing.xs,
    borderWidth: 1.5,
    borderColor: colors.white,
  },
  notifBadgeText: { fontSize: 9, fontWeight: '900', color: colors.white },
  hero: {
    backgroundColor: colors.baytgo,
    paddingHorizontal: layout.screenPadding,
    paddingTop: spacing.xl,
    paddingBottom: spacing.xl,
    overflow: 'hidden',
  },
  heroBg: { ...StyleSheet.absoluteFillObject },
  heroOverlay: {
    ...StyleSheet.absoluteFillObject,
    backgroundColor: 'rgba(15, 34, 29, 0.72)',
  },
  heroIntro: {
    ...typography.caption,
    fontWeight: '600',
    color: 'rgba(255,255,255,0.8)',
  },
  heroTitle: {
    marginTop: spacing.sm,
    fontSize: 24,
    lineHeight: 30,
    fontFamily: 'PlusJakartaSans_800ExtraBold',
    fontWeight: '800',
    color: colors.white,
    letterSpacing: -0.4,
  },
  heroAccent: { color: colors.gold },
  heroSub: {
    marginTop: spacing.md,
    ...typography.caption,
    color: 'rgba(255,255,255,0.85)',
    lineHeight: 20,
  },
  sectionPad: { marginTop: spacing.xl },
  sectionHead: {
    flexDirection: 'row',
    alignItems: 'center',
    justifyContent: 'space-between',
    paddingHorizontal: layout.screenPadding,
    marginBottom: spacing.md + 2,
  },
  sectionTitle: {
    ...typography.subtitle,
    fontSize: 17,
    color: colors.baytgo,
    fontFamily: 'PlusJakartaSans_800ExtraBold',
    fontWeight: '800',
  },
  seeAllRow: { flexDirection: 'row', alignItems: 'center', gap: 2 },
  seeAll: { ...typography.caption, fontWeight: '800', color: colors.goldMuted },
  spotlightRow: {
    paddingHorizontal: layout.screenPadding,
    gap: spacing.md,
    paddingBottom: spacing.xs,
  },
});
