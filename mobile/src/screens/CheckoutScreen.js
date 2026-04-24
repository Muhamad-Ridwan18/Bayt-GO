import React, { useState, useEffect, useMemo } from 'react';
import { 
  StyleSheet, 
  Text, 
  View, 
  TouchableOpacity, 
  ScrollView, 
  Image,
  Dimensions,
  Alert,
  ActivityIndicator,
  TextInput
} from 'react-native';
import { SafeAreaView, useSafeAreaInsets } from 'react-native-safe-area-context';
import { Ionicons } from '@expo/vector-icons';
import * as DocumentPicker from 'expo-document-picker';
import { apiClient } from '../api/client';
import SwipeableScreen from '../components/SwipeableScreen';
import { LinearGradient } from 'expo-linear-gradient';

const { width } = Dimensions.get('window');

export default function CheckoutScreen({ route, navigation, user }) {
  const { id, start_date, end_date, profile, services } = route.params || {};
  const insets = useSafeAreaInsets();

  const formatDateFriendly = (dateStr) => {
    if (!dateStr) return null;
    try {
      const months = ['Jan', 'Feb', 'Mar', 'Apr', 'Mei', 'Juni', 'Juli', 'Agust', 'Sept', 'Okt', 'Nov', 'Des'];
      const d = new Date(dateStr);
      return `${d.getDate()} ${months[d.getMonth()]}`;
    } catch (e) {
      return dateStr;
    }
  };

  const [loading, setLoading] = useState(false);
  const [serviceType, setServiceType] = useState('private'); // 'group' or 'private'
  const [pilgrimCount, setPilgrimCount] = useState('1');
  const [selectedAddOns, setSelectedAddOns] = useState([]);
  const [withHotel, setWithHotel] = useState(false);
  const [withTransport, setWithTransport] = useState(false);
  
  // Documents
  const [ticketOutbound, setTicketOutbound] = useState(null);
  const [ticketReturn, setTicketReturn] = useState(null);
  const [passport, setPassport] = useState(null);
  const [itinerary, setItinerary] = useState(null);
  const [visa, setVisa] = useState(null);

  const selectedService = useMemo(() => {
    return services.find(s => s.type === serviceType);
  }, [services, serviceType]);

  const nights = useMemo(() => {
    if (!start_date || !end_date) return 1;
    const start = new Date(start_date);
    const end = new Date(end_date);
    const diffTime = Math.abs(end - start);
    const diffDays = Math.ceil(diffTime / (1000 * 60 * 60 * 24)) + 1;
    return diffDays;
  }, [start_date, end_date]);

  const totalPrice = useMemo(() => {
    if (!selectedService) return 0;
    
    let total = Number(selectedService.price);
    
    // Add-ons
    selectedAddOns.forEach(addonId => {
      const addon = selectedService.add_ons.find(a => a.id === addonId);
      if (addon) total += Number(addon.price);
    });

    // Hotel
    if (withHotel && selectedService.same_hotel_price_per_day) {
      total += (Number(selectedService.same_hotel_price_per_day) * nights);
    }

    // Transport
    if (withTransport && selectedService.transport_price_flat) {
      total += Number(selectedService.transport_price_flat);
    }

    return total;
  }, [selectedService, selectedAddOns, withHotel, withTransport, nights]);

  const pickDocument = async (type) => {
    try {
      const result = await DocumentPicker.getDocumentAsync({
        type: ['application/pdf', 'image/*'],
      });

      if (!result.canceled) {
        const file = result.assets[0];
        switch (type) {
          case 'outbound': setTicketOutbound(file); break;
          case 'return': setTicketReturn(file); break;
          case 'passport': setPassport(file); break;
          case 'itinerary': setItinerary(file); break;
          case 'visa': setVisa(file); break;
        }
      }
    } catch (err) {
      Alert.alert('Error', 'Gagal memilih dokumen');
    }
  };

  const handleSubmit = async () => {
    if (!ticketOutbound || !ticketReturn || !passport) {
      Alert.alert('Dokumen Kurang', 'Harap lengkapi dokumen Tiket dan Paspor.');
      return;
    }

    if (serviceType === 'group' && !itinerary) {
      Alert.alert('Dokumen Kurang', 'Paket Group wajib menyertakan Itinerary.');
      return;
    }

    setLoading(true);
    try {
      const formData = new FormData();
      formData.append('muthowif_profile_id', id);
      formData.append('start_date', start_date);
      formData.append('end_date', end_date);
      formData.append('service_type', serviceType);
      formData.append('pilgrim_count', pilgrimCount);
      formData.append('with_same_hotel', withHotel ? '1' : '0');
      formData.append('with_transport', withTransport ? '1' : '0');

      selectedAddOns.forEach((id, index) => {
        formData.append(`add_on_ids[${index}]`, id);
      });

      // Append files
      const appendFile = (name, file) => {
        if (file) {
          formData.append(name, {
            uri: file.uri,
            name: file.name,
            type: file.mimeType || 'application/octet-stream',
          });
        }
      };

      appendFile('ticket_outbound', ticketOutbound);
      appendFile('ticket_return', ticketReturn);
      appendFile('passport', passport);
      appendFile('itinerary', itinerary);
      appendFile('visa', visa);

      const bookingRes = await apiClient.createBooking(user.token, formData);
      const paymentRes = await apiClient.requestPayment(user.token, bookingRes.booking_id);

      navigation.navigate('PaymentWeb', { 
        url: paymentRes.redirect_url,
        booking_id: bookingRes.booking_id 
      });

    } catch (error) {
      Alert.alert('Gagal', error.message || 'Terjadi kesalahan saat memproses pesanan');
    } finally {
      setLoading(false);
    }
  };

  const toggleAddOn = (id) => {
    if (selectedAddOns.includes(id)) {
      setSelectedAddOns(selectedAddOns.filter(item => item !== id));
    } else {
      setSelectedAddOns([...selectedAddOns, id]);
    }
  };

  return (
    <SwipeableScreen onSwipeBack={() => navigation.goBack()}>
      <View style={styles.container}>
        <ScrollView contentContainerStyle={{ paddingBottom: 120 }}>
          <LinearGradient 
            colors={['#0F172A', '#1E3A8A']} 
            style={styles.headerGradient}
          >
            <SafeAreaView edges={['top']}>
              <View style={styles.headerRow}>
                <TouchableOpacity onPress={() => navigation.goBack()} style={styles.backBtnWrapper}>
                  <Ionicons name="arrow-back" size={24} color="#FFF" />
                </TouchableOpacity>
                <Text style={styles.headerTitle}>Konfirmasi Pemesanan</Text>
              </View>
            </SafeAreaView>
          </LinearGradient>

          <View style={styles.content}>
            {/* Service Summary Card */}
            <View style={styles.summaryCardWrapper}>
              <LinearGradient 
                colors={['#FFFFFF', '#F8FAFC']} 
                style={styles.summaryCard}
              >
                <View style={styles.profileInfo}>
                  <Image source={{ uri: profile.avatar }} style={styles.miniAvatar} />
                  <View>
                    <Text style={styles.muthowifName}>{profile.name}</Text>
                    <Text style={styles.muthowifStatus}>Verified Muthowif</Text>
                  </View>
                </View>
                
                <View style={styles.divider} />
                
                <View style={styles.scheduleGrid}>
                  <View style={styles.scheduleItem}>
                    <Text style={styles.scheduleLabel}>MULAI</Text>
                    <Text style={styles.scheduleValue}>
                      {formatDateFriendly(start_date)}
                    </Text>
                  </View>
                  <Ionicons name="arrow-forward" size={16} color="#CBD5E1" />
                  <View style={styles.scheduleItem}>
                    <Text style={styles.scheduleLabel}>SELESAI</Text>
                    <Text style={styles.scheduleValue}>
                      {formatDateFriendly(end_date)}
                    </Text>
                  </View>
                  <View style={styles.durationBadge}>
                    <Text style={styles.durationText}>{nights} Hari</Text>
                  </View>
                </View>
              </LinearGradient>
            </View>

            {/* Service Type */}
            <View style={styles.section}>
              <Text style={styles.sectionTitle}>Tipe Layanan</Text>
              <View style={styles.typeSelector}>
                {services.map(s => (
                  <TouchableOpacity 
                    key={s.id}
                    style={[styles.typeBtn, serviceType === s.type && styles.typeBtnActive]}
                    onPress={() => setServiceType(s.type)}
                  >
                    <Text style={[styles.typeBtnText, serviceType === s.type && styles.typeBtnTextActive]}>
                      {s.type === 'private' ? 'Private' : 'Group'}
                    </Text>
                  </TouchableOpacity>
                ))}
              </View>
            </View>

            {/* Pilgrim Count */}
            <View style={styles.section}>
              <Text style={styles.sectionTitle}>Jumlah Jamaah</Text>
              <View style={styles.inputContainer}>
                <Ionicons name="people" size={20} color="#64748B" style={styles.inputIcon} />
                <TextInput 
                  style={styles.input}
                  keyboardType="numeric"
                  value={pilgrimCount}
                  onChangeText={setPilgrimCount}
                  placeholder="Contoh: 2"
                />
              </View>
              {selectedService && (
                <Text style={styles.inputHelper}>Min: {selectedService.min_pilgrims || 1} - Max: {selectedService.max_pilgrims || 50}</Text>
              )}
            </View>

            {/* Add-ons & Acommodation */}
            <View style={styles.section}>
              <Text style={styles.sectionTitle}>Layanan Tambahan</Text>
              
              {selectedService?.same_hotel_price_per_day > 0 && (
                <TouchableOpacity style={styles.optionItem} onPress={() => setWithHotel(!withHotel)}>
                  <Ionicons name={withHotel ? "checkbox" : "square-outline"} size={24} color={withHotel ? "#0984e3" : "#94A3B8"} />
                  <View style={styles.optionTextContainer}>
                    <Text style={styles.optionLabel}>Satu Hotel dengan Jamaah</Text>
                    <Text style={styles.optionPrice}>+ Rp {Number(selectedService.same_hotel_price_per_day).toLocaleString('id-ID')}/malam</Text>
                  </View>
                </TouchableOpacity>
              )}

              {selectedService?.transport_price_flat > 0 && (
                <TouchableOpacity style={styles.optionItem} onPress={() => setWithTransport(!withTransport)}>
                  <Ionicons name={withTransport ? "checkbox" : "square-outline"} size={24} color={withTransport ? "#0984e3" : "#94A3B8"} />
                  <View style={styles.optionTextContainer}>
                    <Text style={styles.optionLabel}>Transportasi Antar Jemput</Text>
                    <Text style={styles.optionPrice}>+ Rp {Number(selectedService.transport_price_flat).toLocaleString('id-ID')}</Text>
                  </View>
                </TouchableOpacity>
              )}

              {selectedService?.add_ons && selectedService.add_ons.map(addon => (
                <TouchableOpacity key={addon.id} style={styles.optionItem} onPress={() => toggleAddOn(addon.id)}>
                  <Ionicons name={selectedAddOns.includes(addon.id) ? "checkbox" : "square-outline"} size={24} color={selectedAddOns.includes(addon.id) ? "#0984e3" : "#94A3B8"} />
                  <View style={styles.optionTextContainer}>
                    <Text style={styles.optionLabel}>{addon.name}</Text>
                    <Text style={styles.optionPrice}>+ Rp {Number(addon.price).toLocaleString('id-ID')}</Text>
                  </View>
                </TouchableOpacity>
              ))}
            </View>

            {/* Document Upload */}
            <View style={styles.section}>
              <Text style={styles.sectionTitle}>Unggah Dokumen</Text>
              <Text style={styles.sectionHelper}>Wajib mengunggah Tiket dan Paspor.</Text>
              
              <View style={styles.docGrid}>
                <DocBtn title="Tiket Berangkat" file={ticketOutbound} onPress={() => pickDocument('outbound')} required />
                <DocBtn title="Tiket Pulang" file={ticketReturn} onPress={() => pickDocument('return')} required />
                <DocBtn title="Paspor" file={passport} onPress={() => pickDocument('passport')} required />
                <DocBtn title="Visa (Opsional)" file={visa} onPress={() => pickDocument('visa')} />
                {serviceType === 'group' && (
                  <DocBtn title="Itinerary" file={itinerary} onPress={() => pickDocument('itinerary')} required />
                )}
              </View>
            </View>
          </View>
        </ScrollView>

        {/* Bottom Bar */}
        <View style={[styles.bottomBar, { paddingBottom: Math.max(insets.bottom, 20) }]}>
          <View style={styles.bottomBarLeft}>
            <Text style={styles.totalLabel}>Total Pembayaran</Text>
            <Text style={styles.totalPrice}>Rp {totalPrice.toLocaleString('id-ID')}</Text>
          </View>
          <TouchableOpacity 
            style={[styles.payBtn, loading && styles.payBtnDisabled]} 
            onPress={handleSubmit}
            disabled={loading}
          >
            {loading ? (
              <ActivityIndicator color="#FFF" />
            ) : (
              <>
                <Text style={styles.payBtnText}>Bayar Sekarang</Text>
                <Ionicons name="card" size={18} color="#FFF" />
              </>
            )}
          </TouchableOpacity>
        </View>
      </View>
    </SwipeableScreen>
  );
}

