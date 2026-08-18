import React, { useCallback, useEffect, useMemo, useState } from 'react';
import { ActivityIndicator, FlatList, StyleSheet, Text, View } from 'react-native';
import { SafeAreaView } from 'react-native-safe-area-context';
import { ArrowLeft, Search, ShieldCheck, Star } from 'lucide-react-native';
import DatePickerField, { parseIsoDateTime } from '../components/DatePickerField';
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
  { value: 'umrah', label: 'Pendamping Salat' },
  { value: 'other', label: 'Fotografer & Videografer' },
  { value: 'ziarah', label: 'Raudhah' },
];

function formatSlot(value) {
  if (!value) return '';
  try {
    return parseIsoDateTime(value).toLocaleString('id-ID', {
      day: '2-digit',
      month: 'short',
      year: 'numeric',
      hour: '2-digit',
      minute: '2-digit',
    });
  } catch {
    return value;
  }
}

export default function SupportCatalogScreen({ navigation, route }) {
  const { token } = useAuth();
  const initial = route.params || {};
  const initialStarts = String(initial.startsAt || initial.startsAtDate || '').trim();
  const initialCategory = initial.category || 'mobility';

  const [category, setCategory] = useState(initialCategory);
  const [q, setQ] = useState(initial.q || '');
  const [startsAt, setStartsAt] = useState(initialStarts);
  const [applied, setApplied] = useState(() => (
    initialStarts
      ? {
          category: initialCategory,
          q: String(initial.q || '').trim(),
          startsAt: initialStarts,
        }
      : null
  ));
  const [categories, setCategories] = useState(DEFAULT_CATEGORIES);
  const [items, setItems] = useState([]);
  const [page, setPage] = useState(1);
  const [lastPage, setLastPage] = useState(1);
  const [loading, setLoading] = useState(Boolean(initialStarts));
  const [loadingMore, setLoadingMore] = useState(false);
  const [error, setError] = useState(null);
  const [stats, setStats] = useState(null);

  useEffect(() => {
    const next = route.params || {};
    if (next.category) setCategory(next.category);
    if (next.q != null) setQ(next.q || '');
    const nextStarts = String(next.startsAt ?? next.startsAtDate ?? '').trim();
    if (next.startsAt == null && next.startsAtDate == null) return;
    setStartsAt(nextStarts);
    if (nextStarts) {
      setApplied({
        category: next.category || 'mobility',
        q: String(next.q || '').trim(),
        startsAt: nextStarts,
      });
    }
  }, [route.params?.category, route.params?.q, route.params?.startsAt, route.params?.startsAtDate]);

  const today = useMemo(() => {
    const d = new Date();
    d.setHours(0, 0, 0, 0);
    return d;
  }, []);

  const hasSearch = Boolean(applied?.startsAt);

  const applyMeta = (data) => {
    if (Array.isArray(data.categories) && data.categories.length) {
      setCategories(data.categories);
    }
    if (data.catalog_stats) setStats(data.catalog_stats);
  };

  useEffect(() => {
    let cancelled = false;
    fetchSupportPackages({ token, category: category || 'mobility' })
      .then((data) => {
        if (!cancelled) applyMeta(data);
      })
      .catch(() => {});
    return () => { cancelled = true; };
  }, [token]);

  const loadPage = useCallback(async (pageNum, { append = false } = {}) => {
    const searchStart = String(applied?.startsAt || '').trim();
    if (!searchStart || !applied?.category) {
      setItems([]);
      setLoading(false);
      return;
    }

    if (append) setLoadingMore(true);
    else setLoading(true);

    try {
      const data = await fetchSupportPackages({
        token,
        category: applied.category,
        startsAt: searchStart,
        q: String(applied.q || '').trim(),
        page: pageNum,
      });

      applyMeta(data);
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
  }, [token, applied]);

  useEffect(() => {
    if (!applied?.startsAt) {
      setItems([]);
      setError(null);
      setLoading(false);
      return;
    }
    loadPage(1);
  }, [applied, loadPage]);

  const handleSearch = () => {
    const nextStart = String(startsAt || '').trim();
    if (!nextStart) {
      setApplied(null);
      return;
    }
    setApplied({
      category,
      q: String(q || '').trim(),
      startsAt: nextStart,
    });
  };

  const handleCategory = (value) => {
    setCategory(value);
    if (applied?.startsAt) {
      setApplied((prev) => ({ ...prev, category: value }));
    }
  };

  const openDetail = (item) => {
    navigation.navigate('SupportPackageDetail', {
      id: item.id,
      startsAt: applied?.startsAt || startsAt || undefined,
      packagePreview: item,
    });
  };

  const slotLabel = applied?.startsAt ? formatSlot(applied.startsAt) : '';
  const categoryLabel = categories.find((c) => c.value === (applied?.category || category))?.label;

  const listFooter = (
    <View>
      {loadingMore ? <ActivityIndicator color={colors.baytgo} style={styles.moreLoader} /> : null}
      <View style={styles.trustStrip}>
        <ShieldCheck size={16} color={colors.baytgo} strokeWidth={2} />
        <Text style={styles.trustText}>
          Semua muthowif telah diverifikasi oleh BaytGo untuk keamanan dan kenyamanan Anda.
        </Text>
      </View>
    </View>
  );

  return (
    <SafeAreaView style={styles.safe} edges={['top']}>
      <View style={styles.header}>
        <PressableScale onPress={() => navigation.goBack()} style={styles.backBtn} haptic="light">
          <ArrowLeft size={20} color={colors.baytgo} strokeWidth={2.2} />
        </PressableScale>
        <View style={styles.flex}>
          <Text style={styles.title}>Layanan Pendukung</Text>
          <Text style={styles.subtitle}>
            Pilih tanggal & jam dulu — kami hanya menampilkan muthowif yang jadwalnya kosong di hari itu.
          </Text>
        </View>
      </View>

      <View style={styles.filters}>
        <ScrollChips
          categories={categories}
          category={category}
          onSelect={handleCategory}
        />
        <DatePickerField
          label="Tanggal & jam mulai"
          mode="datetime"
          value={startsAt}
          onChange={setStartsAt}
          placeholder="Pilih tanggal & jam"
          minimumDate={today}
          clearable
          onClear={() => setStartsAt('')}
        />
        <Text style={styles.hint}>
          Ketersediaan dicek per hari kalender agar tidak bentrok dengan trip atau layanan lain.
        </Text>
        <SearchBar
          value={q}
          onChangeText={setQ}
          placeholder="Cari layanan atau nama muthowif…"
        />
        <Button
          label="Cari"
          onPress={handleSearch}
          icon={<Search size={16} color={colors.white} />}
        />
        {stats ? (
          <Text style={styles.statsText}>
            {stats.packages} paket · {stats.muthowifs} muthowif · rating {stats.avg_rating || 0}
          </Text>
        ) : null}
        {hasSearch && slotLabel ? (
          <View style={styles.slotBanner}>
            <Text style={styles.slotText}>
              Ketersediaan: {slotLabel}
              {' · '}Hanya muthowif yang tersedia di hari ini
              {categoryLabel ? ` · ${categoryLabel}` : ''}
            </Text>
          </View>
        ) : null}
      </View>

      {loading ? <SkeletonList count={4} /> : null}
      {!loading && error ? <ErrorState description={error} onRetry={handleSearch} /> : null}

      {!loading && !error && !hasSearch ? (
        <View style={styles.emptyWrap}>
          <EmptyState
            variant="schedule"
            title="Pilih tanggal & jam layanan"
            description="Isi waktu mulai di atas, lalu tekan Cari untuk melihat paket yang tersedia."
          />
          {listFooter}
        </View>
      ) : null}

      {!loading && !error && hasSearch && items.length === 0 ? (
        <View style={styles.emptyWrap}>
          <EmptyState
            variant="package"
            title="Tidak ada layanan tersedia di jadwal ini"
            description="Coba tanggal atau jam lain, atau ubah filter kategori."
          />
          {listFooter}
        </View>
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
          ListFooterComponent={listFooter}
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
  subtitle: { ...typography.small, color: colors.textMuted, marginTop: 2 },
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
  hint: {
    ...typography.small,
    color: colors.textMuted,
    lineHeight: 18,
    marginTop: -spacing.xs,
  },
  statsText: {
    ...typography.small,
    color: colors.textMuted,
  },
  slotBanner: {
    backgroundColor: colors.baytgoLight,
    borderRadius: radius.sm,
    paddingHorizontal: spacing.md,
    paddingVertical: spacing.sm,
  },
  slotText: {
    ...typography.small,
    color: colors.baytgo,
    fontFamily: 'PlusJakartaSans_600SemiBold',
    lineHeight: 18,
  },
  emptyWrap: { flex: 1, paddingHorizontal: layout.screenPadding },
  list: {
    paddingHorizontal: layout.screenPadding,
    paddingBottom: spacing['2xl'],
    gap: spacing.md,
  },
  moreLoader: { marginVertical: spacing.lg },
  trustStrip: {
    flexDirection: 'row',
    alignItems: 'center',
    gap: spacing.sm,
    marginTop: spacing.lg,
    marginBottom: spacing['2xl'],
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
