import React, { useCallback, useMemo, useState } from 'react';
import { ScrollView, StyleSheet, Text, View } from 'react-native';
import { FlashList } from '@shopify/flash-list';
import { useFocusEffect } from '@react-navigation/native';
import { Bell, CalendarCheck, CheckCircle2, ChevronRight } from 'lucide-react-native';
import TabPageHeader from '../components/TabPageHeader';
import MuthowifBookingListItem from '../components/MuthowifBookingListItem';
import { fetchMuthowifBookings } from '../api/muthowifBookings';
import { useAuth } from '../context/AuthContext';
import { MUTHOWIF_BOOKING_FILTERS } from '../constants/muthowifBookingFilters';
import EmptyState from '../ui/EmptyState';
import ErrorState from '../ui/ErrorState';
import FilterChip from '../ui/FilterChip';
import PressableScale from '../ui/PressableScale';
import { SkeletonList } from '../ui/Skeleton';
import StatTile from '../ui/StatTile';
import { colors, layout, spacing, typography } from '../theme/tokens';
import { bookingStatusMeta } from '../utils/bookingLabels';

function PendingBanner({ count, color, onPress }) {
  if (count < 1) return null;

  return (
    <PressableScale onPress={onPress} haptic="light" style={styles.bannerPress}>
      <View style={[styles.banner, { backgroundColor: `${color}12`, borderColor: `${color}30` }]}>
        <View style={[styles.bannerIcon, { backgroundColor: colors.white }]}>
          <Bell size={18} color={color} strokeWidth={2} />
        </View>
        <View style={styles.bannerCopy}>
          <Text style={[styles.bannerTitle, { color }]}>{count} permintaan menunggu konfirmasi</Text>
          <Text style={styles.bannerSub}>Tinjau dan konfirmasi permintaan jamaah</Text>
        </View>
        <ChevronRight size={18} color={color} strokeWidth={2} />
      </View>
    </PressableScale>
  );
}

export default function MuthowifBookingsListScreen({ navigation }) {
  const { token } = useAuth();
  const [items, setItems] = useState([]);
  const [statusFilter, setStatusFilter] = useState('all');
  const [loading, setLoading] = useState(true);
  const [refreshing, setRefreshing] = useState(false);
  const [error, setError] = useState(null);

  const pendingMeta = bookingStatusMeta('pending');
  const confirmedMeta = bookingStatusMeta('confirmed');
  const completedMeta = bookingStatusMeta('completed');

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

  const stats = useMemo(() => ({
    pending: items.filter((b) => b.status === 'pending').length,
    active: items.filter((b) => ['confirmed', 'in_progress'].includes(b.status)).length,
    done: items.filter((b) => b.status === 'completed').length,
  }), [items]);

  const filteredItems = useMemo(() => {
    if (statusFilter === 'all') return items;
    return items.filter((item) => item.status === statusFilter);
  }, [items, statusFilter]);

  const renderItem = useCallback(({ item }) => (
    <MuthowifBookingListItem
      item={item}
      onPress={() => navigation.navigate('MuthowifBookingDetail', { bookingId: item.id })}
    />
  ), [navigation]);

  const listHeader = (
    <View style={styles.headerBlock}>
      <PendingBanner
        count={stats.pending}
        color={pendingMeta.color}
        onPress={() => setStatusFilter('pending')}
      />

      <View style={styles.statsRow}>
        <StatTile label="Menunggu" value={stats.pending} color={pendingMeta.color} icon={Bell} />
        <StatTile label="Aktif" value={stats.active} color={confirmedMeta.color} icon={CalendarCheck} />
        <StatTile label="Selesai" value={stats.done} color={completedMeta.color} icon={CheckCircle2} />
      </View>

      <ScrollView
        horizontal
        showsHorizontalScrollIndicator={false}
        contentContainerStyle={styles.filters}
      >
        {MUTHOWIF_BOOKING_FILTERS.map((filter) => (
          <FilterChip
            key={filter.value}
            label={filter.label}
            icon={filter.icon}
            active={statusFilter === filter.value}
            onPress={() => setStatusFilter(filter.value)}
          />
        ))}
      </ScrollView>

      {filteredItems.length > 0 ? (
        <Text style={styles.resultCount}>{filteredItems.length} permintaan</Text>
      ) : null}
    </View>
  );

  if (loading && !refreshing) {
    return (
      <View style={styles.container}>
        <TabPageHeader title="Permintaan" subtitle="Kelola booking jamaah" />
        <SkeletonList count={4} style={styles.skeleton} />
      </View>
    );
  }

  return (
    <View style={styles.container}>
      <TabPageHeader
        title="Permintaan"
        subtitle={stats.pending > 0 ? `${stats.pending} menunggu konfirmasi` : 'Kelola booking jamaah'}
      />

      {error && items.length === 0 ? (
        <ErrorState description={error} onRetry={() => load()} />
      ) : (
        <FlashList
          data={filteredItems}
          keyExtractor={(item) => String(item.id)}
          renderItem={renderItem}
          estimatedItemSize={200}
          ListHeaderComponent={listHeader}
          contentContainerStyle={styles.list}
          showsVerticalScrollIndicator={false}
          refreshing={refreshing}
          onRefresh={() => load(true)}
          ListEmptyComponent={
            error ? (
              <ErrorState description={error} onRetry={() => load()} />
            ) : (
              <EmptyState
                variant="bookings"
                title="Belum ada permintaan"
                description="Permintaan booking dari jamaah akan muncul di sini."
              />
            )
          }
        />
      )}
    </View>
  );
}

const styles = StyleSheet.create({
  container: {
    flex: 1,
    backgroundColor: colors.background,
  },
  skeleton: {
    paddingHorizontal: layout.screenPadding,
    paddingTop: spacing.lg,
  },
  headerBlock: {
    paddingBottom: spacing.sm,
  },
  bannerPress: {
    marginBottom: spacing.lg,
  },
  banner: {
    flexDirection: 'row',
    alignItems: 'center',
    gap: spacing.md,
    borderRadius: 20,
    padding: spacing.lg,
    borderWidth: 1,
  },
  bannerIcon: {
    width: 40,
    height: 40,
    borderRadius: 12,
    alignItems: 'center',
    justifyContent: 'center',
  },
  bannerCopy: { flex: 1 },
  bannerTitle: {
    ...typography.caption,
    fontFamily: 'PlusJakartaSans_700Bold',
  },
  bannerSub: {
    ...typography.small,
    color: colors.textSecondary,
    marginTop: spacing.xs,
    fontWeight: '500',
    fontFamily: 'PlusJakartaSans_500Medium',
  },
  statsRow: {
    flexDirection: 'row',
    gap: spacing.md,
    marginBottom: spacing.lg,
  },
  filters: {
    gap: spacing.sm,
    paddingRight: spacing.lg,
    marginBottom: spacing.md,
  },
  resultCount: {
    ...typography.small,
    color: colors.textSecondary,
    marginBottom: spacing.md,
    marginLeft: spacing.xs,
    fontWeight: '500',
    fontFamily: 'PlusJakartaSans_500Medium',
  },
  list: {
    paddingHorizontal: layout.screenPadding,
    paddingBottom: spacing.lg,
  },
});
