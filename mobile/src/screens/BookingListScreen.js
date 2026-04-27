import React, { useState, useEffect, useCallback } from 'react';
import { 
  StyleSheet, 
  View, 
  Text, 
  FlatList, 
  TouchableOpacity, 
  ActivityIndicator, 
  RefreshControl,
  Image,
  Dimensions,
  StatusBar
} from 'react-native';
import { SafeAreaView } from 'react-native-safe-area-context';
import { Ionicons } from '@expo/vector-icons';
import { LinearGradient } from 'expo-linear-gradient';
import { apiClient } from '../api/client';
import SwipeableScreen from '../components/SwipeableScreen';

const { width } = Dimensions.get('window');

export default function BookingListScreen({ user, navigation }) {
  const [loading, setLoading] = useState(true);
  const [refreshing, setRefreshing] = useState(false);
  const [bookings, setBookings] = useState([]);
  const [activeTab, setActiveTab] = useState('all');

  const fetchBookings = useCallback(async () => {
    try {
      const data = await apiClient.getCustomerBookings(user.token);
      setBookings(data || []);
    } catch (error) {
      console.error(error);
    } finally {
      setLoading(false);
      setRefreshing(false);
    }
  }, [user.token]);

  useEffect(() => {
    fetchBookings();
  }, [fetchBookings]);

  const onRefresh = () => {
    setRefreshing(true);
    fetchBookings();
  };

  const getStatusStyle = (status, paymentStatus) => {
    if (paymentStatus === 'paid') return { label: 'Lunas', color: '#10B981', bg: '#DCFCE7', icon: 'checkmark-circle' };
    
    switch (status) {
      case 'pending': return { label: 'Menunggu', color: '#F59E0B', bg: '#FEF3C7', icon: 'time' };
      case 'confirmed': return { label: 'Dikonfirmasi', color: '#3B82F6', bg: '#DBEAFE', icon: 'shield-checkmark' };
      case 'cancelled': return { label: 'Dibatalkan', color: '#EF4444', bg: '#FEE2E2', icon: 'close-circle' };
      default: return { label: status, color: '#64748B', bg: '#F1F5F9', icon: 'help-circle' };
    }
  };

  const filteredBookings = bookings.filter(b => {
    if (activeTab === 'all') return true;
    if (activeTab === 'unpaid') return b.payment_status === 'pending' && b.status !== 'cancelled';
    if (activeTab === 'paid') return b.payment_status === 'paid';
    return true;
  });

  const renderBookingItem = ({ item }) => {
    const status = getStatusStyle(item.status, item.payment_status);
    const date = new Date(item.starts_on).toLocaleDateString('id-ID', { day: 'numeric', month: 'short', year: 'numeric' });
    
    return (
      <TouchableOpacity 
        style={styles.card} 
        activeOpacity={0.9}
        onPress={() => navigation.navigate('BookingDetail', { bookingId: item.id })}
      >
        <View style={styles.cardHeader}>
          <View style={styles.codeWrapper}>
            <Text style={styles.bookingCode}>{item.booking_code}</Text>
            <Text style={styles.dateText}>{date}</Text>
          </View>
          <View style={[styles.statusBadge, { backgroundColor: status.bg }]}>
            <Ionicons name={status.icon} size={14} color={status.color} style={{ marginRight: 4 }} />
            <Text style={[styles.statusLabel, { color: status.color }]}>{status.label}</Text>
          </View>
        </View>

        <View style={styles.cardBody}>
          <View style={styles.muthowifInfo}>
            <Image 
              source={{ uri: item.muthowif_profile?.avatar || 'https://ui-avatars.com/api/?name=' + (item.muthowif_profile?.user?.name || 'M') + '&background=0984e3&color=fff' }} 
              style={styles.avatar} 
            />
            <View style={{ flex: 1 }}>
              <Text style={styles.muthowifName}>{item.muthowif_profile?.user?.name || 'Muthowif'}</Text>
              <Text style={styles.serviceType}>
                {item.service_type === 'private' ? 'Layanan Private' : 'Layanan Group'} • {item.pilgrim_count} Jamaah
              </Text>
            </View>
          </View>
          
          <View style={styles.priceRow}>
            <Text style={styles.priceLabel}>Total Biaya</Text>
            <Text style={styles.priceValue}>Rp {item.total_amount?.toString().replace(/\B(?=(\d{3})+(?!\d))/g, ".")}</Text>
          </View>
        </View>

        {item.status === 'confirmed' && item.payment_status === 'pending' && (
          <TouchableOpacity 
            style={styles.payBtn}
            onPress={() => navigation.navigate('Payment', { 
              booking_id: item.id,
              booking_code: item.booking_code,
              amount: item.amount_due
            })}
          >
            <LinearGradient
              colors={['#3B82F6', '#2563EB']}
              start={{ x: 0, y: 0 }}
              end={{ x: 1, y: 0 }}
              style={styles.payGradient}
            >
              <Text style={styles.payText}>Bayar Sekarang</Text>
              <Ionicons name="chevron-forward" size={16} color="#FFF" />
            </LinearGradient>
          </TouchableOpacity>
        )}
      </TouchableOpacity>
    );
  };

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
              <Text style={styles.headerTitle}>Pesanan Saya</Text>
              <View style={{ width: 40 }} />
            </View>

            <View style={styles.tabBar}>
              <TouchableOpacity 
                style={[styles.tab, activeTab === 'all' && styles.activeTab]}
                onPress={() => setActiveTab('all')}
              >
                <Text style={[styles.tabText, activeTab === 'all' && styles.activeTabText]}>Semua</Text>
              </TouchableOpacity>
              <TouchableOpacity 
                style={[styles.tab, activeTab === 'unpaid' && styles.activeTab]}
                onPress={() => setActiveTab('unpaid')}
              >
                <Text style={[styles.tabText, activeTab === 'unpaid' && styles.activeTabText]}>Belum Bayar</Text>
              </TouchableOpacity>
              <TouchableOpacity 
                style={[styles.tab, activeTab === 'paid' && styles.activeTab]}
                onPress={() => setActiveTab('paid')}
              >
                <Text style={[styles.tabText, activeTab === 'paid' && styles.activeTabText]}>Berhasil</Text>
              </TouchableOpacity>
            </View>
          </SafeAreaView>
        </View>

        {loading ? (
          <View style={styles.center}>
            <ActivityIndicator size="large" color="#3B82F6" />
          </View>
        ) : (
          <FlatList
            data={filteredBookings}
            keyExtractor={(item) => item.id}
            renderItem={renderBookingItem}
            contentContainerStyle={styles.list}
            showsVerticalScrollIndicator={false}
            refreshControl={
              <RefreshControl refreshing={refreshing} onRefresh={onRefresh} tintColor="#3B82F6" />
            }
            ListEmptyComponent={
              <View style={styles.empty}>
                <View style={styles.emptyIconBg}>
                  <Ionicons name="receipt-outline" size={64} color="#CBD5E1" />
                </View>
                <Text style={styles.emptyTitle}>Belum Ada Pesanan</Text>
                <Text style={styles.emptySub}>Daftar pesanan Anda akan muncul di sini setelah Anda melakukan booking.</Text>
                <TouchableOpacity 
                  style={styles.exploreBtn}
                  onPress={() => navigation.navigate('Dashboard')}
                >
                  <Text style={styles.exploreBtnText}>Cari Muthowif</Text>
                </TouchableOpacity>
              </View>
            }
          />
        )}
      </View>
    </SwipeableScreen>
  );
}

