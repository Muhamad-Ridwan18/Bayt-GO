import React, { useState } from 'react';
import { View, Text, StyleSheet, ScrollView } from 'react-native';
import { Lock, KeyRound } from 'lucide-react-native';
import AuthInput from '../components/AuthInput';
import ScreenHeader from '../components/ScreenHeader';
import Button from '../ui/Button';
import { updatePassword } from '../api/profile';
import { useAuth } from '../context/AuthContext';
import { notifySuccessThen } from '../utils/feedback';
import { useLocale } from '../utils/locale';
import { colors, layout, radius, spacing, typography } from '../theme/tokens';

export default function ChangePasswordScreen({ navigation }) {
  const locale = useLocale(); const isEn = locale === 'en';
  const { token } = useAuth();
  const [currentPassword, setCurrentPassword] = useState('');
  const [password, setPassword] = useState('');
  const [passwordConfirmation, setPasswordConfirmation] = useState('');
  const [loading, setLoading] = useState(false);
  const [error, setError] = useState('');

  const handleSave = async () => {
    if (!currentPassword || !password || !passwordConfirmation) {
      setError(isEn ? 'All fields are required.' : 'Semua field wajib diisi.');
      return;
    }
    if (password !== passwordConfirmation) {
      setError(isEn ? 'Password confirmation does not match.' : 'Konfirmasi password tidak cocok.');
      return;
    }

    setLoading(true);
    setError('');
    try {
      await updatePassword(token, { currentPassword, password, passwordConfirmation });
      notifySuccessThen(navigation, isEn ? 'Password updated successfully.' : 'Password berhasil diperbarui.', () => navigation.goBack());
    } catch (err) {
      setError(err.message || (isEn ? 'Failed to update password' : 'Gagal memperbarui password'));
    } finally {
      setLoading(false);
    }
  };

  return (
    <View style={styles.container}>
      <ScreenHeader title={isEn ? 'Change Password' : 'Ganti Password'} onBack={() => navigation.goBack()} />

      <ScrollView contentContainerStyle={styles.scroll} keyboardShouldPersistTaps="handled">
        {error ? <Text style={styles.error}>{error}</Text> : null}

        <AuthInput
          label={isEn ? 'Current password' : 'Password saat ini'}
          icon={Lock}
          secureTextEntry
          value={currentPassword}
          onChangeText={setCurrentPassword}
          placeholder={isEn ? 'Old password' : 'Password lama'}
        />
        <AuthInput
          label={isEn ? 'New password' : 'Password baru'}
          icon={KeyRound}
          secureTextEntry
          value={password}
          onChangeText={setPassword}
          placeholder={isEn ? 'Minimum 8 characters' : 'Minimal 8 karakter'}
        />
        <AuthInput
          label={isEn ? 'Confirm new password' : 'Konfirmasi password baru'}
          icon={KeyRound}
          secureTextEntry
          value={passwordConfirmation}
          onChangeText={setPasswordConfirmation}
          placeholder={isEn ? 'Repeat new password' : 'Ulangi password baru'}
        />

        <Button label={isEn ? 'Save Password' : 'Simpan Password'} onPress={handleSave} loading={loading} />
      </ScrollView>
    </View>
  );
}

const styles = StyleSheet.create({
  container: { flex: 1, backgroundColor: colors.background },
  scroll: { padding: layout.screenPadding, paddingBottom: spacing['3xl'] },
  error: {
    backgroundColor: colors.errorLight,
    color: colors.error,
    padding: spacing.md,
    borderRadius: radius.sm,
    marginBottom: spacing.lg,
    ...typography.caption,
    fontFamily: 'PlusJakartaSans_600SemiBold',
  },
});
