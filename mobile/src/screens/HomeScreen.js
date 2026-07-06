import React, { useCallback, useEffect, useMemo, useState } from 'react';
import {
  View,
  Text,
  StyleSheet,
  ScrollView,
  TouchableOpacity,
  StatusBar,
  ActivityIndicator,
  RefreshControl,
  TextInput,
  Image,
  Dimensions,
} from 'react-native';
import { SafeAreaView } from 'react-native-safe-area-context';
import { LinearGradient } from 'expo-linear-gradient';
import { Ionicons } from '@expo/vector-icons';
import { colors } from '../theme/colors';
import { fetchHomeData } from '../api/home';
import { fetchCustomerDashboard } from '../api/dashboard';
import { useAuth } from '../context/AuthContext';
import { useBrand } from '../context/BrandContext';
import { navigateRoot } from '../navigation/rootNavigation';
import AppLogo from '../components/AppLogo';
import MuthowifListingCard from '../components/MuthowifListingCard';
import DatePickerField, { parseIsoDate } from '../components/DatePickerField';
import { resolveMediaUrl } from '../utils/mediaUrl';
import {
  bookingStatusMeta,
  formatDateRange,
  needsPayment,
  paymentStatusMeta,
} from '../utils/bookingLabels';

const { width: SCREEN_W } = Dimensions.get('window');
const HERO_W = SCREEN_W - 40;

const HERO_SLIDES = [
  {
    id: '1',
    kicker: 'Booking Muthowif Jadi Mudah',
    title: 'Temukan Muthowif Terpercaya untuk Ibadahmu',
    sub: 'Booking mudah, harga transparan, dan jadwal real-time.',
    cta: 'Lihat Muthowif Terpopuler',
    bg: ['#F9F7F2', '#F0EBE0'],
    accent: colors.baytgo,
  },
  {
    id: '2',
    kicker: 'Pendamping Ibadah Profesional',
    title: 'Pilih Muthowif Sesuai Kebutuhan Jamaah',
    sub: 'Filter bahasa, lokasi, dan rating untuk pengalaman terbaik.',
    cta: 'Mulai Pencarian',
    bg: ['#ECFDF5', '#D1FAE5'],
    accent: '#059669',
  },
];

const FEATURES = [
  {
    id: 'top',
    title: 'Top Rated',
    sub: 'Muthowif dengan rating tertinggi',
    icon: 'ribbon',
    bg: '#FEF3C7',
    color: '#D97706',
    sort: 'rating',
  },
  {
    id: 'price',
    title: 'Harga Terjangkau',
    sub: 'Pilihan muthowif harga terbaik',
    icon: 'pricetag',
    bg: '#E0F2FE',
    color: '#0284C7',
    sort: 'price',
  },
  {
    id: 'popular',
    title: 'Paling Banyak Dipesan',
    sub: 'Muthowif favorit jamaah',
    icon: 'flame',
    bg: '#FEE2E2',
    color: '#DC2626',
    sort: 'popular',
  },
];

const TRUST_USPS = [
  { icon: 'shield-checkmark', title: 'Terverifikasi', sub: 'Muthowif melalui proses verifikasi' },
  { icon: 'chatbubbles', title: 'Chat Sebelum Booking', sub: 'Komunikasi langsung sebelum pesan' },
  { icon: 'calendar', title: 'Jadwal Real-time', sub: 'Ketersediaan selalu diperbarui' },
];

function daysUntil(iso) {
  if (!iso) return null;
  const today = new Date();
  today.setHours(0, 0, 0, 0);
  const target = new Date(`${iso}T00:00:00`);
  const diff = Math.ceil((target - today) / 86400000);
  if (diff < 0) return null;
  if (diff === 0) return 'Hari ini';
  if (diff === 1) return 'Besok';
  return `${diff} hari lagi`;
}

