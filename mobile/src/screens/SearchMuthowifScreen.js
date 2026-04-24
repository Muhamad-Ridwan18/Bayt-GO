import React, { useState, useEffect, useCallback } from 'react';
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
  Alert
} from 'react-native';
import { SafeAreaView } from 'react-native-safe-area-context';
import { StatusBar } from 'expo-status-bar';
import { Ionicons } from '@expo/vector-icons';
import { CalendarList, LocaleConfig } from 'react-native-calendars';
import { apiClient } from '../api/client';
import SwipeableScreen from '../components/SwipeableScreen';
import { SkeletonCard, SkeletonText, Skeleton } from '../components/Skeleton';

LocaleConfig.locales['id'] = {
  monthNames: ['Januari','Februari','Maret','April','Mei','Juni','Juli','Agustus','September','Oktober','November','Desember'],
  monthNamesShort: ['Jan','Feb','Mar','Apr','Mei','Jun','Jul','Agu','Sep','Okt','Nov','Des'],
  dayNames: ['Minggu','Senin','Selasa','Rabu','Kamis','Jumat','Sabtu'],
  dayNamesShort: ['Min','Sen','Sel','Rab','Kam','Jum','Sab'],
  today: 'Hari ini'
};
LocaleConfig.defaultLocale = 'id';

const { width, height } = Dimensions.get('window');

