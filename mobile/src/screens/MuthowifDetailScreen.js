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
  Alert,
  StatusBar
} from 'react-native';
import { SafeAreaView, useSafeAreaInsets } from 'react-native-safe-area-context';
import { Ionicons } from '@expo/vector-icons';
import { LinearGradient } from 'expo-linear-gradient';
import { apiClient } from '../api/client';
import SwipeableScreen from '../components/SwipeableScreen';
import { Skeleton, SkeletonText } from '../components/Skeleton';

const { width } = Dimensions.get('window');

export default function MuthowifDetailScreen({ route, navigation, user }) {
  const { id, start_date, end_date } = route.params || {};
  const insets = useSafeAreaInsets();
  
  const [loading, setLoading] = useState(true);
  const [data, setData] = useState(null);
  const [activeTab, setActiveTab] = useState('tentang');
  const [startDate] = useState(start_date || null);
  const [endDate] = useState(end_date || null);

  const fetchDetail = useCallback(async () => {
    try {
      setLoading(true);
      const params = {};
      if (startDate) params.start_date = startDate;
      if (endDate) params.end_date = endDate;
      
      const res = await apiClient.getMuthowifDetail(user.token, id, params);
      setData(res);
    } catch (error) {
      console.error(error);
      Alert.alert('Gagal Memuat', 'Terjadi kesalahan saat mengambil data.');
    } finally {
      setLoading(false);
    }
  }, [id, user.token, startDate, endDate]);

  useEffect(() => {
    fetchDetail();
  }, [fetchDetail]);

  const handleBooking = (service) => {
    if (!startDate || !endDate) {
      Alert.alert(
        'Tanggal Belum Dipilih', 
        'Harap tentukan tanggal keberangkatan dan kepulangan Anda di halaman pencarian terlebih dahulu.',
        [{ text: 'Kembali', onPress: () => navigation.goBack() }]
      );
      return;
    }

    if (!data?.bookingIntent?.can_submit) {
      Alert.alert('Tidak Tersedia', data?.bookingIntent?.reason || 'Muthowif tidak tersedia pada tanggal tersebut.');
      return;
    }
    
    navigation.navigate('Checkout', {
      muthowif: data.profile,
      service: service,
      startDate: startDate,
      endDate: endDate
    });
  };

  const formatIDR = (num) => num?.toString().replace(/\B(?=(\d{3})+(?!\d))/g, ".");

  if (loading && !data) {
    return (
      <View style={styles.center}>
        <ActivityIndicator size="large" color="#3B82F6" />
      </View>
    );
  }

  const profile = data.profile;

  return (
    <SwipeableScreen onSwipeBack={() => navigation.goBack()}>
      <View style={styles.container}>
        <StatusBar barStyle="light-content" translucent backgroundColor="transparent" />
        
        <ScrollView showsVerticalScrollIndicator={false} contentContainerStyle={{ paddingBottom: 120 }}>
          {/* Cover Image & Header */}
          <View style={styles.coverWrapper}>
            <Image source={{ uri: profile.avatar }} style={styles.coverImage} />
            <LinearGradient 
              colors={['rgba(0,0,0,0.4)', 'transparent', 'rgba(0,0,0,0.8)']} 
              style={StyleSheet.absoluteFill} 
            />
            <SafeAreaView style={styles.headerTop}>
              <TouchableOpacity onPress={() => navigation.goBack()} style={styles.circleBtn}>
                <Ionicons name="chevron-back" size={24} color="#FFF" />
              </TouchableOpacity>
              <TouchableOpacity style={styles.circleBtn}>
                <Ionicons name="share-outline" size={22} color="#FFF" />
              </TouchableOpacity>
            </SafeAreaView>
            
            <View style={styles.coverInfo}>
               <Text style={styles.muthowifName}>{profile.user?.name}</Text>
               <View style={styles.locationRow}>
                  <Ionicons name="location" size={16} color="#3B82F6" />
                  <Text style={styles.locationText}>{profile.location || 'Makkah, Saudi Arabia'}</Text>
               </View>
            </View>
          </View>

          {/* Stats Section */}
          <View style={styles.statsBar}>
            <View style={styles.statItem}>
              <Text style={styles.statVal}>{profile.rating}</Text>
              <View style={styles.statLabelRow}>
                <Ionicons name="star" size={12} color="#F59E0B" />
                <Text style={styles.statLbl}>Rating</Text>
              </View>
            </View>
            <View style={styles.statDivider} />
            <View style={styles.statItem}>
              <Text style={styles.statVal}>{data.reviews_count || 0}</Text>
              <Text style={styles.statLbl}>Review</Text>
            </View>
            <View style={styles.statDivider} />
            <View style={styles.statItem}>
              <Text style={styles.statVal}>{profile.experience_years || '5+'}</Text>
              <Text style={styles.statLbl}>Thn Exp</Text>
            </View>
          </View>

          {/* Tabs */}
          <View style={styles.tabContainer}>
            {['tentang', 'layanan', 'ulasan'].map((tab) => (
              <TouchableOpacity 
                key={tab} 
                style={[styles.tab, activeTab === tab && styles.tabActive]}
                onPress={() => setActiveTab(tab)}
              >
                <Text style={[styles.tabText, activeTab === tab && styles.tabTextActive]}>
                  {tab.charAt(0).toUpperCase() + tab.slice(1)}
                </Text>
              </TouchableOpacity>
            ))}
          </View>

          <View style={styles.contentPadding}>
            {activeTab === 'tentang' && (
              <View style={styles.tabContent}>
                <Text style={styles.bioTitle}>Biografi</Text>
                <Text style={styles.bioText}>{profile.bio || 'Muthowif berpengalaman yang siap membimbing perjalanan ibadah Anda dengan penuh khidmat dan sesuai sunnah.'}</Text>
                
                <Text style={styles.bioTitle}>Bahasa</Text>
                <View style={styles.tagGrid}>
                   {profile.languages?.map((lang, i) => (
                     <View key={i} style={styles.tag}><Text style={styles.tagText}>{lang}</Text></View>
                   ))}
                </View>

                <Text style={styles.bioTitle}>Pendidikan</Text>
                {profile.educations?.map((edu, i) => (
                  <View key={i} style={styles.eduRow}>
                    <Ionicons name="school-outline" size={18} color="#0984e3" />
                    <Text style={styles.eduText}>{edu}</Text>
                  </View>
                ))}
              </View>
            )}

            {activeTab === 'layanan' && (
              <View style={styles.tabContent}>
                {data.services?.map((service) => (
                  <TouchableOpacity 
                    key={service.id} 
                    style={styles.serviceCard}
                    activeOpacity={0.8}
                    onPress={() => handleBooking(service)}
                  >
                    <View style={styles.serviceHeader}>
                      <Text style={styles.serviceName}>{service.name}</Text>
                      <View style={[styles.typeBadge, { backgroundColor: service.type === 'private' ? '#F0F9FF' : '#F0FDF4' }]}>
                        <Text style={[styles.typeText, { color: service.type === 'private' ? '#0984e3' : '#166534' }]}>
                          {service.type?.toUpperCase()}
                        </Text>
                      </View>
                    </View>
                    <Text style={styles.serviceDesc} numberOfLines={2}>{service.description || 'Layanan pendampingan profesional.'}</Text>
                    <View style={styles.serviceFooter}>
                      <View>
                        <Text style={styles.servicePriceLabel}>Mulai dari</Text>
                        <Text style={styles.servicePrice}>Rp {formatIDR(service.daily_price)} <Text style={{fontSize: 12, fontWeight: '600', color: '#94A3B8'}}>/hari</Text></Text>
                      </View>
                      <View style={styles.bookIcon}><Ionicons name="chevron-forward" size={20} color="#FFF" /></View>
                    </View>
                  </TouchableOpacity>
                ))}
              </View>
            )}

            {activeTab === 'ulasan' && (
              <View style={styles.tabContent}>
                <View style={styles.emptyUlasan}>
                   <Ionicons name="chatbubbles-outline" size={48} color="#E2E8F0" />
                   <Text style={styles.emptyUlasanText}>Belum ada ulasan untuk Muthowif ini.</Text>
                </View>
              </View>
            )}
          </View>
        </ScrollView>

        <View style={[styles.stickyFooter, { paddingBottom: Math.max(insets.bottom, 24) }]}>
           <View style={{ flex: 1 }}>
              <Text style={styles.footerLabel}>Estimasi Mulai</Text>
              <Text style={styles.footerPrice}>Rp {formatIDR(data.services[0]?.daily_price || 0)} <Text style={{fontSize: 13, color: '#94A3B8'}}>/hari</Text></Text>
           </View>
           <TouchableOpacity style={styles.mainBtn} onPress={() => setActiveTab('layanan')}>
              <LinearGradient colors={['#0F172A', '#1E3A8A']} style={styles.mainBtnGradient}>
                 <Text style={styles.mainBtnText}>Pilih Layanan</Text>
              </LinearGradient>
           </TouchableOpacity>
        </View>
      </View>
    </SwipeableScreen>
  );
}

