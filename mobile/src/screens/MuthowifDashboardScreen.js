import React, { useCallback, useEffect, useState } from 'react';
import {
  View,
  Text,
  StyleSheet,
  ScrollView,
  TouchableOpacity,
  ActivityIndicator,
  RefreshControl,
  Share,
} from 'react-native';
import { LinearGradient } from 'expo-linear-gradient';
import { Ionicons } from '@expo/vector-icons';
import { useAuth } from '../context/AuthContext';
import { fetchMuthowifDashboard } from '../api/dashboard';
import TabPageHeader from '../components/TabPageHeader';
import { colors } from '../theme/colors';

function StatCard({ stat }) {
  return (
    <View style={[styles.statCard, { borderLeftColor: stat.color || colors.baytgo }]}>
      <Text style={styles.statValue}>{stat.value}</Text>
      <Text style={styles.statLabel}>{stat.label}</Text>
    </View>
  );
}

function ScheduleRow({ item, onPress }) {
  return (
    <TouchableOpacity style={styles.scheduleRow} onPress={onPress} activeOpacity={0.9}>
      <View style={styles.scheduleMeta}>
        <Text style={styles.scheduleCode}>{item.booking_number}</Text>
        <Text style={styles.scheduleCustomer}>{item.customer_name}</Text>
      </View>
      <View style={styles.scheduleRight}>
        <Text style={styles.scheduleDate}>{item.date}</Text>
        <Text style={styles.scheduleStatus}>{item.status}</Text>
      </View>
    </TouchableOpacity>
  );
}

