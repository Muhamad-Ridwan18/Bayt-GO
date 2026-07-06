import React, { useCallback, useEffect, useMemo, useState } from 'react';
import {
  View,
  Text,
  StyleSheet,
  ScrollView,
  TouchableOpacity,
  ActivityIndicator,
  RefreshControl,
  Share,
  Image,
  Dimensions,
} from 'react-native';
import { SafeAreaView } from 'react-native-safe-area-context';
import { LinearGradient } from 'expo-linear-gradient';
import { Ionicons } from '@expo/vector-icons';
import { useAuth } from '../context/AuthContext';
import { fetchMuthowifDashboard } from '../api/dashboard';
import { colors } from '../theme/colors';
import { formatIdr } from '../utils/format';
import { bookingStatusMeta, formatDateRange } from '../utils/bookingLabels';

const { width: SCREEN_W } = Dimensions.get('window');

const PRIMARY_SERVICES = [
  { key: 'requests', icon: 'clipboard', label: 'Permintaan', bg: '#EDE9FE', color: '#7C3AED' },
  { key: 'wallet', icon: 'wallet', label: 'Dompet', bg: '#FEF3C7', color: '#D97706' },
  { key: 'chat', icon: 'chatbubbles', label: 'Chat', bg: '#DCFCE7', color: '#16A34A' },
  { key: 'emergency', icon: 'medkit', label: 'Darurat', bg: '#FEE2E2', color: '#DC2626' },
];

