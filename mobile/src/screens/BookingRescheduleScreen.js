import React, { useEffect, useMemo, useState } from 'react';
import { View, Text, StyleSheet, ScrollView, TextInput } from 'react-native';
import { Calendar } from 'lucide-react-native';
import ScreenHeader from '../components/ScreenHeader';
import DatePickerField, { parseIsoDate, toIsoDate } from '../components/DatePickerField';
import { fetchBooking, submitRescheduleRequest } from '../api/bookings';
import { useAuth } from '../context/AuthContext';
import { Button, Card, InlineAlert } from '../ui';
import ChangePolicyNote from '../features/booking/ChangePolicyNote';
import { notifySuccessThen } from '../utils/feedback';
import { colors, layout, radius, spacing, typography } from '../theme/tokens';
import { billingNights, formatDateRange } from '../utils/bookingLabels';
import { useLocale } from '../utils/locale';

function addDays(isoDate, days) {
  const d = parseIsoDate(isoDate);
  d.setDate(d.getDate() + days);
  return toIsoDate(d);
}

export default function BookingRescheduleScreen({ navigation, route }) {
  const { token } = useAuth();
  const locale = useLocale(); const isEn = locale === 'en';
  const { bookingId, startsOn, endsOn, changePolicy: initialPolicy } = route.params;

  const nights = useMemo(() => billingNights(startsOn, endsOn), [startsOn, endsOn]);
  const [policy, setPolicy] = useState(initialPolicy || null);
  const [newStart, setNewStart] = useState('');
  const newEnd = newStart ? addDays(newStart, nights - 1) : '';
  const [note, setNote] = useState('');
  const [loading, setLoading] = useState(false);
  const [error, setError] = useState('');

  useEffect(() => {
    if (initialPolicy || !token) return;
    fetchBooking(token, bookingId)
      .then((data) => setPolicy(data.change_policy || null))
      .catch(() => {});
  }, [token, bookingId, initialPolicy]);

  const minStartDate = useMemo(() => {
    const d = new Date();
    d.setHours(0, 0, 0, 0);
    d.setDate(d.getDate() + (policy?.reschedule_min_days ?? 30));
    return d;
  }, [policy]);

  const handleSubmit = async () => {
    if (!newStart) {
      setError(isEn ? 'Select a new start date.' : 'Pilih tanggal mulai baru.');
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
        isEn ? 'Reschedule request submitted successfully.' : 'Permintaan reschedule berhasil diajukan.',
        'BookingDetail',
        { bookingId },
      );
    } catch (err) {
      setError(err.message || (isEn ? 'Failed to submit reschedule' : 'Gagal mengajukan reschedule'));
    } finally {
      setLoading(false);
    }
  };

  return (
    <View style={styles.container}>
      <ScreenHeader title={isEn ? 'Request Reschedule' : 'Ajukan Reschedule'} onBack={() => navigation.goBack()} />

      <ScrollView contentContainerStyle={styles.scroll} keyboardShouldPersistTaps="handled">
        <Card padding={spacing.lg} elevated={false}>
          <View style={styles.infoHeader}>
            <Calendar size={16} color={colors.baytgo} strokeWidth={2} />
            <Text style={styles.infoLabel}>{isEn ? 'Current schedule' : 'Jadwal saat ini'}</Text>
          </View>
          <Text style={styles.infoValue}>{formatDateRange(startsOn, endsOn)}</Text>
          <Text style={styles.infoHint}>{isEn ? `${nights} days — duration must remain the same after reschedule` : `${nights} hari — durasi harus sama setelah reschedule`}</Text>
        </Card>

        {policy ? (
          <View style={styles.policy}>
            <ChangePolicyNote policy={policy} mode="reschedule" />
          </View>
        ) : null}

        <Text style={styles.intro}>
          {isEn ? `Select a new start date. End date is calculated automatically (${nights} days).` : `Pilih tanggal mulai baru. Tanggal selesai dihitung otomatis (${nights} hari).`}
        </Text>

        {error ? <InlineAlert variant="error">{error}</InlineAlert> : null}

        <DatePickerField
          label={isEn ? 'New start date' : 'Tanggal mulai baru'}
          value={newStart}
          onChange={setNewStart}
          minimumDate={minStartDate}
          placeholder={isEn ? 'Select date' : 'Pilih tanggal'}
        />

        {newStart ? (
          <Card style={styles.preview} padding={spacing.md} elevated={false}>
            <Text style={styles.previewLabel}>{isEn ? 'New schedule' : 'Jadwal baru'}</Text>
            <Text style={styles.previewValue}>{formatDateRange(newStart, newEnd)}</Text>
          </Card>
        ) : null}

        <Text style={styles.label}>{isEn ? 'Note (optional)' : 'Catatan (opsional)'}</Text>
        <TextInput
          style={styles.textarea}
          value={note}
          onChangeText={setNote}
          placeholder={isEn ? 'Reason for reschedule...' : 'Alasan reschedule...'}
          placeholderTextColor={colors.textMuted}
          multiline
          maxLength={2000}
          textAlignVertical="top"
        />

        <Button label={isEn ? 'Submit Reschedule Request' : 'Kirim Permintaan Reschedule'} onPress={handleSubmit} loading={loading} />
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
  policy: { marginTop: spacing.lg },
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
