import React, { useState, useEffect, useCallback } from 'react';
import { 
  StyleSheet, 
  Text, 
  View, 
  TouchableOpacity, 
  ScrollView, 
  ActivityIndicator,
  Alert,
  Image,
  Dimensions,
  StatusBar
} from 'react-native';
import { SafeAreaView } from 'react-native-safe-area-context';
import { LinearGradient } from 'expo-linear-gradient';
import { Ionicons } from '@expo/vector-icons';
import { apiClient } from '../api/client';
import SwipeableScreen from '../components/SwipeableScreen';

const { width } = Dimensions.get('window');

export default function BookingDetailScreen({ route, user, navigation }) {
  const { bookingId } = route.params;
  const [loading, setLoading] = useState(true);
  const [booking, setBooking] = useState(null);
  const isMuthowif = user?.user?.role === 'muthowif';

  const fetchDetail = useCallback(async () => {
    try {
      const data = await apiClient.getBookingDetail(user.token, bookingId, isMuthowif);
      setBooking(data);
    } catch (error) {
      console.error(error);
      Alert.alert('Error', 'Gagal memuat detail pesanan');
      navigation.goBack();
    } finally {
      setLoading(false);
    }
  }, [user.token, bookingId, navigation, isMuthowif]);

  useEffect(() => {
    fetchDetail();
  }, [fetchDetail]);

  const handleConfirm = async () => {
    Alert.alert('Konfirmasi Pesanan', 'Setujui permintaan pendampingan ini?', [
      { text: 'Batal', style: 'cancel' },
      { 
        text: 'Setujui', 
        onPress: async () => {
          try {
            await apiClient.confirmBooking(user.token, bookingId);
            fetchDetail();
            Alert.alert('Sukses', 'Pesanan telah disetujui.');
          } catch (error) {
            Alert.alert('Error', error.message);
          }
        }
      }
    ]);
  };

  const getStatusInfo = (status, paymentStatus) => {
    if (paymentStatus === 'paid') return { label: 'Lunas & Aman', color: '#10B981', bg: '#DCFCE7', icon: 'shield-checkmark' };
    
    switch (status) {
      case 'pending': return { label: 'Menunggu Konfirmasi', color: '#F59E0B', bg: '#FEF3C7', icon: 'time' };
      case 'confirmed': return { label: 'Siap Dibayar', color: '#3B82F6', bg: '#DBEAFE', icon: 'card' };
      case 'cancelled': return { label: 'Pesanan Dibatalkan', color: '#EF4444', bg: '#FEE2E2', icon: 'close-circle' };
      default: return { label: status, color: '#64748B', bg: '#F1F5F9', icon: 'help-circle' };
    }
  };

  const formatIDR = (amount) => {
    if (!amount) return '0';
    return amount.toString().replace(/\B(?=(\d{3})+(?!\d))/g, ".");
  };

  if (loading || !booking) {
    return (
      <View style={styles.center}>
        <ActivityIndicator size="large" color="#3B82F6" />
      </View>
    );
  }

  const statusInfo = getStatusInfo(booking.status, booking.payment_status);
  const otherParty = isMuthowif ? booking.customer : booking.muthowif_profile?.user;
  const avatar = isMuthowif ? null : booking.muthowif_profile?.avatar;

  return (
    <SwipeableScreen onSwipeBack={() => navigation.goBack()}>
      <View style={styles.container}>
        <StatusBar barStyle="dark-content" />
        <View style={styles.header}>
          <SafeAreaView edges={['top']}>
            <View style={styles.headerTop}>
              <TouchableOpacity onPress={() => navigation.goBack()} style={styles.backBtn}>
                <Ionicons name="chevron-back" size={24} color="#0F172A" />
              </TouchableOpacity>
              <View style={styles.headerTitleCenter}>
                <Text style={styles.headerTitle}>Detail Booking</Text>
                <Text style={styles.headerSub}>{booking.booking_code}</Text>
              </View>
              <TouchableOpacity style={styles.backBtn}>
                <Ionicons name="share-outline" size={22} color="#0F172A" />
              </TouchableOpacity>
            </View>
          </SafeAreaView>
        </View>

        <ScrollView showsVerticalScrollIndicator={false} contentContainerStyle={styles.scrollContent}>
          
          {/* Status Banner */}
          <View style={[styles.statusBanner, { backgroundColor: statusInfo.bg }]}>
            <Ionicons name={statusInfo.icon} size={18} color={statusInfo.color} />
            <Text style={[styles.statusLabelBanner, { color: statusInfo.color }]}>
              {statusInfo.label}
            </Text>
          </View>

          {/* Parties Info Card */}
          <View style={styles.card}>
            <View style={styles.cardHeader}>
              <Text style={styles.sectionTitle}>{isMuthowif ? 'Profil Jamaah' : 'Profil Muthowif'}</Text>
              {booking.status !== 'cancelled' && (
                <TouchableOpacity style={styles.chatBtnBadge} onPress={() => navigation.navigate('Chat', { bookingId: booking.id, bookingCode: booking.booking_code, partnerName: otherParty?.name })}>
                   <Ionicons name="chatbubble-ellipses" size={16} color="#3B82F6" />
                   <Text style={styles.chatBtnText}>Chat</Text>
                </TouchableOpacity>
              )}
            </View>
            <View style={styles.profileRow}>
              <Image 
                source={{ uri: avatar || 'https://ui-avatars.com/api/?name=' + (otherParty?.name || 'User') + '&background=0984e3&color=fff' }} 
                style={styles.avatarLarge} 
              />
              <View style={styles.profileInfo}>
                <Text style={styles.profileName}>{otherParty?.name || 'User'}</Text>
                <View style={styles.metaRow}>
                   <Ionicons name="call-outline" size={14} color="#94A3B8" />
                   <Text style={styles.profileMeta}>{otherParty?.phone || 'No Phone'}</Text>
                </View>
              </View>
            </View>
          </View>

          {/* Schedule Summary */}
          <View style={styles.card}>
            <Text style={styles.sectionTitle}>Jadwal Layanan</Text>
            <View style={styles.scheduleBox}>
              <View style={styles.scheduleItem}>
                <View style={styles.iconCircle}><Ionicons name="calendar-outline" size={20} color="#3B82F6" /></View>
                <View>
                  <Text style={styles.scheduleLabel}>Mulai</Text>
                  <Text style={styles.scheduleValue}>{new Date(booking.starts_on).toLocaleDateString('id-ID', { day: 'numeric', month: 'long', year: 'numeric' })}</Text>
                </View>
              </View>
              <View style={styles.scheduleItem}>
                <View style={styles.iconCircle}><Ionicons name="flag-outline" size={20} color="#3B82F6" /></View>
                <View>
                  <Text style={styles.scheduleLabel}>Selesai</Text>
                  <Text style={styles.scheduleValue}>{new Date(booking.ends_on).toLocaleDateString('id-ID', { day: 'numeric', month: 'long', year: 'numeric' })}</Text>
                </View>
              </View>
            </View>
            <View style={styles.infoBadgeRow}>
               <View style={styles.infoBadge}>
                 <Ionicons name="people-outline" size={14} color="#64748B" />
                 <Text style={styles.infoBadgeText}>{booking.pilgrim_count} Jamaah</Text>
               </View>
               <View style={styles.infoBadge}>
                 <Ionicons name="briefcase-outline" size={14} color="#64748B" />
                 <Text style={styles.infoBadgeText}>{booking.service_type === 'private' ? 'Private' : 'Group'}</Text>
               </View>
            </View>
          </View>

          {/* Price Detail - PREMIUM STYLE */}
          <View style={styles.card}>
            <Text style={styles.sectionTitle}>Rincian Biaya</Text>
            
            <View style={styles.priceRow}>
              <Text style={styles.priceName}>Harga Dasar Layanan</Text>
              <Text style={styles.priceVal}>Rp {formatIDR(booking.daily_price_snapshot)}</Text>
            </View>

            {booking.with_same_hotel && (
              <View style={styles.priceRow}>
                <Text style={styles.priceName}>Layanan Hotel</Text>
                <Text style={styles.priceVal}>+ Rp {formatIDR(booking.same_hotel_price_snapshot)}</Text>
              </View>
            )}

            {booking.with_transport && (
              <View style={styles.priceRow}>
                <Text style={styles.priceName}>Transportasi</Text>
                <Text style={styles.priceVal}>+ Rp {formatIDR(booking.transport_price_snapshot)}</Text>
              </View>
            )}

            {booking.add_ons_snapshot && booking.add_ons_snapshot.length > 0 && (
              <View style={styles.addOnSection}>
                {booking.add_ons_snapshot.map((addon, idx) => (
                  <View key={idx} style={styles.priceRow}>
                    <Text style={styles.priceName}>Add-on: {addon.name}</Text>
                    <Text style={styles.priceVal}>+ Rp {formatIDR(addon.price)}</Text>
                  </View>
                ))}
              </View>
            )}

            <View style={styles.totalBox}>
              <Text style={styles.totalLabel}>Total Pembayaran</Text>
              <Text style={styles.totalValue}>Rp {formatIDR(booking.total_amount)}</Text>
            </View>
          </View>

          {/* Document Verification */}
          <View style={styles.card}>
            <Text style={styles.sectionTitle}>Dokumen & Verifikasi</Text>
            <View style={styles.docGrid}>
              <View style={styles.docItem}>
                <View style={styles.docCheck}><Ionicons name="checkmark-circle" size={18} color="#10B981" /></View>
                <Text style={styles.docText}>Tiket Pesawat</Text>
              </View>
              <View style={styles.docItem}>
                <View style={styles.docCheck}><Ionicons name="checkmark-circle" size={18} color="#10B981" /></View>
                <Text style={styles.docText}>Visa / Paspor</Text>
              </View>
            </View>
          </View>

        </ScrollView>

        {/* Action Footer */}
        <View style={styles.footer}>
          {!isMuthowif && booking.status === 'confirmed' && booking.payment_status === 'pending' && (
            <TouchableOpacity 
              style={styles.primaryBtn}
              onPress={() => navigation.navigate('Payment', { 
                booking_id: booking.id,
                booking_code: booking.booking_code,
                amount: booking.amount_due
              })}
            >
              <LinearGradient colors={['#3B82F6', '#2563EB']} style={styles.btnGradient}>
                <Ionicons name="card" size={20} color="#FFF" />
                <Text style={styles.btnText}>Bayar Sekarang</Text>
              </LinearGradient>
            </TouchableOpacity>
          )}

          {isMuthowif && booking.status === 'pending' && (
            <TouchableOpacity style={styles.primaryBtn} onPress={handleConfirm}>
              <LinearGradient colors={['#10B981', '#059669']} style={styles.btnGradient}>
                <Ionicons name="checkmark-circle" size={20} color="#FFF" />
                <Text style={styles.btnText}>Setujui Permintaan</Text>
              </LinearGradient>
            </TouchableOpacity>
          )}

          {booking.status !== 'cancelled' && (
            <TouchableOpacity 
              style={styles.secondaryBtn}
              onPress={() => navigation.navigate('Chat', { bookingId: booking.id, bookingCode: booking.booking_code, partnerName: otherParty?.name })}
            >
              <Ionicons name="chatbubbles" size={20} color="#3B82F6" />
              <Text style={styles.secondaryBtnText}>Kirim Pesan Chat</Text>
            </TouchableOpacity>
          )}
        </View>
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
  backBtn: { width: 44, height: 44, borderRadius: 22, backgroundColor: '#F8FAFC', justifyContent: 'center', alignItems: 'center' },
  headerTitleCenter: { alignItems: 'center' },
  headerTitle: { color: '#0F172A', fontSize: 18, fontWeight: '900' },
  headerSub: { color: '#94A3B8', fontSize: 11, fontWeight: '700', marginTop: 2, letterSpacing: 1 },
  
  scrollContent: { padding: 20, paddingBottom: 120 },
  
  statusBanner: { flexDirection: 'row', alignItems: 'center', justifyContent: 'center', paddingVertical: 14, borderRadius: 20, marginBottom: 20, gap: 10 },
  statusLabelBanner: { fontSize: 14, fontWeight: '900', textTransform: 'uppercase', letterSpacing: 0.5 },

  card: {
    backgroundColor: '#FFF',
    borderRadius: 28,
    padding: 24,
    marginBottom: 20,
    borderWidth: 1,
    borderColor: '#F1F5F9',
    shadowColor: '#64748B', shadowOffset: { width: 0, height: 10 }, shadowOpacity: 0.05, shadowRadius: 15, elevation: 4
  },
  cardHeader: { flexDirection: 'row', justifyContent: 'space-between', alignItems: 'center', marginBottom: 20 },
  sectionTitle: { fontSize: 16, fontWeight: '900', color: '#0F172A', letterSpacing: -0.3 },
  chatBtnBadge: { flexDirection: 'row', alignItems: 'center', gap: 6, backgroundColor: '#EFF6FF', paddingHorizontal: 12, paddingVertical: 6, borderRadius: 12 },
  chatBtnText: { fontSize: 12, fontWeight: '800', color: '#3B82F6' },
  
  profileRow: { flexDirection: 'row', alignItems: 'center' },
  avatarLarge: { width: 64, height: 64, borderRadius: 22, backgroundColor: '#F8FAFC' },
  profileInfo: { marginLeft: 16, flex: 1 },
  profileName: { fontSize: 18, fontWeight: '900', color: '#1E293B', marginBottom: 6 },
  metaRow: { flexDirection: 'row', alignItems: 'center', gap: 6 },
  profileMeta: { fontSize: 13, color: '#94A3B8', fontWeight: '600' },

  scheduleBox: { flexDirection: 'row', justifyContent: 'space-between', marginTop: 10, gap: 15 },
  scheduleItem: { flex: 1, flexDirection: 'row', alignItems: 'center', gap: 12 },
  iconCircle: { width: 40, height: 40, borderRadius: 14, backgroundColor: '#F0F9FF', justifyContent: 'center', alignItems: 'center' },
  scheduleLabel: { fontSize: 10, fontWeight: '800', color: '#94A3B8', marginBottom: 2, textTransform: 'uppercase' },
  scheduleValue: { fontSize: 13, fontWeight: '700', color: '#1E293B' },
  
  infoBadgeRow: { flexDirection: 'row', gap: 10, marginTop: 20, paddingTop: 20, borderTopWidth: 1, borderTopColor: '#F1F5F9' },
  infoBadge: { flexDirection: 'row', alignItems: 'center', gap: 6, backgroundColor: '#F8FAFC', paddingHorizontal: 12, paddingVertical: 8, borderRadius: 12 },
  infoBadgeText: { fontSize: 12, fontWeight: '700', color: '#64748B' },

  priceRow: { flexDirection: 'row', justifyContent: 'space-between', marginBottom: 14, gap: 10 },
  priceName: { fontSize: 14, color: '#64748B', fontWeight: '600', flex: 1 },
  priceVal: { fontSize: 14, fontWeight: '800', color: '#1E293B', textAlign: 'right' },
  addOnSection: { marginTop: 4, paddingTop: 14, borderTopWidth: 1, borderTopColor: '#F1F5F9', borderStyle: 'dashed' },

  totalBox: { marginTop: 10, paddingTop: 20, borderTopWidth: 2, borderTopColor: '#F1F5F9', borderStyle: 'dashed', flexDirection: 'row', justifyContent: 'space-between', alignItems: 'center', gap: 10 },
  totalLabel: { fontSize: 15, fontWeight: '900', color: '#0F172A', flex: 1 },
  totalValue: { fontSize: 22, fontWeight: '900', color: '#3B82F6', textAlign: 'right' },

  docGrid: { flexDirection: 'row', gap: 12, marginTop: 10 },
  docItem: { flex: 1, flexDirection: 'row', alignItems: 'center', gap: 8, backgroundColor: '#F0FDF4', padding: 12, borderRadius: 16 },
  docCheck: { width: 24, height: 24, borderRadius: 12, backgroundColor: '#FFF', justifyContent: 'center', alignItems: 'center' },
  docText: { fontSize: 12, fontWeight: '800', color: '#166534' },

  footer: { position: 'absolute', bottom: 0, left: 0, right: 0, padding: 24, backgroundColor: 'rgba(255, 255, 255, 0.9)', borderTopWidth: 1, borderTopColor: '#F1F5F9' },
  primaryBtn: { borderRadius: 20, overflow: 'hidden' },
  btnGradient: { flexDirection: 'row', alignItems: 'center', justifyContent: 'center', gap: 10, paddingVertical: 18 },
  btnText: { color: '#FFF', fontSize: 16, fontWeight: '900' },
  secondaryBtn: { flexDirection: 'row', alignItems: 'center', justifyContent: 'center', gap: 10, paddingVertical: 18, borderRadius: 20, backgroundColor: '#EFF6FF', borderWidth: 1, borderColor: '#DBEAFE' },
  secondaryBtnText: { color: '#3B82F6', fontSize: 16, fontWeight: '900' },
  center: { flex: 1, justifyContent: 'center', alignItems: 'center' }
});