const SECONDARY_SERVICES = [
  { key: 'schedule', icon: 'calendar', label: 'Libur', bg: '#E0F2FE', color: '#0284C7' },
  { key: 'services', icon: 'pricetag', label: 'Layanan', bg: '#FCE7F3', color: '#DB2777' },
  { key: 'portfolio', icon: 'images', label: 'Portfolio', bg: '#F3E8FF', color: '#9333EA' },
  { key: 'profile', icon: 'person', label: 'Profil', bg: '#ECFDF5', color: '#059669' },
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

function ServiceTile({ item, badge, onPress }) {
  return (
    <TouchableOpacity style={styles.serviceTile} onPress={onPress} activeOpacity={0.88}>
      <View style={[styles.serviceIcon, { backgroundColor: item.bg }]}>
        <Ionicons name={item.icon} size={22} color={item.color} />
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

function StatPill({ label, value, color }) {
  return (
    <View style={styles.statPill}>
      <View style={[styles.statDot, { backgroundColor: color }]} />
      <Text style={styles.statPillValue}>{value}</Text>
      <Text style={styles.statPillLabel}>{label}</Text>
    </View>
  );
}

function ScheduleCard({ item, featured, onPress }) {
  const statusMeta = bookingStatusMeta(item.status);
  const countdown = featured ? daysUntil(item.starts_on) : null;

  return (
    <TouchableOpacity
      style={[styles.scheduleCard, featured && styles.scheduleCardFeatured]}
      onPress={onPress}
      activeOpacity={0.92}
    >
      {featured ? (
        <LinearGradient colors={['#1A3D34', '#256B5C']} style={styles.scheduleFeaturedGradient}>
          <View style={styles.scheduleFeaturedTop}>
            <Text style={styles.scheduleFeaturedKicker}>Jadwal terdekat</Text>
            {countdown ? <Text style={styles.scheduleCountdown}>{countdown}</Text> : null}
          </View>
          <View style={styles.scheduleFeaturedBody}>
            <Image source={{ uri: item.customer_avatar }} style={styles.scheduleAvatar} />
            <View style={styles.scheduleInfo}>
              <Text style={styles.scheduleCustomerFeatured} numberOfLines={1}>{item.customer_name}</Text>
              <Text style={styles.scheduleDatesFeatured}>
                {formatDateRange(item.starts_on, item.ends_on)}
              </Text>
              <Text style={styles.scheduleMetaFeatured}>
                {item.duration}
                {item.pilgrim_count ? ` · ${item.pilgrim_count} jamaah` : ''}
              </Text>
            </View>
            <View style={[styles.scheduleStatusBadge, { backgroundColor: statusMeta.color + '40' }]}>
              <Text style={styles.scheduleStatusFeatured}>{statusMeta.label}</Text>
            </View>
          </View>
          <Text style={styles.scheduleCodeFeatured}>#{item.booking_number}</Text>
        </LinearGradient>
      ) : (
        <View style={styles.scheduleCompact}>
          <Image source={{ uri: item.customer_avatar }} style={styles.scheduleAvatarSm} />
          <View style={styles.scheduleCompactInfo}>
            <Text style={styles.scheduleCustomer} numberOfLines={1}>{item.customer_name}</Text>
            <Text style={styles.scheduleDates}>{item.date} · {item.duration}</Text>
          </View>
          <View style={styles.scheduleCompactRight}>
            <View style={[styles.statusDot, { backgroundColor: statusMeta.color }]} />
            <Text style={styles.scheduleStatus}>{statusMeta.label}</Text>
          </View>
        </View>
      )}
    </TouchableOpacity>
  );
}

export default function MuthowifDashboardScreen({ navigation }) {
  const { user, token } = useAuth();
  const [loading, setLoading] = useState(true);
  const [refreshing, setRefreshing] = useState(false);
  const [error, setError] = useState(null);
  const [stats, setStats] = useState([]);
  const [schedules, setSchedules] = useState([]);
  const [unreadMessages, setUnreadMessages] = useState(0);
  const [emergencyCount, setEmergencyCount] = useState(0);
  const [pendingCount, setPendingCount] = useState(0);
  const [referralCode, setReferralCode] = useState(null);
  const [rating, setRating] = useState(null);
  const [reviewCount, setReviewCount] = useState(0);
  const [walletBalance, setWalletBalance] = useState(0);

  const firstName = useMemo(() => user?.name?.split(' ')[0] || 'Muthowif', [user?.name]);
  const [nextSchedule, ...moreSchedules] = schedules;

  const loadDashboard = useCallback(async (refresh = false) => {
    if (refresh) setRefreshing(true);
    else setLoading(true);

    try {
      const data = await fetchMuthowifDashboard(token);
      setStats(data.stats || []);
      setUnreadMessages(data.unread_messages || 0);
      setSchedules(data.recent_schedules || []);
      setEmergencyCount(data.emergency_offer_count || 0);
      setPendingCount(data.pending_booking_count || 0);
      setReferralCode(data.referral_code || null);
      setRating(data.rating || null);
      setReviewCount(data.review_count || 0);
      setWalletBalance(data.wallet_balance ?? 0);
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

  const goRequests = () => {
    navigation.getParent()?.navigate('MuthowifBookingsTab', { screen: 'MuthowifBookingsList' });
  };

  const goWallet = () => {
    navigation.getParent()?.navigate('WalletTab', { screen: 'WalletMain' });
  };

  const goChat = () => {
    navigation.getParent()?.navigate('ChatTab', { screen: 'ChatList' });
  };

  const openSchedule = (item) => {
    navigation.getParent()?.navigate('MuthowifBookingsTab', {
      screen: 'MuthowifBookingDetail',
      params: { bookingId: item.id },
    });
  };

  const shareReferral = async () => {
    if (!referralCode) return;
    try {
      await Share.share({ message: `Daftar sebagai muthowif di BaytGo dengan kode referral saya: ${referralCode}` });
    } catch {
      // ignore
    }
  };

  const handleService = (key) => {
    const routes = {
      requests: goRequests,
      wallet: goWallet,
      chat: goChat,
      emergency: () => navigation.navigate('EmergencyOffers'),
      schedule: () => navigation.navigate('Schedule'),
      services: () => navigation.navigate('Services'),
      portfolio: () => navigation.navigate('Portfolio'),
      profile: () => navigation.navigate('EditMuthowifProfile'),
    };
    routes[key]?.();
  };

  const serviceBadge = (key) => {
    if (key === 'requests' && pendingCount > 0) return pendingCount;
    if (key === 'chat' && unreadMessages > 0) return unreadMessages;
    if (key === 'emergency' && emergencyCount > 0) return emergencyCount;
    return null;
  };

  return (
    <View style={styles.root}>
      <ScrollView
        showsVerticalScrollIndicator={false}
        refreshControl={
          <RefreshControl refreshing={refreshing} onRefresh={() => loadDashboard(true)} tintColor={colors.white} />
        }
      >
        <LinearGradient colors={['#0F2E28', '#1A3D34', '#2D5A4E']} style={styles.hero}>
          <SafeAreaView edges={['top']}>
            <View style={styles.heroInner}>
              <View style={styles.heroTop}>
                <View style={styles.heroLeft}>
                  <Text style={styles.heroGreet}>Assalamu'alaikum,</Text>
                  <Text style={styles.heroName}>{firstName}</Text>
                  {rating ? (
                    <View style={styles.ratingChip}>
                      <Ionicons name="star" size={12} color={colors.gold} />
                      <Text style={styles.ratingText}>{rating}</Text>
                      <Text style={styles.ratingReviews}>({reviewCount} ulasan)</Text>
                    </View>
                  ) : null}
                </View>
                <TouchableOpacity style={styles.bellBtn} onPress={goRequests}>
                  <Ionicons name="notifications-outline" size={22} color={colors.white} />
                  {pendingCount > 0 ? (
                    <View style={styles.bellBadge}>
                      <Text style={styles.bellBadgeText}>{pendingCount > 9 ? '9+' : pendingCount}</Text>
                    </View>
                  ) : null}
                </TouchableOpacity>
              </View>
              <Text style={styles.heroTagline}>Kelola jadwal dan pendapatan Anda</Text>
            </View>
          </SafeAreaView>
        </LinearGradient>

        <TouchableOpacity style={styles.walletCard} onPress={goWallet} activeOpacity={0.92}>
          <LinearGradient colors={['#C5A059', '#E8C97A', '#B8954D']} start={{ x: 0, y: 0 }} end={{ x: 1, y: 1 }} style={styles.walletGradient}>
            <View style={styles.walletTop}>
              <View>
                <Text style={styles.walletLabel}>Saldo dompet</Text>
                <Text style={styles.walletAmount}>{formatIdr(walletBalance)}</Text>
              </View>
              <View style={styles.walletIconWrap}>
                <Ionicons name="wallet" size={28} color={colors.baytgoDark} />
              </View>
            </View>
            <View style={styles.walletFooter}>
              <Text style={styles.walletHint}>Tarik saldo kapan saja</Text>
              <View style={styles.walletCta}>
                <Text style={styles.walletCtaText}>Buka dompet</Text>
                <Ionicons name="arrow-forward" size={14} color={colors.baytgoDark} />
              </View>
            </View>
          </LinearGradient>
        </TouchableOpacity>

        {pendingCount > 0 ? (
          <TouchableOpacity style={styles.alertCard} onPress={goRequests} activeOpacity={0.9}>
            <View style={styles.alertIcon}>
              <Ionicons name="clipboard" size={20} color="#7C3AED" />
            </View>
            <View style={styles.alertTexts}>
              <Text style={styles.alertTitle}>{pendingCount} permintaan baru</Text>
              <Text style={styles.alertSub}>Jamaah menunggu konfirmasi Anda</Text>
            </View>
            <Ionicons name="chevron-forward" size={18} color={colors.slate400} />
          </TouchableOpacity>
        ) : null}

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
              <Text style={styles.gridTitle}>Menu cepat</Text>
              <View style={styles.serviceGrid}>
                {PRIMARY_SERVICES.map((item) => (
                  <ServiceTile
                    key={item.key}
                    item={item}
                    badge={serviceBadge(item.key)}
                    onPress={() => handleService(item.key)}
                  />
                ))}
              </View>
              <View style={[styles.serviceGrid, styles.serviceGridSecond]}>
                {SECONDARY_SERVICES.map((item) => (
                  <ServiceTile key={item.key} item={item} onPress={() => handleService(item.key)} />
                ))}
              </View>

              {stats.length > 0 ? (
                <ScrollView horizontal showsHorizontalScrollIndicator={false} contentContainerStyle={styles.statsRow}>
                  {stats.map((stat) => (
                    <StatPill key={stat.label} label={stat.label} value={stat.value} color={stat.color} />
                  ))}
                </ScrollView>
              ) : null}

              <View style={styles.sectionHead}>
                <Text style={styles.sectionTitle}>Jadwal mendatang</Text>
                <TouchableOpacity onPress={goRequests}>
                  <Text style={styles.seeAll}>Lihat semua</Text>
                </TouchableOpacity>
              </View>

              {!nextSchedule ? (
                <View style={styles.emptySchedule}>
                  <Ionicons name="calendar-outline" size={36} color={colors.slate400} />
                  <Text style={styles.emptyScheduleTitle}>Belum ada jadwal aktif</Text>
                  <Text style={styles.emptyScheduleSub}>Permintaan yang disetujui akan muncul di sini</Text>
                  <TouchableOpacity style={styles.emptyScheduleBtn} onPress={() => navigation.navigate('Schedule')}>
                    <Text style={styles.emptyScheduleBtnText}>Atur jadwal libur</Text>
                  </TouchableOpacity>
                </View>
              ) : (
                <>
                  <ScheduleCard item={nextSchedule} featured onPress={() => openSchedule(nextSchedule)} />
                  {moreSchedules.map((item) => (
                    <ScheduleCard key={item.id} item={item} onPress={() => openSchedule(item)} />
                  ))}
                </>
              )}

              {referralCode ? (
                <TouchableOpacity style={styles.referralCard} onPress={shareReferral} activeOpacity={0.9}>
                  <LinearGradient
                    colors={[colors.baytgoLight, colors.white]}
                    style={styles.referralGradient}
                  >
                    <View style={styles.referralLeft}>
                      <Ionicons name="gift" size={24} color={colors.baytgo} />
                      <View style={styles.referralTexts}>
                        <Text style={styles.referralTitle}>Kode referral Anda</Text>
                        <Text style={styles.referralCode}>{referralCode}</Text>
                      </View>
                    </View>
                    <View style={styles.referralShare}>
                      <Ionicons name="share-outline" size={18} color={colors.baytgo} />
                    </View>
                  </LinearGradient>
                </TouchableOpacity>
              ) : null}
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
    paddingBottom: 72,
    borderBottomLeftRadius: 28,
    borderBottomRightRadius: 28,
  },
  heroInner: { paddingHorizontal: 20, paddingTop: 8 },
  heroTop: { flexDirection: 'row', justifyContent: 'space-between', alignItems: 'flex-start' },
  heroLeft: { flex: 1 },
  heroGreet: { fontSize: 14, fontWeight: '600', color: 'rgba(255,255,255,0.75)' },
  heroName: { fontSize: 26, fontWeight: '900', color: colors.white, marginTop: 2, letterSpacing: -0.5 },
  ratingChip: {
    flexDirection: 'row',
    alignItems: 'center',
    gap: 4,
    alignSelf: 'flex-start',
    marginTop: 8,
    backgroundColor: 'rgba(255,255,255,0.12)',
    paddingHorizontal: 10,
    paddingVertical: 5,
    borderRadius: 999,
  },
  ratingText: { fontSize: 13, fontWeight: '900', color: colors.gold },
  ratingReviews: { fontSize: 11, fontWeight: '600', color: 'rgba(255,255,255,0.65)' },
  heroTagline: { marginTop: 12, fontSize: 13, fontWeight: '500', color: 'rgba(255,255,255,0.65)' },
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
  bellBadge: {
    position: 'absolute',
    top: -2,
    right: -2,
    minWidth: 18,
    height: 18,
    borderRadius: 9,
    backgroundColor: '#EF4444',
    alignItems: 'center',
    justifyContent: 'center',
    paddingHorizontal: 4,
    borderWidth: 2,
    borderColor: '#1A3D34',
  },
  bellBadgeText: { fontSize: 10, fontWeight: '900', color: colors.white },
  walletCard: {
    marginHorizontal: 20,
    marginTop: -52,
    borderRadius: 22,
    overflow: 'hidden',
    shadowColor: '#B8954D',
    shadowOffset: { width: 0, height: 8 },
    shadowOpacity: 0.25,
    shadowRadius: 16,
    elevation: 8,
  },
  walletGradient: { padding: 20 },
  walletTop: { flexDirection: 'row', justifyContent: 'space-between', alignItems: 'flex-start' },
  walletLabel: { fontSize: 12, fontWeight: '700', color: colors.baytgoDark, opacity: 0.75 },
  walletAmount: { marginTop: 4, fontSize: 28, fontWeight: '900', color: colors.baytgoDark, letterSpacing: -0.5 },
  walletIconWrap: {
    width: 52,
    height: 52,
    borderRadius: 16,
    backgroundColor: 'rgba(255,255,255,0.45)',
    alignItems: 'center',
    justifyContent: 'center',
  },
  walletFooter: { flexDirection: 'row', justifyContent: 'space-between', alignItems: 'center', marginTop: 16 },
  walletHint: { fontSize: 12, fontWeight: '600', color: colors.baytgoDark, opacity: 0.7 },
  walletCta: { flexDirection: 'row', alignItems: 'center', gap: 4, backgroundColor: 'rgba(255,255,255,0.5)', paddingHorizontal: 12, paddingVertical: 6, borderRadius: 10 },
  walletCtaText: { fontSize: 12, fontWeight: '900', color: colors.baytgoDark },
  alertCard: {
    flexDirection: 'row',
    alignItems: 'center',
    gap: 12,
    marginHorizontal: 20,
    marginTop: 14,
    backgroundColor: colors.white,
    borderRadius: 16,
    padding: 14,
    borderWidth: 1,
    borderColor: '#EDE9FE',
    shadowColor: '#7C3AED',
    shadowOffset: { width: 0, height: 4 },
    shadowOpacity: 0.08,
    shadowRadius: 10,
    elevation: 3,
  },
  alertIcon: {
    width: 44,
    height: 44,
    borderRadius: 14,
    backgroundColor: '#EDE9FE',
    alignItems: 'center',
    justifyContent: 'center',
  },
  alertTexts: { flex: 1 },
  alertTitle: { fontSize: 15, fontWeight: '900', color: colors.slate900 },
  alertSub: { marginTop: 2, fontSize: 12, fontWeight: '600', color: colors.slate500 },
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
  gridTitle: { fontSize: 16, fontWeight: '900', color: colors.baytgo, marginBottom: 12 },
  serviceGrid: { flexDirection: 'row', justifyContent: 'space-between' },
  serviceGridSecond: { marginTop: 12, marginBottom: 16 },
  serviceTile: { alignItems: 'center', width: (SCREEN_W - 40) / 4 - 4 },
  serviceIcon: {
    width: 54,
    height: 54,
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
  serviceLabel: { marginTop: 8, fontSize: 11, fontWeight: '700', color: colors.slate700, textAlign: 'center' },
  statsRow: { gap: 10, paddingBottom: 4, marginBottom: 20 },
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
  statPillValue: { fontSize: 14, fontWeight: '900', color: colors.slate900 },
  statPillLabel: { fontSize: 11, fontWeight: '600', color: colors.slate500 },
  sectionHead: { flexDirection: 'row', alignItems: 'center', justifyContent: 'space-between', marginBottom: 14 },
  sectionTitle: { fontSize: 20, fontWeight: '900', color: colors.baytgo },
  seeAll: { fontSize: 13, fontWeight: '800', color: colors.goldMuted },
  scheduleCard: { marginBottom: 10, borderRadius: 18, overflow: 'hidden' },
  scheduleCardFeatured: { marginBottom: 12 },
  scheduleFeaturedGradient: { padding: 18 },
  scheduleFeaturedTop: { flexDirection: 'row', justifyContent: 'space-between', alignItems: 'center' },
  scheduleFeaturedKicker: { fontSize: 11, fontWeight: '800', color: 'rgba(255,255,255,0.65)', textTransform: 'uppercase', letterSpacing: 0.8 },
  scheduleCountdown: { fontSize: 14, fontWeight: '900', color: colors.gold },
  scheduleFeaturedBody: { flexDirection: 'row', alignItems: 'center', gap: 12, marginTop: 14 },
  scheduleAvatar: { width: 52, height: 52, borderRadius: 16, borderWidth: 2, borderColor: 'rgba(255,255,255,0.25)' },
  scheduleInfo: { flex: 1 },
  scheduleCustomerFeatured: { fontSize: 17, fontWeight: '900', color: colors.white },
  scheduleDatesFeatured: { marginTop: 4, fontSize: 13, fontWeight: '600', color: 'rgba(255,255,255,0.85)' },
  scheduleMetaFeatured: { marginTop: 2, fontSize: 11, fontWeight: '600', color: 'rgba(255,255,255,0.55)' },
  scheduleStatusBadge: { paddingHorizontal: 10, paddingVertical: 5, borderRadius: 999 },
  scheduleStatusFeatured: { fontSize: 11, fontWeight: '800', color: colors.white },
  scheduleCodeFeatured: { marginTop: 12, fontSize: 11, fontWeight: '700', color: 'rgba(255,255,255,0.45)' },
  scheduleCompact: {
    flexDirection: 'row',
    alignItems: 'center',
    gap: 12,
    backgroundColor: colors.white,
    padding: 14,
    borderRadius: 16,
    borderWidth: 1,
    borderColor: colors.slate100,
  },
  scheduleAvatarSm: { width: 44, height: 44, borderRadius: 14, backgroundColor: colors.slate100 },
  scheduleCompactInfo: { flex: 1 },
  scheduleCustomer: { fontSize: 14, fontWeight: '800', color: colors.slate900 },
  scheduleDates: { marginTop: 3, fontSize: 12, fontWeight: '600', color: colors.slate500 },
  scheduleCompactRight: { alignItems: 'flex-end', gap: 4 },
  statusDot: { width: 8, height: 8, borderRadius: 4 },
  scheduleStatus: { fontSize: 10, fontWeight: '800', color: colors.slate500 },
  emptySchedule: {
    alignItems: 'center',
    backgroundColor: colors.white,
    borderRadius: 20,
    padding: 28,
    borderWidth: 1,
    borderStyle: 'dashed',
    borderColor: colors.slate200,
    marginBottom: 16,
  },
  emptyScheduleTitle: { marginTop: 12, fontSize: 16, fontWeight: '900', color: colors.slate700 },
  emptyScheduleSub: { marginTop: 6, fontSize: 13, fontWeight: '600', color: colors.slate500, textAlign: 'center' },
  emptyScheduleBtn: {
    marginTop: 16,
    backgroundColor: colors.emerald50,
    paddingHorizontal: 16,
    paddingVertical: 10,
    borderRadius: 12,
  },
  emptyScheduleBtnText: { fontSize: 13, fontWeight: '800', color: colors.baytgo },
  referralCard: { borderRadius: 18, overflow: 'hidden', marginTop: 8, borderWidth: 1, borderColor: colors.slate100 },
  referralGradient: { flexDirection: 'row', alignItems: 'center', justifyContent: 'space-between', padding: 16 },
  referralLeft: { flexDirection: 'row', alignItems: 'center', gap: 12, flex: 1 },
  referralTexts: { flex: 1 },
  referralTitle: { fontSize: 12, fontWeight: '700', color: colors.slate600 },
  referralCode: { marginTop: 4, fontSize: 20, fontWeight: '900', color: colors.baytgo, letterSpacing: 2 },
  referralShare: {
    width: 40,
    height: 40,
    borderRadius: 12,
    backgroundColor: colors.white,
    alignItems: 'center',
    justifyContent: 'center',
    borderWidth: 1,
    borderColor: colors.slate100,
  },
});
