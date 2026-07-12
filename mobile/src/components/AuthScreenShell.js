import React from 'react';
import {
  KeyboardAvoidingView,
  Platform,
  ScrollView,
  StyleSheet,
  Text,
  View,
} from 'react-native';
import { SafeAreaView } from 'react-native-safe-area-context';
import { StatusBar } from 'expo-status-bar';
import { ChevronLeft } from 'lucide-react-native';
import AppLogo from './AppLogo';
import { useBrand } from '../context/BrandContext';
import PressableScale from '../ui/PressableScale';
import { colors, layout, radius, spacing, typography } from '../theme/tokens';

export default function AuthScreenShell({ title, subtitle, onBack, children }) {
  const { logoUrl, appName } = useBrand();

  return (
    <SafeAreaView style={styles.safe} edges={['top', 'bottom']}>
      <StatusBar style="dark" />
      <KeyboardAvoidingView
        style={styles.flex}
        behavior={Platform.OS === 'ios' ? 'padding' : undefined}
      >
        <View style={styles.header}>
          <PressableScale onPress={onBack} haptic="light" style={styles.backBtn}>
            <ChevronLeft size={22} color={colors.baytgo} strokeWidth={2.2} />
          </PressableScale>
          <AppLogo url={logoUrl} name={appName} size={40} />
          <View style={styles.headerSpacer} />
        </View>

        <ScrollView
          contentContainerStyle={styles.scroll}
          keyboardShouldPersistTaps="handled"
          showsVerticalScrollIndicator={false}
        >
          <Text style={styles.title}>{title}</Text>
          {subtitle ? <Text style={styles.subtitle}>{subtitle}</Text> : null}
          {children}
        </ScrollView>
      </KeyboardAvoidingView>
    </SafeAreaView>
  );
}

const styles = StyleSheet.create({
  safe: { flex: 1, backgroundColor: colors.background },
  flex: { flex: 1 },
  header: {
    flexDirection: 'row',
    alignItems: 'center',
    justifyContent: 'space-between',
    paddingHorizontal: layout.screenPadding,
    paddingTop: spacing.sm,
    paddingBottom: spacing.xs,
  },
  backBtn: {
    width: layout.minTouch,
    height: layout.minTouch,
    borderRadius: radius.sm,
    backgroundColor: colors.card,
    alignItems: 'center',
    justifyContent: 'center',
    borderWidth: 1,
    borderColor: colors.border,
  },
  headerSpacer: { width: layout.minTouch },
  scroll: {
    paddingHorizontal: layout.screenPadding,
    paddingBottom: spacing['3xl'],
  },
  title: {
    ...typography.hero,
    fontSize: 28,
    lineHeight: 34,
    color: colors.textPrimary,
    marginTop: spacing.lg,
    letterSpacing: -0.5,
  },
  subtitle: {
    ...typography.caption,
    color: colors.textSecondary,
    marginTop: spacing.sm,
    marginBottom: spacing['2xl'],
    lineHeight: 22,
  },
});
