import React from 'react';
import { StyleSheet, Text, View } from 'react-native';
import Card from '../../ui/Card';
import { colors, spacing, typography } from '../../theme/tokens';

export default function BookingSection({ title, children, style, variant }) {
  return (
    <Card style={[styles.card, variant === 'warning' && styles.warning, variant === 'success' && styles.success, variant === 'danger' && styles.danger, style]} padding={spacing.lg}>
      {title ? <Text style={styles.title}>{title}</Text> : null}
      {children}
    </Card>
  );
}

export function SectionHeader({ title, action }) {
  return (
    <View style={styles.header}>
      <Text style={styles.title}>{title}</Text>
      {action}
    </View>
  );
}

const styles = StyleSheet.create({
  card: { marginBottom: spacing.md },
  warning: { borderColor: '#FDE68A', backgroundColor: colors.warningLight },
  success: { borderColor: '#A7F3D0', backgroundColor: colors.successLight },
  danger: { borderColor: '#FECACA', backgroundColor: colors.errorLight },
  title: {
    ...typography.caption,
    fontFamily: 'PlusJakartaSans_800ExtraBold',
    color: colors.baytgo,
    marginBottom: spacing.md,
  },
  header: {
    flexDirection: 'row',
    justifyContent: 'space-between',
    alignItems: 'center',
    marginBottom: spacing.sm,
  },
});
