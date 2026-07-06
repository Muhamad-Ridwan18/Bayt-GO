import React, { useCallback, useEffect, useMemo, useState } from 'react';
import {
  View,
  Text,
  StyleSheet,
  FlatList,
  TextInput,
  TouchableOpacity,
  ActivityIndicator,
  RefreshControl,
  Image,
  Dimensions,
} from 'react-native';
import { SafeAreaView } from 'react-native-safe-area-context';
import { LinearGradient } from 'expo-linear-gradient';
import { Ionicons } from '@expo/vector-icons';
import DatePickerField, { parseIsoDate } from '../components/DatePickerField';
import { fetchDirectory } from '../api/directory';
import { useAuth } from '../context/AuthContext';
import { colors } from '../theme/colors';
import { formatIdr } from '../utils/format';
import { resolveMediaUrl } from '../utils/mediaUrl';

const { width: SCREEN_W } = Dimensions.get('window');

const QUICK_FILTERS = [
  { label: 'Makkah', query: 'Makkah', icon: 'location' },
  { label: 'Madinah', query: 'Madinah', icon: 'moon' },
  { label: 'Bahasa ID', query: 'Indonesia', icon: 'chatbubbles' },
];

function formatChipDate(iso) {
  if (!iso) return '';
  try {
    return new Date(`${iso}T12:00:00`).toLocaleDateString('id-ID', { day: 'numeric', month: 'short' });
  } catch {
    return iso;
  }
}

function DirectoryCard({ item, onPress }) {
  const langs = (item.languages || []).slice(0, 2).join(' · ');

  return (
    <TouchableOpacity style={styles.card} onPress={onPress} activeOpacity={0.92}>
      <View style={styles.cardPhotoWrap}>
        <Image source={{ uri: resolveMediaUrl(item.avatar) }} style={styles.cardPhoto} />
        <LinearGradient
          colors={['transparent', 'rgba(15,23,42,0.55)']}
          style={styles.cardPhotoOverlay}
        />
        <View style={styles.verifiedBadge}>
          <Ionicons name="shield-checkmark" size={12} color={colors.white} />
          <Text style={styles.verifiedText}>Terverifikasi</Text>
        </View>
        {item.location ? (
          <View style={styles.locationOnPhoto}>
            <Ionicons name="location" size={11} color={colors.white} />
            <Text style={styles.locationOnPhotoText} numberOfLines={1}>{item.location}</Text>
          </View>
        ) : null}
      </View>

      <View style={styles.cardBody}>
        <View style={styles.cardTopRow}>
          <Text style={styles.cardName} numberOfLines={1}>{item.name}</Text>
          <View style={styles.ratingBadge}>
            <Ionicons name="star" size={12} color="#F59E0B" />
            <Text style={styles.ratingText}>{item.rating ?? '—'}</Text>
            <Text style={styles.reviewText}>({item.reviews ?? 0})</Text>
          </View>
        </View>

        {langs ? <Text style={styles.cardLangs} numberOfLines={1}>{langs}</Text> : null}

        <View style={styles.cardFooter}>
          <View>
            <Text style={styles.priceLabel}>Mulai dari</Text>
            <Text style={styles.priceValue}>{formatIdr(item.start_price)}<Text style={styles.priceUnit}> /hari</Text></Text>
          </View>
          <View style={styles.bookBtn}>
            <Text style={styles.bookBtnText}>Lihat</Text>
            <Ionicons name="arrow-forward" size={14} color={colors.white} />
          </View>
        </View>
      </View>
    </TouchableOpacity>
  );
}