export default function SearchMuthowifScreen({ user, navigation }) {
  const [profiles, setProfiles] = useState([]);
  const [loading, setLoading] = useState(true);
  const [loadingMore, setLoadingMore] = useState(false);
  const [refreshing, setRefreshing] = useState(false);
  const [page, setPage] = useState(1);
  const [lastPage, setLastPage] = useState(1);
  
  // Search & Filters
  const [searchQuery, setSearchQuery] = useState('');
  const [debouncedQuery, setDebouncedQuery] = useState('');
  const [startDate, setStartDate] = useState(null);
  const [endDate, setEndDate] = useState(null);
  const [filterModalVisible, setFilterModalVisible] = useState(false);
  const [activeDateType, setActiveDateType] = useState(null); // 'start' | 'end'
  
  // Calendar marking
  const [markedDates, setMarkedDates] = useState({});

  useEffect(() => {
    const handler = setTimeout(() => {
      setDebouncedQuery(searchQuery);
    }, 500);
    return () => clearTimeout(handler);
  }, [searchQuery]);

  const fetchProfiles = useCallback(async (pageNum = 1, shouldRefresh = false) => {
    try {
      if (pageNum === 1) {
        if (!shouldRefresh) setLoading(true);
      } else {
        setLoadingMore(true);
      }

      const params = {
        page: pageNum,
        q: debouncedQuery,
      };

      if (startDate) params.start_date = startDate;
      if (endDate) params.end_date = endDate;

      const response = await apiClient.searchMuthowifs(user.token, params);
      
      if (pageNum === 1) {
        setProfiles(response.data || []);
      } else {
        setProfiles(prev => [...prev, ...(response.data || [])]);
      }
      setLastPage(response.last_page || 1);
    } catch (error) {
      console.error('Fetch Muthowifs Error:', error);
      Alert.alert('Pencarian Gagal', error.message || 'Terjadi kesalahan saat memuat data muthowif.');
    } finally {
      setLoading(false);
      setLoadingMore(false);
      setRefreshing(false);
    }
  }, [debouncedQuery, startDate, endDate, user.token]);

  useEffect(() => {
    setPage(1);
    fetchProfiles(1);
  }, [debouncedQuery, startDate, endDate, fetchProfiles]);

  const handleRefresh = () => {
    setRefreshing(true);
    setPage(1);
    fetchProfiles(1, true);
  };

  const handleLoadMore = () => {
    if (!loadingMore && page < lastPage) {
      const nextPage = page + 1;
      setPage(nextPage);
      fetchProfiles(nextPage);
    }
  };

  const handleDayPress = (day) => {
    const dateString = day.dateString;
    
    if (activeDateType === 'start') {
      setStartDate(dateString);
      if (endDate && new Date(dateString) > new Date(endDate)) {
        setEndDate(null);
      }
      setFilterModalVisible(false);
    } else {
      setEndDate(dateString);
      if (startDate && new Date(dateString) < new Date(startDate)) {
        setStartDate(dateString);
      }
      setFilterModalVisible(false);
    }
  };

  const clearDateFilter = () => {
    setStartDate(null);
    setEndDate(null);
  };

  const renderSkeleton = () => (
    <View style={styles.listContent}>
      {[1, 2, 3, 4].map(i => (
        <SkeletonCard key={i} style={styles.muthowifCard}>
          <View style={{ flexDirection: 'row', gap: 16 }}>
            <Skeleton width={70} height={70} borderRadius={24} />
            <View style={{ flex: 1 }}>
              <SkeletonText width="60%" height={16} />
              <Skeleton width={50} height={18} borderRadius={8} style={{ marginBottom: 12 }} />
              <SkeletonText width="80%" height={12} style={{ marginBottom: 0 }} />
            </View>
          </View>
        </SkeletonCard>
      ))}
    </View>
  );

  const renderItem = ({ item }) => (
    <TouchableOpacity 
      style={styles.muthowifCard} 
      activeOpacity={0.7} 
      onPress={() => navigation?.navigate('MuthowifDetail', { id: item.id, start_date: startDate, end_date: endDate })}
    >
      <Image source={{ uri: item.avatar }} style={styles.muthowifAvatar} />
      <View style={styles.muthowifInfo}>
        <View style={styles.muthowifHeaderRow}>
          <Text style={styles.muthowifName}>{item.name}</Text>
          <View style={styles.muthowifRatingWrap}>
            <Ionicons name="star" size={14} color="#F59E0B" />
            <Text style={styles.muthowifRating}>{item.rating}</Text>
          </View>
        </View>
        <Text style={styles.muthowifReviews}>Dari {item.reviews} ulasan jamaah</Text>
        
        <View style={styles.muthowifTags}>
          <View style={styles.muthowifTag}>
            <Ionicons name="location" size={12} color="#0984e3" />
            <Text style={styles.muthowifTagText}>{item.location}</Text>
          </View>
          {item.languages && item.languages.map((lang, idx) => (
            <View key={idx} style={styles.muthowifTag}>
              <Text style={styles.muthowifTagText}>{lang}</Text>
            </View>
          ))}
        </View>
        
        <View style={styles.priceRow}>
          <Text style={styles.priceLabel}>Mulai dari</Text>
          <Text style={styles.priceValue}>Rp {Number(item.start_price).toLocaleString('id-ID')}</Text>
        </View>
      </View>
    </TouchableOpacity>
  );

  return (
    <SwipeableScreen onSwipeBack={() => navigation.goBack()}>
      <SafeAreaView style={styles.container}>
        <StatusBar style="dark" />
        
        {/* Header & Search */}
        <View style={styles.header}>
          <View style={styles.headerTop}>
            <TouchableOpacity onPress={() => navigation.goBack()} style={styles.backBtn}>
              <Ionicons name="arrow-back" size={24} color="#0F172A" />
            </TouchableOpacity>
            <Text style={styles.headerTitle}>Cari Muthowif</Text>
            <View style={{ width: 24 }} />
          </View>

          <View style={styles.dateContainer}>
            <TouchableOpacity 
              style={styles.dateBlock} 
              activeOpacity={0.7}
              onPress={() => { setActiveDateType('start'); setFilterModalVisible(true); }}
            >
              <Text style={styles.dateBlockLabel}>Keberangkatan</Text>
              <Text style={[styles.dateBlockValue, !startDate && { color: '#94A3B8' }]}>
                {startDate ? new Date(startDate).toLocaleDateString('id-ID', {day:'numeric', month:'short', year:'numeric'}) : 'Pilih Tanggal'}
              </Text>
            </TouchableOpacity>

            <View style={styles.dateDivider} />

            <TouchableOpacity 
              style={styles.dateBlock} 
              activeOpacity={0.7}
              onPress={() => { setActiveDateType('end'); setFilterModalVisible(true); }}
            >
              <Text style={styles.dateBlockLabel}>Kepulangan</Text>
              <Text style={[styles.dateBlockValue, !endDate && { color: '#94A3B8' }]}>
                {endDate ? new Date(endDate).toLocaleDateString('id-ID', {day:'numeric', month:'short', year:'numeric'}) : 'Pilih Tanggal'}
              </Text>
            </TouchableOpacity>

            {(startDate || endDate) && (
              <TouchableOpacity onPress={clearDateFilter} style={styles.clearDateBlockBtn}>
                <Ionicons name="close-circle" size={20} color="#CBD5E1" />
              </TouchableOpacity>
            )}
          </View>

          <View style={styles.optionalSearchRow}>
            <Ionicons name="search" size={18} color="#94A3B8" style={styles.optionalSearchIcon} />
            <TextInput 
              style={styles.optionalSearchInput}
              placeholder="Cari nama muthowif (opsional)"
              placeholderTextColor="#94A3B8"
              value={searchQuery}
              onChangeText={setSearchQuery}
            />
            {searchQuery.length > 0 && (
              <TouchableOpacity onPress={() => setSearchQuery('')} style={styles.clearBtn}>
                <Ionicons name="close-circle" size={18} color="#CBD5E1" />
              </TouchableOpacity>
            )}
          </View>
        </View>

        {/* List */}
        {loading ? renderSkeleton() : (
          <FlatList
            data={profiles}
            keyExtractor={item => item.id.toString()}
            renderItem={renderItem}
            contentContainerStyle={styles.listContent}
            showsVerticalScrollIndicator={false}
            refreshControl={<RefreshControl refreshing={refreshing} onRefresh={handleRefresh} color="#0984e3" />}
            onEndReached={handleLoadMore}
            onEndReachedThreshold={0.5}
            ListFooterComponent={loadingMore ? <ActivityIndicator size="small" color="#0984e3" style={{ marginVertical: 20 }} /> : null}
            ListEmptyComponent={
              <View style={styles.emptyState}>
                <Ionicons name="search-outline" size={64} color="#E2E8F0" />
                <Text style={styles.emptyTitle}>Muthowif Tidak Ditemukan</Text>
                <Text style={styles.emptyText}>Coba gunakan kata kunci lain atau ubah filter tanggal ketersediaan Anda.</Text>
              </View>
            }
          />
        )}

        {/* Date Filter Modal */}
        <Modal
          visible={filterModalVisible}
          animationType="slide"
          transparent={true}
          onRequestClose={() => setFilterModalVisible(false)}
        >
          <View style={styles.modalOverlay}>
            <View style={styles.modalContent}>
              <View style={styles.modalHeader}>
                <Text style={styles.modalTitle}>{activeDateType === 'start' ? 'Pilih Keberangkatan' : 'Pilih Kepulangan'}</Text>
                <TouchableOpacity onPress={() => setFilterModalVisible(false)}>
                  <Ionicons name="close" size={24} color="#0F172A" />
                </TouchableOpacity>
              </View>
              
              <View style={{ height: height * 0.5 }}>
                <CalendarList
                  minDate={new Date().toISOString().split('T')[0]}
                  current={activeDateType === 'end' && endDate ? endDate : startDate || undefined}
                  markedDates={{
                    [(activeDateType === 'end' ? endDate : startDate)]: { selected: true, selectedColor: '#0984e3' }
                  }}
                  onDayPress={handleDayPress}
                  pastScrollRange={0}
                  futureScrollRange={12}
                  theme={{
                    calendarBackground: '#ffffff',
                    textSectionTitleColor: '#94A3B8',
                    selectedDayBackgroundColor: '#0984e3',
                    selectedDayTextColor: '#ffffff',
                    todayTextColor: '#0984e3',
                    dayTextColor: '#1E293B',
                    textDisabledColor: '#E2E8F0',
                    dotColor: '#0984e3',
                    selectedDotColor: '#ffffff',
                    arrowColor: '#0984e3',
                    monthTextColor: '#0F172A',
                    indicatorColor: '#0984e3',
                    textDayFontWeight: '500',
                    textMonthFontWeight: '800',
                    textDayHeaderFontWeight: '700',
                    textDayFontSize: 14,
                    textMonthFontSize: 16,
                    textDayHeaderFontSize: 12
                  }}
                />
              </View>
            </View>
          </View>
        </Modal>

      </SafeAreaView>
    </SwipeableScreen>
  );
}

