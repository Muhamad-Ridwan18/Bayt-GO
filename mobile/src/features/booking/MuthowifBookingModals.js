import React from 'react';
import { Modal, StyleSheet, Text, TextInput, View } from 'react-native';
import Button from '../../ui/Button';
import PressableScale from '../../ui/PressableScale';
import { colors, radius, spacing, typography } from '../../theme/tokens';
import { useLocale } from '../../utils/locale';

const REJECTION_OPTIONS = {
  id: [
    { value: 'jadwal_full', label: 'Jadwal muthowif penuh' },
    { value: 'illness', label: 'Sakit / berhalangan' },
    { value: 'force_majeure', label: 'Force majeure' },
    { value: 'other', label: 'Alasan lain' },
  ],
  en: [
    { value: 'jadwal_full', label: 'Muthowif schedule is full' },
    { value: 'illness', label: 'Illness / unavailable' },
    { value: 'force_majeure', label: 'Force majeure' },
    { value: 'other', label: 'Other reason' },
  ],
};

export { REJECTION_OPTIONS };

export function RejectBookingForm({
  rejectKind,
  rejectNote,
  onChangeKind,
  onChangeNote,
}) {
  const locale = useLocale();
  const isEn = locale === 'en';
  const options = REJECTION_OPTIONS[locale] || REJECTION_OPTIONS.id;
  return (
    <View style={styles.rejectForm}>
      <Text style={styles.label}>{isEn ? 'Rejection reason' : 'Alasan penolakan'}</Text>
      <View style={styles.chipRow}>
        {options.map((opt) => (
          <PressableScale
            key={opt.value}
            onPress={() => onChangeKind(opt.value)}
            style={[styles.chip, rejectKind === opt.value && styles.chipActive]}
          >
            <Text style={[styles.chipText, rejectKind === opt.value && styles.chipTextActive]}>
              {opt.label}
            </Text>
          </PressableScale>
        ))}
      </View>
      <Text style={styles.label}>{isEn ? 'Note for pilgrim (optional)' : 'Catatan untuk jamaah (opsional)'}</Text>
      <TextInput
        style={styles.input}
        value={rejectNote}
        onChangeText={onChangeNote}
        placeholder={isEn ? 'Explain the rejection reason to the pilgrim' : 'Jelaskan alasan penolakan kepada jamaah'}
        placeholderTextColor={colors.textMuted}
        multiline
        maxLength={2000}
      />
    </View>
  );
}

export function RescheduleDecisionModal({
  visible,
  approve,
  note,
  onChangeNote,
  onClose,
  onSubmit,
}) {
  const locale = useLocale();
  const isEn = locale === 'en';
  return (
    <Modal visible={visible} transparent animationType="fade" onRequestClose={onClose}>
      <View style={styles.backdrop}>
        <View style={styles.card}>
          <Text style={styles.title}>{approve ? (isEn ? 'Approve reschedule' : 'Setujui reschedule') : (isEn ? 'Reject reschedule' : 'Tolak reschedule')}</Text>
          <Text style={styles.label}>{isEn ? 'Note for pilgrim (optional)' : 'Catatan untuk jamaah (opsional)'}</Text>
          <TextInput
            style={styles.input}
            value={note}
            onChangeText={onChangeNote}
            placeholder={
              approve
                ? (isEn ? 'Example: I have adjusted to the new schedule' : 'Contoh: Jadwal baru sudah saya sesuaikan')
                : (isEn ? 'Explain why the reschedule is rejected' : 'Jelaskan alasan penolakan reschedule')
            }
            placeholderTextColor={colors.textMuted}
            multiline
            maxLength={2000}
          />
          <View style={styles.actions}>
            <View style={styles.actionBtn}><Button label={isEn ? 'Cancel' : 'Batal'} onPress={onClose} variant="secondary" fullWidth={false} /></View>
            <View style={styles.actionBtn}>
              <Button label={approve ? (isEn ? 'Approve' : 'Setujui') : (isEn ? 'Reject' : 'Tolak')} onPress={onSubmit} variant={approve ? 'primary' : 'danger'} fullWidth={false} />
            </View>
          </View>
        </View>
      </View>
    </Modal>
  );
}

const styles = StyleSheet.create({
  rejectForm: { marginTop: spacing.md, gap: spacing.sm },
  backdrop: {
    flex: 1,
    backgroundColor: colors.overlay,
    justifyContent: 'center',
    padding: spacing.xl,
  },
  card: {
    backgroundColor: colors.card,
    borderRadius: radius.md,
    padding: spacing.xl,
  },
  title: { ...typography.subtitle, color: colors.baytgo },
  label: {
    marginTop: spacing.sm,
    marginBottom: spacing.xs,
    ...typography.small,
    color: colors.textSecondary,
    fontFamily: 'PlusJakartaSans_700Bold',
  },
  chipRow: { flexDirection: 'row', flexWrap: 'wrap', gap: spacing.sm },
  chip: {
    borderRadius: radius.full,
    paddingHorizontal: spacing.md,
    paddingVertical: spacing.sm,
    backgroundColor: colors.background,
    borderWidth: 1,
    borderColor: colors.border,
  },
  chipActive: { backgroundColor: colors.baytgo, borderColor: colors.baytgo },
  chipText: { ...typography.small, color: colors.textSecondary },
  chipTextActive: { color: colors.white, fontFamily: 'PlusJakartaSans_700Bold' },
  input: {
    minHeight: 96,
    borderRadius: radius.sm,
    borderWidth: 1,
    borderColor: colors.border,
    paddingHorizontal: spacing.lg,
    paddingVertical: spacing.md,
    ...typography.caption,
    color: colors.textPrimary,
    textAlignVertical: 'top',
  },
  actions: { flexDirection: 'row', gap: spacing.md, marginTop: spacing.xl },
  actionBtn: { flex: 1 },
});
