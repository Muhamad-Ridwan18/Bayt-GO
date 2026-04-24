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
  Alert,
  ScrollView,
  Dimensions
} from 'react-native';
import { SafeAreaView } from 'react-native-safe-area-context';
import { apiClient } from '../api/client';
import { StatusBar } from 'expo-status-bar';
import * as ImagePicker from 'expo-image-picker';
import { Ionicons } from '@expo/vector-icons';

const { width } = Dimensions.get('window');

export default function RegisterScreen({ navigation }) {
  const [step, setStep] = useState(1);
  const [loading, setLoading] = useState(false);
  const [otpLoading, setOtpLoading] = useState(false);

  // Form State
  const [role, setRole] = useState('customer'); // customer, muthowif
  const [customerType, setCustomerType] = useState('personal'); // personal, company
  const [name, setName] = useState('');
  const [email, setEmail] = useState('');
  const [phone, setPhone] = useState('');
  const [address, setAddress] = useState('');
  const [password, setPassword] = useState('');
  const [passwordConfirmation, setPasswordConfirmation] = useState('');
  
  // Specific Fields
  const [ppuiNumber, setPpuiNumber] = useState('');
  const [nik, setNik] = useState('');
  const [birthDate, setBirthDate] = useState('');
  const [passportNumber, setPassportNumber] = useState('');
  
  // Professional & Documents (Muthowif only)
  const [languages, setLanguages] = useState(['']);
  const [educations, setEducations] = useState(['']);
  const [workExperiences, setWorkExperiences] = useState(['']);
  const [referenceText, setReferenceText] = useState('');
  const [photo, setPhoto] = useState(null);
  const [ktp, setKtp] = useState(null);
  const [supportingDocs, setSupportingDocs] = useState([]);

  // OTP State
  const [otp, setOtp] = useState('');
  const [otpSent, setOtpSent] = useState(false);
  const [otpVerified, setOtpVerified] = useState(false);

  const totalSteps = role === 'muthowif' ? 5 : 4;

  const pickImage = async (type) => {
    const result = await ImagePicker.launchImageLibraryAsync({
      mediaTypes: ['images'],
      allowsEditing: true,
      aspect: type === 'photo' ? [1, 1] : [4, 3],
      quality: 0.7,
    });

    if (!result.canceled) {
      if (type === 'photo') setPhoto(result.assets[0].uri);
      else if (type === 'ktp') setKtp(result.assets[0].uri);
      else if (type === 'doc') setSupportingDocs([...supportingDocs, result.assets[0].uri]);
    }
  };

  const removeDoc = (index) => {
    const newDocs = [...supportingDocs];
    newDocs.splice(index, 1);
    setSupportingDocs(newDocs);
  };

  const updateList = (list, setList, index, value) => {
    const newList = [...list];
    newList[index] = value;
    setList(newList);
  };

  const addListItem = (list, setList) => {
    setList([...list, '']);
  };

  const removeListItem = (list, setList, index) => {
    if (list.length === 1) return;
    const newList = [...list];
    newList.splice(index, 1);
    setList(newList);
  };

  const handleSendOtp = async () => {
    if (phone.length < 10) {
      Alert.alert('Perhatian', 'Masukkan nomor WhatsApp yang valid.');
      return;
    }
    setOtpLoading(true);
    try {
      await apiClient.sendOtp(phone, role);
      setOtpSent(true);
      Alert.alert('Sukses', 'Kode OTP telah dikirim.');
    } catch (error) {
      Alert.alert('Gagal', error.message);
    } finally {
      setOtpLoading(false);
    }
  };

  const handleVerifyOtp = async () => {
    setOtpLoading(true);
    try {
      await apiClient.verifyOtp(phone, otp);
      setOtpVerified(true);
      Alert.alert('Berhasil', 'Nomor terverifikasi.');
    } catch (error) {
      Alert.alert('Gagal', error.message);
    } finally {
      setOtpLoading(false);
    }
  };

  const nextStep = () => {
    if (step === 2 && !otpVerified) {
      Alert.alert('Perhatian', 'Verifikasi WhatsApp wajib dilakukan.');
      return;
    }
    if (step === totalSteps) return;
    setStep(step + 1);
  };

  const prevStep = () => {
    if (step === 1) navigation.navigate('Login');
    else setStep(step - 1);
  };

  const handleRegister = async () => {
    setLoading(true);
    try {
      await apiClient.register(
        name, email, password, passwordConfirmation, 
        role, phone, address, customerType, ppuiNumber, 
        nik, birthDate, passportNumber,
        languages, educations, workExperiences, 
        referenceText, photo, ktp, supportingDocs
      );
      
      if (role === 'muthowif') {
        Alert.alert('Pendaftaran Berhasil', 'Akun Muthowif Anda menunggu verifikasi oleh Admin.');
      } else {
        Alert.alert('Sukses', 'Akun berhasil dibuat.');
      }
      navigation.navigate('Login');
    } catch (error) {
      Alert.alert('Gagal', error.message);
    } finally {
      setLoading(false);
    }
  };

  return (
    <SafeAreaView style={styles.container}>
      <StatusBar style="dark" />
      <KeyboardAvoidingView behavior={Platform.OS === 'ios' ? 'padding' : 'height'} style={{ flex: 1 }}>
        <View style={styles.header}>
          <TouchableOpacity onPress={prevStep}>
            <Text style={styles.backLink}>← Kembali</Text>
          </TouchableOpacity>
          <View style={styles.progressContainer}>
            {Array.from({ length: totalSteps }, (_, i) => i + 1).map((i) => (
              <View key={i} style={[styles.progressDot, step >= i && styles.progressDotActive]} />
            ))}
          </View>
        </View>

        <ScrollView contentContainerStyle={styles.scrollContent} showsVerticalScrollIndicator={false}>
          
          {step === 1 && (
            <View style={styles.stepWrapper}>
              <Text style={styles.title}>Buat akun</Text>
              <Text style={styles.subtitle}>Pilih peran Anda. Muthowif wajib melengkapi biodata, passport, foto, dan dokumen.</Text>
              
              <Text style={styles.fieldLabel}>SAYA MENDAFTAR SEBAGAI</Text>
              <View style={styles.roleGrid}>
                <TouchableOpacity 
                  style={[styles.roleCard, role === 'customer' && styles.roleCardActive]}
                  onPress={() => setRole('customer')}
                >
                  <View style={[styles.radio, role === 'customer' && styles.radioActive]} />
                  <View style={styles.roleTextWrapper}>
                    <Text style={styles.roleTitle}>Jamaah</Text>
                    <Text style={styles.roleSub}>Buat permintaan pendampingan</Text>
                  </View>
                </TouchableOpacity>

                <TouchableOpacity 
                  style={[styles.roleCard, role === 'muthowif' && styles.roleCardActive]}
                  onPress={() => setRole('muthowif')}
                >
                  <View style={[styles.radio, role === 'muthowif' && styles.radioActive]} />
                  <View style={styles.roleTextWrapper}>
                    <Text style={styles.roleTitle}>Muthowif</Text>
                    <Text style={styles.roleSub}>Lengkapi dokumen & riwayat</Text>
                  </View>
                </TouchableOpacity>
              </View>

              {role === 'customer' && (
                <View style={{ marginTop: 30 }}>
                  <Text style={styles.fieldLabel}>TIPE JAMAAH</Text>
                  <View style={styles.roleGrid}>
                    <TouchableOpacity 
                      style={[styles.roleCard, customerType === 'personal' && styles.roleCardActive]}
                      onPress={() => setCustomerType('personal')}
                    >
                      <View style={[styles.radio, customerType === 'personal' && styles.radioActive]} />
                      <View style={styles.roleTextWrapper}>
                        <Text style={styles.roleTitle}>Personal</Text>
                        <Text style={styles.roleSub}>Individu</Text>
                      </View>
                    </TouchableOpacity>

                    <TouchableOpacity 
                      style={[styles.roleCard, customerType === 'company' && styles.roleCardActive]}
                      onPress={() => setCustomerType('company')}
                    >
                      <View style={[styles.radio, customerType === 'company' && styles.radioActive]} />
                      <View style={styles.roleTextWrapper}>
                        <Text style={styles.roleTitle}>Perusahaan</Text>
                        <Text style={styles.roleSub}>Badan usaha</Text>
                      </View>
                    </TouchableOpacity>
                  </View>
                </View>
              )}
            </View>
          )}

          {step === 2 && (
            <View style={styles.stepWrapper}>
              <Text style={styles.title}>Kontak & Identitas</Text>
              <Text style={styles.subtitle}>Gunakan nomor WhatsApp aktif untuk menerima kode verifikasi.</Text>

              <View style={styles.inputGroup}>
                <Text style={styles.label}>{role === 'customer' && customerType === 'company' ? 'NAMA PERUSAHAAN' : 'NAMA LENGKAP'}</Text>
                <TextInput style={styles.input} placeholder="Sesuai identitas resmi" value={name} onChangeText={setName} />
              </View>

              <View style={styles.inputGroup}>
                <Text style={styles.label}>NO. WHATSAPP</Text>
                <View style={styles.otpInputRow}>
                  <TextInput 
                    style={[styles.input, { flex: 1 }, otpVerified && styles.inputDisabled]} 
                    placeholder="08xxxxxxxxxx" 
                    value={phone} 
                    onChangeText={setPhone} 
                    keyboardType="phone-pad"
                    editable={!otpVerified}
                  />
                  {!otpVerified && (
                    <TouchableOpacity style={styles.otpActionBtn} onPress={handleSendOtp} disabled={otpLoading}>
                      <Text style={styles.otpActionText}>{otpSent ? 'Ulang' : 'Kirim'}</Text>
                    </TouchableOpacity>
                  )}
                </View>
              </View>

              {otpSent && !otpVerified && (
                <View style={styles.otpVerificationBox}>
                  <Text style={styles.label}>KODE VERIFIKASI (6 DIGIT)</Text>
                  <View style={styles.otpInputRow}>
                    <TextInput 
                      style={[styles.input, { flex: 1, textAlign: 'center', letterSpacing: 5 }]} 
                      placeholder="XXXXXX" 
                      value={otp} 
                      onChangeText={setOtp} 
                      keyboardType="number-pad" 
                      maxLength={6} 
                    />
                    <TouchableOpacity style={styles.verifyActionBtn} onPress={handleVerifyOtp} disabled={otpLoading}>
                      <Text style={styles.otpActionText}>Verifikasi</Text>
                    </TouchableOpacity>
                  </View>
                </View>
              )}

              {otpVerified && <View style={styles.verifiedTag}><Text style={styles.verifiedTagText}>✓ WhatsApp Terverifikasi</Text></View>}

              <View style={styles.inputGroup}>
                <Text style={styles.label}>EMAIL</Text>
                <TextInput style={styles.input} placeholder="alamat@email.com" value={email} onChangeText={setEmail} keyboardType="email-address" autoCapitalize="none" />
              </View>
            </View>
          )}

          {step === 3 && (
            <View style={styles.stepWrapper}>
              <Text style={styles.title}>Data Tambahan</Text>
              <Text style={styles.subtitle}>Informasi pendukung sesuai peran Anda.</Text>

              <View style={styles.inputGroup}>
                <Text style={styles.label}>ALAMAT LENGKAP</Text>
                <TextInput style={[styles.input, { height: 80 }]} placeholder="Alamat sesuai KTP/Domisili" value={address} onChangeText={setAddress} multiline />
              </View>

              {role === 'customer' && customerType === 'company' && (
                <View style={styles.inputGroup}>
                  <Text style={styles.label}>NOMOR PPUI</Text>
                  <TextInput style={styles.input} placeholder="Wajib untuk tipe perusahaan" value={ppuiNumber} onChangeText={setPpuiNumber} />
                </View>
              )}

              {role === 'muthowif' && (
                <>
                  <View style={styles.inputGroup}>
                    <Text style={styles.label}>NIK (16 DIGIT)</Text>
                    <TextInput style={styles.input} placeholder="Masukkan 16 digit NIK" value={nik} onChangeText={setNik} keyboardType="number-pad" maxLength={16} />
                  </View>
                  <View style={styles.inputGroup}>
                    <Text style={styles.label}>TANGGAL LAHIR</Text>
                    <TextInput style={styles.input} placeholder="YYYY-MM-DD" value={birthDate} onChangeText={setBirthDate} />
                  </View>
                  <View style={styles.inputGroup}>
                    <Text style={styles.label}>NOMOR PASSPORT</Text>
                    <TextInput style={styles.input} placeholder="Masukkan nomor passport" value={passportNumber} onChangeText={setPassportNumber} />
                  </View>
                </>
              )}
            </View>
          )}

          {step === 4 && role === 'muthowif' && (
            <View style={styles.stepWrapper}>
              <Text style={styles.title}>Dokumen & Keahlian</Text>
              <Text style={styles.subtitle}>Lengkapi data profesional Anda untuk diverifikasi oleh tim Bayt-GO.</Text>

              {/* Uploads */}
              <View style={styles.uploadGrid}>
                <TouchableOpacity style={styles.uploadBox} onPress={() => pickImage('photo')}>
                  {photo ? (
                    <View style={styles.previewWrapper}>
                      <TextInput style={{ display: 'none' }} />
                      <Text style={styles.previewImgText}>✓ Foto Profil</Text>
                    </View>
                  ) : (
                    <>
                      <Ionicons name="person-outline" size={24} color="#64748B" />
                      <Text style={styles.uploadText}>Foto Profil</Text>
                    </>
                  )}
                </TouchableOpacity>

                <TouchableOpacity style={styles.uploadBox} onPress={() => pickImage('ktp')}>
                  {ktp ? (
                    <View style={styles.previewWrapper}>
                      <Text style={styles.previewImgText}>✓ Foto KTP</Text>
                    </View>
                  ) : (
                    <>
                      <Ionicons name="card-outline" size={24} color="#64748B" />
                      <Text style={styles.uploadText}>Foto KTP</Text>
                    </>
                  )}
                </TouchableOpacity>
              </View>

              {/* Repeating Lists */}
              <View style={styles.listSection}>
                <Text style={styles.label}>BAHASA YANG DIKUASAI</Text>
                {languages.map((lang, idx) => (
                  <View key={idx} style={styles.listItemRow}>
                    <TextInput 
                      style={[styles.input, { flex: 1 }]} 
                      placeholder="Contoh: Arab (Fasih)" 
                      value={lang} 
                      onChangeText={(v) => updateList(languages, setLanguages, idx, v)} 
                    />
                    <TouchableOpacity onPress={() => removeListItem(languages, setLanguages, idx)} style={styles.removeBtn}>
                      <Ionicons name="close-circle" size={20} color="#EF4444" />
                    </TouchableOpacity>
                  </View>
                ))}
                <TouchableOpacity style={styles.addBtn} onPress={() => addListItem(languages, setLanguages)}>
                  <Text style={styles.addBtnText}>+ Tambah Bahasa</Text>
                </TouchableOpacity>
              </View>

              <View style={styles.listSection}>
                <Text style={styles.label}>PENGALAMAN KERJA</Text>
                {workExperiences.map((work, idx) => (
                  <View key={idx} style={styles.listItemRow}>
                    <TextInput 
                      style={[styles.input, { flex: 1 }]} 
                      placeholder="Contoh: Pembimbing Umroh (3 Tahun)" 
                      value={work} 
                      onChangeText={(v) => updateList(workExperiences, setWorkExperiences, idx, v)} 
                    />
                    <TouchableOpacity onPress={() => removeListItem(workExperiences, setWorkExperiences, idx)} style={styles.removeBtn}>
                      <Ionicons name="close-circle" size={20} color="#EF4444" />
                    </TouchableOpacity>
                  </View>
                ))}
                <TouchableOpacity style={styles.addBtn} onPress={() => addListItem(workExperiences, setWorkExperiences)}>
                  <Text style={styles.addBtnText}>+ Tambah Pengalaman</Text>
                </TouchableOpacity>
              </View>

              <View style={styles.listSection}>
                <Text style={styles.label}>PENDIDIKAN (OPSIONAL)</Text>
                {educations.map((edu, idx) => (
                  <View key={idx} style={styles.listItemRow}>
                    <TextInput 
                      style={[styles.input, { flex: 1 }]} 
                      placeholder="Contoh: S1 Syariah UI" 
                      value={edu} 
                      onChangeText={(v) => updateList(educations, setEducations, idx, v)} 
                    />
                    <TouchableOpacity onPress={() => removeListItem(educations, setEducations, idx)} style={styles.removeBtn}>
                      <Ionicons name="close-circle" size={20} color="#EF4444" />
                    </TouchableOpacity>
                  </View>
                ))}
                <TouchableOpacity style={styles.addBtn} onPress={() => addListItem(educations, setEducations)}>
                  <Text style={styles.addBtnText}>+ Tambah Pendidikan</Text>
                </TouchableOpacity>
              </View>

              <View style={styles.inputGroup}>
                <Text style={styles.label}>REFERENSI (OPSIONAL)</Text>
                <TextInput 
                  style={[styles.input, { height: 80 }]} 
                  placeholder="Lembaga atau kontak referensi" 
                  value={referenceText} 
                  onChangeText={setReferenceText} 
                  multiline 
                />
              </View>

              <View style={styles.listSection}>
                <Text style={styles.label}>DOKUMEN PENDUKUNG (MAX 10MB)</Text>
                <View style={styles.docGrid}>
                  {supportingDocs.map((uri, idx) => (
                    <View key={idx} style={styles.docTag}>
                      <Text style={styles.docTagText} numberOfLines={1}>Dokumen {idx+1}</Text>
                      <TouchableOpacity onPress={() => removeDoc(idx)}>
                        <Ionicons name="close-circle" size={16} color="#EF4444" />
                      </TouchableOpacity>
                    </View>
                  ))}
                  <TouchableOpacity style={styles.addDocBtn} onPress={() => pickImage('doc')}>
                    <Ionicons name="cloud-upload-outline" size={20} color="#0984e3" />
                    <Text style={styles.addDocBtnText}>Unggah File</Text>
                  </TouchableOpacity>
                </View>
              </View>
            </View>
          )}

          {((step === 4 && role === 'customer') || (step === 5 && role === 'muthowif')) && (
            <View style={styles.stepWrapper}>
              <Text style={styles.title}>Keamanan Akun</Text>
              <Text style={styles.subtitle}>Buat kata sandi yang kuat untuk melindungi akun Anda.</Text>

              <View style={styles.inputGroup}>
                <Text style={styles.label}>KATA SANDI</Text>
                <TextInput style={styles.input} placeholder="Minimal 8 karakter" value={password} onChangeText={setPassword} secureTextEntry />
              </View>

              <View style={styles.inputGroup}>
                <Text style={styles.label}>KONFIRMASI KATA SANDI</Text>
                <TextInput style={styles.input} placeholder="Ulangi kata sandi" value={passwordConfirmation} onChangeText={setPasswordConfirmation} secureTextEntry />
              </View>

              <View style={styles.policyBox}>
                <Text style={styles.policyText}>Dengan menekan Daftar, Anda menyetujui kebijakan privasi kami.</Text>
              </View>
            </View>
          )}

          <TouchableOpacity 
            style={styles.mainButton} 
            onPress={step === totalSteps ? handleRegister : nextStep}
            disabled={loading}
          >
            {loading ? <ActivityIndicator color="#fff" /> : <Text style={styles.mainButtonText}>{step === totalSteps ? 'Daftar Sekarang' : 'Lanjutkan'}</Text>}
          </TouchableOpacity>

        </ScrollView>
      </KeyboardAvoidingView>
    </SafeAreaView>
  );
}

