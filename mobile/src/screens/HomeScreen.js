import React, { useCallback, useEffect, useMemo, useState } from 'react';
import { View, Text, StyleSheet, ScrollView, StatusBar, RefreshControl } from 'react-native';
import { Image } from 'expo-image';
import { SafeAreaView } from 'react-native-safe-area-context';
import { Bell, ChevronRight, Headphones, Menu } from 'lucide-react-native';
import { fetchHomeData } from '../api/home';
import { fetchCustomerDashboard } from '../api/dashboard';
import { useAuth } from '../context/AuthContext';
import { useBrand } from '../context/BrandContext';
import { navigateRoot } from '../navigation/rootNavigation';
import AppLogo from '../components/AppLogo';
import MuthowifSpotlightCard from '../components/MuthowifSpotlightCard';
import { parseIsoDate } from '../components/DatePickerField';
import HeroCarousel from '../features/home/HeroCarousel';
import SearchPanel from '../features/home/SearchPanel';
import UpcomingTripCard from '../features/home/UpcomingTripCard';
import FeatureChips from '../features/home/FeatureChips';
import TrustSection from '../features/home/TrustSection';
import GuestCta from '../features/home/GuestCta';
import { AppImage, EmptyState, ErrorState, PressableScale, SkeletonList } from '../ui';
import { colors, layout, radius, spacing, typography } from '../theme/tokens';
import { resolveMediaUrl } from '../utils/mediaUrl';

