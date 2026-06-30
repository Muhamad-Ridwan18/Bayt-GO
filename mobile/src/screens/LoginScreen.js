import React, { useState } from 'react';
import { View, Text, StyleSheet, TouchableOpacity, ActivityIndicator, Alert } from 'react-native';
import { LinearGradient } from 'expo-linear-gradient';
import AuthScreenShell from '../components/AuthScreenShell';
import AuthInput from '../components/AuthInput';
import { useAuth } from '../context/AuthContext';
import { resetRoot } from '../navigation/rootNavigation';
import { colors } from '../theme/colors';

export default function LoginScreen({ navigation }) {
  const { login } = useAuth();
  const [email, setEmail] = useState('');
  const [password, setPassword] = useState('');
  const [loading, setLoading] = useState(false);
  const [error, setError] = useState('');

  const handleLogin = async () => {
    if (!email.trim() || !password) {
      setError('Email dan password wajib diisi.');
      return;
    }

    setLoading(true);
    setError('');
    try {
      await login(email.trim(), password);
      resetRoot(navigation, [{ name: 'Main' }]);
    } catch (err) {
      setError(err.message || 'Gagal login');
    } finally {
      setLoading(false);
    }
  };

  return (
    <AuthScreenShell
      title="Masuk"
      subtitle="Selamat datang kembali. Masuk untuk mengelola pemesanan dan profil Anda."
      onBack={() => navigation.goBack()}
    >
      {error ? <Text style={styles.bannerError}>{error}</Text> : null}

      <AuthInput
        label="Email"
        icon="mail-outline"
        value={email}
        onChangeText={setEmail}
        placeholder="nama@email.com"
        keyboardType="email-address"
        autoCapitalize="none"
        autoCorrect={false}
      />
      <AuthInput
        label="Password"
        icon="lock-closed-outline"
        value={password}
        onChangeText={setPassword}
        placeholder="Password Anda"
        secureTextEntry
      />

      <TouchableOpacity
        style={styles.primaryBtn}
        onPress={handleLogin}
        disabled={loading}
        activeOpacity={0.9}
      >
        <LinearGradient colors={[colors.baytgo, colors.baytgoDark]} style={styles.primaryGradient}>
          {loading ? (
            <ActivityIndicator color={colors.white} />
          ) : (
            <Text style={styles.primaryText}>Masuk</Text>
          )}
        </LinearGradient>
      </TouchableOpacity>

      <View style={styles.footerRow}>
        <Text style={styles.footerText}>Belum punya akun? </Text>
        <TouchableOpacity onPress={() => navigation.replace('Register')}>
          <Text style={styles.footerLink}>Daftar</Text>
        </TouchableOpacity>
      </View>
    </AuthScreenShell>
  );
}

const styles = StyleSheet.create({
  bannerError: {
    backgroundColor: '#FEF2F2',
    color: '#B91C1C',
    padding: 12,
    borderRadius: 14,
    marginBottom: 16,
    fontSize: 13,
    fontWeight: '600',
  },
  primaryBtn: { borderRadius: 16, overflow: 'hidden', marginTop: 8 },
  primaryGradient: { paddingVertical: 16, alignItems: 'center' },
  primaryText: { color: colors.white, fontSize: 16, fontWeight: '800' },
  footerRow: { flexDirection: 'row', justifyContent: 'center', marginTop: 24 },
  footerText: { fontSize: 14, color: colors.slate500, fontWeight: '600' },
  footerLink: { fontSize: 14, color: colors.baytgo, fontWeight: '800' },
});
