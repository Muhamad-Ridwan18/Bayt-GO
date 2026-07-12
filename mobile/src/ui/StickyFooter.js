import React from 'react';
import { StyleSheet, Text, View } from 'react-native';
import { LinearGradient } from 'expo-linear-gradient';
import { useSafeAreaInsets } from 'react-native-safe-area-context';
import { colors, spacing, typography } from '../theme/tokens';

export default function StickyFooter({ children, priceLabel, priceValue, priceSuffix }) {
  const insets = useSafeAreaInsets();

  return (
    <View style={[styles.wrap, { paddingBottom: Math.max(insets.bottom, spacing.md) }]}>
      <LinearGradient
        colors={['rgba(248,250,252,0)', 'rgba(248,250,252,0.92)', colors.background]}
        style={styles.fade}
        pointerEvents="none"
      />
      <View style={styles.inner}>
        {priceLabel || priceValue ? (
          <View style={styles.priceCol}>
            {priceLabel ? <Text style={styles.priceLabel}>{priceLabel}</Text> : null}
            {priceValue ? (
              <Text style={styles.priceValue}>
                {priceValue}
                {priceSuffix ? <Text style={styles.priceSuffix}>{priceSuffix}</Text> : null}
              </Text>
            ) : null}
          </View>
        ) : null}
        <View style={styles.actions}>{children}</View>
      </View>
    </View>
  );
}

const styles = StyleSheet.create({
  wrap: {
    position: 'absolute',
    left: 0,
    right: 0,
    bottom: 0,
    backgroundColor: colors.background,
    borderTopWidth: 1,
    borderTopColor: colors.border,
  },
  fade: {
    position: 'absolute',
    left: 0,
    right: 0,
    top: -28,
    height: 28,
  },
  inner: {
    flexDirection: 'row',
    alignItems: 'center',
    gap: spacing.lg,
    paddingHorizontal: spacing.xl,
    paddingTop: spacing.md,
  },
  priceCol: { flex: 1 },
  priceLabel: { ...typography.label, color: colors.textSecondary },
  priceValue: { ...typography.subtitle, color: colors.baytgo, marginTop: 2 },
  priceSuffix: { ...typography.caption, color: colors.textSecondary, fontWeight: '600' },
  actions: { flex: 1 },
});