function HeroCarousel({ onCta }) {
  const [active, setActive] = useState(0);

  const onScroll = (e) => {
    const x = e.nativeEvent.contentOffset.x;
    const idx = Math.round(x / (HERO_W + 12));
    if (idx !== active) setActive(idx);
  };

  return (
    <View style={styles.heroWrap}>
      <ScrollView
        horizontal
        pagingEnabled
        showsHorizontalScrollIndicator={false}
        onScroll={onScroll}
        scrollEventThrottle={16}
        decelerationRate="fast"
        snapToInterval={HERO_W + 12}
        contentContainerStyle={styles.heroScroll}
      >
        {HERO_SLIDES.map((slide) => (
          <LinearGradient key={slide.id} colors={slide.bg} style={styles.heroCard}>
            <View style={styles.heroCardInner}>
              <View style={styles.heroKicker}>
                <Ionicons name="sparkles" size={12} color={slide.accent} />
                <Text style={[styles.heroKickerText, { color: slide.accent }]}>{slide.kicker}</Text>
              </View>
              <Text style={styles.heroTitle}>{slide.title}</Text>
              <Text style={styles.heroSub}>{slide.sub}</Text>
              <TouchableOpacity
                style={[styles.heroCta, { backgroundColor: slide.accent }]}
                onPress={onCta}
                activeOpacity={0.9}
              >
                <Text style={styles.heroCtaText}>{slide.cta}</Text>
                <Ionicons name="arrow-forward" size={16} color={colors.white} />
              </TouchableOpacity>
            </View>
            <View style={styles.heroIllustration}>
              <View style={[styles.heroOrb, { backgroundColor: slide.accent + '18' }]}>
                <Ionicons name="moon" size={36} color={slide.accent} />
              </View>
            </View>
          </LinearGradient>
        ))}
      </ScrollView>
      <View style={styles.heroDots}>
        {HERO_SLIDES.map((s, i) => (
          <View key={s.id} style={[styles.heroDot, i === active && styles.heroDotActive]} />
        ))}
      </View>
    </View>
  );
}

