import React from 'react';
import { StyleSheet, Text, View } from 'react-native';
import { LinearGradient } from 'expo-linear-gradient';
import { SafeAreaView } from 'react-native-safe-area-context';
import { ChevronLeft } from 'lucide-react-native';
import PressableScale from '../ui/PressableScale';
import { colors, gradients, radius, shadows, spacing, typography } from '../theme/tokens';

export default function ScreenHeader({ title, subtitle, onBack, rightAction, variant = 'light' }) {
  const branded = variant === 'brand';
  const inner = (
    <View style={styles.row}>
      <PressableScale onPress={onBack} haptic="light" style={[styles.backBtn, branded && styles.backBtnBrand]}>
        <ChevronLeft size={22} color={branded ? colors.white : colors.baytgo} strokeWidth={2.2} />
      </PressableScale>
      <View style={styles.titleWrap}>
        <Text style={[styles.title, branded && styles.titleBrand]} numberOfLines={1}>{title}</Text>
        {subtitle ? (
          <Text style={[styles.subtitle, branded && styles.subtitleBrand]} numberOfLines={1}>{subtitle}</Text>
        ) : null}
      </View>
      <View style={styles.right}>{rightAction ?? <View style={styles.placeholder} />}</View>
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
  safe: { backgroundColor: colors.background },
  safeBrand: { backgroundColor: 'transparent' },
  row: {
    flexDirection: 'row',
    alignItems: 'center',
    paddingHorizontal: spacing.lg,
    paddingBottom: spacing.md,
    gap: spacing.sm,
  },
  backBtn: {
    width: 44,
    height: 44,
    borderRadius: radius.sm,
    backgroundColor: colors.card,
    alignItems: 'center',
    justifyContent: 'center',
    ...shadows.sm,
    borderWidth: 1,
    borderColor: 'rgba(226,232,240,0.8)',
  },
  backBtnBrand: {
    backgroundColor: 'rgba(255,255,255,0.12)',
    borderColor: 'rgba(255,255,255,0.18)',
    shadowOpacity: 0,
    elevation: 0,
  },
  titleWrap: { flex: 1, paddingHorizontal: spacing.xs },
  title: {
    ...typography.subtitle,
    color: colors.textPrimary,
    letterSpacing: -0.2,
  },
  titleBrand: { color: colors.white },
  subtitle: {
    ...typography.small,
    color: colors.textSecondary,
    marginTop: 2,
    fontWeight: '500',
    fontFamily: 'PlusJakartaSans_500Medium',
  },
  subtitleBrand: { color: 'rgba(232, 220, 184, 0.92)' },
  right: { minWidth: 44, alignItems: 'flex-end' },
  placeholder: { width: 44 },
});
