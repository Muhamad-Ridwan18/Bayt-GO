import React from 'react';
import { View, Text, StyleSheet } from 'react-native';
import { Clock } from 'lucide-react-native';
import AuthScreenShell from '../components/AuthScreenShell';
import Button from '../ui/Button';
import Card from '../ui/Card';
import { colors, spacing, typography } from '../theme/tokens';
import { useLocale } from '../utils/locale';

export default function CompanyRegistrationPendingScreen({ navigation, route }) {
  const locale = useLocale(); const isEn = locale === 'en';
  const message =
    route.params?.message ||
    (isEn ? 'Your company registration is being reviewed by admin. You will be able to sign in once your account is approved.' : 'Pendaftaran perusahaan Anda sedang ditinjau admin. Anda akan dapat masuk setelah akun disetujui.');

  return (
    <AuthScreenShell
      title={isEn ? 'Awaiting approval' : 'Menunggu persetujuan'}
      subtitle={isEn ? 'Your company account is being verified.' : 'Akun perusahaan Anda dalam proses verifikasi.'}
      onBack={() => navigation.replace('Login')}
    >
      <Card style={styles.card} padding={spacing.xl} elevated={false} variant="flat">
        <View style={styles.iconWrap}>
          <Clock size={36} color={colors.baytgo} strokeWidth={2} />
        </View>
        <Text style={styles.message}>{message}</Text>
        <Text style={styles.hint}>
          {isEn ? 'The admin team will verify your company data. Once approved, use the registered email and password to sign in.' : 'Tim admin akan memverifikasi data perusahaan Anda. Setelah disetujui, gunakan email dan password yang didaftarkan untuk masuk.'}
        </Text>
      </Card>

      <Button label={isEn ? 'Go to login' : 'Ke halaman masuk'} onPress={() => navigation.replace('Login')} />
    </AuthScreenShell>
  );
}

const styles = StyleSheet.create({
  card: { alignItems: 'center', marginBottom: spacing.xl },
  iconWrap: {
    width: 72,
    height: 72,
    borderRadius: 36,
    backgroundColor: colors.primaryLight,
    alignItems: 'center',
    justifyContent: 'center',
    marginBottom: spacing.lg,
  },
  message: {
    ...typography.caption,
    lineHeight: 22,
    fontFamily: 'PlusJakartaSans_700Bold',
    color: colors.textPrimary,
    textAlign: 'center',
  },
  hint: {
    marginTop: spacing.md,
    ...typography.caption,
    lineHeight: 20,
    color: colors.textSecondary,
    textAlign: 'center',
  },
});
