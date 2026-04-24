import React, { useState, useEffect } from 'react';
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
  RefreshControl
} from 'react-native';
import { SafeAreaView } from 'react-native-safe-area-context';
import { StatusBar } from 'expo-status-bar';
import { Calendar } from 'react-native-calendars';
import { apiClient } from '../api/client';

const { width } = Dimensions.get('window');

export default function TimeOffScreen({ user, navigation }) {
  const [loading, setLoading] = useState(true);
  const [submitting, setSubmitting] = useState(false);
  const [refreshing, setRefreshing] = useState(false);
  const [blockedDates, setBlockedDates] = useState([]);
  const [selectedDate, setSelectedDate] = useState('');
  const [note, setNote] = useState('');

  const fetchBlockedDates = async () => {
    try {
      const data = await apiClient.getBlockedDates(user.token);
      setBlockedDates(data.blocked_dates || []);
    } catch (error) {
      console.error('Fetch Blocked Dates Error:', error);
    } finally {
      setLoading(false);
      setRefreshing(false);
    }
  };

  useEffect(() => {
    fetchBlockedDates();
  }, []);

  const handleAddBlockedDate = async () => {
    if (!selectedDate) {
      Alert.alert('Info', 'Silakan pilih tanggal di kalender terlebih dahulu');
      return;
    }

    setSubmitting(true);
    try {
      await apiClient.addBlockedDate(user.token, {
        blocked_on: selectedDate,
        note: note
      });
      setSelectedDate('');
      setNote('');
      fetchBlockedDates();
      Alert.alert('Sukses', 'Jadwal libur berhasil disimpan');
    } catch (error) {
      Alert.alert('Error', error.message || 'Gagal menyimpan jadwal libur');
    } finally {
      setSubmitting(false);
    }
  };

  const handleDelete = (id) => {
    Alert.alert(
      'Konfirmasi',
      'Apakah Anda yakin ingin membatalkan jadwal libur ini?',
      [
        { text: 'Batal', style: 'cancel' },
        { 
          text: 'Ya, Hapus', 
          style: 'destructive',
          onPress: async () => {
            try {
              await apiClient.deleteBlockedDate(user.token, id);
              fetchBlockedDates();
            } catch (error) {
              Alert.alert('Error', 'Gagal menghapus data');
            }
          }
        }
      ]
    );
  };

  // Format marked dates for calendar
  const getMarkedDates = () => {
    const marked = {};
    
    // Existing blocked dates
    blockedDates.forEach(item => {
      marked[item.raw_date] = { 
        selected: true, 
        selectedColor: '#FEE2E2', 
        selectedTextColor: '#991B1B'
      };
    });

    // Currently selected date for adding
    if (selectedDate) {
      marked[selectedDate] = { 
        selected: true, 
        selectedColor: '#0984e3', 
        selectedTextColor: '#ffffff'
      };
    }

    return marked;
  };

  return (
    <SafeAreaView style={styles.container}>
      <StatusBar style="dark" />
      
      {/* Header */}
      <View style={styles.header}>
        <TouchableOpacity onPress={() => navigation.navigate('Dashboard')}>
          <Text style={styles.backBtn}>←</Text>
        </TouchableOpacity>
        <Text style={styles.headerTitle}>Atur Jadwal Libur</Text>
        <View style={{ width: 40 }} />
      </View>

      <ScrollView 
        showsVerticalScrollIndicator={false}
        contentContainerStyle={styles.scrollContent}
        refreshControl={<RefreshControl refreshing={refreshing} onRefresh={fetchBlockedDates} color="#0984e3" />}
      >
        <Text style={styles.sectionTitle}>Pilih Tanggal Libur</Text>
        <View style={styles.calendarCard}>
          <Calendar
            minDate={new Date().toISOString().split('T')[0]}
            onDayPress={day => setSelectedDate(day.dateString)}
            markedDates={getMarkedDates()}
            monthFormat={'MMMM yyyy'}
            theme={{
              todayTextColor: '#0984e3',
              arrowColor: '#0984e3',
              textMonthFontWeight: '800',
              textDayHeaderFontWeight: '800',
            }}
          />
        </View>

        <View style={styles.formCard}>
          <Text style={styles.label}>CATATAN (OPSIONAL)</Text>
          <TextInput 
            style={styles.input}
            value={note}
            onChangeText={setNote}
            placeholder="Misal: Acara Keluarga / Umroh Pribadi"
            placeholderTextColor="#94A3B8"
          />
          
          <TouchableOpacity 
            style={[styles.submitBtn, submitting && styles.disabledBtn]} 
            onPress={handleAddBlockedDate}
            disabled={submitting}
          >
            {submitting ? (
              <ActivityIndicator color="#fff" />
            ) : (
              <Text style={styles.submitBtnText}>Simpan Jadwal Libur</Text>
            )}
          </TouchableOpacity>
        </View>

        <View style={styles.listHeader}>
          <Text style={styles.sectionTitle}>Daftar Libur Mendatang</Text>
          <Text style={styles.countBadge}>{blockedDates.length}</Text>
        </View>

        {loading ? (
          <ActivityIndicator size="small" color="#0984e3" style={{marginTop: 20}} />
        ) : blockedDates.length > 0 ? (
          blockedDates.map((item) => (
            <View key={item.id} style={styles.blockedItem}>
              <View style={styles.itemLeft}>
                <View style={styles.dateBadge}>
                  <Text style={styles.dateText}>{item.date}</Text>
                </View>
                <Text style={styles.itemNote} numberOfLines={1}>{item.note}</Text>
              </View>
              <TouchableOpacity onPress={() => handleDelete(item.id)} style={styles.deleteBtn}>
                <Text style={styles.deleteIcon}>✕</Text>
              </TouchableOpacity>
            </View>
          ))
        ) : (
          <View style={styles.emptyBox}>
            <Text style={styles.emptyText}>Belum ada jadwal libur yang diatur.</Text>
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
  
  scrollContent: { padding: 20, paddingBottom: 50 },
  sectionTitle: { fontSize: 16, fontWeight: '800', color: '#1E293B', marginBottom: 15 },
  
  calendarCard: {
    backgroundColor: '#fff',
    borderRadius: 24,
    padding: 10,
    marginBottom: 20,
    borderWidth: 1,
    borderColor: '#F1F5F9',
    shadowColor: '#000', shadowOffset: { width: 0, height: 4 }, shadowOpacity: 0.05, shadowRadius: 10, elevation: 2
  },

  formCard: {
    backgroundColor: '#fff',
    borderRadius: 24,
    padding: 20,
    marginBottom: 30,
    borderWidth: 1,
    borderColor: '#F1F5F9'
  },
  label: { fontSize: 11, fontWeight: '800', color: '#94A3B8', marginBottom: 10, letterSpacing: 0.5 },
  input: {
    backgroundColor: '#F8FAFC',
    borderWidth: 1,
    borderColor: '#E2E8F0',
    borderRadius: 15,
    padding: 15,
    fontSize: 14,
    color: '#1E293B',
    fontWeight: '600',
    marginBottom: 15
  },
  submitBtn: {
    backgroundColor: '#0984e3',
    paddingVertical: 15,
    borderRadius: 15,
    alignItems: 'center'
  },
  submitBtnText: { color: '#fff', fontWeight: '800', fontSize: 14 },
  disabledBtn: { opacity: 0.7 },

  listHeader: { flexDirection: 'row', alignItems: 'center', marginBottom: 15 },
  countBadge: { backgroundColor: '#F1F5F9', color: '#64748B', fontSize: 12, fontWeight: '800', paddingHorizontal: 10, paddingVertical: 2, borderRadius: 10, marginLeft: 10 },
  
  blockedItem: {
    backgroundColor: '#fff',
    borderRadius: 18,
    padding: 15,
    flexDirection: 'row',
    justifyContent: 'space-between',
    alignItems: 'center',
    marginBottom: 12,
    borderWidth: 1,
    borderColor: '#F1F5F9'
  },
  itemLeft: { flex: 1, flexDirection: 'row', alignItems: 'center' },
  dateBadge: { backgroundColor: '#FEF2F2', paddingHorizontal: 10, paddingVertical: 5, borderRadius: 8, marginRight: 12 },
  dateText: { color: '#B91C1C', fontSize: 12, fontWeight: '800' },
  itemNote: { color: '#64748B', fontSize: 13, fontWeight: '600', flex: 1 },
  deleteBtn: { padding: 5 },
  deleteIcon: { color: '#94A3B8', fontSize: 16, fontWeight: 'bold' },

  emptyBox: { alignItems: 'center', marginTop: 10 },
  emptyText: { color: '#94A3B8', fontWeight: '600', fontSize: 14 }
});
