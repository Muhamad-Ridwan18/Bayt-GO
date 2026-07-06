import React, { useCallback, useState } from 'react';
import {
  View,
  Text,
  StyleSheet,
  ScrollView,
  ActivityIndicator,
  RefreshControl,
  TouchableOpacity,
  TextInput,
  Alert,
} from 'react-native';
import { useFocusEffect } from '@react-navigation/native';
import { Ionicons } from '@expo/vector-icons';
import TabPageHeader from '../components/TabPageHeader';
import DatePickerField, { parseIsoDate } from '../components/DatePickerField';
import { fetchBlockedDates, addBlockedDates, removeBlockedDate } from '../api/jadwal';
import { useAuth } from '../context/AuthContext';
import { colors } from '../theme/colors';

function BlockedDateRow({ item, onDelete, deleting }) {
  return (
    <View style={styles.row}>
      <View style={styles.rowMeta}>
        <Text style={styles.rowDate}>{item.date}</Text>
        <Text style={styles.rowNote}>{item.note}</Text>
      </View>
      <TouchableOpacity
        style={styles.deleteBtn}
        onPress={() => onDelete(item)}
        disabled={deleting}
        hitSlop={8}
      >
        <Ionicons name="trash-outline" size={18} color="#B91C1C" />
      </TouchableOpacity>
    </View>
  );
}

export default function ScheduleScreen() {
  const { token } = useAuth();
  const [loading, setLoading] = useState(true);
  const [refreshing, setRefreshing] = useState(false);
  const [submitting, setSubmitting] = useState(false);
  const [deletingId, setDeletingId] = useState(null);
  const [items, setItems] = useState([]);
  const [startDate, setStartDate] = useState('');
  const [endDate, setEndDate] = useState('');
  const [note, setNote] = useState('');

  const load = useCallback(async (refresh = false) => {
    if (refresh) setRefreshing(true);
    else setLoading(true);

    try {
      const data = await fetchBlockedDates(token);
      setItems(data.blocked_dates || []);
    } catch (err) {
      Alert.alert('Gagal', err.message || 'Tidak dapat memuat jadwal libur');
    } finally {
      setLoading(false);
      setRefreshing(false);
    }
  }, [token]);

  useFocusEffect(
    useCallback(() => {
      load();
    }, [load]),
  );

  const handleAdd = async () => {
    if (!startDate) {
      Alert.alert('Validasi', 'Pilih tanggal mulai libur.');
      return;
    }

    setSubmitting(true);
    try {
      await addBlockedDates(token, {
        start_date: startDate,
        end_date: endDate || startDate,
        note: note.trim() || null,
      });
      Alert.alert('Berhasil', 'Jadwal libur berhasil disimpan.');
      setStartDate('');
      setEndDate('');
      setNote('');
      await load(true);
    } catch (err) {
      Alert.alert('Gagal', err.message || 'Tidak dapat menyimpan jadwal libur');
    } finally {
      setSubmitting(false);
    }
  };

  const handleDelete = (item) => {
    Alert.alert('Hapus libur?', `Hapus tanggal ${item.date}?`, [
      { text: 'Batal', style: 'cancel' },
      {
        text: 'Hapus',
        style: 'destructive',
        onPress: async () => {
          setDeletingId(item.id);
          try {
            await removeBlockedDate(token, item.id);
            await load(true);
          } catch (err) {
            Alert.alert('Gagal', err.message || 'Tidak dapat menghapus jadwal libur');
          } finally {
            setDeletingId(null);
          }
        },
      },
    ]);
  };

  return (
    <View style={styles.container}>
      <TabPageHeader title="Jadwal libur" subtitle="Blokir tanggal tidak tersedia" />

      {loading && !refreshing ? (
        <ActivityIndicator color={colors.baytgo} style={styles.loader} />
      ) : (
        <ScrollView
          contentContainerStyle={styles.scroll}
          refreshControl={
            <RefreshControl refreshing={refreshing} onRefresh={() => load(true)} tintColor={colors.baytgo} />
          }
        >
          <View style={styles.formCard}>
            <Text style={styles.formTitle}>Tambah libur</Text>
            <DatePickerField
              label="Tanggal mulai"
              value={startDate}
              onChange={setStartDate}
              placeholder="Pilih tanggal mulai"
              variant="soft"
            />
            <DatePickerField
              label="Tanggal selesai"
              value={endDate}
              onChange={setEndDate}
              placeholder="Sama dengan mulai (opsional)"
              minimumDate={startDate ? parseIsoDate(startDate) : undefined}
              variant="soft"
            />
            <TextInput
              style={styles.input}
              placeholder="Catatan (opsional)"
              value={note}
              onChangeText={setNote}
            />
            <TouchableOpacity
              style={[styles.submitBtn, submitting && styles.submitBtnDisabled]}
              onPress={handleAdd}
              disabled={submitting}
            >
              <Text style={styles.submitBtnText}>{submitting ? 'Menyimpan…' : 'Simpan libur'}</Text>
            </TouchableOpacity>
          </View>

          <Text style={styles.sectionTitle}>Tanggal diblokir</Text>
          {items.length === 0 ? (
            <Text style={styles.muted}>Belum ada jadwal libur.</Text>
          ) : (
            items.map((item) => (
              <BlockedDateRow
                key={String(item.id)}
                item={item}
                onDelete={handleDelete}
                deleting={deletingId === item.id}
              />
            ))
          )}
        </ScrollView>
      )}
    </View>
  );
}

const styles = StyleSheet.create({
  container: { flex: 1, backgroundColor: colors.canvas },
  scroll: { paddingHorizontal: 20, paddingBottom: 32 },
  loader: { marginTop: 40 },
  formCard: {
    backgroundColor: colors.white,
    borderRadius: 18,
    padding: 16,
    marginBottom: 16,
    borderWidth: 1,
    borderColor: colors.slate100,
  },
  formTitle: { fontSize: 16, fontWeight: '900', color: colors.baytgo, marginBottom: 12 },
  input: {
    backgroundColor: colors.canvas,
    borderRadius: 12,
    paddingHorizontal: 14,
    paddingVertical: 12,
    marginBottom: 10,
    fontSize: 14,
    fontWeight: '600',
    color: colors.slate900,
    borderWidth: 1,
    borderColor: colors.slate100,
  },
  submitBtn: {
    marginTop: 4,
    backgroundColor: colors.baytgo,
    borderRadius: 14,
    paddingVertical: 14,
    alignItems: 'center',
  },
  submitBtnDisabled: { opacity: 0.6 },
  submitBtnText: { color: colors.white, fontWeight: '800', fontSize: 14 },
  sectionTitle: { fontSize: 18, fontWeight: '900', color: colors.baytgo, marginBottom: 12 },
  muted: { fontSize: 14, color: colors.slate500, fontWeight: '600' },
  row: {
    flexDirection: 'row',
    alignItems: 'center',
    gap: 12,
    backgroundColor: colors.white,
    borderRadius: 14,
    padding: 14,
    marginBottom: 8,
    borderWidth: 1,
    borderColor: colors.slate100,
  },
  rowMeta: { flex: 1 },
  rowDate: { fontSize: 14, fontWeight: '800', color: colors.slate900 },
  rowNote: { marginTop: 4, fontSize: 12, fontWeight: '600', color: colors.slate500 },
  deleteBtn: {
    width: 36,
    height: 36,
    borderRadius: 10,
    backgroundColor: '#FEF2F2',
    alignItems: 'center',
    justifyContent: 'center',
  },
});
