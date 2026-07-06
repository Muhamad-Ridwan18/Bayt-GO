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
} from 'react-native';
import { SafeAreaView } from 'react-native-safe-area-context';
import { LinearGradient } from 'expo-linear-gradient';
import { Ionicons } from '@expo/vector-icons';
import DatePickerField, { parseIsoDate } from '../components/DatePickerField';
import MuthowifListingCard from '../components/MuthowifListingCard';
import { fetchDirectory } from '../api/directory';
import { useAuth } from '../context/AuthContext';
import { colors } from '../theme/colors';

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

  const renderListHeader = () => (
    <View style={styles.listHeader}>
      <View style={styles.searchCard}>
        <LinearGradient
          colors={['rgba(26,61,52,0.06)', 'transparent']}
          style={styles.searchCardAccent}
        />
        <View style={styles.searchCardHead}>
          <View style={styles.searchCardIcon}>
            <Ionicons name="compass" size={18} color={colors.baytgo} />
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
            <Ionicons name="arrow-forward" size={16} color={colors.slate400} />
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

        <View style={styles.searchInputWrap}>
          <Ionicons name="search" size={18} color={colors.slate400} />
          <TextInput
            style={styles.searchInput}
            value={q}
            onChangeText={setQ}
            placeholder="Nama muthowif, bahasa, atau kota..."
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

        <TouchableOpacity style={styles.searchCta} onPress={handleSearch} activeOpacity={0.9}>
          <Ionicons name="search" size={18} color={colors.white} />
          <Text style={styles.searchCtaText}>Tampilkan hasil</Text>
        </TouchableOpacity>
      </View>

      {!loading && total > 0 ? (
        <View style={styles.resultBanner}>
          <View style={styles.resultIcon}>
            <Ionicons name="people" size={18} color={colors.baytgo} />
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

  return (
    <View style={styles.container}>
      <SafeAreaView edges={['top']} style={styles.topBar}>
        <TouchableOpacity style={styles.backBtn} onPress={() => navigation.goBack()} hitSlop={8}>
          <Ionicons name="arrow-back" size={22} color={colors.baytgo} />
        </TouchableOpacity>
        <Text style={styles.pageTitle}>Cari Muthowif</Text>
      </SafeAreaView>

      {loading && !refreshing ? (
        <View style={styles.loadingWrap}>
          <ActivityIndicator color={colors.baytgo} size="large" />
          <Text style={styles.loadingText}>Mencari muthowif terbaik...</Text>
        </View>
      ) : (
        <FlatList
          data={items}
          keyExtractor={(item) => String(item.id)}
          renderItem={({ item }) => (
            <MuthowifListingCard
              item={item}
              onPressDetail={() => openDetail(item)}
              onPressBook={() => openBook(item)}
            />
          )}
          contentContainerStyle={styles.list}
          showsVerticalScrollIndicator={false}
          refreshControl={
            <RefreshControl
              refreshing={refreshing}
              onRefresh={() => loadPage(1, { refresh: true })}
              tintColor={colors.baytgo}
            />
          }
          onEndReached={handleLoadMore}
          onEndReachedThreshold={0.35}
          ListHeaderComponent={renderListHeader}
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

const styles = StyleSheet.create({
  container: { flex: 1, backgroundColor: colors.canvas },
  topBar: {
    flexDirection: 'row',
    alignItems: 'center',
    gap: 12,
    paddingHorizontal: 16,
    paddingBottom: 12,
    backgroundColor: colors.white,
    borderBottomWidth: 1,
    borderBottomColor: colors.slate100,
  },
  backBtn: {
    width: 40,
    height: 40,
    borderRadius: 12,
    backgroundColor: colors.canvas,
    alignItems: 'center',
    justifyContent: 'center',
    borderWidth: 1,
    borderColor: colors.slate100,
  },
  pageTitle: { flex: 1, fontSize: 18, fontWeight: '900', color: colors.baytgo },
  list: { paddingHorizontal: 20, paddingTop: 12, paddingBottom: 24 },
  listHeader: { marginBottom: 4 },
  searchCard: {
    marginTop: 0,
    backgroundColor: colors.white,
    borderRadius: 22,
    padding: 18,
    marginBottom: 16,
    borderWidth: 1,
    borderColor: 'rgba(26,61,52,0.08)',
    shadowColor: '#0F2E28',
    shadowOffset: { width: 0, height: 10 },
    shadowOpacity: 0.12,
    shadowRadius: 24,
    elevation: 10,
    overflow: 'hidden',
  },
  searchCardAccent: {
    position: 'absolute',
    top: 0,
    left: 0,
    right: 0,
    height: 72,
  },
  searchCardHead: {
    flexDirection: 'row',
    alignItems: 'center',
    gap: 12,
    marginBottom: 14,
  },
  searchCardIcon: {
    width: 40,
    height: 40,
    borderRadius: 12,
    backgroundColor: colors.baytgoLight,
    alignItems: 'center',
    justifyContent: 'center',
  },
  searchCardHeadText: { flex: 1 },
  searchCardTitle: { fontSize: 16, fontWeight: '900', color: colors.baytgo },
  searchCardSub: { marginTop: 2, fontSize: 12, fontWeight: '600', color: colors.slate500, lineHeight: 17 },
  dateRow: {
    flexDirection: 'row',
    alignItems: 'center',
    gap: 6,
    marginBottom: 12,
  },
  dateField: { flex: 1 },
  dateArrow: {
    width: 20,
    alignItems: 'center',
    justifyContent: 'center',
    paddingTop: 14,
  },
  searchInputWrap: {
    flexDirection: 'row',
    alignItems: 'center',
    gap: 10,
    backgroundColor: colors.canvas,
    borderRadius: 14,
    paddingHorizontal: 14,
    borderWidth: 1,
    borderColor: 'rgba(26,61,52,0.08)',
    marginBottom: 12,
  },
  searchInput: { flex: 1, paddingVertical: 13, fontSize: 14, fontWeight: '600', color: colors.slate900 },
  searchCta: {
    flexDirection: 'row',
    alignItems: 'center',
    justifyContent: 'center',
    gap: 8,
    backgroundColor: colors.baytgo,
    borderRadius: 14,
    paddingVertical: 15,
  },
  searchCtaText: { fontSize: 15, fontWeight: '800', color: colors.white },
  resultBanner: {
    flexDirection: 'row',
    alignItems: 'center',
    gap: 12,
    backgroundColor: colors.white,
    borderRadius: 14,
    paddingHorizontal: 14,
    paddingVertical: 12,
    marginBottom: 14,
    borderWidth: 1,
    borderColor: 'rgba(26,61,52,0.08)',
  },
  resultIcon: {
    width: 36,
    height: 36,
    borderRadius: 10,
    backgroundColor: colors.baytgoLight,
    alignItems: 'center',
    justifyContent: 'center',
  },
  resultCopy: { flex: 1 },
  resultText: { fontSize: 14, fontWeight: '700', color: colors.slate700 },
  resultCount: { fontWeight: '900', color: colors.baytgo },
  loadingWrap: { flex: 1, alignItems: 'center', justifyContent: 'center', gap: 12 },
  loadingText: { fontSize: 14, fontWeight: '600', color: colors.slate500 },
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
  listBottom: { height: 8 },
});
