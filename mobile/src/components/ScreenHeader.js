import React from 'react';
import { StyleSheet, Text, View } from 'react-native';
import { SafeAreaView } from 'react-native-safe-area-context';
import { ChevronLeft } from 'lucide-react-native';
import PressableScale from '../ui/PressableScale';
import { colors, radius, shadows, spacing, typography } from '../theme/tokens';

export default function ScreenHeader({ title, subtitle, onBack, rightAction }) {
  return (
    <SafeAreaView edges={['top']} style={styles.safe}>
      <View style={styles.row}>
        <PressableScale onPress={onBack} haptic="light" style={styles.backBtn}>
          <ChevronLeft size={22} color={colors.baytgo} strokeWidth={2.2} />
        </PressableScale>
        <View style={styles.titleWrap}>
          <Text style={styles.title} numberOfLines={1}>{title}</Text>
          {subtitle ? <Text style={styles.subtitle} numberOfLines={1}>{subtitle}</Text> : null}
        </View>
        <View style={styles.right}>{rightAction ?? <View style={styles.placeholder} />}</View>
      </View>
    </SafeAreaView>
  );
}

const styles = StyleSheet.create({
  safe: { backgroundColor: colors.background },
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
  titleWrap: { flex: 1, paddingHorizontal: spacing.xs },
  title: {
    ...typography.subtitle,
    color: colors.textPrimary,
    letterSpacing: -0.2,
  },
  subtitle: {
    ...typography.small,
    color: colors.textSecondary,
    marginTop: 2,
    fontWeight: '500',
    fontFamily: 'PlusJakartaSans_500Medium',
  },
  right: { minWidth: 44, alignItems: 'flex-end' },
  placeholder: { width: 44 },
});
