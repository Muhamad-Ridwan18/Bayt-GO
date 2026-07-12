import React from 'react';
import { StyleSheet, View } from 'react-native';
import { colors, radius, shadows, spacing } from '../theme/tokens';

export default function Card({
  children,
  style,
  padding = spacing.xl,
  elevated = true,
  variant = 'default',
}) {
  return (
    <View
      style={[
        styles.base,
        elevated && shadows.md,
        variant === 'flat' && styles.flat,
        variant === 'glass' && styles.glass,
        { padding },
        style,
      ]}
    >
      {children}
    </View>
  );
}

const styles = StyleSheet.create({
  base: {
    backgroundColor: colors.card,
    borderRadius: radius.md,
    borderWidth: 1,
    borderColor: 'rgba(226, 232, 240, 0.7)',
  },
  flat: {
    ...shadows.sm,
    shadowOpacity: 0.02,
    elevation: 1,
  },
  glass: {
    backgroundColor: 'rgba(255,255,255,0.82)',
    borderColor: 'rgba(255,255,255,0.9)',
  },
});
