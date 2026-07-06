import React, { useCallback, useEffect, useMemo, useState } from 'react';
import {
  View,
  Text,
  StyleSheet,
  ScrollView,
  TouchableOpacity,
  ActivityIndicator,
  RefreshControl,
  Image,
  Dimensions,
} from 'react-native';
import { SafeAreaView } from 'react-native-safe-area-context';
import { LinearGradient } from 'expo-linear-gradient';
import { Ionicons } from '@expo/vector-icons';
import { useAuth } from '../context/AuthContext';
import { fetchCustomerDashboard } from '../api/dashboard';
import MuthowifCard from '../components/MuthowifCard';
import { colors } from '../theme/colors';
import { bookingStatusMeta, formatDateRange, needsPayment, paymentStatusMeta } from '../utils/bookingLabels';
import { resolveMediaUrl } from '../utils/mediaUrl';

const { width: SCREEN_W } = Dimensions.get('window');

const QUICK_SERVICES = [
  { key: 'search', icon: 'compass', label: 'Cari\nMuthowif', bg: '#E0F2FE', color: '#0284C7' },
  { key: 'bookings', icon: 'airplane', label: 'Pesanan\nSaya', bg: '#DCFCE7', color: '#16A34A' },
  { key: 'chat', icon: 'chatbubbles', label: 'Chat\nBooking', bg: '#FEF3C7', color: '#D97706' },
  { key: 'support', icon: 'headset', label: 'Bantuan\nCS', bg: '#EDE9FE', color: '#7C3AED' },
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

function StatPill({ label, value, color }) {
  return (
    <View style={styles.statPill}>
      <View style={[styles.statDot, { backgroundColor: color }]} />
      <Text style={styles.statPillValue}>{value}</Text>
      <Text style={styles.statPillLabel}>{label}</Text>
    </View>
  );
}

function ServiceTile({ item, badge, onPress }) {
  return (
    <TouchableOpacity style={styles.serviceTile} onPress={onPress} activeOpacity={0.88}>
      <View style={[styles.serviceIcon, { backgroundColor: item.bg }]}>
        <Ionicons name={item.icon} size={24} color={item.color} />
        {badge ? (
          <View style={styles.serviceBadge}>
            <Text style={styles.serviceBadgeText}>{badge > 99 ? '99+' : badge}</Text>
          </View>
        ) : null}
      </View>
      <Text style={styles.serviceLabel}>{item.label}</Text>
    </TouchableOpacity>
  );
}

function UpcomingTripCard({ booking, onPress, onPay, onChat }) {
  if (!booking) {
    return (
      <View style={styles.emptyTrip}>
        <LinearGradient colors={['#1A3D34', '#2D6A5A']} style={styles.emptyTripGradient}>
          <View style={styles.emptyTripIcon}>
            <Ionicons name="map-outline" size={32} color={colors.gold} />
          </View>
          <Text style={styles.emptyTripTitle}>Belum ada perjalanan</Text>
          <Text style={styles.emptyTripSub}>
            Temukan muthowif terpercaya dan mulai rencanakan ibadah umrah Anda.
          </Text>
          <TouchableOpacity style={styles.emptyTripBtn} onPress={onPress} activeOpacity={0.9}>
            <Text style={styles.emptyTripBtnText}>Cari Muthowif Sekarang</Text>
            <Ionicons name="arrow-forward" size={16} color={colors.baytgo} />
          </TouchableOpacity>
        </LinearGradient>
      </View>
    );
  }

  const statusMeta = bookingStatusMeta(booking.status);
  const paymentMeta = paymentStatusMeta(booking.payment_status);
  const countdown = daysUntil(booking.starts_on);
  const showPay = needsPayment(booking);

  return (
    <TouchableOpacity style={styles.tripCard} onPress={onPress} activeOpacity={0.92}>
      <LinearGradient colors={['#1A3D34', '#256B5C', '#1A3D34']} start={{ x: 0, y: 0 }} end={{ x: 1, y: 1 }} style={styles.tripGradient}>
        <View style={styles.tripTop}>
          <View>
            <Text style={styles.tripKicker}>Perjalanan berikutnya</Text>
            {countdown ? <Text style={styles.tripCountdown}>{countdown}</Text> : null}
          </View>
          <View style={[styles.tripStatusBadge, { backgroundColor: statusMeta.color + '33' }]}>
            <Text style={[styles.tripStatusText, { color: colors.white }]}>{statusMeta.label}</Text>
          </View>
        </View>

        <View style={styles.tripBody}>
          <Image source={{ uri: resolveMediaUrl(booking.muthowif_avatar) }} style={styles.tripAvatar} />
          <View style={styles.tripInfo}>
            <Text style={styles.tripMuthowif} numberOfLines={1}>{booking.muthowif_name}</Text>
            <Text style={styles.tripDates}>{formatDateRange(booking.starts_on, booking.ends_on)}</Text>
            <Text style={styles.tripCode}>#{booking.booking_code}</Text>
          </View>
        </View>

        <View style={styles.tripFooter}>
          <View style={[styles.paymentChip, { backgroundColor: paymentMeta.color + '30' }]}>
            <Ionicons
              name={booking.payment_status === 'paid' ? 'checkmark-circle' : 'time'}
              size={14}
              color={colors.goldLight}
            />
            <Text style={styles.paymentChipText}>{paymentMeta.label}</Text>
          </View>
          <View style={styles.tripActions}>
            {showPay ? (
              <TouchableOpacity
                style={styles.tripPayBtn}
                onPress={(e) => {
                  e.stopPropagation?.();
                  onPay?.();
                }}
                hitSlop={8}
              >
                <Text style={styles.tripPayText}>Bayar</Text>
              </TouchableOpacity>
            ) : null}
            <TouchableOpacity
              style={styles.tripChatBtn}
              onPress={(e) => {
                e.stopPropagation?.();
                onChat?.();
              }}
              hitSlop={8}
            >
              <Ionicons name="chatbubble-ellipses" size={16} color={colors.baytgo} />
            </TouchableOpacity>
          </View>
        </View>
      </LinearGradient>
    </TouchableOpacity>
  );
}

export default function CustomerDashboardScreen({ navigation }) {
  const { user, token } = useAuth();
  const [loading, setLoading] = useState(true);
  const [refreshing, setRefreshing] = useState(false);
  const [error, setError] = useState(null);
  const [stats, setStats] = useState([]);
  const [topMuthowifs, setTopMuthowifs] = useState([]);
  const [unreadMessages, setUnreadMessages] = useState(0);
  const [nextBooking, setNextBooking] = useState(null);

  const firstName = useMemo(() => user?.name?.split(' ')[0] || 'Jamaah', [user?.name]);

  const loadDashboard = useCallback(async (refresh = false) => {
    if (refresh) setRefreshing(true);
    else setLoading(true);

    try {
      const data = await fetchCustomerDashboard(token);
      setStats(data.stats || []);
      setUnreadMessages(data.unread_messages || 0);
      setTopMuthowifs(data.top_muthowifs || []);
      setNextBooking(data.next_booking || null);
      setError(null);
    } catch (err) {
      setError(err.message || 'Gagal memuat dashboard');
    } finally {
      setLoading(false);
      setRefreshing(false);
    }
  }, [token]);

  useEffect(() => {
    loadDashboard();
  }, [loadDashboard]);

  const goDirectory = () => navigation.navigate('Directory');

  const goBookings = () => {
    navigation.getParent()?.navigate('BookingsTab', { screen: 'BookingsList' });
  };

  const goChat = () => {
    navigation.getParent()?.navigate('ChatTab', { screen: 'ChatList' });
  };

  const goSupport = () => {
    navigation.getParent()?.navigate('SupportTab', { screen: 'SupportList' });
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

  const openMuthowif = (item) => {
    navigation.navigate('MuthowifDetail', { id: item.id });
  };

  const handleServicePress = (key) => {
    if (key === 'search') goDirectory();
    else if (key === 'bookings') goBookings();
    else if (key === 'chat') goChat();
    else if (key === 'support') goSupport();
  };

  return (
    <View style={styles.root}>
      <ScrollView
        showsVerticalScrollIndicator={false}
        bounces
        refreshControl={
          <RefreshControl refreshing={refreshing} onRefresh={() => loadDashboard(true)} tintColor={colors.white} />
        }
      >
        <LinearGradient colors={['#0F2E28', '#1A3D34', '#256B5C']} style={styles.hero}>
          <SafeAreaView edges={['top']}>
            <View style={styles.heroInner}>
              <View style={styles.heroTop}>
                <View>
                  <Text style={styles.heroGreet}>Assalamu'alaikum,</Text>
                  <Text style={styles.heroName}>{firstName} 👋</Text>
                </View>
                <TouchableOpacity style={styles.bellBtn} onPress={goBookings}>
                  <Ionicons name="notifications-outline" size={22} color={colors.white} />
                  {unreadMessages > 0 ? <View style={styles.bellDot} /> : null}
                </TouchableOpacity>
              </View>
              <Text style={styles.heroTagline}>Rencanakan ibadah umrah Anda dengan nyaman</Text>
            </View>
          </SafeAreaView>
        </LinearGradient>

        <TouchableOpacity style={styles.searchCard} onPress={goDirectory} activeOpacity={0.92}>
          <View style={styles.searchRow}>
            <View style={styles.searchIconWrap}>
              <Ionicons name="search" size={20} color={colors.baytgo} />
            </View>
            <View style={styles.searchTexts}>
              <Text style={styles.searchTitle}>Mau ke mana?</Text>
              <Text style={styles.searchSub}>Cari muthowif · pilih tanggal perjalanan</Text>
            </View>
            <View style={styles.searchGo}>
              <Ionicons name="chevron-forward" size={18} color={colors.slate400} />
            </View>
          </View>
          <View style={styles.searchChips}>
            <View style={styles.searchChip}>
              <Ionicons name="calendar-outline" size={12} color={colors.baytgo} />
              <Text style={styles.searchChipText}>Fleksibel</Text>
            </View>
            <View style={styles.searchChip}>
              <Ionicons name="shield-checkmark-outline" size={12} color={colors.baytgo} />
              <Text style={styles.searchChipText}>Terverifikasi</Text>
            </View>
            <View style={styles.searchChip}>
              <Ionicons name="cash-outline" size={12} color={colors.baytgo} />
              <Text style={styles.searchChipText}>Harga jelas</Text>
            </View>
          </View>
        </TouchableOpacity>

        <View style={styles.body}>
          {loading && !refreshing ? (
            <ActivityIndicator color={colors.baytgo} style={styles.loader} />
          ) : null}

          {error ? (
            <View style={styles.errorBox}>
              <Text style={styles.errorText}>{error}</Text>
              <TouchableOpacity onPress={() => loadDashboard()}>
                <Text style={styles.retry}>Coba lagi</Text>
              </TouchableOpacity>
            </View>
          ) : null}

          {!loading ? (
            <>
              <UpcomingTripCard
                booking={nextBooking}
                onPress={nextBooking ? openNextBooking : goDirectory}
                onPay={openNextPayment}
                onChat={openNextChat}
              />

              <View style={styles.serviceGrid}>
                {QUICK_SERVICES.map((item) => (
                  <ServiceTile
                    key={item.key}
                    item={item}
                    badge={item.key === 'chat' && unreadMessages > 0 ? unreadMessages : null}
                    onPress={() => handleServicePress(item.key)}
                  />
                ))}
              </View>

              {stats.length > 0 ? (
                <ScrollView
                  horizontal
                  showsHorizontalScrollIndicator={false}
                  contentContainerStyle={styles.statsRow}
                >
                  {stats.map((stat) => (
                    <StatPill key={stat.label} label={stat.label} value={stat.value} color={stat.color} />
                  ))}
                </ScrollView>
              ) : null}

              <View style={styles.promoBanner}>
                <LinearGradient
                  colors={[colors.goldLight + 'CC', '#F5E6C8']}
                  start={{ x: 0, y: 0 }}
                  end={{ x: 1, y: 0 }}
                  style={styles.promoGradient}
                >
                  <Ionicons name="star" size={28} color={colors.goldMuted} />
                  <View style={styles.promoTexts}>
                    <Text style={styles.promoTitle}>Muthowif terverifikasi</Text>
                    <Text style={styles.promoSub}>Pendamping ibadah terpercaya di Tanah Suci</Text>
                  </View>
                </LinearGradient>
              </View>

              <View style={styles.sectionHead}>
                <Text style={styles.sectionTitle}>Pilihan untuk Anda</Text>
                <TouchableOpacity onPress={goDirectory}>
                  <Text style={styles.seeAll}>Lihat semua</Text>
                </TouchableOpacity>
              </View>

              {topMuthowifs.length === 0 ? (
                <Text style={styles.muted}>Belum ada rekomendasi muthowif.</Text>
              ) : (
                <ScrollView
                  horizontal
                  showsHorizontalScrollIndicator={false}
                  contentContainerStyle={styles.muthowifRow}
                  nestedScrollEnabled
                >
                  {topMuthowifs.map((item) => (
                    <MuthowifCard key={item.id} item={item} onPress={() => openMuthowif(item)} />
                  ))}
                </ScrollView>
              )}
            </>
          ) : null}
        </View>
      </ScrollView>
    </View>
  );
}

const styles = StyleSheet.create({
  root: { flex: 1, backgroundColor: colors.canvas },
  hero: {
    paddingBottom: 56,
    borderBottomLeftRadius: 28,
    borderBottomRightRadius: 28,
  },
  heroInner: { paddingHorizontal: 20, paddingTop: 8, paddingBottom: 4 },
  heroTop: { flexDirection: 'row', justifyContent: 'space-between', alignItems: 'flex-start' },
  heroGreet: { fontSize: 14, fontWeight: '600', color: 'rgba(255,255,255,0.75)' },
  heroName: { fontSize: 26, fontWeight: '900', color: colors.white, marginTop: 2, letterSpacing: -0.5 },
  heroTagline: { marginTop: 10, fontSize: 13, fontWeight: '500', color: 'rgba(255,255,255,0.65)', lineHeight: 18 },
  bellBtn: {
    width: 44,
    height: 44,
    borderRadius: 14,
    backgroundColor: 'rgba(255,255,255,0.12)',
    alignItems: 'center',
    justifyContent: 'center',
    borderWidth: 1,
    borderColor: 'rgba(255,255,255,0.15)',
  },
  bellDot: {
    position: 'absolute',
    top: 10,
    right: 10,
    width: 8,
    height: 8,
    borderRadius: 4,
    backgroundColor: '#F59E0B',
    borderWidth: 1.5,
    borderColor: '#1A3D34',
  },
  searchCard: {
    marginHorizontal: 20,
    marginTop: -40,
    backgroundColor: colors.white,
    borderRadius: 20,
    padding: 16,
    shadowColor: '#0F2E28',
    shadowOffset: { width: 0, height: 8 },
    shadowOpacity: 0.12,
    shadowRadius: 20,
    elevation: 8,
    borderWidth: 1,
    borderColor: colors.slate100,
  },
  searchRow: { flexDirection: 'row', alignItems: 'center', gap: 12 },
  searchIconWrap: {
    width: 44,
    height: 44,
    borderRadius: 14,
    backgroundColor: colors.emerald50,
    alignItems: 'center',
    justifyContent: 'center',
  },
  searchTexts: { flex: 1 },
  searchTitle: { fontSize: 16, fontWeight: '800', color: colors.slate900 },
  searchSub: { marginTop: 2, fontSize: 12, fontWeight: '600', color: colors.slate500 },
  searchGo: {
    width: 32,
    height: 32,
    borderRadius: 10,
    backgroundColor: colors.slate100,
    alignItems: 'center',
    justifyContent: 'center',
  },
  searchChips: { flexDirection: 'row', flexWrap: 'wrap', gap: 8, marginTop: 14 },
  searchChip: {
    flexDirection: 'row',
    alignItems: 'center',
    gap: 4,
    backgroundColor: colors.canvas,
    paddingHorizontal: 10,
    paddingVertical: 6,
    borderRadius: 999,
    borderWidth: 1,
    borderColor: colors.slate100,
  },
  searchChipText: { fontSize: 11, fontWeight: '700', color: colors.baytgo },
  body: { paddingHorizontal: 20, paddingTop: 20, paddingBottom: 32 },
  loader: { marginVertical: 24 },
  errorBox: {
    backgroundColor: colors.white,
    borderRadius: 16,
    padding: 16,
    marginBottom: 16,
    borderWidth: 1,
    borderColor: colors.slate100,
  },
  errorText: { fontSize: 14, color: colors.slate500, fontWeight: '600' },
  retry: { marginTop: 8, fontSize: 14, fontWeight: '800', color: colors.baytgo },
  tripCard: { borderRadius: 22, overflow: 'hidden', marginBottom: 20 },
  tripGradient: { padding: 18 },
  tripTop: { flexDirection: 'row', justifyContent: 'space-between', alignItems: 'flex-start' },
  tripKicker: { fontSize: 11, fontWeight: '800', color: 'rgba(255,255,255,0.65)', textTransform: 'uppercase', letterSpacing: 0.8 },
  tripCountdown: { marginTop: 4, fontSize: 22, fontWeight: '900', color: colors.gold },
  tripStatusBadge: { paddingHorizontal: 10, paddingVertical: 5, borderRadius: 999 },
  tripStatusText: { fontSize: 11, fontWeight: '800' },
  tripBody: { flexDirection: 'row', alignItems: 'center', gap: 14, marginTop: 16 },
  tripAvatar: { width: 56, height: 56, borderRadius: 16, backgroundColor: 'rgba(255,255,255,0.2)', borderWidth: 2, borderColor: 'rgba(255,255,255,0.3)' },
  tripInfo: { flex: 1 },
  tripMuthowif: { fontSize: 17, fontWeight: '900', color: colors.white },
  tripDates: { marginTop: 4, fontSize: 13, fontWeight: '600', color: 'rgba(255,255,255,0.8)' },
  tripCode: { marginTop: 2, fontSize: 11, fontWeight: '700', color: 'rgba(255,255,255,0.5)' },
  tripFooter: { flexDirection: 'row', alignItems: 'center', justifyContent: 'space-between', marginTop: 16 },
  paymentChip: { flexDirection: 'row', alignItems: 'center', gap: 6, paddingHorizontal: 10, paddingVertical: 6, borderRadius: 999 },
  paymentChipText: { fontSize: 12, fontWeight: '800', color: colors.goldLight },
  tripActions: { flexDirection: 'row', alignItems: 'center', gap: 8 },
  tripPayBtn: {
    backgroundColor: colors.gold,
    paddingHorizontal: 16,
    paddingVertical: 8,
    borderRadius: 12,
  },
  tripPayText: { fontSize: 13, fontWeight: '900', color: colors.baytgoDark },
  tripChatBtn: {
    width: 36,
    height: 36,
    borderRadius: 12,
    backgroundColor: colors.white,
    alignItems: 'center',
    justifyContent: 'center',
  },
  emptyTrip: { borderRadius: 22, overflow: 'hidden', marginBottom: 20 },
  emptyTripGradient: { padding: 24, alignItems: 'center' },
  emptyTripIcon: {
    width: 64,
    height: 64,
    borderRadius: 20,
    backgroundColor: 'rgba(255,255,255,0.1)',
    alignItems: 'center',
    justifyContent: 'center',
    marginBottom: 14,
  },
  emptyTripTitle: { fontSize: 18, fontWeight: '900', color: colors.white },
  emptyTripSub: { marginTop: 8, fontSize: 13, lineHeight: 20, color: 'rgba(255,255,255,0.7)', textAlign: 'center', fontWeight: '500' },
  emptyTripBtn: {
    flexDirection: 'row',
    alignItems: 'center',
    gap: 6,
    marginTop: 18,
    backgroundColor: colors.gold,
    paddingHorizontal: 20,
    paddingVertical: 12,
    borderRadius: 14,
  },
  emptyTripBtnText: { fontSize: 14, fontWeight: '900', color: colors.baytgoDark },
  serviceGrid: {
    flexDirection: 'row',
    justifyContent: 'space-between',
    marginBottom: 20,
  },
  serviceTile: { alignItems: 'center', width: (SCREEN_W - 40) / 4 - 4 },
  serviceIcon: {
    width: 58,
    height: 58,
    borderRadius: 18,
    alignItems: 'center',
    justifyContent: 'center',
    position: 'relative',
  },
  serviceBadge: {
    position: 'absolute',
    top: -4,
    right: -4,
    minWidth: 18,
    height: 18,
    borderRadius: 9,
    backgroundColor: '#EF4444',
    alignItems: 'center',
    justifyContent: 'center',
    paddingHorizontal: 4,
    borderWidth: 2,
    borderColor: colors.canvas,
  },
  serviceBadgeText: { fontSize: 9, fontWeight: '900', color: colors.white },
  serviceLabel: { marginTop: 8, fontSize: 11, fontWeight: '700', color: colors.slate700, textAlign: 'center', lineHeight: 14 },
  statsRow: { gap: 10, paddingBottom: 4, marginBottom: 16 },
  statPill: {
    flexDirection: 'row',
    alignItems: 'center',
    gap: 8,
    backgroundColor: colors.white,
    paddingHorizontal: 14,
    paddingVertical: 10,
    borderRadius: 14,
    borderWidth: 1,
    borderColor: colors.slate100,
  },
  statDot: { width: 8, height: 8, borderRadius: 4 },
  statPillValue: { fontSize: 15, fontWeight: '900', color: colors.slate900 },
  statPillLabel: { fontSize: 12, fontWeight: '600', color: colors.slate500 },
  promoBanner: { borderRadius: 16, overflow: 'hidden', marginBottom: 24 },
  promoGradient: { flexDirection: 'row', alignItems: 'center', gap: 14, padding: 16 },
  promoTexts: { flex: 1 },
  promoTitle: { fontSize: 14, fontWeight: '900', color: colors.baytgo },
  promoSub: { marginTop: 2, fontSize: 12, fontWeight: '600', color: colors.slate600, lineHeight: 16 },
  sectionHead: { flexDirection: 'row', alignItems: 'center', justifyContent: 'space-between', marginBottom: 14 },
  sectionTitle: { fontSize: 20, fontWeight: '900', color: colors.baytgo },
  seeAll: { fontSize: 13, fontWeight: '800', color: colors.goldMuted },
  muthowifRow: { paddingRight: 4, paddingBottom: 8 },
  muted: { fontSize: 14, color: colors.slate500, fontWeight: '600', marginBottom: 16 },
});
