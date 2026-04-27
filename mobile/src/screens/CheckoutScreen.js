import React, { useState, useMemo } from 'react';
import { 
  StyleSheet, 
  Text, 
  View, 
  TouchableOpacity, 
  ScrollView, 
  TextInput, 
  ActivityIndicator, 
  Alert,
  Dimensions,
  Platform,
  StatusBar
} from 'react-native';
import { SafeAreaView } from 'react-native-safe-area-context';
import { LinearGradient } from 'expo-linear-gradient';
import { Ionicons } from '@expo/vector-icons';
import { apiClient } from '../api/client';
import SwipeableScreen from '../components/SwipeableScreen';

const { width } = Dimensions.get('window');

export default function CheckoutScreen({ route, user, navigation }) {
  const { muthowif, service, startDate, endDate } = route.params;
  
  const [pilgrimCount, setPilgrimCount] = useState('1');
  const [withSameHotel, setWithSameHotel] = useState(false);
  const [withTransport, setWithTransport] = useState(false);
  const [selectedAddOns, setSelectedAddOns] = useState([]);
  const [loading, setLoading] = useState(false);

  // Perhitungan hari (100% PARITY DENGAN WEB)
  const days = useMemo(() => {
    const start = new Date(startDate);
    const end = new Date(endDate);
    const diffTime = Math.abs(end - start);
    return Math.ceil(diffTime / (1000 * 60 * 60 * 24)) + 1;
  }, [startDate, endDate]);

  const toggleAddOn = (addon) => {
    if (selectedAddOns.some(a => a.name === addon.name)) {
      setSelectedAddOns(selectedAddOns.filter(a => a.name !== addon.name));
    } else {
      setSelectedAddOns([...selectedAddOns, addon]);
    }
  };

  const totalPrice = useMemo(() => {
    let total = parseFloat(service.daily_price) * days;
    if (withSameHotel) total += parseFloat(service.same_hotel_price_per_day) * days;
    if (withTransport) total += parseFloat(service.transport_price_flat);
    
    selectedAddOns.forEach(addon => {
      total += parseFloat(addon.price);
    });
    
    return total;
  }, [service, days, withSameHotel, withTransport, selectedAddOns]);

  const handleBooking = async () => {
    const count = parseInt(pilgrimCount);
    if (isNaN(count) || count < 1) {
      Alert.alert('Perhatian', 'Jumlah jamaah harus valid');
      return;
    }

    setLoading(true);
    try {
      const result = await apiClient.createBooking(user.token, {
        muthowif_id: muthowif.id,
        service_id: service.id,
        starts_on: startDate,
        ends_on: endDate,
        pilgrim_count: count,
        with_same_hotel: withSameHotel,
        with_transport: withTransport,
        add_ons: selectedAddOns
      });

      Alert.alert('Sukses', 'Pesanan berhasil dibuat. Menunggu konfirmasi Muthowif.', [
        { text: 'Lihat Pesanan', onPress: () => navigation.navigate('BookingList') }
      ]);
    } catch (error) {
      Alert.alert('Gagal', error.message || 'Terjadi kesalahan saat memesan');
    } finally {
      setLoading(false);
    }
  };

  const formatIDR = (num) => num.toString().replace(/\B(?=(\d{3})+(?!\d))/g, ".");

  return (
    <SwipeableScreen onSwipeBack={() => navigation.goBack()}>
      <View style={styles.container}>
        <StatusBar barStyle="dark-content" />
        <View style={styles.header}>
          <SafeAreaView edges={['top']}>
            <View style={styles.headerContent}>
              <TouchableOpacity onPress={() => navigation.goBack()} style={styles.backBtn}>
                <Ionicons name="chevron-back" size={24} color="#0F172A" />
              </TouchableOpacity>
              <View style={styles.headerTitleBox}>
                <Text style={styles.headerTitle}>Konfirmasi Pesanan</Text>
                <Text style={styles.headerSub}>Langkah Terakhir</Text>
              </View>
              <View style={{ width: 44 }} />
            </View>
          </SafeAreaView>
        </View>

        <ScrollView showsVerticalScrollIndicator={false} contentContainerStyle={styles.scrollContent}>
          
          {/* Summary Banner */}
          <View style={styles.serviceBanner}>
            <LinearGradient colors={['#F8FAFC', '#F1F5F9']} style={styles.bannerInner}>
              <View style={styles.bannerIcon}><Ionicons name="briefcase" size={24} color="#3B82F6" /></View>
              <View style={{ flex: 1 }}>
                <Text style={styles.bannerLabel}>LAYANAN PILIHAN</Text>
                <Text style={styles.bannerName}>{service.name}</Text>
                <Text style={styles.bannerDuration}>{days} Hari • {startDate} s/d {endDate}</Text>
              </View>
            </LinearGradient>
          </View>

          {/* Form Options */}
          <View style={styles.section}>
            <Text style={styles.sectionTitle}>Jumlah Jamaah</Text>
            <View style={styles.inputWrapper}>
              <Ionicons name="people-outline" size={20} color="#94A3B8" style={styles.inputIcon} />
              <TextInput 
                style={styles.input}
                value={pilgrimCount}
                onChangeText={setPilgrimCount}
                keyboardType="numeric"
                placeholder="Contoh: 2"
                placeholderTextColor="#CBD5E1"
              />
            </View>
          </View>

          <View style={styles.section}>
            <Text style={styles.sectionTitle}>Layanan Tambahan</Text>
            
            <TouchableOpacity 
              style={[styles.optionCard, withSameHotel && styles.optionCardActive]}
              onPress={() => setWithSameHotel(!withSameHotel)}
              activeOpacity={0.7}
            >
              <View style={styles.optionInfo}>
                <View style={[styles.optionIcon, withSameHotel && styles.optionIconActive]}>
                  <Ionicons name="business-outline" size={22} color={withSameHotel ? '#FFF' : '#3B82F6'} />
                </View>
                <View style={{ flex: 1 }}>
                  <Text style={[styles.optionTitle, withSameHotel && styles.optionTitleActive]}>Hotel Yang Sama</Text>
                  <Text style={styles.optionPrice}>Rp {formatIDR(service.same_hotel_price_per_day)} /hari</Text>
                </View>
                <View style={[styles.checkbox, withSameHotel && styles.checkboxActive]}>
                  {withSameHotel && <Ionicons name="checkmark" size={16} color="#FFF" />}
                </View>
              </View>
            </TouchableOpacity>

            <TouchableOpacity 
              style={[styles.optionCard, withTransport && styles.optionCardActive]}
              onPress={() => setWithTransport(!withTransport)}
              activeOpacity={0.7}
            >
              <View style={styles.optionInfo}>
                <View style={[styles.optionIcon, withTransport && styles.optionIconActive]}>
                  <Ionicons name="car-outline" size={22} color={withTransport ? '#FFF' : '#3B82F6'} />
                </View>
                <View style={{ flex: 1 }}>
                  <Text style={[styles.optionTitle, withTransport && styles.optionTitleActive]}>Transportasi Flat</Text>
                  <Text style={styles.optionPrice}>Rp {formatIDR(service.transport_price_flat)} sekali jalan</Text>
                </View>
                <View style={[styles.checkbox, withTransport && styles.checkboxActive]}>
                  {withTransport && <Ionicons name="checkmark" size={16} color="#FFF" />}
                </View>
              </View>
            </TouchableOpacity>
          </View>

          {service.add_ons && service.add_ons.length > 0 && (
            <View style={styles.section}>
              <Text style={styles.sectionTitle}>Add-Ons Tersedia</Text>
              <View style={styles.addOnGrid}>
                {service.add_ons.map((addon, index) => {
                  const isSelected = selectedAddOns.some(a => a.name === addon.name);
                  return (
                    <TouchableOpacity 
                      key={index}
                      style={[styles.addOnChip, isSelected && styles.addOnChipActive]}
                      onPress={() => toggleAddOn(addon)}
                    >
                      <Text style={[styles.addOnText, isSelected && styles.addOnTextActive]}>{addon.name}</Text>
                      <Text style={[styles.addOnPrice, isSelected && styles.addOnPriceActive]}>+Rp {formatIDR(addon.price)}</Text>
                    </TouchableOpacity>
                  );
                })}
              </View>
            </View>
          )}

          {/* Pricing Summary Card */}
          <View style={styles.summaryCard}>
            <Text style={styles.summaryTitle}>Ringkasan Biaya</Text>
            <View style={styles.priceRow}>
              <Text style={styles.priceLabel}>Biaya Layanan ({days} Hari)</Text>
              <Text style={styles.priceValue}>Rp {formatIDR(parseFloat(service.daily_price) * days)}</Text>
            </View>
            {withSameHotel && (
              <View style={styles.priceRow}>
                <Text style={styles.priceLabel}>Biaya Hotel ({days} Hari)</Text>
                <Text style={styles.priceValue}>+ Rp {formatIDR(parseFloat(service.same_hotel_price_per_day) * days)}</Text>
              </View>
            )}
            {withTransport && (
              <View style={styles.priceRow}>
                <Text style={styles.priceLabel}>Transportasi Flat</Text>
                <Text style={styles.priceValue}>+ Rp {formatIDR(service.transport_price_flat)}</Text>
              </View>
            )}
            {selectedAddOns.map((a, i) => (
              <View key={i} style={styles.priceRow}>
                <Text style={styles.priceLabel}>Add-on: {a.name}</Text>
                <Text style={styles.priceValue}>+ Rp {formatIDR(a.price)}</Text>
              </View>
            ))}
            
            <View style={styles.divider} />
            <View style={styles.totalRow}>
              <Text style={styles.totalLabel}>Total Estimasi</Text>
              <Text style={styles.totalValue}>Rp {formatIDR(totalPrice)}</Text>
            </View>
            <View style={styles.infoBox}>
               <Ionicons name="information-circle" size={16} color="#64748B" />
               <Text style={styles.infoText}>Harga belum termasuk biaya admin pembayaran (DOKU).</Text>
            </View>
          </View>

        </ScrollView>

        <View style={styles.footer}>
          <TouchableOpacity 
            style={[styles.bookingBtn, loading && styles.bookingBtnDisabled]} 
            onPress={handleBooking}
            disabled={loading}
          >
            <LinearGradient colors={['#0F172A', '#1E3A8A']} style={styles.btnGradient}>
              {loading ? (
                <ActivityIndicator color="#FFF" />
              ) : (
                <>
                  <Text style={styles.bookingBtnText}>Buat Pesanan Sekarang</Text>
                  <Ionicons name="arrow-forward" size={20} color="#FFF" />
                </>
              )}
            </LinearGradient>
          </TouchableOpacity>
        </View>
      </View>
    </SwipeableScreen>
  );
}

