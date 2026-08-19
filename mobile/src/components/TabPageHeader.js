import React from 'react';
import { StyleSheet, Text, View } from 'react-native';
import { LinearGradient } from 'expo-linear-gradient';
import { SafeAreaView } from 'react-native-safe-area-context';
import { colors, gradients, spacing, typography } from '../theme/tokens';

export default function TabPageHeader({ title, subtitle, right, variant = 'light' }) {
  const branded = variant === 'brand';
  const inner = (
    <View style={styles.row}>
      <View style={styles.copy}>
        <Text style={[styles.title, branded && styles.titleBrand]}>{title}</Text>
        {subtitle ? (
          <Text style={[styles.subtitle, branded && styles.subtitleBrand]}>{subtitle}</Text>
        ) : null}
      </View>
      {right ? <View style={styles.right}>{right}</View> : null}
    </View>
  );

  if (!branded) {
    return (
      <SafeAreaView edges={['top']} style={styles.safe}>
        {inner}
      </SafeAreaView>
    );
  }

  return (
    <LinearGradient colors={gradients.primary} start={{ x: 0, y: 0 }} end={{ x: 1, y: 1 }}>
      <SafeAreaView edges={['top']} style={styles.safeBrand}>
        {inner}
      </SafeAreaView>
    </LinearGradient>
  );
}

const styles = StyleSheet.create({
  safe: {
    backgroundColor: colors.background,
  },
  safeBrand: {
    backgroundColor: 'transparent',
  },
  row: {
    flexDirection: 'row',
    alignItems: 'flex-end',
    justifyContent: 'space-between',
    paddingHorizontal: spacing['2xl'],
    paddingTop: spacing.sm,
    paddingBottom: spacing.lg,
    gap: spacing.lg,
  },
  copy: { flex: 1 },
  right: { marginBottom: 2 },
  title: {
    ...typography.title,
    color: colors.textPrimary,
    letterSpacing: -0.3,
  },
  titleBrand: {
    color: colors.white,
  },
  subtitle: {
    ...typography.caption,
    color: colors.textSecondary,
    marginTop: spacing.xs,
  },
  subtitleBrand: {
    color: 'rgba(232, 220, 184, 0.92)',
  },
});
