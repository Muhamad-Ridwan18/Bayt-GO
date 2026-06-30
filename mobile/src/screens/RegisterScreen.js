import React, { useState } from 'react';
import {
  View,
  Text,
  StyleSheet,
  TouchableOpacity,
  ActivityIndicator,
  Alert,
  Image,
} from 'react-native';
import { LinearGradient } from 'expo-linear-gradient';
import * as ImagePicker from 'expo-image-picker';
import AuthScreenShell from '../components/AuthScreenShell';
import AuthInput from '../components/AuthInput';
import DatePickerField from '../components/DatePickerField';
import { useAuth } from '../context/AuthContext';
import { sendOtp, verifyOtp } from '../api/auth';
import { colors } from '../theme/colors';
import { resetRoot, navigateRoot } from '../navigation/rootNavigation';

function RoleTab({ label, active, onPress }) {
  return (
    <TouchableOpacity
      style={[styles.roleTab, active && styles.roleTabActive]}
      onPress={onPress}
    >
      <Text style={[styles.roleTabText, active && styles.roleTabTextActive]}>{label}</Text>
    </TouchableOpacity>
  );
}

function maskPhone(phone) {
  const digits = phone.replace(/\D/g, '');
  if (digits.length < 4) return phone;
  return `•••• ${digits.slice(-4)}`;
}

function ImagePickerField({ label, image, onPick }) {
  return (
    <View style={styles.imageField}>
      <Text style={styles.imageLabel}>{label}</Text>
      <TouchableOpacity style={styles.imageBtn} onPress={onPick}>
        {image ? (
          <Image source={{ uri: image.uri }} style={styles.imagePreview} />
        ) : (
          <Text style={styles.imagePlaceholder}>Pilih foto</Text>
        )}
      </TouchableOpacity>
    </View>
  );
}

