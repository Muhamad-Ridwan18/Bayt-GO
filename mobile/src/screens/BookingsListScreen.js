import React, { useCallback, useMemo, useState } from 'react';
import {
  View,
  Text,
  StyleSheet,
  FlatList,
  ActivityIndicator,
  RefreshControl,
  TouchableOpacity,
  ScrollView,
} from 'react-native';
import { useFocusEffect } from '@react-navigation/native';
import { Ionicons } from '@expo/vector-icons';
import TabPageHeader from '../components/TabPageHeader';
import BookingListItem from '../components/BookingListItem';
import { fetchBookings } from '../api/bookings';
import { useAuth } from '../context/AuthContext';
import { colors } from '../theme/colors';
import { canPayBooking } from '../utils/bookingLabels';

const STATUS_FILTERS = [
  { value: 'all', label: 'Semua', icon: 'layers-outline' },
  { value: 'unpaid', label: 'Belum bayar', icon: 'wallet-outline' },
  { value: 'pending', label: 'Menunggu', icon: 'time-outline' },
  { value: 'confirmed', label: 'Dikonfirmasi', icon: 'checkmark-circle-outline' },
  { value: 'in_progress', label: 'Berlangsung', icon: 'walk-outline' },
  { value: 'completed', label: 'Selesai', icon: 'flag-outline' },
  { value: 'cancelled', label: 'Dibatalkan', icon: 'close-circle-outline' },
];

function StatCard({ label, value, color, icon }) {
  return (
    <View style={[styles.statCard, { borderColor: color + '30' }]}>
      <View style={[styles.statIcon, { backgroundColor: color + '18' }]}>
        <Ionicons name={icon} size={16} color={color} />
      </View>
      <Text style={[styles.statValue, { color }]}>{value}</Text>
      <Text style={styles.statLabel}>{label}</Text>
    </View>
  );
}

export default function BookingsListScreen({ navigation }) {
  const { token } = useAuth();
  const [items, setItems] = useState([]);
  const [statusFilter, setStatusFilter] = useState('all');
  const [loading, setLoading] = useState(true);
  const [refreshing, setRefreshing] = useState(false);
  const [error, setError] = useState(null);

  const load = useCallback(async (refresh = false) => {
    if (refresh) setRefreshing(true);
    else setLoading(true);

    try {
      const data = await fetchBookings(token);
      setItems(data.data || []);
      setError(null);
    } catch (err) {
      setError(err.message || 'Gagal memuat booking');
      if (!refresh) setItems([]);
    } finally {
      setLoading(false);
      setRefreshing(false);
    }
  }, [token]);

  useFocusEffect(
    useCallback(() => {
      load();
    }, [load]),
  );

  const stats = useMemo(() => ({
    unpaid: items.filter((b) => canPayBooking(b)).length,
    active: items.filter((b) => ['confirmed', 'in_progress'].includes(b.status)).length,
    done: items.filter((b) => b.status === 'completed').length,
  }), [items]);

  const filteredItems = useMemo(() => {
    if (statusFilter === 'all') return items;
    if (statusFilter === 'unpaid') return items.filter((item) => canPayBooking(item));
    return items.filter((item) => item.status === statusFilter);
  }, [items, statusFilter]);

  const openPayment = (item) => {
    navigation.navigate('BookingPayment', {
      bookingId: item.id,
      bookingCode: item.booking_code,
    });
  };

  const renderHeader = () => (
    <View style={styles.headerBlock}>
      {stats.unpaid > 0 ? (
        <TouchableOpacity style={styles.alertBanner} onPress={() => setStatusFilter('unpaid')} activeOpacity={0.9}>
          <Ionicons name="wallet-outline" size={18} color="#B45309" />
          <Text style={styles.alertText}>
            {stats.unpaid} pesanan menunggu pembayaran
          </Text>
          <Ionicons name="chevron-forward" size={16} color="#B45309" />
        </TouchableOpacity>
      ) : null}

      <View style={styles.statsRow}>
        <StatCard label="Belum bayar" value={stats.unpaid} color="#F59E0B" icon="wallet-outline" />
        <StatCard label="Aktif" value={stats.active} color="#0984E3" icon="calendar-outline" />
        <StatCard label="Selesai" value={stats.done} color="#00B894" icon="checkmark-done-outline" />
      </View>

      <ScrollView horizontal showsHorizontalScrollIndicator={false} contentContainerStyle={styles.tabBar}>
        {STATUS_FILTERS.map((filter) => {
          const active = statusFilter === filter.value;
          return (
            <TouchableOpacity
              key={filter.value}
              style={[styles.tabBtn, active && styles.tabBtnActive]}
              onPress={() => setStatusFilter(filter.value)}
              activeOpacity={0.88}
            >
              <Ionicons name={filter.icon} size={14} color={active ? colors.white : colors.slate500} />
              <Text style={[styles.tabText, active && styles.tabTextActive]}>{filter.label}</Text>
            </TouchableOpacity>
          );
        })}
      </ScrollView>

      {filteredItems.length > 0 ? (
        <Text style={styles.resultCount}>{filteredItems.length} pesanan</Text>
      ) : null}
    </View>
  );

  return (
    <View style={styles.container}>
      <TabPageHeader title="Pesanan Saya" subtitle="Kelola booking muthowif Anda" />

      {loading && !refreshing ? (
        <ActivityIndicator color={colors.baytgo} style={styles.loader} />
      ) : (
        <FlatList
          data={filteredItems}
          keyExtractor={(item) => String(item.id)}
          ListHeaderComponent={renderHeader}
          renderItem={({ item }) => (
            <BookingListItem
              item={item}
              onPress={() => navigation.navigate('BookingDetail', { bookingId: item.id })}
              onPay={openPayment}
            />
          )}
          contentContainerStyle={styles.list}
          showsVerticalScrollIndicator={false}
          refreshControl={
            <RefreshControl refreshing={refreshing} onRefresh={() => load(true)} tintColor={colors.baytgo} />
          }
          ListEmptyComponent={
            <View style={styles.empty}>
              <View style={styles.emptyIcon}>
                <Ionicons name="receipt-outline" size={32} color={colors.slate400} />
              </View>
              <Text style={styles.emptyTitle}>
                {error ? 'Gagal memuat data' : 'Belum ada pesanan'}
              </Text>
              <Text style={styles.emptyText}>
                {error || 'Cari muthowif untuk memulai perjalanan ibadah Anda.'}
              </Text>
              {error ? (
                <TouchableOpacity style={styles.retryBtn} onPress={() => load()}>
                  <Text style={styles.retryText}>Coba lagi</Text>
                </TouchableOpacity>
              ) : (
                <TouchableOpacity
                  style={styles.cta}
                  onPress={() => navigation.getParent()?.navigate('HomeTab', { screen: 'Directory' })}
                >
                  <Text style={styles.ctaText}>Cari Muthowif</Text>
                </TouchableOpacity>
              )}
            </View>
          }
        />
      )}
    </View>
  );
}

