import React, { useMemo, useState } from 'react';
import {
  View,
  Text,
  StyleSheet,
  ScrollView,
  TouchableOpacity,
  ActivityIndicator,
  Alert,
  TextInput,
} from 'react-native';
import { LinearGradient } from 'expo-linear-gradient';
import ScreenHeader from '../components/ScreenHeader';
import DatePickerField, { parseIsoDate, toIsoDate } from '../components/DatePickerField';
import { submitRescheduleRequest } from '../api/bookings';
import { useAuth } from '../context/AuthContext';
import { colors } from '../theme/colors';
import { billingNights, formatDateRange } from '../utils/bookingLabels';

function addDays(isoDate, days) {
  const d = parseIsoDate(isoDate);
  d.setDate(d.getDate() + days);
  return toIsoDate(d);
}

export default function BookingRescheduleScreen({ navigation, route }) {
  const { token } = useAuth();
  const { bookingId, startsOn, endsOn } = route.params;

  const nights = useMemo(() => billingNights(startsOn, endsOn), [startsOn, endsOn]);
  const [newStart, setNewStart] = useState('');
  const newEnd = newStart ? addDays(newStart, nights - 1) : '';
  const [note, setNote] = useState('');
  const [loading, setLoading] = useState(false);
  const [error, setError] = useState('');

  const tomorrow = useMemo(() => {
    const d = new Date();
    d.setDate(d.getDate() + 1);
    d.setHours(0, 0, 0, 0);
    return d;
  }, []);

  const handleSubmit = async () => {
    if (!newStart) {
      setError('Pilih tanggal mulai baru.');
      return;
    }

    setLoading(true);
    setError('');
    try {
      await submitRescheduleRequest(token, bookingId, {
        new_start_date: newStart,
        ends_on: newEnd,
        reschedule_note: note.trim() || null,
      });
      Alert.alert('Berhasil', 'Permintaan reschedule berhasil diajukan.', [
        { text: 'OK', onPress: () => navigation.navigate('BookingDetail', { bookingId }) },
      ]);
    } catch (err) {
      setError(err.message || 'Gagal mengajukan reschedule');
    } finally {
      setLoading(false);
    }
  };

  return (
    <View style={styles.container}>
      <ScreenHeader title="Ajukan Reschedule" onBack={() => navigation.goBack()} />

      <ScrollView contentContainerStyle={styles.scroll} keyboardShouldPersistTaps="handled">
        <View style={styles.infoCard}>
          <Text style={styles.infoLabel}>Jadwal saat ini</Text>
          <Text style={styles.infoValue}>{formatDateRange(startsOn, endsOn)}</Text>
          <Text style={styles.infoHint}>{nights} hari — durasi harus sama setelah reschedule</Text>
        </View>

        <Text style={styles.intro}>
          Pilih tanggal mulai baru. Tanggal selesai dihitung otomatis ({nights} hari).
        </Text>

        {error ? <Text style={styles.error}>{error}</Text> : null}

        <DatePickerField
          label="Tanggal mulai baru"
          value={newStart}
          onChange={setNewStart}
          minimumDate={tomorrow}
          placeholder="Pilih tanggal"
        />

        {newStart ? (
          <View style={styles.preview}>
            <Text style={styles.previewLabel}>Jadwal baru</Text>
            <Text style={styles.previewValue}>{formatDateRange(newStart, newEnd)}</Text>
          </View>
        ) : null}

        <Text style={styles.label}>Catatan (opsional)</Text>
        <TextInput
          style={styles.textarea}
          value={note}
          onChangeText={setNote}
          placeholder="Alasan reschedule..."
          placeholderTextColor={colors.slate400}
          multiline
          maxLength={2000}
          textAlignVertical="top"
        />

        <TouchableOpacity style={styles.submitBtn} onPress={handleSubmit} disabled={loading} activeOpacity={0.9}>
          <LinearGradient colors={[colors.baytgo, colors.baytgoDark]} style={styles.submitGradient}>
            {loading ? (
              <ActivityIndicator color={colors.white} />
            ) : (
              <Text style={styles.submitText}>Kirim Permintaan Reschedule</Text>
            )}
          </LinearGradient>
        </TouchableOpacity>
      </ScrollView>
    </View>
  );
}

const styles = StyleSheet.create({
  container: { flex: 1, backgroundColor: colors.canvas },
  scroll: { padding: 16, paddingBottom: 32 },
  infoCard: {
    backgroundColor: colors.white,
    borderRadius: 16,
    padding: 16,
    marginBottom: 16,
    borderWidth: 1,
    borderColor: colors.slate100,
  },
  infoLabel: { fontSize: 11, fontWeight: '800', color: colors.slate500, textTransform: 'uppercase' },
  infoValue: { marginTop: 6, fontSize: 16, fontWeight: '800', color: colors.baytgo },
  infoHint: { marginTop: 4, fontSize: 12, color: colors.slate500, fontWeight: '600' },
  intro: { fontSize: 14, lineHeight: 21, color: colors.slate600, fontWeight: '500', marginBottom: 12 },
  error: { marginBottom: 12, fontSize: 13, color: '#DC2626', fontWeight: '600' },
  preview: {
    backgroundColor: colors.emerald50,
    borderRadius: 14,
    padding: 12,
    marginBottom: 16,
    borderWidth: 1,
    borderColor: '#A7F3D0',
  },
  previewLabel: { fontSize: 11, fontWeight: '700', color: colors.emerald600 },
  previewValue: { marginTop: 4, fontSize: 14, fontWeight: '800', color: colors.baytgo },
  label: { fontSize: 12, fontWeight: '800', color: colors.slate600, marginBottom: 8 },
  textarea: {
    minHeight: 100,
    backgroundColor: colors.white,
    borderRadius: 16,
    borderWidth: 1,
    borderColor: colors.slate100,
    padding: 14,
    fontSize: 14,
    fontWeight: '500',
    color: colors.slate900,
    marginBottom: 20,
  },
  submitBtn: { borderRadius: 16, overflow: 'hidden' },
  submitGradient: { paddingVertical: 16, alignItems: 'center' },
  submitText: { color: colors.white, fontSize: 15, fontWeight: '800' },
});