const DocBtn = ({ title, file, onPress, required }) => (
  <TouchableOpacity style={[styles.docBtn, file && styles.docBtnSuccess]} onPress={onPress}>
    <Ionicons name={file ? "checkmark-circle" : "cloud-upload"} size={24} color={file ? "#10B981" : "#0984e3"} />
    <Text style={[styles.docBtnTitle, file && styles.docBtnTitleSuccess]} numberOfLines={1}>
      {file ? file.name : title}
    </Text>
    {required && !file && <View style={styles.requiredDot} />}
  </TouchableOpacity>
);

const styles = StyleSheet.create({
  container: { flex: 1, backgroundColor: '#F8FAFC' },
  headerGradient: {
    paddingBottom: 60,
    borderBottomLeftRadius: 32,
    borderBottomRightRadius: 32,
  },
  headerRow: { 
    flexDirection: 'row', 
    alignItems: 'center', 
    paddingHorizontal: 20, 
    paddingTop: 10,
    gap: 16
  },
  backBtnWrapper: {
    width: 40,
    height: 40,
    borderRadius: 12,
    backgroundColor: 'rgba(255, 255, 255, 0.15)',
    justifyContent: 'center',
    alignItems: 'center',
  },
  headerTitle: { fontSize: 20, fontWeight: '800', color: '#FFF', letterSpacing: -0.5 },
  
  content: { padding: 20, marginTop: -50 },
  
  summaryCardWrapper: {
    shadowColor: '#0F172A',
    shadowOffset: { width: 0, height: 12 },
    shadowOpacity: 0.1,
    shadowRadius: 24,
    elevation: 8,
    marginBottom: 24,
  },
  summaryCard: { 
    borderRadius: 24, 
    padding: 20, 
    borderWidth: 1, 
    borderColor: '#FFF',
  },
  profileInfo: { flexDirection: 'row', alignItems: 'center', gap: 14, marginBottom: 16 },
  miniAvatar: { width: 44, height: 44, borderRadius: 14, backgroundColor: '#F1F5F9' },
  muthowifName: { fontSize: 16, fontWeight: '800', color: '#0F172A' },
  muthowifStatus: { fontSize: 11, fontWeight: '700', color: '#10B981', marginTop: 2 },
  
  divider: { height: 1, backgroundColor: '#F1F5F9', marginBottom: 16 },
  
  scheduleGrid: { flexDirection: 'row', alignItems: 'center', justifyContent: 'space-between' },
  scheduleItem: { gap: 4 },
  scheduleLabel: { fontSize: 10, fontWeight: '800', color: '#94A3B8', letterSpacing: 1 },
  scheduleValue: { fontSize: 15, fontWeight: '800', color: '#1E293B' },
  durationBadge: { 
    backgroundColor: '#F0F9FF', 
    paddingHorizontal: 12, 
    paddingVertical: 6, 
    borderRadius: 10,
    borderWidth: 1,
    borderColor: '#E0F2FE'
  },
  durationText: { fontSize: 12, fontWeight: '800', color: '#0984e3' },

  section: { marginBottom: 28 },
  sectionTitle: { fontSize: 17, fontWeight: '800', color: '#0F172A', marginBottom: 16 },
  sectionHelper: { fontSize: 13, color: '#64748B', marginBottom: 16, marginTop: -8 },
  
  typeSelector: { flexDirection: 'row', gap: 12 },
  typeBtn: { 
    flex: 1, 
    paddingVertical: 16, 
    alignItems: 'center', 
    borderRadius: 18, 
    backgroundColor: '#FFF',
    borderWidth: 1,
    borderColor: '#E2E8F0',
    shadowColor: '#000',
    shadowOffset: { width: 0, height: 2 },
    shadowOpacity: 0.03,
    shadowRadius: 4,
    elevation: 2
  },
  typeBtnActive: { 
    backgroundColor: '#0984e3', 
    borderColor: '#0984e3',
    shadowColor: '#0984e3',
    shadowOpacity: 0.3,
    shadowRadius: 8,
  },
  typeBtnText: { fontSize: 14, fontWeight: '700', color: '#64748B' },
  typeBtnTextActive: { color: '#FFF' },

  inputContainer: { 
    flexDirection: 'row', 
    alignItems: 'center', 
    backgroundColor: '#FFF', 
    borderRadius: 18, 
    borderWidth: 1, 
    borderColor: '#E2E8F0',
    paddingHorizontal: 18,
    height: 58,
    shadowColor: '#000',
    shadowOpacity: 0.02,
    elevation: 1
  },
  inputIcon: { marginRight: 14 },
  input: { flex: 1, fontSize: 16, fontWeight: '700', color: '#0F172A' },
  inputHelper: { fontSize: 12, color: '#94A3B8', marginTop: 10, fontWeight: '600', paddingLeft: 4 },

  optionItem: { 
    flexDirection: 'row', 
    alignItems: 'center', 
    backgroundColor: '#FFF', 
    padding: 16, 
    borderRadius: 20, 
    marginBottom: 14,
    borderWidth: 1,
    borderColor: '#F1F5F9',
    shadowColor: '#000',
    shadowOpacity: 0.03,
    elevation: 2
  },
  optionTextContainer: { flex: 1, marginLeft: 14 },
  optionLabel: { fontSize: 14, fontWeight: '800', color: '#1E293B' },
  optionPrice: { fontSize: 12, fontWeight: '700', color: '#10B981', marginTop: 3 },

  docGrid: { flexDirection: 'row', flexWrap: 'wrap', gap: 14 },
  docBtn: { 
    width: (width - 54) / 2, 
    backgroundColor: '#FFF', 
    borderRadius: 20, 
    padding: 20, 
    alignItems: 'center',
    borderWidth: 1.5,
    borderColor: '#E2E8F0',
    borderStyle: 'dashed'
  },
  docBtnSuccess: { backgroundColor: '#F0FDF4', borderColor: '#10B981', borderStyle: 'solid' },
  docBtnTitle: { fontSize: 12, fontWeight: '800', color: '#64748B', marginTop: 10, textAlign: 'center' },
  docBtnTitleSuccess: { color: '#166534' },
  requiredDot: { 
    position: 'absolute', 
    top: 10, 
    right: 10, 
    width: 7, 
    height: 7, 
    borderRadius: 4, 
    backgroundColor: '#EF4444' 
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
  bottomBarLeft: { flex: 1 },
  totalLabel: { fontSize: 12, color: '#64748B', fontWeight: '700', marginBottom: 4 },
  totalPrice: { fontSize: 22, fontWeight: '900', color: '#0F172A' },
  payBtn: { 
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
  payBtnDisabled: { opacity: 0.7 },
  payBtnText: { color: '#FFF', fontSize: 16, fontWeight: '800' }
});