export default function HomeScreen({ navigation }) {
  const { isAuthenticated, user, token } = useAuth();
  const { logoUrl, appName } = useBrand();
  const isCustomer = isAuthenticated && user?.role !== 'muthowif';

  const [loading, setLoading] = useState(true);
  const [refreshing, setRefreshing] = useState(false);
  const [muthowifs, setMuthowifs] = useState([]);
  const [error, setError] = useState(null);
  const [startDate, setStartDate] = useState('');
  const [endDate, setEndDate] = useState('');
  const [searchName, setSearchName] = useState('');
  const [nextBooking, setNextBooking] = useState(null);
  const [unreadMessages, setUnreadMessages] = useState(0);

  const firstName = useMemo(() => user?.name?.split(' ')[0] || 'Jamaah', [user?.name]);
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
    if (muthowifs.length >= 10) return `${muthowifs.length}+ Muthowif Aktif`;
    return '1.200+ Muthowif Aktif';
  }, [muthowifs.length]);

  const heroRatingLabel = useMemo(() => {
    const rated = muthowifs.filter((m) => m.rating);
    if (!rated.length) return '4.9/5 (1.200 review)';
    const avg = rated.reduce((sum, m) => sum + Number(m.rating), 0) / rated.length;
    const reviews = rated.reduce((sum, m) => sum + (m.reviews || 0), 0);
    return `${avg.toFixed(1)}/5 (${reviews || '1.200'} review)`;
  }, [muthowifs]);

  const openDirectory = useCallback((params = {}) => {
    navigation.navigate('Directory', {
      q: params.q ?? searchName.trim(),
      startDate: params.startDate ?? startDate.trim(),
      endDate: params.endDate ?? endDate.trim(),
      sort: params.sort,
      minRating: params.minRating,
    });
  }, [navigation, searchName, startDate, endDate]);

  const openMuthowifDetail = (item) => navigation.navigate('MuthowifDetail', {
    id: item.id, startDate: startDate.trim() || undefined, endDate: endDate.trim() || undefined,
  });

  const openSupport = () => {
    if (!isAuthenticated) {
      navigateRoot(navigation, 'Login');
      return;
    }
    navigation.getParent()?.navigate('SupportTab', { screen: 'SupportList' });
  };

  const loadData = useCallback(async (isRefresh = false) => {
    if (isRefresh) setRefreshing(true);
    else setLoading(true);
    try {
      const homeData = await fetchHomeData();
      let list = homeData.featured_muthowifs || [];
      if (isCustomer && token) {
        try {
          const dash = await fetchCustomerDashboard(token);
          if (dash.top_muthowifs?.length) list = dash.top_muthowifs;
          setNextBooking(dash.next_booking || null);
          setUnreadMessages(dash.unread_messages || 0);
        } catch { setNextBooking(null); }
      } else { setNextBooking(null); setUnreadMessages(0); }
      setMuthowifs(list);
      setError(null);
    } catch (err) {
      setError(err.message || 'Gagal memuat data');
    } finally { setLoading(false); setRefreshing(false); }
  }, [isCustomer, token]);

  useEffect(() => { loadData(); }, [loadData]);

  const goBookings = () => navigation.getParent()?.navigate('BookingsTab', { screen: 'BookingsList' });
  const openNextBooking = () => nextBooking?.id && navigation.getParent()?.navigate('BookingsTab', {
    screen: 'BookingDetail', params: { bookingId: nextBooking.id },
  });
  const openNextPayment = () => nextBooking?.id && navigation.navigate('BookingPayment', { bookingId: nextBooking.id });
  const openNextChat = () => nextBooking?.id && navigation.getParent()?.navigate('ChatTab', {
    screen: 'ChatRoom',
    params: { bookingId: nextBooking.id, bookingCode: nextBooking.booking_code, otherName: nextBooking.muthowif_name || 'Muthowif' },
  });

  return (
    <View style={styles.container}>
      <StatusBar barStyle="dark-content" />
      <SafeAreaView edges={['top']} style={styles.safeTop}>
        <View style={styles.header}>
          <View style={styles.headerBrand}>
            <AppLogo url={logoUrl} name={appName} size={40} showName />
            <Text style={styles.tagline}>Teman Ibadahmu</Text>
          </View>
          <View style={styles.headerActions}>
            <PressableScale onPress={openSupport} haptic="light" style={styles.supportBtn}>
              <Headphones size={14} color={colors.baytgo} strokeWidth={2.2} />
              <Text style={styles.supportText}>Bantuan 24/7</Text>
            </PressableScale>
            {isCustomer ? (
              <PressableScale onPress={goBookings} haptic="light" style={styles.iconBtn}>
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
        <View style={styles.greetingWrap}>
          <Image
            source={require('../../assets/hero/hero-welcome.png')}
            style={styles.greetingBg}
            contentFit="cover"
            contentPosition="center"
          />
          <View style={styles.greetingOverlay} />
          <Text style={styles.greetingHi}>
            {isCustomer ? `Assalamu'alaikum, ${firstName} 👋` : 'Assalamu\'alaikum 👋'}
          </Text>
          <Text style={styles.greetingTitle}>
            Mau cari <Text style={styles.greetingAccent}>Muthowif</Text> untuk ibadahmu?
          </Text>
        </View>

        <SearchPanel
          searchName={searchName}
          onSearchNameChange={setSearchName}
          startDate={startDate}
          endDate={endDate}
          onStartDateChange={(iso) => { setStartDate(iso); if (endDate && iso && endDate < iso) setEndDate(''); }}
          onEndDateChange={setEndDate}
          onClearEndDate={() => setEndDate('')}
          today={today}
          endMinDate={endMinDate}
          endMaxDate={endMaxDate}
          onSearch={() => openDirectory()}
          onServicePress={() => openDirectory()}
        />

        <HeroCarousel
          onCta={() => openDirectory()}
          faces={heroFaces}
          countLabel={heroCountLabel}
          ratingLabel={heroRatingLabel}
        />

        {isCustomer && nextBooking ? (
          <View style={styles.sectionPad}>
            <UpcomingTripCard booking={nextBooking} onPress={openNextBooking} onPay={openNextPayment} onChat={openNextChat} />
          </View>
        ) : null}

        <FeatureChips onFeaturePress={openDirectory} onSeeAll={() => openDirectory()} />

        <View style={styles.sectionPad}>
          <View style={styles.sectionHead}>
            <Text style={styles.sectionTitle}>Muthowif Top Rated</Text>
            <PressableScale onPress={() => openDirectory({ sort: 'rating' })} haptic="light">
              <View style={styles.seeAllRow}>
                <Text style={styles.seeAll}>Lihat semua</Text>
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
            <EmptyState variant="search" title="Belum ada muthowif" description="Muthowif terverifikasi akan muncul di sini." />
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
  supportBtn: {
    flexDirection: 'row',
    alignItems: 'center',
    gap: spacing.xs,
    paddingHorizontal: spacing.sm + 2,
    paddingVertical: spacing.sm,
    borderRadius: radius.full,
    backgroundColor: colors.background,
    borderWidth: 1,
    borderColor: colors.border,
  },
  supportText: {
    fontSize: 10,
    fontWeight: '700',
    fontFamily: 'PlusJakartaSans_700Bold',
    color: colors.baytgo,
  },
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
  greetingWrap: {
    marginHorizontal: layout.screenPadding,
    marginTop: spacing.sm,
    borderRadius: radius.md,
    overflow: 'hidden',
    padding: spacing.lg + 2,
    minHeight: 108,
    justifyContent: 'flex-end',
  },
  greetingBg: { ...StyleSheet.absoluteFillObject, opacity: 0.22 },
  greetingOverlay: {
    ...StyleSheet.absoluteFillObject,
    backgroundColor: 'rgba(255,255,255,0.72)',
  },
  greetingHi: {
    ...typography.caption,
    fontWeight: '700',
    fontFamily: 'PlusJakartaSans_700Bold',
    color: colors.baytgo,
  },
  greetingTitle: {
    marginTop: spacing.sm,
    fontSize: 22,
    lineHeight: 30,
    fontFamily: 'PlusJakartaSans_800ExtraBold',
    fontWeight: '800',
    color: colors.textPrimary,
    letterSpacing: -0.3,
  },
  greetingAccent: { color: colors.primary },
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
