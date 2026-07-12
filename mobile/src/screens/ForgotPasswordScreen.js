import React, { useState } from 'react';
import { Text, StyleSheet } from 'react-native';
import { KeyRound, Lock } from 'lucide-react-native';
import AuthScreenShell from '../components/AuthScreenShell';
import AuthInput from '../components/AuthInput';
import Button from '../ui/Button';
import PhoneInternationalInput from '../components/PhoneInternationalInput';
import { sendPasswordResetOtp, resetPassword } from '../api/auth';
import { DEFAULT_PHONE_COUNTRY, buildFullPhone } from '../utils/phoneCountries';
import { colors, radius, spacing, typography } from '../theme/tokens';
import { navigateToSuccess } from '../navigation/rootNavigation';

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
      navigateToSuccess(navigation, {
        title: 'Password berhasil direset',
        description: data.message || 'Silakan masuk dengan password baru Anda.',
        primaryLabel: 'Masuk',
        primaryTarget: { replace: true, name: 'Login' },
      });
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
        <AuthInput label="Kode OTP" icon={KeyRound} value={otp} onChangeText={setOtp} keyboardType="number-pad" maxLength={6} />
        <AuthInput label="Password baru" icon={Lock} value={password} onChangeText={setPassword} secureTextEntry />
        <AuthInput label="Konfirmasi password" icon={Lock} value={passwordConfirmation} onChangeText={setPasswordConfirmation} secureTextEntry />
        <Button label="Simpan password baru" onPress={handleReset} loading={loading} />
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
      <Button label="Kirim kode reset" onPress={handleSendOtp} loading={loading} />
    </AuthScreenShell>
  );
}

const styles = StyleSheet.create({
  bannerError: {
    backgroundColor: colors.errorLight,
    color: colors.error,
    padding: spacing.md,
    borderRadius: radius.sm,
    marginBottom: spacing.lg,
    ...typography.caption,
    fontFamily: 'PlusJakartaSans_600SemiBold',
  },
});
