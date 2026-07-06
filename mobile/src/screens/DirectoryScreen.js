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
  ImageBackground,
  Dimensions,
} from 'react-native';
import { SafeAreaView } from 'react-native-safe-area-context';
import { LinearGradient } from 'expo-linear-gradient';
import { Ionicons } from '@expo/vector-icons';
import DatePickerField, { parseIsoDate } from '../components/DatePickerField';
import { fetchDirectory } from '../api/directory';
import { useAuth } from '../context/AuthContext';
import { colors } from '../theme/colors';
import { WEB_BASE_URL } from '../config/api';
import { formatIdr } from '../utils/format';
import { resolveMediaUrl } from '../utils/mediaUrl';

const { width: SCREEN_W } = Dimensions.get('window');
const HERO_IMAGE = `${WEB_BASE_URL}/images/bg-01.jpeg`;

function DirectoryCard({ item, onPress }) {
  const langs = (item.languages || []).join(' · ');
  const avatarUri = resolveMediaUrl(item.avatar);
  const specialty = item.specialty ? `Spesialis ${item.specialty}` : null;

  return (
    <TouchableOpacity style={styles.card} onPress={onPress} activeOpacity={0.92}>
      <View style={styles.photoColumn}>
        <View style={styles.cardPhotoWrap}>
          {avatarUri ? (
            <Image source={{ uri: avatarUri }} style={styles.cardPhoto} resizeMode="cover" />
          ) : (
            <View style={[styles.cardPhoto, styles.cardPhotoPlaceholder]}>
              <Ionicons name="person" size={36} color={colors.slate400} />
            </View>
          )}
        </View>
        <View style={styles.verifiedChip}>
          <Ionicons name="shield-checkmark" size={11} color={colors.emerald600} />
          <Text style={styles.verifiedText}>Terverifikasi</Text>
        </View>
        {specialty ? (
          <Text style={styles.specialtyText} numberOfLines={2}>{specialty}</Text>
        ) : null}
      </View>

      <View style={styles.cardBody}>
        <View style={styles.cardNameRow}>
          <Text style={styles.cardName} numberOfLines={1}>{item.name}</Text>
          <Ionicons name="heart-outline" size={20} color={colors.slate400} />
        </View>

        <View style={styles.ratingRow}>
          <Ionicons name="star" size={13} color="#F59E0B" />
          <Text style={styles.ratingText}>{item.rating ?? '—'}</Text>
          <Text style={styles.reviewText}>({item.reviews ?? 0} ulasan)</Text>
        </View>

        {langs ? (
          <View style={styles.metaRow}>
            <Ionicons name="person-outline" size={13} color={colors.slate400} />
            <Text style={styles.metaText} numberOfLines={1}>{langs}</Text>
          </View>
        ) : null}

        {item.location ? (
          <View style={styles.metaRow}>
            <Ionicons name="location-outline" size={13} color={colors.slate400} />
            <Text style={styles.metaText} numberOfLines={1}>{item.location}</Text>
          </View>
        ) : null}

        {item.experience ? (
          <View style={styles.metaRow}>
            <Ionicons name="briefcase-outline" size={13} color={colors.slate400} />
            <Text style={styles.metaText} numberOfLines={2}>{item.experience}</Text>
          </View>
        ) : null}

        <View style={styles.cardFooter}>
          <View style={styles.priceBlock}>
            <Text style={styles.priceLabel}>Mulai dari</Text>
            <Text style={styles.priceValue}>
              {formatIdr(item.start_price)}
              <Text style={styles.priceUnit}> /hari</Text>
            </Text>
          </View>
          <TouchableOpacity style={styles.lihatBtn} onPress={onPress} activeOpacity={0.88}>
            <Text style={styles.lihatBtnText}>Lihat</Text>
            <Ionicons name="arrow-forward" size={14} color={colors.white} />
          </TouchableOpacity>
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

  const handleSearch = () => {
    loadPage(1);
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

  const renderListHeader = () => (
    <View style={styles.listHeader}>
      {!loading && total > 0 ? (
        <View style={styles.resultBanner}>
          <View style={styles.resultIcon}>
            <Ionicons name="people" size={18} color={colors.baytgo} />
          </View>
          <View style={styles.resultCopy}>
            <Text style={styles.resultText}>
              <Text style={styles.resultCount}>{total}</Text> muthowif tersedia
              {startDate ? ' untuk tanggal Anda' : ''}
            </Text>
            <Text style={styles.resultSub}>Pilih muthowif terbaik untuk perjalanan ibadah Anda</Text>
          </View>
        </View>
      ) : null}

      <View style={styles.sectionHead}>
        <View style={styles.sectionHeadIcon}>
          <Ionicons name="shield-checkmark" size={18} color={colors.emerald600} />
        </View>
        <View style={styles.sectionHeadText}>
          <Text style={styles.sectionTitle}>Muthowif Terverifikasi</Text>
          <Text style={styles.sectionSub}>Semua pendamping telah melalui proses verifikasi BaytGo</Text>
        </View>
      </View>
    </View>
  );

  return (
    <View style={styles.container}>
      <ImageBackground source={{ uri: HERO_IMAGE }} style={styles.heroImage} resizeMode="cover">
        <LinearGradient
          colors={['rgba(10,34,30,0.15)', 'rgba(10,34,30,0.72)']}
          style={StyleSheet.absoluteFill}
        />
        <SafeAreaView edges={['top']}>
          <View style={styles.heroRow}>
            <TouchableOpacity style={styles.heroBtn} onPress={() => navigation.goBack()} hitSlop={8}>
              <Ionicons name="arrow-back" size={20} color={colors.baytgo} />
            </TouchableOpacity>
          </View>
        </SafeAreaView>
      </ImageBackground>

      <View style={styles.pageBody}>
        <View style={styles.titleBlock}>
          <Text style={styles.pageTitle}>Cari Muthowif</Text>
          <Text style={styles.pageSub}>Temukan pendamping ibadah umroh terpercaya</Text>
        </View>

        <View style={styles.filterCard}>
          <View style={styles.searchRow}>
            <Ionicons name="search" size={18} color={colors.slate400} />
            <TextInput
              style={styles.input}
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

          <View style={styles.dateRow}>
            <View style={styles.dateField}>
              <DatePickerField
                label="Mulai"
                value={startDate}
                onChange={handleStartDateChange}
                placeholder="Pilih"
                minimumDate={today}
                variant="chip"
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
                variant="chip"
              />
            </View>
          </View>

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
    </View>
  );
}

const CARD_PHOTO_W = 112;
const CARD_PHOTO_H = 136;

const styles = StyleSheet.create({
  container: { flex: 1, backgroundColor: colors.canvas },
  heroImage: {
    width: SCREEN_W,
    height: 148,
  },
  heroRow: {
    flexDirection: 'row',
    alignItems: 'center',
    paddingHorizontal: 16,
    paddingTop: 4,
  },
  heroBtn: {
    width: 40,
    height: 40,
    borderRadius: 20,
    backgroundColor: colors.white,
    alignItems: 'center',
    justifyContent: 'center',
    shadowColor: '#000',
    shadowOffset: { width: 0, height: 2 },
    shadowOpacity: 0.12,
    shadowRadius: 6,
    elevation: 3,
  },
  pageBody: { flex: 1 },
  titleBlock: {
    paddingHorizontal: 20,
    paddingTop: 14,
    paddingBottom: 4,
  },
  pageTitle: { fontSize: 22, fontWeight: '900', color: colors.baytgo },
  pageSub: { marginTop: 4, fontSize: 13, fontWeight: '600', color: colors.slate500, lineHeight: 18 },
  filterCard: {
    marginHorizontal: 20,
    marginTop: 12,
    backgroundColor: colors.white,
    borderRadius: 20,
    padding: 16,
    shadowColor: '#0F2E28',
    shadowOffset: { width: 0, height: 8 },
    shadowOpacity: 0.1,
    shadowRadius: 20,
    elevation: 8,
    borderWidth: 1,
    borderColor: 'rgba(26,61,52,0.08)',
    marginBottom: 4,
  },
  searchRow: {
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
  input: { flex: 1, paddingVertical: 13, fontSize: 14, fontWeight: '600', color: colors.slate900 },
  dateRow: {
    flexDirection: 'row',
    alignItems: 'flex-end',
    gap: 8,
    marginBottom: 4,
  },
  dateField: { flex: 1 },
  searchBtn: { borderRadius: 14, overflow: 'hidden', marginTop: 12 },
  searchGradient: {
    flexDirection: 'row',
    alignItems: 'center',
    justifyContent: 'center',
    gap: 8,
    paddingVertical: 15,
  },
  searchText: { color: colors.white, fontWeight: '800', fontSize: 15 },
  loadingWrap: { flex: 1, alignItems: 'center', justifyContent: 'center', gap: 12, paddingTop: 40 },
  loadingText: { fontSize: 14, fontWeight: '600', color: colors.slate500 },
  list: { paddingHorizontal: 20, paddingTop: 12 },
  listHeader: { marginBottom: 4 },
  resultBanner: {
    flexDirection: 'row',
    alignItems: 'flex-start',
    gap: 12,
    backgroundColor: colors.slate100,
    borderRadius: 14,
    paddingHorizontal: 14,
    paddingVertical: 12,
    marginBottom: 16,
  },
  resultIcon: {
    width: 36,
    height: 36,
    borderRadius: 10,
    backgroundColor: colors.white,
    alignItems: 'center',
    justifyContent: 'center',
  },
  resultCopy: { flex: 1 },
  resultText: { fontSize: 13, fontWeight: '700', color: colors.slate700, lineHeight: 18 },
  resultCount: { fontWeight: '900', color: colors.baytgo },
  resultSub: { marginTop: 2, fontSize: 11, fontWeight: '600', color: colors.slate500, lineHeight: 15 },
  sectionHead: {
    flexDirection: 'row',
    alignItems: 'flex-start',
    gap: 10,
    marginBottom: 14,
  },
  sectionHeadIcon: {
    width: 36,
    height: 36,
    borderRadius: 10,
    backgroundColor: colors.emerald50,
    alignItems: 'center',
    justifyContent: 'center',
  },
  sectionHeadText: { flex: 1 },
  sectionTitle: { fontSize: 16, fontWeight: '900', color: colors.baytgo },
  sectionSub: { marginTop: 2, fontSize: 11, fontWeight: '600', color: colors.slate500, lineHeight: 15 },
  card: {
    flexDirection: 'row',
    alignItems: 'flex-start',
    backgroundColor: colors.white,
    borderRadius: 18,
    marginBottom: 14,
    overflow: 'hidden',
    borderWidth: 1,
    borderColor: 'rgba(26,61,52,0.08)',
    shadowColor: '#0F2E28',
    shadowOffset: { width: 0, height: 4 },
    shadowOpacity: 0.06,
    shadowRadius: 12,
    elevation: 3,
    padding: 12,
    gap: 12,
  },
  photoColumn: {
    width: CARD_PHOTO_W,
    alignItems: 'center',
  },
  cardPhotoWrap: {
    width: CARD_PHOTO_W,
    height: CARD_PHOTO_H,
    borderRadius: 14,
    overflow: 'hidden',
    backgroundColor: colors.slate100,
    borderWidth: 1,
    borderColor: 'rgba(26,61,52,0.08)',
  },
  cardPhoto: { width: '100%', height: '100%' },
  cardPhotoPlaceholder: { alignItems: 'center', justifyContent: 'center' },
  verifiedChip: {
    flexDirection: 'row',
    alignItems: 'center',
    justifyContent: 'center',
    gap: 4,
    marginTop: 8,
    backgroundColor: colors.emerald50,
    paddingHorizontal: 8,
    paddingVertical: 4,
    borderRadius: 999,
    borderWidth: 1,
    borderColor: 'rgba(5,150,105,0.15)',
    alignSelf: 'stretch',
  },
  verifiedText: { fontSize: 9, fontWeight: '800', color: colors.emerald600 },
  specialtyText: {
    marginTop: 6,
    fontSize: 9,
    fontWeight: '800',
    color: colors.baytgo,
    lineHeight: 12,
    textAlign: 'center',
  },
  cardBody: { flex: 1, paddingTop: 2 },
  cardNameRow: {
    flexDirection: 'row',
    alignItems: 'center',
    justifyContent: 'space-between',
    gap: 8,
  },
  cardName: { flex: 1, fontSize: 15, fontWeight: '900', color: colors.slate900 },
  ratingRow: { flexDirection: 'row', alignItems: 'center', gap: 4, marginTop: 5 },
  ratingText: { fontSize: 12, fontWeight: '900', color: colors.slate900 },
  reviewText: { fontSize: 11, fontWeight: '600', color: colors.slate500 },
  metaRow: { flexDirection: 'row', alignItems: 'flex-start', gap: 6, marginTop: 5 },
  metaText: { flex: 1, fontSize: 11, fontWeight: '600', color: colors.slate500, lineHeight: 15 },
  cardFooter: {
    flexDirection: 'row',
    alignItems: 'flex-end',
    justifyContent: 'space-between',
    marginTop: 10,
    paddingTop: 8,
    borderTopWidth: 1,
    borderTopColor: colors.slate100,
  },
  priceBlock: { flex: 1, paddingRight: 8 },
  priceLabel: { fontSize: 9, fontWeight: '700', color: colors.slate500, textTransform: 'uppercase' },
  priceValue: { marginTop: 2, fontSize: 14, fontWeight: '900', color: colors.baytgo },
  priceUnit: { fontSize: 11, fontWeight: '700', color: colors.slate500 },
  lihatBtn: {
    flexDirection: 'row',
    alignItems: 'center',
    gap: 4,
    backgroundColor: colors.baytgo,
    paddingHorizontal: 14,
    paddingVertical: 9,
    borderRadius: 12,
  },
  lihatBtnText: { fontSize: 12, fontWeight: '800', color: colors.white },
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
