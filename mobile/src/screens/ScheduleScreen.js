import React, { useState, useEffect, useCallback } from 'react';
import {
  StyleSheet, Text, View, TouchableOpacity, ScrollView,
  ActivityIndicator, Alert, StatusBar, FlatList, Modal, TextInput
} from 'react-native';
import { SafeAreaView } from 'react-native-safe-area-context';
import { Ionicons } from '@expo/vector-icons';
import { LinearGradient } from 'expo-linear-gradient';
import { apiClient } from '../api/client';
import SwipeableScreen from '../components/SwipeableScreen';

export default function ScheduleScreen({ user, navigation }) {
  const [blockedDates, setBlockedDates] = useState([]);
  const [loading, setLoading] = useState(true);
  const [modalVisible, setModalVisible] = useState(false);
  const [note, setNote] = useState('');
  const [selectedDate, setSelectedDate] = useState(new Date());

  const loadSchedule = useCallback(async () => {
    try {
      const data = await apiClient.getMuthowifSchedule(user.token);
      setBlockedDates(data.blocked_dates || []);
    } catch (err) {
      Alert.alert('Error', err.message);
    } finally {
      setLoading(false);
    }
  }, [user.token]);

  useEffect(() => {
    loadSchedule();
  }, [loadSchedule]);

  const handleBlockDate = async () => {
    if (!selectedDate) return;
    const dateStr = selectedDate.toISOString().split('T')[0];
    
    setLoading(true);
    try {
      await apiClient.blockDate(user.token, dateStr, note);
      setNote('');
      setModalVisible(false);
      loadSchedule();
      Alert.alert('Berhasil', 'Tanggal telah diblokir.');
    } catch (err) {
      Alert.alert('Gagal', err.message);
      setLoading(false);
    }
  };

  const handleUnblock = (id) => {
    Alert.alert(
      'Hapus Blokir',
      'Apakah Anda yakin ingin membuka kembali tanggal ini?',
      [
        { text: 'Batal', style: 'cancel' },
        { 
          text: 'Ya, Hapus', 
          style: 'destructive',
          onPress: async () => {
            setLoading(true);
            try {
              await apiClient.unblockDate(user.token, id);
              loadSchedule();
            } catch (err) {
              Alert.alert('Error', err.message);
              setLoading(false);
            }
          }
        }
      ]
    );
  };

  const renderItem = ({ item }) => (
    <View style={styles.dateCard}>
      <View style={styles.dateInfo}>
        <View style={styles.calendarIcon}>
          <Ionicons name="calendar" size={24} color="#3B82F6" />
        </View>
        <View style={{ flex: 1 }}>
          <Text style={styles.dateValue}>{new Date(item.date).toLocaleDateString('id-ID', { day: 'numeric', month: 'long', year: 'numeric' })}</Text>
          {item.note && <Text style={styles.dateNote}>{item.note}</Text>}
        </View>
      </View>
      <TouchableOpacity onPress={() => handleUnblock(item.id)} style={styles.deleteBtn}>
        <Ionicons name="trash-outline" size={20} color="#EF4444" />
      </TouchableOpacity>
    </View>
  );

  return (
    <SwipeableScreen onSwipeBack={() => navigation.goBack()}>
      <View style={styles.container}>
        <StatusBar barStyle="dark-content" />
        
        {/* Header */}
        <View style={styles.header}>
          <SafeAreaView edges={['top']}>
            <View style={styles.headerTop}>
              <TouchableOpacity onPress={() => navigation.goBack()} style={styles.backBtn}>
                <Ionicons name="arrow-back" size={24} color="#0F172A" />
              </TouchableOpacity>
              <Text style={styles.headerTitle}>Manajemen Jadwal</Text>
              <TouchableOpacity onPress={() => setModalVisible(true)} style={styles.addBtn}>
                <Ionicons name="add" size={24} color="#3B82F6" />
              </TouchableOpacity>
            </View>
          </SafeAreaView>
        </View>

        {loading && blockedDates.length === 0 ? (
          <View style={styles.center}>
            <ActivityIndicator size="large" color="#3B82F6" />
          </View>
        ) : (
          <FlatList
            data={blockedDates}
            renderItem={renderItem}
            keyExtractor={item => item.id.toString()}
            contentContainerStyle={styles.list}
            ListHeaderComponent={() => (
              <View style={styles.infoBanner}>
                <Ionicons name="information-circle" size={20} color="#3B82F6" />
                <Text style={styles.infoText}>Daftar tanggal di bawah ini akan dianggap tidak tersedia dan tidak akan muncul di hasil pencarian jamaah.</Text>
              </View>
            )}
            ListEmptyComponent={() => (
              <View style={styles.emptyContainer}>
                <View style={styles.emptyIconBg}>
                  <Ionicons name="calendar-outline" size={48} color="#94A3B8" />
                </View>
                <Text style={styles.emptyTitle}>Belum ada tanggal diblokir</Text>
                <Text style={styles.emptySub}>Anda bisa memblokir tanggal tertentu saat Anda tidak bisa melayani jamaah.</Text>
              </View>
            )}
          />
        )}

        {/* Modal Block Date */}
        <Modal visible={modalVisible} animationType="slide" transparent={true}>
          <View style={styles.modalOverlay}>
            <View style={styles.modalContent}>
              <View style={styles.modalHeader}>
                <Text style={styles.modalTitle}>Blokir Tanggal</Text>
                <TouchableOpacity onPress={() => setModalVisible(false)}>
                  <Ionicons name="close" size={24} color="#0F172A" />
                </TouchableOpacity>
              </View>

              <ScrollView>
                <View style={styles.inputGroup}>
                  <Text style={styles.inputLabel}>Pilih Tanggal</Text>
                  {/* For simplicity in this demo, we'll use a simple text input or date selection logic */}
                  <TextInput 
                    style={styles.input} 
                    placeholder="YYYY-MM-DD" 
                    onChangeText={(val) => setSelectedDate(new Date(val))}
                    placeholderTextColor="#94A3B8"
                  />
                  <Text style={styles.helperText}>Format: YYYY-MM-DD (Contoh: 2024-05-20)</Text>
                </View>

                <View style={styles.inputGroup}>
                  <Text style={styles.inputLabel}>Catatan (Opsional)</Text>
                  <TextInput 
                    style={[styles.input, { height: 100, textAlignVertical: 'top' }]} 
                    placeholder="Contoh: Ada urusan keluarga" 
                    multiline 
                    value={note}
                    onChangeText={setNote}
                    placeholderTextColor="#94A3B8"
                  />
                </View>

                <TouchableOpacity style={styles.submitBtn} onPress={handleBlockDate}>
                  <LinearGradient colors={['#3B82F6', '#2563EB']} style={styles.submitGradient}>
                    <Text style={styles.submitText}>Simpan Blokir</Text>
                  </LinearGradient>
                </TouchableOpacity>
              </ScrollView>
            </View>
          </View>
        </Modal>
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
    shadowColor: '#000', shadowOffset: { width: 0, height: 4 }, shadowOpacity: 0.03, shadowRadius: 10, elevation: 5
  },
  headerTop: { flexDirection: 'row', alignItems: 'center', justifyContent: 'space-between', paddingHorizontal: 20, paddingTop: 10 },
  backBtn: { width: 44, height: 44, borderRadius: 22, backgroundColor: '#F8FAFC', justifyContent: 'center', alignItems: 'center' },
  addBtn: { width: 44, height: 44, borderRadius: 22, backgroundColor: '#EFF6FF', justifyContent: 'center', alignItems: 'center' },
  headerTitle: { color: '#0F172A', fontSize: 18, fontWeight: '900' },
  
  center: { flex: 1, justifyContent: 'center', alignItems: 'center' },
  list: { padding: 20, paddingBottom: 100 },
  
  infoBanner: { flexDirection: 'row', backgroundColor: '#EFF6FF', padding: 16, borderRadius: 20, marginBottom: 20, gap: 12 },
  infoText: { flex: 1, fontSize: 12, color: '#3B82F6', fontWeight: '600', lineHeight: 18 },

  dateCard: { 
    backgroundColor: '#FFF', 
    borderRadius: 24, 
    padding: 18, 
    marginBottom: 16, 
    flexDirection: 'row', 
    alignItems: 'center',
    borderWidth: 1, 
    borderColor: '#F1F5F9'
  },
  calendarIcon: { width: 48, height: 48, borderRadius: 16, backgroundColor: '#F0F9FF', justifyContent: 'center', alignItems: 'center', marginRight: 16 },
  dateInfo: { flex: 1, flexDirection: 'row', alignItems: 'center' },
  dateValue: { fontSize: 15, fontWeight: '800', color: '#1E293B' },
  dateNote: { fontSize: 12, color: '#94A3B8', marginTop: 2, fontWeight: '500' },
  deleteBtn: { width: 40, height: 40, borderRadius: 12, backgroundColor: '#FEF2F2', justifyContent: 'center', alignItems: 'center' },

  emptyContainer: { flex: 1, alignItems: 'center', justifyContent: 'center', marginTop: 60, paddingHorizontal: 40 },
  emptyIconBg: { width: 100, height: 100, borderRadius: 50, backgroundColor: '#F1F5F9', justifyContent: 'center', alignItems: 'center', marginBottom: 24 },
  emptyTitle: { fontSize: 18, fontWeight: '900', color: '#0F172A', marginBottom: 10 },
  emptySub: { fontSize: 13, color: '#64748B', textAlign: 'center', lineHeight: 20 },

  modalOverlay: { flex: 1, backgroundColor: 'rgba(0,0,0,0.5)', justifyContent: 'flex-end' },
  modalContent: { backgroundColor: '#FFF', borderTopLeftRadius: 32, borderTopRightRadius: 32, padding: 24, maxHeight: '80%' },
  modalHeader: { flexDirection: 'row', justifyContent: 'space-between', alignItems: 'center', marginBottom: 25 },
  modalTitle: { fontSize: 20, fontWeight: '900', color: '#0F172A' },
  
  inputGroup: { marginBottom: 20 },
  inputLabel: { fontSize: 13, fontWeight: '800', color: '#64748B', marginBottom: 10, marginLeft: 4 },
  input: { backgroundColor: '#F8FAFC', borderRadius: 18, padding: 16, fontSize: 15, fontWeight: '700', color: '#1E293B', borderWidth: 1, borderColor: '#F1F5F9' },
  helperText: { fontSize: 11, color: '#94A3B8', marginTop: 6, marginLeft: 4, fontWeight: '600' },
  
  submitBtn: { marginTop: 10, borderRadius: 20, overflow: 'hidden' },
  submitGradient: { paddingVertical: 18, alignItems: 'center' },
  submitText: { color: '#FFF', fontSize: 16, fontWeight: '900' }
});
