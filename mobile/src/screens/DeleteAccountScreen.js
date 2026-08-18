import React, { useState } from 'react';
import { View, Text, StyleSheet, Alert } from 'react-native';
import { Lock } from 'lucide-react-native';
import AuthInput from '../components/AuthInput';
import ScreenHeader from '../components/ScreenHeader';
import Button from '../ui/Button';
import { deleteAccount } from '../api/profile';
import { useAuth } from '../context/AuthContext';
import { resetRoot } from '../navigation/rootNavigation';
import { useLocale } from '../utils/locale';
import { colors, layout, radius, spacing, typography } from '../theme/tokens';

export default function DeleteAccountScreen({ navigation }) {
  const locale = useLocale(); const isEn = locale === 'en';
  const { token, logout } = useAuth();
  const [password, setPassword] = useState('');
  const [loading, setLoading] = useState(false);
  const [error, setError] = useState('');

  const handleDelete = () => {
    if (!password) {
      setError(isEn ? 'Enter your password to confirm.' : 'Masukkan password untuk konfirmasi.');
      return;
    }

    Alert.alert(
      isEn ? 'Delete account?' : 'Hapus akun?',
      isEn ? 'This action is permanent. All your account data will be deleted.' : 'Tindakan ini permanen. Semua data akun Anda akan dihapus.',
      [
        { text: isEn ? 'Cancel' : 'Batal', style: 'cancel' },
        {
          text: isEn ? 'Delete' : 'Hapus',
          style: 'destructive',
          onPress: async () => {
            setLoading(true);
            setError('');
            try {
              await deleteAccount(token, password);
              await logout();
              resetRoot(navigation, [{ name: 'Login' }]);
            } catch (err) {
              setError(err.message || (isEn ? 'Failed to delete account' : 'Gagal menghapus akun'));
            } finally {
              setLoading(false);
            }
          },
        },
      ],
    );
  };

  return (
    <View style={styles.container}>
      <ScreenHeader title={isEn ? 'Delete Account' : 'Hapus Akun'} onBack={() => navigation.goBack()} />

      <View style={styles.form}>
        <Text style={styles.warning}>
          {isEn ? 'Once deleted, your account and related data cannot be recovered. Enter your password to confirm.' : 'Setelah dihapus, akun dan data terkait tidak dapat dipulihkan. Masukkan password untuk mengonfirmasi.'}
        </Text>

        {error ? <Text style={styles.error}>{error}</Text> : null}

        <AuthInput
          label="Password"
          icon={Lock}
          value={password}
          onChangeText={setPassword}
          secureTextEntry
          placeholder={isEn ? 'Your account password' : 'Password akun Anda'}
        />

        <Button
          label={isEn ? 'Delete account permanently' : 'Hapus akun permanen'}
          onPress={handleDelete}
          loading={loading}
          variant="danger"
        />
      </View>
    </View>
  );
}

const styles = StyleSheet.create({
  container: { flex: 1, backgroundColor: colors.background },
  form: { padding: layout.screenPadding },
  warning: {
    ...typography.caption,
    lineHeight: 22,
    color: colors.textSecondary,
    marginBottom: spacing.lg,
  },
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
