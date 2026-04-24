import React, { useState, useEffect } from 'react';
import { 
  StyleSheet, 
  Text, 
  View, 
  TouchableOpacity, 
  ScrollView, 
  Dimensions,
  RefreshControl,
  Image
} from 'react-native';
import { SafeAreaView } from 'react-native-safe-area-context';
import { StatusBar } from 'expo-status-bar';
import { Ionicons } from '@expo/vector-icons';
import { LinearGradient } from 'expo-linear-gradient';
import { apiClient } from '../api/client';
import { Skeleton, SkeletonCard, SkeletonText, SkeletonListItem } from '../components/Skeleton';

const { width } = Dimensions.get('window');

export default function DashboardScreen({ user, onLogout, navigation }) {
  const [loading, setLoading] = useState(true);
  const [refreshing, setRefreshing] = useState(false);
  const [dashboardData, setDashboardData] = useState({
    stats: [],
    top_muthowifs: [],
    unread_messages: 0,
  });

  const getGreeting = () => {
    const hour = new Date().getHours();
    if (hour < 12) return 'Selamat Pagi';
    if (hour < 15) return 'Selamat Siang';
    if (hour < 18) return 'Selamat Sore';
    return 'Selamat Malam';
  };

  const fetchData = async () => {
    try {
      const data = await apiClient.getDashboardData(user.token);
      setDashboardData(data);
    } catch (error) {
      console.error('Fetch Dashboard Error:', error);
    } finally {
      setLoading(false);
      setRefreshing(false);
    }
  };

  useEffect(() => {
    fetchData();
    const interval = setInterval(() => {
      apiClient.getDashboardData(user.token).then(data => {
        setDashboardData(prev => ({ ...prev, unread_messages: data.unread_messages }));
      }).catch(() => {});
    }, 5000);
    return () => clearInterval(interval);
  }, []);

  const onRefresh = () => {
    setRefreshing(true);
    fetchData();
  };

  if (loading) {
    return (
      <SafeAreaView style={styles.container}>
        <View style={styles.header}>
          <View>
            <SkeletonText width={100} height={11} />
            <SkeletonText width={160} height={20} style={{ marginBottom: 0 }} />
          </View>
          <Skeleton width={70} height={32} borderRadius={10} />
        </View>
        <ScrollView contentContainerStyle={styles.scrollContent} showsVerticalScrollIndicator={false}>
          {/* Stats */}
          <View style={{ flexDirection: 'row', gap: 12, marginBottom: 20 }}>
            {[1,2,3].map(i => (
              <SkeletonCard key={i} style={{ flex: 1, padding: 14, marginBottom: 0 }}>
                <SkeletonText width="60%" height={20} />
                <SkeletonText width="80%" height={11} style={{ marginBottom: 0 }} />
              </SkeletonCard>
            ))}
          </View>
          {/* Banner */}
          <Skeleton width="100%" height={110} borderRadius={20} style={{ marginBottom: 20 }} />
          {/* Action grid */}
          <SkeletonText width={120} height={16} style={{ marginBottom: 14 }} />
          <View style={{ flexDirection: 'row', flexWrap: 'wrap', gap: 12, marginBottom: 20 }}>
            {[1,2,3,4].map(i => (
              <Skeleton key={i} width="47%" height={80} borderRadius={16} />
            ))}
          </View>
          {/* List items */}
          <SkeletonText width={140} height={16} style={{ marginBottom: 14 }} />
          {[1,2,3].map(i => <SkeletonListItem key={i} />)}
        </ScrollView>
      </SafeAreaView>
    );
  }

  return (
    <SafeAreaView style={styles.container}>
      <StatusBar style="dark" />
      
      {/* Header Premium */}
      <View style={styles.header}>
        <View style={styles.headerProfile}>
          <Image 
            source={{ uri: user?.user?.avatar || 'https://ui-avatars.com/api/?name=' + (user?.user?.name || 'Jamaah') + '&background=0984e3&color=fff' }} 
            style={styles.avatar} 
          />
          <View>
            <Text style={styles.greeting}>{getGreeting()},</Text>
            <Text style={styles.userName}>{user?.user?.name || 'Jamaah'}</Text>
          </View>
        </View>
        <TouchableOpacity style={styles.notifBtn}>
          <Ionicons name="notifications-outline" size={22} color="#0F172A" />
          <View style={styles.notifDot} />
        </TouchableOpacity>
      </View>

      <ScrollView 
        showsVerticalScrollIndicator={false} 
        contentContainerStyle={styles.scrollContent}
        refreshControl={<RefreshControl refreshing={refreshing} onRefresh={onRefresh} tintColor="#0984e3" />}
      >
        
        {/* Banner Premium */}
        <LinearGradient
          colors={['#0F172A', '#1E3A8A', '#0984e3']}
          start={{ x: 0, y: 0 }}
          end={{ x: 1, y: 1 }}
          style={styles.promoBanner}
        >
          <View style={styles.promoGlow} />
          <View style={styles.promoTextContent}>
            <View style={styles.badgeContainer}>
              <View style={styles.badgeDot} />
              <Text style={styles.badgeText}>DASHBOARD JAMAAH</Text>
            </View>
            <Text style={styles.promoTitle}>Temukan pendamping untuk tanggal perjalanan Anda</Text>
            <Text style={styles.promoSub}>Marketplace hanya menampilkan muthowif yang tersedia — tidak bentrok libur atau booking lain.</Text>
            <TouchableOpacity style={styles.promoBtn}>
              <Text style={styles.promoBtnText}>Cari Ketersediaan</Text>
              <Ionicons name="arrow-forward" size={16} color="#0F172A" />
            </TouchableOpacity>
          </View>
          <Ionicons name="compass" size={120} color="rgba(255,255,255,0.1)" style={styles.promoBgIcon} />
        </LinearGradient>

        {/* Quick Access (Akses Cepat) */}
        <View style={styles.sectionHeader}>
          <Text style={styles.sectionTitle}>Akses cepat</Text>
          <Text style={styles.sectionSub}>Pintasan ke fitur yang sering dipakai</Text>
        </View>

        <View style={styles.actionsGrid}>
          {/* Action: Cari Muthowif */}
          <TouchableOpacity style={styles.actionCard} activeOpacity={0.7} onPress={() => navigation?.navigate('SearchMuthowif')}>
            <View style={[styles.actionIconWrap, { backgroundColor: '#F5F3FF' }]}>
              <Ionicons name="search" size={26} color="#8B5CF6" />
            </View>
            <Text style={styles.actionTitle}>Cari muthowif</Text>
            <Text style={styles.actionDesc}>Jelajahi direktori & filter tanggal</Text>
          </TouchableOpacity>

          {/* Action: Booking Saya */}
          <TouchableOpacity style={styles.actionCard} activeOpacity={0.7}>
            <View style={[styles.actionIconWrap, { backgroundColor: '#EFF6FF' }]}>
              <Ionicons name="calendar-clear" size={26} color="#3B82F6" />
            </View>
            <Text style={styles.actionTitle}>Booking saya</Text>
            <Text style={styles.actionDesc}>Status pembayaran & perjalanan</Text>
          </TouchableOpacity>

          {/* Action: Pesan (Chat) */}
          <TouchableOpacity style={styles.actionCard} activeOpacity={0.7} onPress={() => navigation?.navigate('ChatList')}>
            <View style={[styles.actionIconWrap, { backgroundColor: '#ECFDF5' }]}>
              <Ionicons name="chatbubble-ellipses" size={26} color="#10B981" />
              {dashboardData.unread_messages > 0 && (
                <View style={styles.badgeAction}>
                  <Text style={styles.badgeActionText}>{dashboardData.unread_messages > 99 ? '99+' : dashboardData.unread_messages}</Text>
                </View>
              )}
            </View>
            <Text style={styles.actionTitle}>Pesan</Text>
            <Text style={styles.actionDesc}>Chat dengan muthowif Anda</Text>
          </TouchableOpacity>

          {/* Action: Profil */}
          <TouchableOpacity style={styles.actionCard} activeOpacity={0.7}>
            <View style={[styles.actionIconWrap, { backgroundColor: '#FFF7ED' }]}>
              <Ionicons name="person" size={26} color="#F97316" />
            </View>
            <Text style={styles.actionTitle}>Profil & akun</Text>
            <Text style={styles.actionDesc}>Nama, email, dan keamanan</Text>
          </TouchableOpacity>
        </View>

        {/* Top Muthowifs */}
        <View style={styles.recentHeader}>
          <Text style={styles.recentTitle}>Rekomendasi Muthowif</Text>
          <TouchableOpacity onPress={() => navigation?.navigate('SearchMuthowif')}><Text style={styles.seeAll}>Lihat Semua</Text></TouchableOpacity>
        </View>

        
        {dashboardData.top_muthowifs && dashboardData.top_muthowifs.length > 0 ? (
          dashboardData.top_muthowifs.map((muthowif) => (
            <TouchableOpacity 
              key={muthowif.id} 
              style={styles.muthowifCard} 
              activeOpacity={0.7}
              onPress={() => navigation?.navigate('MuthowifDetail', { id: muthowif.id })}
            >
              <Image source={{ uri: muthowif.avatar }} style={styles.muthowifAvatar} />
              <View style={styles.muthowifInfo}>
                <View style={styles.muthowifHeaderRow}>
                  <Text style={styles.muthowifName}>{muthowif.name}</Text>
                  <View style={styles.muthowifRatingWrap}>
                    <Ionicons name="star" size={14} color="#F59E0B" />
                    <Text style={styles.muthowifRating}>{muthowif.rating}</Text>
                  </View>
                </View>
                <Text style={styles.muthowifReviews}>Dari {muthowif.reviews} ulasan jamaah</Text>
                
                <View style={styles.muthowifTags}>
                  <View style={styles.muthowifTag}>
                    <Ionicons name="location" size={12} color="#0984e3" />
                    <Text style={styles.muthowifTagText}>{muthowif.location}</Text>
                  </View>
                  {muthowif.languages && muthowif.languages.map((lang, idx) => (
                    <View key={idx} style={styles.muthowifTag}>
                      <Text style={styles.muthowifTagText}>{lang}</Text>
                    </View>
                  ))}
                </View>
              </View>
            </TouchableOpacity>
          ))
        ) : (
          <View style={styles.emptyCard}>
            <Text style={styles.emptyText}>Belum ada rekomendasi muthowif.</Text>
          </View>
        )}

      </ScrollView>

        {/* Tab Bar Mengambang (Floating/Glass effect) */}
      <View style={styles.tabBarWrap}>
        <View style={styles.tabBar}>
          <TouchableOpacity style={styles.tabItem}>
            <Ionicons name="home" size={24} color="#0984e3" />
            <Text style={[styles.tabLabel, { color: '#0984e3' }]}>Home</Text>
          </TouchableOpacity>
          <TouchableOpacity style={styles.tabItem}>
            <Ionicons name="calendar-outline" size={24} color="#94A3B8" />
            <Text style={styles.tabLabel}>Booking</Text>
          </TouchableOpacity>
          <TouchableOpacity style={styles.tabItem} onPress={() => navigation?.navigate('ChatList')}>
            <View>
              <Ionicons name="chatbubbles-outline" size={24} color="#94A3B8" />
              {dashboardData.unread_messages > 0 && (
                <View style={styles.badgeTab}>
                  <Text style={styles.badgeTabText}>{dashboardData.unread_messages > 99 ? '99+' : dashboardData.unread_messages}</Text>
                </View>
              )}
            </View>
            <Text style={styles.tabLabel}>Pesan</Text>
          </TouchableOpacity>
          <TouchableOpacity style={styles.tabItem} onPress={onLogout}>
            {/* Sementara digunakan untuk Logout seperti di instruksi */}
            <Ionicons name="log-out-outline" size={24} color="#94A3B8" />
            <Text style={styles.tabLabel}>Keluar</Text>
          </TouchableOpacity>
        </View>
      </View>
    </SafeAreaView>
  );
}

