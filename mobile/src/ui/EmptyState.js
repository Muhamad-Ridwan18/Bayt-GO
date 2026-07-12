import React from 'react';
import { StyleSheet, Text, View } from 'react-native';
import {
  CalendarCheck,
  MessageCircle,
  PackageOpen,
  Receipt,
  Search,
  Sparkles,
} from 'lucide-react-native';
import Button from './Button';
import Card from './Card';
import { colors, radius, spacing, typography } from '../theme/tokens';

const PRESETS = {
  default: { Icon: Sparkles, bg: colors.baytgoLight, color: colors.baytgo },
  bookings: { Icon: Receipt, bg: '#EFF6FF', color: '#2563EB' },
  chat: { Icon: MessageCircle, bg: '#ECFDF5', color: colors.success },
  search: { Icon: Search, bg: colors.baytgoLight, color: colors.baytgo },
  schedule: { Icon: CalendarCheck, bg: '#FFFBEB', color: '#D97706' },
  package: { Icon: PackageOpen, bg: '#F5F3FF', color: '#7C3AED' },
};

export default function EmptyState({
  title,
  description,
  actionLabel,
  onAction,
  icon,
  variant = 'default',
}) {
  const preset = PRESETS[variant] || PRESETS.default;
  const { Icon } = preset;

  return (
    <Card elevated={false} variant="flat" style={styles.wrap} padding={spacing['3xl']}>
      <View style={[styles.iconWrap, { backgroundColor: preset.bg }]}>
        {icon || <Icon size={30} color={preset.color} strokeWidth={1.8} />}
      </View>
      <Text style={styles.title}>{title}</Text>
      {description ? <Text style={styles.description}>{description}</Text> : null}
      {actionLabel ? (
        <View style={styles.action}>
          <Button label={actionLabel} onPress={onAction} size="sm" fullWidth={false} />
        </View>
      ) : null}
    </Card>
  );
}

const styles = StyleSheet.create({
  wrap: {
    alignItems: 'center',
    marginHorizontal: spacing['2xl'],
    marginTop: spacing['4xl'],
  },
  iconWrap: {
    width: 72,
    height: 72,
    borderRadius: radius.lg,
    alignItems: 'center',
    justifyContent: 'center',
    marginBottom: spacing.xl,
  },
  title: {
    ...typography.subtitle,
    color: colors.textPrimary,
    textAlign: 'center',
  },
  description: {
    ...typography.caption,
    color: colors.textSecondary,
    textAlign: 'center',
    marginTop: spacing.sm,
    maxWidth: 280,
    lineHeight: 22,
  },
  action: { marginTop: spacing['2xl'] },
});
