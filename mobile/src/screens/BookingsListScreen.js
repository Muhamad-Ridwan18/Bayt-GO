import React, { useCallback, useMemo, useState } from 'react';
import { ScrollView, StyleSheet, Text, View } from 'react-native';
import { FlashList } from '@shopify/flash-list';
import { useFocusEffect } from '@react-navigation/native';
import { CalendarCheck, ChevronRight, SlidersHorizontal, Wallet } from 'lucide-react-native';
import TabPageHeader from '../components/TabPageHeader';
import BookingListItem from '../components/BookingListItem';
import { fetchBookings } from '../api/bookings';
import { useAuth } from '../context/AuthContext';
import { BOOKING_STATUS_FILTERS } from '../constants/bookingFilters';
import EmptyState from '../ui/EmptyState';
import ErrorState from '../ui/ErrorState';
import FilterChip from '../ui/FilterChip';
import FilterSheet from '../ui/FilterSheet';
import Button from '../ui/Button';
import PressableScale from '../ui/PressableScale';
import { SkeletonList } from '../ui/Skeleton';
import StatTile from '../ui/StatTile';
import { colors, layout, radius, spacing, typography } from '../theme/tokens';
import { canPayBooking, canOpenBookingChat } from '../utils/bookingLabels';
import SwipeableRow from '../ui/SwipeableRow';
import { navigateToChatRoom } from '../navigation/rootNavigation';

function UnpaidBanner({ count, onPress }) {
  if (count < 1) return null;

  return (
    <PressableScale onPress={onPress} haptic="light" style={styles.bannerPress}>
      <View style={styles.banner}>
        <View style={styles.bannerIcon}>
          <Wallet size={18} color={colors.warning} strokeWidth={2} />
        </View>
        <View style={styles.bannerCopy}>
          <Text style={styles.bannerTitle}>{count} pesanan menunggu pembayaran</Text>
          <Text style={styles.bannerSub}>Selesaikan pembayaran agar booking diproses</Text>
        </View>
        <ChevronRight size={18} color={colors.warning} strokeWidth={2} />
      </View>
    </PressableScale>
  );
}

export default function BookingsListScreen({ navigation }) {
  const { token } = useAuth();
  const [items, setItems] = useState([]);
  const [statusFilter, setStatusFilter] = useState('all');
  const [filterSheetOpen, setFilterSheetOpen] = useState(false);
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

  const openPayment = useCallback((item) => {
    navigation.navigate('BookingPayment', {
      bookingId: item.id,
      bookingCode: item.booking_code,
    });
  }, [navigation]);

  const openChat = useCallback((item) => {
    navigateToChatRoom({
      bookingId: item.id,
      bookingCode: item.booking_code,
      otherName: item.muthowif_name,
    });
  }, []);

  const renderItem = useCallback(({ item }) => {
    const rightActions = [];
    const leftActions = [];

    if (canPayBooking(item)) {
      rightActions.push({
        key: 'pay',
        label: 'Bayar',
        backgroundColor: '#D97706',
        width: 92,
        onPress: () => openPayment(item),
      });
    }
    if (canOpenBookingChat(item)) {
      leftActions.push({
        key: 'chat',
        label: 'Chat',
        backgroundColor: colors.success,
        width: 88,
        onPress: () => openChat(item),
      });
    }

    return (
      <SwipeableRow leftActions={leftActions} rightActions={rightActions}>
        <BookingListItem
          item={item}
          onPress={() => navigation.navigate('BookingDetail', { bookingId: item.id })}
          onPay={openPayment}
        />
      </SwipeableRow>
    );
  }, [navigation, openPayment, openChat]);

  const listHeader = (
    <View style={styles.headerBlock}>
      <UnpaidBanner count={stats.unpaid} onPress={() => setStatusFilter('unpaid')} />

      <View style={styles.statsRow}>
        <StatTile label="Belum bayar" value={stats.unpaid} color={colors.warning} icon={Wallet} />
        <StatTile label="Aktif" value={stats.active} color="#0284C7" icon={CalendarCheck} />
        <StatTile label="Selesai" value={stats.done} color={colors.success} icon={CalendarCheck} />
      </View>

      <ScrollView
        horizontal
        showsHorizontalScrollIndicator={false}
        contentContainerStyle={styles.filters}
      >
        <PressableScale onPress={() => setFilterSheetOpen(true)} haptic="light">
          <View style={styles.filterSheetBtn}>
            <SlidersHorizontal size={16} color={colors.baytgo} strokeWidth={2} />
            <Text style={styles.filterSheetText}>Filter</Text>
          </View>
        </PressableScale>
        {BOOKING_STATUS_FILTERS.map((filter) => (
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
        <Text style={styles.resultCount}>{filteredItems.length} pesanan</Text>
      ) : null}
    </View>
  );

  if (loading && !refreshing) {
    return (
      <View style={styles.container}>
        <TabPageHeader title="Pesanan Saya" subtitle="Kelola booking muthowif Anda" />
        <SkeletonList count={4} style={styles.skeleton} />
      </View>
    );
  }

  return (
    <View style={styles.container}>
      <TabPageHeader title="Pesanan Saya" subtitle="Kelola booking muthowif Anda" />

      {error && items.length === 0 ? (
        <ErrorState description={error} onRetry={() => load()} />
      ) : (
        <FlashList
          data={filteredItems}
          keyExtractor={(item) => String(item.id)}
          renderItem={renderItem}
          estimatedItemSize={180}
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
                title="Belum ada pesanan"
                description="Cari muthowif terpercaya dan mulai rencanakan perjalanan ibadah Anda."
                actionLabel="Cari Muthowif"
                onAction={() => navigation.getParent()?.navigate('HomeTab', { screen: 'Directory' })}
              />
            )
          }
        />
      )}

      <FilterSheet
        visible={filterSheetOpen}
        onClose={() => setFilterSheetOpen(false)}
        title="Filter pesanan"
        subtitle={`${filteredItems.length} dari ${items.length} pesanan`}
        footer={(
          <Button label="Terapkan" onPress={() => setFilterSheetOpen(false)} />
        )}
      >
        {BOOKING_STATUS_FILTERS.map((filter) => (
          <FilterChip
            key={`sheet-${filter.value}`}
            label={filter.label}
            icon={filter.icon}
            active={statusFilter === filter.value}
            onPress={() => setStatusFilter(filter.value)}
          />
        ))}
      </FilterSheet>
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
    backgroundColor: colors.warningLight,
    borderRadius: 20,
    padding: spacing.lg,
    borderWidth: 1,
    borderColor: '#FDE68A',
  },
  bannerIcon: {
    width: 40,
    height: 40,
    borderRadius: 12,
    backgroundColor: colors.white,
    alignItems: 'center',
    justifyContent: 'center',
  },
  bannerCopy: { flex: 1 },
  bannerTitle: {
    ...typography.caption,
    fontFamily: 'PlusJakartaSans_700Bold',
    color: '#B45309',
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
  filterSheetBtn: {
    flexDirection: 'row',
    alignItems: 'center',
    gap: spacing.xs,
    backgroundColor: colors.baytgoLight,
    borderRadius: radius.full,
    paddingHorizontal: spacing.md,
    paddingVertical: spacing.sm,
    borderWidth: 1,
    borderColor: colors.border,
  },
  filterSheetText: { ...typography.small, color: colors.baytgo, fontWeight: '700' },
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
