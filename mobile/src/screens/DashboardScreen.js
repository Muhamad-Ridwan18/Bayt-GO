import React, { useCallback, useEffect, useState } from 'react';
import {
  View,
  Text,
  StyleSheet,
  ScrollView,
  TouchableOpacity,
  ActivityIndicator,
  RefreshControl,
} from 'react-native';
import { SafeAreaView } from 'react-native-safe-area-context';
import { LinearGradient } from 'expo-linear-gradient';
import { Ionicons } from '@expo/vector-icons';
import { useAuth } from '../context/AuthContext';
import { fetchCustomerDashboard, fetchMuthowifDashboard } from '../api/dashboard';
import MuthowifListItem from '../components/MuthowifListItem';
import { colors } from '../theme/colors';

function StatCard({ stat }) {
  return (
    <View style={[styles.statCard, { borderLeftColor: stat.color || colors.baytgo }]}>
      <Text style={styles.statValue}>{stat.value}</Text>
      <Text style={styles.statLabel}>{stat.label}</Text>
    </View>
  );
}

function ScheduleRow({ item }) {
  return (
    <View style={styles.scheduleRow}>
      <View style={styles.scheduleMeta}>
        <Text style={styles.scheduleCode}>{item.booking_number}</Text>
        <Text style={styles.scheduleCustomer}>{item.customer_name}</Text>
      </View>
      <View style={styles.scheduleRight}>
        <Text style={styles.scheduleDate}>{item.date}</Text>
        <Text style={styles.scheduleStatus}>{item.status}</Text>
      </View>
    </View>
  );
}

export default function DashboardScreen({ navigation }) {
  const { user, token, logout } = useAuth();
  const isMuthowif = user?.role === 'muthowif';

  const [loading, setLoading] = useState(true);
  const [refreshing, setRefreshing] = useState(false);
  const [error, setError] = useState(null);
  const [stats, setStats] = useState([]);
  const [topMuthowifs, setTopMuthowifs] = useState([]);
  const [schedules, setSchedules] = useState([]);
  const [unreadMessages, setUnreadMessages] = useState(0);

  const loadDashboard = useCallback(async (refresh = false) => {
    if (refresh) setRefreshing(true);
    else setLoading(true);

    try {
      const data = isMuthowif
        ? await fetchMuthowifDashboard(token)
        : await fetchCustomerDashboard(token);

      setStats(data.stats || []);
      setUnreadMessages(data.unread_messages || 0);
      setTopMuthowifs(data.top_muthowifs || []);
      setSchedules(data.recent_schedules || []);
      setError(null);
    } catch (err) {
      setError(err.message || 'Gagal memuat dashboard');
    } finally {
      setLoading(false);
      setRefreshing(false);
    }
  }, [token, isMuthowif]);

  useEffect(() => {
    loadDashboard();
  }, [loadDashboard]);

  const handleLogout = async () => {
    await logout();
    navigation.reset({ index: 0, routes: [{ name: 'Home' }] });
  };

  const openMuthowif = (item) => {
    navigation.navigate('MuthowifDetail', { id: item.id });
  };

  return (
    <SafeAreaView style={styles.safe} edges={['top', 'bottom']}>
      <LinearGradient colors={[colors.canvas, colors.white]} style={StyleSheet.absoluteFill} />

      <View style={styles.header}>
        <View>
          <Text style={styles.greeting}>Halo,</Text>
          <Text style={styles.name}>{user?.name || 'Pengguna'}</Text>
        </View>
        <TouchableOpacity style={styles.logoutBtn} onPress={handleLogout}>
          <Ionicons name="log-out-outline" size={20} color={colors.baytgo} />
        </TouchableOpacity>
      </View>

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

          {unreadMessages > 0 && (
            <View style={styles.unreadBanner}>
              <Ionicons name="chatbubble-ellipses-outline" size={18} color={colors.baytgo} />
              <Text style={styles.unreadText}>{unreadMessages} pesan belum dibaca</Text>
            </View>
          )}

          <View style={styles.statsGrid}>
            {stats.map((stat) => (
              <StatCard key={stat.label} stat={stat} />
            ))}
          </View>

          {!isMuthowif ? (
            <TouchableOpacity style={styles.bookingsBtn} onPress={() => navigation.navigate('BookingsList')}>
              <Ionicons name="receipt-outline" size={18} color={colors.baytgo} />
              <Text style={styles.bookingsBtnText}>Pesanan Saya</Text>
              <Ionicons name="chevron-forward" size={18} color={colors.slate400} />
            </TouchableOpacity>
          ) : null}

          {isMuthowif ? (
            <>
              <Text style={styles.sectionTitle}>Jadwal Mendatang</Text>
              {schedules.length === 0 ? (
                <Text style={styles.muted}>Belum ada jadwal aktif.</Text>
              ) : (
                schedules.map((item) => <ScheduleRow key={item.id} item={item} />)
              )}
            </>
          ) : (
            <>
              <Text style={styles.sectionTitle}>Rekomendasi Muthowif</Text>
              {topMuthowifs.length === 0 ? (
                <Text style={styles.muted}>Belum ada rekomendasi.</Text>
              ) : (
                topMuthowifs.map((item) => (
                  <MuthowifListItem key={item.id} item={item} onPress={() => openMuthowif(item)} />
                ))
              )}
            </>
          )}

          <TouchableOpacity style={styles.searchBtn} onPress={() => navigation.navigate('Directory')}>
            <Text style={styles.searchBtnText}>Cari Muthowif</Text>
          </TouchableOpacity>

          <TouchableOpacity style={styles.homeBtn} onPress={() => navigation.navigate('Home')}>
            <Text style={styles.homeBtnText}>Kembali ke Beranda</Text>
          </TouchableOpacity>
        </ScrollView>
      )}
    </SafeAreaView>
  );
}

