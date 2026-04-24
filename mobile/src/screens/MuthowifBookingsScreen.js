import React, { useState, useEffect } from 'react';
import { 
  StyleSheet, 
  Text, 
  View, 
  TouchableOpacity, 
  ScrollView, 
  ActivityIndicator,
  Alert,
  RefreshControl,
  Dimensions
} from 'react-native';
import { SafeAreaView } from 'react-native-safe-area-context';
import { StatusBar } from 'expo-status-bar';
import { apiClient } from '../api/client';

const { width } = Dimensions.get('window');

export default function MuthowifBookingsScreen({ user, navigation }) {
  const [loading, setLoading] = useState(true);
  const [refreshing, setRefreshing] = useState(false);
  const [bookings, setBookings] = useState([]);
  const [activeTab, setActiveTab] = useState('all'); // 'all', 'pending', 'confirmed', 'completed'

  const fetchBookings = async () => {
    try {
      const data = await apiClient.getMuthowifBookings(user.token);
      setBookings(data.bookings || []);
    } catch (error) {
      console.error('Fetch Bookings Error:', error);
    } finally {
      setLoading(false);
      setRefreshing(false);
    }
  };

  useEffect(() => {
    fetchBookings();
  }, []);

  const handleConfirm = (id) => {
    Alert.alert(
      'Konfirmasi Pesanan',
      'Apakah Anda bersedia membimbing jamaah ini pada tanggal tersebut?',
      [
        { text: 'Batal', style: 'cancel' },
        { 
          text: 'Ya, Setujui', 
          onPress: async () => {
            try {
              await apiClient.confirmBooking(user.token, id);
              fetchBookings();
              Alert.alert('Sukses', 'Pesanan berhasil disetujui');
            } catch (error) {
              Alert.alert('Error', error.message || 'Gagal menyetujui pesanan');
            }
          }
        }
      ]
    );
  };

  const handleCancel = (id) => {
    Alert.alert(
      'Tolak Pesanan',
      'Apakah Anda yakin ingin menolak pesanan ini?',
      [
        { text: 'Batal', style: 'cancel' },
        { 
          text: 'Ya, Tolak', 
          style: 'destructive',
          onPress: async () => {
            try {
              await apiClient.cancelBooking(user.token, id);
              fetchBookings();
            } catch (error) {
              Alert.alert('Error', error.message || 'Gagal membatalkan pesanan');
            }
          }
        }
      ]
    );
  };

  const filteredBookings = bookings.filter(b => {
    if (activeTab === 'all') return true;
    return b.status === activeTab;
  });

  const getStatusColor = (status) => {
    switch (status) {
      case 'pending': return { bg: '#FEF3C7', text: '#92400E', dot: '#F59E0B' };
      case 'confirmed': return { bg: '#D1FAE5', text: '#065F46', dot: '#10B981' };
      case 'completed': return { bg: '#F1F5F9', text: '#475569', dot: '#94A3B8' };
      case 'cancelled': return { bg: '#FEE2E2', text: '#991B1B', dot: '#EF4444' };
      default: return { bg: '#F1F5F9', text: '#475569', dot: '#94A3B8' };
    }
  };

  return (
    <SafeAreaView style={styles.container}>
      <StatusBar style="dark" />
      
      {/* Header */}
      <View style={styles.header}>
        <TouchableOpacity onPress={() => navigation.navigate('Dashboard')}>
          <Text style={styles.backBtn}>←</Text>
        </TouchableOpacity>
        <Text style={styles.headerTitle}>Daftar Booking</Text>
        <View style={{ width: 40 }} />
      </View>

      {/* Tabs */}
      <View style={styles.tabContainer}>
        <ScrollView horizontal showsHorizontalScrollIndicator={false} contentContainerStyle={styles.tabScroll}>
          {['all', 'pending', 'confirmed', 'completed', 'cancelled'].map((tab) => (
            <TouchableOpacity 
              key={tab} 
              style={[styles.tab, activeTab === tab && styles.activeTab]}
              onPress={() => setActiveTab(tab)}
            >
              <Text style={[styles.tabText, activeTab === tab && styles.activeTabText]}>
                {tab.charAt(0).toUpperCase() + tab.slice(1)}
              </Text>
            </TouchableOpacity>
          ))}
        </ScrollView>
      </View>

      <ScrollView 
        showsVerticalScrollIndicator={false}
        contentContainerStyle={styles.scrollContent}
        refreshControl={<RefreshControl refreshing={refreshing} onRefresh={fetchBookings} color="#0984e3" />}
      >
        {loading ? (
          <ActivityIndicator size="large" color="#0984e3" style={{ marginTop: 50 }} />
        ) : filteredBookings.length > 0 ? (
          filteredBookings.map((item) => {
            const colors = getStatusColor(item.status);
            return (
              <View key={item.id} style={styles.card}>
                <View style={styles.cardHeader}>
                  <View>
                    <Text style={styles.bookingCode}>{item.booking_code}</Text>
                    <Text style={styles.customerName}>{item.customer_name}</Text>
                  </View>
                  <View style={[styles.statusBadge, { backgroundColor: colors.bg }]}>
                    <View style={[styles.statusDot, { backgroundColor: colors.dot }]} />
                    <Text style={[styles.statusText, { color: colors.text }]}>{item.status_label}</Text>
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
                    <Text style={styles.infoValue}>{item.service_type}</Text>
                  </View>
                  <View style={styles.infoItem}>
                    <Text style={styles.infoLabel}>JAMAAH</Text>
                    <Text style={styles.infoValue}>{item.pilgrim_count} Orang</Text>
                  </View>
                  <View style={styles.infoItem}>
                    <Text style={styles.infoLabel}>TOTAL</Text>
                    <Text style={[styles.infoValue, { color: '#0984e3' }]}>{item.total_price}</Text>
                  </View>
                </View>

                {item.status === 'pending' && (
                  <View style={styles.actionRow}>
                    <TouchableOpacity 
                      style={[styles.actionBtn, styles.rejectBtn]} 
                      onPress={() => handleCancel(item.id)}
                    >
                      <Text style={styles.rejectBtnText}>Tolak</Text>
                    </TouchableOpacity>
                    <TouchableOpacity 
                      style={[styles.actionBtn, styles.approveBtn]} 
                      onPress={() => handleConfirm(item.id)}
                    >
                      <Text style={styles.approveBtnText}>Setujui</Text>
                    </TouchableOpacity>
                  </View>
                )}

                {(item.status === 'confirmed' || item.status === 'completed' || item.status === 'cancelled') && (
                  <TouchableOpacity 
                    style={styles.detailBtn}
                    onPress={() => navigation.navigate('BookingDetail', { bookingId: item.id })}
                  >
                    <Text style={styles.detailBtnText}>Lihat Detail</Text>
                  </TouchableOpacity>
                )}
              </View>
            );
          })
        ) : (
          <View style={styles.emptyContainer}>
            <Text style={{fontSize: 50, marginBottom: 20}}>📄</Text>
            <Text style={styles.emptyTitle}>Tidak Ada Pesanan</Text>
            <Text style={styles.emptySub}>Belum ada data booking untuk kategori ini.</Text>
          </View>
        )}
      </ScrollView>
    </SafeAreaView>
  );
}