function QuickLink({ icon, label, onPress, badge }) {
  return (
    <TouchableOpacity style={styles.quickLink} onPress={onPress} activeOpacity={0.9}>
      <View style={styles.quickIcon}>
        <Ionicons name={icon} size={20} color={colors.baytgo} />
        {badge ? (
          <View style={styles.quickBadge}>
            <Text style={styles.quickBadgeText}>{badge > 99 ? '99+' : badge}</Text>
          </View>
        ) : null}
      </View>
      <Text style={styles.quickLabel}>{label}</Text>
      <Ionicons name="chevron-forward" size={16} color={colors.slate400} />
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
  const [referralCode, setReferralCode] = useState(null);
  const [rating, setRating] = useState(null);

  const loadDashboard = useCallback(async (refresh = false) => {
    if (refresh) setRefreshing(true);
    else setLoading(true);

    try {
      const data = await fetchMuthowifDashboard(token);
      setStats(data.stats || []);
      setUnreadMessages(data.unread_messages || 0);
      setSchedules(data.recent_schedules || []);
      setEmergencyCount(data.emergency_offer_count || 0);
      setReferralCode(data.referral_code || null);
      setRating(data.rating || null);
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
      await Share.share({ message: `Daftar di BaytGo dengan kode referral saya: ${referralCode}` });
    } catch {
      // ignore
    }
  };

  return (
    <View style={styles.safe}>
      <LinearGradient colors={[colors.canvas, colors.white]} style={StyleSheet.absoluteFill} />
      <TabPageHeader
        title="Beranda"
        subtitle={rating ? `Halo, ${user?.name || 'Muthowif'} · ⭐ ${rating}` : `Halo, ${user?.name || 'Muthowif'}`}
      />

      {loading && !refreshing ? (
        <ActivityIndicator color={colors.baytgo} style={styles.loader} />
      ) : (
        <ScrollView
          contentContainerStyle={styles.scroll}
          refreshControl={
            <RefreshControl refreshing={refreshing} onRefresh={() => loadDashboard(true)} tintColor={colors.baytgo} />
          }
          showsVerticalScrollIndicator={false}
        >
          {error ? (
            <View style={styles.errorBox}>
              <Text style={styles.errorText}>{error}</Text>
              <TouchableOpacity onPress={() => loadDashboard()}>
                <Text style={styles.retry}>Coba lagi</Text>
              </TouchableOpacity>
            </View>
          ) : null}

          <View style={styles.statsGrid}>
            {stats.map((stat) => (
              <StatCard key={stat.label} stat={stat} />
            ))}
          </View>

          <View style={styles.quickSection}>
            <QuickLink icon="clipboard-outline" label="Kelola permintaan" onPress={goRequests} />
            <QuickLink icon="wallet-outline" label="Dompet & tarik saldo" onPress={goWallet} />
            <QuickLink
              icon="alert-circle-outline"
              label="Penawaran darurat"
              onPress={() => navigation.navigate('EmergencyOffers')}
              badge={emergencyCount > 0 ? emergencyCount : null}
            />
            <QuickLink icon="calendar-outline" label="Jadwal libur" onPress={() => navigation.navigate('Schedule')} />
            <QuickLink icon="pricetag-outline" label="Kelola layanan" onPress={() => navigation.navigate('Services')} />
            <QuickLink icon="medkit-outline" label="Paket layanan pendukung" onPress={() => navigation.navigate('SupportPackages')} />
            <QuickLink icon="images-outline" label="Portfolio" onPress={() => navigation.navigate('Portfolio')} />
            <QuickLink icon="create-outline" label="Profil publik" onPress={() => navigation.navigate('EditMuthowifProfile')} />
            {referralCode ? (
              <QuickLink icon="share-social-outline" label={`Referral: ${referralCode}`} onPress={shareReferral} />
            ) : null}
            <QuickLink
              icon="chatbubble-ellipses-outline"
              label="Chat jamaah"
              onPress={goChat}
              badge={unreadMessages > 0 ? unreadMessages : null}
            />
          </View>

          <Text style={styles.sectionTitle}>Jadwal Mendatang</Text>
          {schedules.length === 0 ? (
            <Text style={styles.muted}>Belum ada jadwal aktif.</Text>
          ) : (
            schedules.map((item) => (
              <ScheduleRow key={item.id} item={item} onPress={() => openSchedule(item)} />
            ))
          )}
        </ScrollView>
      )}
    </View>
  );
}

const styles = StyleSheet.create({
  safe: { flex: 1, backgroundColor: colors.canvas },
  scroll: { paddingHorizontal: 20, paddingBottom: 32 },
  loader: { marginTop: 40 },
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
  statsGrid: { flexDirection: 'row', flexWrap: 'wrap', gap: 10, marginBottom: 16 },
  statCard: {
    flex: 1,
    minWidth: '30%',
    backgroundColor: colors.white,
    borderRadius: 16,
    padding: 14,
    borderWidth: 1,
    borderColor: colors.slate100,
    borderLeftWidth: 4,
  },
  statValue: { fontSize: 16, fontWeight: '900', color: colors.slate900 },
  statLabel: { marginTop: 4, fontSize: 11, fontWeight: '700', color: colors.slate500 },
  quickSection: { gap: 10, marginBottom: 20 },
  quickLink: {
    flexDirection: 'row',
    alignItems: 'center',
    gap: 12,
    backgroundColor: colors.white,
    borderRadius: 16,
    padding: 14,
    borderWidth: 1,
    borderColor: colors.slate100,
  },
  quickIcon: { position: 'relative' },
  quickBadge: {
    position: 'absolute',
    top: -6,
    right: -8,
    minWidth: 16,
    height: 16,
    borderRadius: 8,
    backgroundColor: colors.baytgo,
    alignItems: 'center',
    justifyContent: 'center',
    paddingHorizontal: 4,
  },
  quickBadgeText: { fontSize: 9, fontWeight: '800', color: colors.white },
  quickLabel: { flex: 1, fontSize: 15, fontWeight: '800', color: colors.baytgo },
  sectionTitle: { fontSize: 18, fontWeight: '900', color: colors.baytgo, marginBottom: 12 },
  muted: { fontSize: 14, color: colors.slate500, fontWeight: '600', marginBottom: 16 },
  scheduleRow: {
    flexDirection: 'row',
    justifyContent: 'space-between',
    backgroundColor: colors.white,
    borderRadius: 14,
    padding: 14,
    marginBottom: 8,
    borderWidth: 1,
    borderColor: colors.slate100,
  },
  scheduleMeta: { flex: 1 },
  scheduleCode: { fontSize: 13, fontWeight: '800', color: colors.baytgo },
  scheduleCustomer: { marginTop: 4, fontSize: 13, color: colors.slate600, fontWeight: '600' },
  scheduleRight: { alignItems: 'flex-end' },
  scheduleDate: { fontSize: 12, fontWeight: '700', color: colors.slate700 },
  scheduleStatus: { marginTop: 4, fontSize: 10, fontWeight: '800', color: colors.slate400 },
});
