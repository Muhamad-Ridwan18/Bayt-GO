import React, { useState, useEffect } from 'react';
import { 
  StyleSheet, 
  Text, 
  View, 
  TouchableOpacity, 
  ScrollView, 
  ActivityIndicator,
  Alert,
  Dimensions
} from 'react-native';
import { SafeAreaView } from 'react-native-safe-area-context';
import { StatusBar } from 'expo-status-bar';
import { Ionicons } from '@expo/vector-icons';
import { apiClient } from '../api/client';

export default function BookingDetailScreen({ route, user, navigation }) {
  const { bookingId } = route.params;
  const [loading, setLoading] = useState(true);
  const [booking, setBooking] = useState(null);

  const fetchDetail = async () => {
    try {
      const data = await apiClient.getBookingDetail(user.token, bookingId);
      setBooking(data.booking);
    } catch (error) {
      Alert.alert('Error', 'Gagal memuat detail pesanan');
      navigation.goBack();
    } finally {
      setLoading(false);
    }
  };

  useEffect(() => {
    fetchDetail();
  }, []);

  const handleConfirm = async () => {
    Alert.alert('Setujui Pesanan', 'Konfirmasi ketersediaan Anda untuk jadwal ini?', [
      { text: 'Batal', style: 'cancel' },
      { 
        text: 'Setujui', 
        onPress: async () => {
          try {
            await apiClient.confirmBooking(user.token, bookingId);
            fetchDetail();
            Alert.alert('Sukses', 'Pesanan disetujui');
          } catch (error) {
            Alert.alert('Error', error.message);
          }
        }
      }
    ]);
  };

  const getStatusColor = (status) => {
    switch (status) {
      case 'pending': return { bg: '#FEF3C7', text: '#92400E', dot: '#F59E0B' };
      case 'confirmed': return { bg: '#D1FAE5', text: '#065F46', dot: '#10B981' };
      case 'completed': return { bg: '#F1F5F9', text: '#475569', dot: '#94A3B8' };
      case 'cancelled': return { bg: '#FEE2E2', text: '#991B1B', dot: '#EF4444' };
      default: return { bg: '#F1F5F9', text: '#475569', dot: '#94A3B8' };
    }
  };

  if (loading) {
    return (
      <View style={styles.center}>
        <ActivityIndicator size="large" color="#0984e3" />
      </View>
    );
  }

  const colors = getStatusColor(booking.status);

  return (
    <SafeAreaView style={styles.container}>
      <StatusBar style="dark" />
      
      {/* Header */}
      <View style={styles.header}>
        <TouchableOpacity onPress={() => navigation.goBack()}>
          <Text style={styles.backBtn}>←</Text>
        </TouchableOpacity>
        <Text style={styles.headerTitle}>Detail Booking</Text>
        <TouchableOpacity 
          onPress={() => navigation.navigate('Chat', { 
            bookingId: booking?.id, 
            bookingCode: booking?.booking_code,
            partnerName: booking?.customer_name,
            from: 'BookingDetail'
          })}
        >
          <View style={styles.chatIconBtn}>
            <Ionicons name="chatbubble-ellipses-outline" size={20} color="#0984e3" />
          </View>
        </TouchableOpacity>
      </View>

      <ScrollView showsVerticalScrollIndicator={false} contentContainerStyle={styles.scrollContent}>
        
        {/* Status Section */}
        <View style={styles.statusSection}>
          <View style={[styles.statusBadge, { backgroundColor: colors.bg }]}>
            <View style={[styles.statusDot, { backgroundColor: colors.dot }]} />
            <Text style={[styles.statusText, { color: colors.text }]}>{booking.status_label}</Text>
          </View>
          <Text style={styles.bookingCode}>{booking.booking_code}</Text>
        </View>

        {/* Customer Card */}
        <View style={styles.card}>
          <Text style={styles.cardLabel}>JAMAAH</Text>
          <Text style={styles.customerName}>{booking.customer_name}</Text>
          <Text style={styles.customerInfo}>{booking.customer_email}</Text>
          <Text style={styles.customerInfo}>{booking.customer_phone}</Text>
        </View>

        {/* Schedule Card */}
        <View style={styles.card}>
          <Text style={styles.cardLabel}>JADWAL & LAYANAN</Text>
          <View style={styles.infoRow}>
            <View style={styles.infoItem}>
              <Text style={styles.infoLabel}>TANGGAL MULAI</Text>
              <Text style={styles.infoValue}>{booking.starts_on}</Text>
            </View>
            <View style={styles.infoItem}>
              <Text style={styles.infoLabel}>TANGGAL SELESAI</Text>
              <Text style={styles.infoValue}>{booking.ends_on}</Text>
            </View>
          </View>
          <View style={[styles.infoRow, { marginTop: 15 }]}>
            <View style={styles.infoItem}>
              <Text style={styles.infoLabel}>TIPE LAYANAN</Text>
              <Text style={styles.infoValue}>{booking.service_type}</Text>
            </View>
            <View style={styles.infoItem}>
              <Text style={styles.infoLabel}>JUMLAH JAMAAH</Text>
              <Text style={styles.infoValue}>{booking.pilgrim_count} Orang</Text>
            </View>
          </View>
        </View>

        {/* Pricing Card */}
        <View style={styles.card}>
          <Text style={styles.cardLabel}>RINCIAN BIAYA</Text>
          <View style={styles.priceRow}>
            <Text style={styles.priceLabel}>Biaya Layanan Utama</Text>
            <Text style={styles.priceValue}>{booking.total_price}</Text>
          </View>
          
          {booking.add_ons && booking.add_ons.length > 0 && (
            <View style={styles.addOnSection}>
              <Text style={styles.addOnHeader}>Add-Ons Tambahan:</Text>
              {booking.add_ons.map((addon, idx) => (
                <View key={idx} style={styles.priceRow}>
                  <Text style={styles.addOnName}>+ {addon.name}</Text>
                  <Text style={styles.addOnPrice}>Rp {addon.price.toLocaleString('id-ID')}</Text>
                </View>
              ))}
            </View>
          )}

          <View style={styles.optionsRow}>
            {booking.with_same_hotel && <Text style={styles.optionTag}>✓ Hotel Sama</Text>}
            {booking.with_transport && <Text style={styles.optionTag}>✓ Transportasi</Text>}
          </View>
        </View>

        {/* Notes Card */}
        <View style={styles.card}>
          <Text style={styles.cardLabel}>CATATAN JAMAAH</Text>
          <Text style={styles.notesText}>{booking.notes}</Text>
        </View>

        {/* Action Buttons */}
        {['confirmed', 'ongoing'].includes(booking.status) && (
          <TouchableOpacity 
            style={styles.chatBtn}
            onPress={() => navigation.navigate('Chat', {
              bookingId: booking.id,
              bookingCode: booking.booking_code,
              partnerName: booking.customer_name,
              from: 'BookingDetail'
            })}
          >
            <Ionicons name="chatbubble-ellipses-outline" size={18} color="#0984e3" />
            <Text style={styles.chatBtnText}>Buka Chat Jamaah</Text>
          </TouchableOpacity>
        )}

        {booking.status === 'pending' && (
          <TouchableOpacity style={styles.approveBtn} onPress={handleConfirm}>
            <Text style={styles.approveBtnText}>Setujui Pesanan Ini</Text>
          </TouchableOpacity>
        )}

      </ScrollView>
    </SafeAreaView>
  );
}

