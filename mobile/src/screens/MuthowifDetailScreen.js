import React, { useState, useEffect, useCallback } from 'react';
import { 
  StyleSheet, 
  Text, 
  View, 
  TouchableOpacity, 
  ScrollView, 
  Image,
  Dimensions,
  ActivityIndicator,
  Alert
} from 'react-native';
import { SafeAreaView, useSafeAreaInsets } from 'react-native-safe-area-context';
import { StatusBar } from 'expo-status-bar';
import { Ionicons } from '@expo/vector-icons';
import { apiClient } from '../api/client';
import SwipeableScreen from '../components/SwipeableScreen';
import { Skeleton, SkeletonText } from '../components/Skeleton';
import { LinearGradient } from 'expo-linear-gradient';

const { width, height } = Dimensions.get('window');

export default function MuthowifDetailScreen({ route, navigation, user }) {
  const { id, start_date, end_date } = route.params || {};
  const insets = useSafeAreaInsets();
  
  const [loading, setLoading] = useState(true);
  const [data, setData] = useState(null);
  const [activeTab, setActiveTab] = useState('tentang'); // tentang, layanan, ulasan
  
  const [startDate, setStartDate] = useState(start_date || null);
  const [endDate, setEndDate] = useState(end_date || null);


  const fetchDetail = useCallback(async (sd, ed) => {
    try {
      setLoading(true);
      const params = {};
      if (sd) params.start_date = sd;
      if (ed) params.end_date = ed;
      
      const res = await apiClient.getMuthowifDetail(user.token, id, params);
      setData(res);
    } catch (error) {
      console.error('Fetch Detail Error:', error);
      Alert.alert('Gagal Memuat', error.message || 'Terjadi kesalahan');
    } finally {
      setLoading(false);
    }
  }, [id, user.token]);

  useEffect(() => {
    fetchDetail(startDate, endDate);
  }, [fetchDetail, startDate, endDate]);

  const handleBooking = () => {
    if (!startDate || !endDate) {
      Alert.alert(
        'Tanggal Belum Dipilih', 
        'Harap tentukan tanggal keberangkatan dan kepulangan Anda di halaman pencarian terlebih dahulu.',
        [{ text: 'Kembali ke Pencarian', onPress: () => navigation.goBack() }, { text: 'OK' }]
      );
      return;
    }

    if (!data?.bookingIntent?.can_submit) {
      Alert.alert('Tidak dapat memesan', data?.bookingIntent?.reason || 'Muthowif tidak tersedia pada tanggal tersebut.');
      return;
    }
    
    // Proceed to checkout screen
    navigation.navigate('Checkout', {
      id,
      start_date: startDate,
      end_date: endDate,
      profile: data.profile,
      services: data.services
    });
  };

  if (loading && !data) {
    return (
      <SafeAreaView style={styles.container}>
        <View style={styles.headerRow}>
          <Skeleton width={40} height={40} borderRadius={20} />
        </View>
        <ScrollView contentContainerStyle={{ padding: 20 }}>
          <Skeleton width={width - 40} height={200} borderRadius={24} style={{ marginBottom: 20 }} />
          <SkeletonText width="60%" height={24} style={{ marginBottom: 12 }} />
          <SkeletonText width="40%" height={16} style={{ marginBottom: 24 }} />
          <View style={{ flexDirection: 'row', gap: 10, marginBottom: 30 }}>
            <Skeleton width={80} height={35} borderRadius={20} />
            <Skeleton width={80} height={35} borderRadius={20} />
            <Skeleton width={80} height={35} borderRadius={20} />
          </View>
          <SkeletonText width="100%" height={14} />
          <SkeletonText width="100%" height={14} />
          <SkeletonText width="80%" height={14} />
        </ScrollView>
      </SafeAreaView>
    );
  }

  if (!data) return null;

  const profile = data.profile;
  const services = data.services || [];
  const reviews = data.reviews || [];

  return (
    <SwipeableScreen onSwipeBack={() => navigation.goBack()}>
      <View style={styles.container}>
        <StatusBar style="light" />
        
        <ScrollView showsVerticalScrollIndicator={false} contentContainerStyle={{ paddingBottom: 120 }}>
          {/* Hero Cover Image */}
          <View style={styles.heroWrap}>
            <LinearGradient 
              colors={['#0F172A', '#1E3A8A', '#0984e3']} 
              start={{ x: 0, y: 0 }}
              end={{ x: 1, y: 1 }}
              style={StyleSheet.absoluteFillObject} 
            />
            
            <SafeAreaView edges={['top']} style={styles.heroNav}>
              <TouchableOpacity onPress={() => navigation.goBack()} style={styles.backBtnWrapper}>
                <Ionicons name="arrow-back" size={24} color="#FFFFFF" />
              </TouchableOpacity>
            </SafeAreaView>

            <View style={styles.heroContent}>
              <Image source={{ uri: profile.avatar }} style={styles.avatarMain} />
              <View style={styles.heroInfo}>
                <Text style={styles.heroName}>{profile.name}</Text>
                <View style={styles.verifiedBadge}>
                  <Ionicons name="checkmark-circle" size={14} color="#10B981" />
                  <Text style={styles.verifiedText}>Verified Muthowif</Text>
                </View>
              </View>
            </View>
          </View>

          {/* Key Stats Row */}
          <View style={styles.statsCard}>
            <View style={styles.statItem}>
              <Ionicons name="star" size={22} color="#F59E0B" />
              <Text style={styles.statValue}>{profile.rating}</Text>
              <Text style={styles.statLabel}>{profile.reviews_count} Ulasan</Text>
            </View>
            <View style={styles.statDivider} />
            <View style={styles.statItem}>
              <Ionicons name="location" size={22} color="#0984e3" />
              <Text style={styles.statValue}>Makkah</Text>
              <Text style={styles.statLabel}>& Madinah</Text>
            </View>
            <View style={styles.statDivider} />
            <View style={styles.statItem}>
              <Ionicons name="briefcase" size={22} color="#10B981" />
              <Text style={styles.statValue}>{profile.confirmed_bookings}</Text>
              <Text style={styles.statLabel}>Tugas Selesai</Text>
            </View>
          </View>

          {/* Content Area */}
          <View style={styles.contentWrap}>
            {/* Custom Tabs */}
            <View style={styles.tabHeader}>
              {['tentang', 'layanan', 'ulasan'].map((tab) => (
                <TouchableOpacity 
                  key={tab} 
                  style={[styles.tabBtn, activeTab === tab && styles.tabBtnActive]}
                  onPress={() => setActiveTab(tab)}
                >
                  <Text style={[styles.tabText, activeTab === tab && styles.tabTextActive]}>
                    {tab.charAt(0).toUpperCase() + tab.slice(1)}
                  </Text>
                </TouchableOpacity>
              ))}
            </View>

            {/* Tab: Tentang */}
            {activeTab === 'tentang' && (
              <View style={styles.tabContent}>
                <Text style={styles.sectionTitle}>Biografi</Text>
                <Text style={styles.bioText}>{profile.bio || 'Muthowif ini belum menuliskan biografi.'}</Text>
                
                <Text style={[styles.sectionTitle, { marginTop: 24 }]}>Bahasa yang Dikuasai</Text>
                <View style={styles.langWrap}>
                  {profile.languages.map((lang, i) => (
                    <View key={i} style={styles.langPill}>
                      <Ionicons name="language" size={16} color="#0984e3" />
                      <Text style={styles.langPillText}>{lang}</Text>
                    </View>
                  ))}
                </View>
              </View>
            )}

            {/* Tab: Layanan */}
            {activeTab === 'layanan' && (
              <View style={styles.tabContent}>
                {services.length > 0 ? services.map(srv => (
                  <View key={srv.id} style={styles.serviceCard}>
                    <View style={styles.serviceHeader}>
                      <Text style={styles.serviceName}>{srv.name}</Text>
                      <View style={styles.serviceTypeBadge}>
                        <Text style={styles.serviceTypeText}>{srv.type.toUpperCase()}</Text>
                      </View>
                    </View>
                    <Text style={styles.serviceDesc}>{srv.description}</Text>
                    
                    <View style={styles.serviceMetaRow}>
                      <View style={styles.serviceMetaItem}>
                        <Ionicons name="time-outline" size={16} color="#64748B" />
                        <Text style={styles.serviceMetaText}>{srv.duration_hours} Jam</Text>
                      </View>
                      <View style={styles.serviceMetaItem}>
                        <Ionicons name="people-outline" size={16} color="#64748B" />
                        <Text style={styles.serviceMetaText}>Maks {srv.max_pax} Pax</Text>
                      </View>
                    </View>
                    
                    <View style={styles.servicePriceRow}>
                      <Text style={styles.servicePriceLabel}>Harga Paket</Text>
                      <Text style={styles.servicePrice}>Rp {Number(srv.price).toLocaleString('id-ID')}</Text>
                    </View>

                    {/* Akomodasi & Transportasi Section */}
                    {(srv.same_hotel_price_per_day > 0 || srv.transport_price_flat > 0) && (
                      <View style={styles.addOnsContainer}>
                        <Text style={styles.addOnsTitle}>Akomodasi & Transportasi</Text>
                        
                        {srv.same_hotel_price_per_day > 0 && (
                          <View style={styles.addonItem}>
                            <View style={styles.addonLeft}>
                              <Ionicons name="bed" size={18} color="#0984e3" />
                              <Text style={styles.addonName}>Muthowif 1 Hotel dengan Jamaah</Text>
                            </View>
                            <Text style={styles.addonPrice}>+ Rp {Number(srv.same_hotel_price_per_day).toLocaleString('id-ID')}/malam</Text>
                          </View>
                        )}
                        
                        {srv.transport_price_flat > 0 && (
                          <View style={styles.addonItem}>
                            <View style={styles.addonLeft}>
                              <Ionicons name="car" size={18} color="#0984e3" />
                              <Text style={styles.addonName}>Transportasi Antar Jemput</Text>
                            </View>
                            <Text style={styles.addonPrice}>+ Rp {Number(srv.transport_price_flat).toLocaleString('id-ID')}</Text>
                          </View>
                        )}
                      </View>
                    )}

                    {/* Add-Ons Section */}
                    {srv.add_ons && srv.add_ons.length > 0 && (
                      <View style={srv.same_hotel_price_per_day > 0 || srv.transport_price_flat > 0 ? styles.addOnsContainerNoBorder : styles.addOnsContainer}>
                        <Text style={styles.addOnsTitle}>Layanan Tambahan (Add-ons)</Text>
                        {srv.add_ons.map((addon) => (
                          <View key={addon.id} style={styles.addonItem}>
                            <View style={styles.addonLeft}>
                              <Ionicons name="add-circle" size={18} color="#10B981" />
                              <Text style={styles.addonName}>{addon.name}</Text>
                            </View>
                            <Text style={styles.addonPrice}>+ Rp {Number(addon.price).toLocaleString('id-ID')}</Text>
                          </View>
                        ))}
                      </View>
                    )}
                  </View>
                )) : (
                  <Text style={styles.emptyText}>Belum ada layanan yang tersedia.</Text>
                )}
              </View>
            )}

            {/* Tab: Ulasan */}
            {activeTab === 'ulasan' && (
              <View style={styles.tabContent}>
                {reviews.length > 0 ? reviews.map(rev => (
                  <View key={rev.id} style={styles.reviewCard}>
                    <View style={styles.reviewHeader}>
                      <Image source={{ uri: rev.customer_avatar }} style={styles.reviewAvatar} />
                      <View style={styles.reviewInfo}>
                        <Text style={styles.reviewName}>{rev.customer_name}</Text>
                        <Text style={styles.reviewDate}>{rev.created_at}</Text>
                      </View>
                      <View style={styles.reviewRatingWrap}>
                        <Ionicons name="star" size={12} color="#F59E0B" />
                        <Text style={styles.reviewRatingText}>{rev.rating}</Text>
                      </View>
                    </View>
                    <Text style={styles.reviewBody}>{rev.comment}</Text>
                  </View>
                )) : (
                  <Text style={styles.emptyText}>Belum ada ulasan dari jamaah.</Text>
                )}
              </View>
            )}
          </View>
        </ScrollView>

        {/* Sticky Bottom Bar */}
        <View style={[styles.bottomBar, { paddingBottom: Math.max(insets.bottom, 20) }]}>
          <View style={styles.bottomBarLeft}>
            <Text style={styles.bottomBarLabel}>Mulai dari</Text>
            <Text style={styles.bottomBarPrice}>Rp {Number(profile.start_price).toLocaleString('id-ID')}</Text>
          </View>
          <TouchableOpacity style={styles.bookingBtn} activeOpacity={0.8} onPress={handleBooking}>
            <Text style={styles.bookingBtnText}>Lanjutkan Pemesanan</Text>
            <Ionicons name="arrow-forward" size={18} color="#FFFFFF" />
          </TouchableOpacity>
        </View>

      </View>
    </SwipeableScreen>
  );
}

