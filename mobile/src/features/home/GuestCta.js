import React from 'react';
import { StyleSheet, Text, View } from 'react-native';
import { Button, Card, PressableScale } from '../../ui';
import { colors, layout, radius, spacing, typography } from '../../theme/tokens';
import { useLocale } from '../../utils/locale';

export default function GuestCta({ onRegister, onLogin }) {
  const locale = useLocale();
  const isEn = locale === 'en';
  return (
    <Card style={styles.wrap} padding={spacing.xl} elevated={false}>
      <Text style={styles.title}>{isEn ? "Don't have an account?" : 'Belum punya akun?'}</Text>
      <Text style={styles.sub}>{isEn ? 'Register for free and start booking muthowif' : 'Daftar gratis dan mulai booking muthowif'}</Text>
      <View style={styles.btn}>
        <Button label={isEn ? 'Register Now' : 'Daftar Sekarang'} onPress={onRegister} />
      </View>
      <PressableScale onPress={onLogin} haptic="light">
        <Text style={styles.loginLink}>
          {isEn ? 'Already have an account? ' : 'Sudah punya akun? '}<Text style={styles.loginBold}>{isEn ? 'Sign in' : 'Masuk'}</Text>
        </Text>
      </PressableScale>
    </Card>
  );
}

const styles = StyleSheet.create({
  wrap: {
    marginHorizontal: layout.screenPadding,
    marginTop: spacing.xl,
    alignItems: 'center',
    borderRadius: radius.md - 2,
  },
  title: {
    ...typography.subtitle,
    fontSize: 17,
    color: colors.baytgo,
    fontFamily: 'PlusJakartaSans_800ExtraBold',
    fontWeight: '800',
  },
  sub: {
    marginTop: spacing.sm - 2,
    ...typography.caption,
    color: colors.textSecondary,
    textAlign: 'center',
  },
  btn: { width: '100%', marginTop: spacing.lg },
  loginLink: {
    marginTop: spacing.md + 2,
    ...typography.caption,
    color: colors.textSecondary,
  },
  loginBold: { color: colors.baytgo, fontFamily: 'PlusJakartaSans_800ExtraBold', fontWeight: '800' },
});
