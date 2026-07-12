import React, { useCallback, useState } from 'react';
import {
  View, Text, StyleSheet, ScrollView, RefreshControl, TextInput, Alert,
} from 'react-native';
import { useFocusEffect } from '@react-navigation/native';
import { Trash2 } from 'lucide-react-native';
import TabPageHeader from '../components/TabPageHeader';
import DatePickerField, { parseIsoDate } from '../components/DatePickerField';
import { fetchBlockedDates, addBlockedDates, removeBlockedDate } from '../api/jadwal';
import { useAuth } from '../context/AuthContext';
import { Button, Card, EmptyState, PressableScale, SkeletonList } from '../ui';
import { colors, layout, radius, spacing, typography } from '../theme/tokens';
import { notifyError, notifySuccess } from '../utils/feedback';

function BlockedDateRow({ item, onDelete, deleting }) {
  return (
    <Card style={styles.row} padding={spacing.lg} elevated={false}>
      <View style={styles.rowMeta}>
        <Text style={styles.rowDate}>{item.date}</Text>
        {item.note ? <Text style={styles.rowNote}>{item.note}</Text> : null}
      </View>
      <PressableScale onPress={() => onDelete(item)} disabled={deleting} haptic="light" style={styles.deleteBtn}>
        <Trash2 size={18} color={colors.error} strokeWidth={2} />
      </PressableScale>
    </Card>
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

  useFocusEffect(useCallback(() => { load(); }, [load]));

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
      notifySuccess('Jadwal libur berhasil disimpan.');
      setStartDate('');
      setEndDate('');
      setNote('');
      await load(true);
    } catch (err) {
      notifyError(err.message || 'Tidak dapat menyimpan jadwal libur');
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
        <SkeletonList count={3} style={styles.skeleton} />
      ) : (
        <ScrollView
          contentContainerStyle={styles.scroll}
          refreshControl={
            <RefreshControl refreshing={refreshing} onRefresh={() => load(true)} tintColor={colors.baytgo} />
          }
        >
          <Card padding={spacing.lg} elevated={false}>
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
              placeholderTextColor={colors.textMuted}
              value={note}
              onChangeText={setNote}
            />
            <Button label="Simpan libur" onPress={handleAdd} loading={submitting} />
          </Card>

          <Text style={styles.sectionTitle}>Tanggal diblokir</Text>
          {items.length === 0 ? (
            <EmptyState
              variant="schedule"
              title="Belum ada jadwal libur"
              description="Tambahkan tanggal libur agar jamaah tidak bisa memesan pada hari tersebut."
            />
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
  container: { flex: 1, backgroundColor: colors.background },
  skeleton: { paddingHorizontal: layout.screenPadding, paddingTop: spacing.lg },
  scroll: { paddingHorizontal: layout.screenPadding, paddingBottom: spacing['3xl'] },
  formTitle: { ...typography.caption, fontFamily: 'PlusJakartaSans_800ExtraBold', color: colors.baytgo, marginBottom: spacing.md },
  input: {
    backgroundColor: colors.background,
    borderRadius: radius.sm,
    paddingHorizontal: spacing.lg,
    paddingVertical: spacing.md,
    marginBottom: spacing.md,
    ...typography.caption,
    color: colors.textPrimary,
    borderWidth: 1,
    borderColor: colors.border,
  },
  sectionTitle: { ...typography.subtitle, color: colors.baytgo, marginTop: spacing.xl, marginBottom: spacing.md },
  row: { flexDirection: 'row', alignItems: 'center', gap: spacing.md, marginBottom: spacing.sm },
  rowMeta: { flex: 1 },
  rowDate: { ...typography.caption, fontFamily: 'PlusJakartaSans_800ExtraBold', color: colors.textPrimary },
  rowNote: { marginTop: spacing.xs, ...typography.small, color: colors.textSecondary, fontWeight: '500' },
  deleteBtn: {
    width: 36,
    height: 36,
    borderRadius: radius.sm - 2,
    backgroundColor: colors.errorLight,
    alignItems: 'center',
    justifyContent: 'center',
  },
});