const styles = StyleSheet.create({
  container: { flex: 1, backgroundColor: '#F8FAFC' },
  center: { flex: 1, justifyContent: 'center', alignItems: 'center' },
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
  
  scrollContent: { padding: 20, paddingBottom: 50 },
  
  statusSection: { alignItems: 'center', marginBottom: 25 },
  statusBadge: { flexDirection: 'row', alignItems: 'center', paddingHorizontal: 15, paddingVertical: 8, borderRadius: 20, marginBottom: 10 },
  statusDot: { width: 8, height: 8, borderRadius: 4, marginRight: 8 },
  statusText: { fontSize: 13, fontWeight: '800' },
  bookingCode: { fontSize: 14, fontWeight: '800', color: '#94A3B8', letterSpacing: 2 },

  card: {
    backgroundColor: '#fff',
    borderRadius: 24,
    padding: 20,
    marginBottom: 20,
    borderWidth: 1,
    borderColor: '#F1F5F9'
  },
  cardLabel: { fontSize: 10, fontWeight: '800', color: '#94A3B8', marginBottom: 15, letterSpacing: 1 },
  customerName: { fontSize: 22, fontWeight: '800', color: '#1E293B' },
  customerInfo: { fontSize: 14, color: '#64748B', marginTop: 5, fontWeight: '600' },

  infoRow: { flexDirection: 'row', justifyContent: 'space-between' },
  infoItem: { flex: 1 },
  infoLabel: { fontSize: 9, fontWeight: '800', color: '#94A3B8', marginBottom: 5 },
  infoValue: { fontSize: 15, fontWeight: '700', color: '#1E293B' },

  priceRow: { flexDirection: 'row', justifyContent: 'space-between', alignItems: 'center', marginBottom: 10 },
  priceLabel: { fontSize: 14, fontWeight: '600', color: '#64748B' },
  priceValue: { fontSize: 16, fontWeight: '800', color: '#1E293B' },
  
  addOnSection: { marginTop: 10, paddingTop: 10, borderTopWidth: 1, borderTopColor: '#F1F5F9' },
  addOnHeader: { fontSize: 12, fontWeight: '800', color: '#1E293B', marginBottom: 10 },
  addOnName: { fontSize: 13, color: '#64748B', fontWeight: '600' },
  addOnPrice: { fontSize: 13, color: '#1E293B', fontWeight: '700' },
  
  optionsRow: { flexDirection: 'row', gap: 10, marginTop: 10 },
  optionTag: { backgroundColor: '#F0F9FF', color: '#0369A1', fontSize: 11, fontWeight: '700', paddingHorizontal: 10, paddingVertical: 5, borderRadius: 8 },

  notesText: { fontSize: 14, color: '#475569', lineHeight: 22, fontWeight: '500' },

  approveBtn: {
    backgroundColor: '#0984e3',
    paddingVertical: 18,
    borderRadius: 18,
    alignItems: 'center',
    marginTop: 10,
    shadowColor: '#0984e3', shadowOffset: { width: 0, height: 4 }, shadowOpacity: 0.2, shadowRadius: 10, elevation: 5
  },
  approveBtnText: { color: '#fff', fontSize: 16, fontWeight: '800' },
  chatBtn: {
    flexDirection: 'row',
    alignItems: 'center',
    justifyContent: 'center',
    gap: 8,
    borderWidth: 1.5,
    borderColor: '#0984e3',
    paddingVertical: 16,
    borderRadius: 18,
    marginTop: 10,
    marginBottom: 10,
    backgroundColor: '#EFF6FF',
  },
  chatBtnText: { color: '#0984e3', fontSize: 15, fontWeight: '800' },
  chatIconBtn: {
    width: 36,
    height: 36,
    borderRadius: 10,
    backgroundColor: '#EFF6FF',
    justifyContent: 'center',
    alignItems: 'center',
  }
});
