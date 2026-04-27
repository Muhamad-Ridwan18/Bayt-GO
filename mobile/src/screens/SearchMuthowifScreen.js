import React, { useState, useEffect, useCallback, useMemo, useRef } from 'react';
import { 
  StyleSheet, 
  Text, 
  View, 
  TouchableOpacity, 
  FlatList, 
  TextInput,
  Image,
  Dimensions,
  Modal,
  ActivityIndicator,
  RefreshControl,
  KeyboardAvoidingView,
  Platform,
  Alert,
  StatusBar
} from 'react-native';
import { SafeAreaView } from 'react-native-safe-area-context';
import { Ionicons } from '@expo/vector-icons';
import { LinearGradient } from 'expo-linear-gradient';
import { apiClient } from '../api/client';
import SwipeableScreen from '../components/SwipeableScreen';
import { SkeletonCard, SkeletonText, Skeleton } from '../components/Skeleton';

const { width } = Dimensions.get('window');

const MONTH_NAMES = ['Januari','Februari','Maret','April','Mei','Juni','Juli','Agustus','September','Oktober','November','Desember'];
const DAY_NAMES_SHORT = ['Min','Sen','Sel','Rab','Kam','Jum','Sab'];

const formatIDR = (amount) => {
  if (amount === undefined || amount === null) return '0';
  return amount.toString().replace(/\B(?=(\d{3})+(?!\d))/g, ".");
};

