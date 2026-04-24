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
import { StatusBar } from 'expo-status-bar';

export default function ForgotPasswordScreen({ navigation }) {
  const [email, setEmail] = useState('');
  const [loading, setLoading] = useState(false);

  const handleReset = async () => {
    if (!email) {
      Alert.alert('Perhatian', 'Mohon masukkan email Anda.');
      return;
    }
    setLoading(true);
    // Simulasi pengiriman reset password
    setTimeout(() => {
      setLoading(false);
      Alert.alert('Sukses', 'Instruksi reset password telah dikirim ke email Anda.');
      navigation.navigate('Login');
    }, 1500);
  };

  return (
    <SafeAreaView style={styles.container}>
      <StatusBar style="dark" />
      <KeyboardAvoidingView 
        behavior={Platform.OS === 'ios' ? 'padding' : 'height'}
        style={{ flex: 1 }}
      >
        <View style={styles.content}>
          <TouchableOpacity onPress={() => navigation.navigate('Login')} style={styles.backBtn}>
            <Text style={styles.backText}>← Kembali</Text>
          </TouchableOpacity>

          <Text style={styles.title}>Lupa Kata Sandi?</Text>
          <Text style={styles.subtitle}>Jangan khawatir. Masukkan email Anda dan kami akan mengirimkan instruksi pemulihan.</Text>

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

          <TouchableOpacity 
            style={styles.mainBtn} 
            onPress={handleReset}
            disabled={loading}
          >
            {loading ? <ActivityIndicator color="#fff" /> : <Text style={styles.mainBtnText}>Kirim Instruksi</Text>}
          </TouchableOpacity>
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
  content: {
    flex: 1,
    paddingHorizontal: 40,
    paddingTop: 20,
  },
  backBtn: {
    marginBottom: 40,
  },
  backText: {
    color: '#64748B',
    fontSize: 15,
    fontWeight: '500',
  },
  title: {
    fontSize: 32,
    fontWeight: '700',
    color: '#0F172A',
    marginBottom: 10,
  },
  subtitle: {
    fontSize: 16,
    color: '#64748B',
    lineHeight: 24,
    marginBottom: 40,
  },
  inputGroup: {
    marginBottom: 30,
  },
  label: {
    fontSize: 11,
    fontWeight: '800',
    color: '#94A3B8',
    letterSpacing: 1,
    marginBottom: 10,
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
  mainBtn: {
    backgroundColor: '#0984e3',
    paddingVertical: 20,
    borderRadius: 18,
    alignItems: 'center',
    shadowColor: '#0984e3',
    shadowOffset: { width: 0, height: 10 },
    shadowOpacity: 0.2,
    shadowRadius: 20,
    elevation: 8,
  },
  mainBtnText: {
    color: '#FFFFFF',
    fontSize: 16,
    fontWeight: '700',
  },
});
