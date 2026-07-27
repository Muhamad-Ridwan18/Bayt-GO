import React, { useCallback, useEffect, useMemo, useState } from 'react';
import { ActivityIndicator, FlatList, StyleSheet, Text, View } from 'react-native';
import { SafeAreaView } from 'react-native-safe-area-context';
import { ArrowLeft, Search, Star } from 'lucide-react-native';
import DatePickerField from '../components/DatePickerField';
import { fetchSupportPackages } from '../api/supportCatalog';
import { useAuth } from '../context/AuthContext';
import AppImage from '../ui/AppImage';
import Button from '../ui/Button';
import Card from '../ui/Card';
import EmptyState from '../ui/EmptyState';
import ErrorState from '../ui/ErrorState';
import FilterChip from '../ui/FilterChip';
import PressableScale from '../ui/PressableScale';
import SearchBar from '../ui/SearchBar';
import { SkeletonList } from '../ui/Skeleton';
import { colors, layout, radius, spacing, typography } from '../theme/tokens';
import { formatIdr } from '../utils/format';

const DEFAULT_CATEGORIES = [
  { value: 'mobility', label: 'Kursi Roda' },
  { value: 'umrah', label: 'Umrah' },
  { value: 'ziarah', label: 'Ziarah' },
  { value: 'other', label: 'Lainnya' },
];

export default function SupportCatalogScreen({ navigation, route }) {
  const { token } = useAuth();
  const initial = route.params || {};

  const [category, setCategory] = useState(initial.category || 'mobility');
  const [q, setQ] = useState(initial.q || '');
  const [startsAtDate, setStartsAtDate] = useState(initial.startsAtDate || '');
  const [categories, setCategories] = useState(DEFAULT_CATEGORIES);
  const [items, setItems] = useState([]);
  const [hasSearch, setHasSearch] = useState(false);
  const [page, setPage] = useState(1);
  const [lastPage, setLastPage] = useState(1);
  const [loading, setLoading] = useState(false);
  const [loadingMore, setLoadingMore] = useState(false);
  const [error, setError] = useState(null);
  const [stats, setStats] = useState(null);

  const today = useMemo(() => {
    const d = new Date();
    d.setHours(0, 0, 0, 0);
    return d;
  }, []);

  const startsAtParam = startsAtDate ? `${startsAtDate}T09:00:00` : '';

  const loadPage = useCallback(async (pageNum, { append = false } = {}) => {
    if (!category) return;
    if (append) setLoadingMore(true);
    else setLoading(true);

    try {
      const data = await fetchSupportPackages({
        token,
        category,
        startsAt: startsAtParam,
        q: String(q || '').trim(),
        page: pageNum,
      });

      if (Array.isArray(data.categories) && data.categories.length) {
        setCategories(data.categories);
      }
      setStats(data.catalog_stats || null);
      setHasSearch(Boolean(data.filters?.has_search));
      setItems((prev) => (append ? [...prev, ...(data.data || [])] : data.data || []));
      setPage(data.meta?.current_page || pageNum);
      setLastPage(data.meta?.last_page || 1);
      setError(null);
    } catch (err) {
      setError(err.message || 'Gagal memuat layanan pendukung');
      if (!append) setItems([]);
    } finally {
      setLoading(false);
      setLoadingMore(false);
    }
  }, [token, category, startsAtParam, q]);

  useEffect(() => {
    loadPage(1);
  }, [loadPage]);

  const handleSearch = () => loadPage(1);

  const openDetail = (item) => {
    navigation.navigate('SupportPackageDetail', {
      id: item.id,
      startsAtDate: startsAtDate || undefined,
      packagePreview: item,
    });
  };

  return (
    <SafeAreaView style={styles.safe} edges={['top']}>
      <View style={styles.header}>
        <PressableScale onPress={() => navigation.goBack()} style={styles.backBtn} haptic="light">
          <ArrowLeft size={20} color={colors.baytgo} strokeWidth={2.2} />
        </PressableScale>
        <View style={styles.flex}>
          <Text style={styles.title}>Layanan Pendukung</Text>
          <Text style={styles.subtitle}>Kursi roda, umrah, ziarah, dll.</Text>
        </View>
      </View>

      <View style={styles.filters}>
        <ScrollChips
          categories={categories}
          category={category}
          onSelect={(value) => setCategory(value)}
        />
        <DatePickerField
          label="Tanggal layanan"
          value={startsAtDate}
          onChange={setStartsAtDate}
          placeholder="Pilih tanggal"
          minimumDate={today}
        />
        <SearchBar
          value={q}
          onChangeText={setQ}
          placeholder="Cari paket / muthowif"
        />
        <Button label="Cari paket tersedia" onPress={handleSearch} icon={<Search size={16} color={colors.white} />} />
        {stats ? (
          <Text style={styles.statsText}>
            {stats.packages} paket · {stats.muthowifs} muthowif · rating {stats.avg_rating || 0}
          </Text>
        ) : null}
      </View>

      {loading ? <SkeletonList count={4} /> : null}
      {!loading && error ? <ErrorState description={error} onRetry={handleSearch} /> : null}

      {!loading && !error && !hasSearch ? (
        <EmptyState
          variant="package"
          title="Pilih tanggal dulu"
          description="Isi tanggal layanan lalu cari untuk melihat paket yang tersedia."
        />
      ) : null}

      {!loading && !error && hasSearch && items.length === 0 ? (
        <EmptyState
          variant="package"
          title="Tidak ada paket"
          description="Coba tanggal atau kategori lain."
        />
      ) : null}

      {!loading && !error && items.length > 0 ? (
        <FlatList
          data={items}
          keyExtractor={(item) => String(item.id)}
          contentContainerStyle={styles.list}
          onEndReached={() => {
            if (!loadingMore && page < lastPage) loadPage(page + 1, { append: true });
          }}
          onEndReachedThreshold={0.4}
          ListFooterComponent={loadingMore ? <ActivityIndicator color={colors.baytgo} style={{ marginVertical: 16 }} /> : null}
          renderItem={({ item }) => (
            <PressableScale onPress={() => openDetail(item)} haptic="light">
              <Card style={styles.card}>
                <View style={styles.cardTop}>
                  <AppImage uri={item.muthowif?.avatar} name={item.muthowif?.name} style={styles.avatar} />
                  <View style={styles.flex}>
                    <Text style={styles.pkgName} numberOfLines={2}>{item.name}</Text>
                    <Text style={styles.muthowifName}>{item.muthowif?.name}</Text>
                    <View style={styles.metaRow}>
                      <Star size={12} color={colors.goldMuted} fill={colors.goldMuted} />
                      <Text style={styles.metaText}>
                        {item.muthowif?.average_rating || 0} · {item.category_label}
                      </Text>
                    </View>
                  </View>
                  <Text style={styles.price}>{formatIdr(item.price)}</Text>
                </View>
                {item.description ? (
                  <Text style={styles.desc} numberOfLines={2}>{item.description}</Text>
                ) : null}
              </Card>
            </PressableScale>
          )}
        />
      ) : null}
    </SafeAreaView>
  );
}

