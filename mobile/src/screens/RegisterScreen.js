import React, { useState } from 'react';
import {
  View,
  Text,
  StyleSheet,
  TouchableOpacity,
  ActivityIndicator,
  Alert,
  Image,
  Linking,
  Modal,
} from 'react-native';
import { LinearGradient } from 'expo-linear-gradient';
import * as ImagePicker from 'expo-image-picker';
import * as DocumentPicker from 'expo-document-picker';
import AuthScreenShell from '../components/AuthScreenShell';
import AuthInput from '../components/AuthInput';
import DatePickerField from '../components/DatePickerField';
import PhoneInternationalInput from '../components/PhoneInternationalInput';
import RepeatingTextField from '../components/RepeatingTextField';
import { DEFAULT_PHONE_COUNTRY, buildFullPhone } from '../utils/phoneCountries';
import { useAuth } from '../context/AuthContext';
import { sendOtp, verifyOtp } from '../api/auth';
import { colors } from '../theme/colors';
import { WEB_BASE_URL } from '../config/api';
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

function cleanRows(rows) {
  return (rows || []).map((s) => s.trim()).filter(Boolean);
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
      if (!cleanRows(languages).length) {
        return 'Isi minimal satu bahasa.';
      }
      if (!cleanRows(workExperiences).length) {
        return 'Isi minimal satu pengalaman kerja.';
      }
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
          Alert.alert('Berhasil', data.message || 'Pendaftaran berhasil.', [
            { text: 'OK', onPress: () => navigation.navigate('Login') },
          ]);
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

      <AuthInput
        label={role === 'customer' && customerType === 'company' ? 'Nama perusahaan' : 'Nama lengkap'}
        icon="person-outline"
        value={name}
        onChangeText={setName}
        placeholder={role === 'customer' && customerType === 'company' ? 'Nama perusahaan' : 'Nama Anda'}
      />
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
      <PhoneInternationalInput
        label="Nomor HP / WhatsApp"
        dial={phoneDial}
        national={phoneNational}
        countryIso={phoneCountryIso}
        onChange={handlePhoneChange}
        hint="Pilih kode negara lalu masukkan nomor tanpa kode negara."
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
          <RepeatingTextField
            label="Penguasaan bahasa"
            items={languages}
            onChange={setLanguages}
            placeholder="Contoh: Arab (fasih), Inggris"
            addLabel="Tambah bahasa"
          />
          <RepeatingTextField
            label="Studi / pendidikan"
            items={educations}
            onChange={setEducations}
            placeholder="Riwayat studi atau pendidikan formal"
            addLabel="Tambah studi"
            optional
          />
          <RepeatingTextField
            label="Pengalaman kerja"
            items={workExperiences}
            onChange={setWorkExperiences}
            placeholder="Masukkan pengalaman kerja sebagai muthowif"
            addLabel="Tambah pengalaman"
          />
          <AuthInput
            label="Referensi muthowif (opsional)"
            icon="document-text-outline"
            value={referenceText}
            onChangeText={setReferenceText}
            multiline
            placeholder="Nama lembaga, kontak, atau keterangan referensi"
          />
          <AuthInput
            label="Kode referral muthowif (opsional)"
            icon="gift-outline"
            value={referralCode}
            onChangeText={setReferralCode}
            placeholder="Contoh: ABCD12"
            autoCapitalize="characters"
          />
          <Text style={styles.fieldHint}>
            Jika ada muthowif yang mengundang Anda, masukkan kode mereka. Hanya kode muthowif terverifikasi yang diterima.
          </Text>
          <ImagePickerField label="Foto profil *" image={photo} onPick={() => pickImage(setPhoto)} />
          <ImagePickerField label="Foto KTP *" image={ktp} onPick={() => pickImage(setKtp)} />
          <View style={styles.imageField}>
            <Text style={styles.imageLabel}>Dokumen pendukung (opsional)</Text>
            <TouchableOpacity style={styles.imageBtn} onPress={pickSupportingDocs}>
              <Text style={styles.imagePlaceholder}>
                {supportingDocs.length > 0 ? 'Tambah file lagi' : 'Pilih PDF / foto'}
              </Text>
            </TouchableOpacity>
            {supportingDocs.length > 0 ? (
              <View style={styles.docList}>
                {supportingDocs.map((doc, index) => (
                  <View key={`${doc.uri}-${index}`} style={styles.docRow}>
                    <Text style={styles.docName} numberOfLines={1}>
                      {doc.name || `Dokumen ${index + 1}`}
                    </Text>
                    <TouchableOpacity
                      onPress={() => setSupportingDocs((prev) => prev.filter((_, i) => i !== index))}
                      hitSlop={8}
                    >
                      <Text style={styles.docRemove}>Hapus</Text>
                    </TouchableOpacity>
                  </View>
                ))}
              </View>
            ) : null}
          </View>
        </>
      )}

      <TouchableOpacity
        style={styles.termsRow}
        onPress={() => setTermsAccepted((v) => !v)}
        activeOpacity={0.8}
      >
        <View style={[styles.termsCheck, termsAccepted && styles.termsCheckActive]}>
          {termsAccepted ? <Text style={styles.termsCheckMark}>✓</Text> : null}
        </View>
        <Text style={styles.termsText}>
          Saya telah membaca dan menyetujui{' '}
          <Text
            style={styles.termsLink}
            onPress={() => Linking.openURL(`${WEB_BASE_URL}/terms`)}
          >
            Syarat & Ketentuan
          </Text>
        </Text>
      </TouchableOpacity>

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

      <Modal visible={termsModalOpen} transparent animationType="fade" onRequestClose={() => setTermsModalOpen(false)}>
        <View style={styles.modalBackdrop}>
          <View style={styles.modalCard}>
            <Text style={styles.modalTitle}>Syarat & Ketentuan</Text>
            <Text style={styles.modalBody}>
              Sebelum mendaftar, pastikan Anda sudah membaca dan menyetujui syarat & ketentuan BaytGo.
            </Text>
            <TouchableOpacity onPress={() => Linking.openURL(`${WEB_BASE_URL}/terms`)}>
              <Text style={styles.modalLink}>Baca Syarat & Ketentuan</Text>
            </TouchableOpacity>
            <View style={styles.modalActions}>
              <TouchableOpacity style={styles.modalCancelBtn} onPress={() => setTermsModalOpen(false)}>
                <Text style={styles.modalCancelText}>Batal</Text>
              </TouchableOpacity>
              <TouchableOpacity
                style={styles.modalAgreeBtn}
                onPress={agreeAndSubmit}
              >
                <Text style={styles.modalAgreeText}>Setuju dan Daftar</Text>
              </TouchableOpacity>
            </View>
          </View>
        </View>
      </Modal>
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
  fieldHint: {
    fontSize: 11,
    color: colors.slate500,
    fontWeight: '600',
    marginTop: -8,
    marginBottom: 14,
    lineHeight: 16,
  },
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
  docList: { marginTop: 10, gap: 6 },
  docRow: {
    flexDirection: 'row',
    alignItems: 'center',
    gap: 8,
    backgroundColor: colors.white,
    borderRadius: 12,
    paddingHorizontal: 12,
    paddingVertical: 10,
    borderWidth: 1,
    borderColor: colors.slate100,
  },
  docName: { flex: 1, fontSize: 13, fontWeight: '600', color: colors.slate700 },
  docRemove: { fontSize: 12, fontWeight: '800', color: '#B91C1C' },
  primaryBtn: { borderRadius: 16, overflow: 'hidden', marginTop: 8 },
  primaryGradient: { paddingVertical: 16, alignItems: 'center' },
  primaryText: { color: colors.white, fontSize: 16, fontWeight: '800' },
  footerRow: { flexDirection: 'row', justifyContent: 'center', marginTop: 24 },
  footerText: { fontSize: 14, color: colors.slate500, fontWeight: '600' },
  footerLink: { fontSize: 14, color: colors.baytgo, fontWeight: '800' },
  termsRow: { flexDirection: 'row', alignItems: 'flex-start', gap: 10, marginBottom: 16 },
  termsCheck: {
    width: 22,
    height: 22,
    borderRadius: 6,
    borderWidth: 2,
    borderColor: colors.slate200,
    alignItems: 'center',
    justifyContent: 'center',
    marginTop: 1,
  },
  termsCheckActive: { backgroundColor: colors.baytgo, borderColor: colors.baytgo },
  termsCheckMark: { color: colors.white, fontSize: 13, fontWeight: '900' },
  termsText: { flex: 1, fontSize: 13, lineHeight: 20, color: colors.slate600, fontWeight: '600' },
  termsLink: { color: colors.baytgo, fontWeight: '800' },
  modalBackdrop: {
    flex: 1,
    backgroundColor: 'rgba(15, 23, 42, 0.45)',
    justifyContent: 'center',
    padding: 24,
  },
  modalCard: {
    backgroundColor: colors.white,
    borderRadius: 20,
    padding: 20,
  },
  modalTitle: { fontSize: 18, fontWeight: '900', color: colors.slate900 },
  modalBody: { marginTop: 10, fontSize: 14, lineHeight: 21, color: colors.slate600, fontWeight: '500' },
  modalLink: { marginTop: 12, fontSize: 14, fontWeight: '800', color: colors.baytgo },
  modalActions: { flexDirection: 'row', gap: 10, marginTop: 20 },
  modalCancelBtn: {
    flex: 1,
    paddingVertical: 14,
    borderRadius: 14,
    borderWidth: 1,
    borderColor: colors.slate200,
    alignItems: 'center',
  },
  modalCancelText: { fontSize: 14, fontWeight: '800', color: colors.slate600 },
  modalAgreeBtn: {
    flex: 1,
    paddingVertical: 14,
    borderRadius: 14,
    backgroundColor: colors.baytgo,
    alignItems: 'center',
  },
  modalAgreeText: { fontSize: 14, fontWeight: '800', color: colors.white },
});
