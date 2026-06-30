import React, { useState } from 'react';
import {
  View,
  Text,
  StyleSheet,
  TouchableOpacity,
  ActivityIndicator,
  Alert,
  ScrollView,
} from 'react-native';
import { LinearGradient } from 'expo-linear-gradient';
import AuthInput from '../components/AuthInput';
import ScreenHeader from '../components/ScreenHeader';
import { updatePassword } from '../api/profile';
import { useAuth } from '../context/AuthContext';
import { colors } from '../theme/colors';

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
      Alert.alert('Berhasil', 'Password berhasil diperbarui.', [
        { text: 'OK', onPress: () => navigation.goBack() },
      ]);
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
          icon="lock-closed-outline"
          secureTextEntry
          value={currentPassword}
          onChangeText={setCurrentPassword}
          placeholder="Password lama"
        />
        <AuthInput
          label="Password baru"
          icon="key-outline"
          secureTextEntry
          value={password}
          onChangeText={setPassword}
          placeholder="Minimal 8 karakter"
        />
        <AuthInput
          label="Konfirmasi password baru"
          icon="key-outline"
          secureTextEntry
          value={passwordConfirmation}
          onChangeText={setPasswordConfirmation}
          placeholder="Ulangi password baru"
        />

        <TouchableOpacity style={styles.saveBtn} onPress={handleSave} disabled={loading} activeOpacity={0.9}>
          <LinearGradient colors={[colors.baytgo, colors.baytgoDark]} style={styles.saveGradient}>
            {loading ? (
              <ActivityIndicator color={colors.white} />
            ) : (
              <Text style={styles.saveText}>Simpan Password</Text>
            )}
          </LinearGradient>
        </TouchableOpacity>
      </ScrollView>
    </View>
  );
}

const styles = StyleSheet.create({
  container: { flex: 1, backgroundColor: colors.canvas },
  scroll: { padding: 16, paddingBottom: 32 },
  error: { marginBottom: 12, fontSize: 13, color: '#DC2626', fontWeight: '600' },
  saveBtn: { marginTop: 8, borderRadius: 16, overflow: 'hidden' },
  saveGradient: { paddingVertical: 16, alignItems: 'center' },
  saveText: { color: colors.white, fontSize: 15, fontWeight: '800' },
});
