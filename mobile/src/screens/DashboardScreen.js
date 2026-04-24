import React, { useState, useEffect } from 'react';
import { 
  StyleSheet, 
  Text, 
  View, 
  TouchableOpacity, 
  ScrollView, 
  Dimensions,
  ActivityIndicator,
  RefreshControl
} from 'react-native';
import { SafeAreaView } from 'react-native-safe-area-context';
import { StatusBar } from 'expo-status-bar';
import { Ionicons } from '@expo/vector-icons';
import { apiClient } from '../api/client';

const { width } = Dimensions.get('window');

export default function DashboardScreen({ user, onLogout, navigation }) {
  const [loading, setLoading] = useState(true);
  const [refreshing, setRefreshing] = useState(false);
  const [dashboardData, setDashboardData] = useState({
    stats: [],
    recent_bookings: []
  });

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
  }, []);

  const onRefresh = () => {
    setRefreshing(true);
    fetchData();
  };

  if (loading) {
    return (
      <View style={[styles.container, styles.center]}>
        <ActivityIndicator size="large" color="#0984e3" />
      </View>
    );
  }

  return (
    <SafeAreaView style={styles.container}>
      <StatusBar style="dark" />
      
      <View style={styles.header}>
        <View>
          <Text style={styles.greeting}>Assalamu'alaikum,</Text>
          <Text style={styles.userName}>{user?.user?.name || 'Jamaah'}</Text>
        </View>
        <TouchableOpacity style={styles.logoutBtn} onPress={onLogout}>
          <Text style={styles.logoutText}>Keluar</Text>
        </TouchableOpacity>
      </View>

      <ScrollView 
        showsVerticalScrollIndicator={false} 
        contentContainerStyle={styles.scrollContent}
        refreshControl={<RefreshControl refreshing={refreshing} onRefresh={onRefresh} color="#0984e3" />}
      >
        
        {/* Stats Section */}
        <View style={styles.statsGrid}>
          {dashboardData.stats.map((item, index) => (
            <View key={index} style={styles.statCard}>
              <Text style={[styles.statValue, { color: item.color }]}>{item.value}</Text>
              <Text style={styles.statLabel}>{item.label}</Text>
            </View>
          ))}
        </View>

        {/* Banner / Promotion */}
        <View style={styles.promoBanner}>
          <View style={styles.promoTextContent}>
            <Text style={styles.promoTitle}>Cari Muthowif Terbaik</Text>
            <Text style={styles.promoSub}>Temukan pendamping ibadah yang sesuai dengan kebutuhan Anda.</Text>
            <TouchableOpacity style={styles.promoBtn}>
              <Text style={styles.promoBtnText}>Cari Sekarang</Text>
            </TouchableOpacity>
          </View>
          <Text style={styles.promoIcon}>🕋</Text>
        </View>

        {/* Quick Actions */}
        <Text style={styles.sectionTitle}>Layanan Kami</Text>
        <View style={styles.actionsGrid}>
          <TouchableOpacity style={styles.actionItem}>
            <View style={[styles.actionIcon, { backgroundColor: '#e1f5fe' }]}>
              <Text style={{ fontSize: 24 }}>🔍</Text>
            </View>
            <Text style={styles.actionLabel}>Direktori</Text>
          </TouchableOpacity>

          <TouchableOpacity style={styles.actionItem}>
            <View style={[styles.actionIcon, { backgroundColor: '#f3e5f5' }]}>
              <Text style={{ fontSize: 24 }}>📅</Text>
            </View>
            <Text style={styles.actionLabel}>Jadwal</Text>
          </TouchableOpacity>

          <TouchableOpacity style={styles.actionItem} onPress={() => navigation?.navigate('ChatList')}>
            <View style={[styles.actionIcon, { backgroundColor: '#e8f5e9' }]}>
              <Text style={{ fontSize: 24 }}>💬</Text>
            </View>
            <Text style={styles.actionLabel}>Pesan</Text>
          </TouchableOpacity>

          <TouchableOpacity style={styles.actionItem}>
            <View style={[styles.actionIcon, { backgroundColor: '#fff3e0' }]}>
              <Text style={{ fontSize: 24 }}>⭐</Text>
            </View>
            <Text style={styles.actionLabel}>Ulasan</Text>
          </TouchableOpacity>
        </View>

        {/* Recent Activity */}
        <View style={styles.recentHeader}>
          <Text style={styles.sectionTitle}>Booking Terbaru</Text>
          <TouchableOpacity><Text style={styles.seeAll}>Lihat Semua</Text></TouchableOpacity>
        </View>
        
        {dashboardData.recent_bookings.length > 0 ? (
          dashboardData.recent_bookings.map((booking) => (
            <View key={booking.id} style={styles.bookingCard}>
              <View style={styles.bookingHeader}>
                <Text style={styles.bookingId}>{booking.booking_number}</Text>
                <View style={[styles.statusBadge, { backgroundColor: booking.status === 'PENDING' ? '#FEF3C7' : '#E0F2FE' }]}>
                  <Text style={[styles.statusText, { color: booking.status === 'PENDING' ? '#92400E' : '#0369A1' }]}>{booking.status}</Text>
                </View>
              </View>
              <Text style={styles.bookingMuthowif}>{booking.muthowif_name}</Text>
              <Text style={styles.bookingDate}>{booking.date} - {booking.duration}</Text>
              <View style={styles.divider} />
              <TouchableOpacity style={styles.detailBtn}>
                <Text style={styles.detailBtnText}>Lihat Detail Pesanan</Text>
              </TouchableOpacity>
            </View>
          ))
        ) : (
          <View style={styles.emptyCard}>
            <Text style={styles.emptyText}>Belum ada riwayat pemesanan.</Text>
          </View>
        )}

      </ScrollView>

      {/* Simulated Tab Bar */}
      <View style={styles.tabBar}>
        <TouchableOpacity style={styles.tabItem}>
          <Text style={[styles.tabIcon, { color: '#0984e3' }]}>🏠</Text>
          <Text style={[styles.tabLabel, { color: '#0984e3' }]}>Home</Text>
        </TouchableOpacity>
        <TouchableOpacity style={styles.tabItem}>
          <Text style={styles.tabIcon}>📋</Text>
          <Text style={styles.tabLabel}>Booking</Text>
        </TouchableOpacity>
        <TouchableOpacity style={styles.tabItem} onPress={() => navigation?.navigate('ChatList')}>
          <Ionicons name="chatbubbles-outline" size={22} color="#94A3B8" style={{ marginBottom: 4 }} />
          <Text style={styles.tabLabel}>Pesan</Text>
        </TouchableOpacity>
        <TouchableOpacity style={styles.tabItem}>
          <Text style={styles.tabIcon}>👤</Text>
          <Text style={styles.tabLabel}>Profil</Text>
        </TouchableOpacity>
      </View>
    </SafeAreaView>
  );
}

