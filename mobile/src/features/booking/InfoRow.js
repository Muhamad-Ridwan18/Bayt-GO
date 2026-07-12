import React from 'react';
import { StyleSheet, Text, View } from 'react-native';
import { colors, spacing, typography } from '../../theme/tokens';

export default function InfoRow({ label, value, bold }) {
  return (
    <View style={styles.row}>
      <Text style={styles.label}>{label}</Text>
      <Text style={[styles.value, bold && styles.bold]}>{value}</Text>
    </View>
  );
}

const styles = StyleSheet.create({
  row: {
    flexDirection: 'row',
    justifyContent: 'space-between',
    gap: spacing.md,
    paddingVertical: spacing.sm,
    borderBottomWidth: 1,
    borderBottomColor: colors.border,
  },
  label: { ...typography.caption, color: colors.textSecondary },
  value: {
    flex: 1,
    ...typography.caption,
    fontFamily: 'PlusJakartaSans_700Bold',
    color: colors.textPrimary,
    textAlign: 'right',
  },
  bold: { ...typography.body, fontSize: 15, color: colors.baytgo },
});
