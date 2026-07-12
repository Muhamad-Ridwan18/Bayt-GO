import React, { useState } from 'react';
import { View, Text, StyleSheet, Alert } from 'react-native';
import { Lock } from 'lucide-react-native';
import AuthInput from '../components/AuthInput';
import ScreenHeader from '../components/ScreenHeader';
import Button from '../ui/Button';
import { deleteAccount } from '../api/profile';
import { useAuth } from '../context/AuthContext';
import { resetRoot } from '../navigation/rootNavigation';
import { colors, layout, radius, spacing, typography } from '../theme/tokens';

export default function DeleteAccountScreen({ navigation }) {
  const { token, logout } = useAuth();
  const [password, setPassword] = useState('');
  const [loading, setLoading] = useState(false);
  const [error, setError] = useState('');

  const handleDelete = () => {
    if (!password) {
      setError('Masukkan password untuk konfirmasi.');
      return;
    }

    Alert.alert(
      'Hapus akun?',
      'Tindakan ini permanen. Semua data akun Anda akan dihapus.',
      [
        { text: 'Batal', style: 'cancel' },
        {
          text: 'Hapus',
          style: 'destructive',
          onPress: async () => {
            setLoading(true);
            setError('');
            try {
              await deleteAccount(token, password);
              await logout();
              resetRoot(navigation, [{ name: 'Login' }]);
            } catch (err) {
              setError(err.message || 'Gagal menghapus akun');
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
      <ScreenHeader title="Hapus Akun" onBack={() => navigation.goBack()} />

      <View style={styles.form}>
        <Text style={styles.warning}>
          Setelah dihapus, akun dan data terkait tidak dapat dipulihkan. Masukkan password untuk mengonfirmasi.
        </Text>

        {error ? <Text style={styles.error}>{error}</Text> : null}

        <AuthInput
          label="Password"
          icon={Lock}
          value={password}
          onChangeText={setPassword}
          secureTextEntry
          placeholder="Password akun Anda"
        />

        <Button
          label="Hapus akun permanen"
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
