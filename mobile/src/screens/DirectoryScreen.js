import React, { useCallback, useEffect, useMemo, useState } from 'react';
import { ActivityIndicator, StyleSheet, Text, View } from 'react-native';
import { FlashList } from '@shopify/flash-list';
import { SafeAreaView } from 'react-native-safe-area-context';
import { ArrowLeft, ArrowRight, Compass, Search, Users } from 'lucide-react-native';
import DatePickerField, { parseIsoDate } from '../components/DatePickerField';
import MuthowifListingCard from '../components/MuthowifListingCard';
import { fetchDirectory } from '../api/directory';
import { useAuth } from '../context/AuthContext';
import Button from '../ui/Button';
import Card from '../ui/Card';
import EmptyState from '../ui/EmptyState';
import ErrorState from '../ui/ErrorState';
import PressableScale from '../ui/PressableScale';
import SearchBar from '../ui/SearchBar';
import { SkeletonList } from '../ui/Skeleton';
import { colors, layout, radius, spacing, typography } from '../theme/tokens';

export default function DirectoryScreen({ navigation, route }) {
  const { token } = useAuth();
  const initial = route.params || {};

  const [q, setQ] = useState(initial.q || '');
  const [startDate, setStartDate] = useState(initial.startDate || '');
  const [endDate, setEndDate] = useState(initial.endDate || '');
  const [items, setItems] = useState([]);
  const [page, setPage] = useState(1);
  const [lastPage, setLastPage] = useState(1);
  const [total, setTotal] = useState(0);
  const [loading, setLoading] = useState(true);
  const [loadingMore, setLoadingMore] = useState(false);
  const [refreshing, setRefreshing] = useState(false);
  const [error, setError] = useState(null);

  const today = useMemo(() => {
    const d = new Date();
    d.setHours(0, 0, 0, 0);
    return d;
  }, []);

  const handleStartDateChange = (iso) => {
    setStartDate(iso);
    if (endDate && iso && endDate < iso) setEndDate('');
  };

  const endMinDate = startDate ? parseIsoDate(startDate) : today;
  const endMaxDate = useMemo(() => {
    if (!startDate) return undefined;
    const max = parseIsoDate(startDate);
    max.setDate(max.getDate() + 90);
    return max;
  }, [startDate]);

  const loadPage = useCallback(async (pageNum, { append = false, refresh = false, overrides = {} } = {}) => {
    if (append) setLoadingMore(true);
    else if (refresh) setRefreshing(true);
    else setLoading(true);

    const searchQ = overrides.q !== undefined ? overrides.q : q;
    const searchStart = overrides.startDate !== undefined ? overrides.startDate : startDate;
    const searchEnd = overrides.endDate !== undefined ? overrides.endDate : endDate;

    try {
      const data = await fetchDirectory({
        token,
        q: String(searchQ || '').trim(),
        startDate: String(searchStart || '').trim(),
        endDate: String(searchEnd || '').trim(),
        page: pageNum,
      });

      setItems((prev) => (append ? [...prev, ...(data.data || [])] : data.data || []));
      setPage(data.current_page || pageNum);
      setLastPage(data.last_page || 1);
      setTotal(data.total || 0);
      setError(null);
    } catch (err) {
      setError(err.message || 'Gagal memuat direktori');
      if (!append) setItems([]);
    } finally {
      setLoading(false);
      setLoadingMore(false);
      setRefreshing(false);
    }
  }, [token, q, startDate, endDate]);

  useEffect(() => {
    loadPage(1);
  }, [loadPage]);

  const handleSearch = () => loadPage(1);

  const handleLoadMore = () => {
    if (loadingMore || page >= lastPage) return;
    loadPage(page + 1, { append: true });
  };

  const navParams = (item) => ({
    id: item.id,
    startDate: startDate.trim() || undefined,
    endDate: endDate.trim() || undefined,
  });

  const openDetail = (item) => {
    navigation.navigate('MuthowifDetail', navParams(item));
  };

  const openBook = (item) => {
    navigation.navigate('MuthowifDetail', { ...navParams(item), autoBook: true });
  };

  const renderItem = useCallback(({ item }) => (
    <MuthowifListingCard
      item={item}
      onPressDetail={() => openDetail(item)}
      onPressBook={() => openBook(item)}
    />
  ), [startDate, endDate, navigation]);

  const listHeader = (
    <View style={styles.listHeader}>
      <Card style={styles.searchCard} padding={spacing.xl} elevated>
        <View style={styles.searchCardHead}>
          <View style={styles.searchCardIcon}>
            <Compass size={18} color={colors.baytgo} strokeWidth={2} />
          </View>
          <View style={styles.searchCardHeadText}>
            <Text style={styles.searchCardTitle}>Sesuaikan Pencarian</Text>
            <Text style={styles.searchCardSub}>Atur tanggal dan kata kunci untuk hasil terbaik</Text>
          </View>
        </View>

        <View style={styles.dateRow}>
          <View style={styles.dateField}>
            <DatePickerField
              label="Berangkat"
              value={startDate}
              onChange={handleStartDateChange}
              placeholder="Pilih"
              minimumDate={today}
              variant="chip"
            />
          </View>
          <View style={styles.dateArrow}>
            <ArrowRight size={16} color={colors.textMuted} strokeWidth={2} />
          </View>
          <View style={styles.dateField}>
            <DatePickerField
              label="Pulang"
              value={endDate}
              onChange={setEndDate}
              placeholder="Opsional"
              minimumDate={endMinDate}
              maximumDate={endMaxDate}
              clearable
              onClear={() => setEndDate('')}
              variant="chip"
            />
          </View>
        </View>

        <SearchBar
          value={q}
          onChangeText={setQ}
          placeholder="Nama muthowif, bahasa, atau kota..."
          style={styles.searchBar}
        />

        <View style={styles.searchCta}>
          <Button
            label="Tampilkan hasil"
            onPress={handleSearch}
            icon={<Search size={18} color={colors.white} strokeWidth={2} />}
          />
        </View>
      </Card>

      {!loading && total > 0 ? (
        <View style={styles.resultBanner}>
          <View style={styles.resultIcon}>
            <Users size={18} color={colors.baytgo} strokeWidth={2} />
          </View>
          <View style={styles.resultCopy}>
            <Text style={styles.resultText}>
              <Text style={styles.resultCount}>{total}</Text> muthowif ditemukan
              {startDate ? ' untuk tanggal Anda' : ''}
            </Text>
          </View>
        </View>
      ) : null}
    </View>
  );

  if (loading && !refreshing) {
    return (
      <View style={styles.container}>
        <SafeAreaView edges={['top']} style={styles.topBar}>
          <PressableScale onPress={() => navigation.goBack()} haptic="light" style={styles.backBtn}>
            <ArrowLeft size={22} color={colors.baytgo} strokeWidth={2} />
          </PressableScale>
          <Text style={styles.pageTitle}>Cari Muthowif</Text>
        </SafeAreaView>
        <SkeletonList count={4} style={styles.skeleton} />
      </View>
    );
  }

  return (
    <View style={styles.container}>
      <SafeAreaView edges={['top']} style={styles.topBar}>
        <PressableScale onPress={() => navigation.goBack()} haptic="light" style={styles.backBtn}>
          <ArrowLeft size={22} color={colors.baytgo} strokeWidth={2} />
        </PressableScale>
        <Text style={styles.pageTitle}>Cari Muthowif</Text>
      </SafeAreaView>

      {error && items.length === 0 ? (
        <ErrorState description={error} onRetry={() => loadPage(1)} />
      ) : (
        <FlashList
          data={items}
          keyExtractor={(item) => String(item.id)}
          renderItem={renderItem}
          estimatedItemSize={320}
          contentContainerStyle={styles.list}
          showsVerticalScrollIndicator={false}
          refreshing={refreshing}
          onRefresh={() => loadPage(1, { refresh: true })}
          onEndReached={handleLoadMore}
          onEndReachedThreshold={0.35}
          ListHeaderComponent={listHeader}
          ListEmptyComponent={
            error ? (
              <ErrorState description={error} onRetry={() => loadPage(1)} />
            ) : (
              <EmptyState
                variant="search"
                title="Tidak ada muthowif ditemukan"
                description="Coba ubah tanggal perjalanan atau kata kunci pencarian."
              />
            )
          }
          ListFooterComponent={
            loadingMore ? (
              <View style={styles.footerLoader}>
                <ActivityIndicator color={colors.baytgo} />
                <Text style={styles.footerText}>Memuat lebih banyak...</Text>
              </View>
            ) : (
              <View style={styles.listBottom} />
            )
          }
        />
      )}
    </View>
  );
}