const styles = StyleSheet.create({
  container: { flex: 1, backgroundColor: '#F8FAFC' },
  header: { 
    backgroundColor: '#FFFFFF',
    paddingBottom: 25, 
    borderBottomLeftRadius: 32, 
    borderBottomRightRadius: 32,
    shadowColor: '#000',
    shadowOffset: { width: 0, height: 4 },
    shadowOpacity: 0.03,
    shadowRadius: 10,
    elevation: 5
  },
  headerContent: { flexDirection: 'row', alignItems: 'center', justifyContent: 'space-between', paddingHorizontal: 20, paddingTop: 10 },
  backBtn: { width: 44, height: 44, borderRadius: 22, backgroundColor: '#F8FAFC', justifyContent: 'center', alignItems: 'center' },
  headerTitleBox: { alignItems: 'center' },
  headerTitle: { color: '#0F172A', fontSize: 18, fontWeight: '900' },
  headerSub: { color: '#94A3B8', fontSize: 10, fontWeight: '800', marginTop: 2, textTransform: 'uppercase', letterSpacing: 1 },
  
  scrollContent: { padding: 20, paddingBottom: 120 },
  
  serviceBanner: { marginBottom: 25 },
  bannerInner: { flexDirection: 'row', alignItems: 'center', padding: 20, borderRadius: 24, borderWidth: 1, borderColor: '#F1F5F9' },
  bannerIcon: { width: 48, height: 48, borderRadius: 16, backgroundColor: '#FFF', justifyContent: 'center', alignItems: 'center', marginRight: 16, shadowColor: '#3B82F6', shadowOffset: { width: 0, height: 4 }, shadowOpacity: 0.1, shadowRadius: 10, elevation: 2 },
  bannerLabel: { fontSize: 10, fontWeight: '800', color: '#94A3B8', letterSpacing: 1, marginBottom: 4 },
  bannerName: { fontSize: 16, fontWeight: '900', color: '#1E293B' },
  bannerDuration: { fontSize: 12, color: '#64748B', fontWeight: '600', marginTop: 4 },

  section: { marginBottom: 25 },
  sectionTitle: { fontSize: 16, fontWeight: '900', color: '#0F172A', marginBottom: 15, marginLeft: 5 },
  
  inputWrapper: { flexDirection: 'row', alignItems: 'center', backgroundColor: '#FFF', borderRadius: 18, borderWidth: 1, borderColor: '#F1F5F9', paddingHorizontal: 16 },
  inputIcon: { marginRight: 12 },
  input: { flex: 1, paddingVertical: 16, fontSize: 15, fontWeight: '700', color: '#1E293B' },

  optionCard: { backgroundColor: '#FFF', borderRadius: 20, padding: 18, marginBottom: 12, borderWidth: 1, borderColor: '#F1F5F9' },
  optionCardActive: { borderColor: '#3B82F6', backgroundColor: '#F0F9FF' },
  optionInfo: { flexDirection: 'row', alignItems: 'center' },
  optionIcon: { width: 44, height: 44, borderRadius: 14, backgroundColor: '#EFF6FF', justifyContent: 'center', alignItems: 'center', marginRight: 16 },
  optionIconActive: { backgroundColor: '#3B82F6' },
  optionTitle: { fontSize: 15, fontWeight: '800', color: '#1E293B' },
  optionTitleActive: { color: '#1E3A8A' },
  optionPrice: { fontSize: 12, color: '#64748B', fontWeight: '600', marginTop: 2 },
  checkbox: { width: 24, height: 24, borderRadius: 12, borderWidth: 2, borderColor: '#CBD5E1', justifyContent: 'center', alignItems: 'center' },
  checkboxActive: { backgroundColor: '#3B82F6', borderColor: '#3B82F6' },

  addOnGrid: { flexDirection: 'row', flexWrap: 'wrap', gap: 10 },
  addOnChip: { backgroundColor: '#FFF', borderRadius: 16, paddingHorizontal: 16, paddingVertical: 12, borderWidth: 1, borderColor: '#F1F5F9', minWidth: (width - 60) / 2 },
  addOnChipActive: { backgroundColor: '#3B82F6', borderColor: '#3B82F6' },
  addOnText: { fontSize: 14, fontWeight: '800', color: '#1E293B' },
  addOnTextActive: { color: '#FFF' },
  addOnPrice: { fontSize: 11, color: '#64748B', fontWeight: '700', marginTop: 4 },
  addOnPriceActive: { color: 'rgba(255, 255, 255, 0.8)' },

  summaryCard: { backgroundColor: '#0F172A', borderRadius: 28, padding: 24, shadowColor: '#000', shadowOffset: { width: 0, height: 10 }, shadowOpacity: 0.2, shadowRadius: 20, elevation: 10 },
  summaryTitle: { fontSize: 18, fontWeight: '900', color: '#FFF', marginBottom: 20 },
  priceRow: { flexDirection: 'row', justifyContent: 'space-between', marginBottom: 14, gap: 10 },
  priceLabel: { fontSize: 14, color: 'rgba(255, 255, 255, 0.6)', fontWeight: '600', flex: 1 },
  priceValue: { fontSize: 14, color: '#FFF', fontWeight: '800', textAlign: 'right' },
  divider: { height: 1, backgroundColor: 'rgba(255, 255, 255, 0.1)', marginVertical: 10 },
  totalRow: { flexDirection: 'row', justifyContent: 'space-between', alignItems: 'center', marginTop: 10, gap: 10 },
  totalLabel: { fontSize: 16, fontWeight: '900', color: '#FFF', flex: 1 },
  totalValue: { fontSize: 20, fontWeight: '900', color: '#3B82F6', textAlign: 'right' },
  infoBox: { flexDirection: 'row', alignItems: 'center', gap: 8, marginTop: 20, backgroundColor: 'rgba(255, 255, 255, 0.05)', padding: 12, borderRadius: 12 },
  infoText: { fontSize: 11, color: 'rgba(255, 255, 255, 0.5)', fontWeight: '600' },

  footer: { position: 'absolute', bottom: 0, left: 0, right: 0, padding: 24, backgroundColor: 'rgba(255, 255, 255, 0.9)', borderTopWidth: 1, borderTopColor: '#F1F5F9' },
  bookingBtn: { borderRadius: 20, overflow: 'hidden' },
  bookingBtnDisabled: { opacity: 0.7 },
  btnGradient: { flexDirection: 'row', alignItems: 'center', justifyContent: 'center', gap: 12, paddingVertical: 18 },
  bookingBtnText: { color: '#FFF', fontSize: 16, fontWeight: '900' }
});
