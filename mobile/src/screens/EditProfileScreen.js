import React, { useEffect, useState } from 'react';
import { View, Text, StyleSheet, ScrollView, ActivityIndicator } from 'react-native';
import AuthInput from '../components/AuthInput';
import ScreenHeader from '../components/ScreenHeader';
import { fetchProfile, sendVerificationEmail, updateProfile } from '../api/profile';
import { useAuth } from '../context/AuthContext';
import { Button, Card, PressableScale } from '../ui';
import { notifyError, notifySuccess, notifySuccessThen } from '../utils/feedback';
import { colors, layout, radius, spacing, typography } from '../theme/tokens';

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
        await updateLocalUser({ name: data.user.name, email: data.user.email });
        setEmailVerifiedAt(data.user.email_verified_at || null);
      }
      notifySuccessThen(navigation, 'Profil berhasil diperbarui.', () => navigation.goBack());
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
      notifySuccess(data.message || 'Link verifikasi telah dikirim.');
    } catch (err) {
      notifyError(err.message || 'Tidak dapat mengirim verifikasi');
    } finally {
      setSendingVerification(false);
    }
  };

  const isEmailUnverified = !emailVerifiedAt;

  return (
    <View style={styles.container}>
      <ScreenHeader title="Edit Profil" onBack={() => navigation.goBack()} />

      <ScrollView contentContainerStyle={styles.form} keyboardShouldPersistTaps="handled">
        {error ? (
          <Card style={styles.errorCard} padding={spacing.md} elevated={false}>
            <Text style={styles.errorText}>{error}</Text>
          </Card>
        ) : null}

        <AuthInput label="Nama" icon="person-outline" value={name} onChangeText={setName} placeholder="Nama lengkap" />
        <AuthInput label="Email" icon="mail-outline" value={email} onChangeText={setEmail} placeholder="nama@email.com" keyboardType="email-address" autoCapitalize="none" />

        {isEmailUnverified ? (
          <Card style={styles.verifyBox} padding={spacing.md} elevated={false}>
            <Text style={styles.verifyText}>Email belum terverifikasi.</Text>
            <PressableScale onPress={handleResendVerification} disabled={sendingVerification} haptic="light">
              {sendingVerification ? (
                <ActivityIndicator color={colors.baytgo} size="small" />
              ) : (
                <Text style={styles.verifyLink}>Kirim ulang verifikasi</Text>
              )}
            </PressableScale>
          </Card>
        ) : null}

        <AuthInput label="Nomor WhatsApp" icon="call-outline" value={phone} onChangeText={setPhone} placeholder="08xxxxxxxxxx" keyboardType="phone-pad" />

        <Button label="Simpan" onPress={handleSave} loading={loading} style={styles.saveBtn} />

        <Card style={styles.dangerSection} padding={spacing.lg} elevated={false}>
          <Text style={styles.dangerTitle}>Zona berbahaya</Text>
          <Text style={styles.dangerHint}>Hapus akun secara permanen beserta data terkait.</Text>
          <PressableScale onPress={() => navigation.navigate('DeleteAccount')} haptic="medium">
            <View style={styles.dangerBtn}>
              <Text style={styles.dangerBtnText}>Hapus akun</Text>
            </View>
          </PressableScale>
        </Card>
      </ScrollView>
    </View>
  );
}

const styles = StyleSheet.create({
  container: { flex: 1, backgroundColor: colors.background },
  form: { padding: layout.screenPadding, paddingBottom: spacing['3xl'] },
  errorCard: { backgroundColor: colors.errorLight, borderColor: '#FECACA', marginBottom: spacing.lg },
  errorText: { ...typography.caption, color: colors.error, fontWeight: '600' },
  verifyBox: { marginTop: -spacing.sm, marginBottom: spacing.md, backgroundColor: colors.warningLight, borderColor: '#FDE68A' },
  verifyText: { ...typography.caption, color: '#92400E', fontWeight: '600' },
  verifyLink: { marginTop: spacing.sm, ...typography.caption, color: colors.baytgo, fontFamily: 'PlusJakartaSans_800ExtraBold' },
  saveBtn: { marginTop: spacing.sm },
  dangerSection: { marginTop: spacing['3xl'], borderColor: '#FECACA', backgroundColor: '#FFFBFB' },
  dangerTitle: { ...typography.caption, fontFamily: 'PlusJakartaSans_800ExtraBold', color: '#991B1B' },
  dangerHint: { marginTop: spacing.sm, ...typography.caption, lineHeight: 20, color: colors.textSecondary, fontWeight: '500' },
  dangerBtn: {
    marginTop: spacing.md,
    paddingVertical: spacing.md,
    borderRadius: radius.sm,
    borderWidth: 1,
    borderColor: '#FCA5A5',
    alignItems: 'center',
    backgroundColor: colors.card,
  },
  dangerBtnText: { ...typography.caption, fontFamily: 'PlusJakartaSans_800ExtraBold', color: colors.error },
});