const styles = StyleSheet.create({
  container: { flex: 1, backgroundColor: '#F8FAFC' },
  headerRow: { paddingHorizontal: 20, paddingTop: 10, paddingBottom: 10 },
  
  heroWrap: {
    height: 280,
    justifyContent: 'flex-end',
    padding: 20,
    paddingBottom: 40,
  },
  heroImage: {
    ...StyleSheet.absoluteFillObject,
    width: '100%',
    height: '100%',
  },
  heroNav: {
    position: 'absolute',
    top: 0,
    left: 20,
    right: 20,
    zIndex: 10,
  },
  backBtnWrapper: {
    width: 44,
    height: 44,
    borderRadius: 22,
    backgroundColor: 'rgba(15, 23, 42, 0.4)',
    justifyContent: 'center',
    alignItems: 'center',
    marginTop: 10,
  },
  heroContent: {
    flexDirection: 'row',
    alignItems: 'flex-end',
    gap: 16,
  },
  avatarMain: {
    width: 90,
    height: 90,
    borderRadius: 30,
    borderWidth: 4,
    borderColor: '#FFFFFF',
    backgroundColor: '#F1F5F9',
  },
  heroInfo: {
    flex: 1,
    paddingBottom: 4,
  },
  heroName: {
    fontSize: 24,
    fontWeight: '800',
    color: '#FFFFFF',
    letterSpacing: -0.5,
    marginBottom: 4,
  },
  verifiedBadge: {
    flexDirection: 'row',
    alignItems: 'center',
    backgroundColor: 'rgba(255, 255, 255, 0.2)',
    paddingHorizontal: 8,
    paddingVertical: 4,
    borderRadius: 8,
    alignSelf: 'flex-start',
    gap: 4,
  },
  verifiedText: {
    color: '#FFFFFF',
    fontSize: 11,
    fontWeight: '700',
  },

  statsCard: {
    flexDirection: 'row',
    backgroundColor: '#FFFFFF',
    marginHorizontal: 20,
    borderRadius: 24,
    padding: 20,
    marginTop: -25, // Overlaps hero
    shadowColor: '#000',
    shadowOffset: { width: 0, height: 8 },
    shadowOpacity: 0.05,
    shadowRadius: 20,
    elevation: 4,
    justifyContent: 'space-between',
    alignItems: 'center',
  },
  statItem: { alignItems: 'center', flex: 1 },
  statValue: { fontSize: 16, fontWeight: '800', color: '#0F172A', marginTop: 6 },
  statLabel: { fontSize: 11, color: '#64748B', marginTop: 2 },
  statDivider: { width: 1, height: 40, backgroundColor: '#F1F5F9' },

  contentWrap: {
    padding: 20,
  },
  tabHeader: {
    flexDirection: 'row',
    backgroundColor: '#F1F5F9',
    borderRadius: 16,
    padding: 4,
    marginBottom: 24,
  },
  tabBtn: {
    flex: 1,
    paddingVertical: 10,
    alignItems: 'center',
    borderRadius: 12,
  },
  tabBtnActive: {
    backgroundColor: '#FFFFFF',
    shadowColor: '#000',
    shadowOffset: { width: 0, height: 2 },
    shadowOpacity: 0.05,
    shadowRadius: 5,
    elevation: 2,
  },
  tabText: {
    fontSize: 14,
    fontWeight: '600',
    color: '#64748B',
  },
  tabTextActive: {
    color: '#0F172A',
    fontWeight: '800',
  },

  tabContent: {
    minHeight: 200,
  },
  sectionTitle: {
    fontSize: 18,
    fontWeight: '800',
    color: '#0F172A',
    marginBottom: 12,
  },
  bioText: {
    fontSize: 15,
    lineHeight: 24,
    color: '#475569',
  },
  langWrap: {
    flexDirection: 'row',
    flexWrap: 'wrap',
    gap: 10,
  },
  langPill: {
    flexDirection: 'row',
    alignItems: 'center',
    backgroundColor: '#F0F9FF',
    paddingHorizontal: 12,
    paddingVertical: 8,
    borderRadius: 12,
    gap: 6,
    borderWidth: 1,
    borderColor: '#E0F2FE',
  },
  langPillText: {
    fontSize: 14,
    fontWeight: '700',
    color: '#0369A1',
  },

  serviceCard: {
    backgroundColor: '#FFFFFF',
    borderRadius: 20,
    padding: 20,
    marginBottom: 16,
    borderWidth: 1,
    borderColor: '#F1F5F9',
  },
  serviceHeader: {
    flexDirection: 'row',
    justifyContent: 'space-between',
    alignItems: 'flex-start',
    marginBottom: 8,
  },
  serviceName: {
    fontSize: 16,
    fontWeight: '800',
    color: '#0F172A',
    flex: 1,
  },
  serviceTypeBadge: {
    backgroundColor: '#F1F5F9',
    paddingHorizontal: 8,
    paddingVertical: 4,
    borderRadius: 6,
    marginLeft: 12,
  },
  serviceTypeText: {
    fontSize: 10,
    fontWeight: '700',
    color: '#64748B',
  },
  serviceDesc: {
    fontSize: 13,
    lineHeight: 20,
    color: '#64748B',
    marginBottom: 16,
  },
  serviceMetaRow: {
    flexDirection: 'row',
    gap: 16,
    marginBottom: 16,
  },
  serviceMetaItem: {
    flexDirection: 'row',
    alignItems: 'center',
    gap: 4,
  },
  serviceMetaText: {
    fontSize: 12,
    color: '#475569',
    fontWeight: '600',
  },
  serviceMetaText: {
    fontSize: 12,
    color: '#475569',
    fontWeight: '600',
  },
  servicePriceRow: {
    flexDirection: 'row',
    justifyContent: 'space-between',
    alignItems: 'center',
    marginTop: 8,
  },
  servicePriceLabel: {
    fontSize: 12,
    color: '#64748B',
    fontWeight: '700',
  },
  servicePrice: {
    fontSize: 18,
    fontWeight: '800',
    color: '#0984e3',
  },
  addOnsContainer: {
    marginTop: 16,
    paddingTop: 16,
    borderTopWidth: 1,
    borderTopColor: '#E2E8F0',
    borderStyle: 'dashed',
  },
  addOnsContainerNoBorder: {
    marginTop: 16,
  },
  addOnsTitle: {
    fontSize: 13,
    fontWeight: '700',
    color: '#0F172A',
    marginBottom: 10,
  },
  addonItem: {
    flexDirection: 'row',
    justifyContent: 'space-between',
    alignItems: 'center',
    marginBottom: 8,
  },
  addonLeft: {
    flexDirection: 'row',
    alignItems: 'center',
    gap: 8,
    flex: 1,
  },
  addonName: {
    fontSize: 13,
    color: '#475569',
    flex: 1,
  },
  addonPrice: {
    fontSize: 13,
    fontWeight: '700',
    color: '#10B981',
  },

  reviewCard: {
    backgroundColor: '#FFFFFF',
    borderRadius: 20,
    padding: 20,
    marginBottom: 16,
    borderWidth: 1,
    borderColor: '#F1F5F9',
  },
  reviewHeader: {
    flexDirection: 'row',
    alignItems: 'center',
    marginBottom: 12,
  },
  reviewAvatar: {
    width: 40,
    height: 40,
    borderRadius: 20,
    backgroundColor: '#F1F5F9',
    marginRight: 12,
  },
  reviewInfo: {
    flex: 1,
  },
  reviewName: {
    fontSize: 15,
    fontWeight: '800',
    color: '#0F172A',
  },
  reviewDate: {
    fontSize: 12,
    color: '#94A3B8',
    marginTop: 2,
  },
  reviewRatingWrap: {
    flexDirection: 'row',
    alignItems: 'center',
    backgroundColor: '#FFFBEB',
    paddingHorizontal: 8,
    paddingVertical: 4,
    borderRadius: 8,
    gap: 4,
  },
  reviewRatingText: {
    fontSize: 12,
    fontWeight: '800',
    color: '#D97706',
  },
  reviewBody: {
    fontSize: 14,
    lineHeight: 22,
    color: '#475569',
  },
  emptyText: {
    color: '#94A3B8',
    textAlign: 'center',
    marginTop: 30,
  },

  bottomBar: {
    position: 'absolute',
    bottom: 0,
    left: 0,
    right: 0,
    backgroundColor: 'rgba(255, 255, 255, 0.95)',
    flexDirection: 'row',
    paddingHorizontal: 24,
    paddingTop: 18,
    borderTopWidth: 1,
    borderTopColor: '#F1F5F9',
    alignItems: 'center',
    justifyContent: 'space-between',
  },
  bottomBarLeft: {
    flex: 1,
  },
  bottomBarLabel: {
    fontSize: 12,
    color: '#64748B',
    fontWeight: '700',
    marginBottom: 4,
  },
  bottomBarPrice: {
    fontSize: 22,
    fontWeight: '900',
    color: '#0F172A',
  },
  bookingBtn: {
    backgroundColor: '#0984e3',
    flexDirection: 'row',
    alignItems: 'center',
    justifyContent: 'center',
    paddingHorizontal: 26,
    height: 56,
    borderRadius: 18,
    gap: 10,
    shadowColor: '#0984e3',
    shadowOffset: { width: 0, height: 4 },
    shadowOpacity: 0.3,
    shadowRadius: 12,
    elevation: 6
  },
  bookingBtnText: {
    color: '#FFFFFF',
    fontSize: 16,
    fontWeight: '800',
  },

  // Modal Styles
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
    paddingBottom: 40,
  },
  modalHeader: {
    flexDirection: 'row',
    justifyContent: 'space-between',
    alignItems: 'center',
    paddingHorizontal: 24,
    marginBottom: 20,
  },
  modalTitle: {
    fontSize: 20,
    fontWeight: '800',
    color: '#0F172A',
  },
  dateContainer: {
    flexDirection: 'row',
    alignItems: 'center',
    backgroundColor: '#FFFFFF',
    borderWidth: 1,
    borderColor: '#E2E8F0',
    borderRadius: 16,
    marginHorizontal: 24,
    marginBottom: 20,
    height: 60,
  },
  dateBlock: {
    flex: 1,
    paddingHorizontal: 16,
    justifyContent: 'center',
  },
  dateDivider: {
    width: 1,
    height: '50%',
    backgroundColor: '#E2E8F0',
  },
  dateBlockLabel: {
    fontSize: 11,
    color: '#64748B',
    fontWeight: '700',
    marginBottom: 2,
    textTransform: 'uppercase',
  },
  dateBlockValue: {
    fontSize: 13,
    color: '#0F172A',
    fontWeight: '800',
  },

  // Modal Header & Selector
  modalHeader: {
    flexDirection: 'row',
    justifyContent: 'space-between',
    alignItems: 'center',
    paddingHorizontal: 24,
    marginBottom: 20,
  },
  modalTitle: {
    fontSize: 18,
    fontWeight: '800',
    color: '#0F172A',
  },
  dateSelectorContainer: {
    flexDirection: 'row',
    alignItems: 'center',
    backgroundColor: '#F8FAFC',
    borderRadius: 20,
    marginHorizontal: 24,
    marginBottom: 16,
    padding: 4,
    borderWidth: 1,
    borderColor: '#F1F5F9',
  },
  modalDateBlock: {
    flex: 1,
    paddingVertical: 12,
    alignItems: 'center',
    borderRadius: 16,
  },
  modalDateBlockActive: {
    backgroundColor: '#FFFFFF',
    shadowColor: '#000',
    shadowOpacity: 0.05,
    shadowRadius: 10,
    elevation: 2,
  },
  modalDateLabel: {
    fontSize: 10,
    fontWeight: '800',
    color: '#94A3B8',
    textTransform: 'uppercase',
    marginBottom: 2,
  },
  modalDateValue: {
    fontSize: 14,
    fontWeight: '800',
    color: '#0F172A',
  },
  calendarWrap: {
    paddingHorizontal: 12,
    marginBottom: 16,
  },
  doneBtn: {
    backgroundColor: '#0984e3',
    marginHorizontal: 24,
    height: 56,
    borderRadius: 18,
    justifyContent: 'center',
    alignItems: 'center',
    shadowColor: '#0984e3',
    shadowOpacity: 0.2,
    shadowRadius: 10,
    elevation: 4,
  },
  doneBtnDisabled: {
    backgroundColor: '#E2E8F0',
    shadowOpacity: 0,
    elevation: 0,
  },
  doneBtnText: {
    color: '#FFFFFF',
    fontSize: 16,
    fontWeight: '800',
  },
});