const styles = StyleSheet.create({
  container: {
    flex: 1,
    backgroundColor: '#FFFFFF',
  },
  center: {
    justifyContent: 'center',
    alignItems: 'center',
  },
  header: {
    paddingHorizontal: 25,
    paddingTop: 15,
    paddingBottom: 20,
    flexDirection: 'row',
    justifyContent: 'space-between',
    alignItems: 'center',
    backgroundColor: '#FFFFFF',
  },
  greeting: {
    fontSize: 14,
    color: '#64748B',
    fontWeight: '500',
  },
  userName: {
    fontSize: 22,
    fontWeight: '700',
    color: '#0F172A',
    marginTop: 2,
  },
  logoutBtn: {
    paddingVertical: 8,
    paddingHorizontal: 15,
    borderRadius: 10,
    backgroundColor: '#F1F5F9',
  },
  logoutText: {
    fontSize: 13,
    color: '#EF4444',
    fontWeight: '600',
  },
  scrollContent: {
    paddingHorizontal: 25,
    paddingBottom: 100,
  },
  statsGrid: {
    flexDirection: 'row',
    justifyContent: 'space-between',
    marginTop: 10,
    marginBottom: 30,
  },
  statCard: {
    flex: 1,
    backgroundColor: '#F8FAFC',
    paddingVertical: 15,
    paddingHorizontal: 10,
    borderRadius: 16,
    alignItems: 'center',
    marginHorizontal: 4,
    borderWidth: 1,
    borderColor: '#F1F5F9',
  },
  statValue: {
    fontSize: 24,
    fontWeight: '800',
  },
  statLabel: {
    fontSize: 11,
    color: '#94A3B8',
    fontWeight: '700',
    marginTop: 5,
    textTransform: 'uppercase',
  },
  promoBanner: {
    backgroundColor: '#0F172A',
    borderRadius: 24,
    padding: 25,
    flexDirection: 'row',
    alignItems: 'center',
    marginBottom: 35,
    overflow: 'hidden',
  },
  promoTextContent: {
    flex: 1,
  },
  promoTitle: {
    color: '#FFFFFF',
    fontSize: 20,
    fontWeight: '700',
  },
  promoSub: {
    color: 'rgba(255, 255, 255, 0.7)',
    fontSize: 13,
    marginTop: 8,
    lineHeight: 18,
  },
  promoBtn: {
    backgroundColor: '#0984e3',
    paddingVertical: 10,
    paddingHorizontal: 18,
    borderRadius: 10,
    alignSelf: 'flex-start',
    marginTop: 20,
  },
  promoBtnText: {
    color: '#FFFFFF',
    fontSize: 13,
    fontWeight: '700',
  },
  promoIcon: {
    fontSize: 60,
    marginLeft: 15,
    opacity: 0.8,
  },
  sectionTitle: {
    fontSize: 18,
    fontWeight: '700',
    color: '#0F172A',
    marginBottom: 20,
  },
  actionsGrid: {
    flexDirection: 'row',
    justifyContent: 'space-between',
    marginBottom: 40,
  },
  actionItem: {
    alignItems: 'center',
    width: (width - 50) / 4,
  },
  actionIcon: {
    width: 60,
    height: 60,
    borderRadius: 20,
    alignItems: 'center',
    justifyContent: 'center',
    marginBottom: 10,
  },
  actionLabel: {
    fontSize: 12,
    fontWeight: '600',
    color: '#475569',
  },
  recentHeader: {
    flexDirection: 'row',
    justifyContent: 'space-between',
    alignItems: 'center',
    marginBottom: 15,
  },
  seeAll: {
    fontSize: 13,
    color: '#0984e3',
    fontWeight: '600',
  },
  bookingCard: {
    backgroundColor: '#FFFFFF',
    borderRadius: 20,
    padding: 20,
    borderWidth: 1,
    borderColor: '#E2E8F0',
    shadowColor: '#000',
    shadowOffset: { width: 0, height: 4 },
    shadowOpacity: 0.05,
    shadowRadius: 10,
    elevation: 2,
    marginBottom: 15,
  },
  bookingHeader: {
    flexDirection: 'row',
    justifyContent: 'space-between',
    alignItems: 'center',
    marginBottom: 12,
  },
  bookingId: {
    fontSize: 13,
    fontWeight: '800',
    color: '#94A3B8',
  },
  statusBadge: {
    paddingVertical: 4,
    paddingHorizontal: 10,
    borderRadius: 6,
  },
  statusText: {
    fontSize: 11,
    fontWeight: '800',
  },
  bookingMuthowif: {
    fontSize: 17,
    fontWeight: '700',
    color: '#1E293B',
  },
  bookingDate: {
    fontSize: 14,
    color: '#64748B',
    marginTop: 5,
  },
  divider: {
    height: 1,
    backgroundColor: '#F1F5F9',
    marginVertical: 15,
  },
  detailBtn: {
    alignItems: 'center',
  },
  detailBtnText: {
    color: '#0984e3',
    fontSize: 14,
    fontWeight: '700',
  },
  emptyCard: {
    padding: 30,
    alignItems: 'center',
    backgroundColor: '#F8FAFC',
    borderRadius: 20,
    borderWidth: 1,
    borderColor: '#F1F5F9',
    borderStyle: 'dashed',
  },
  emptyText: {
    color: '#94A3B8',
    fontSize: 14,
  },
  tabBar: {
    position: 'absolute',
    bottom: 0,
    left: 0,
    right: 0,
    height: 80,
    backgroundColor: '#FFFFFF',
    flexDirection: 'row',
    borderTopWidth: 1,
    borderTopColor: '#F1F5F9',
    paddingBottom: 20,
  },
  tabItem: {
    flex: 1,
    alignItems: 'center',
    justifyContent: 'center',
  },
  tabIcon: {
    fontSize: 22,
    marginBottom: 4,
    color: '#94A3B8',
  },
  tabLabel: {
    fontSize: 11,
    fontWeight: '600',
    color: '#94A3B8',
  }
});