const styles = StyleSheet.create({
  safe: { flex: 1, backgroundColor: colors.canvas },
  header: {
    flexDirection: 'row',
    justifyContent: 'space-between',
    alignItems: 'center',
    paddingHorizontal: 20,
    paddingTop: 8,
    paddingBottom: 12,
  },
  greeting: { fontSize: 14, color: colors.slate500, fontWeight: '600' },
  name: { fontSize: 24, fontWeight: '900', color: colors.baytgo, marginTop: 2 },
  logoutBtn: {
    width: 44,
    height: 44,
    borderRadius: 14,
    backgroundColor: colors.white,
    alignItems: 'center',
    justifyContent: 'center',
    borderWidth: 1,
    borderColor: colors.slate100,
  },
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
  unreadBanner: {
    flexDirection: 'row',
    alignItems: 'center',
    gap: 8,
    backgroundColor: colors.emerald50,
    borderRadius: 14,
    padding: 12,
    marginBottom: 16,
  },
  unreadText: { fontSize: 13, fontWeight: '700', color: colors.baytgo },
  statsGrid: { flexDirection: 'row', flexWrap: 'wrap', gap: 10, marginBottom: 16 },
  bookingsBtn: {
    flexDirection: 'row',
    alignItems: 'center',
    gap: 10,
    backgroundColor: colors.white,
    borderRadius: 16,
    padding: 16,
    marginBottom: 20,
    borderWidth: 1,
    borderColor: colors.slate100,
  },
  bookingsBtnText: { flex: 1, fontSize: 15, fontWeight: '800', color: colors.baytgo },
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
  statValue: { fontSize: 18, fontWeight: '900', color: colors.slate900 },
  statLabel: { marginTop: 4, fontSize: 11, fontWeight: '700', color: colors.slate500 },
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
  searchBtn: {
    marginTop: 8,
    backgroundColor: colors.baytgo,
    borderRadius: 16,
    paddingVertical: 16,
    alignItems: 'center',
  },
  searchBtnText: { color: colors.white, fontWeight: '800', fontSize: 15 },
  homeBtn: {
    marginTop: 10,
    borderRadius: 16,
    paddingVertical: 16,
    alignItems: 'center',
    borderWidth: 1,
    borderColor: colors.slate200,
    backgroundColor: colors.white,
  },
  homeBtnText: { color: colors.baytgo, fontWeight: '800', fontSize: 15 },
});
