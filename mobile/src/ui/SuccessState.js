import React from 'react';
import { StyleSheet, Text, View } from 'react-native';
import { CheckCircle2 } from 'lucide-react-native';
import Button from './Button';
import Card from './Card';
import { colors, radius, spacing, typography } from '../theme/tokens';

export default function SuccessState({
  title,
  description,
  actionLabel,
  onAction,
  icon,
}) {
  return (
    <Card elevated={false} variant="flat" style={styles.wrap} padding={spacing['3xl']}>
      <View style={styles.iconWrap}>
        {icon || <CheckCircle2 size={32} color={colors.success} strokeWidth={2} />}
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
    width: 64,
    height: 64,
    borderRadius: radius.lg,
    backgroundColor: colors.successLight,
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
