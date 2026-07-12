import React, { useState } from 'react';
import {
  View,
  Text,
  StyleSheet,
  Alert,
  Linking,
  Modal,
} from 'react-native';
import {
  Building2,
  CreditCard,
  FileText,
  Gift,
  KeyRound,
  Lock,
  Mail,
  MapPin,
  Plane,
  User,
} from 'lucide-react-native';
import * as ImagePicker from 'expo-image-picker';
import * as DocumentPicker from 'expo-document-picker';
import AuthScreenShell from '../components/AuthScreenShell';
import AuthInput from '../components/AuthInput';
import DatePickerField from '../components/DatePickerField';
import PhoneInternationalInput from '../components/PhoneInternationalInput';
import RepeatingTextField from '../components/RepeatingTextField';
import Button from '../ui/Button';
import PressableScale from '../ui/PressableScale';
import Card from '../ui/Card';
import SingleImagePreview from '../ui/SingleImagePreview';
import UploadPreviewStrip from '../ui/UploadPreviewStrip';
import { DEFAULT_PHONE_COUNTRY, buildFullPhone } from '../utils/phoneCountries';
import { useAuth } from '../context/AuthContext';
import { sendOtp, verifyOtp } from '../api/auth';
import { colors, radius, spacing, typography } from '../theme/tokens';
import { WEB_BASE_URL } from '../config/api';
import { resetRoot, navigateToSuccess } from '../navigation/rootNavigation';

function RoleTab({ label, active, onPress }) {
  return (
    <PressableScale
      onPress={onPress}
      haptic="light"
      style={[styles.roleTab, active && styles.roleTabActive]}
    >
      <Text style={[styles.roleTabText, active && styles.roleTabTextActive]}>{label}</Text>
    </PressableScale>
  );
}

function maskPhone(phone) {
  const digits = phone.replace(/\D/g, '');
  if (digits.length < 4) return phone;
  return `•••• ${digits.slice(-4)}`;
}

function cleanRows(rows) {
  return (rows || []).map((s) => s.trim()).filter(Boolean);
}

