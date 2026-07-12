import React, { useMemo, useState } from 'react';
import { View, Text, StyleSheet, ScrollView, TextInput } from 'react-native';
import { Calendar } from 'lucide-react-native';
import ScreenHeader from '../components/ScreenHeader';
import DatePickerField, { parseIsoDate, toIsoDate } from '../components/DatePickerField';
import { submitRescheduleRequest } from '../api/bookings';
import { useAuth } from '../context/AuthContext';
import { Button, Card, InlineAlert } from '../ui';
import { notifySuccessThen } from '../utils/feedback';
import { colors, layout, radius, spacing, typography } from '../theme/tokens';
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
      notifySuccessThen(
        navigation,
        'Permintaan reschedule berhasil diajukan.',
        'BookingDetail',
        { bookingId },
      );
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
        <Card padding={spacing.lg} elevated={false}>
          <View style={styles.infoHeader}>
            <Calendar size={16} color={colors.baytgo} strokeWidth={2} />
            <Text style={styles.infoLabel}>Jadwal saat ini</Text>
          </View>
          <Text style={styles.infoValue}>{formatDateRange(startsOn, endsOn)}</Text>
          <Text style={styles.infoHint}>{nights} hari — durasi harus sama setelah reschedule</Text>
        </Card>

        <Text style={styles.intro}>
          Pilih tanggal mulai baru. Tanggal selesai dihitung otomatis ({nights} hari).
        </Text>

        {error ? <InlineAlert variant="error">{error}</InlineAlert> : null}

        <DatePickerField
          label="Tanggal mulai baru"
          value={newStart}
          onChange={setNewStart}
          minimumDate={tomorrow}
          placeholder="Pilih tanggal"
        />

        {newStart ? (
          <Card style={styles.preview} padding={spacing.md} elevated={false}>
            <Text style={styles.previewLabel}>Jadwal baru</Text>
            <Text style={styles.previewValue}>{formatDateRange(newStart, newEnd)}</Text>
          </Card>
        ) : null}

        <Text style={styles.label}>Catatan (opsional)</Text>
        <TextInput
          style={styles.textarea}
          value={note}
          onChangeText={setNote}
          placeholder="Alasan reschedule..."
          placeholderTextColor={colors.textMuted}
          multiline
          maxLength={2000}
          textAlignVertical="top"
        />

        <Button label="Kirim Permintaan Reschedule" onPress={handleSubmit} loading={loading} />
      </ScrollView>
    </View>
  );
}

const styles = StyleSheet.create({
  container: { flex: 1, backgroundColor: colors.background },
  scroll: { padding: layout.screenPadding, paddingBottom: spacing['3xl'] },
  infoHeader: { flexDirection: 'row', alignItems: 'center', gap: spacing.sm },
  infoLabel: { ...typography.label, color: colors.textSecondary },
  infoValue: { marginTop: spacing.sm, ...typography.caption, fontFamily: 'PlusJakartaSans_800ExtraBold', color: colors.baytgo },
  infoHint: { marginTop: spacing.xs, ...typography.small, color: colors.textSecondary, fontWeight: '500' },
  intro: { ...typography.caption, lineHeight: 22, color: colors.textSecondary, marginVertical: spacing.lg },
  preview: { backgroundColor: colors.successLight, borderColor: '#A7F3D0', marginBottom: spacing.lg },
  previewLabel: { ...typography.label, color: colors.success },
  previewValue: { marginTop: spacing.xs, ...typography.caption, fontFamily: 'PlusJakartaSans_800ExtraBold', color: colors.baytgo },
  label: { ...typography.label, color: colors.textSecondary, marginBottom: spacing.sm },
  textarea: {
    minHeight: 100,
    backgroundColor: colors.card,
    borderRadius: radius.sm,
    borderWidth: 1,
    borderColor: colors.border,
    padding: spacing.lg,
    ...typography.caption,
    color: colors.textPrimary,
    marginBottom: spacing.xl,
  },
});
