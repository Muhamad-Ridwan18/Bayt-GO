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
import TabPageHeader from '../components/TabPageHeader';
import BookingListItem from '../components/BookingListItem';
import { fetchBookings } from '../api/bookings';
import { useAuth } from '../context/AuthContext';
import { colors } from '../theme/colors';

const STATUS_FILTERS = [
  { value: 'all', label: 'Semua' },
  { value: 'pending', label: 'Menunggu' },
  { value: 'confirmed', label: 'Dikonfirmasi' },
  { value: 'in_progress', label: 'Berlangsung' },
  { value: 'completed', label: 'Selesai' },
  { value: 'cancelled', label: 'Dibatalkan' },
];

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

  const filteredItems = useMemo(() => {
    if (statusFilter === 'all') return items;
    return items.filter((item) => item.status === statusFilter);
  }, [items, statusFilter]);

  const renderFilters = () => (
    <ScrollView horizontal showsHorizontalScrollIndicator={false} contentContainerStyle={styles.filters}>
      {STATUS_FILTERS.map((filter) => {
        const active = statusFilter === filter.value;
        return (
          <TouchableOpacity
            key={filter.value}
            style={[styles.filterChip, active && styles.filterChipActive]}
            onPress={() => setStatusFilter(filter.value)}
          >
            <Text style={[styles.filterText, active && styles.filterTextActive]}>{filter.label}</Text>
          </TouchableOpacity>
        );
      })}
    </ScrollView>
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
          ListHeaderComponent={renderFilters}
          renderItem={({ item }) => (
            <BookingListItem
              item={item}
              onPress={() => navigation.navigate('BookingDetail', { bookingId: item.id })}
            />
          )}
          contentContainerStyle={styles.list}
          refreshControl={
            <RefreshControl refreshing={refreshing} onRefresh={() => load(true)} tintColor={colors.baytgo} />
          }
          ListEmptyComponent={
            <View style={styles.empty}>
              <Text style={styles.emptyText}>
                {error || 'Belum ada pesanan. Cari muthowif untuk memulai.'}
              </Text>
              {error ? (
                <TouchableOpacity onPress={() => load()}>
                  <Text style={styles.retry}>Coba lagi</Text>
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
  filters: { gap: 8, paddingBottom: 12 },
  filterChip: {
    borderRadius: 999,
    paddingHorizontal: 14,
    paddingVertical: 8,
    backgroundColor: colors.white,
    borderWidth: 1,
    borderColor: colors.slate200,
  },
  filterChipActive: { backgroundColor: colors.baytgo, borderColor: colors.baytgo },
  filterText: { fontSize: 12, fontWeight: '800', color: colors.slate600 },
  filterTextActive: { color: colors.white },
  list: { padding: 16, paddingBottom: 24 },
  loader: { marginTop: 40 },
  empty: {
    backgroundColor: colors.white,
    borderRadius: 18,
    padding: 24,
    borderWidth: 1,
    borderStyle: 'dashed',
    borderColor: colors.slate200,
    alignItems: 'center',
  },
  emptyText: { fontSize: 14, color: colors.slate500, fontWeight: '600', textAlign: 'center', lineHeight: 20 },
  retry: { marginTop: 10, fontSize: 14, fontWeight: '800', color: colors.baytgo },
  cta: {
    marginTop: 14,
    backgroundColor: colors.baytgo,
    borderRadius: 14,
    paddingHorizontal: 16,
    paddingVertical: 12,
  },
  ctaText: { color: colors.white, fontWeight: '800', fontSize: 14 },
});
