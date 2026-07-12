import React from 'react';
import { View, Text, StyleSheet } from 'react-native';
import { Paperclip } from 'lucide-react-native';
import { PressableScale, UploadPreviewStrip } from '../ui';
import { colors, spacing, radius, typography } from '../theme/tokens';

export default function DocumentPickerField({ label, required, file, onPick, onClear }) {
  return (
    <View style={styles.field}>
      <Text style={styles.label}>
        {label}
        {required ? <Text style={styles.required}> *</Text> : null}
      </Text>

      {file ? (
        <>
          <UploadPreviewStrip files={[file]} onRemove={() => onClear?.()} size={80} />
          <PressableScale onPress={onPick} haptic="light" style={styles.changeBtn}>
            <Text style={styles.changeText}>Ganti file</Text>
          </PressableScale>
        </>
      ) : (
        <PressableScale style={styles.btn} onPress={onPick} haptic="light">
          <Paperclip size={20} color={colors.baytgo} strokeWidth={2} />
          <Text style={styles.btnText}>Pilih file (PDF/JPG/PNG)</Text>
        </PressableScale>
      )}
    </View>
  );
}

const styles = StyleSheet.create({
  field: { marginBottom: spacing.md + 2 },
  label: { ...typography.caption, fontWeight: '800', color: colors.slate700, marginBottom: spacing.sm },
  required: { color: colors.error },
  btn: {
    flexDirection: 'row',
    alignItems: 'center',
    gap: spacing.sm + 2,
    backgroundColor: colors.white,
    borderRadius: radius.sm,
    paddingHorizontal: spacing.md + 2,
    paddingVertical: spacing.md + 2,
    borderWidth: 1,
    borderColor: colors.surface,
  },
  btnText: { flex: 1, ...typography.caption, fontWeight: '600', color: colors.slate600 },
  changeBtn: { marginTop: spacing.sm },
  changeText: { ...typography.caption, fontWeight: '700', color: colors.baytgo },
});
