import React, { useState } from 'react';
import { 
  StyleSheet, 
  Text, 
  View, 
  TouchableOpacity, 
  TextInput, 
  ScrollView, 
  ActivityIndicator,
  Alert,
  KeyboardAvoidingView,
  Platform
} from 'react-native';
import { SafeAreaView } from 'react-native-safe-area-context';
import { StatusBar } from 'expo-status-bar';
import { apiClient } from '../api/client';

export default function EditServiceScreen({ route, user, navigation }) {
  const { service } = route.params;
  
  const [loading, setLoading] = useState(false);
  
  // States matching Web Form fields - 100% PARITY
  const [name, setName] = useState(service.name || '');
  const [dailyPrice, setDailyPrice] = useState(service.daily_price.toString());
  const [hotelPrice, setHotelPrice] = useState(service.same_hotel_price_per_day.toString());
  const [transportPrice, setTransportPrice] = useState(service.transport_price_flat.toString());
  const [minPilgrims, setMinPilgrims] = useState(service.min_pilgrims.toString());
  const [maxPilgrims, setMaxPilgrims] = useState(service.max_pilgrims.toString());
  const [description, setDescription] = useState(service.description || '');
  
  // Dynamic Add Ons for Private Service
  const [addOns, setAddOns] = useState(
    service.add_ons && service.add_ons.length > 0 
    ? service.add_ons.map(a => ({ name: a.name, price: a.price.toString() })) 
    : [{ name: '', price: '' }]
  );

  const addAddOnRow = () => {
    setAddOns([...addOns, { name: '', price: '' }]);
  };

  const removeAddOnRow = (index) => {
    const newAddOns = [...addOns];
    newAddOns.splice(index, 1);
    setAddOns(newAddOns.length > 0 ? newAddOns : [{ name: '', price: '' }]);
  };

  const updateAddOn = (index, field, value) => {
    const newAddOns = [...addOns];
    newAddOns[index][field] = value;
    setAddOns(newAddOns);
  };

  const handleUpdate = async () => {
    if (!name || !dailyPrice || !hotelPrice || !transportPrice || !minPilgrims || !maxPilgrims) {
      Alert.alert('Error', 'Nama, harga, dan kapasitas wajib diisi');
      return;
    }

    setLoading(true);
    try {
      await apiClient.updateMuthowifService(user.token, service.id, {
        name,
        daily_price: dailyPrice,
        same_hotel_price_per_day: hotelPrice,
        transport_price_flat: transportPrice,
        min_pilgrims: minPilgrims,
        max_pilgrims: maxPilgrims,
        description: description,
        add_ons: addOns.filter(a => a.name.trim() !== '') // Only send non-empty add-ons
      });
      
      Alert.alert('Sukses', 'Layanan berhasil diperbarui', [
        { text: 'OK', onPress: () => navigation.navigate('Services') }
      ]);
    } catch (error) {
      Alert.alert('Error', error.message || 'Gagal memperbarui layanan');
    } finally {
      setLoading(false);
    }
  };

  return (
    <SafeAreaView style={styles.container}>
      <StatusBar style="dark" />
      
      <View style={styles.header}>
        <TouchableOpacity onPress={() => navigation.navigate('Services')}>
          <Text style={styles.backBtn}>←</Text>
        </TouchableOpacity>
        <Text style={styles.headerTitle}>Edit Layanan</Text>
        <View style={{ width: 40 }} />
      </View>

      <KeyboardAvoidingView 
        behavior={Platform.OS === 'ios' ? 'padding' : 'height'}
        style={{ flex: 1 }}
      >
        <ScrollView contentContainerStyle={styles.scrollContent} showsVerticalScrollIndicator={false}>
          
          <View style={styles.banner}>
            <View style={[styles.typeBadge, { backgroundColor: service.type === 'private' ? '#F0FDF4' : '#E0F2FE' }]}>
              <Text style={[styles.typeText, { color: service.type === 'private' ? '#166534' : '#0369A1' }]}>
                {service.type === 'private' ? '👤 PRIVATE' : '👥 GROUP'}
              </Text>
            </View>
            <Text style={styles.serviceTitle}>Konfigurasi Layanan</Text>
          </View>

          {/* Form Fields - 100% Web Parity */}
          <View style={styles.form}>
            <View style={styles.inputGroup}>
              <Text style={styles.label}>NAMA LAYANAN</Text>
              <TextInput 
                style={styles.input}
                value={name}
                onChangeText={setName}
                placeholder="Contoh: Layanan Umrah Eksekutif 9 Hari"
              />
            </View>

            <View style={styles.inputGroup}>
              <Text style={styles.label}>HARGA HARIAN (RP)</Text>
              <TextInput 
                style={styles.input}
                value={dailyPrice}
                onChangeText={setDailyPrice}
                keyboardType="numeric"
                placeholder="0"
              />
            </View>

            <View style={styles.inputGroup}>
              <Text style={styles.label}>HARGA HOTEL YANG SAMA (RP)</Text>
              <TextInput 
                style={styles.input}
                value={hotelPrice}
                onChangeText={setHotelPrice}
                keyboardType="numeric"
                placeholder="0"
              />
            </View>

            <View style={styles.inputGroup}>
              <Text style={styles.label}>BIAYA TRANSPORTASI FLAT (RP)</Text>
              <TextInput 
                style={styles.input}
                value={transportPrice}
                onChangeText={setTransportPrice}
                keyboardType="numeric"
                placeholder="0"
              />
            </View>

            <View style={styles.row}>
              <View style={[styles.inputGroup, { flex: 1, marginRight: 10 }]}>
                <Text style={styles.label}>MIN JAMAAH</Text>
                <TextInput 
                  style={styles.input}
                  value={minPilgrims}
                  onChangeText={setMinPilgrims}
                  keyboardType="numeric"
                  placeholder="1"
                />
              </View>
              <View style={[styles.inputGroup, { flex: 1, marginLeft: 10 }]}>
                <Text style={styles.label}>MAX JAMAAH</Text>
                <TextInput 
                  style={styles.input}
                  value={maxPilgrims}
                  onChangeText={setMaxPilgrims}
                  keyboardType="numeric"
                  placeholder="5"
                />
              </View>
            </View>

            <View style={styles.inputGroup}>
              <Text style={styles.label}>DESKRIPSI LAYANAN</Text>
              <TextInput 
                style={[styles.input, styles.textArea]}
                value={description}
                onChangeText={setDescription}
                multiline
                numberOfLines={4}
                placeholder="Jelaskan layanan, fasilitas, pendampingan, dll."
              />
            </View>

            {/* DYNAMIC ADD ONS - EXCLUSIVE FOR PRIVATE */}
            {service.type === 'private' && (
              <View style={styles.addOnSection}>
                <View style={styles.addOnHeader}>
                  <Text style={styles.addOnTitle}>+ Add Ons</Text>
                  <TouchableOpacity onPress={addAddOnRow}>
                    <Text style={styles.addMoreBtn}>+ Baris</Text>
                  </TouchableOpacity>
                </View>
                <Text style={styles.addOnSub}>Tambahkan opsi add ons tambahan untuk layanan ini.</Text>
                
                {addOns.map((addon, index) => (
                  <View key={index} style={styles.addOnRow}>
                    <View style={{ flex: 2, marginRight: 10 }}>
                      <Text style={styles.miniLabel}>Nama Add On</Text>
                      <TextInput 
                        style={styles.miniInput}
                        value={addon.name}
                        onChangeText={(val) => updateAddOn(index, 'name', val)}
                        placeholder="Contoh: City Tour"
                      />
                    </View>
                    <View style={{ flex: 1.5, marginRight: 10 }}>
                      <Text style={styles.miniLabel}>Harga (Rp)</Text>
                      <TextInput 
                        style={styles.miniInput}
                        value={addon.price}
                        onChangeText={(val) => updateAddOn(index, 'price', val)}
                        keyboardType="numeric"
                        placeholder="0"
                      />
                    </View>
                    <TouchableOpacity 
                      style={styles.removeBtn} 
                      onPress={() => removeAddOnRow(index)}
                    >
                      <Text style={styles.removeText}>✕</Text>
                    </TouchableOpacity>
                  </View>
                ))}
              </View>
            )}

            <TouchableOpacity 
              style={[styles.saveBtn, loading && styles.disabledBtn]} 
              onPress={handleUpdate}
              disabled={loading}
            >
              {loading ? (
                <ActivityIndicator color="#fff" />
              ) : (
                <Text style={styles.saveBtnText}>
                  Simpan Layanan {service.type === 'private' ? 'Private' : 'Group'}
                </Text>
              )}
            </TouchableOpacity>
          </View>

        </ScrollView>
      </KeyboardAvoidingView>
    </SafeAreaView>
  );
}

