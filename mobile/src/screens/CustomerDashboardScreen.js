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
import { LinearGradient } from 'expo-linear-gradient';
import { Ionicons } from '@expo/vector-icons';
import { useAuth } from '../context/AuthContext';
import { fetchCustomerDashboard } from '../api/dashboard';
import MuthowifListItem from '../components/MuthowifListItem';
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

export default function CustomerDashboardScreen({ navigation }) {
  const { user, token } = useAuth();
  const [loading, setLoading] = useState(true);
  const [refreshing, setRefreshing] = useState(false);
  const [error, setError] = useState(null);
  const [stats, setStats] = useState([]);
  const [topMuthowifs, setTopMuthowifs] = useState([]);
  const [unreadMessages, setUnreadMessages] = useState(0);
  const [nextBooking, setNextBooking] = useState(null);

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

  const openMuthowif = (item) => {
    navigation.navigate('MuthowifDetail', { id: item.id });
  };

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

  return (
    <View style={styles.safe}>
      <LinearGradient colors={[colors.canvas, colors.white]} style={StyleSheet.absoluteFill} />
      <TabPageHeader title="Beranda" subtitle={`Halo, ${user?.name || 'Jamaah'}`} />

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
            <QuickLink icon="receipt-outline" label="Pesanan Saya" onPress={goBookings} />
            <QuickLink icon="help-buoy-outline" label="Tiket bantuan" onPress={goSupport} />
            <QuickLink
              icon="chatbubble-ellipses-outline"
              label="Chat booking"
              onPress={goChat}
              badge={unreadMessages > 0 ? unreadMessages : null}
            />
            <QuickLink icon="search-outline" label="Cari Muthowif" onPress={() => navigation.navigate('Directory')} />
          </View>

          {nextBooking ? (
            <>
              <Text style={styles.sectionTitle}>Perjalanan Berikutnya</Text>
              <TouchableOpacity style={styles.nextCard} onPress={openNextBooking} activeOpacity={0.9}>
                <View>
                  <Text style={styles.nextCode}>{nextBooking.booking_code}</Text>
                  <Text style={styles.nextMeta}>
                    {nextBooking.muthowif_name || 'Muthowif'} · {nextBooking.starts_on}
                  </Text>
                </View>
                <Ionicons name="chevron-forward" size={18} color={colors.slate400} />
              </TouchableOpacity>
            </>
          ) : null}

          <Text style={styles.sectionTitle}>Rekomendasi Muthowif</Text>
          {topMuthowifs.length === 0 ? (
            <Text style={styles.muted}>Belum ada rekomendasi.</Text>
          ) : (
            topMuthowifs.map((item) => (
              <MuthowifListItem key={item.id} item={item} onPress={() => openMuthowif(item)} />
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
  statValue: { fontSize: 18, fontWeight: '900', color: colors.slate900 },
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
  nextCard: {
    flexDirection: 'row',
    alignItems: 'center',
    justifyContent: 'space-between',
    backgroundColor: colors.white,
    borderRadius: 16,
    padding: 14,
    marginBottom: 20,
    borderWidth: 1,
    borderColor: colors.slate100,
  },
  nextCode: { fontSize: 14, fontWeight: '900', color: colors.baytgo },
  nextMeta: { marginTop: 4, fontSize: 12, fontWeight: '600', color: colors.slate500 },
  muted: { fontSize: 14, color: colors.slate500, fontWeight: '600', marginBottom: 16 },
});