const styles = StyleSheet.create({
  container: { flex: 1, backgroundColor: '#F8FAFC' },
  header: { 
    backgroundColor: '#FFFFFF',
    paddingBottom: 20, 
    borderBottomLeftRadius: 32, 
    borderBottomRightRadius: 32,
    shadowColor: '#000',
    shadowOffset: { width: 0, height: 4 },
    shadowOpacity: 0.03,
    shadowRadius: 10,
    elevation: 5
  },
  headerTop: { flexDirection: 'row', alignItems: 'center', justifyContent: 'space-between', paddingHorizontal: 20, paddingTop: 10 },
  backBtn: { width: 40, height: 40, justifyContent: 'center', alignItems: 'center' },
  headerTitle: { color: '#0F172A', fontSize: 20, fontWeight: '900', letterSpacing: -0.5 },
  
  tabBar: { flexDirection: 'row', paddingHorizontal: 20, marginTop: 20, gap: 10 },
  tab: { paddingHorizontal: 16, paddingVertical: 8, borderRadius: 20, backgroundColor: '#F8FAFC', borderWidth: 1, borderColor: '#F1F5F9' },
  activeTab: { backgroundColor: '#0F172A', borderColor: '#0F172A' },
  tabText: { color: '#64748B', fontSize: 13, fontWeight: '700' },
  activeTabText: { color: '#FFF' },

  list: { padding: 20, paddingBottom: 100 },
  card: { 
    backgroundColor: '#FFF', 
    borderRadius: 24, 
    padding: 20, 
    marginBottom: 20,
    borderWidth: 1,
    borderColor: '#F1F5F9',
    shadowColor: '#64748B',
    shadowOffset: { width: 0, height: 10 },
    shadowOpacity: 0.05,
    shadowRadius: 15,
    elevation: 4
  },
  cardHeader: { flexDirection: 'row', justifyContent: 'space-between', alignItems: 'flex-start', marginBottom: 20 },
  codeWrapper: { flex: 1 },
  bookingCode: { fontSize: 13, fontWeight: '900', color: '#0F172A', letterSpacing: 0.5 },
  dateText: { fontSize: 12, color: '#94A3B8', marginTop: 4, fontWeight: '600' },
  statusBadge: { flexDirection: 'row', alignItems: 'center', paddingHorizontal: 12, paddingVertical: 6, borderRadius: 12 },
  statusLabel: { fontSize: 11, fontWeight: '800', textTransform: 'uppercase' },

  cardBody: { borderTopWidth: 1, borderTopColor: '#F1F5F9', paddingTop: 20 },
  muthowifInfo: { flexDirection: 'row', alignItems: 'center', marginBottom: 20 },
  avatar: { width: 48, height: 48, borderRadius: 16, backgroundColor: '#F8FAFC' },
  muthowifName: { fontSize: 16, fontWeight: '800', color: '#1E293B', marginLeft: 12 },
  serviceType: { fontSize: 12, color: '#64748B', marginLeft: 12, marginTop: 2, fontWeight: '500' },

  priceRow: { flexDirection: 'row', justifyContent: 'space-between', alignItems: 'center', gap: 10 },
  priceLabel: { fontSize: 12, color: '#94A3B8', fontWeight: '700', letterSpacing: 0.5, flex: 1 },
  priceValue: { fontSize: 18, fontWeight: '900', color: '#3B82F6', textAlign: 'right' },

  payBtn: { marginTop: 20 },
  payGradient: { flexDirection: 'row', alignItems: 'center', justifyContent: 'center', paddingVertical: 14, borderRadius: 16, gap: 8 },
  payText: { color: '#FFF', fontSize: 14, fontWeight: '800' },

  center: { flex: 1, justifyContent: 'center', alignItems: 'center' },
  empty: { flex: 1, alignItems: 'center', justifyContent: 'center', marginTop: 60, paddingHorizontal: 40 },
  emptyIconBg: { width: 120, height: 120, borderRadius: 60, backgroundColor: '#F1F5F9', justifyContent: 'center', alignItems: 'center', marginBottom: 24 },
  emptyTitle: { fontSize: 20, fontWeight: '900', color: '#0F172A', marginBottom: 12 },
  emptySub: { fontSize: 14, color: '#64748B', textAlign: 'center', lineHeight: 22, marginBottom: 30 },
  exploreBtn: { backgroundColor: '#0F172A', paddingHorizontal: 24, paddingVertical: 14, borderRadius: 16 },
  exploreBtnText: { color: '#FFF', fontSize: 14, fontWeight: '800' }
});