export default function SearchMuthowifScreen({ user, navigation }) {
  const [profiles, setProfiles] = useState([]);
  const [loading, setLoading] = useState(true);
  const [loadingMore, setLoadingMore] = useState(false);
  const [refreshing, setRefreshing] = useState(false);
  const [page, setPage] = useState(1);
  const [lastPage, setLastPage] = useState(1);
  
  const [searchQuery, setSearchQuery] = useState('');
  const [startDate, setStartDate] = useState(null);
  const [endDate, setEndDate] = useState(null);
  const [filterModalVisible, setFilterModalVisible] = useState(false);
  const [activeDateType, setActiveDateType] = useState(null);
  
  const [selectedMonth, setSelectedMonth] = useState(new Date().getMonth());
  const [selectedYear, setSelectedYear] = useState(new Date().getFullYear());
  const dayListRef = useRef(null);

  const months = useMemo(() => {
    const res = [];
    const now = new Date();
    for (let i = 0; i < 12; i++) {
      const d = new Date(now.getFullYear(), now.getMonth() + i, 1);
      res.push({
        month: d.getMonth(),
        year: d.getFullYear(),
        label: `${MONTH_NAMES[d.getMonth()]} ${d.getFullYear()}`
      });
    }
    return res;
  }, []);

  const days = useMemo(() => {
    const res = [];
    const numDays = new Date(selectedYear, selectedMonth + 1, 0).getDate();
    const today = new Date();
    today.setHours(0,0,0,0);

    for (let i = 1; i <= numDays; i++) {
      const d = new Date(selectedYear, selectedMonth, i, 12, 0, 0);
      const y = d.getFullYear();
      const m = String(d.getMonth() + 1).padStart(2, '0');
      const dayNum = String(d.getDate()).padStart(2, '0');
      const dateString = `${y}-${m}-${dayNum}`;
      res.push({
        day: i,
        dayName: DAY_NAMES_SHORT[d.getDay()],
        dateString,
        isPast: d < today,
        monthName: MONTH_NAMES[selectedMonth]?.substring(0, 3)
      });
    }
    return res;
  }, [selectedMonth, selectedYear]);

  const fetchProfiles = useCallback(async (p = 1, append = false) => {
    if (p === 1) setLoading(true);
    else setLoadingMore(true);

    try {
      const params = { page: p, q: searchQuery };
      if (startDate) params.start_date = startDate;
      if (endDate) params.end_date = endDate;

      const data = await apiClient.searchMuthowif(user.token, params);
      setProfiles(prev => append ? [...prev, ...data.data] : data.data);
      setLastPage(data.last_page);
      setPage(p);
    } catch (error) {
      console.error(error);
    } finally {
      setLoading(false);
      setLoadingMore(false);
      setRefreshing(false);
    }
  }, [user.token, searchQuery, startDate, endDate]);

  useEffect(() => {
    const timer = setTimeout(() => fetchProfiles(1), 500);
    return () => clearTimeout(timer);
  }, [searchQuery, startDate, endDate]);

  const handleRefresh = () => {
    setRefreshing(true);
    fetchProfiles(1);
  };

  const handleLoadMore = () => {
    if (page < lastPage && !loadingMore) {
      fetchProfiles(page + 1, true);
    }
  };

  const handleDateSelect = (dateString) => {
    if (activeDateType === 'start') {
      setStartDate(dateString);
      if (endDate && dateString > endDate) setEndDate(null);
    } else {
      if (startDate && dateString < startDate) {
        Alert.alert('Perhatian', 'Tanggal kepulangan tidak boleh sebelum keberangkatan.');
        return;
      }
      setEndDate(dateString);
    }
    setFilterModalVisible(false);
  };

  const formatDateDisplay = (dateStr) => {
    if (!dateStr) return '';
    const parts = dateStr.split('-');
    return `${parts[2]} ${MONTH_NAMES[parseInt(parts[1]) - 1].substring(0, 3)} ${parts[0]}`;
  };

  const renderItem = ({ item }) => (
    <TouchableOpacity 
      style={styles.muthowifCard} 
      activeOpacity={0.9}
      onPress={() => navigation.navigate('MuthowifDetail', { id: item.id, start_date: startDate, end_date: endDate })}
    >
      <View style={styles.imageWrapper}>
        <Image source={{ uri: item.avatar }} style={styles.muthowifAvatar} />
        <View style={styles.ratingBadge}>
          <Ionicons name="star" size={12} color="#F59E0B" />
          <Text style={styles.ratingText}>{item.rating}</Text>
        </View>
      </View>
      <View style={styles.muthowifInfo}>
        <Text style={styles.muthowifName} numberOfLines={1}>{item.name}</Text>
        <View style={styles.locationRow}>
          <Ionicons name="location-sharp" size={14} color="#64748B" />
          <Text style={styles.locationText}>{item.location || 'Makkah'}</Text>
        </View>
        <View style={styles.tagRow}>
          {item.languages?.slice(0, 2).map((lang, i) => (
            <View key={i} style={styles.tag}>
              <Text style={styles.tagText}>{lang}</Text>
            </View>
          ))}
        </View>
        <View style={styles.priceContainer}>
          <Text style={styles.pricePrefix}>Mulai dari</Text>
          <Text style={styles.priceValue}>Rp {formatIDR(item.min_price)}</Text>
          <Text style={styles.priceSuffix}>/hari</Text>
        </View>
      </View>
    </TouchableOpacity>
  );

  return (
    <SwipeableScreen onSwipeBack={() => navigation.goBack()}>
      <View style={styles.container}>
        <StatusBar barStyle="dark-content" />
        <View style={styles.header}>
          <SafeAreaView edges={['top']}>
            <View style={styles.headerTop}>
              <TouchableOpacity onPress={() => navigation.goBack()} style={styles.backBtn}>
                <Ionicons name="arrow-back" size={24} color="#0F172A" />
              </TouchableOpacity>
              <Text style={styles.headerTitle}>Cari Muthowif</Text>
              <View style={{ width: 24 }} />
            </View>

            <View style={styles.searchSection}>
              <View style={styles.searchBar}>
                <Ionicons name="search" size={20} color="#94A3B8" style={{ marginRight: 10 }} />
                <TextInput 
                  style={styles.searchInput}
                  placeholder="Cari nama muthowif..."
                  placeholderTextColor="#94A3B8"
                  value={searchQuery}
                  onChangeText={setSearchQuery}
                />
              </View>

              <View style={styles.filterRow}>
                <TouchableOpacity 
                  style={[styles.dateBtn, startDate && styles.dateBtnActive]} 
                  onPress={() => { setActiveDateType('start'); setFilterModalVisible(true); }}
                >
                  <Ionicons name="calendar-outline" size={16} color={startDate ? '#0984e3' : '#64748B'} />
                  <Text style={[styles.dateBtnText, startDate && styles.dateBtnTextActive]}>
                    {startDate ? formatDateDisplay(startDate) : 'Berangkat'}
                  </Text>
                </TouchableOpacity>

                <Ionicons name="arrow-forward" size={14} color="#CBD5E1" />

                <TouchableOpacity 
                  style={[styles.dateBtn, endDate && styles.dateBtnActive]} 
                  onPress={() => { setActiveDateType('end'); setFilterModalVisible(true); }}
                >
                  <Ionicons name="calendar-outline" size={16} color={endDate ? '#0984e3' : '#64748B'} />
                  <Text style={[styles.dateBtnText, endDate && styles.dateBtnTextActive]}>
                    {endDate ? formatDateDisplay(endDate) : 'Pulang'}
                  </Text>
                </TouchableOpacity>

                {(startDate || endDate) && (
                  <TouchableOpacity style={styles.clearBtn} onPress={() => { setStartDate(null); setEndDate(null); }}>
                    <Ionicons name="refresh-circle" size={28} color="#94A3B8" />
                  </TouchableOpacity>
                )}
              </View>
            </View>
          </SafeAreaView>
        </View>

        {loading ? (
          <View style={styles.listContent}>
            {[1,2,3,4].map(i => (
              <View key={i} style={{ marginBottom: 20, flexDirection: 'row', gap: 15, backgroundColor: '#FFF', padding: 15, borderRadius: 20 }}>
                <Skeleton width={100} height={100} borderRadius={16} />
                <View style={{ flex: 1, gap: 10 }}>
                  <SkeletonText width="80%" height={18} />
                  <SkeletonText width="40%" height={12} />
                  <SkeletonText width="100%" height={24} style={{ marginTop: 10 }} />
                </View>
              </View>
            ))}
          </View>
        ) : (
          <FlatList
            data={profiles}
            keyExtractor={item => item.id.toString()}
            renderItem={renderItem}
            contentContainerStyle={styles.listContent}
            showsVerticalScrollIndicator={false}
            refreshControl={<RefreshControl refreshing={refreshing} onRefresh={handleRefresh} tintColor="#3B82F6" />}
            onEndReached={handleLoadMore}
            ListEmptyComponent={
              <View style={styles.empty}>
                <Ionicons name="search-outline" size={80} color="#E2E8F0" />
                <Text style={styles.emptyTitle}>Muthowif Tidak Ditemukan</Text>
                <Text style={styles.emptySub}>Coba cari dengan kata kunci lain atau ubah tanggal perjalanan Anda.</Text>
              </View>
            }
          />
        )}

        <Modal visible={filterModalVisible} animationType="fade" transparent={true}>
          <View style={styles.modalOverlay}>
            <TouchableOpacity style={StyleSheet.absoluteFill} onPress={() => setFilterModalVisible(false)} />
            <View style={styles.modalContent}>
              <View style={styles.modalHeader}>
                <Text style={styles.modalTitle}>{activeDateType === 'start' ? 'Pilih Keberangkatan' : 'Pilih Kepulangan'}</Text>
                <TouchableOpacity onPress={() => setFilterModalVisible(false)}><Ionicons name="close" size={24} color="#0F172A" /></TouchableOpacity>
              </View>
              
              <FlatList
                horizontal
                showsHorizontalScrollIndicator={false}
                data={months}
                keyExtractor={(item, index) => index.toString()}
                renderItem={({ item }) => (
                  <TouchableOpacity 
                    onPress={() => { setSelectedMonth(item.month); setSelectedYear(item.year); }}
                    style={[styles.monthPill, selectedMonth === item.month && selectedYear === item.year && styles.monthPillActive]}
                  >
                    <Text style={[styles.monthPillText, selectedMonth === item.month && selectedYear === item.year && styles.monthPillTextActive]}>{item.label}</Text>
                  </TouchableOpacity>
                )}
                contentContainerStyle={{ paddingHorizontal: 20, gap: 8, marginBottom: 20 }}
              />

              <FlatList
                horizontal
                showsHorizontalScrollIndicator={false}
                data={days}
                keyExtractor={item => item.dateString}
                renderItem={({ item }) => {
                  const isSelected = activeDateType === 'start' ? startDate === item.dateString : endDate === item.dateString;
                  return (
                    <TouchableOpacity 
                      disabled={item.isPast}
                      onPress={() => handleDateSelect(item.dateString)}
                      style={[styles.dayCard, isSelected && styles.dayCardActive, item.isPast && styles.dayCardDisabled]}
                    >
                      <Text style={[styles.dayCardName, isSelected && styles.dayCardTextActive, item.isPast && styles.dayCardTextDisabled]}>{item.dayName}</Text>
                      <Text style={[styles.dayCardNum, isSelected && styles.dayCardTextActive, item.isPast && styles.dayCardTextDisabled]}>{item.day}</Text>
                      <Text style={[styles.dayCardMonth, isSelected && styles.dayCardTextActive, item.isPast && styles.dayCardTextDisabled]}>{item.monthName}</Text>
                    </TouchableOpacity>
                  );
                }}
                contentContainerStyle={{ paddingHorizontal: 20, gap: 12, paddingBottom: 20 }}
              />
            </View>
          </View>
        </Modal>
      </View>
    </SwipeableScreen>
  );
}

