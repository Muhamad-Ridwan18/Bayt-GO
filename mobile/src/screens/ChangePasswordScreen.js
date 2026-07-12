import React, { useState } from 'react';
import { View, Text, StyleSheet, ScrollView } from 'react-native';
import { Lock, KeyRound } from 'lucide-react-native';
import AuthInput from '../components/AuthInput';
import ScreenHeader from '../components/ScreenHeader';
import Button from '../ui/Button';
import { updatePassword } from '../api/profile';
import { useAuth } from '../context/AuthContext';
import { notifySuccessThen } from '../utils/feedback';
import { colors, layout, radius, spacing, typography } from '../theme/tokens';

export default function ChangePasswordScreen({ navigation }) {
  const { token } = useAuth();
  const [currentPassword, setCurrentPassword] = useState('');
  const [password, setPassword] = useState('');
  const [passwordConfirmation, setPasswordConfirmation] = useState('');
  const [loading, setLoading] = useState(false);
  const [error, setError] = useState('');

  const handleSave = async () => {
    if (!currentPassword || !password || !passwordConfirmation) {
      setError('Semua field wajib diisi.');
      return;
    }
    if (password !== passwordConfirmation) {
      setError('Konfirmasi password tidak cocok.');
      return;
    }

    setLoading(true);
    setError('');
    try {
      await updatePassword(token, { currentPassword, password, passwordConfirmation });
      notifySuccessThen(navigation, 'Password berhasil diperbarui.', () => navigation.goBack());
    } catch (err) {
      setError(err.message || 'Gagal memperbarui password');
    } finally {
      setLoading(false);
    }
  };

  return (
    <View style={styles.container}>
      <ScreenHeader title="Ganti Password" onBack={() => navigation.goBack()} />

      <ScrollView contentContainerStyle={styles.scroll} keyboardShouldPersistTaps="handled">
        {error ? <Text style={styles.error}>{error}</Text> : null}

        <AuthInput
          label="Password saat ini"
          icon={Lock}
          secureTextEntry
          value={currentPassword}
          onChangeText={setCurrentPassword}
          placeholder="Password lama"
        />
        <AuthInput
          label="Password baru"
          icon={KeyRound}
          secureTextEntry
          value={password}
          onChangeText={setPassword}
          placeholder="Minimal 8 karakter"
        />
        <AuthInput
          label="Konfirmasi password baru"
          icon={KeyRound}
          secureTextEntry
          value={passwordConfirmation}
          onChangeText={setPasswordConfirmation}
          placeholder="Ulangi password baru"
        />

        <Button label="Simpan Password" onPress={handleSave} loading={loading} />
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