function ScrollChips({ categories, category, onSelect }) {
  return (
    <View style={styles.chipRow}>
      {categories.map((c) => (
        <FilterChip
          key={c.value}
          label={c.label}
          active={category === c.value}
          onPress={() => onSelect(c.value)}
        />
      ))}
    </View>
  );
}

const styles = StyleSheet.create({
  safe: { flex: 1, backgroundColor: colors.background },
  flex: { flex: 1 },
  header: {
    flexDirection: 'row',
    alignItems: 'center',
    gap: spacing.sm,
    paddingHorizontal: layout.screenPadding,
    paddingBottom: spacing.md,
  },
  backBtn: {
    width: 44,
    height: 44,
    borderRadius: radius.sm,
    backgroundColor: colors.card,
    alignItems: 'center',
    justifyContent: 'center',
  },
  title: {
    ...typography.subtitle,
    fontFamily: 'PlusJakartaSans_800ExtraBold',
    color: colors.baytgo,
  },
  subtitle: { ...typography.small, color: colors.textMuted },
  filters: {
    paddingHorizontal: layout.screenPadding,
    gap: spacing.sm,
    marginBottom: spacing.md,
  },
  chipRow: {
    flexDirection: 'row',
    flexWrap: 'wrap',
    gap: spacing.sm,
  },
  statsText: {
    ...typography.small,
    color: colors.textMuted,
  },
  list: {
    paddingHorizontal: layout.screenPadding,
    paddingBottom: spacing.xxl,
    gap: spacing.md,
  },
  card: { gap: spacing.sm },
  cardTop: { flexDirection: 'row', gap: spacing.md, alignItems: 'flex-start' },
  avatar: { width: 48, height: 48, borderRadius: 24, backgroundColor: colors.border },
  pkgName: {
    ...typography.caption,
    fontWeight: '800',
    fontFamily: 'PlusJakartaSans_800ExtraBold',
    color: colors.textPrimary,
  },
  muthowifName: { ...typography.small, color: colors.textSecondary, marginTop: 2 },
  metaRow: { flexDirection: 'row', alignItems: 'center', gap: 4, marginTop: 4 },
  metaText: { ...typography.small, color: colors.textMuted },
  price: {
    ...typography.caption,
    fontWeight: '800',
    color: colors.baytgo,
  },
  desc: { ...typography.small, color: colors.textSecondary, lineHeight: 18 },
});
