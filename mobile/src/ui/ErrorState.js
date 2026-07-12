import React from 'react';
import { StyleSheet, Text, View } from 'react-native';
import { AlertTriangle } from 'lucide-react-native';
import Button from './Button';
import Card from './Card';
import { colors, radius, spacing, typography } from '../theme/tokens';

export default function ErrorState({
  title = 'Terjadi kesalahan',
  description,
  onRetry,
  retryLabel = 'Coba lagi',
}) {
  return (
    <Card elevated={false} variant="flat" style={styles.wrap} padding={spacing['3xl']}>
      <View style={styles.iconWrap}>
        <AlertTriangle size={28} color={colors.error} strokeWidth={2} />
      </View>
      <Text style={styles.title}>{title}</Text>
      {description ? <Text style={styles.description}>{description}</Text> : null}
      {onRetry ? (
        <View style={styles.action}>
          <Button label={retryLabel} onPress={onRetry} size="sm" fullWidth={false} variant="secondary" />
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
    borderColor: '#FECACA',
    backgroundColor: colors.errorLight,
  },
  iconWrap: {
    width: 64,
    height: 64,
    borderRadius: radius.lg,
    backgroundColor: colors.white,
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
    lineHeight: 22,
    maxWidth: 280,
  },
  action: { marginTop: spacing['2xl'] },
});