const styles = StyleSheet.create({
  container: { flex: 1, backgroundColor: '#F8FAFC' },
  header: { 
    backgroundColor: '#FFFFFF',
    borderBottomLeftRadius: 30, 
    borderBottomRightRadius: 30,
    shadowColor: '#000',
    shadowOffset: { width: 0, height: 4 },
    shadowOpacity: 0.03,
    shadowRadius: 10,
    elevation: 5,
    paddingBottom: 20
  },
  headerTop: { flexDirection: 'row', alignItems: 'center', justifyContent: 'space-between', paddingHorizontal: 20, paddingTop: 10 },
  backBtn: { width: 40, height: 40, justifyContent: 'center', alignItems: 'center' },
  headerTitle: { color: '#0F172A', fontSize: 20, fontWeight: '900', letterSpacing: -0.5 },
  
  searchSection: { paddingHorizontal: 20, marginTop: 15 },
  searchBar: { 
    flexDirection: 'row', 
    alignItems: 'center', 
    backgroundColor: '#F8FAFC', 
    borderRadius: 16, 
    paddingHorizontal: 16, 
    height: 50,
    borderWidth: 1,
    borderColor: '#F1F5F9'
  },
  searchInput: { flex: 1, fontSize: 14, fontWeight: '700', color: '#1E293B' },
  
  filterRow: { flexDirection: 'row', alignItems: 'center', marginTop: 12, gap: 8 },
  dateBtn: { 
    flex: 1, 
    flexDirection: 'row', 
    alignItems: 'center', 
    backgroundColor: '#F8FAFC', 
    paddingVertical: 10, 
    paddingHorizontal: 12, 
    borderRadius: 12, 
    gap: 6,
    borderWidth: 1,
    borderColor: '#F1F5F9'
  },
  dateBtnActive: { backgroundColor: '#F0F9FF', borderColor: '#DBEAFE' },
  dateBtnText: { color: '#64748B', fontSize: 12, fontWeight: '700' },
  dateBtnTextActive: { color: '#0984e3' },
  clearBtn: { padding: 2 },

  listContent: { padding: 20, paddingBottom: 100 },
  muthowifCard: { backgroundColor: '#FFF', borderRadius: 24, padding: 16, marginBottom: 16, flexDirection: 'row', gap: 16, borderWidth: 1, borderColor: '#F1F5F9', elevation: 3, shadowColor: '#64748B', shadowOffset: { width: 0, height: 5 }, shadowOpacity: 0.05, shadowRadius: 10 },
  imageWrapper: { width: 100, height: 120, borderRadius: 20, overflow: 'hidden' },
  muthowifAvatar: { width: '100%', height: '100%', backgroundColor: '#F8FAFC' },
  ratingBadge: { position: 'absolute', top: 8, left: 8, backgroundColor: 'rgba(255, 255, 255, 0.95)', paddingHorizontal: 6, paddingVertical: 3, borderRadius: 8, flexDirection: 'row', alignItems: 'center', gap: 3 },
  ratingText: { fontSize: 10, fontWeight: '800', color: '#1E293B' },
  
  muthowifInfo: { flex: 1, justifyContent: 'center' },
  muthowifName: { fontSize: 18, fontWeight: '900', color: '#0F172A', marginBottom: 4 },
  locationRow: { flexDirection: 'row', alignItems: 'center', gap: 4, marginBottom: 8 },
  locationText: { fontSize: 12, color: '#64748B', fontWeight: '600' },
  tagRow: { flexDirection: 'row', gap: 6, marginBottom: 12 },
  tag: { backgroundColor: '#F0F9FF', paddingHorizontal: 8, paddingVertical: 4, borderRadius: 8 },
  tagText: { fontSize: 10, color: '#0984e3', fontWeight: '800' },
  
  priceContainer: { flexDirection: 'row', alignItems: 'baseline', gap: 4 },
  pricePrefix: { fontSize: 10, color: '#94A3B8', fontWeight: '600' },
  priceValue: { fontSize: 16, fontWeight: '900', color: '#3B82F6' },
  priceSuffix: { fontSize: 10, color: '#94A3B8', fontWeight: '600' },

  empty: { alignItems: 'center', marginTop: 100, paddingHorizontal: 40 },
  emptyTitle: { fontSize: 18, fontWeight: '900', color: '#0F172A', marginTop: 20 },
  emptySub: { fontSize: 14, color: '#64748B', textAlign: 'center', marginTop: 10, lineHeight: 22 },

  modalOverlay: { flex: 1, backgroundColor: 'rgba(0,0,0,0.4)', justifyContent: 'flex-end' },
  modalContent: { backgroundColor: '#FFF', borderTopLeftRadius: 32, borderTopRightRadius: 32, paddingVertical: 24 },
  modalHeader: { flexDirection: 'row', justifyContent: 'space-between', alignItems: 'center', paddingHorizontal: 24, marginBottom: 20 },
  modalTitle: { fontSize: 18, fontWeight: '900', color: '#0F172A' },
  
  monthPill: { paddingHorizontal: 16, paddingVertical: 10, borderRadius: 20, backgroundColor: '#F1F5F9' },
  monthPillActive: { backgroundColor: '#0F172A' },
  monthPillText: { fontSize: 13, fontWeight: '700', color: '#64748B' },
  monthPillTextActive: { color: '#FFF' },

  dayCard: { width: 65, height: 90, borderRadius: 20, backgroundColor: '#F8FAFC', justifyContent: 'center', alignItems: 'center', borderWidth: 1, borderColor: '#F1F5F9' },
  dayCardActive: { backgroundColor: '#3B82F6', borderColor: '#3B82F6' },
  dayCardDisabled: { opacity: 0.3 },
  dayCardName: { fontSize: 11, fontWeight: '700', color: '#94A3B8', marginBottom: 4 },
  dayCardNum: { fontSize: 20, fontWeight: '900', color: '#0F172A' },
  dayCardMonth: { fontSize: 10, fontWeight: '700', color: '#94A3B8', marginTop: 2 },
  dayCardTextActive: { color: '#FFF' },
  dayCardTextDisabled: { color: '#CBD5E1' }
});
