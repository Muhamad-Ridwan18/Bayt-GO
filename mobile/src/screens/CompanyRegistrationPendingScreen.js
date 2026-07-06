import React from 'react';
import { View, Text, StyleSheet, TouchableOpacity } from 'react-native';
import { LinearGradient } from 'expo-linear-gradient';
import { Ionicons } from '@expo/vector-icons';
import AuthScreenShell from '../components/AuthScreenShell';
import { colors } from '../theme/colors';

export default function CompanyRegistrationPendingScreen({ navigation, route }) {
  const message =
    route.params?.message ||
    'Pendaftaran perusahaan Anda sedang ditinjau admin. Anda akan dapat masuk setelah akun disetujui.';

  return (
    <AuthScreenShell title="Menunggu persetujuan" subtitle="Akun perusahaan Anda dalam proses verifikasi.">
      <View style={styles.card}>
        <View style={styles.iconWrap}>
          <Ionicons name="time-outline" size={36} color={colors.baytgo} />
        </View>
        <Text style={styles.message}>{message}</Text>
        <Text style={styles.hint}>
          Tim admin akan memverifikasi data perusahaan Anda. Setelah disetujui, gunakan email dan password yang
          didaftarkan untuk masuk.
        </Text>
      </View>

      <TouchableOpacity
        style={styles.primaryBtn}
        onPress={() => navigation.replace('Login')}
        activeOpacity={0.9}
      >
        <LinearGradient colors={[colors.baytgo, colors.baytgoDark]} style={styles.primaryGradient}>
          <Text style={styles.primaryText}>Ke halaman masuk</Text>
        </LinearGradient>
      </TouchableOpacity>
    </AuthScreenShell>
  );
}

const styles = StyleSheet.create({
  card: {
    backgroundColor: colors.white,
    borderRadius: 16,
    padding: 20,
    marginBottom: 20,
    borderWidth: 1,
    borderColor: colors.slate100,
    alignItems: 'center',
  },
  iconWrap: {
    width: 72,
    height: 72,
    borderRadius: 36,
    backgroundColor: '#ECFDF5',
    alignItems: 'center',
    justifyContent: 'center',
    marginBottom: 16,
  },
  message: {
    fontSize: 15,
    lineHeight: 22,
    fontWeight: '700',
    color: colors.slate900,
    textAlign: 'center',
  },
  hint: {
    marginTop: 12,
    fontSize: 13,
    lineHeight: 20,
    color: colors.slate500,
    fontWeight: '600',
    textAlign: 'center',
  },
  primaryBtn: { borderRadius: 16, overflow: 'hidden' },
  primaryGradient: { paddingVertical: 16, alignItems: 'center' },
  primaryText: { color: colors.white, fontSize: 16, fontWeight: '800' },
});