const styles = StyleSheet.create({
  container: {
    flex: 1,
    backgroundColor: '#FFFFFF',
  },
  header: {
    paddingHorizontal: 25,
    paddingTop: 15,
    flexDirection: 'row',
    alignItems: 'center',
    justifyContent: 'space-between',
  },
  backLink: {
    fontSize: 14,
    color: '#64748B',
    fontWeight: '500',
  },
  progressContainer: {
    flexDirection: 'row',
    gap: 8,
  },
  progressDot: {
    width: 25,
    height: 4,
    borderRadius: 2,
    backgroundColor: '#F1F5F9',
  },
  progressDotActive: {
    backgroundColor: '#0984e3',
  },
  scrollContent: {
    paddingHorizontal: 30,
    paddingTop: 30,
    paddingBottom: 50,
  },
  stepWrapper: {
    flex: 1,
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
    marginBottom: 35,
  },
  fieldLabel: {
    fontSize: 11,
    fontWeight: '800',
    color: '#94A3B8',
    letterSpacing: 1.5,
    marginBottom: 15,
  },
  roleGrid: {
    gap: 12,
  },
  roleCard: {
    flexDirection: 'row',
    alignItems: 'center',
    padding: 20,
    borderRadius: 16,
    borderWidth: 1.5,
    borderColor: '#F1F5F9',
    backgroundColor: '#F8FAFC',
  },
  roleCardActive: {
    borderColor: '#0984e3',
    backgroundColor: '#FFFFFF',
    shadowColor: '#0984e3',
    shadowOffset: { width: 0, height: 4 },
    shadowOpacity: 0.1,
    shadowRadius: 10,
    elevation: 2,
  },
  radio: {
    width: 20,
    height: 20,
    borderRadius: 10,
    borderWidth: 2,
    borderColor: '#CBD5E1',
    marginRight: 15,
  },
  radioActive: {
    borderColor: '#0984e3',
    backgroundColor: '#0984e3',
    borderWidth: 5,
  },
  roleTextWrapper: {
    flex: 1,
  },
  roleTitle: {
    fontSize: 16,
    fontWeight: '700',
    color: '#1E293B',
  },
  roleSub: {
    fontSize: 12,
    color: '#64748B',
    marginTop: 2,
  },
  inputGroup: {
    marginBottom: 20,
  },
  label: {
    fontSize: 11,
    fontWeight: '700',
    color: '#64748B',
    marginBottom: 8,
  },
  input: {
    backgroundColor: '#F8FAFC',
    borderWidth: 1,
    borderColor: '#E2E8F0',
    paddingVertical: 14,
    paddingHorizontal: 16,
    borderRadius: 12,
    fontSize: 16,
    color: '#0F172A',
  },
  inputDisabled: {
    backgroundColor: '#F1F5F9',
    color: '#94A3B8',
  },
  otpInputRow: {
    flexDirection: 'row',
    gap: 10,
  },
  otpActionBtn: {
    backgroundColor: '#0984e3',
    paddingHorizontal: 20,
    justifyContent: 'center',
    borderRadius: 12,
  },
  verifyActionBtn: {
    backgroundColor: '#0984e3',
    paddingHorizontal: 15,
    justifyContent: 'center',
    borderRadius: 12,
  },
  otpActionText: {
    color: '#FFFFFF',
    fontSize: 13,
    fontWeight: '700',
  },
  otpVerificationBox: {
    backgroundColor: '#F1F5F9',
    padding: 15,
    borderRadius: 16,
    marginBottom: 20,
  },
  verifiedTag: {
    backgroundColor: '#DCFCE7',
    padding: 12,
    borderRadius: 12,
    marginBottom: 20,
    alignItems: 'center',
  },
  verifiedTagText: {
    color: '#166534',
    fontSize: 14,
    fontWeight: '700',
  },
  mainButton: {
    backgroundColor: '#0984e3',
    paddingVertical: 20,
    borderRadius: 18,
    alignItems: 'center',
    marginTop: 20,
    shadowColor: '#0984e3',
    shadowOffset: { width: 0, height: 10 },
    shadowOpacity: 0.2,
    shadowRadius: 15,
    elevation: 5,
  },
  mainButtonText: {
    color: '#FFFFFF',
    fontSize: 16,
    fontWeight: '700',
  },
  policyBox: {
    marginTop: 10,
    paddingHorizontal: 10,
  },
  policyText: {
    fontSize: 12,
    color: '#94A3B8',
    textAlign: 'center',
    lineHeight: 18,
  },
  uploadGrid: {
    flexDirection: 'row',
    gap: 15,
    marginBottom: 25,
  },
  uploadBox: {
    flex: 1,
    height: 100,
    backgroundColor: '#F8FAFC',
    borderWidth: 1.5,
    borderColor: '#E2E8F0',
    borderStyle: 'dashed',
    borderRadius: 12,
    justifyContent: 'center',
    alignItems: 'center',
  },
  uploadText: {
    fontSize: 12,
    color: '#64748B',
    marginTop: 5,
    fontWeight: '500',
  },
  previewWrapper: {
    alignItems: 'center',
  },
  previewImgText: {
    fontSize: 13,
    color: '#059669',
    fontWeight: '700',
  },
  listSection: {
    marginBottom: 25,
  },
  listItemRow: {
    flexDirection: 'row',
    alignItems: 'center',
    gap: 10,
    marginBottom: 10,
  },
  removeBtn: {
    padding: 5,
  },
  addBtn: {
    paddingVertical: 10,
  },
  addBtnText: {
    color: '#0984e3',
    fontSize: 14,
    fontWeight: '700',
  },
  docGrid: {
    flexDirection: 'row',
    flexWrap: 'wrap',
    gap: 10,
    marginTop: 10,
  },
  docTag: {
    flexDirection: 'row',
    alignItems: 'center',
    backgroundColor: '#F1F5F9',
    paddingHorizontal: 12,
    paddingVertical: 8,
    borderRadius: 20,
    gap: 8,
    maxWidth: 150,
  },
  docTagText: {
    fontSize: 12,
    color: '#1E293B',
    flexShrink: 1,
  },
  addDocBtn: {
    flexDirection: 'row',
    alignItems: 'center',
    borderWidth: 1,
    borderColor: '#0984e3',
    paddingHorizontal: 15,
    paddingVertical: 8,
    borderRadius: 20,
    gap: 5,
  },
  addDocBtnText: {
    color: '#0984e3',
    fontSize: 12,
    fontWeight: '700',
  }
});