function UpcomingTripCard({ booking, onPress, onPay, onChat }) {
  if (!booking) return null;

  const statusMeta = bookingStatusMeta(booking.status);
  const paymentMeta = paymentStatusMeta(booking.payment_status);
  const countdown = daysUntil(booking.starts_on);
  const showPay = needsPayment(booking);
  const avatarUri = resolveMediaUrl(booking.muthowif_avatar);

  return (
    <TouchableOpacity style={styles.tripCard} onPress={onPress} activeOpacity={0.92}>
      <View style={styles.tripHeader}>
        <View>
          <Text style={styles.tripKicker}>Perjalanan berikutnya</Text>
          {countdown ? <Text style={styles.tripCountdown}>{countdown}</Text> : null}
        </View>
        <View style={[styles.tripStatus, { backgroundColor: statusMeta.color + '20' }]}>
          <Text style={[styles.tripStatusText, { color: statusMeta.color }]}>{statusMeta.label}</Text>
        </View>
      </View>
      <View style={styles.tripBody}>
        {avatarUri ? (
          <Image source={{ uri: avatarUri }} style={styles.tripAvatar} />
        ) : (
          <View style={[styles.tripAvatar, styles.tripAvatarPh]}>
            <Ionicons name="person" size={22} color={colors.slate400} />
          </View>
        )}
        <View style={styles.tripInfo}>
          <Text style={styles.tripName} numberOfLines={1}>{booking.muthowif_name}</Text>
          <Text style={styles.tripDates}>{formatDateRange(booking.starts_on, booking.ends_on)}</Text>
          <Text style={styles.tripPayment}>{paymentMeta.label}</Text>
        </View>
        <View style={styles.tripActions}>
          {showPay ? (
            <TouchableOpacity style={styles.tripPayBtn} onPress={(e) => { e.stopPropagation?.(); onPay?.(); }}>
              <Text style={styles.tripPayText}>Bayar</Text>
            </TouchableOpacity>
          ) : null}
          <TouchableOpacity style={styles.tripChatBtn} onPress={(e) => { e.stopPropagation?.(); onChat?.(); }}>
            <Ionicons name="chatbubble-ellipses" size={18} color={colors.baytgo} />
          </TouchableOpacity>
        </View>
      </View>
    </TouchableOpacity>
  );
}

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

  const sortedMuthowifs = useMemo(() => {
    return [...muthowifs].sort(
      (a, b) => (parseFloat(b.rating) || 0) - (parseFloat(a.rating) || 0),
    );
  }, [muthowifs]);

  const today = useMemo(() => {
    const d = new Date();
    d.setHours(0, 0, 0, 0);
    return d;
  }, []);

  const endMinDate = startDate ? parseIsoDate(startDate) : today;
  const endMaxDate = useMemo(() => {
    if (!startDate) return undefined;
    const max = parseIsoDate(startDate);
    max.setDate(max.getDate() + 90);
    return max;
  }, [startDate]);

  const handleStartDateChange = (iso) => {
    setStartDate(iso);
    if (endDate && iso && endDate < iso) setEndDate('');
  };

  const openDirectory = (params = {}) => {
    navigation.navigate('Directory', {
      q: params.q ?? searchName.trim(),
      startDate: params.startDate ?? startDate.trim(),
      endDate: params.endDate ?? endDate.trim(),
      sort: params.sort,
      minRating: params.minRating,
    });
  };

  const openMuthowifDetail = (item) => {
    navigation.navigate('MuthowifDetail', {
      id: item.id,
      startDate: startDate.trim() || undefined,
      endDate: endDate.trim() || undefined,
    });
  };

  const openMuthowifBook = (item) => {
    navigation.navigate('MuthowifDetail', {
      id: item.id,
      startDate: startDate.trim() || undefined,
      endDate: endDate.trim() || undefined,
      autoBook: true,
    });
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
        } catch {
          setNextBooking(null);
        }
      } else {
        setNextBooking(null);
        setUnreadMessages(0);
      }

      setMuthowifs(list);
      setError(null);
    } catch (err) {
      setError(err.message || 'Gagal memuat data');
    } finally {
      setLoading(false);
      setRefreshing(false);
    }
  }, [isCustomer, token]);

  useEffect(() => {
    loadData();
  }, [loadData]);

  const goBookings = () => {
    navigation.getParent()?.navigate('BookingsTab', { screen: 'BookingsList' });
  };

  const goChat = () => {
    navigation.getParent()?.navigate('ChatTab', { screen: 'ChatList' });
  };

  const openNextBooking = () => {
    if (!nextBooking?.id) return;
    navigation.getParent()?.navigate('BookingsTab', {
      screen: 'BookingDetail',
      params: { bookingId: nextBooking.id },
    });
  };

  const openNextPayment = () => {
    if (!nextBooking?.id) return;
    navigation.navigate('BookingPayment', { bookingId: nextBooking.id });
  };

  const openNextChat = () => {
    if (!nextBooking?.id) return;
    navigation.getParent()?.navigate('ChatTab', {
      screen: 'ChatRoom',
      params: {
        bookingId: nextBooking.id,
        bookingCode: nextBooking.booking_code,
        otherName: nextBooking.muthowif_name || 'Muthowif',
      },
    });
  };

  const notifCount = unreadMessages;

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
            {isCustomer ? (
              <TouchableOpacity style={styles.iconBtn} onPress={goBookings}>
                <Ionicons name="notifications-outline" size={22} color={colors.baytgo} />
                {notifCount > 0 ? (
                  <View style={styles.notifBadge}>
                    <Text style={styles.notifBadgeText}>{notifCount > 9 ? '9+' : notifCount}</Text>
                  </View>
                ) : null}
              </TouchableOpacity>
            ) : null}
            <TouchableOpacity
              style={styles.iconBtn}
              onPress={() => {
                if (isAuthenticated) {
                  navigation.getParent()?.navigate('ProfileTab');
                } else {
                  navigateRoot(navigation, 'Login');
                }
              }}
            >
              <Ionicons
                name={isAuthenticated ? 'person-circle-outline' : 'menu-outline'}
                size={24}
                color={colors.baytgo}
              />
            </TouchableOpacity>
          </View>
        </View>

        {isCustomer ? (
          <Text style={styles.greeting}>Assalamu'alaikum, {firstName} 👋</Text>
        ) : null}
      </SafeAreaView>

      <ScrollView
        showsVerticalScrollIndicator={false}
        refreshControl={
          <RefreshControl refreshing={refreshing} onRefresh={() => loadData(true)} tintColor={colors.baytgo} />
        }
      >
        <HeroCarousel onCta={() => openDirectory()} />

        <View style={styles.searchCard}>
          <LinearGradient
            colors={['rgba(26,61,52,0.06)', 'transparent']}
            style={styles.searchCardAccent}
          />
          <View style={styles.searchCardHead}>
            <View style={styles.searchCardIcon}>
              <Ionicons name="search" size={18} color={colors.baytgo} />
            </View>
            <View style={styles.searchCardHeadText}>
              <Text style={styles.searchCardTitle}>Cari Muthowif</Text>
              <Text style={styles.searchCardSub}>Atur tanggal perjalanan lalu cari pendamping ibadah</Text>
            </View>
          </View>

          <View style={styles.dateRow}>
            <DatePickerField
              label="Berangkat"
              value={startDate}
              onChange={handleStartDateChange}
              placeholder="Pilih tanggal"
              minimumDate={today}
              variant="chip"
            />
            <View style={styles.dateArrow}>
              <Ionicons name="arrow-forward" size={16} color={colors.slate400} />
            </View>
            <DatePickerField
              label="Pulang"
              value={endDate}
              onChange={setEndDate}
              placeholder="Opsional"
              minimumDate={endMinDate}
              maximumDate={endMaxDate}
              clearable
              onClear={() => setEndDate('')}
              variant="chip"
            />
          </View>

          <View style={styles.searchInputWrap}>
            <Ionicons name="person-outline" size={18} color={colors.slate400} />
            <TextInput
              style={styles.searchInput}
              value={searchName}
              onChangeText={setSearchName}
              placeholder="Nama, bahasa, atau keahlian muthowif..."
              placeholderTextColor={colors.slate400}
              returnKeyType="search"
              onSubmitEditing={() => openDirectory()}
            />
          </View>

          <TouchableOpacity style={styles.searchCta} onPress={() => openDirectory()} activeOpacity={0.9}>
            <Ionicons name="search" size={18} color={colors.white} />
            <Text style={styles.searchCtaText}>Cari Muthowif</Text>
          </TouchableOpacity>
        </View>

        {isCustomer && nextBooking ? (
          <View style={styles.sectionPad}>
            <UpcomingTripCard
              booking={nextBooking}
              onPress={openNextBooking}
              onPay={openNextPayment}
              onChat={openNextChat}
            />
          </View>
        ) : null}

        <ScrollView
          horizontal
          showsHorizontalScrollIndicator={false}
          contentContainerStyle={styles.featureRow}
          nestedScrollEnabled
        >
          {FEATURES.map((feat) => (
            <View key={feat.id} style={[styles.featureCard, { backgroundColor: feat.bg }]}>
              <View style={[styles.featureIconWrap, { backgroundColor: `${feat.color}18` }]}>
                <Ionicons name={feat.icon} size={20} color={feat.color} />
              </View>
              <Text style={styles.featureTitle}>{feat.title}</Text>
              <Text style={styles.featureSub}>{feat.sub}</Text>
            </View>
          ))}
        </ScrollView>

        <View style={styles.sectionPad}>
          <View style={styles.sectionHead}>
            <Text style={styles.sectionTitle}>Muthowif Tersedia</Text>
            <TouchableOpacity onPress={() => openDirectory()}>
              <Text style={styles.seeAll}>Lihat semua ›</Text>
            </TouchableOpacity>
          </View>

          <View style={styles.listToolbar}>
            <TouchableOpacity style={styles.toolbarBtn} onPress={() => openDirectory()} activeOpacity={0.88}>
              <Ionicons name="options-outline" size={15} color={colors.baytgo} />
              <Text style={styles.toolbarBtnText}>Filter</Text>
              <Ionicons name="chevron-down" size={14} color={colors.slate400} />
            </TouchableOpacity>
            <View style={styles.toolbarBtn}>
              <Text style={styles.toolbarBtnText}>Rating tertinggi</Text>
              <Ionicons name="chevron-down" size={14} color={colors.slate400} />
            </View>
          </View>

          {loading && !refreshing ? (
            <ActivityIndicator color={colors.baytgo} style={styles.loader} />
          ) : error ? (
            <View style={styles.emptyBox}>
              <Text style={styles.emptyText}>{error}</Text>
              <TouchableOpacity onPress={() => loadData()}>
                <Text style={styles.retryText}>Coba lagi</Text>
              </TouchableOpacity>
            </View>
          ) : muthowifs.length === 0 ? (
            <View style={styles.emptyBox}>
              <Ionicons name="people-outline" size={32} color={colors.slate400} />
              <Text style={styles.emptyText}>Muthowif terverifikasi akan muncul di sini.</Text>
            </View>
          ) : (
            sortedMuthowifs.map((item) => (
              <MuthowifListingCard
                key={item.id}
                item={item}
                onPressDetail={() => openMuthowifDetail(item)}
                onPressBook={() => openMuthowifBook(item)}
              />
            ))
          )}
        </View>

        <View style={styles.trustSection}>
          {TRUST_USPS.map((usp) => (
            <View key={usp.title} style={styles.trustItem}>
              <View style={styles.trustIcon}>
                <Ionicons name={usp.icon} size={22} color={colors.baytgo} />
              </View>
              <View style={{ flex: 1 }}>
                <Text style={styles.trustTitle}>{usp.title}</Text>
                <Text style={styles.trustSub}>{usp.sub}</Text>
              </View>
            </View>
          ))}
        </View>

        {!isAuthenticated ? (
          <View style={styles.registerSection}>
            <Text style={styles.registerTitle}>Belum punya akun?</Text>
            <Text style={styles.registerSub}>Daftar gratis dan mulai booking muthowif</Text>
            <TouchableOpacity
              style={styles.registerBtn}
              onPress={() => navigateRoot(navigation, 'Register', { role: 'customer' })}
              activeOpacity={0.9}
            >
              <LinearGradient colors={[colors.baytgo, colors.baytgoDark]} style={styles.registerGradient}>
                <Text style={styles.registerBtnText}>Daftar Sekarang</Text>
              </LinearGradient>
            </TouchableOpacity>
            <TouchableOpacity onPress={() => navigateRoot(navigation, 'Login')}>
              <Text style={styles.loginLink}>Sudah punya akun? <Text style={styles.loginBold}>Masuk</Text></Text>
            </TouchableOpacity>
          </View>
        ) : null}

        <View style={{ height: 24 }} />
      </ScrollView>
    </View>
  );
}

