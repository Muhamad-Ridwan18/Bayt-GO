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
import { LinearGradient } from 'expo-linear-gradient';
import { Ionicons } from '@expo/vector-icons';
import ScreenHeader from '../components/ScreenHeader';
import MuthowifListItem from '../components/MuthowifListItem';
import DatePickerField, { parseIsoDate } from '../components/DatePickerField';
import { fetchDirectory } from '../api/directory';
import { useAuth } from '../context/AuthContext';
import { colors } from '../theme/colors';

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

  const loadPage = useCallback(async (pageNum, { append = false, refresh = false } = {}) => {
    if (append) setLoadingMore(true);
    else if (refresh) setRefreshing(true);
    else setLoading(true);

    try {
      const data = await fetchDirectory({
        token,
        q: q.trim(),
        startDate: startDate.trim(),
        endDate: endDate.trim(),
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

  const openDetail = (item) => {
    navigation.navigate('MuthowifDetail', {
      id: item.id,
      startDate: startDate.trim() || undefined,
      endDate: endDate.trim() || undefined,
    });
  };

  return (
    <View style={styles.container}>
      <ScreenHeader title="Cari Muthowif" onBack={() => navigation.goBack()} />

      <View style={styles.filters}>
        <View style={styles.searchRow}>
          <Ionicons name="search-outline" size={18} color={colors.slate400} />
          <TextInput
            style={styles.input}
            value={q}
            onChangeText={setQ}
            placeholder="Nama muthowif"
            placeholderTextColor={colors.slate400}
            returnKeyType="search"
            onSubmitEditing={handleSearch}
          />
        </View>
        <View style={styles.dateRow}>
          <View style={styles.dateField}>
            <DatePickerField
              label="Mulai"
              value={startDate}
              onChange={handleStartDateChange}
              placeholder="Pilih tanggal"
              minimumDate={today}
            />
          </View>
          <View style={styles.dateField}>
            <DatePickerField
              label="Selesai"
              value={endDate}
              onChange={setEndDate}
              placeholder="Opsional"
              minimumDate={endMinDate}
              maximumDate={endMaxDate}
              clearable
              onClear={() => setEndDate('')}
            />
          </View>
        </View>
        <TouchableOpacity style={styles.searchBtn} onPress={handleSearch} activeOpacity={0.9}>
          <LinearGradient colors={[colors.baytgo, colors.baytgoDark]} style={styles.searchGradient}>
            <Ionicons name="search" size={16} color={colors.white} />
            <Text style={styles.searchText}>Cari</Text>
          </LinearGradient>
        </TouchableOpacity>
      </View>

      {loading && !refreshing ? (
        <ActivityIndicator color={colors.baytgo} style={styles.loader} />
      ) : (
        <FlatList
          data={items}
          keyExtractor={(item) => String(item.id)}
          renderItem={({ item }) => <MuthowifListItem item={item} onPress={() => openDetail(item)} />}
          contentContainerStyle={styles.list}
          refreshControl={
            <RefreshControl refreshing={refreshing} onRefresh={() => loadPage(1, { refresh: true })} tintColor={colors.baytgo} />
          }
          onEndReached={handleLoadMore}
          onEndReachedThreshold={0.4}
          ListHeaderComponent={
            total > 0 ? (
              <Text style={styles.resultCount}>{total} muthowif ditemukan</Text>
            ) : null
          }
          ListEmptyComponent={
            <View style={styles.empty}>
              <Text style={styles.emptyText}>
                {error || 'Belum ada muthowif yang cocok. Coba ubah tanggal atau kata kunci.'}
              </Text>
              {error ? (
                <TouchableOpacity onPress={() => loadPage(1)}>
                  <Text style={styles.retry}>Coba lagi</Text>
                </TouchableOpacity>
              ) : null}
            </View>
          }
          ListFooterComponent={
            loadingMore ? <ActivityIndicator color={colors.baytgo} style={styles.footerLoader} /> : null
          }
        />
      )}
    </View>
  );
}

const styles = StyleSheet.create({
  container: { flex: 1, backgroundColor: colors.canvas },
  filters: {
    paddingHorizontal: 16,
    paddingBottom: 12,
    gap: 10,
  },
  searchRow: {
    flexDirection: 'row',
    alignItems: 'center',
    gap: 10,
    backgroundColor: colors.white,
    borderRadius: 14,
    paddingHorizontal: 14,
    borderWidth: 1,
    borderColor: colors.slate100,
  },
  input: { flex: 1, paddingVertical: 14, fontSize: 14, fontWeight: '600', color: colors.slate900 },
  dateRow: { flexDirection: 'row', gap: 10 },
  dateField: { flex: 1 },
  searchBtn: { borderRadius: 14, overflow: 'hidden' },
  searchGradient: {
    flexDirection: 'row',
    alignItems: 'center',
    justifyContent: 'center',
    gap: 8,
    paddingVertical: 14,
  },
  searchText: { color: colors.white, fontWeight: '800', fontSize: 14 },
  list: { paddingHorizontal: 16, paddingBottom: 24 },
  resultCount: {
    fontSize: 12,
    fontWeight: '700',
    color: colors.slate500,
    marginBottom: 10,
  },
  loader: { marginTop: 40 },
  footerLoader: { marginVertical: 16 },
  empty: {
    backgroundColor: colors.white,
    borderRadius: 18,
    padding: 24,
    borderWidth: 1,
    borderStyle: 'dashed',
    borderColor: colors.slate200,
    alignItems: 'center',
  },
  emptyText: { fontSize: 14, color: colors.slate500, fontWeight: '600', textAlign: 'center', lineHeight: 20 },
  retry: { marginTop: 10, fontSize: 14, fontWeight: '800', color: colors.baytgo },
});
