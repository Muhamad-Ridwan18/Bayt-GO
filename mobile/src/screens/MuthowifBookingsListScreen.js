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
import MuthowifBookingListItem from '../components/MuthowifBookingListItem';
import { fetchMuthowifBookings } from '../api/muthowifBookings';
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

export default function MuthowifBookingsListScreen({ navigation }) {
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
      const data = await fetchMuthowifBookings(token);
      setItems(data.bookings || []);
      setError(null);
    } catch (err) {
      setError(err.message || 'Gagal memuat permintaan');
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

  const pendingCount = items.filter((b) => b.status === 'pending').length;

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
      <TabPageHeader
        title="Permintaan"
        subtitle={pendingCount > 0 ? `${pendingCount} menunggu konfirmasi` : 'Kelola booking jamaah'}
      />

      {loading && !refreshing ? (
        <ActivityIndicator color={colors.baytgo} style={styles.loader} />
      ) : (
        <FlatList
          data={filteredItems}
          keyExtractor={(item) => String(item.id)}
          ListHeaderComponent={renderFilters}
          renderItem={({ item }) => (
            <MuthowifBookingListItem
              item={item}
              onPress={() => navigation.navigate('MuthowifBookingDetail', { bookingId: item.id })}
            />
          )}
          contentContainerStyle={styles.list}
          refreshControl={
            <RefreshControl refreshing={refreshing} onRefresh={() => load(true)} tintColor={colors.baytgo} />
          }
          ListEmptyComponent={
            <View style={styles.empty}>
              <Text style={styles.emptyText}>
                {error || 'Belum ada permintaan booking.'}
              </Text>
              {error ? (
                <TouchableOpacity onPress={() => load()}>
                  <Text style={styles.retry}>Coba lagi</Text>
                </TouchableOpacity>
              ) : null}
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
  list: { paddingHorizontal: 20, paddingBottom: 32 },
  loader: { marginTop: 40 },
  empty: { paddingTop: 48, alignItems: 'center' },
  emptyText: { fontSize: 14, color: colors.slate500, fontWeight: '600', textAlign: 'center' },
  retry: { marginTop: 12, fontSize: 14, fontWeight: '800', color: colors.baytgo },
});