const styles = StyleSheet.create({
  container: { flex: 1, backgroundColor: '#FFF' },
  center: { flex: 1, justifyContent: 'center', alignItems: 'center' },
  
  coverWrapper: { height: 380, width: width },
  coverImage: { width: '100%', height: '100%' },
  headerTop: { position: 'absolute', top: 0, left: 0, right: 0, flexDirection: 'row', justifyContent: 'space-between', paddingHorizontal: 20, paddingTop: 10 },
  circleBtn: { 
    width: 44, 
    height: 44, 
    borderRadius: 22, 
    backgroundColor: 'rgba(255,255,255,0.2)', 
    justifyContent: 'center', 
    alignItems: 'center',
    borderWidth: 1,
    borderColor: 'rgba(255,255,255,0.3)'
  },
  
  coverInfo: { position: 'absolute', bottom: 40, left: 24, right: 24 },
  muthowifName: { fontSize: 32, fontWeight: '900', color: '#FFF', letterSpacing: -0.5 },
  locationRow: { flexDirection: 'row', alignItems: 'center', gap: 6, marginTop: 8 },
  locationText: { color: 'rgba(255,255,255,0.8)', fontSize: 14, fontWeight: '600' },

  statsBar: { 
    flexDirection: 'row', 
    backgroundColor: '#FFF', 
    marginHorizontal: 24, 
    marginTop: -30, 
    borderRadius: 24, 
    paddingVertical: 20,
    shadowColor: '#000', shadowOffset: { width: 0, height: 10 }, shadowOpacity: 0.1, shadowRadius: 20, elevation: 10
  },
  statItem: { flex: 1, alignItems: 'center' },
  statVal: { fontSize: 18, fontWeight: '900', color: '#0F172A' },
  statLabelRow: { flexDirection: 'row', alignItems: 'center', gap: 4, marginTop: 2 },
  statLbl: { fontSize: 11, fontWeight: '700', color: '#94A3B8', textTransform: 'uppercase' },
  statDivider: { width: 1, height: '60%', backgroundColor: '#F1F5F9' },

  tabContainer: { flexDirection: 'row', paddingHorizontal: 24, marginTop: 30, borderBottomWidth: 1, borderBottomColor: '#F1F5F9' },
  tab: { marginRight: 30, paddingBottom: 15, borderBottomWidth: 3, borderBottomColor: 'transparent' },
  tabActive: { borderBottomColor: '#3B82F6' },
  tabText: { fontSize: 15, fontWeight: '700', color: '#94A3B8' },
  tabTextActive: { color: '#0F172A' },

  contentPadding: { paddingHorizontal: 24, paddingTop: 25 },
  tabContent: { flex: 1 },
  bioTitle: { fontSize: 18, fontWeight: '900', color: '#0F172A', marginBottom: 12, marginTop: 5 },
  bioText: { fontSize: 15, color: '#475569', lineHeight: 26, marginBottom: 25 },
  
  tagGrid: { flexDirection: 'row', flexWrap: 'wrap', gap: 8, marginBottom: 25 },
  tag: { backgroundColor: '#F8FAFC', paddingHorizontal: 16, paddingVertical: 8, borderRadius: 12, borderWidth: 1, borderColor: '#F1F5F9' },
  tagText: { fontSize: 13, fontWeight: '700', color: '#475569' },

  eduRow: { flexDirection: 'row', alignItems: 'center', gap: 12, marginBottom: 12 },
  eduText: { fontSize: 14, color: '#475569', fontWeight: '600' },

  serviceCard: { backgroundColor: '#F8FAFC', borderRadius: 24, padding: 20, marginBottom: 16, borderWidth: 1, borderColor: '#F1F5F9' },
  serviceHeader: { flexDirection: 'row', justifyContent: 'space-between', alignItems: 'center', marginBottom: 12 },
  serviceName: { fontSize: 16, fontWeight: '900', color: '#0F172A', flex: 1 },
  typeBadge: { paddingHorizontal: 10, paddingVertical: 4, borderRadius: 8 },
  typeText: { fontSize: 10, fontWeight: '800' },
  serviceDesc: { fontSize: 13, color: '#64748B', lineHeight: 20, marginBottom: 15 },
  serviceFooter: { flexDirection: 'row', justifyContent: 'space-between', alignItems: 'flex-end' },
  servicePriceLabel: { fontSize: 10, fontWeight: '800', color: '#94A3B8', marginBottom: 4 },
  servicePrice: { fontSize: 18, fontWeight: '900', color: '#0F172A' },
  bookIcon: { width: 40, height: 40, borderRadius: 20, backgroundColor: '#0F172A', justifyContent: 'center', alignItems: 'center' },

  emptyUlasan: { alignItems: 'center', paddingVertical: 50 },
  emptyUlasanText: { color: '#94A3B8', fontSize: 14, marginTop: 15, fontWeight: '600' },

  stickyFooter: { position: 'absolute', bottom: 0, left: 0, right: 0, backgroundColor: '#FFF', flexDirection: 'row', alignItems: 'center', paddingHorizontal: 24, paddingTop: 20, borderTopWidth: 1, borderTopColor: '#F1F5F9' },
  footerLabel: { fontSize: 11, fontWeight: '800', color: '#94A3B8', marginBottom: 4 },
  footerPrice: { fontSize: 20, fontWeight: '900', color: '#0F172A' },
  mainBtn: { flex: 1, marginLeft: 20 },
  mainBtnGradient: { height: 56, borderRadius: 18, justifyContent: 'center', alignItems: 'center' },
  mainBtnText: { color: '#FFF', fontSize: 16, fontWeight: '900' }
});