const styles = StyleSheet.create({
  container: { flex: 1, backgroundColor: '#F8FAFC' },
  
  header: {
    paddingHorizontal: 20,
    paddingTop: 10,
    paddingBottom: 20,
    backgroundColor: '#FFFFFF',
    borderBottomWidth: 1,
    borderBottomColor: '#F1F5F9',
  },
  headerTop: {
    flexDirection: 'row',
    alignItems: 'center',
    justifyContent: 'space-between',
    marginBottom: 20,
  },
  backBtn: { padding: 4 },
  headerTitle: {
    fontSize: 20,
    fontWeight: '800',
    color: '#0F172A',
    letterSpacing: -0.5,
  },
  dateContainer: {
    flexDirection: 'row',
    alignItems: 'center',
    backgroundColor: '#FFFFFF',
    borderWidth: 1,
    borderColor: '#E2E8F0',
    borderRadius: 16,
    marginBottom: 12,
    shadowColor: '#000',
    shadowOffset: { width: 0, height: 2 },
    shadowOpacity: 0.02,
    shadowRadius: 5,
    elevation: 2,
    height: 70,
  },
  dateBlock: {
    flex: 1,
    paddingHorizontal: 16,
    justifyContent: 'center',
    height: '100%',
  },
  dateDivider: {
    width: 1,
    height: '60%',
    backgroundColor: '#E2E8F0',
  },
  dateBlockLabel: {
    fontSize: 12,
    color: '#64748B',
    fontWeight: '700',
    marginBottom: 4,
    textTransform: 'uppercase',
  },
  dateBlockValue: {
    fontSize: 14,
    color: '#0F172A',
    fontWeight: '800',
  },
  clearDateBlockBtn: {
    padding: 12,
    position: 'absolute',
    right: 4,
    top: 14,
  },

  optionalSearchRow: {
    flexDirection: 'row',
    alignItems: 'center',
    backgroundColor: '#F8FAFC',
    borderRadius: 12,
    paddingHorizontal: 14,
    height: 46,
    borderWidth: 1,
    borderColor: '#F1F5F9',
  },
  optionalSearchIcon: { marginRight: 10 },
  optionalSearchInput: {
    flex: 1,
    fontSize: 14,
    color: '#0F172A',
    height: '100%',
  },
  clearBtn: { padding: 4 },

  listContent: {
    paddingHorizontal: 20,
    paddingTop: 20,
    paddingBottom: 40,
  },

  muthowifCard: {
    backgroundColor: '#FFFFFF',
    borderRadius: 24,
    padding: 18,
    borderWidth: 1,
    borderColor: '#F1F5F9',
    shadowColor: '#000',
    shadowOffset: { width: 0, height: 6 },
    shadowOpacity: 0.04,
    shadowRadius: 15,
    elevation: 3,
    marginBottom: 16,
    flexDirection: 'row',
    gap: 16,
    alignItems: 'flex-start',
  },
  muthowifAvatar: {
    width: 70,
    height: 70,
    borderRadius: 24,
    backgroundColor: '#F1F5F9',
  },
  muthowifInfo: {
    flex: 1,
  },
  muthowifHeaderRow: {
    flexDirection: 'row',
    justifyContent: 'space-between',
    alignItems: 'center',
    marginBottom: 4,
  },
  muthowifName: {
    fontSize: 17,
    fontWeight: '800',
    color: '#0F172A',
    letterSpacing: -0.3,
    flex: 1,
  },
  muthowifRatingWrap: {
    flexDirection: 'row',
    alignItems: 'center',
    backgroundColor: '#FFFBEB',
    paddingHorizontal: 6,
    paddingVertical: 2,
    borderRadius: 8,
    gap: 4,
  },
  muthowifRating: {
    fontSize: 13,
    fontWeight: '800',
    color: '#D97706',
  },
  muthowifReviews: {
    fontSize: 12,
    color: '#64748B',
    marginBottom: 12,
  },
  muthowifTags: {
    flexDirection: 'row',
    flexWrap: 'wrap',
    gap: 8,
    marginBottom: 14,
  },
  muthowifTag: {
    flexDirection: 'row',
    alignItems: 'center',
    backgroundColor: '#F8FAFC',
    paddingHorizontal: 10,
    paddingVertical: 6,
    borderRadius: 8,
    gap: 4,
    borderWidth: 1,
    borderColor: '#F1F5F9',
  },
  muthowifTagText: {
    fontSize: 11,
    color: '#475569',
    fontWeight: '600',
  },
  priceRow: {
    flexDirection: 'row',
    justifyContent: 'space-between',
    alignItems: 'center',
    borderTopWidth: 1,
    borderTopColor: '#F1F5F9',
    paddingTop: 12,
  },
  priceLabel: {
    fontSize: 11,
    color: '#94A3B8',
    fontWeight: '600',
  },
  priceValue: {
    fontSize: 15,
    color: '#0984e3',
    fontWeight: '800',
  },

  emptyState: {
    alignItems: 'center',
    justifyContent: 'center',
    paddingTop: 60,
    paddingHorizontal: 40,
  },
  emptyTitle: {
    fontSize: 18,
    fontWeight: '800',
    color: '#0F172A',
    marginTop: 16,
    marginBottom: 8,
  },
  emptyText: {
    fontSize: 14,
    color: '#64748B',
    textAlign: 'center',
    lineHeight: 22,
  },

  modalOverlay: {
    flex: 1,
    backgroundColor: 'rgba(15, 23, 42, 0.4)',
    justifyContent: 'flex-end',
  },
  modalContent: {
    backgroundColor: '#FFFFFF',
    borderTopLeftRadius: 32,
    borderTopRightRadius: 32,
    paddingTop: 24,
    paddingBottom: Platform.OS === 'ios' ? 40 : 24,
    minHeight: height * 0.7,
  },
  modalHeader: {
    flexDirection: 'row',
    justifyContent: 'space-between',
    alignItems: 'center',
    paddingHorizontal: 24,
    marginBottom: 8,
  },
  modalTitle: {
    fontSize: 20,
    fontWeight: '800',
    color: '#0F172A',
    letterSpacing: -0.5,
  },
  modalSub: {
    fontSize: 13,
    color: '#64748B',
    paddingHorizontal: 24,
    marginBottom: 20,
    lineHeight: 20,
  },
  modalActions: {
    flexDirection: 'row',
    gap: 12,
    paddingHorizontal: 24,
    paddingTop: 20,
    borderTopWidth: 1,
    borderTopColor: '#F1F5F9',
  },
  btnOutline: {
    flex: 1,
    height: 52,
    borderRadius: 16,
    borderWidth: 1,
    borderColor: '#E2E8F0',
    justifyContent: 'center',
    alignItems: 'center',
  },
  btnOutlineText: {
    fontSize: 15,
    fontWeight: '700',
    color: '#64748B',
  },
  btnPrimary: {
    flex: 2,
    height: 52,
    borderRadius: 16,
    backgroundColor: '#0984e3',
    justifyContent: 'center',
    alignItems: 'center',
  },
  btnPrimaryText: {
    fontSize: 15,
    fontWeight: '700',
    color: '#FFFFFF',
  },
});