export default function RegisterScreen({ navigation, route }) {
  const { registerCustomer, registerMuthowif } = useAuth();
  const [step, setStep] = useState('form');
  const [role, setRole] = useState(route.params?.role || 'customer');
  const [customerType, setCustomerType] = useState('personal');
  const [loading, setLoading] = useState(false);
  const [error, setError] = useState('');

  const [name, setName] = useState('');
  const [email, setEmail] = useState('');
  const [password, setPassword] = useState('');
  const [passwordConfirmation, setPasswordConfirmation] = useState('');
  const [phone, setPhone] = useState('');
  const [address, setAddress] = useState('');
  const [ppuiNumber, setPpuiNumber] = useState('');

  const [nik, setNik] = useState('');
  const [birthDate, setBirthDate] = useState('');
  const [passportNumber, setPassportNumber] = useState('');
  const [languages, setLanguages] = useState('');
  const [referenceText, setReferenceText] = useState('');
  const [photo, setPhoto] = useState(null);
  const [ktp, setKtp] = useState(null);

  const [otp, setOtp] = useState('');
  const [otpMessage, setOtpMessage] = useState('');
  const [sendingOtp, setSendingOtp] = useState(false);

  const pickImage = async (setter) => {
    const permission = await ImagePicker.requestMediaLibraryPermissionsAsync();
    if (!permission.granted) {
      Alert.alert('Izin diperlukan', 'Izinkan akses galeri untuk upload foto.');
      return;
    }
    const result = await ImagePicker.launchImageLibraryAsync({
      mediaTypes: ['images'],
      quality: 0.8,
    });
    if (!result.canceled && result.assets[0]) {
      setter(result.assets[0]);
    }
  };

  const validateForm = () => {
    if (!name.trim() || !email.trim() || !password || !phone.trim() || !address.trim()) {
      return 'Lengkapi semua field wajib.';
    }
    if (password !== passwordConfirmation) {
      return 'Konfirmasi password tidak cocok.';
    }
    if (customerType === 'company' && role === 'customer' && !ppuiNumber.trim()) {
      return 'Nomor PPUI wajib untuk jamaah perusahaan.';
    }
    if (role === 'muthowif') {
      if (!nik.trim() || nik.trim().length !== 16) return 'NIK harus 16 digit.';
      if (!birthDate.trim()) return 'Tanggal lahir wajib diisi.';
      if (!passportNumber.trim()) return 'Nomor paspor wajib diisi.';
      if (!languages.split(',').map((s) => s.trim()).filter(Boolean).length) {
        return 'Isi minimal satu bahasa.';
      }
      if (!photo || !ktp) return 'Foto profil dan KTP wajib diupload.';
    }
    return null;
  };

  const dispatchOtp = async () => {
    setSendingOtp(true);
    setOtpMessage('');
    try {
      const data = await sendOtp(phone.trim(), role);
      setOtpMessage(data.message || 'Kode OTP berhasil dikirim.');
    } catch (err) {
      throw err;
    } finally {
      setSendingOtp(false);
    }
  };

  const handleSubmitForm = async () => {
    const validationError = validateForm();
    if (validationError) {
      setError(validationError);
      return;
    }

    setLoading(true);
    setError('');
    try {
      await dispatchOtp();
      setOtp('');
      setStep('verify');
    } catch (err) {
      setError(err.message || 'Gagal mengirim OTP');
    } finally {
      setLoading(false);
    }
  };

  const completeRegistration = async () => {
    if (otp.length !== 6) {
      setError('Kode OTP harus 6 digit.');
      return;
    }

    setLoading(true);
    setError('');

    try {
      await verifyOtp(phone.trim(), otp);

      if (role === 'customer') {
        const data = await registerCustomer({
          name: name.trim(),
          email: email.trim(),
          password,
          passwordConfirmation,
          phone: phone.trim(),
          address: address.trim(),
          customerType,
          ppuiNumber: ppuiNumber.trim(),
        });
        if (data.token) {
          resetRoot(navigation, [{ name: 'Main' }]);
        } else {
          Alert.alert('Berhasil', data.message || 'Pendaftaran berhasil.', [
            { text: 'OK', onPress: () => navigation.navigate('Login') },
          ]);
        }
      } else {
        const langList = languages.split(',').map((s) => s.trim()).filter(Boolean);
        const data = await registerMuthowif({
          name: name.trim(),
          email: email.trim(),
          password,
          passwordConfirmation,
          phone: phone.trim(),
          address: address.trim(),
          nik: nik.trim(),
          birthDate: birthDate.trim(),
          passportNumber: passportNumber.trim(),
          languages: langList,
          educations: [],
          workExperiences: [],
          referenceText: referenceText.trim(),
          photo,
          ktp,
        });
        if (data.token) {
          resetRoot(navigation, [{ name: 'Main' }]);
        } else {
          Alert.alert('Berhasil', data.message || 'Pendaftaran berhasil.', [
            { text: 'OK', onPress: () => navigation.navigate('Login') },
          ]);
        }
      }
    } catch (err) {
      setError(err.message || 'Gagal menyelesaikan pendaftaran');
    } finally {
      setLoading(false);
    }
  };

  const handleBack = () => {
    if (step === 'verify') {
      setStep('form');
      setError('');
      setOtp('');
      return;
    }
    navigation.goBack();
  };

  if (step === 'verify') {
    return (
      <AuthScreenShell
        title="Verifikasi WhatsApp"
        subtitle={`Masukkan kode 6 digit yang dikirim ke ${maskPhone(phone)}`}
        onBack={handleBack}
      >
        {error ? <Text style={styles.bannerError}>{error}</Text> : null}

        <View style={styles.otpBox}>
          <Text style={styles.otpHint}>
            Kami telah mengirim kode verifikasi ke nomor WhatsApp Anda. Masukkan kode tersebut untuk menyelesaikan pendaftaran.
          </Text>
          <AuthInput
            label="Kode OTP"
            icon="key-outline"
            value={otp}
            onChangeText={setOtp}
            keyboardType="number-pad"
            maxLength={6}
            placeholder="000000"
          />
          <TouchableOpacity
            style={styles.otpSendBtn}
            onPress={dispatchOtp}
            disabled={sendingOtp}
          >
            {sendingOtp ? (
              <ActivityIndicator color={colors.baytgo} />
            ) : (
              <Text style={styles.otpSendText}>Kirim ulang kode</Text>
            )}
          </TouchableOpacity>
          {otpMessage ? <Text style={styles.otpMessage}>{otpMessage}</Text> : null}
        </View>

        <TouchableOpacity
          style={styles.primaryBtn}
          onPress={completeRegistration}
          disabled={loading}
          activeOpacity={0.9}
        >
          <LinearGradient colors={[colors.baytgo, colors.baytgoDark]} style={styles.primaryGradient}>
            {loading ? (
              <ActivityIndicator color={colors.white} />
            ) : (
              <Text style={styles.primaryText}>Selesaikan Pendaftaran</Text>
            )}
          </LinearGradient>
        </TouchableOpacity>
      </AuthScreenShell>
    );
  }

  return (
    <AuthScreenShell
      title="Daftar"
      subtitle="Buat akun jamaah atau muthowif untuk mulai menggunakan BaytGo."
      onBack={handleBack}
    >
      <View style={styles.roleRow}>
        <RoleTab
          label="Jamaah"
          active={role === 'customer'}
          onPress={() => setRole('customer')}
        />
        <RoleTab
          label="Muthowif"
          active={role === 'muthowif'}
          onPress={() => setRole('muthowif')}
        />
      </View>

      {error ? <Text style={styles.bannerError}>{error}</Text> : null}

      <AuthInput label="Nama lengkap" icon="person-outline" value={name} onChangeText={setName} placeholder="Nama Anda" />
      <AuthInput
        label="Email"
        icon="mail-outline"
        value={email}
        onChangeText={setEmail}
        placeholder="nama@email.com"
        keyboardType="email-address"
        autoCapitalize="none"
      />
      <AuthInput label="Password" icon="lock-closed-outline" value={password} onChangeText={setPassword} secureTextEntry placeholder="Min. 8 karakter" />
      <AuthInput
        label="Konfirmasi password"
        icon="lock-closed-outline"
        value={passwordConfirmation}
        onChangeText={setPasswordConfirmation}
        secureTextEntry
        placeholder="Ulangi password"
      />
      <AuthInput
        label="Nomor HP / WhatsApp"
        icon="call-outline"
        value={phone}
        onChangeText={setPhone}
        placeholder="08xxxxxxxxxx"
        keyboardType="phone-pad"
      />
      <AuthInput label="Alamat" icon="location-outline" value={address} onChangeText={setAddress} placeholder="Alamat lengkap" multiline />

      {role === 'customer' && (
        <>
          <Text style={styles.sectionLabel}>Tipe jamaah</Text>
          <View style={styles.roleRow}>
            <RoleTab label="Personal" active={customerType === 'personal'} onPress={() => setCustomerType('personal')} />
            <RoleTab label="Perusahaan" active={customerType === 'company'} onPress={() => setCustomerType('company')} />
          </View>
          {customerType === 'company' && (
            <AuthInput
              label="Nomor PPUI"
              icon="business-outline"
              value={ppuiNumber}
              onChangeText={setPpuiNumber}
              placeholder="Nomor PPUI perusahaan"
            />
          )}
        </>
      )}

      {role === 'muthowif' && (
        <>
          <AuthInput label="NIK (16 digit)" icon="card-outline" value={nik} onChangeText={setNik} keyboardType="number-pad" maxLength={16} />
          <DatePickerField
            label="Tanggal lahir"
            value={birthDate}
            onChange={setBirthDate}
            placeholder="Pilih tanggal lahir"
            maximumDate={new Date()}
          />
          <AuthInput label="Nomor paspor" icon="airplane-outline" value={passportNumber} onChangeText={setPassportNumber} />
          <AuthInput
            label="Bahasa"
            icon="language-outline"
            value={languages}
            onChangeText={setLanguages}
            placeholder="Indonesia, Arab, Inggris"
          />
          <AuthInput
            label="Referensi (opsional)"
            icon="document-text-outline"
            value={referenceText}
            onChangeText={setReferenceText}
            multiline
            placeholder="Pengalaman atau referensi"
          />
          <ImagePickerField label="Foto profil *" image={photo} onPick={() => pickImage(setPhoto)} />
          <ImagePickerField label="Foto KTP *" image={ktp} onPick={() => pickImage(setKtp)} />
        </>
      )}

      <TouchableOpacity style={styles.primaryBtn} onPress={handleSubmitForm} disabled={loading} activeOpacity={0.9}>
        <LinearGradient colors={[colors.baytgo, colors.baytgoDark]} style={styles.primaryGradient}>
          {loading ? <ActivityIndicator color={colors.white} /> : <Text style={styles.primaryText}>Daftar Sekarang</Text>}
        </LinearGradient>
      </TouchableOpacity>

      <View style={styles.footerRow}>
        <Text style={styles.footerText}>Sudah punya akun? </Text>
        <TouchableOpacity onPress={() => navigation.replace('Login')}>
          <Text style={styles.footerLink}>Masuk</Text>
        </TouchableOpacity>
      </View>
    </AuthScreenShell>
  );
}