const styles = StyleSheet.create({
  container: { flex: 1, backgroundColor: colors.background },
  topBar: {
    flexDirection: 'row',
    alignItems: 'center',
    gap: spacing.md,
    paddingHorizontal: spacing.lg,
    paddingBottom: spacing.md,
    backgroundColor: colors.card,
    borderBottomWidth: 1,
    borderBottomColor: colors.border,
  },
  backBtn: {
    width: 40,
    height: 40,
    borderRadius: radius.sm,
    backgroundColor: colors.background,
    alignItems: 'center',
    justifyContent: 'center',
    borderWidth: 1,
    borderColor: colors.border,
  },
  pageTitle: {
    flex: 1,
    ...typography.subtitle,
    fontFamily: 'PlusJakartaSans_800ExtraBold',
    color: colors.baytgo,
  },
  skeleton: {
    paddingHorizontal: layout.screenPadding,
    paddingTop: spacing.md,
  },
  list: {
    paddingHorizontal: layout.screenPadding,
    paddingTop: spacing.md,
    paddingBottom: spacing['2xl'],
  },
  listHeader: { marginBottom: spacing.xs },
  searchCard: {
    marginBottom: spacing.lg,
    borderRadius: radius.md,
  },
  searchCardHead: {
    flexDirection: 'row',
    alignItems: 'center',
    gap: spacing.md,
    marginBottom: spacing.lg,
  },
  searchCardIcon: {
    width: 40,
    height: 40,
    borderRadius: radius.sm,
    backgroundColor: colors.baytgoLight,
    alignItems: 'center',
    justifyContent: 'center',
  },
  searchCardHeadText: { flex: 1 },
  searchCardTitle: {
    ...typography.body,
    fontFamily: 'PlusJakartaSans_800ExtraBold',
    color: colors.baytgo,
  },
  searchCardSub: {
    marginTop: spacing.xs,
    ...typography.small,
    fontWeight: '600',
    fontFamily: 'PlusJakartaSans_600SemiBold',
    color: colors.textSecondary,
    lineHeight: 17,
  },
  dateRow: {
    flexDirection: 'row',
    alignItems: 'center',
    gap: spacing.sm,
    marginBottom: spacing.md,
  },
  dateField: { flex: 1 },
  dateArrow: {
    width: 20,
    alignItems: 'center',
    justifyContent: 'center',
    paddingTop: spacing.lg,
  },
  searchBar: { marginBottom: spacing.md },
  searchCta: { marginTop: spacing.xs },
  resultBanner: {
    flexDirection: 'row',
    alignItems: 'center',
    gap: spacing.md,
    backgroundColor: colors.card,
    borderRadius: radius.sm,
    paddingHorizontal: spacing.lg,
    paddingVertical: spacing.md,
    marginBottom: spacing.lg,
    borderWidth: 1,
    borderColor: colors.border,
  },
  resultIcon: {
    width: 36,
    height: 36,
    borderRadius: radius.sm,
    backgroundColor: colors.baytgoLight,
    alignItems: 'center',
    justifyContent: 'center',
  },
  resultCopy: { flex: 1 },
  resultText: {
    ...typography.caption,
    fontFamily: 'PlusJakartaSans_700Bold',
    color: colors.slate700,
  },
  resultCount: {
    fontFamily: 'PlusJakartaSans_800ExtraBold',
    color: colors.baytgo,
  },
  footerLoader: {
    flexDirection: 'row',
    alignItems: 'center',
    justifyContent: 'center',
    gap: spacing.md,
    paddingVertical: spacing.xl,
  },
  footerText: {
    ...typography.caption,
    color: colors.textSecondary,
  },
  listBottom: { height: spacing.sm },
});
