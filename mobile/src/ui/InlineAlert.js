import React from 'react';
import { StyleSheet, Text, View } from 'react-native';
import { AlertCircle, AlertTriangle, Info } from 'lucide-react-native';
import { colors, radius, spacing, typography } from '../theme/tokens';

const VARIANTS = {
  error: { Icon: AlertCircle, bg: colors.errorLight, border: '#FECACA', color: colors.error },
  warning: { Icon: AlertTriangle, bg: colors.warningLight, border: '#FDE68A', color: '#B45309' },
  info: { Icon: Info, bg: colors.baytgoLight, border: colors.border, color: colors.baytgo },
};

export default function InlineAlert({ children, variant = 'error', style }) {
  const config = VARIANTS[variant] || VARIANTS.error;
  const { Icon } = config;

  return (
    <View style={[styles.wrap, { backgroundColor: config.bg, borderColor: config.border }, style]}>
      <Icon size={16} color={config.color} strokeWidth={2} />
      <Text style={[styles.text, { color: config.color }]}>{children}</Text>
    </View>
  );
}

const styles = StyleSheet.create({
  wrap: {
    flexDirection: 'row',
    alignItems: 'flex-start',
    gap: spacing.sm,
    borderRadius: radius.sm,
    borderWidth: 1,
    padding: spacing.md,
    marginBottom: spacing.lg,
  },
  text: {
    flex: 1,
    ...typography.caption,
    fontFamily: 'PlusJakartaSans_600SemiBold',
    lineHeight: 20,
  },
});