const styles = StyleSheet.create({
  roleRow: { flexDirection: 'row', gap: 10, marginBottom: 18 },
  roleTab: {
    flex: 1,
    paddingVertical: 12,
    borderRadius: 14,
    backgroundColor: colors.white,
    borderWidth: 1,
    borderColor: colors.slate100,
    alignItems: 'center',
  },
  roleTabActive: { backgroundColor: colors.baytgo, borderColor: colors.baytgo },
  roleTabText: { fontSize: 13, fontWeight: '800', color: colors.slate600 },
  roleTabTextActive: { color: colors.white },
  sectionLabel: { fontSize: 12, fontWeight: '800', color: colors.slate600, marginBottom: 10 },
  bannerError: {
    backgroundColor: '#FEF2F2',
    color: '#B91C1C',
    padding: 12,
    borderRadius: 14,
    marginBottom: 16,
    fontSize: 13,
    fontWeight: '600',
  },
  otpBox: {
    backgroundColor: colors.white,
    borderRadius: 18,
    padding: 16,
    marginBottom: 18,
    borderWidth: 1,
    borderColor: '#BAE6FD',
  },
  otpHint: { fontSize: 13, color: colors.slate600, marginBottom: 16, lineHeight: 20, fontWeight: '500' },
  otpSendBtn: {
    alignSelf: 'flex-start',
    paddingHorizontal: 14,
    paddingVertical: 10,
    borderRadius: 12,
    backgroundColor: '#EFF6FF',
    marginTop: 4,
  },
  otpSendText: { fontSize: 13, fontWeight: '800', color: colors.baytgo },
  otpMessage: { marginTop: 12, fontSize: 12, color: colors.emerald600, fontWeight: '600' },
  imageField: { marginBottom: 14 },
  imageLabel: { fontSize: 12, fontWeight: '800', color: colors.slate600, marginBottom: 8 },
  imageBtn: {
    height: 120,
    borderRadius: 16,
    borderWidth: 1,
    borderColor: colors.slate200,
    borderStyle: 'dashed',
    backgroundColor: colors.white,
    overflow: 'hidden',
    alignItems: 'center',
    justifyContent: 'center',
  },
  imagePreview: { width: '100%', height: '100%' },
  imagePlaceholder: { color: colors.slate400, fontWeight: '700' },
  primaryBtn: { borderRadius: 16, overflow: 'hidden', marginTop: 8 },
  primaryGradient: { paddingVertical: 16, alignItems: 'center' },
  primaryText: { color: colors.white, fontSize: 16, fontWeight: '800' },
  footerRow: { flexDirection: 'row', justifyContent: 'center', marginTop: 24 },
  footerText: { fontSize: 14, color: colors.slate500, fontWeight: '600' },
  footerLink: { fontSize: 14, color: colors.baytgo, fontWeight: '800' },
});
