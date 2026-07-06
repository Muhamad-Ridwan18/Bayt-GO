import React, { useState } from 'react';
import {
  View,
  Text,
  StyleSheet,
  TouchableOpacity,
  ActivityIndicator,
  Alert,
} from 'react-native';
import AuthInput from '../components/AuthInput';
import ScreenHeader from '../components/ScreenHeader';
import { deleteAccount } from '../api/profile';
import { useAuth } from '../context/AuthContext';
import { resetRoot } from '../navigation/rootNavigation';
import { colors } from '../theme/colors';

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
          icon="lock-closed-outline"
          value={password}
          onChangeText={setPassword}
          secureTextEntry
          placeholder="Password akun Anda"
        />

        <TouchableOpacity style={styles.deleteBtn} onPress={handleDelete} disabled={loading} activeOpacity={0.9}>
          {loading ? (
            <ActivityIndicator color={colors.white} />
          ) : (
            <Text style={styles.deleteText}>Hapus akun permanen</Text>
          )}
        </TouchableOpacity>
      </View>
    </View>
  );
}

const styles = StyleSheet.create({
  container: { flex: 1, backgroundColor: colors.canvas },
  form: { padding: 20 },
  warning: {
    fontSize: 14,
    lineHeight: 21,
    color: colors.slate600,
    fontWeight: '600',
    marginBottom: 16,
  },
  error: {
    backgroundColor: '#FEF2F2',
    color: '#B91C1C',
    padding: 12,
    borderRadius: 12,
    fontSize: 13,
    fontWeight: '600',
    marginBottom: 16,
  },
  deleteBtn: {
    marginTop: 8,
    borderRadius: 16,
    backgroundColor: '#B91C1C',
    paddingVertical: 16,
    alignItems: 'center',
  },
  deleteText: { color: colors.white, fontSize: 16, fontWeight: '800' },
});
