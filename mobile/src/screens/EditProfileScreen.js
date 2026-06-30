import React, { useEffect, useState } from 'react';
import {
  View,
  Text,
  StyleSheet,
  TouchableOpacity,
  ActivityIndicator,
  Alert,
} from 'react-native';
import { LinearGradient } from 'expo-linear-gradient';
import AuthInput from '../components/AuthInput';
import ScreenHeader from '../components/ScreenHeader';
import { updateProfile } from '../api/profile';
import { useAuth } from '../context/AuthContext';
import { colors } from '../theme/colors';

export default function EditProfileScreen({ navigation, route }) {
  const { token, user, updateLocalUser } = useAuth();
  const initial = route.params?.profile?.user || user || {};

  const [name, setName] = useState(initial.name || '');
  const [email, setEmail] = useState(initial.email || '');
  const [loading, setLoading] = useState(false);
  const [error, setError] = useState('');

  useEffect(() => {
    setName(initial.name || '');
    setEmail(initial.email || '');
  }, [initial.name, initial.email]);

  const handleSave = async () => {
    if (!name.trim() || !email.trim()) {
      setError('Nama dan email wajib diisi.');
      return;
    }

    setLoading(true);
    setError('');
    try {
      const data = await updateProfile(token, { name: name.trim(), email: email.trim() });
      if (data.user) {
        await updateLocalUser({ name: data.user.name, email: data.user.email });
      }
      Alert.alert('Berhasil', 'Profil berhasil diperbarui.', [
        { text: 'OK', onPress: () => navigation.goBack() },
      ]);
    } catch (err) {
      setError(err.message || 'Gagal menyimpan profil');
    } finally {
      setLoading(false);
    }
  };

  return (
    <View style={styles.container}>
      <ScreenHeader title="Edit Profil" onBack={() => navigation.goBack()} />

      <View style={styles.form}>
        {error ? <Text style={styles.error}>{error}</Text> : null}

        <AuthInput
          label="Nama"
          icon="person-outline"
          value={name}
          onChangeText={setName}
          placeholder="Nama lengkap"
        />
        <AuthInput
          label="Email"
          icon="mail-outline"
          value={email}
          onChangeText={setEmail}
          placeholder="nama@email.com"
          keyboardType="email-address"
          autoCapitalize="none"
        />

        <TouchableOpacity style={styles.saveBtn} onPress={handleSave} disabled={loading} activeOpacity={0.9}>
          <LinearGradient colors={[colors.baytgo, colors.baytgoDark]} style={styles.saveGradient}>
            {loading ? (
              <ActivityIndicator color={colors.white} />
            ) : (
              <Text style={styles.saveText}>Simpan</Text>
            )}
          </LinearGradient>
        </TouchableOpacity>
      </View>
    </View>
  );
}

const styles = StyleSheet.create({
  container: { flex: 1, backgroundColor: colors.canvas },
  form: { padding: 20 },
  error: {
    backgroundColor: '#FEF2F2',
    color: '#B91C1C',
    padding: 12,
    borderRadius: 12,
    fontSize: 13,
    fontWeight: '600',
    marginBottom: 12,
  },
  saveBtn: { marginTop: 16, borderRadius: 16, overflow: 'hidden' },
  saveGradient: { paddingVertical: 16, alignItems: 'center' },
  saveText: { color: colors.white, fontSize: 15, fontWeight: '800' },
});