const styles = StyleSheet.create({
  container: { flex: 1, backgroundColor: '#F8FAFC' },
  header: { 
    flexDirection: 'row', 
    justifyContent: 'space-between', 
    alignItems: 'center', 
    paddingHorizontal: 20, 
    paddingVertical: 15,
    backgroundColor: '#fff',
    borderBottomWidth: 1,
    borderBottomColor: '#F1F5F9'
  },
  backBtn: { fontSize: 24, fontWeight: 'bold', color: '#1E293B', width: 40 },
  headerTitle: { fontSize: 18, fontWeight: '800', color: '#1E293B' },
  
  tabContainer: { backgroundColor: '#fff', paddingBottom: 10 },
  tabScroll: { paddingHorizontal: 15 },
  tab: { paddingHorizontal: 20, paddingVertical: 10, borderRadius: 12, marginRight: 8, backgroundColor: '#F1F5F9' },
  activeTab: { backgroundColor: '#0984e3' },
  tabText: { fontSize: 13, fontWeight: '700', color: '#64748B' },
  activeTabText: { color: '#fff' },

  scrollContent: { padding: 15, paddingBottom: 50 },
  
  card: {
    backgroundColor: '#fff',
    borderRadius: 24,
    padding: 20,
    marginBottom: 15,
    borderWidth: 1,
    borderColor: '#F1F5F9',
    shadowColor: '#000', shadowOffset: { width: 0, height: 2 }, shadowOpacity: 0.03, shadowRadius: 10, elevation: 2
  },
  cardHeader: { flexDirection: 'row', justifyContent: 'space-between', alignItems: 'flex-start', marginBottom: 15 },
  bookingCode: { fontSize: 10, fontWeight: '800', color: '#94A3B8', letterSpacing: 1 },
  customerName: { fontSize: 18, fontWeight: '800', color: '#1E293B', marginTop: 2 },
  statusBadge: { flexDirection: 'row', alignItems: 'center', paddingHorizontal: 10, paddingVertical: 5, borderRadius: 10 },
  statusDot: { width: 6, height: 6, borderRadius: 3, marginRight: 6 },
  statusText: { fontSize: 11, fontWeight: '800' },
  
  divider: { height: 1, backgroundColor: '#F1F5F9', marginBottom: 15 },
  
  infoGrid: { flexDirection: 'row', flexWrap: 'wrap' },
  infoItem: { width: '50%', marginBottom: 15 },
  infoLabel: { fontSize: 9, fontWeight: '800', color: '#94A3B8', marginBottom: 4, letterSpacing: 0.5 },
  infoValue: { fontSize: 13, fontWeight: '700', color: '#1E293B' },
  
  actionRow: { flexDirection: 'row', gap: 10, marginTop: 5 },
  actionBtn: { flex: 1, paddingVertical: 12, borderRadius: 12, alignItems: 'center', justifyContent: 'center' },
  rejectBtn: { backgroundColor: '#fff', borderWidth: 1, borderColor: '#E2E8F0' },
  rejectBtnText: { color: '#64748B', fontWeight: '800', fontSize: 13 },
  approveBtn: { backgroundColor: '#0984e3' },
  approveBtnText: { color: '#fff', fontWeight: '800', fontSize: 13 },
  
  detailBtn: { width: '100%', paddingVertical: 12, borderRadius: 12, backgroundColor: '#F8FAFC', borderWidth: 1, borderColor: '#F1F5F9', alignItems: 'center', marginTop: 5 },
  detailBtnText: { color: '#1E293B', fontWeight: '800', fontSize: 13 },

  emptyContainer: { alignItems: 'center', justifyContent: 'center', marginTop: 80 },
  emptyTitle: { fontSize: 20, fontWeight: '800', color: '#1E293B', marginBottom: 8 },
  emptySub: { fontSize: 14, color: '#94A3B8', textAlign: 'center' }
});
