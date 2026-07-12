import React, { useCallback, useEffect, useMemo, useState } from 'react';
import {
  View,
  Text,
  StyleSheet,
  ScrollView,
  RefreshControl,
  Share,
  Dimensions,
} from 'react-native';
import { SafeAreaView } from 'react-native-safe-area-context';
import { LinearGradient } from 'expo-linear-gradient';
import {
  ArrowRight,
  Bell,
  Calendar,
  ChevronRight,
  ClipboardList,
  Gift,
  Images,
  MessageCircle,
  Share2,
  Star,
  Tag,
  User,
  Wallet,
  HeartPulse,
} from 'lucide-react-native';
import { useAuth } from '../context/AuthContext';
import { fetchMuthowifDashboard } from '../api/dashboard';
import {
  AppImage,
  Card,
  EmptyState,
  ErrorState,
  PressableScale,
  SkeletonList,
} from '../ui';
import { bookingStatusMeta, formatDateRange } from '../utils/bookingLabels';
import { formatIdr } from '../utils/format';
import { colors, gradients, layout, radius, shadows, spacing, typography } from '../theme/tokens';

const { width: SCREEN_W } = Dimensions.get('window');

const PRIMARY_SERVICES = [
  { key: 'requests', Icon: ClipboardList, label: 'Permintaan', bg: '#EDE9FE', color: '#7C3AED' },
  { key: 'wallet', Icon: Wallet, label: 'Dompet', bg: colors.warningLight, color: colors.warning },
  { key: 'chat', Icon: MessageCircle, label: 'Chat', bg: colors.successLight, color: colors.success },
  { key: 'emergency', Icon: HeartPulse, label: 'Darurat', bg: colors.errorLight, color: colors.error },
];

const SECONDARY_SERVICES = [
  { key: 'schedule', Icon: Calendar, label: 'Libur', bg: '#E0F2FE', color: '#0284C7' },
  { key: 'services', Icon: Tag, label: 'Layanan', bg: '#FCE7F3', color: '#DB2777' },
  { key: 'portfolio', Icon: Images, label: 'Portfolio', bg: '#F3E8FF', color: '#9333EA' },
  { key: 'profile', Icon: User, label: 'Profil', bg: colors.primaryLight, color: colors.primary },
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
  const Icon = item.Icon;
  return (
    <PressableScale onPress={onPress} haptic="light" scaleTo={0.94} style={styles.serviceTile}>
      <View style={[styles.serviceIcon, { backgroundColor: item.bg }]}>
        <Icon size={22} color={item.color} strokeWidth={2} />
        {badge ? (
          <View style={styles.serviceBadge}>
            <Text style={styles.serviceBadgeText}>{badge > 99 ? '99+' : badge}</Text>
          </View>
        ) : null}
      </View>
      <Text style={styles.serviceLabel}>{item.label}</Text>
    </PressableScale>
  );
}

function StatPill({ label, value, color }) {
  return (
    <Card style={styles.statPill} padding={spacing.md + 2} elevated={false}>
      <View style={[styles.statDot, { backgroundColor: color }]} />
      <Text style={styles.statPillValue}>{value}</Text>
      <Text style={styles.statPillLabel}>{label}</Text>
    </Card>
  );
}

