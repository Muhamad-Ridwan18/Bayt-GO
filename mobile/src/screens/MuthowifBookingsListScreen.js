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
import { useLocale } from '../utils/locale';

function PendingBanner({ count, color, onPress, isEn }) {
  if (count < 1) return null;

  return (
    <PressableScale onPress={onPress} haptic="light" style={styles.bannerPress}>
      <View style={[styles.banner, { backgroundColor: `${color}12`, borderColor: `${color}30` }]}>
        <View style={[styles.bannerIcon, { backgroundColor: colors.white }]}>
          <Bell size={18} color={color} strokeWidth={2} />
        </View>
        <View style={styles.bannerCopy}>
          <Text style={[styles.bannerTitle, { color }]}>
            {count} {isEn ? 'requests awaiting confirmation' : 'permintaan menunggu konfirmasi'}
          </Text>
          <Text style={styles.bannerSub}>
            {isEn ? 'Review and confirm incoming pilgrim requests' : 'Tinjau dan konfirmasi permintaan jamaah'}
          </Text>
        </View>
        <ChevronRight size={18} color={color} strokeWidth={2} />
      </View>
    </PressableScale>
  );
}

export default function MuthowifBookingsListScreen({ navigation }) {
  const { token } = useAuth();
  const locale = useLocale();
  const isEn = locale === 'en';
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
      setError(err.message || (isEn ? 'Failed to load requests' : 'Gagal memuat permintaan'));
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

  const localizedFilters = useMemo(() => {
    const labels = isEn
      ? {
          all: 'All',
          pending: 'Pending',
          confirmed: 'Confirmed',
          in_progress: 'In progress',
          completed: 'Completed',
          cancelled: 'Cancelled',
        }
      : {
          all: 'Semua',
          pending: 'Menunggu',
          confirmed: 'Dikonfirmasi',
          in_progress: 'Berlangsung',
          completed: 'Selesai',
          cancelled: 'Dibatalkan',
        };
    return MUTHOWIF_BOOKING_FILTERS.map((filter) => ({
      ...filter,
      label: labels[filter.value] || filter.label,
    }));
  }, [isEn]);

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
        isEn={isEn}
        onPress={() => setStatusFilter('pending')}
      />

      <View style={styles.statsRow}>
        <StatTile label={isEn ? 'Pending' : 'Menunggu'} value={stats.pending} color={pendingMeta.color} icon={Bell} />
        <StatTile label={isEn ? 'Active' : 'Aktif'} value={stats.active} color={confirmedMeta.color} icon={CalendarCheck} />
        <StatTile label={isEn ? 'Done' : 'Selesai'} value={stats.done} color={completedMeta.color} icon={CheckCircle2} />
      </View>

      <ScrollView
        horizontal
        showsHorizontalScrollIndicator={false}
        contentContainerStyle={styles.filters}
      >
        {localizedFilters.map((filter) => (
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
        <Text style={styles.resultCount}>{filteredItems.length} {isEn ? 'requests' : 'permintaan'}</Text>
      ) : null}
    </View>
  );

  if (loading && !refreshing) {
    return (
      <View style={styles.container}>
        <TabPageHeader variant="brand" title={isEn ? 'Requests' : 'Permintaan'} subtitle={isEn ? 'Manage pilgrim bookings' : 'Kelola booking jamaah'} />
        <SkeletonList count={4} style={styles.skeleton} />
      </View>
    );
  }

  return (
    <View style={styles.container}>
      <TabPageHeader
        variant="brand"
        title={isEn ? 'Requests' : 'Permintaan'}
        subtitle={stats.pending > 0
          ? `${stats.pending} ${isEn ? 'awaiting confirmation' : 'menunggu konfirmasi'}`
          : (isEn ? 'Manage pilgrim bookings' : 'Kelola booking jamaah')}
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
                title={isEn ? 'No requests yet' : 'Belum ada permintaan'}
                description={isEn ? 'Pilgrim booking requests will appear here.' : 'Permintaan booking dari jamaah akan muncul di sini.'}
                actionLabel={isEn ? 'Set time off schedule' : 'Atur jadwal libur'}
                onAction={() => navigation.getParent()?.navigate('HomeTab', { screen: 'Schedule' })}
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
