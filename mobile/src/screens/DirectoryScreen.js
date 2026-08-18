import React, { useCallback, useEffect, useMemo, useState } from 'react';
import { ActivityIndicator, StyleSheet, Text, View } from 'react-native';
import { FlashList } from '@shopify/flash-list';
import { SafeAreaView } from 'react-native-safe-area-context';
import { ArrowLeft, ArrowRight, Clock, Compass, Search, ShieldCheck, Users } from 'lucide-react-native';
import DatePickerField, { parseIsoDate } from '../components/DatePickerField';
import MuthowifListingCard from '../components/MuthowifListingCard';
import { fetchDirectory } from '../api/directory';
import { useAuth } from '../context/AuthContext';
import Button from '../ui/Button';
import Card from '../ui/Card';
import EmptyState from '../ui/EmptyState';
import ErrorState from '../ui/ErrorState';
import FilterChip from '../ui/FilterChip';
import PressableScale from '../ui/PressableScale';
import SearchBar from '../ui/SearchBar';
import { SkeletonList } from '../ui/Skeleton';
import { colors, layout, radius, spacing, typography } from '../theme/tokens';
import { useLocale } from '../utils/locale';

export default function DirectoryScreen({ navigation, route }) {
  const { token } = useAuth();
  const locale = useLocale(); const isEn = locale === 'en';
  const initial = route.params || {};

  const initialSort = initial.sort === 'newest' ? 'newest' : 'recommended';
  const initialStart = String(initial.startDate || '').trim();

  const [q, setQ] = useState(initial.q || '');
  const [startDate, setStartDate] = useState(initial.startDate || '');
  const [endDate, setEndDate] = useState(initial.endDate || '');
  const [sort, setSort] = useState(initialSort);
  const [applied, setApplied] = useState(() => (
    initialStart
      ? {
          q: String(initial.q || '').trim(),
          startDate: initialStart,
          endDate: String(initial.endDate || '').trim(),
          sort: initialSort,
        }
      : null
  ));
  const [items, setItems] = useState([]);
  const [page, setPage] = useState(1);
  const [lastPage, setLastPage] = useState(1);
  const [total, setTotal] = useState(0);
  const [loading, setLoading] = useState(Boolean(initialStart));
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

  const loadPage = useCallback(async (pageNum, { append = false, refresh = false } = {}) => {
    const searchStart = String(applied?.startDate || '').trim();
    if (!searchStart) {
      setItems([]);
      setTotal(0);
      setLoading(false);
      setRefreshing(false);
      return;
    }

    if (append) setLoadingMore(true);
    else if (refresh) setRefreshing(true);
    else setLoading(true);

    try {
      const data = await fetchDirectory({
        token,
        q: String(applied?.q || '').trim(),
        startDate: searchStart,
        endDate: String(applied?.endDate || '').trim(),
        sort: applied?.sort || 'recommended',
        page: pageNum,
      });

      setItems((prev) => (append ? [...prev, ...(data.data || [])] : data.data || []));
      setPage(data.current_page || pageNum);
      setLastPage(data.last_page || 1);
      setTotal(data.total || 0);
      setError(null);
    } catch (err) {
      setError(err.message || (isEn ? 'Failed to load directory' : 'Gagal memuat direktori'));
      if (!append) setItems([]);
    } finally {
      setLoading(false);
      setLoadingMore(false);
      setRefreshing(false);
    }
  }, [token, applied]);

  useEffect(() => {
    if (!applied?.startDate) {
      setItems([]);
      setTotal(0);
      setError(null);
      setLoading(false);
      return;
    }
    loadPage(1);
  }, [applied, loadPage]);

  const handleSearch = () => {
    const nextStart = String(startDate || '').trim();
    if (!nextStart) {
      setApplied(null);
      return;
    }
    setApplied({
      q: String(q || '').trim(),
      startDate: nextStart,
      endDate: String(endDate || '').trim(),
      sort,
    });
  };

  const handleSort = (next) => {
    setSort(next);
    if (applied?.startDate) {
      setApplied((prev) => ({ ...prev, sort: next }));
    }
  };

  const handleLoadMore = () => {
    if (loadingMore || page >= lastPage) return;
    loadPage(page + 1, { append: true });
  };

  const resultStart = applied?.startDate || startDate;
  const resultEnd = applied?.endDate || endDate;

  const navParams = (item) => ({
    id: item.id,
    startDate: String(resultStart || '').trim() || undefined,
    endDate: String(resultEnd || '').trim() || undefined,
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
  ), [resultStart, resultEnd, navigation]);

  const listHeader = (
    <View style={styles.listHeader}>
      <Card style={styles.searchCard} padding={spacing.xl} elevated>
        <View style={styles.searchCardHead}>
          <View style={styles.searchCardIcon}>
            <Compass size={18} color={colors.baytgo} strokeWidth={2} />
          </View>
          <View style={styles.searchCardHeadText}>
            <Text style={styles.searchCardTitle}>{isEn ? 'Check availability' : 'Cari ketersediaan'}</Text>
            <Text style={styles.searchCardSub}>{isEn ? 'Select travel dates and filter by name (optional).' : 'Pilih tanggal perjalanan dan filter nama (opsional).'}</Text>
          </View>
        </View>

        <View style={styles.dateRow}>
          <View style={styles.dateField}>
            <DatePickerField
              label={isEn ? 'Departure' : 'Berangkat'}
              value={startDate}
              onChange={handleStartDateChange}
              placeholder={isEn ? 'Select' : 'Pilih'}
              minimumDate={today}
              variant="chip"
            />
          </View>
          <View style={styles.dateArrow}>
            <ArrowRight size={16} color={colors.textMuted} strokeWidth={2} />
          </View>
          <View style={styles.dateField}>
            <DatePickerField
              label={isEn ? 'Return' : 'Pulang'}
              value={endDate}
              onChange={setEndDate}
              placeholder={isEn ? 'Optional' : 'Opsional'}
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
          placeholder={isEn ? 'Optional — filter by name' : 'Opsional — filter nama'}
          style={styles.searchBar}
        />

        <View style={styles.sortRow}>
          <FilterChip
            label={isEn ? 'Recommended' : 'Rekomendasi'}
            icon={Compass}
            active={sort === 'recommended'}
            onPress={() => handleSort('recommended')}
          />
          <FilterChip
            label={isEn ? 'Newest' : 'Terbaru'}
            icon={Clock}
            active={sort === 'newest'}
            onPress={() => handleSort('newest')}
          />
        </View>

        <View style={styles.searchCta}>
          <Button
            label={isEn ? 'Search muthowif' : 'Cari muthowif'}
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
              <Text style={styles.resultCount}>{total}</Text> {isEn ? 'Muthowif available' : 'Muthowif tersedia'}
              {applied?.startDate ? (isEn ? ' for your dates' : ' untuk tanggal Anda') : ''}
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
          <Text style={styles.pageTitle}>{isEn ? 'Find Muthowif' : 'Cari Muthowif'}</Text>
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
        <Text style={styles.pageTitle}>{isEn ? 'Find Muthowif' : 'Cari Muthowif'}</Text>
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
            ) : !applied?.startDate ? (
              <EmptyState
                variant="schedule"
                title={isEn ? 'Select a start date (and end date if more than one day), then tap Search.' : 'Pilih tanggal mulai (dan selesai jika lebih dari satu hari), lalu tekan Cari.'}
                description={isEn ? 'The muthowif list only appears after dates are filled — so you don\'t waste time on unavailable ones.' : 'Daftar muthowif hanya muncul setelah tanggal diisi — supaya Anda tidak membuang waktu pada yang tidak available.'}
              />
            ) : (
              <EmptyState
                variant="search"
                title={isEn ? 'No matching muthowif for this date range' : 'Belum ada muthowif yang cocok pada rentang ini'}
                description={isEn ? 'All are on leave or fully booked. Try changing the dates or name keyword.' : 'Semua sedang libur atau jadwal sudah terisi. Coba ubah tanggal atau kata kunci nama.'}
                actionLabel={isEn ? 'Search muthowif' : 'Cari muthowif'}
                onAction={handleSearch}
              />
            )
          }
          ListFooterComponent={
            <View>
              {loadingMore ? (
                <View style={styles.footerLoader}>
                  <ActivityIndicator color={colors.baytgo} />
                  <Text style={styles.footerText}>{isEn ? 'Loading more...' : 'Memuat lebih banyak...'}</Text>
                </View>
              ) : null}
              <View style={styles.trustStrip}>
                <ShieldCheck size={16} color={colors.baytgo} strokeWidth={2} />
                <Text style={styles.trustText}>
                  {isEn ? 'Identity and services verified by the BaytGo team.' : 'Identitas dan layanan diverifikasi tim BaytGo.'}
                </Text>
              </View>
            </View>
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
  sortRow: {
    flexDirection: 'row',
    flexWrap: 'wrap',
    gap: spacing.sm,
    marginBottom: spacing.md,
  },
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
  trustStrip: {
    flexDirection: 'row',
    alignItems: 'center',
    gap: spacing.sm,
    marginTop: spacing.lg,
    marginBottom: spacing.md,
    paddingHorizontal: spacing.md,
    paddingVertical: spacing.md,
    borderRadius: radius.sm,
    backgroundColor: colors.baytgoLight,
  },
  trustText: {
    flex: 1,
    ...typography.small,
    color: colors.baytgo,
    fontFamily: 'PlusJakartaSans_600SemiBold',
    lineHeight: 18,
  },
});
