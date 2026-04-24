import React, { useState } from 'react';
import { 
  StyleSheet, 
  Text, 
  View, 
  TextInput, 
  TouchableOpacity, 
  KeyboardAvoidingView, 
  Platform, 
  ActivityIndicator, 
  Alert 
} from 'react-native';
import { SafeAreaView } from 'react-native-safe-area-context';
import { apiClient } from '../api/client';
import { StatusBar } from 'expo-status-bar';

export default function LoginScreen({ navigation, onLoginSuccess }) {
  const [email, setEmail] = useState('');
  const [password, setPassword] = useState('');
  const [loading, setLoading] = useState(false);

  const handleLogin = async () => {
    if (!email || !password) {
      Alert.alert('Perhatian', 'Mohon isi email dan password Anda.');
      return;
    }

    setLoading(true);
    try {
      const data = await apiClient.login(email, password);
      if (onLoginSuccess) {
        onLoginSuccess(data);
      }
    } catch (error) {
      Alert.alert('Gagal Masuk', error.message);
    } finally {
      setLoading(false);
    }
  };

  return (
    <SafeAreaView style={styles.container}>
      <StatusBar style="dark" />
      <KeyboardAvoidingView 
        behavior={Platform.OS === 'ios' ? 'padding' : 'height'}
        style={styles.keyboardView}
      >
        <View style={styles.content}>
          <View style={styles.header}>
            <TouchableOpacity onPress={() => navigation.navigate('Opening')}>
              <Text style={styles.backLink}>Kembali</Text>
            </TouchableOpacity>
          </View>

          <View style={styles.titleSection}>
            <Text style={styles.title}>Selamat Datang.</Text>
            <Text style={styles.subtitle}>Masuk untuk melanjutkan rencana ibadah Anda bersama Bayt-GO.</Text>
          </View>

          <View style={styles.form}>
            <View style={styles.inputGroup}>
              <Text style={styles.label}>ALAMAT EMAIL</Text>
              <TextInput 
                style={styles.input}
                placeholder="nama@email.com"
                value={email}
                onChangeText={setEmail}
                keyboardType="email-address"
                autoCapitalize="none"
              />
            </View>

            <View style={styles.inputGroup}>
              <View style={styles.labelRow}>
                <Text style={styles.label}>KATA SANDI</Text>
                <TouchableOpacity onPress={() => navigation.navigate('ForgotPassword')}>
                  <Text style={styles.forgotAction}>Lupa Sandi?</Text>
                </TouchableOpacity>
              </View>
              <TextInput 
                style={styles.input}
                placeholder="Masukkan kata sandi"
                value={password}
                onChangeText={setPassword}
                secureTextEntry
              />
            </View>

            <TouchableOpacity 
              activeOpacity={0.8}
              style={styles.loginButton}
              onPress={handleLogin}
              disabled={loading}
            >
              {loading ? (
                <ActivityIndicator color="#fff" />
              ) : (
                <Text style={styles.loginButtonText}>Masuk Ke Akun</Text>
              )}
            </TouchableOpacity>

            <View style={styles.footer}>
              <Text style={styles.footerText}>Belum memiliki akun?</Text>
              <TouchableOpacity onPress={() => navigation.navigate('Register')}>
                <Text style={styles.registerLink}> Daftar Sekarang</Text>
              </TouchableOpacity>
            </View>
          </View>
        </View>
      </KeyboardAvoidingView>
    </SafeAreaView>
  );
}

const styles = StyleSheet.create({
  container: {
    flex: 1,
    backgroundColor: '#FFFFFF',
  },
  keyboardView: {
    flex: 1,
  },
  content: {
    flex: 1,
    paddingHorizontal: 40,
    justifyContent: 'center',
    paddingBottom: 40,
  },
  header: {
    position: 'absolute',
    top: 20,
    left: 40,
  },
  backLink: {
    color: '#64748B',
    fontSize: 15,
    fontWeight: '500',
  },
  titleSection: {
    marginBottom: 50,
  },
  title: {
    fontSize: 34,
    fontWeight: '700',
    color: '#0F172A',
    letterSpacing: -0.5,
  },
  subtitle: {
    fontSize: 16,
    color: '#64748B',
    marginTop: 12,
    lineHeight: 24,
  },
  form: {
    width: '100%',
  },
  inputGroup: {
    marginBottom: 25,
  },
  labelRow: {
    flexDirection: 'row',
    justifyContent: 'space-between',
    marginBottom: 10,
  },
  label: {
    fontSize: 11,
    fontWeight: '800',
    color: '#94A3B8',
    letterSpacing: 1,
  },
  forgotAction: {
    fontSize: 12,
    color: '#0984e3',
    fontWeight: '700',
  },
  input: {
    backgroundColor: '#F8FAFC',
    borderWidth: 1,
    borderColor: '#E2E8F0',
    paddingVertical: 16,
    paddingHorizontal: 20,
    borderRadius: 14,
    fontSize: 16,
    color: '#0F172A',
  },
  loginButton: {
    backgroundColor: '#0984e3',
    paddingVertical: 20,
    borderRadius: 18,
    alignItems: 'center',
    marginTop: 15,
    shadowColor: '#0984e3',
    shadowOffset: { width: 0, height: 10 },
    shadowOpacity: 0.3,
    shadowRadius: 20,
    elevation: 8,
  },
  loginButtonText: {
    color: '#FFFFFF',
    fontSize: 16,
    fontWeight: '700',
    letterSpacing: 0.5,
  },
  footer: {
    flexDirection: 'row',
    justifyContent: 'center',
    marginTop: 40,
  },
  footerText: {
    color: '#64748B',
    fontSize: 15,
  },
  registerLink: {
    color: '#0F172A',
    fontSize: 15,
    fontWeight: '700',
  },
});
