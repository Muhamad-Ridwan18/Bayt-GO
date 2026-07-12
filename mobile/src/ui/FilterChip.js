import React from 'react';
import { StyleSheet, Text } from 'react-native';
import Animated, {
  useAnimatedStyle,
  withSpring,
} from 'react-native-reanimated';
import PressableScale from './PressableScale';
import { colors, motion, radius, spacing, typography } from '../theme/tokens';

export default function FilterChip({ label, icon: Icon, active, onPress }) {
  const pillStyle = useAnimatedStyle(() => ({
    transform: [{ scale: withSpring(active ? 1 : 1, motion.spring) }],
  }));

  return (
    <PressableScale onPress={onPress} haptic="light" scaleTo={0.96}>
      <Animated.View style={[styles.chip, active && styles.chipActive, pillStyle]}>
        {Icon ? <Icon size={16} color={active ? colors.white : colors.textSecondary} strokeWidth={2} /> : null}
        <Text style={[styles.label, active && styles.labelActive]}>{label}</Text>
      </Animated.View>
    </PressableScale>
  );
}

const styles = StyleSheet.create({
  chip: {
    flexDirection: 'row',
    alignItems: 'center',
    gap: spacing.sm,
    paddingHorizontal: spacing.lg,
    paddingVertical: spacing.md,
    borderRadius: radius.full,
    backgroundColor: colors.card,
    borderWidth: 1,
    borderColor: colors.border,
    minHeight: 44,
  },
  chipActive: {
    backgroundColor: colors.baytgo,
    borderColor: colors.baytgo,
  },
  label: {
    ...typography.small,
    color: colors.textSecondary,
    fontFamily: 'PlusJakartaSans_700Bold',
  },
  labelActive: {
    color: colors.white,
  },
});