const styles = StyleSheet.create({
  container: { flex: 1, backgroundColor: colors.canvas },
  headerBlock: { paddingBottom: 4 },
  alertBanner: {
    flexDirection: 'row',
    alignItems: 'center',
    gap: 10,
    backgroundColor: '#FFFBEB',
    borderRadius: 14,
    padding: 14,
    marginBottom: 14,
    borderWidth: 1,
    borderColor: '#FDE68A',
  },
  alertText: { flex: 1, fontSize: 13, fontWeight: '700', color: '#B45309', lineHeight: 18 },
  statsRow: { flexDirection: 'row', gap: 10, marginBottom: 14 },
  statCard: {
    flex: 1,
    backgroundColor: colors.white,
    borderRadius: 16,
    padding: 12,
    borderWidth: 1,
    alignItems: 'center',
  },
  statIcon: {
    width: 32,
    height: 32,
    borderRadius: 10,
    alignItems: 'center',
    justifyContent: 'center',
    marginBottom: 8,
  },
  statValue: { fontSize: 20, fontWeight: '900' },
  statLabel: { marginTop: 2, fontSize: 11, fontWeight: '700', color: colors.slate500 },
  tabBar: { flexDirection: 'row', gap: 8, marginBottom: 12, paddingRight: 4 },
  tabBtn: {
    flexDirection: 'row',
    alignItems: 'center',
    gap: 5,
    paddingHorizontal: 12,
    paddingVertical: 9,
    borderRadius: 999,
    backgroundColor: colors.white,
    borderWidth: 1,
    borderColor: colors.slate100,
  },
  tabBtnActive: { backgroundColor: colors.baytgo, borderColor: colors.baytgo },
  tabText: { fontSize: 12, fontWeight: '800', color: colors.slate600 },
  tabTextActive: { color: colors.white },
  resultCount: {
    fontSize: 12,
    fontWeight: '700',
    color: colors.slate500,
    marginBottom: 10,
    marginLeft: 2,
  },
  list: { paddingHorizontal: 20, paddingBottom: 32 },
  loader: { marginTop: 40 },
  empty: { alignItems: 'center', paddingTop: 40, paddingHorizontal: 20 },
  emptyIcon: {
    width: 72,
    height: 72,
    borderRadius: 36,
    backgroundColor: colors.white,
    alignItems: 'center',
    justifyContent: 'center',
    marginBottom: 16,
    borderWidth: 1,
    borderColor: colors.slate100,
  },
  emptyTitle: { fontSize: 16, fontWeight: '900', color: colors.baytgo },
  emptyText: {
    marginTop: 8,
    fontSize: 13,
    fontWeight: '600',
    color: colors.slate500,
    textAlign: 'center',
    lineHeight: 19,
  },
  retryBtn: {
    marginTop: 16,
    paddingHorizontal: 20,
    paddingVertical: 12,
    borderRadius: 12,
    backgroundColor: colors.baytgo,
  },
  retryText: { fontSize: 14, fontWeight: '800', color: colors.white },
  cta: {
    marginTop: 16,
    backgroundColor: colors.baytgo,
    borderRadius: 14,
    paddingHorizontal: 20,
    paddingVertical: 13,
  },
  ctaText: { color: colors.white, fontWeight: '800', fontSize: 14 },
});
