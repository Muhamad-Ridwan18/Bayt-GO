import React from 'react';
import { Modal, StyleSheet, Text, TextInput, View } from 'react-native';
import Button from '../../ui/Button';
import PressableScale from '../../ui/PressableScale';
import { colors, radius, spacing, typography } from '../../theme/tokens';

const REJECTION_OPTIONS = [
  { value: 'jadwal_full', label: 'Jadwal penuh' },
  { value: 'illness', label: 'Sakit' },
  { value: 'force_majeure', label: 'Force majeure' },
  { value: 'other', label: 'Lainnya' },
];

export { REJECTION_OPTIONS };

export function RejectBookingModal({
  visible,
  rejectKind,
  rejectNote,
  onChangeKind,
  onChangeNote,
  onClose,
  onSubmit,
}) {
  return (
    <Modal visible={visible} transparent animationType="fade" onRequestClose={onClose}>
      <View style={styles.backdrop}>
        <View style={styles.card}>
          <Text style={styles.title}>Tolak booking</Text>
          <Text style={styles.label}>Alasan penolakan</Text>
          <View style={styles.chipRow}>
            {REJECTION_OPTIONS.map((opt) => (
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
          <Text style={styles.label}>Catatan (opsional)</Text>
          <TextInput
            style={styles.input}
            value={rejectNote}
            onChangeText={onChangeNote}
            placeholder="Jelaskan alasan penolakan kepada jamaah"
            placeholderTextColor={colors.textMuted}
            multiline
            maxLength={2000}
          />
          <View style={styles.actions}>
            <View style={styles.actionBtn}><Button label="Batal" onPress={onClose} variant="secondary" fullWidth={false} /></View>
            <View style={styles.actionBtn}><Button label="Tolak booking" onPress={onSubmit} variant="danger" fullWidth={false} /></View>
          </View>
        </View>
      </View>
    </Modal>
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
  return (
    <Modal visible={visible} transparent animationType="fade" onRequestClose={onClose}>
      <View style={styles.backdrop}>
        <View style={styles.card}>
          <Text style={styles.title}>{approve ? 'Setujui reschedule' : 'Tolak reschedule'}</Text>
          <Text style={styles.label}>Catatan untuk jamaah (opsional)</Text>
          <TextInput
            style={styles.input}
            value={note}
            onChangeText={onChangeNote}
            placeholder={
              approve
                ? 'Contoh: Jadwal baru sudah saya sesuaikan'
                : 'Jelaskan alasan penolakan reschedule'
            }
            placeholderTextColor={colors.textMuted}
            multiline
            maxLength={2000}
          />
          <View style={styles.actions}>
            <View style={styles.actionBtn}><Button label="Batal" onPress={onClose} variant="secondary" fullWidth={false} /></View>
            <View style={styles.actionBtn}>
              <Button label={approve ? 'Setujui' : 'Tolak'} onPress={onSubmit} variant={approve ? 'primary' : 'danger'} fullWidth={false} />
            </View>
          </View>
        </View>
      </View>
    </Modal>
  );
}

const styles = StyleSheet.create({
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
    marginTop: spacing.lg,
    marginBottom: spacing.sm,
    ...typography.small,
    color: colors.textSecondary,
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
  chipTextActive: { color: colors.white },
  input: {
    minHeight: 88,
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
