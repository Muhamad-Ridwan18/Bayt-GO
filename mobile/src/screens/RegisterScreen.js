import React, { useState } from 'react';
import {
  View,
  Text,
  StyleSheet,
  Alert,
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
import { useLocale } from '../utils/locale';
import { useAuth } from '../context/AuthContext';
import { sendOtp, verifyOtp } from '../api/auth';
import { colors, radius, spacing, typography } from '../theme/tokens';
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

function ImagePickerField({ label, image, onPick, onClear, isEn }) {
  return (
    <View style={styles.imageField}>
      <Text style={styles.imageLabel}>{label}</Text>
      {image ? (
        <SingleImagePreview uri={image.uri} onRemove={onClear || (() => onPick())} size={120} />
      ) : null}
      <PressableScale onPress={onPick} haptic="light" style={styles.imageBtn}>
        <Text style={styles.imagePlaceholder}>{image ? (isEn ? 'Change photo' : 'Ganti foto') : (isEn ? 'Select photo' : 'Pilih foto')}</Text>
      </PressableScale>
    </View>
  );
}

export default function RegisterScreen({ navigation, route }) {
  const locale = useLocale(); const isEn = locale === 'en';
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
  const [workLocation, setWorkLocation] = useState('');
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
  const [galleryImages, setGalleryImages] = useState([]);
  const [supportingDocs, setSupportingDocs] = useState([]);

  const [otp, setOtp] = useState('');
  const [otpMessage, setOtpMessage] = useState('');
  const [sendingOtp, setSendingOtp] = useState(false);

  const pickImage = async (setter) => {
    const permission = await ImagePicker.requestMediaLibraryPermissionsAsync();
    if (!permission.granted) {
      Alert.alert(isEn ? 'Permission required' : 'Izin diperlukan', isEn ? 'Allow gallery access to upload photos.' : 'Izinkan akses galeri untuk upload foto.');
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

  const pickGalleryImages = async () => {
    const permission = await ImagePicker.requestMediaLibraryPermissionsAsync();
    if (!permission.granted) {
      Alert.alert(isEn ? 'Permission required' : 'Izin diperlukan', isEn ? 'Allow gallery access to upload photos.' : 'Izinkan akses galeri untuk upload foto.');
      return;
    }
    const result = await ImagePicker.launchImageLibraryAsync({
      mediaTypes: ['images'],
      quality: 0.8,
      allowsMultipleSelection: true,
      selectionLimit: 20,
    });
    if (!result.canceled && result.assets?.length) {
      setGalleryImages((prev) => [...prev, ...result.assets].slice(0, 20));
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
      return isEn ? 'Complete all required fields.' : 'Lengkapi semua field wajib.';
    }
    if (password !== passwordConfirmation) {
      return isEn ? 'Password confirmation does not match.' : 'Konfirmasi password tidak cocok.';
    }
    if (customerType === 'company' && role === 'customer' && !ppuiNumber.trim()) {
      return isEn ? 'PPUI number is required for company pilgrims.' : 'Nomor PPUI wajib untuk jamaah perusahaan.';
    }
    if (role === 'muthowif') {
      if (!nik.trim() || nik.trim().length !== 16) return isEn ? 'NIK must be 16 digits.' : 'NIK harus 16 digit.';
      if (!birthDate.trim()) return isEn ? 'Date of birth is required.' : 'Tanggal lahir wajib diisi.';
      if (!passportNumber.trim()) return isEn ? 'Passport number is required.' : 'Nomor paspor wajib diisi.';
      if (!workLocation.trim()) return isEn ? 'Work location is required.' : 'Lokasi kerja wajib diisi.';
      if (!cleanRows(languages).length) return isEn ? 'Enter at least one language.' : 'Isi minimal satu bahasa.';
      if (!cleanRows(workExperiences).length) return isEn ? 'Enter at least one work experience.' : 'Isi minimal satu pengalaman kerja.';
      if (!photo || !ktp) return isEn ? 'Profile photo and ID card are required.' : 'Foto profil dan KTP wajib diupload.';
      if (galleryImages.length < 3) return isEn ? 'Upload at least 3 gallery photos.' : 'Unggah minimal 3 foto galeri.';
    }
    return null;
  };

  const dispatchOtp = async () => {
    setSendingOtp(true);
    setOtpMessage('');
    const fullPhone = phone || buildFullPhone(phoneDial, phoneNational);
    try {
      const data = await sendOtp(fullPhone, role);
      setOtpMessage(data.message || (isEn ? 'OTP code sent successfully.' : 'Kode OTP berhasil dikirim.'));
    } catch (err) {
      setError(err.message || (isEn ? 'Failed to send OTP' : 'Gagal mengirim OTP'));
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
      setError(err.message || (isEn ? 'Failed to send OTP' : 'Gagal mengirim OTP'));
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
      setError(isEn ? 'OTP code must be 6 digits.' : 'Kode OTP harus 6 digit.');
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
            title: isEn ? 'Registration successful' : 'Pendaftaran berhasil',
            description: data.message || (isEn ? 'Your pilgrim account has been created. Please sign in.' : 'Akun jamaah Anda sudah dibuat. Silakan masuk.'),
            primaryLabel: isEn ? 'Sign In' : 'Masuk',
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
          workLocation: workLocation.trim(),
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
          galleryImages,
          supportingDocuments: supportingDocs,
        });
        if (data.token) {
          resetRoot(navigation, [{ name: 'Main' }]);
        } else {
          navigateToSuccess(navigation, {
            title: isEn ? 'Registration successful' : 'Pendaftaran berhasil',
            description: data.message || (isEn ? 'Muthowif registration accepted. Please sign in after verification.' : 'Pendaftaran muthowif diterima. Silakan masuk setelah verifikasi.'),
            primaryLabel: isEn ? 'Sign In' : 'Masuk',
            primaryTarget: { replace: true, name: 'Login' },
          });
        }
      }
    } catch (err) {
      setError(err.message || (isEn ? 'Failed to complete registration' : 'Gagal menyelesaikan pendaftaran'));
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
        title={isEn ? 'WhatsApp Verification' : 'Verifikasi WhatsApp'}
        subtitle={isEn ? `Enter the 6-digit code sent to ${maskPhone(phone)}` : `Masukkan kode 6 digit yang dikirim ke ${maskPhone(phone)}`}
        onBack={handleBack}
      >
        {error ? <Text style={styles.bannerError}>{error}</Text> : null}

        <Card style={styles.otpBox} padding={spacing.lg} elevated={false} variant="flat">
          <Text style={styles.otpHint}>
            {isEn ? 'We have sent a verification code to your WhatsApp number. Enter the code to complete registration.' : 'Kami telah mengirim kode verifikasi ke nomor WhatsApp Anda. Masukkan kode tersebut untuk menyelesaikan pendaftaran.'}
          </Text>
          <AuthInput
            label={isEn ? 'OTP Code' : 'Kode OTP'}
            icon={KeyRound}
            value={otp}
            onChangeText={setOtp}
            keyboardType="number-pad"
            maxLength={6}
            placeholder="000000"
          />
          <Button
            label={isEn ? 'Resend code' : 'Kirim ulang kode'}
            onPress={dispatchOtp}
            loading={sendingOtp}
            variant="secondary"
            size="sm"
            fullWidth={false}
          />
          {otpMessage ? <Text style={styles.otpMessage}>{otpMessage}</Text> : null}
        </Card>

        <Button label={isEn ? 'Complete Registration' : 'Selesaikan Pendaftaran'} onPress={completeRegistration} loading={loading} />
      </AuthScreenShell>
    );
  }

  return (
    <AuthScreenShell
      title={isEn ? 'Register' : 'Daftar'}
      subtitle={isEn ? 'Create a pilgrim or muthowif account to start using BaytGo.' : 'Buat akun jamaah atau muthowif untuk mulai menggunakan BaytGo.'}
      onBack={handleBack}
    >
      <View style={styles.roleRow}>
        <RoleTab label={isEn ? 'Pilgrim' : 'Jamaah'} active={role === 'customer'} onPress={() => setRole('customer')} />
        <RoleTab label="Muthowif" active={role === 'muthowif'} onPress={() => setRole('muthowif')} />
      </View>

      {error ? <Text style={styles.bannerError}>{error}</Text> : null}

      <AuthInput
        label={role === 'customer' && customerType === 'company' ? (isEn ? 'Company name' : 'Nama perusahaan') : (isEn ? 'Full name' : 'Nama lengkap')}
        icon={User}
        value={name}
        onChangeText={setName}
        placeholder={role === 'customer' && customerType === 'company' ? (isEn ? 'Company name' : 'Nama perusahaan') : (isEn ? 'Your name' : 'Nama Anda')}
      />
      <AuthInput label="Email" icon={Mail} value={email} onChangeText={setEmail} placeholder="name@email.com" keyboardType="email-address" autoCapitalize="none" />
      <AuthInput label="Password" icon={Lock} value={password} onChangeText={setPassword} secureTextEntry placeholder={isEn ? 'Min. 8 characters' : 'Min. 8 karakter'} />
      <AuthInput label={isEn ? 'Confirm password' : 'Konfirmasi password'} icon={Lock} value={passwordConfirmation} onChangeText={setPasswordConfirmation} secureTextEntry placeholder={isEn ? 'Repeat password' : 'Ulangi password'} />
      <PhoneInternationalInput
        label={isEn ? 'Phone / WhatsApp' : 'Nomor HP / WhatsApp'}
        dial={phoneDial}
        national={phoneNational}
        countryIso={phoneCountryIso}
        onChange={handlePhoneChange}
        hint={isEn ? 'Select country code then enter number without country code.' : 'Pilih kode negara lalu masukkan nomor tanpa kode negara.'}
      />
      <AuthInput label={isEn ? 'Address' : 'Alamat'} icon={MapPin} value={address} onChangeText={setAddress} placeholder={isEn ? 'Full address' : 'Alamat lengkap'} multiline />

      {role === 'customer' && (
        <>
          <Text style={styles.sectionLabel}>{isEn ? 'Pilgrim type' : 'Tipe jamaah'}</Text>
          <View style={styles.roleRow}>
            <RoleTab label="Personal" active={customerType === 'personal'} onPress={() => setCustomerType('personal')} />
            <RoleTab label={isEn ? 'Company' : 'Perusahaan'} active={customerType === 'company'} onPress={() => setCustomerType('company')} />
          </View>
          {customerType === 'company' && (
            <AuthInput label={isEn ? 'PPUI Number' : 'Nomor PPUI'} icon={Building2} value={ppuiNumber} onChangeText={setPpuiNumber} placeholder={isEn ? 'Company PPUI number' : 'Nomor PPUI perusahaan'} />
          )}
        </>
      )}

      {role === 'muthowif' && (
        <>
          <AuthInput
            label={isEn ? 'Current work location *' : 'Lokasi kerja saat ini *'}
            icon={MapPin}
            value={workLocation}
            onChangeText={setWorkLocation}
            placeholder={isEn ? 'e.g. Mecca, Medina, Jakarta' : 'Contoh: Mekkah, Madinah, Jakarta'}
          />
          <Text style={styles.fieldHint}>
            {isEn ? 'Enter the city or area where you are currently stationed.' : 'Isi kota atau wilayah tempat Anda sedang bertugas saat ini.'}
          </Text>
          <AuthInput label={isEn ? 'NIK (16 digits)' : 'NIK (16 digit)'} icon={CreditCard} value={nik} onChangeText={setNik} keyboardType="number-pad" maxLength={16} />
          <DatePickerField label={isEn ? 'Date of birth' : 'Tanggal lahir'} value={birthDate} onChange={setBirthDate} placeholder={isEn ? 'Select date of birth' : 'Pilih tanggal lahir'} maximumDate={new Date()} />
          <AuthInput label={isEn ? 'Passport number' : 'Nomor paspor'} icon={Plane} value={passportNumber} onChangeText={setPassportNumber} />
          <RepeatingTextField label={isEn ? 'Languages' : 'Penguasaan bahasa'} items={languages} onChange={setLanguages} placeholder={isEn ? 'e.g. Arabic (fluent), English' : 'Contoh: Arab (fasih), Inggris'} addLabel={isEn ? 'Add language' : 'Tambah bahasa'} />
          <RepeatingTextField label={isEn ? 'Education' : 'Studi / pendidikan'} items={educations} onChange={setEducations} placeholder={isEn ? 'Formal education history' : 'Riwayat studi atau pendidikan formal'} addLabel={isEn ? 'Add education' : 'Tambah studi'} optional />
          <RepeatingTextField label={isEn ? 'Work experience' : 'Pengalaman kerja'} items={workExperiences} onChange={setWorkExperiences} placeholder={isEn ? 'Enter work experience as muthowif' : 'Masukkan pengalaman kerja sebagai muthowif'} addLabel={isEn ? 'Add experience' : 'Tambah pengalaman'} />
          <AuthInput label={isEn ? 'Muthowif reference (optional)' : 'Referensi muthowif (opsional)'} icon={FileText} value={referenceText} onChangeText={setReferenceText} multiline placeholder={isEn ? 'Organization name, contact, or reference details' : 'Nama lembaga, kontak, atau keterangan referensi'} />
          <AuthInput label={isEn ? 'Muthowif referral code (optional)' : 'Kode referral muthowif (opsional)'} icon={Gift} value={referralCode} onChangeText={setReferralCode} placeholder={isEn ? 'e.g. ABCD12' : 'Contoh: ABCD12'} autoCapitalize="characters" />
          <Text style={styles.fieldHint}>
            {isEn ? 'If a muthowif invited you, enter their code. Only verified muthowif codes are accepted.' : 'Jika ada muthowif yang mengundang Anda, masukkan kode mereka. Hanya kode muthowif terverifikasi yang diterima.'}
          </Text>
          <ImagePickerField label={isEn ? 'Profile photo *' : 'Foto profil *'} image={photo} onPick={() => pickImage(setPhoto)} onClear={() => setPhoto(null)} isEn={isEn} />
          <ImagePickerField label={isEn ? 'ID card photo *' : 'Foto KTP *'} image={ktp} onPick={() => pickImage(setKtp)} onClear={() => setKtp(null)} isEn={isEn} />
          <View style={styles.imageField}>
            <Text style={styles.imageLabel}>{isEn ? 'Gallery (minimum 3 photos) *' : 'Galeri (minimal 3 foto) *'}</Text>
            <PressableScale onPress={pickGalleryImages} haptic="light" style={styles.imageBtn}>
              <Text style={styles.imagePlaceholder}>
                {galleryImages.length > 0 ? (isEn ? 'Add more photos' : 'Tambah foto lagi') : (isEn ? 'Select gallery photos' : 'Pilih foto galeri')}
              </Text>
            </PressableScale>
            <UploadPreviewStrip
              files={galleryImages}
              onRemove={(index) => setGalleryImages((prev) => prev.filter((_, i) => i !== index))}
              style={styles.docPreview}
            />
            <Text style={styles.fieldHint}>
              {isEn ? 'Upload at least 3 work or documentation photos. These will be added to your Gallery portfolio.' : 'Unggah minimal 3 foto kerja atau dokumentasi. Foto ini masuk ke portofolio Galeri.'}
            </Text>
          </View>
          <View style={styles.imageField}>
            <Text style={styles.imageLabel}>{isEn ? 'Supporting documents (optional)' : 'Dokumen pendukung (opsional)'}</Text>
            <PressableScale onPress={pickSupportingDocs} haptic="light" style={styles.imageBtn}>
              <Text style={styles.imagePlaceholder}>
                {supportingDocs.length > 0 ? (isEn ? 'Add more files' : 'Tambah file lagi') : (isEn ? 'Select PDF / photo' : 'Pilih PDF / foto')}
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
          {isEn ? 'I have read and agree to the ' : 'Saya telah membaca dan menyetujui '}
          <Text style={styles.termsLink} onPress={() => navigation.navigate('Terms')}>
            {isEn ? 'Terms & Conditions' : 'Syarat & Ketentuan'}
          </Text>
        </Text>
      </PressableScale>

      <Button label={isEn ? 'Register Now' : 'Daftar Sekarang'} onPress={handleSubmitForm} loading={loading} />

      <View style={styles.footerRow}>
        <Text style={styles.footerText}>{isEn ? 'Already have an account? ' : 'Sudah punya akun? '}</Text>
        <PressableScale onPress={() => navigation.replace('Login')} haptic="light">
          <Text style={styles.footerLink}>{isEn ? 'Sign In' : 'Masuk'}</Text>
        </PressableScale>
      </View>

      <Modal visible={termsModalOpen} transparent animationType="fade" onRequestClose={() => setTermsModalOpen(false)}>
        <View style={styles.modalBackdrop}>
          <Card style={styles.modalCard} padding={spacing.xl}>
            <Text style={styles.modalTitle}>{isEn ? 'Terms & Conditions' : 'Syarat & Ketentuan'}</Text>
            <Text style={styles.modalBody}>
              {isEn ? 'Before registering, make sure you have read and agreed to the BaytGo terms & conditions.' : 'Sebelum mendaftar, pastikan Anda sudah membaca dan menyetujui syarat & ketentuan BaytGo.'}
            </Text>
            <PressableScale onPress={() => navigation.navigate('Terms')} haptic="light">
              <Text style={styles.modalLink}>{isEn ? 'Read Terms & Conditions' : 'Baca Syarat & Ketentuan'}</Text>
            </PressableScale>
            <View style={styles.modalActions}>
              <View style={styles.modalBtn}>
                <Button label={isEn ? 'Cancel' : 'Batal'} onPress={() => setTermsModalOpen(false)} variant="secondary" />
              </View>
              <View style={styles.modalBtn}>
                <Button label={isEn ? 'Agree and Register' : 'Setuju dan Daftar'} onPress={agreeAndSubmit} />
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
