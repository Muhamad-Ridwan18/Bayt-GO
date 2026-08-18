import React, { useState } from 'react';
import { View, Text, StyleSheet } from 'react-native';
import { Mail, Lock } from 'lucide-react-native';
import AuthScreenShell from '../components/AuthScreenShell';
import AuthInput from '../components/AuthInput';
import Button from '../ui/Button';
import PressableScale from '../ui/PressableScale';
import { useAuth } from '../context/AuthContext';
import { resetRoot } from '../navigation/rootNavigation';
import { colors, radius, spacing, typography } from '../theme/tokens';
import { useLocale } from '../utils/locale';

export default function LoginScreen({ navigation }) {
  const { login } = useAuth();
  const locale = useLocale();
  const isEn = locale === 'en';
  const [email, setEmail] = useState('');
  const [password, setPassword] = useState('');
  const [loading, setLoading] = useState(false);
  const [error, setError] = useState('');

  const handleLogin = async () => {
    if (!email.trim() || !password) {
      setError(isEn ? 'Email and password are required.' : 'Email dan password wajib diisi.');
      return;
    }

    setLoading(true);
    setError('');
    try {
      await login(email.trim(), password);
      resetRoot(navigation, [{ name: 'Main' }]);
    } catch (err) {
      setError(err.message || (isEn ? 'Login failed' : 'Gagal login'));
    } finally {
      setLoading(false);
    }
  };

  return (
    <AuthScreenShell
      title={isEn ? 'Sign in' : 'Masuk'}
      subtitle={isEn ? 'Welcome back. Sign in to manage your bookings and profile.' : 'Selamat datang kembali. Masuk untuk mengelola pemesanan dan profil Anda.'}
      onBack={() => navigation.goBack()}
    >
      {error ? <Text style={styles.bannerError}>{error}</Text> : null}

      <AuthInput
        label="Email"
        icon={Mail}
        value={email}
        onChangeText={setEmail}
        placeholder="nama@email.com"
        keyboardType="email-address"
        autoCapitalize="none"
        autoCorrect={false}
      />
      <AuthInput
        label="Password"
        icon={Lock}
        value={password}
        onChangeText={setPassword}
        placeholder={isEn ? 'Your password' : 'Password Anda'}
        secureTextEntry
      />

      <PressableScale
        onPress={() => navigation.navigate('ForgotPassword')}
        haptic="light"
        style={styles.forgotBtn}
      >
        <Text style={styles.forgotText}>{isEn ? 'Forgot password?' : 'Lupa password?'}</Text>
      </PressableScale>

      <Button label={isEn ? 'Sign in' : 'Masuk'} onPress={handleLogin} loading={loading} />

      <View style={styles.footerRow}>
        <Text style={styles.footerText}>{isEn ? "Don't have an account? " : 'Belum punya akun? '}</Text>
        <PressableScale onPress={() => navigation.replace('Register')} haptic="light">
          <Text style={styles.footerLink}>{isEn ? 'Register' : 'Daftar'}</Text>
        </PressableScale>
      </View>
    </AuthScreenShell>
  );
}

const styles = StyleSheet.create({
  bannerError: {
    backgroundColor: colors.errorLight,
    color: colors.error,
    padding: spacing.md,
    borderRadius: radius.sm,
    marginBottom: spacing.lg,
    ...typography.caption,
    fontFamily: 'PlusJakartaSans_600SemiBold',
  },
  forgotBtn: { alignSelf: 'flex-end', marginTop: spacing.sm, marginBottom: spacing.xs },
  forgotText: { ...typography.caption, fontFamily: 'PlusJakartaSans_700Bold', color: colors.baytgo },
  footerRow: { flexDirection: 'row', justifyContent: 'center', marginTop: spacing['2xl'] },
  footerText: { ...typography.caption, color: colors.textSecondary },
  footerLink: { ...typography.caption, fontFamily: 'PlusJakartaSans_700Bold', color: colors.baytgo },
});