function ImagePickerField({ label, image, onPick, onClear }) {
  return (
    <View style={styles.imageField}>
      <Text style={styles.imageLabel}>{label}</Text>
      {image ? (
        <SingleImagePreview uri={image.uri} onRemove={onClear || (() => onPick())} size={120} />
      ) : null}
      <PressableScale onPress={onPick} haptic="light" style={styles.imageBtn}>
        <Text style={styles.imagePlaceholder}>{image ? 'Ganti foto' : 'Pilih foto'}</Text>
      </PressableScale>
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
  const [phoneDial, setPhoneDial] = useState(DEFAULT_PHONE_COUNTRY.d);
  const [phoneNational, setPhoneNational] = useState('');
  const [phoneCountryIso, setPhoneCountryIso] = useState(DEFAULT_PHONE_COUNTRY.iso);
  const [phone, setPhone] = useState('');
  const [address, setAddress] = useState('');
  const [ppuiNumber, setPpuiNumber] = useState('');

  const [nik, setNik] = useState('');
  const [birthDate, setBirthDate] = useState('');
  const [passportNumber, setPassportNumber] = useState('');
  const [languages, setLanguages] = useState(['']);
  const [educations, setEducations] = useState(['']);
  const [workExperiences, setWorkExperiences] = useState(['']);
  const [referenceText, setReferenceText] = useState('');
  const [referralCode, setReferralCode] = useState('');
  const [termsAccepted, setTermsAccepted] = useState(false);
  const [termsModalOpen, setTermsModalOpen] = useState(false);
  const [photo, setPhoto] = useState(null);
  const [ktp, setKtp] = useState(null);
  const [supportingDocs, setSupportingDocs] = useState([]);

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

  const pickSupportingDocs = async () => {
    const result = await DocumentPicker.getDocumentAsync({
      type: ['image/*', 'application/pdf'],
      multiple: true,
      copyToCacheDirectory: true,
    });
    if (!result.canceled && result.assets?.length) {
      setSupportingDocs((prev) => [...prev, ...result.assets].slice(0, 20));
    }
  };

  const handlePhoneChange = ({ dial, national, countryIso, fullPhone }) => {
    setPhoneDial(dial);
    setPhoneNational(national);
    setPhoneCountryIso(countryIso);
    setPhone(fullPhone || buildFullPhone(dial, national));
  };

  const validateForm = () => {
    const fullPhone = phone || buildFullPhone(phoneDial, phoneNational);
    if (!name.trim() || !email.trim() || !password || !fullPhone || !address.trim()) {
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
      if (!cleanRows(languages).length) return 'Isi minimal satu bahasa.';
      if (!cleanRows(workExperiences).length) return 'Isi minimal satu pengalaman kerja.';
      if (!photo || !ktp) return 'Foto profil dan KTP wajib diupload.';
    }
    return null;
  };

  const dispatchOtp = async () => {
    setSendingOtp(true);
    setOtpMessage('');
    const fullPhone = phone || buildFullPhone(phoneDial, phoneNational);
    try {
      const data = await sendOtp(fullPhone, role);
      setOtpMessage(data.message || 'Kode OTP berhasil dikirim.');
    } catch (err) {
      setError(err.message || 'Gagal mengirim OTP');
      throw err;
    } finally {
      setSendingOtp(false);
    }
  };

  const proceedToOtp = async () => {
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

  const handleSubmitForm = async () => {
    const validationError = validateForm();
    if (validationError) {
      setError(validationError);
      return;
    }
    if (!termsAccepted) {
      setTermsModalOpen(true);
      return;
    }
    await proceedToOtp();
  };

  const agreeAndSubmit = async () => {
    setTermsAccepted(true);
    setTermsModalOpen(false);
    await proceedToOtp();
  };

  const completeRegistration = async () => {
    if (otp.length !== 6) {
      setError('Kode OTP harus 6 digit.');
      return;
    }

    setLoading(true);
    setError('');

    try {
      const fullPhone = phone || buildFullPhone(phoneDial, phoneNational);
      await verifyOtp(fullPhone, otp);

      if (role === 'customer') {
        const data = await registerCustomer({
          name: name.trim(),
          email: email.trim(),
          password,
          passwordConfirmation,
          phone: fullPhone,
          country: phoneCountryIso,
          address: address.trim(),
          customerType,
          ppuiNumber: ppuiNumber.trim(),
        });
        if (data.token) {
          resetRoot(navigation, [{ name: 'Main' }]);
        } else if (customerType === 'company') {
          navigation.replace('CompanyRegistrationPending', { message: data.message });
        } else {
          navigateToSuccess(navigation, {
            title: 'Pendaftaran berhasil',
            description: data.message || 'Akun jamaah Anda sudah dibuat. Silakan masuk.',
            primaryLabel: 'Masuk',
            primaryTarget: { replace: true, name: 'Login' },
          });
        }
      } else {
        const data = await registerMuthowif({
          name: name.trim(),
          email: email.trim(),
          password,
          passwordConfirmation,
          phone: fullPhone,
          country: phoneCountryIso,
          address: address.trim(),
          nik: nik.trim(),
          birthDate: birthDate.trim(),
          passportNumber: passportNumber.trim(),
          languages: cleanRows(languages),
          educations: cleanRows(educations),
          workExperiences: cleanRows(workExperiences),
          referenceText: referenceText.trim(),
          referralCode: referralCode.trim(),
          photo,
          ktp,
          supportingDocuments: supportingDocs,
        });
        if (data.token) {
          resetRoot(navigation, [{ name: 'Main' }]);
        } else {
          navigateToSuccess(navigation, {
            title: 'Pendaftaran berhasil',
            description: data.message || 'Pendaftaran muthowif diterima. Silakan masuk setelah verifikasi.',
            primaryLabel: 'Masuk',
            primaryTarget: { replace: true, name: 'Login' },
          });
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

        <Card style={styles.otpBox} padding={spacing.lg} elevated={false} variant="flat">
          <Text style={styles.otpHint}>
            Kami telah mengirim kode verifikasi ke nomor WhatsApp Anda. Masukkan kode tersebut untuk menyelesaikan pendaftaran.
          </Text>
          <AuthInput
            label="Kode OTP"
            icon={KeyRound}
            value={otp}
            onChangeText={setOtp}
            keyboardType="number-pad"
            maxLength={6}
            placeholder="000000"
          />
          <Button
            label="Kirim ulang kode"
            onPress={dispatchOtp}
            loading={sendingOtp}
            variant="secondary"
            size="sm"
            fullWidth={false}
          />
          {otpMessage ? <Text style={styles.otpMessage}>{otpMessage}</Text> : null}
        </Card>

        <Button label="Selesaikan Pendaftaran" onPress={completeRegistration} loading={loading} />
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
        <RoleTab label="Jamaah" active={role === 'customer'} onPress={() => setRole('customer')} />
        <RoleTab label="Muthowif" active={role === 'muthowif'} onPress={() => setRole('muthowif')} />
      </View>

      {error ? <Text style={styles.bannerError}>{error}</Text> : null}

      <AuthInput
        label={role === 'customer' && customerType === 'company' ? 'Nama perusahaan' : 'Nama lengkap'}
        icon={User}
        value={name}
        onChangeText={setName}
        placeholder={role === 'customer' && customerType === 'company' ? 'Nama perusahaan' : 'Nama Anda'}
      />
      <AuthInput label="Email" icon={Mail} value={email} onChangeText={setEmail} placeholder="nama@email.com" keyboardType="email-address" autoCapitalize="none" />
      <AuthInput label="Password" icon={Lock} value={password} onChangeText={setPassword} secureTextEntry placeholder="Min. 8 karakter" />
      <AuthInput label="Konfirmasi password" icon={Lock} value={passwordConfirmation} onChangeText={setPasswordConfirmation} secureTextEntry placeholder="Ulangi password" />
      <PhoneInternationalInput
        label="Nomor HP / WhatsApp"
        dial={phoneDial}
        national={phoneNational}
        countryIso={phoneCountryIso}
        onChange={handlePhoneChange}
        hint="Pilih kode negara lalu masukkan nomor tanpa kode negara."
      />
      <AuthInput label="Alamat" icon={MapPin} value={address} onChangeText={setAddress} placeholder="Alamat lengkap" multiline />

      {role === 'customer' && (
        <>
          <Text style={styles.sectionLabel}>Tipe jamaah</Text>
          <View style={styles.roleRow}>
            <RoleTab label="Personal" active={customerType === 'personal'} onPress={() => setCustomerType('personal')} />
            <RoleTab label="Perusahaan" active={customerType === 'company'} onPress={() => setCustomerType('company')} />
          </View>
          {customerType === 'company' && (
            <AuthInput label="Nomor PPUI" icon={Building2} value={ppuiNumber} onChangeText={setPpuiNumber} placeholder="Nomor PPUI perusahaan" />
          )}
        </>
      )}

      {role === 'muthowif' && (
        <>
          <AuthInput label="NIK (16 digit)" icon={CreditCard} value={nik} onChangeText={setNik} keyboardType="number-pad" maxLength={16} />
          <DatePickerField label="Tanggal lahir" value={birthDate} onChange={setBirthDate} placeholder="Pilih tanggal lahir" maximumDate={new Date()} />
          <AuthInput label="Nomor paspor" icon={Plane} value={passportNumber} onChangeText={setPassportNumber} />
          <RepeatingTextField label="Penguasaan bahasa" items={languages} onChange={setLanguages} placeholder="Contoh: Arab (fasih), Inggris" addLabel="Tambah bahasa" />
          <RepeatingTextField label="Studi / pendidikan" items={educations} onChange={setEducations} placeholder="Riwayat studi atau pendidikan formal" addLabel="Tambah studi" optional />
          <RepeatingTextField label="Pengalaman kerja" items={workExperiences} onChange={setWorkExperiences} placeholder="Masukkan pengalaman kerja sebagai muthowif" addLabel="Tambah pengalaman" />
          <AuthInput label="Referensi muthowif (opsional)" icon={FileText} value={referenceText} onChangeText={setReferenceText} multiline placeholder="Nama lembaga, kontak, atau keterangan referensi" />
          <AuthInput label="Kode referral muthowif (opsional)" icon={Gift} value={referralCode} onChangeText={setReferralCode} placeholder="Contoh: ABCD12" autoCapitalize="characters" />
          <Text style={styles.fieldHint}>
            Jika ada muthowif yang mengundang Anda, masukkan kode mereka. Hanya kode muthowif terverifikasi yang diterima.
          </Text>
          <ImagePickerField label="Foto profil *" image={photo} onPick={() => pickImage(setPhoto)} onClear={() => setPhoto(null)} />
          <ImagePickerField label="Foto KTP *" image={ktp} onPick={() => pickImage(setKtp)} onClear={() => setKtp(null)} />
          <View style={styles.imageField}>
            <Text style={styles.imageLabel}>Dokumen pendukung (opsional)</Text>
            <PressableScale onPress={pickSupportingDocs} haptic="light" style={styles.imageBtn}>
              <Text style={styles.imagePlaceholder}>
                {supportingDocs.length > 0 ? 'Tambah file lagi' : 'Pilih PDF / foto'}
              </Text>
            </PressableScale>
            <UploadPreviewStrip
              files={supportingDocs}
              onRemove={(index) => setSupportingDocs((prev) => prev.filter((_, i) => i !== index))}
              style={styles.docPreview}
            />
          </View>
        </>
      )}

      <PressableScale onPress={() => setTermsAccepted((v) => !v)} haptic="light" style={styles.termsRow}>
        <View style={[styles.termsCheck, termsAccepted && styles.termsCheckActive]}>
          {termsAccepted ? <Text style={styles.termsCheckMark}>✓</Text> : null}
        </View>
        <Text style={styles.termsText}>
          Saya telah membaca dan menyetujui{' '}
          <Text style={styles.termsLink} onPress={() => Linking.openURL(`${WEB_BASE_URL}/terms`)}>
            Syarat & Ketentuan
          </Text>
        </Text>
      </PressableScale>

      <Button label="Daftar Sekarang" onPress={handleSubmitForm} loading={loading} />

      <View style={styles.footerRow}>
        <Text style={styles.footerText}>Sudah punya akun? </Text>
        <PressableScale onPress={() => navigation.replace('Login')} haptic="light">
          <Text style={styles.footerLink}>Masuk</Text>
        </PressableScale>
      </View>

      <Modal visible={termsModalOpen} transparent animationType="fade" onRequestClose={() => setTermsModalOpen(false)}>
        <View style={styles.modalBackdrop}>
          <Card style={styles.modalCard} padding={spacing.xl}>
            <Text style={styles.modalTitle}>Syarat & Ketentuan</Text>
            <Text style={styles.modalBody}>
              Sebelum mendaftar, pastikan Anda sudah membaca dan menyetujui syarat & ketentuan BaytGo.
            </Text>
            <PressableScale onPress={() => Linking.openURL(`${WEB_BASE_URL}/terms`)} haptic="light">
              <Text style={styles.modalLink}>Baca Syarat & Ketentuan</Text>
            </PressableScale>
            <View style={styles.modalActions}>
              <View style={styles.modalBtn}>
                <Button label="Batal" onPress={() => setTermsModalOpen(false)} variant="secondary" />
              </View>
              <View style={styles.modalBtn}>
                <Button label="Setuju dan Daftar" onPress={agreeAndSubmit} />
              </View>
            </View>
          </Card>
        </View>
      </Modal>
    </AuthScreenShell>
  );
}

const styles = StyleSheet.create({
  roleRow: { flexDirection: 'row', gap: spacing.sm, marginBottom: spacing.lg },
  roleTab: {
    flex: 1,
    paddingVertical: spacing.md,
    borderRadius: radius.sm,
    backgroundColor: colors.card,
    borderWidth: 1,
    borderColor: colors.border,
    alignItems: 'center',
  },
  roleTabActive: { backgroundColor: colors.baytgo, borderColor: colors.baytgo },
  roleTabText: { ...typography.small, color: colors.textSecondary },
  roleTabTextActive: { color: colors.white },
  sectionLabel: { ...typography.small, color: colors.textSecondary, marginBottom: spacing.sm },
  fieldHint: {
    ...typography.small,
    color: colors.textMuted,
    marginTop: -spacing.sm,
    marginBottom: spacing.lg,
    lineHeight: 16,
    fontFamily: 'PlusJakartaSans_500Medium',
  },
  bannerError: {
    backgroundColor: colors.errorLight,
    color: colors.error,
    padding: spacing.md,
    borderRadius: radius.sm,
    marginBottom: spacing.lg,
    ...typography.caption,
    fontFamily: 'PlusJakartaSans_600SemiBold',
  },
  otpBox: { marginBottom: spacing.lg, borderColor: '#BAE6FD' },
  otpHint: { ...typography.caption, color: colors.textSecondary, marginBottom: spacing.lg, lineHeight: 20 },
  otpMessage: { marginTop: spacing.md, ...typography.small, color: colors.success, fontFamily: 'PlusJakartaSans_500Medium' },
  imageField: { marginBottom: spacing.lg },
  imageLabel: { ...typography.small, color: colors.textSecondary, marginBottom: spacing.sm },
  imageBtn: {
    height: 120,
    borderRadius: radius.md,
    borderWidth: 1,
    borderColor: colors.border,
    borderStyle: 'dashed',
    backgroundColor: colors.card,
    overflow: 'hidden',
    alignItems: 'center',
    justifyContent: 'center',
  },
  imagePlaceholder: { ...typography.caption, color: colors.textMuted, fontFamily: 'PlusJakartaSans_700Bold' },
  docPreview: { marginTop: spacing.sm },
  footerRow: { flexDirection: 'row', justifyContent: 'center', marginTop: spacing['2xl'] },
  footerText: { ...typography.caption, color: colors.textSecondary },
  footerLink: { ...typography.caption, fontFamily: 'PlusJakartaSans_700Bold', color: colors.baytgo },
  termsRow: { flexDirection: 'row', alignItems: 'flex-start', gap: spacing.sm, marginBottom: spacing.lg },
  termsCheck: {
    width: 22,
    height: 22,
    borderRadius: 6,
    borderWidth: 2,
    borderColor: colors.border,
    alignItems: 'center',
    justifyContent: 'center',
    marginTop: 1,
  },
  termsCheckActive: { backgroundColor: colors.baytgo, borderColor: colors.baytgo },
  termsCheckMark: { color: colors.white, fontSize: 13, fontWeight: '900' },
  termsText: { flex: 1, ...typography.caption, lineHeight: 20, color: colors.textSecondary },
  termsLink: { color: colors.baytgo, fontFamily: 'PlusJakartaSans_700Bold' },
  modalBackdrop: {
    flex: 1,
    backgroundColor: colors.overlay,
    justifyContent: 'center',
    padding: spacing['2xl'],
  },
  modalCard: { width: '100%' },
  modalTitle: { ...typography.subtitle, color: colors.textPrimary },
  modalBody: { marginTop: spacing.sm, ...typography.caption, lineHeight: 22, color: colors.textSecondary },
  modalLink: { marginTop: spacing.md, ...typography.caption, fontFamily: 'PlusJakartaSans_700Bold', color: colors.baytgo },
  modalActions: { flexDirection: 'row', gap: spacing.sm, marginTop: spacing.xl },
  modalBtn: { flex: 1 },
});
