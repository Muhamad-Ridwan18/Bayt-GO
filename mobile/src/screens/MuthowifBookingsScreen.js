import React, { useState, useEffect, useCallback } from 'react';
import { 
  StyleSheet, 
  Text, 
  View, 
  TouchableOpacity, 
  ScrollView, 
  ActivityIndicator, 
  Alert, 
  RefreshControl,
  Dimensions,
  StatusBar
} from 'react-native';
import { SafeAreaView } from 'react-native-safe-area-context';
import { LinearGradient } from 'expo-linear-gradient';
import { Ionicons } from '@expo/vector-icons';
import { apiClient } from '../api/client';
import SwipeableScreen from '../components/SwipeableScreen';

const { width } = Dimensions.get('window');

export default function MuthowifBookingsScreen({ user, navigation }) {
  const [loading, setLoading] = useState(true);
  const [refreshing, setRefreshing] = useState(false);
  const [bookings, setBookings] = useState([]);
  const [activeTab, setActiveTab] = useState('all');

  const fetchBookings = useCallback(async () => {
    try {
      const data = await apiClient.getMuthowifBookings(user.token);
      setBookings(data.bookings || []);
    } catch (error) {
      console.error(error);
      Alert.alert('Error', 'Gagal memuat daftar pesanan');
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

  const handleConfirm = (id) => {
    Alert.alert(
      'Setujui Pesanan',
      'Apakah Anda bersedia membimbing jamaah ini sesuai jadwal?',
      [
        { text: 'Nanti', style: 'cancel' },
        { 
          text: 'Ya, Setujui', 
          onPress: async () => {
            try {
              await apiClient.confirmBooking(user.token, id);
              fetchBookings();
              Alert.alert('Sukses', 'Pesanan berhasil disetujui');
            } catch (error) {
              Alert.alert('Error', error.message);
            }
          }
        }
      ]
    );
  };

  const getStatusStyle = (status) => {
    switch (status) {
      case 'pending': return { label: 'Baru', color: '#F59E0B', bg: '#FEF3C7', icon: 'flash' };
      case 'confirmed': return { label: 'Disetujui', color: '#10B981', bg: '#DCFCE7', icon: 'checkmark-circle' };
      case 'completed': return { label: 'Selesai', color: '#64748B', bg: '#F1F5F9', icon: 'archive' };
      case 'cancelled': return { label: 'Dibatalkan', color: '#EF4444', bg: '#FEE2E2', icon: 'close-circle' };
      default: return { label: status, color: '#64748B', bg: '#F1F5F9', icon: 'help-circle' };
    }
  };

  const filteredBookings = bookings.filter(b => {
    if (activeTab === 'all') return true;
    return b.status === activeTab;
  });

  const renderBookingCard = (item) => {
    const status = getStatusStyle(item.status);
    
    return (
      <View key={item.id} style={styles.card}>
        <View style={styles.cardHeader}>
          <View>
            <Text style={styles.bookingCode}>{item.booking_code}</Text>
            <Text style={styles.customerName}>{item.customer_name}</Text>
          </View>
          <View style={[styles.statusBadge, { backgroundColor: status.bg }]}>
            <Ionicons name={status.icon} size={14} color={status.color} style={{ marginRight: 4 }} />
            <Text style={[styles.statusLabel, { color: status.color }]}>{status.label}</Text>
          </View>
        </View>

        <View style={styles.divider} />

        <View style={styles.infoGrid}>
          <View style={styles.infoItem}>
            <Text style={styles.infoLabel}>TANGGAL</Text>
            <Text style={styles.infoValue}>{item.starts_on}</Text>
          </View>
          <View style={styles.infoItem}>
            <Text style={styles.infoLabel}>LAYANAN</Text>
            <Text style={styles.infoValue}>{item.service_type === 'private' ? 'Private' : 'Group'}</Text>
          </View>
          <View style={styles.infoItem}>
            <Text style={styles.infoLabel}>JAMAAH</Text>
            <Text style={styles.infoValue}>{item.pilgrim_count} Orang</Text>
          </View>
          <View style={styles.infoItem}>
            <Text style={styles.infoLabel}>PENDAPATAN</Text>
            <Text style={[styles.infoValue, { color: '#0984e3' }]}>{item.total_price}</Text>
          </View>
        </View>

        {item.status === 'pending' ? (
          <View style={styles.actionRow}>
            <TouchableOpacity 
              style={[styles.actionBtn, styles.approveBtn]} 
              onPress={() => handleConfirm(item.id)}
            >
              <LinearGradient colors={['#10B981', '#059669']} style={styles.btnGradient}>
                 <Text style={styles.approveBtnText}>Setujui Pesanan</Text>
              </LinearGradient>
            </TouchableOpacity>
          </View>
        ) : (
          <TouchableOpacity 
            style={styles.detailBtn}
            onPress={() => navigation.navigate('BookingDetail', { bookingId: item.id })}
          >
            <Text style={styles.detailBtnText}>Lihat Detail & Chat</Text>
            <Ionicons name="chevron-forward" size={16} color="#64748B" />
          </TouchableOpacity>
        )}
      </View>
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
              <Text style={styles.headerTitle}>Agenda Booking</Text>
              <View style={{ width: 40 }} />
            </View>

            <ScrollView horizontal showsHorizontalScrollIndicator={false} style={styles.tabScroll} contentContainerStyle={styles.tabContent}>
              {['all', 'pending', 'confirmed', 'completed', 'cancelled'].map((tab) => (
                <TouchableOpacity 
                  key={tab} 
                  style={[styles.tab, activeTab === tab && styles.activeTab]}
                  onPress={() => setActiveTab(tab)}
                >
                  <Text style={[styles.tabText, activeTab === tab && styles.activeTabText]}>
                    {tab === 'all' ? 'Semua' : tab === 'pending' ? 'Masuk' : tab.charAt(0).toUpperCase() + tab.slice(1)}
                  </Text>
                </TouchableOpacity>
              ))}
            </ScrollView>
          </SafeAreaView>
        </View>

        <ScrollView 
          showsVerticalScrollIndicator={false}
          contentContainerStyle={styles.scrollContent}
          refreshControl={<RefreshControl refreshing={refreshing} onRefresh={onRefresh} tintColor="#3B82F6" />}
        >
          {loading ? (
            <ActivityIndicator size="large" color="#3B82F6" style={{ marginTop: 50 }} />
          ) : filteredBookings.length > 0 ? (
            filteredBookings.map(renderBookingCard)
          ) : (
            <View style={styles.empty}>
              <View style={styles.emptyIconBg}>
                <Ionicons name="calendar-outline" size={64} color="#CBD5E1" />
              </View>
              <Text style={styles.emptyTitle}>Kosong</Text>
              <Text style={styles.emptySub}>Tidak ada data booking untuk kategori ini.</Text>
            </View>
          )}
        </ScrollView>
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
  headerTitle: { color: '#0F172A', fontSize: 20, fontWeight: '900' },
  
  tabScroll: { marginTop: 20 },
  tabContent: { paddingHorizontal: 20, gap: 10 },
  tab: { paddingHorizontal: 16, paddingVertical: 8, borderRadius: 20, backgroundColor: '#F8FAFC', borderWidth: 1, borderColor: '#F1F5F9' },
  activeTab: { backgroundColor: '#0F172A', borderColor: '#0F172A' },
  tabText: { color: '#64748B', fontSize: 13, fontWeight: '700' },
  activeTabText: { color: '#FFF' },

  scrollContent: { padding: 20, paddingBottom: 100 },
  card: {
    backgroundColor: '#FFF',
    borderRadius: 24,
    padding: 20,
    marginBottom: 20,
    borderWidth: 1,
    borderColor: '#F1F5F9',
    shadowColor: '#64748B', shadowOffset: { width: 0, height: 10 }, shadowOpacity: 0.05, shadowRadius: 15, elevation: 4
  },
  cardHeader: { flexDirection: 'row', justifyContent: 'space-between', alignItems: 'flex-start', marginBottom: 15 },
  bookingCode: { fontSize: 11, fontWeight: '800', color: '#94A3B8', letterSpacing: 1 },
  customerName: { fontSize: 18, fontWeight: '900', color: '#0F172A', marginTop: 4 },
  statusBadge: { flexDirection: 'row', alignItems: 'center', paddingHorizontal: 12, paddingVertical: 6, borderRadius: 12 },
  statusLabel: { fontSize: 11, fontWeight: '800', textTransform: 'uppercase' },
  
  divider: { height: 1, backgroundColor: '#F1F5F9', marginVertical: 15 },
  
  infoGrid: { flexDirection: 'row', flexWrap: 'wrap' },
  infoItem: { width: '50%', marginBottom: 15 },
  infoLabel: { fontSize: 10, fontWeight: '800', color: '#94A3B8', marginBottom: 4, letterSpacing: 0.5 },
  infoValue: { fontSize: 13, fontWeight: '700', color: '#1E293B' },
  
  actionRow: { marginTop: 5 },
  actionBtn: { borderRadius: 16, overflow: 'hidden' },
  btnGradient: { paddingVertical: 14, alignItems: 'center' },
  approveBtnText: { color: '#FFF', fontWeight: '900', fontSize: 14 },
  
  detailBtn: { flexDirection: 'row', alignItems: 'center', justifyContent: 'center', paddingVertical: 14, borderRadius: 16, backgroundColor: '#F8FAFC', borderWidth: 1, borderColor: '#F1F5F9', gap: 8 },
  detailBtnText: { color: '#64748B', fontWeight: '800', fontSize: 13 },

  empty: { flex: 1, alignItems: 'center', justifyContent: 'center', marginTop: 60, paddingHorizontal: 40 },
  emptyIconBg: { width: 120, height: 120, borderRadius: 60, backgroundColor: '#F1F5F9', justifyContent: 'center', alignItems: 'center', marginBottom: 24 },
  emptyTitle: { fontSize: 20, fontWeight: '900', color: '#0F172A', marginBottom: 12 },
  emptySub: { fontSize: 14, color: '#64748B', textAlign: 'center', lineHeight: 22 }
});
