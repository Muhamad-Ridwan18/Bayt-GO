import React, { useEffect } from 'react';
import { StyleSheet, View } from 'react-native';
import Animated, {
  useAnimatedStyle,
  useSharedValue,
  withRepeat,
  withTiming,
} from 'react-native-reanimated';
import { colors, radius, spacing } from '../theme/tokens';

function Bone({ width, height, style }) {
  const opacity = useSharedValue(0.45);

  useEffect(() => {
    opacity.value = withRepeat(withTiming(1, { duration: 900 }), -1, true);
  }, [opacity]);

  const animatedStyle = useAnimatedStyle(() => ({
    opacity: opacity.value,
  }));

  return (
    <Animated.View
      style={[
        styles.bone,
        { width, height: height || 14, borderRadius: radius.sm },
        animatedStyle,
        style,
      ]}
    />
  );
}

export function SkeletonLine({ width = '100%', height = 14, style }) {
  return <Bone width={width} height={height} style={style} />;
}

export function SkeletonCircle({ size = 52, style }) {
  return <Bone width={size} height={size} style={[{ borderRadius: size / 2 }, style]} />;
}

export function SkeletonCard({ lines = 3, style }) {
  return (
    <View style={[styles.card, style]}>
      <View style={styles.row}>
        <SkeletonCircle size={52} />
        <View style={styles.col}>
          <SkeletonLine width="55%" height={16} />
          <SkeletonLine width="80%" style={{ marginTop: spacing.sm }} />
        </View>
      </View>
      {Array.from({ length: lines }).map((_, i) => (
        <SkeletonLine key={i} width={i === lines - 1 ? '65%' : '100%'} style={{ marginTop: spacing.md }} />
      ))}
    </View>
  );
}

export function SkeletonList({ count = 4, style }) {
  return (
    <View style={style}>
      {Array.from({ length: count }).map((_, i) => (
        <SkeletonCard key={i} style={{ marginBottom: spacing.lg }} />
      ))}
    </View>
  );
}

const styles = StyleSheet.create({
  bone: {
    backgroundColor: colors.surface,
  },
  card: {
    backgroundColor: colors.card,
    borderRadius: radius.md,
    padding: spacing.xl,
    borderWidth: 1,
    borderColor: colors.border,
  },
  row: { flexDirection: 'row', gap: spacing.lg },
  col: { flex: 1 },
});