const styles = StyleSheet.create({
  container: {
    flex: 1,
    backgroundColor: '#F8FAFC',
  },
  header: {
    paddingHorizontal: 25,
    paddingTop: 15,
    paddingBottom: 15,
    flexDirection: 'row',
    justifyContent: 'space-between',
    alignItems: 'center',
    backgroundColor: '#F8FAFC',
  },
  headerProfile: {
    flexDirection: 'row',
    alignItems: 'center',
    gap: 12,
  },
  avatar: {
    width: 44,
    height: 44,
    borderRadius: 22,
    borderWidth: 2,
    borderColor: '#FFFFFF',
  },
  greeting: {
    fontSize: 13,
    color: '#64748B',
    fontWeight: '600',
  },
  userName: {
    fontSize: 20,
    fontWeight: '800',
    color: '#0F172A',
    marginTop: 2,
    letterSpacing: -0.5,
  },
  notifBtn: {
    width: 44,
    height: 44,
    borderRadius: 22,
    backgroundColor: '#FFFFFF',
    justifyContent: 'center',
    alignItems: 'center',
    shadowColor: '#000',
    shadowOffset: { width: 0, height: 2 },
    shadowOpacity: 0.05,
    shadowRadius: 8,
    elevation: 2,
  },
  notifDot: {
    position: 'absolute',
    top: 12,
    right: 12,
    width: 8,
    height: 8,
    borderRadius: 4,
    backgroundColor: '#EF4444',
    borderWidth: 1.5,
    borderColor: '#FFFFFF',
  },
  scrollContent: {
    paddingHorizontal: 20,
    paddingBottom: 120,
  },
  promoBanner: {
    borderRadius: 28,
    padding: 24,
    marginTop: 10,
    marginBottom: 30,
    overflow: 'hidden',
    position: 'relative',
    shadowColor: '#0984e3',
    shadowOffset: { width: 0, height: 8 },
    shadowOpacity: 0.3,
    shadowRadius: 15,
    elevation: 8,
  },
  promoGlow: {
    position: 'absolute',
    top: -40,
    right: -40,
    width: 150,
    height: 150,
    borderRadius: 75,
    backgroundColor: 'rgba(255,255,255,0.15)',
  },
  badgeContainer: {
    flexDirection: 'row',
    alignItems: 'center',
    backgroundColor: 'rgba(255,255,255,0.15)',
    alignSelf: 'flex-start',
    paddingHorizontal: 10,
    paddingVertical: 4,
    borderRadius: 20,
    marginBottom: 16,
    borderWidth: 1,
    borderColor: 'rgba(255,255,255,0.2)',
  },
  badgeDot: {
    width: 6,
    height: 6,
    borderRadius: 3,
    backgroundColor: '#34D399',
    marginRight: 6,
  },
  badgeText: {
    color: '#E0F2FE',
    fontSize: 10,
    fontWeight: '800',
    letterSpacing: 0.5,
  },
  promoTitle: {
    color: '#FFFFFF',
    fontSize: 22,
    fontWeight: '800',
    lineHeight: 30,
    letterSpacing: -0.5,
  },
  promoSub: {
    color: 'rgba(255, 255, 255, 0.85)',
    fontSize: 13,
    marginTop: 10,
    lineHeight: 20,
    maxWidth: '85%',
  },
  promoBtn: {
    backgroundColor: '#FFFFFF',
    flexDirection: 'row',
    alignItems: 'center',
    paddingVertical: 12,
    paddingHorizontal: 20,
    borderRadius: 16,
    alignSelf: 'flex-start',
    marginTop: 24,
    gap: 8,
  },
  promoBtnText: {
    color: '#0F172A',
    fontSize: 14,
    fontWeight: '700',
  },
  promoBgIcon: {
    position: 'absolute',
    bottom: -20,
    right: -20,
    transform: [{ rotate: '-15deg' }],
  },
  sectionHeader: {
    marginBottom: 16,
    paddingHorizontal: 5,
  },
  sectionTitle: {
    fontSize: 19,
    fontWeight: '800',
    color: '#0F172A',
    letterSpacing: -0.5,
  },
  sectionSub: {
    fontSize: 13,
    color: '#64748B',
    marginTop: 2,
  },
  actionsGrid: {
    flexDirection: 'row',
    flexWrap: 'wrap',
    justifyContent: 'space-between',
    marginBottom: 30,
  },
  actionCard: {
    width: '48%',
    backgroundColor: '#FFFFFF',
    borderRadius: 24,
    padding: 18,
    marginBottom: 14,
    borderWidth: 1,
    borderColor: '#F1F5F9',
    shadowColor: '#000',
    shadowOffset: { width: 0, height: 4 },
    shadowOpacity: 0.03,
    shadowRadius: 10,
    elevation: 2,
  },
  actionIconWrap: {
    width: 48,
    height: 48,
    borderRadius: 16,
    alignItems: 'center',
    justifyContent: 'center',
    marginBottom: 14,
  },
  actionTitle: {
    fontSize: 15,
    fontWeight: '700',
    color: '#1E293B',
    letterSpacing: -0.3,
  },
  actionDesc: {
    fontSize: 12,
    color: '#64748B',
    marginTop: 4,
    lineHeight: 18,
  },
  badgeAction: {
    position: 'absolute',
    top: -4,
    right: -4,
    backgroundColor: '#EF4444',
    borderRadius: 10,
    minWidth: 20,
    height: 20,
    justifyContent: 'center',
    alignItems: 'center',
    paddingHorizontal: 5,
    borderWidth: 2,
    borderColor: '#FFFFFF',
  },
  badgeActionText: {
    color: '#FFFFFF',
    fontSize: 10,
    fontWeight: '800',
  },
  recentHeader: {
    flexDirection: 'row',
    justifyContent: 'space-between',
    alignItems: 'center',
    marginBottom: 15,
    paddingHorizontal: 5,
  },
  recentTitle: {
    fontSize: 18,
    fontWeight: '800',
    color: '#0F172A',
    letterSpacing: -0.5,
  },
  seeAll: {
    fontSize: 13,
    color: '#0984e3',
    fontWeight: '700',
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
    alignItems: 'center',
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
  emptyCard: {
    padding: 40,
    alignItems: 'center',
    backgroundColor: '#FFFFFF',
    borderRadius: 24,
    borderWidth: 2,
    borderColor: '#F8FAFC',
    borderStyle: 'dashed',
  },
  emptyText: {
    color: '#94A3B8',
    fontSize: 14,
    fontWeight: '500',
  },
  tabBarWrap: {
    position: 'absolute',
    bottom: 25,
    left: 20,
    right: 20,
    shadowColor: '#000',
    shadowOffset: { width: 0, height: 10 },
    shadowOpacity: 0.1,
    shadowRadius: 20,
    elevation: 10,
  },
  tabBar: {
    backgroundColor: 'rgba(255, 255, 255, 0.95)',
    borderRadius: 30,
    height: 70,
    flexDirection: 'row',
    paddingHorizontal: 10,
    alignItems: 'center',
  },
  tabItem: {
    flex: 1,
    alignItems: 'center',
    justifyContent: 'center',
    gap: 4,
  },
  tabLabel: {
    fontSize: 10,
    fontWeight: '700',
    color: '#94A3B8',
  },
  badgeTab: {
    position: 'absolute',
    top: -4,
    right: -8,
    backgroundColor: '#EF4444',
    borderRadius: 8,
    minWidth: 16,
    height: 16,
    justifyContent: 'center',
    alignItems: 'center',
    paddingHorizontal: 4,
  },
  badgeTabText: {
    color: '#FFFFFF',
    fontSize: 8,
    fontWeight: '800',
  }
});
