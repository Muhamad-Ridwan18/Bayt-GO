import React from 'react';
import { View, Text, TextInput, StyleSheet } from 'react-native';
import { CirclePlus, Trash2 } from 'lucide-react-native';
import { PressableScale } from '../ui';
import { colors, spacing, radius, typography } from '../theme/tokens';

export default function RepeatingTextField({
  label,
  items,
  onChange,
  placeholder,
  addLabel = 'Tambah baris',
  optional = false,
  multiline = false,
}) {
  const rows = items?.length ? items : [''];

  const updateRow = (index, value) => {
    const next = [...rows];
    next[index] = value;
    onChange(next);
  };

  const addRow = () => onChange([...rows, '']);

  const removeRow = (index) => {
    if (rows.length <= 1) {
      onChange(['']);
      return;
    }
    onChange(rows.filter((_, i) => i !== index));
  };

  return (
    <View style={styles.wrap}>
      <Text style={styles.label}>
        {label}
        {optional ? ' (opsional)' : ''}
      </Text>
      {rows.map((row, index) => (
        <View key={`row-${index}`} style={styles.row}>
          <TextInput
            style={[styles.input, multiline && styles.inputMultiline]}
            value={row}
            onChangeText={(v) => updateRow(index, v)}
            placeholder={placeholder}
            multiline={multiline}
          />
          <PressableScale style={styles.removeBtn} onPress={() => removeRow(index)} hitSlop={8} haptic="light">
            <Trash2 size={18} color={colors.error} strokeWidth={2} />
          </PressableScale>
        </View>
      ))}
      <PressableScale style={styles.addBtn} onPress={addRow} haptic="light">
        <CirclePlus size={16} color={colors.baytgo} strokeWidth={2} />
        <Text style={styles.addText}>{addLabel}</Text>
      </PressableScale>
    </View>
  );
}

const styles = StyleSheet.create({
  wrap: { marginBottom: spacing.md + 2 },
  label: { ...typography.caption, fontWeight: '800', color: colors.slate600, marginBottom: spacing.sm, marginLeft: 2 },
  row: { flexDirection: 'row', alignItems: 'flex-start', gap: spacing.sm, marginBottom: spacing.sm },
  input: {
    flex: 1,
    backgroundColor: colors.white,
    borderRadius: radius.md,
    borderWidth: 1,
    borderColor: colors.surface,
    paddingHorizontal: spacing.md + 2,
    paddingVertical: spacing.md,
    ...typography.body,
    fontWeight: '600',
    color: colors.textPrimary,
  },
  inputMultiline: { minHeight: 72, textAlignVertical: 'top' },
  removeBtn: { marginTop: spacing.md },
  addBtn: { flexDirection: 'row', alignItems: 'center', gap: spacing.sm - 2 },
  addText: { ...typography.caption, fontWeight: '800', color: colors.baytgo },
});