const styles = StyleSheet.create({
  container: { flex: 1, backgroundColor: '#FFFFFF' },
  header: { 
    flexDirection: 'row', 
    justifyContent: 'space-between', 
    alignItems: 'center', 
    paddingHorizontal: 20, 
    paddingVertical: 15,
    borderBottomWidth: 1,
    borderBottomColor: '#F1F5F9'
  },
  backBtn: { fontSize: 24, fontWeight: 'bold', color: '#1E293B', width: 40 },
  headerTitle: { fontSize: 18, fontWeight: '800', color: '#1E293B' },
  
  scrollContent: { padding: 25, paddingBottom: 100 },
  
  banner: { marginBottom: 30 },
  typeBadge: { paddingHorizontal: 10, paddingVertical: 4, borderRadius: 8, alignSelf: 'flex-start', marginBottom: 10 },
  typeText: { fontSize: 10, fontWeight: '800' },
  serviceTitle: { fontSize: 24, fontWeight: '800', color: '#1E293B' },
  
  form: { marginTop: 10 },
  inputGroup: { marginBottom: 20 },
  label: { fontSize: 11, fontWeight: '800', color: '#94A3B8', marginBottom: 10, letterSpacing: 0.5 },
  input: {
    backgroundColor: '#F8FAFC',
    borderWidth: 1,
    borderColor: '#E2E8F0',
    borderRadius: 15,
    padding: 15,
    fontSize: 15,
    color: '#1E293B',
    fontWeight: '600'
  },
  textArea: { height: 100, textAlignVertical: 'top' },
  row: { flexDirection: 'row' },

  addOnSection: {
    backgroundColor: '#F8FAFC',
    borderRadius: 20,
    padding: 20,
    marginTop: 10,
    marginBottom: 25,
    borderWidth: 1,
    borderColor: '#E2E8F0',
    borderStyle: 'dashed'
  },
  addOnHeader: { flexDirection: 'row', justifyContent: 'space-between', alignItems: 'center', marginBottom: 5 },
  addOnTitle: { fontSize: 14, fontWeight: '800', color: '#1E293B' },
  addMoreBtn: { fontSize: 12, fontWeight: '800', color: '#0984e3' },
  addOnSub: { fontSize: 11, color: '#64748B', marginBottom: 20 },
  addOnRow: { flexDirection: 'row', alignItems: 'flex-end', marginBottom: 15, paddingBottom: 15, borderBottomWidth: 1, borderBottomColor: '#E2E8F0' },
  miniLabel: { fontSize: 10, fontWeight: '700', color: '#94A3B8', marginBottom: 5 },
  miniInput: { backgroundColor: '#fff', borderWidth: 1, borderColor: '#E2E8F0', borderRadius: 10, padding: 10, fontSize: 13, fontWeight: '600', color: '#1E293B' },
  removeBtn: { width: 30, height: 30, justifyContent: 'center', alignItems: 'center' },
  removeText: { color: '#EF4444', fontWeight: 'bold' },
  
  saveBtn: {
    backgroundColor: '#0984e3',
    paddingVertical: 18,
    borderRadius: 18,
    alignItems: 'center',
    marginTop: 10,
    shadowColor: '#0984e3',
    shadowOffset: { width: 0, height: 4 },
    shadowOpacity: 0.2,
    shadowRadius: 10,
    elevation: 5
  },
  disabledBtn: { opacity: 0.7 },
  saveBtnText: { color: '#fff', fontSize: 16, fontWeight: '800' }
});
