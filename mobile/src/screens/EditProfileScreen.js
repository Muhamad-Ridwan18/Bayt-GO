import React, { useEffect, useState } from 'react';
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
import { fetchProfile, sendVerificationEmail, updateProfile } from '../api/profile';
import { useAuth } from '../context/AuthContext';
import { colors } from '../theme/colors';

export default function EditProfileScreen({ navigation, route }) {
  const { token, user, updateLocalUser } = useAuth();
  const initial = route.params?.profile?.user || user || {};

  const [name, setName] = useState(initial.name || '');
  const [email, setEmail] = useState(initial.email || '');
  const [phone, setPhone] = useState(initial.phone || '');
  const [emailVerifiedAt, setEmailVerifiedAt] = useState(initial.email_verified_at || null);
  const [loading, setLoading] = useState(false);
  const [sendingVerification, setSendingVerification] = useState(false);
  const [error, setError] = useState('');

  useEffect(() => {
    (async () => {
      try {
        const data = await fetchProfile(token);
        if (data?.user) {
          setName(data.user.name || '');
          setEmail(data.user.email || '');
          setPhone(data.user.phone || '');
          setEmailVerifiedAt(data.user.email_verified_at || null);
        }
      } catch {
        // keep route params fallback
      }
    })();
  }, [token]);

  const handleSave = async () => {
    if (!name.trim() || !email.trim()) {
      setError('Nama dan email wajib diisi.');
      return;
    }

    setLoading(true);
    setError('');
    try {
      const data = await updateProfile(token, {
        name: name.trim(),
        email: email.trim(),
        phone: phone.trim(),
      });
      if (data.user) {
        await updateLocalUser({
          name: data.user.name,
          email: data.user.email,
        });
        setEmailVerifiedAt(data.user.email_verified_at || null);
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

  const handleResendVerification = async () => {
    setSendingVerification(true);
    try {
      const data = await sendVerificationEmail(token);
      Alert.alert('Berhasil', data.message || 'Link verifikasi telah dikirim.');
    } catch (err) {
      Alert.alert('Gagal', err.message || 'Tidak dapat mengirim verifikasi');
    } finally {
      setSendingVerification(false);
    }
  };

  const isEmailUnverified = !emailVerifiedAt;

  return (
    <View style={styles.container}>
      <ScreenHeader title="Edit Profil" onBack={() => navigation.goBack()} />

      <ScrollView contentContainerStyle={styles.form} keyboardShouldPersistTaps="handled">
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
        {isEmailUnverified ? (
          <View style={styles.verifyBox}>
            <Text style={styles.verifyText}>Email belum terverifikasi.</Text>
            <TouchableOpacity onPress={handleResendVerification} disabled={sendingVerification}>
              {sendingVerification ? (
                <ActivityIndicator color={colors.baytgo} size="small" />
              ) : (
                <Text style={styles.verifyLink}>Kirim ulang verifikasi</Text>
              )}
            </TouchableOpacity>
          </View>
        ) : null}
        <AuthInput
          label="Nomor WhatsApp"
          icon="call-outline"
          value={phone}
          onChangeText={setPhone}
          placeholder="08xxxxxxxxxx"
          keyboardType="phone-pad"
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

        <View style={styles.dangerSection}>
          <Text style={styles.dangerTitle}>Zona berbahaya</Text>
          <Text style={styles.dangerHint}>Hapus akun secara permanen beserta data terkait.</Text>
          <TouchableOpacity
            style={styles.dangerBtn}
            onPress={() => navigation.navigate('DeleteAccount')}
          >
            <Text style={styles.dangerBtnText}>Hapus akun</Text>
          </TouchableOpacity>
        </View>
      </ScrollView>
    </View>
  );
}

const styles = StyleSheet.create({
  container: { flex: 1, backgroundColor: colors.canvas },
  form: { padding: 20, paddingBottom: 32 },
  error: {
    backgroundColor: '#FEF2F2',
    color: '#B91C1C',
    padding: 12,
    borderRadius: 12,
    fontSize: 13,
    fontWeight: '600',
    marginBottom: 16,
  },
  verifyBox: {
    marginTop: -6,
    marginBottom: 14,
    padding: 12,
    borderRadius: 12,
    backgroundColor: '#FFFBEB',
    borderWidth: 1,
    borderColor: '#FDE68A',
  },
  verifyText: { fontSize: 13, color: '#92400E', fontWeight: '600' },
  verifyLink: { marginTop: 6, fontSize: 13, color: colors.baytgo, fontWeight: '800' },
  saveBtn: { borderRadius: 16, overflow: 'hidden', marginTop: 8 },
  saveGradient: { paddingVertical: 16, alignItems: 'center' },
  saveText: { color: colors.white, fontSize: 16, fontWeight: '800' },
  dangerSection: {
    marginTop: 32,
    padding: 16,
    borderRadius: 16,
    borderWidth: 1,
    borderColor: '#FECACA',
    backgroundColor: '#FFFBFB',
  },
  dangerTitle: { fontSize: 15, fontWeight: '900', color: '#991B1B' },
  dangerHint: { marginTop: 6, fontSize: 13, lineHeight: 20, color: colors.slate600, fontWeight: '500' },
  dangerBtn: {
    marginTop: 14,
    paddingVertical: 12,
    borderRadius: 12,
    borderWidth: 1,
    borderColor: '#FCA5A5',
    alignItems: 'center',
    backgroundColor: colors.white,
  },
  dangerBtnText: { fontSize: 14, fontWeight: '800', color: '#B91C1C' },
});