function ScheduleCard({ item, featured, onPress }) {
  const statusMeta = bookingStatusMeta(item.status);
  const countdown = featured ? daysUntil(item.starts_on) : null;

  if (featured) {
    return (
      <PressableScale onPress={onPress} haptic="light" style={styles.scheduleCard}>
        <LinearGradient colors={gradients.primary} style={styles.scheduleFeatured}>
          <View style={styles.scheduleFeaturedTop}>
            <Text style={styles.scheduleFeaturedKicker}>Jadwal terdekat</Text>
            {countdown ? <Text style={styles.scheduleCountdown}>{countdown}</Text> : null}
          </View>
          <View style={styles.scheduleFeaturedBody}>
            <AppImage uri={item.customer_avatar} size={52} rounded={radius.sm} />
            <View style={styles.scheduleInfo}>
              <Text style={styles.scheduleCustomerFeatured} numberOfLines={1}>{item.customer_name}</Text>
              <Text style={styles.scheduleDatesFeatured}>{formatDateRange(item.starts_on, item.ends_on)}</Text>
              <Text style={styles.scheduleMetaFeatured}>
                {item.duration}
                {item.pilgrim_count ? ` · ${item.pilgrim_count} jamaah` : ''}
              </Text>
            </View>
            <View style={[styles.scheduleStatusBadge, { backgroundColor: `${statusMeta.color}40` }]}>
              <Text style={styles.scheduleStatusFeatured}>{statusMeta.label}</Text>
            </View>
          </View>
          <Text style={styles.scheduleCodeFeatured}>#{item.booking_number}</Text>
        </LinearGradient>
      </PressableScale>
    );
  }

  return (
    <PressableScale onPress={onPress} haptic="light" style={styles.scheduleCard}>
      <Card style={styles.scheduleCompact} padding={spacing.lg - 2} elevated={false}>
        <AppImage uri={item.customer_avatar} size={44} rounded={radius.sm} />
        <View style={styles.scheduleCompactInfo}>
          <Text style={styles.scheduleCustomer} numberOfLines={1}>{item.customer_name}</Text>
          <Text style={styles.scheduleDates}>{item.date} · {item.duration}</Text>
        </View>
        <View style={styles.scheduleCompactRight}>
          <View style={[styles.statusDot, { backgroundColor: statusMeta.color }]} />
          <Text style={styles.scheduleStatus}>{statusMeta.label}</Text>
        </View>
      </Card>
    </PressableScale>
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

  useEffect(() => { loadDashboard(); }, [loadDashboard]);

  const goRequests = () => navigation.getParent()?.navigate('MuthowifBookingsTab', { screen: 'MuthowifBookingsList' });
  const goWallet = () => navigation.getParent()?.navigate('WalletTab', { screen: 'WalletMain' });
  const goChat = () => navigation.getParent()?.navigate('ChatTab', { screen: 'ChatList' });
  const openSchedule = (item) => navigation.getParent()?.navigate('MuthowifBookingsTab', {
    screen: 'MuthowifBookingDetail', params: { bookingId: item.id },
  });

  const shareReferral = async () => {
    if (!referralCode) return;
    try {
      await Share.share({ message: `Daftar sebagai muthowif di BaytGo dengan kode referral saya: ${referralCode}` });
    } catch { /* ignore */ }
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
      <SafeAreaView edges={['top']} style={styles.topBar}>
        <View style={styles.heroTop}>
          <View style={styles.heroLeft}>
            <Text style={styles.heroGreet}>Assalamu'alaikum,</Text>
            <Text style={styles.heroName}>{firstName}</Text>
            {rating ? (
              <View style={styles.ratingChip}>
                <Star size={12} color={colors.gold} fill={colors.gold} strokeWidth={2} />
                <Text style={styles.ratingText}>{rating}</Text>
                <Text style={styles.ratingReviews}>({reviewCount} ulasan)</Text>
              </View>
            ) : null}
          </View>
          <PressableScale onPress={goRequests} haptic="light" style={styles.bellBtn}>
            <Bell size={22} color={colors.baytgo} strokeWidth={2} />
            {pendingCount > 0 ? (
              <View style={styles.bellBadge}>
                <Text style={styles.bellBadgeText}>{pendingCount > 9 ? '9+' : pendingCount}</Text>
              </View>
            ) : null}
          </PressableScale>
        </View>
        <Text style={styles.heroTagline}>Kelola jadwal dan pendapatan Anda</Text>
      </SafeAreaView>

      <ScrollView
        showsVerticalScrollIndicator={false}
        contentContainerStyle={{ paddingBottom: spacing.lg }}
        refreshControl={<RefreshControl refreshing={refreshing} onRefresh={() => loadDashboard(true)} tintColor={colors.baytgo} />}
      >
        <PressableScale onPress={goWallet} haptic="light" style={styles.walletCard}>
          <LinearGradient colors={gradients.gold} start={{ x: 0, y: 0 }} end={{ x: 1, y: 1 }} style={styles.walletGradient}>
            <View style={styles.walletTop}>
              <View>
                <Text style={styles.walletLabel}>Saldo dompet</Text>
                <Text style={styles.walletAmount}>{formatIdr(walletBalance)}</Text>
              </View>
              <View style={styles.walletIconWrap}>
                <Wallet size={28} color={colors.baytgoDark} strokeWidth={1.8} />
              </View>
            </View>
            <View style={styles.walletFooter}>
              <Text style={styles.walletHint}>Tarik saldo kapan saja</Text>
              <View style={styles.walletCta}>
                <Text style={styles.walletCtaText}>Buka dompet</Text>
                <ArrowRight size={14} color={colors.baytgoDark} strokeWidth={2.5} />
              </View>
            </View>
          </LinearGradient>
        </PressableScale>

        {pendingCount > 0 ? (
          <PressableScale onPress={goRequests} haptic="light" style={styles.alertCard}>
            <View style={styles.alertIcon}>
              <ClipboardList size={20} color="#7C3AED" strokeWidth={2} />
            </View>
            <View style={styles.alertTexts}>
              <Text style={styles.alertTitle}>{pendingCount} permintaan baru</Text>
              <Text style={styles.alertSub}>Jamaah menunggu konfirmasi Anda</Text>
            </View>
            <ChevronRight size={18} color={colors.textMuted} strokeWidth={2} />
          </PressableScale>
        ) : null}

        <View style={styles.body}>
          {loading && !refreshing ? <SkeletonList count={2} /> : null}
          {error ? <ErrorState description={error} onRetry={() => loadDashboard()} /> : null}

          {!loading ? (
            <>
              <Text style={styles.gridTitle}>Menu cepat</Text>
              <View style={styles.serviceGrid}>
                {PRIMARY_SERVICES.map((item) => (
                  <ServiceTile key={item.key} item={item} badge={serviceBadge(item.key)} onPress={() => handleService(item.key)} />
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
                <PressableScale onPress={goRequests} haptic="light">
                  <Text style={styles.seeAll}>Lihat semua</Text>
                </PressableScale>
              </View>

              {!nextSchedule ? (
                <EmptyState
                  variant="schedule"
                  title="Belum ada jadwal aktif"
                  description="Permintaan yang disetujui akan muncul di sini"
                  actionLabel="Atur jadwal libur"
                  onAction={() => navigation.navigate('Schedule')}
                />
              ) : (
                <>
                  <ScheduleCard item={nextSchedule} featured onPress={() => openSchedule(nextSchedule)} />
                  {moreSchedules.map((item) => (
                    <ScheduleCard key={item.id} item={item} onPress={() => openSchedule(item)} />
                  ))}
                </>
              )}

              {referralCode ? (
                <PressableScale onPress={shareReferral} haptic="light" style={styles.referralCard}>
                  <LinearGradient colors={[colors.baytgoLight, colors.white]} style={styles.referralGradient}>
                    <View style={styles.referralLeft}>
                      <Gift size={24} color={colors.baytgo} strokeWidth={2} />
                      <View style={styles.referralTexts}>
                        <Text style={styles.referralTitle}>Kode referral Anda</Text>
                        <Text style={styles.referralCode}>{referralCode}</Text>
                      </View>
                    </View>
                    <View style={styles.referralShare}>
                      <Share2 size={18} color={colors.baytgo} strokeWidth={2} />
                    </View>
                  </LinearGradient>
                </PressableScale>
              ) : null}
            </>
          ) : null}
        </View>
      </ScrollView>
    </View>
  );
}

const styles = StyleSheet.create({
  root: { flex: 1, backgroundColor: colors.background },
  topBar: {
    backgroundColor: colors.card,
    borderBottomWidth: 1,
    borderBottomColor: colors.border,
    paddingHorizontal: layout.screenPadding,
    paddingTop: spacing.sm,
    paddingBottom: spacing.md + 2,
  },
  heroTop: { flexDirection: 'row', justifyContent: 'space-between', alignItems: 'flex-start' },
  heroLeft: { flex: 1 },
  heroGreet: { ...typography.caption, color: colors.textSecondary },
  heroName: {
    ...typography.title,
    color: colors.baytgo,
    marginTop: 2,
    letterSpacing: -0.5,
    fontFamily: 'PlusJakartaSans_800ExtraBold',
    fontWeight: '800',
  },
  ratingChip: {
    flexDirection: 'row',
    alignItems: 'center',
    gap: spacing.xs,
    alignSelf: 'flex-start',
    marginTop: spacing.sm,
    backgroundColor: colors.baytgoLight,
    paddingHorizontal: spacing.md,
    paddingVertical: spacing.xs + 1,
    borderRadius: radius.full,
  },
  ratingText: { ...typography.caption, fontWeight: '900', color: colors.gold },
  ratingReviews: { ...typography.small, color: colors.textSecondary },
  heroTagline: { marginTop: spacing.md - 2, ...typography.caption, color: colors.textSecondary },
  bellBtn: {
    width: 44,
    height: 44,
    borderRadius: radius.sm,
    backgroundColor: colors.background,
    alignItems: 'center',
    justifyContent: 'center',
    borderWidth: 1,
    borderColor: colors.border,
  },
  bellBadge: {
    position: 'absolute',
    top: -2,
    right: -2,
    minWidth: 18,
    height: 18,
    borderRadius: 9,
    backgroundColor: colors.error,
    alignItems: 'center',
    justifyContent: 'center',
    paddingHorizontal: spacing.xs,
    borderWidth: 2,
    borderColor: colors.white,
  },
  bellBadgeText: { fontSize: 10, fontWeight: '900', color: colors.white },
  walletCard: {
    marginHorizontal: layout.screenPadding,
    marginTop: spacing.lg,
    borderRadius: radius.md,
    overflow: 'hidden',
    ...shadows.lg,
    shadowColor: colors.goldMuted,
  },
  walletGradient: { padding: spacing.xl },
  walletTop: { flexDirection: 'row', justifyContent: 'space-between', alignItems: 'flex-start' },
  walletLabel: { ...typography.small, color: colors.baytgoDark, opacity: 0.75 },
  walletAmount: {
    marginTop: spacing.xs,
    fontSize: 28,
    fontWeight: '900',
    color: colors.baytgoDark,
    letterSpacing: -0.5,
    fontFamily: 'PlusJakartaSans_800ExtraBold',
  },
  walletIconWrap: {
    width: 52,
    height: 52,
    borderRadius: radius.sm,
    backgroundColor: 'rgba(255,255,255,0.45)',
    alignItems: 'center',
    justifyContent: 'center',
  },
  walletFooter: { flexDirection: 'row', justifyContent: 'space-between', alignItems: 'center', marginTop: spacing.lg },
  walletHint: { ...typography.small, color: colors.baytgoDark, opacity: 0.7 },
  walletCta: {
    flexDirection: 'row',
    alignItems: 'center',
    gap: spacing.xs,
    backgroundColor: 'rgba(255,255,255,0.5)',
    paddingHorizontal: spacing.md,
    paddingVertical: spacing.sm - 2,
    borderRadius: radius.sm - 2,
  },
  walletCtaText: { ...typography.small, color: colors.baytgoDark, fontFamily: 'PlusJakartaSans_800ExtraBold', fontWeight: '800' },
  alertCard: {
    flexDirection: 'row',
    alignItems: 'center',
    gap: spacing.md,
    marginHorizontal: layout.screenPadding,
    marginTop: spacing.md + 2,
    backgroundColor: colors.card,
    borderRadius: radius.sm,
    padding: spacing.lg - 2,
    borderWidth: 1,
    borderColor: '#EDE9FE',
    ...shadows.sm,
    shadowColor: '#7C3AED',
  },
  alertIcon: {
    width: 44,
    height: 44,
    borderRadius: radius.sm,
    backgroundColor: '#EDE9FE',
    alignItems: 'center',
    justifyContent: 'center',
  },
  alertTexts: { flex: 1 },
  alertTitle: { ...typography.caption, fontWeight: '900', color: colors.textPrimary, fontFamily: 'PlusJakartaSans_800ExtraBold' },
  alertSub: { marginTop: 2, ...typography.small, color: colors.textSecondary },
  body: { paddingHorizontal: layout.screenPadding, paddingTop: spacing.xl, paddingBottom: spacing.xl },
  gridTitle: { ...typography.subtitle, fontSize: 16, color: colors.baytgo, fontFamily: 'PlusJakartaSans_800ExtraBold', fontWeight: '800', marginBottom: spacing.md },
  serviceGrid: { flexDirection: 'row', justifyContent: 'space-between' },
  serviceGridSecond: { marginTop: spacing.md, marginBottom: spacing.lg },
  serviceTile: { alignItems: 'center', width: (SCREEN_W - 40) / 4 - 4 },
  serviceIcon: {
    width: 54,
    height: 54,
    borderRadius: radius.md - 2,
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
    backgroundColor: colors.error,
    alignItems: 'center',
    justifyContent: 'center',
    paddingHorizontal: spacing.xs,
    borderWidth: 2,
    borderColor: colors.background,
  },
  serviceBadgeText: { fontSize: 9, fontWeight: '900', color: colors.white },
  serviceLabel: { marginTop: spacing.sm, ...typography.small, color: colors.slate700, textAlign: 'center' },
  statsRow: { gap: spacing.md - 2, paddingBottom: spacing.xs, marginBottom: spacing.xl },
  statPill: { flexDirection: 'row', alignItems: 'center', gap: spacing.sm },
  statDot: { width: 8, height: 8, borderRadius: 4 },
  statPillValue: { ...typography.caption, fontWeight: '900', color: colors.textPrimary, fontFamily: 'PlusJakartaSans_800ExtraBold' },
  statPillLabel: { ...typography.small, color: colors.textSecondary },
  sectionHead: { flexDirection: 'row', alignItems: 'center', justifyContent: 'space-between', marginBottom: spacing.md + 2 },
  sectionTitle: { ...typography.title, color: colors.baytgo, fontFamily: 'PlusJakartaSans_800ExtraBold', fontWeight: '800' },
  seeAll: { ...typography.caption, fontWeight: '800', color: colors.goldMuted },
  scheduleCard: { marginBottom: spacing.md - 2, borderRadius: radius.md - 2, overflow: 'hidden' },
  scheduleFeatured: { padding: spacing.lg + 2 },
  scheduleFeaturedTop: { flexDirection: 'row', justifyContent: 'space-between', alignItems: 'center' },
  scheduleFeaturedKicker: { ...typography.label, color: 'rgba(255,255,255,0.65)', textTransform: 'uppercase', letterSpacing: 0.8 },
  scheduleCountdown: { ...typography.caption, fontWeight: '900', color: colors.gold, fontFamily: 'PlusJakartaSans_800ExtraBold' },
  scheduleFeaturedBody: { flexDirection: 'row', alignItems: 'center', gap: spacing.md, marginTop: spacing.md + 2 },
  scheduleInfo: { flex: 1 },
  scheduleCustomerFeatured: { ...typography.subtitle, fontSize: 17, color: colors.white, fontFamily: 'PlusJakartaSans_800ExtraBold', fontWeight: '800' },
  scheduleDatesFeatured: { marginTop: spacing.xs, ...typography.caption, color: 'rgba(255,255,255,0.85)' },
  scheduleMetaFeatured: { marginTop: 2, ...typography.small, color: 'rgba(255,255,255,0.55)' },
  scheduleStatusBadge: { paddingHorizontal: spacing.md - 2, paddingVertical: spacing.xs + 1, borderRadius: radius.full },
  scheduleStatusFeatured: { ...typography.small, fontWeight: '800', color: colors.white },
  scheduleCodeFeatured: { marginTop: spacing.md, ...typography.small, color: 'rgba(255,255,255,0.45)' },
  scheduleCompact: { flexDirection: 'row', alignItems: 'center', gap: spacing.md },
  scheduleCompactInfo: { flex: 1 },
  scheduleCustomer: { ...typography.caption, fontWeight: '800', color: colors.textPrimary, fontFamily: 'PlusJakartaSans_800ExtraBold' },
  scheduleDates: { marginTop: 3, ...typography.small, color: colors.textSecondary },
  scheduleCompactRight: { alignItems: 'flex-end', gap: spacing.xs },
  statusDot: { width: 8, height: 8, borderRadius: 4 },
  scheduleStatus: { ...typography.label, fontSize: 10, color: colors.textSecondary },
  referralCard: { borderRadius: radius.md - 2, overflow: 'hidden', marginTop: spacing.sm, borderWidth: 1, borderColor: colors.border },
  referralGradient: { flexDirection: 'row', alignItems: 'center', justifyContent: 'space-between', padding: spacing.lg },
  referralLeft: { flexDirection: 'row', alignItems: 'center', gap: spacing.md, flex: 1 },
  referralTexts: { flex: 1 },
  referralTitle: { ...typography.small, color: colors.textSecondary },
  referralCode: { marginTop: spacing.xs, fontSize: 20, fontWeight: '900', color: colors.baytgo, letterSpacing: 2, fontFamily: 'PlusJakartaSans_800ExtraBold' },
  referralShare: {
    width: 40,
    height: 40,
    borderRadius: radius.sm,
    backgroundColor: colors.card,
    alignItems: 'center',
    justifyContent: 'center',
    borderWidth: 1,
    borderColor: colors.border,
  },
});