function EmptyState({ error, onRetry }) {
  return (
    <View style={styles.empty}>
      <View style={styles.emptyIcon}>
        <Ionicons name="search-outline" size={36} color={colors.slate400} />
      </View>
      <Text style={styles.emptyTitle}>
        {error ? 'Gagal memuat data' : 'Tidak ada muthowif ditemukan'}
      </Text>
      <Text style={styles.emptyText}>
        {error || 'Coba ubah tanggal perjalanan atau kata kunci pencarian.'}
      </Text>
      {error ? (
        <TouchableOpacity style={styles.emptyBtn} onPress={onRetry}>
          <Text style={styles.emptyBtnText}>Coba lagi</Text>
        </TouchableOpacity>
      ) : null}
    </View>
  );
}

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
  const [filtersExpanded, setFiltersExpanded] = useState(
    !initial.startDate && !initial.q,
  );

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

  const activeFilters = useMemo(() => {
    const chips = [];
    if (q.trim()) chips.push({ key: 'q', label: `"${q.trim()}"` });
    if (startDate) chips.push({ key: 'start', label: `Mulai ${formatChipDate(startDate)}` });
    if (endDate) chips.push({ key: 'end', label: `Selesai ${formatChipDate(endDate)}` });
    return chips;
  }, [q, startDate, endDate]);

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

  const handleSearch = () => {
    setFiltersExpanded(false);
    loadPage(1);
  };

  const applyQuickFilter = (query) => {
    setQ(query);
    setFiltersExpanded(false);
    loadPage(1, { overrides: { q: query } });
  };

  const clearFilter = (key) => {
    const overrides = {
      q: key === 'q' ? '' : q,
      startDate: key === 'start' ? '' : startDate,
      endDate: key === 'end' ? '' : endDate,
    };
    if (key === 'q') setQ('');
    if (key === 'start') setStartDate('');
    if (key === 'end') setEndDate('');
    loadPage(1, { overrides });
  };

  const handleLoadMore = () => {
    if (loadingMore || page >= lastPage) return;
    loadPage(page + 1, { append: true });
  };

  const openDetail = (item) => {
    navigation.navigate('MuthowifDetail', {
      id: item.id,
      startDate: startDate.trim() || undefined,
      endDate: endDate.trim() || undefined,
    });
  };

  const renderHeader = () => (
    <View style={styles.listHeader}>
      {!loading && total > 0 ? (
        <View style={styles.resultBanner}>
          <Ionicons name="people" size={16} color={colors.baytgo} />
          <Text style={styles.resultText}>
            <Text style={styles.resultCount}>{total}</Text> muthowif tersedia
            {startDate ? ' untuk tanggal Anda' : ''}
          </Text>
        </View>
      ) : null}
    </View>
  );

  return (
    <View style={styles.container}>
      <LinearGradient colors={['#0A221E', '#1A3D34', '#2D6A5A']} style={styles.hero}>
        <SafeAreaView edges={['top']}>
          <View style={styles.heroRow}>
            <TouchableOpacity style={styles.backBtn} onPress={() => navigation.goBack()} hitSlop={8}>
              <Ionicons name="arrow-back" size={22} color={colors.white} />
            </TouchableOpacity>
            <View style={styles.heroTitles}>
              <Text style={styles.heroTitle}>Cari Muthowif</Text>
              <Text style={styles.heroSub}>Temukan pendamping ibadah terpercaya</Text>
            </View>
            <TouchableOpacity
              style={styles.filterToggle}
              onPress={() => setFiltersExpanded((v) => !v)}
            >
              <Ionicons name={filtersExpanded ? 'options' : 'options-outline'} size={20} color={colors.white} />
            </TouchableOpacity>
          </View>
        </SafeAreaView>
      </LinearGradient>

      <View style={styles.filterCard}>
        <View style={styles.searchRow}>
          <Ionicons name="search" size={20} color={colors.baytgo} />
          <TextInput
            style={styles.input}
            value={q}
            onChangeText={setQ}
            placeholder="Nama muthowif..."
            placeholderTextColor={colors.slate400}
            returnKeyType="search"
            onSubmitEditing={handleSearch}
          />
          {q ? (
            <TouchableOpacity onPress={() => setQ('')} hitSlop={8}>
              <Ionicons name="close-circle" size={18} color={colors.slate400} />
            </TouchableOpacity>
          ) : null}
        </View>

        {filtersExpanded ? (
          <>
            <View style={styles.dateRow}>
              <View style={styles.dateField}>
                <DatePickerField
                  label="Tanggal mulai"
                  value={startDate}
                  onChange={handleStartDateChange}
                  placeholder="Pilih tanggal"
                  minimumDate={today}
                  variant="soft"
                />
              </View>
              <View style={styles.dateField}>
                <DatePickerField
                  label="Tanggal selesai"
                  value={endDate}
                  onChange={setEndDate}
                  placeholder="Opsional"
                  minimumDate={endMinDate}
                  maximumDate={endMaxDate}
                  clearable
                  onClear={() => setEndDate('')}
                  variant="soft"
                />
              </View>
            </View>
            <ScrollableChips
              items={QUICK_FILTERS}
              onSelect={(item) => applyQuickFilter(item.query)}
            />
          </>
        ) : null}

        {activeFilters.length > 0 ? (
          <View style={styles.activeFilters}>
            {activeFilters.map((chip) => (
              <TouchableOpacity
                key={chip.key}
                style={styles.activeChip}
                onPress={() => clearFilter(chip.key)}
              >
                <Text style={styles.activeChipText}>{chip.label}</Text>
                <Ionicons name="close" size={12} color={colors.baytgo} />
              </TouchableOpacity>
            ))}
          </View>
        ) : null}

        <TouchableOpacity style={styles.searchBtn} onPress={handleSearch} activeOpacity={0.9}>
          <LinearGradient colors={[colors.baytgo, colors.baytgoDark]} style={styles.searchGradient}>
            <Ionicons name="compass" size={18} color={colors.white} />
            <Text style={styles.searchText}>Tampilkan hasil</Text>
          </LinearGradient>
        </TouchableOpacity>
      </View>

      {loading && !refreshing ? (
        <View style={styles.loadingWrap}>
          <ActivityIndicator color={colors.baytgo} size="large" />
          <Text style={styles.loadingText}>Mencari muthowif terbaik...</Text>
        </View>
      ) : (
        <FlatList
          data={items}
          keyExtractor={(item) => String(item.id)}
          renderItem={({ item }) => <DirectoryCard item={item} onPress={() => openDetail(item)} />}
          contentContainerStyle={styles.list}
          showsVerticalScrollIndicator={false}
          refreshControl={
            <RefreshControl refreshing={refreshing} onRefresh={() => loadPage(1, { refresh: true })} tintColor={colors.baytgo} />
          }
          onEndReached={handleLoadMore}
          onEndReachedThreshold={0.35}
          ListHeaderComponent={renderHeader}
          ListEmptyComponent={<EmptyState error={error} onRetry={() => loadPage(1)} />}
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

function ScrollableChips({ items, onSelect }) {
  return (
    <View style={styles.quickRow}>
      {items.map((item) => (
        <TouchableOpacity
          key={item.label}
          style={styles.quickChip}
          onPress={() => onSelect(item)}
          activeOpacity={0.85}
        >
          <Ionicons name={item.icon} size={13} color={colors.baytgo} />
          <Text style={styles.quickChipText}>{item.label}</Text>
        </TouchableOpacity>
      ))}
    </View>
  );
}

const CARD_W = SCREEN_W - 40;

const styles = StyleSheet.create({
  container: { flex: 1, backgroundColor: colors.canvas },
  hero: {
    paddingBottom: 48,
    borderBottomLeftRadius: 24,
    borderBottomRightRadius: 24,
  },
  heroRow: {
    flexDirection: 'row',
    alignItems: 'center',
    paddingHorizontal: 16,
    paddingTop: 4,
    gap: 10,
  },
  backBtn: {
    width: 40,
    height: 40,
    borderRadius: 12,
    backgroundColor: 'rgba(255,255,255,0.12)',
    alignItems: 'center',
    justifyContent: 'center',
  },
  heroTitles: { flex: 1 },
  heroTitle: { fontSize: 20, fontWeight: '900', color: colors.white },
  heroSub: { marginTop: 2, fontSize: 12, fontWeight: '600', color: 'rgba(255,255,255,0.65)' },
  filterToggle: {
    width: 40,
    height: 40,
    borderRadius: 12,
    backgroundColor: 'rgba(255,255,255,0.12)',
    alignItems: 'center',
    justifyContent: 'center',
  },
  filterCard: {
    marginHorizontal: 20,
    marginTop: -36,
    backgroundColor: colors.white,
    borderRadius: 20,
    padding: 16,
    shadowColor: '#0F2E28',
    shadowOffset: { width: 0, height: 8 },
    shadowOpacity: 0.12,
    shadowRadius: 20,
    elevation: 8,
    borderWidth: 1,
    borderColor: colors.slate100,
    marginBottom: 8,
    zIndex: 10,
  },
  searchRow: {
    flexDirection: 'row',
    alignItems: 'center',
    gap: 10,
    backgroundColor: colors.canvas,
    borderRadius: 14,
    paddingHorizontal: 14,
    borderWidth: 1,
    borderColor: colors.slate100,
    marginBottom: 10,
  },
  input: { flex: 1, paddingVertical: 13, fontSize: 15, fontWeight: '600', color: colors.slate900 },
  dateRow: { flexDirection: 'row', gap: 10 },
  dateField: { flex: 1 },
  quickRow: { flexDirection: 'row', flexWrap: 'wrap', gap: 8, marginTop: 4, marginBottom: 4 },
  quickChip: {
    flexDirection: 'row',
    alignItems: 'center',
    gap: 5,
    backgroundColor: colors.emerald50,
    paddingHorizontal: 12,
    paddingVertical: 8,
    borderRadius: 999,
    borderWidth: 1,
    borderColor: colors.baytgoLight,
  },
  quickChipText: { fontSize: 12, fontWeight: '800', color: colors.baytgo },
  activeFilters: { flexDirection: 'row', flexWrap: 'wrap', gap: 8, marginTop: 8 },
  activeChip: {
    flexDirection: 'row',
    alignItems: 'center',
    gap: 4,
    backgroundColor: colors.baytgoLight,
    paddingHorizontal: 10,
    paddingVertical: 6,
    borderRadius: 999,
  },
  activeChipText: { fontSize: 11, fontWeight: '700', color: colors.baytgo },
  searchBtn: { borderRadius: 14, overflow: 'hidden', marginTop: 10 },
  searchGradient: {
    flexDirection: 'row',
    alignItems: 'center',
    justifyContent: 'center',
    gap: 8,
    paddingVertical: 14,
  },
  searchText: { color: colors.white, fontWeight: '800', fontSize: 15 },
  loadingWrap: { flex: 1, alignItems: 'center', justifyContent: 'center', gap: 12 },
  loadingText: { fontSize: 14, fontWeight: '600', color: colors.slate500 },
  list: { paddingHorizontal: 20, paddingTop: 8 },
  listHeader: { marginBottom: 8 },
  resultBanner: {
    flexDirection: 'row',
    alignItems: 'center',
    gap: 8,
    backgroundColor: colors.white,
    borderRadius: 12,
    paddingHorizontal: 14,
    paddingVertical: 10,
    borderWidth: 1,
    borderColor: colors.slate100,
  },
  resultText: { fontSize: 13, fontWeight: '600', color: colors.slate600 },
  resultCount: { fontWeight: '900', color: colors.baytgo },
  card: {
    width: CARD_W,
    backgroundColor: colors.white,
    borderRadius: 20,
    marginBottom: 16,
    overflow: 'hidden',
    borderWidth: 1,
    borderColor: colors.slate100,
    shadowColor: '#000',
    shadowOffset: { width: 0, height: 4 },
    shadowOpacity: 0.07,
    shadowRadius: 12,
    elevation: 4,
  },
  cardPhotoWrap: { height: CARD_W * 0.52, backgroundColor: colors.slate100 },
  cardPhoto: { width: '100%', height: '100%' },
  cardPhotoOverlay: { ...StyleSheet.absoluteFillObject },
  verifiedBadge: {
    position: 'absolute',
    top: 12,
    left: 12,
    flexDirection: 'row',
    alignItems: 'center',
    gap: 4,
    backgroundColor: 'rgba(26,61,52,0.85)',
    paddingHorizontal: 10,
    paddingVertical: 5,
    borderRadius: 999,
  },
  verifiedText: { fontSize: 10, fontWeight: '800', color: colors.white },
  locationOnPhoto: {
    position: 'absolute',
    bottom: 12,
    left: 12,
    right: 12,
    flexDirection: 'row',
    alignItems: 'center',
    gap: 4,
  },
  locationOnPhotoText: { flex: 1, fontSize: 12, fontWeight: '800', color: colors.white },
  cardBody: { padding: 14 },
  cardTopRow: { flexDirection: 'row', alignItems: 'flex-start', justifyContent: 'space-between', gap: 8 },
  cardName: { flex: 1, fontSize: 17, fontWeight: '900', color: colors.slate900 },
  ratingBadge: { flexDirection: 'row', alignItems: 'center', gap: 3 },
  ratingText: { fontSize: 13, fontWeight: '900', color: colors.slate900 },
  reviewText: { fontSize: 11, fontWeight: '600', color: colors.slate500 },
  cardLangs: { marginTop: 6, fontSize: 12, fontWeight: '600', color: colors.slate500 },
  cardFooter: {
    flexDirection: 'row',
    alignItems: 'flex-end',
    justifyContent: 'space-between',
    marginTop: 12,
    paddingTop: 12,
    borderTopWidth: 1,
    borderTopColor: colors.slate100,
  },
  priceLabel: { fontSize: 10, fontWeight: '700', color: colors.slate500, textTransform: 'uppercase' },
  priceValue: { fontSize: 16, fontWeight: '900', color: colors.baytgo },
  priceUnit: { fontSize: 12, fontWeight: '700', color: colors.slate500 },
  bookBtn: {
    flexDirection: 'row',
    alignItems: 'center',
    gap: 4,
    backgroundColor: colors.baytgo,
    paddingHorizontal: 16,
    paddingVertical: 10,
    borderRadius: 12,
  },
  bookBtnText: { fontSize: 13, fontWeight: '800', color: colors.white },
  empty: {
    alignItems: 'center',
    backgroundColor: colors.white,
    borderRadius: 22,
    padding: 32,
    marginTop: 8,
    borderWidth: 1,
    borderStyle: 'dashed',
    borderColor: colors.slate200,
  },
  emptyIcon: {
    width: 72,
    height: 72,
    borderRadius: 22,
    backgroundColor: colors.canvas,
    alignItems: 'center',
    justifyContent: 'center',
    marginBottom: 16,
  },
  emptyTitle: { fontSize: 16, fontWeight: '900', color: colors.slate700 },
  emptyText: { marginTop: 8, fontSize: 13, fontWeight: '600', color: colors.slate500, textAlign: 'center', lineHeight: 20 },
  emptyBtn: {
    marginTop: 16,
    backgroundColor: colors.emerald50,
    paddingHorizontal: 20,
    paddingVertical: 10,
    borderRadius: 12,
  },
  emptyBtnText: { fontSize: 13, fontWeight: '800', color: colors.baytgo },
  footerLoader: { flexDirection: 'row', alignItems: 'center', justifyContent: 'center', gap: 10, paddingVertical: 20 },
  footerText: { fontSize: 13, fontWeight: '600', color: colors.slate500 },
  listBottom: { height: 24 },
});
