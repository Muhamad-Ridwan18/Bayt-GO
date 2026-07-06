import React, { useState } from 'react';
import { View, Text, StyleSheet, TouchableOpacity, ActivityIndicator, Alert } from 'react-native';
import { LinearGradient } from 'expo-linear-gradient';
import AuthScreenShell from '../components/AuthScreenShell';
import AuthInput from '../components/AuthInput';
import PhoneInternationalInput from '../components/PhoneInternationalInput';
import { sendPasswordResetOtp, resetPassword } from '../api/auth';
import { DEFAULT_PHONE_COUNTRY, buildFullPhone } from '../utils/phoneCountries';
import { colors } from '../theme/colors';

export default function ForgotPasswordScreen({ navigation }) {
  const [step, setStep] = useState('phone');
  const [phoneDial, setPhoneDial] = useState(DEFAULT_PHONE_COUNTRY.d);
  const [phoneNational, setPhoneNational] = useState('');
  const [phoneCountryIso, setPhoneCountryIso] = useState(DEFAULT_PHONE_COUNTRY.iso);
  const [phone, setPhone] = useState('');
  const [resetToken, setResetToken] = useState('');
  const [maskedPhone, setMaskedPhone] = useState('');
  const [otp, setOtp] = useState('');
  const [password, setPassword] = useState('');
  const [passwordConfirmation, setPasswordConfirmation] = useState('');
  const [loading, setLoading] = useState(false);
  const [error, setError] = useState('');

  const handlePhoneChange = ({ dial, national, countryIso, fullPhone }) => {
    setPhoneDial(dial);
    setPhoneNational(national);
    setPhoneCountryIso(countryIso);
    setPhone(fullPhone || buildFullPhone(dial, national));
  };

  const handleSendOtp = async () => {
    const fullPhone = phone || buildFullPhone(phoneDial, phoneNational);
    if (!fullPhone) {
      setError('Masukkan nomor WhatsApp terdaftar.');
      return;
    }

    setLoading(true);
    setError('');
    try {
      const data = await sendPasswordResetOtp(fullPhone);
      setResetToken(data.reset_token);
      setMaskedPhone(data.masked_phone || fullPhone);
      setStep('reset');
    } catch (err) {
      setError(err.message || 'Gagal mengirim kode reset');
    } finally {
      setLoading(false);
    }
  };

  const handleReset = async () => {
    if (otp.length !== 6) {
      setError('Kode OTP harus 6 digit.');
      return;
    }
    if (!password || password !== passwordConfirmation) {
      setError('Password tidak cocok.');
      return;
    }

    setLoading(true);
    setError('');
    try {
      const data = await resetPassword({
        token: resetToken,
        otp,
        password,
        passwordConfirmation,
      });
      Alert.alert('Berhasil', data.message || 'Password berhasil direset.', [
        { text: 'Masuk', onPress: () => navigation.replace('Login') },
      ]);
    } catch (err) {
      setError(err.message || 'Gagal reset password');
    } finally {
      setLoading(false);
    }
  };

  if (step === 'reset') {
    return (
      <AuthScreenShell
        title="Reset Password"
        subtitle={`Masukkan kode dari WhatsApp ${maskedPhone}`}
        onBack={() => setStep('phone')}
      >
        {error ? <Text style={styles.bannerError}>{error}</Text> : null}
        <AuthInput label="Kode OTP" icon="key-outline" value={otp} onChangeText={setOtp} keyboardType="number-pad" maxLength={6} />
        <AuthInput label="Password baru" icon="lock-closed-outline" value={password} onChangeText={setPassword} secureTextEntry />
        <AuthInput label="Konfirmasi password" icon="lock-closed-outline" value={passwordConfirmation} onChangeText={setPasswordConfirmation} secureTextEntry />
        <TouchableOpacity style={styles.primaryBtn} onPress={handleReset} disabled={loading}>
          <LinearGradient colors={[colors.baytgo, colors.baytgoDark]} style={styles.primaryGradient}>
            {loading ? <ActivityIndicator color={colors.white} /> : <Text style={styles.primaryText}>Simpan password baru</Text>}
          </LinearGradient>
        </TouchableOpacity>
      </AuthScreenShell>
    );
  }

  return (
    <AuthScreenShell
      title="Lupa Password"
      subtitle="Kami akan mengirim kode reset ke nomor WhatsApp terdaftar."
      onBack={() => navigation.goBack()}
    >
      {error ? <Text style={styles.bannerError}>{error}</Text> : null}
      <PhoneInternationalInput
        label="Nomor WhatsApp"
        dial={phoneDial}
        national={phoneNational}
        countryIso={phoneCountryIso}
        onChange={handlePhoneChange}
      />
      <TouchableOpacity style={styles.primaryBtn} onPress={handleSendOtp} disabled={loading}>
        <LinearGradient colors={[colors.baytgo, colors.baytgoDark]} style={styles.primaryGradient}>
          {loading ? <ActivityIndicator color={colors.white} /> : <Text style={styles.primaryText}>Kirim kode reset</Text>}
        </LinearGradient>
      </TouchableOpacity>
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
});
