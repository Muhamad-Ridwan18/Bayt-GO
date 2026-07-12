import React from 'react';
import { View, Text, StyleSheet } from 'react-native';
import { SafeAreaView } from 'react-native-safe-area-context';
import { StatusBar } from 'expo-status-bar';
import { Lock } from 'lucide-react-native';
import { navigateRoot } from '../navigation/rootNavigation';
import Button from '../ui/Button';
import PressableScale from '../ui/PressableScale';
import { colors, layout, radius, shadows, spacing, typography } from '../theme/tokens';

export default function AuthGateScreen({ navigation, title, message }) {
  return (
    <SafeAreaView style={styles.safe} edges={['top', 'bottom']}>
      <StatusBar style="dark" />
      <View style={styles.content}>
        <View style={styles.iconWrap}>
          <Lock size={32} color={colors.gold} strokeWidth={2} />
        </View>
        <Text style={styles.title}>{title}</Text>
        <Text style={styles.message}>{message}</Text>
        <View style={styles.btnWrap}>
          <Button label="Masuk" onPress={() => navigateRoot(navigation, 'Login')} />
        </View>
        <PressableScale
          onPress={() => navigateRoot(navigation, 'Register', { role: 'customer' })}
          haptic="light"
        >
          <Text style={styles.link}>Belum punya akun? Daftar</Text>
        </PressableScale>
      </View>
    </SafeAreaView>
  );
}

const styles = StyleSheet.create({
  safe: { flex: 1, backgroundColor: colors.background },
  content: { flex: 1, alignItems: 'center', justifyContent: 'center', padding: layout.screenPadding },
  iconWrap: {
    width: 72,
    height: 72,
    borderRadius: radius.md,
    backgroundColor: colors.baytgo,
    alignItems: 'center',
    justifyContent: 'center',
    marginBottom: spacing.xl,
    ...shadows.md,
  },
  title: { ...typography.title, color: colors.baytgo, textAlign: 'center' },
  message: {
    marginTop: spacing.sm,
    ...typography.caption,
    lineHeight: 22,
    color: colors.textSecondary,
    textAlign: 'center',
    maxWidth: 300,
  },
  btnWrap: { marginTop: spacing['2xl'], width: '100%', maxWidth: 280 },
  link: {
    marginTop: spacing.lg,
    ...typography.caption,
    fontFamily: 'PlusJakartaSans_700Bold',
    color: colors.baytgo,
  },
});
