import React from 'react';
import { ActivityIndicator, StyleSheet, Text, View } from 'react-native';
import { LinearGradient } from 'expo-linear-gradient';
import PressableScale from './PressableScale';
import { colors, gradients, radius, spacing, typography } from '../theme/tokens';

export default function Button({
  label,
  onPress,
  loading,
  disabled,
  variant = 'primary',
  size = 'md',
  icon,
  fullWidth = true,
  haptic = 'medium',
}) {
  const isDisabled = disabled || loading;

  const content = (
    <View style={[styles.inner, size === 'sm' && styles.innerSm, !fullWidth && styles.innerAuto]}>
      {loading ? (
        <ActivityIndicator color={variant === 'primary' ? colors.white : colors.baytgo} />
      ) : (
        <>
          {icon ? <View style={styles.icon}>{icon}</View> : null}
          <Text
            style={[
              styles.label,
              size === 'sm' && styles.labelSm,
              variant === 'primary' && styles.labelPrimary,
              variant === 'secondary' && styles.labelSecondary,
              variant === 'ghost' && styles.labelGhost,
              variant === 'danger' && styles.labelDanger,
            ]}
          >
            {label}
          </Text>
        </>
      )}
    </View>
  );

  if (variant === 'primary') {
    return (
      <PressableScale
        onPress={onPress}
        disabled={isDisabled}
        haptic={haptic}
        style={[fullWidth && styles.full, isDisabled && styles.disabled]}
      >
        <LinearGradient
          colors={gradients.primarySoft}
          start={{ x: 0, y: 0 }}
          end={{ x: 1, y: 1 }}
          style={[styles.gradient, size === 'sm' && styles.gradientSm]}
        >
          {content}
        </LinearGradient>
      </PressableScale>
    );
  }

  return (
    <PressableScale
      onPress={onPress}
      disabled={isDisabled}
      haptic={haptic}
      style={[
        styles.base,
        fullWidth && styles.full,
        variant === 'secondary' && styles.secondary,
        variant === 'ghost' && styles.ghost,
        variant === 'danger' && styles.danger,
        size === 'sm' && styles.baseSm,
        isDisabled && styles.disabled,
      ]}
    >
      {content}
    </PressableScale>
  );
}

const styles = StyleSheet.create({
  full: { width: '100%' },
  base: {
    borderRadius: radius.sm,
    backgroundColor: colors.surface,
    borderWidth: 1,
    borderColor: colors.border,
  },
  baseSm: { borderRadius: radius.sm },
  secondary: { backgroundColor: colors.white },
  ghost: { backgroundColor: 'transparent', borderColor: 'transparent' },
  danger: { backgroundColor: colors.errorLight, borderColor: '#FECACA' },
  disabled: { opacity: 0.55 },
  gradient: {
    borderRadius: radius.sm,
    paddingVertical: spacing.lg,
    paddingHorizontal: spacing['2xl'],
  },
  gradientSm: {
    paddingVertical: spacing.md,
    paddingHorizontal: spacing.lg,
  },
  inner: {
    flexDirection: 'row',
    alignItems: 'center',
    justifyContent: 'center',
    gap: spacing.sm,
  },
  innerSm: { minHeight: 20 },
  innerAuto: { alignSelf: 'center' },
  icon: { marginRight: -2 },
  label: {
    ...typography.caption,
    fontFamily: 'PlusJakartaSans_700Bold',
    fontWeight: '700',
  },
  labelSm: { ...typography.small },
  labelPrimary: { color: colors.white },
  labelSecondary: { color: colors.baytgo },
  labelGhost: { color: colors.textSecondary },
  labelDanger: { color: colors.error },
});