const styles = StyleSheet.create({
  container: { flex: 1, backgroundColor: '#F8F9FA' },
  safeTop: { backgroundColor: colors.white, borderBottomWidth: 1, borderBottomColor: colors.slate100 },
  header: {
    flexDirection: 'row',
    alignItems: 'center',
    justifyContent: 'space-between',
    paddingHorizontal: 20,
    paddingVertical: 10,
  },
  headerBrand: { flex: 1, gap: 2 },
  tagline: { marginLeft: 50, fontSize: 12, fontWeight: '600', color: colors.slate500, fontStyle: 'italic' },
  headerActions: { flexDirection: 'row', gap: 8 },
  iconBtn: {
    width: 42,
    height: 42,
    borderRadius: 14,
    backgroundColor: colors.canvas,
    alignItems: 'center',
    justifyContent: 'center',
    borderWidth: 1,
    borderColor: colors.slate100,
  },
  notifBadge: {
    position: 'absolute',
    top: 6,
    right: 6,
    minWidth: 16,
    height: 16,
    borderRadius: 8,
    backgroundColor: colors.emerald600,
    alignItems: 'center',
    justifyContent: 'center',
    paddingHorizontal: 4,
    borderWidth: 1.5,
    borderColor: colors.white,
  },
  notifBadgeText: { fontSize: 9, fontWeight: '900', color: colors.white },
  greeting: {
    paddingHorizontal: 20,
    paddingBottom: 10,
    fontSize: 15,
    fontWeight: '700',
    color: colors.baytgo,
  },
  heroWrap: { marginTop: 16, marginBottom: 4 },
  heroScroll: { paddingHorizontal: 20, gap: 12 },
  heroCard: {
    width: HERO_W,
    minHeight: 168,
    borderRadius: 22,
    overflow: 'hidden',
    flexDirection: 'row',
    borderWidth: 1,
    borderColor: 'rgba(26,61,52,0.08)',
    shadowColor: '#0F2E28',
    shadowOffset: { width: 0, height: 8 },
    shadowOpacity: 0.08,
    shadowRadius: 16,
    elevation: 4,
  },
  heroCardInner: { flex: 1, padding: 20, paddingRight: 4, justifyContent: 'center' },
  heroKicker: {
    flexDirection: 'row',
    alignItems: 'center',
    gap: 5,
    marginBottom: 10,
    alignSelf: 'flex-start',
    backgroundColor: 'rgba(255,255,255,0.65)',
    paddingHorizontal: 10,
    paddingVertical: 5,
    borderRadius: 999,
  },
  heroKickerText: { fontSize: 10, fontWeight: '800' },
  heroTitle: { fontSize: 19, fontWeight: '900', color: colors.baytgo, lineHeight: 26 },
  heroSub: { marginTop: 8, fontSize: 12, fontWeight: '600', color: colors.slate600, lineHeight: 18 },
  heroCta: {
    flexDirection: 'row',
    alignItems: 'center',
    alignSelf: 'flex-start',
    gap: 6,
    marginTop: 16,
    paddingHorizontal: 16,
    paddingVertical: 11,
    borderRadius: 12,
  },
  heroCtaText: { fontSize: 12, fontWeight: '800', color: colors.white },
  heroIllustration: {
    width: 96,
    alignItems: 'center',
    justifyContent: 'center',
    paddingRight: 14,
  },
  heroOrb: {
    width: 72,
    height: 72,
    borderRadius: 36,
    alignItems: 'center',
    justifyContent: 'center',
  },
  heroDots: { flexDirection: 'row', justifyContent: 'center', gap: 6, marginTop: 12 },
  heroDot: { width: 6, height: 6, borderRadius: 3, backgroundColor: colors.slate200 },
  heroDotActive: { width: 18, backgroundColor: colors.baytgo },
  searchCard: {
    marginHorizontal: 20,
    marginTop: 16,
    backgroundColor: colors.white,
    borderRadius: 22,
    padding: 18,
    borderWidth: 1,
    borderColor: 'rgba(26,61,52,0.08)',
    shadowColor: '#0F2E28',
    shadowOffset: { width: 0, height: 10 },
    shadowOpacity: 0.12,
    shadowRadius: 24,
    elevation: 10,
    overflow: 'hidden',
  },
  searchCardAccent: {
    position: 'absolute',
    top: 0,
    left: 0,
    right: 0,
    height: 80,
  },
  searchCardHead: {
    flexDirection: 'row',
    alignItems: 'center',
    gap: 12,
    marginBottom: 16,
  },
  searchCardIcon: {
    width: 40,
    height: 40,
    borderRadius: 12,
    backgroundColor: colors.baytgoLight,
    alignItems: 'center',
    justifyContent: 'center',
  },
  searchCardHeadText: { flex: 1 },
  searchCardTitle: { fontSize: 17, fontWeight: '900', color: colors.baytgo },
  searchCardSub: { marginTop: 2, fontSize: 12, fontWeight: '600', color: colors.slate500, lineHeight: 17 },
  dateRow: {
    flexDirection: 'row',
    alignItems: 'center',
    gap: 6,
    marginBottom: 12,
  },
  dateArrow: {
    width: 24,
    alignItems: 'center',
    justifyContent: 'center',
    paddingTop: 14,
  },
  searchInputWrap: {
    flexDirection: 'row',
    alignItems: 'center',
    gap: 10,
    backgroundColor: colors.canvas,
    borderRadius: 14,
    paddingHorizontal: 14,
    borderWidth: 1,
    borderColor: 'rgba(26,61,52,0.08)',
    marginBottom: 12,
  },
  searchInput: { flex: 1, paddingVertical: 13, fontSize: 14, fontWeight: '600', color: colors.slate900 },
  searchCta: {
    flexDirection: 'row',
    alignItems: 'center',
    justifyContent: 'center',
    gap: 8,
    backgroundColor: colors.baytgo,
    borderRadius: 14,
    paddingVertical: 15,
  },
  searchCtaText: { fontSize: 15, fontWeight: '800', color: colors.white },
  sectionPad: { paddingHorizontal: 20, marginTop: 20 },
  tripCard: {
    backgroundColor: colors.white,
    borderRadius: 16,
    padding: 14,
    borderWidth: 1,
    borderColor: colors.slate100,
    borderLeftWidth: 4,
    borderLeftColor: colors.baytgo,
  },
  tripHeader: { flexDirection: 'row', justifyContent: 'space-between', alignItems: 'flex-start' },
  tripKicker: { fontSize: 10, fontWeight: '800', color: colors.slate500, textTransform: 'uppercase' },
  tripCountdown: { marginTop: 2, fontSize: 16, fontWeight: '900', color: colors.baytgo },
  tripStatus: { paddingHorizontal: 8, paddingVertical: 4, borderRadius: 999 },
  tripStatusText: { fontSize: 10, fontWeight: '800' },
  tripBody: { flexDirection: 'row', alignItems: 'center', gap: 12, marginTop: 12 },
  tripAvatar: { width: 48, height: 48, borderRadius: 14 },
  tripAvatarPh: { backgroundColor: colors.slate100, alignItems: 'center', justifyContent: 'center' },
  tripInfo: { flex: 1 },
  tripName: { fontSize: 14, fontWeight: '900', color: colors.slate900 },
  tripDates: { marginTop: 2, fontSize: 12, fontWeight: '600', color: colors.slate600 },
  tripPayment: { marginTop: 2, fontSize: 11, fontWeight: '700', color: colors.goldMuted },
  tripActions: { flexDirection: 'row', alignItems: 'center', gap: 6 },
  tripPayBtn: { backgroundColor: colors.gold, paddingHorizontal: 12, paddingVertical: 7, borderRadius: 10 },
  tripPayText: { fontSize: 11, fontWeight: '900', color: colors.baytgoDark },
  tripChatBtn: {
    width: 34,
    height: 34,
    borderRadius: 10,
    backgroundColor: colors.canvas,
    alignItems: 'center',
    justifyContent: 'center',
    borderWidth: 1,
    borderColor: colors.slate100,
  },
  featureRow: { paddingHorizontal: 20, gap: 12, paddingTop: 20, paddingBottom: 4 },
  featureCard: {
    width: SCREEN_W * 0.44,
    borderRadius: 18,
    padding: 16,
    minHeight: 110,
    borderWidth: 1,
    borderColor: 'rgba(0,0,0,0.04)',
  },
  featureIconWrap: {
    width: 40,
    height: 40,
    borderRadius: 12,
    alignItems: 'center',
    justifyContent: 'center',
  },
  featureTitle: { marginTop: 12, fontSize: 13, fontWeight: '900', color: colors.slate900 },
  featureSub: { marginTop: 4, fontSize: 11, fontWeight: '600', color: colors.slate600, lineHeight: 15 },
  sectionHead: { flexDirection: 'row', alignItems: 'center', justifyContent: 'space-between', marginBottom: 12 },
  listToolbar: { flexDirection: 'row', gap: 10, marginBottom: 14 },
  toolbarBtn: {
    flex: 1,
    flexDirection: 'row',
    alignItems: 'center',
    justifyContent: 'center',
    gap: 6,
    paddingVertical: 10,
    paddingHorizontal: 12,
    borderRadius: 12,
    backgroundColor: colors.white,
    borderWidth: 1,
    borderColor: colors.slate200,
  },
  toolbarBtnText: { fontSize: 13, fontWeight: '700', color: colors.slate700 },
  sectionTitle: { fontSize: 18, fontWeight: '900', color: colors.baytgo },
  seeAll: { fontSize: 13, fontWeight: '800', color: colors.goldMuted },
  loader: { marginVertical: 24 },
  emptyBox: {
    backgroundColor: colors.white,
    borderRadius: 16,
    borderWidth: 1,
    borderStyle: 'dashed',
    borderColor: colors.slate200,
    padding: 24,
    alignItems: 'center',
    gap: 10,
  },
  emptyText: { fontSize: 14, color: colors.slate500, fontWeight: '600', textAlign: 'center' },
  retryText: { fontSize: 14, fontWeight: '800', color: colors.baytgo },
  trustSection: {
    paddingHorizontal: 20,
    marginTop: 12,
    gap: 10,
  },
  trustItem: {
    flexDirection: 'row',
    alignItems: 'center',
    gap: 12,
    backgroundColor: colors.white,
    borderRadius: 16,
    padding: 14,
    borderWidth: 1,
    borderColor: colors.slate100,
    marginBottom: 8,
  },
  trustIcon: {
    width: 44,
    height: 44,
    borderRadius: 14,
    backgroundColor: colors.baytgoLight,
    alignItems: 'center',
    justifyContent: 'center',
  },
  trustTitle: { fontSize: 13, fontWeight: '900', color: colors.baytgo },
  trustSub: { marginTop: 2, fontSize: 11, fontWeight: '600', color: colors.slate500, lineHeight: 15 },
  registerSection: {
    marginHorizontal: 20,
    marginTop: 20,
    backgroundColor: colors.white,
    borderRadius: 18,
    padding: 20,
    borderWidth: 1,
    borderColor: colors.slate100,
    alignItems: 'center',
  },
  registerTitle: { fontSize: 17, fontWeight: '900', color: colors.baytgo },
  registerSub: { marginTop: 6, fontSize: 13, fontWeight: '600', color: colors.slate500, textAlign: 'center' },
  registerBtn: { width: '100%', marginTop: 16, borderRadius: 14, overflow: 'hidden' },
  registerGradient: { paddingVertical: 15, alignItems: 'center' },
  registerBtnText: { fontSize: 15, fontWeight: '800', color: colors.white },
  loginLink: { marginTop: 14, fontSize: 14, color: colors.slate500, fontWeight: '600' },
  loginBold: { color: colors.baytgo, fontWeight: '800' },
});
