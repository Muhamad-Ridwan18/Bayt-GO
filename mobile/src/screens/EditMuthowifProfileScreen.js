import React, { useCallback, useState } from 'react';
import {
  View,
  Text,
  StyleSheet,
  ScrollView,
  TouchableOpacity,
  ActivityIndicator,
  Alert,
  Image,
  Linking,
  Share,
} from 'react-native';
import { LinearGradient } from 'expo-linear-gradient';
import { useFocusEffect } from '@react-navigation/native';
import * as ImagePicker from 'expo-image-picker';
import * as DocumentPicker from 'expo-document-picker';
import { Ionicons } from '@expo/vector-icons';
import AuthInput from '../components/AuthInput';
import DatePickerField from '../components/DatePickerField';
import PhoneInternationalInput from '../components/PhoneInternationalInput';
import RepeatingTextField from '../components/RepeatingTextField';
import ScreenHeader from '../components/ScreenHeader';
import {
  fetchProfile,
  updatePublicProfile,
  uploadProfilePhoto,
  uploadProfileKtp,
  uploadSupportingDocument,
  deleteSupportingDocument,
} from '../api/profile';
import { useAuth } from '../context/AuthContext';
import { DEFAULT_PHONE_COUNTRY, buildFullPhone, parsePhoneForInput } from '../utils/phoneCountries';
import { resolveMediaUrl } from '../utils/mediaUrl';
import { colors } from '../theme/colors';

function rowsFromList(items) {
  if (!items?.length) return [''];
  if (Array.isArray(items)) return items.map(String);
  return [String(items)];
}

function cleanRows(rows) {
  return (rows || []).map((s) => s.trim()).filter(Boolean);
}

function UploadField({ label, imageUrl, localUri, uploading, onPick }) {
  const source = localUri ? { uri: localUri } : imageUrl ? { uri: imageUrl } : null;

  return (
    <View style={styles.uploadField}>
      <Text style={styles.uploadLabel}>{label}</Text>
      <TouchableOpacity style={styles.uploadBtn} onPress={onPick} disabled={uploading} activeOpacity={0.9}>
        {source ? (
          <Image source={source} style={styles.uploadPreview} />
        ) : (
          <View style={styles.uploadPlaceholder}>
            <Ionicons name="camera-outline" size={28} color={colors.slate400} />
            <Text style={styles.uploadPlaceholderText}>Pilih foto</Text>
          </View>
        )}
        {uploading ? (
          <View style={styles.uploadOverlay}>
            <ActivityIndicator color={colors.white} />
          </View>
        ) : null}
      </TouchableOpacity>
    </View>
  );
}

function DocumentRow({ doc, onDelete, deleting }) {
  return (
    <View style={styles.docRow}>
      {doc.url ? (
        <Image source={{ uri: resolveMediaUrl(doc.url) }} style={styles.docThumb} />
      ) : (
        <View style={[styles.docThumb, styles.docThumbPlaceholder]}>
          <Ionicons name="document-outline" size={20} color={colors.slate400} />
        </View>
      )}
      <Text style={styles.docName} numberOfLines={2}>
        {doc.name || 'Dokumen pendukung'}
      </Text>
      <TouchableOpacity onPress={() => onDelete(doc)} disabled={deleting} hitSlop={8}>
        <Ionicons name="trash-outline" size={20} color="#B91C1C" />
      </TouchableOpacity>
    </View>
  );
}

export default function EditMuthowifProfileScreen({ navigation, route }) {
  const { token, updateLocalUser } = useAuth();
  const initialMuthowif = route.params?.profile?.muthowif;
  const initialUser = route.params?.profile?.user;
  const initialPhone = parsePhoneForInput(initialMuthowif?.phone);

  const [loading, setLoading] = useState(true);
  const [saving, setSaving] = useState(false);
  const [error, setError] = useState('');
  const [name, setName] = useState(initialUser?.name || '');
  const [email, setEmail] = useState(initialUser?.email || '');
  const [phoneDial, setPhoneDial] = useState(initialPhone.dial);
  const [phoneNational, setPhoneNational] = useState(initialPhone.national);
  const [phoneCountryIso, setPhoneCountryIso] = useState(initialPhone.countryIso);
  const [phone, setPhone] = useState(initialMuthowif?.phone || '');
  const [address, setAddress] = useState(initialMuthowif?.address || '');
  const [workLocation, setWorkLocation] = useState(initialMuthowif?.work_location || '');
  const [referenceText, setReferenceText] = useState(initialMuthowif?.reference_text || '');
  const [languages, setLanguages] = useState(rowsFromList(initialMuthowif?.languages));
  const [passportNumber, setPassportNumber] = useState(initialMuthowif?.passport_number || '');
  const [birthDate, setBirthDate] = useState(initialMuthowif?.birth_date || '');
  const [educations, setEducations] = useState(rowsFromList(initialMuthowif?.educations));
  const [workExperiences, setWorkExperiences] = useState(rowsFromList(initialMuthowif?.work_experiences));
  const [inviterCode, setInviterCode] = useState('');
  const [inviterName, setInviterName] = useState('');
  const [hasInviter, setHasInviter] = useState(!!initialMuthowif?.referred_by_muthowif_profile_id);
  const [referralCode, setReferralCode] = useState(initialMuthowif?.referral_code || '');
  const [publicProfileUrl, setPublicProfileUrl] = useState(initialMuthowif?.public_profile_url || null);
  const [verificationStatus, setVerificationStatus] = useState(initialMuthowif?.verification_status || null);
  const [photoUrl, setPhotoUrl] = useState(initialMuthowif?.photo_url || null);
  const [ktpUrl, setKtpUrl] = useState(initialMuthowif?.ktp_url || null);
  const [documents, setDocuments] = useState(initialMuthowif?.supporting_documents || []);
  const [uploadingPhoto, setUploadingPhoto] = useState(false);
  const [uploadingKtp, setUploadingKtp] = useState(false);
  const [uploadingDoc, setUploadingDoc] = useState(false);
  const [deletingDocId, setDeletingDocId] = useState(null);

  const applyMuthowif = useCallback((muthowif, user) => {
    if (!muthowif) return;
    if (user) {
      setName(user.name || '');
      setEmail(user.email || '');
    }
    const parsedPhone = parsePhoneForInput(muthowif.phone);
    setPhoneDial(parsedPhone.dial);
    setPhoneNational(parsedPhone.national);
    setPhoneCountryIso(parsedPhone.countryIso);
    setPhone(muthowif.phone || '');
    setAddress(muthowif.address || '');
    setWorkLocation(muthowif.work_location || '');
    setReferenceText(muthowif.reference_text || '');
    setLanguages(rowsFromList(muthowif.languages));
    setPassportNumber(muthowif.passport_number || '');
    setBirthDate(muthowif.birth_date || '');
    setEducations(rowsFromList(muthowif.educations));
    setWorkExperiences(rowsFromList(muthowif.work_experiences));
    setHasInviter(!!muthowif.referred_by_muthowif_profile_id);
    setInviterCode(muthowif.inviter_referral_code || '');
    setInviterName(muthowif.inviter_name || '');
    setReferralCode(muthowif.referral_code || '');
    setPublicProfileUrl(muthowif.public_profile_url || null);
    setVerificationStatus(muthowif.verification_status || null);
    setPhotoUrl(muthowif.photo_url || null);
    setKtpUrl(muthowif.ktp_url || null);
    setDocuments(muthowif.supporting_documents || []);
  }, []);

  const load = useCallback(async () => {
    setLoading(true);
    try {
      const data = await fetchProfile(token);
      applyMuthowif(data.muthowif, data.user);
      setError('');
    } catch (err) {
      setError(err.message || 'Gagal memuat profil publik');
    } finally {
      setLoading(false);
    }
  }, [token, applyMuthowif]);

  useFocusEffect(
    useCallback(() => {
      load();
    }, [load]),
  );

  const pickImage = async () => {
    const permission = await ImagePicker.requestMediaLibraryPermissionsAsync();
    if (!permission.granted) {
      Alert.alert('Izin diperlukan', 'Izinkan akses galeri untuk mengunggah foto.');
      return null;
    }

    const result = await ImagePicker.launchImageLibraryAsync({
      mediaTypes: ['images'],
      quality: 0.85,
    });

    if (result.canceled || !result.assets?.[0]) return null;
    return result.assets[0];
  };

  const handleUploadPhoto = async () => {
    const asset = await pickImage();
    if (!asset) return;

    setUploadingPhoto(true);
    try {
      const data = await uploadProfilePhoto(token, asset);
      setPhotoUrl(data.photo_url || photoUrl);
      Alert.alert('Berhasil', data.message || 'Foto profil diunggah.');
    } catch (err) {
      Alert.alert('Gagal', err.message || 'Tidak dapat mengunggah foto');
    } finally {
      setUploadingPhoto(false);
    }
  };

  const handleUploadKtp = async () => {
    const asset = await pickImage();
    if (!asset) return;

    setUploadingKtp(true);
    try {
      const data = await uploadProfileKtp(token, asset);
      setKtpUrl(data.ktp_url || ktpUrl);
      Alert.alert('Berhasil', data.message || 'KTP diunggah.');
    } catch (err) {
      Alert.alert('Gagal', err.message || 'Tidak dapat mengunggah KTP');
    } finally {
      setUploadingKtp(false);
    }
  };

  const handlePhoneChange = ({ dial, national, countryIso, fullPhone }) => {
    setPhoneDial(dial);
    setPhoneNational(national);
    setPhoneCountryIso(countryIso);
    setPhone(fullPhone || buildFullPhone(dial, national));
  };

  const handleAddDocument = async () => {
    const result = await DocumentPicker.getDocumentAsync({
      type: ['application/pdf', 'image/*'],
      copyToCacheDirectory: true,
      multiple: false,
    });
    if (result.canceled || !result.assets?.[0]) return;

    const asset = result.assets[0];
    setUploadingDoc(true);
    try {
      const data = await uploadSupportingDocument(token, asset);
      if (data.document) {
        setDocuments((prev) => [...prev, data.document]);
      } else {
        await load();
      }
      Alert.alert('Berhasil', data.message || 'Dokumen diunggah.');
    } catch (err) {
      Alert.alert('Gagal', err.message || 'Tidak dapat mengunggah dokumen');
    } finally {
      setUploadingDoc(false);
    }
  };

  const handleDeleteDocument = (doc) => {
    Alert.alert('Hapus dokumen?', doc.name || 'Dokumen ini akan dihapus permanen.', [
      { text: 'Batal', style: 'cancel' },
      {
        text: 'Hapus',
        style: 'destructive',
        onPress: async () => {
          setDeletingDocId(doc.id);
          try {
            await deleteSupportingDocument(token, doc.id);
            setDocuments((prev) => prev.filter((d) => d.id !== doc.id));
          } catch (err) {
            Alert.alert('Gagal', err.message || 'Tidak dapat menghapus dokumen');
          } finally {
            setDeletingDocId(null);
          }
        },
      },
    ]);
  };

  const handleSave = async () => {
    setSaving(true);
    setError('');
    try {
      const fullPhone = phone || buildFullPhone(phoneDial, phoneNational);
      const data = await updatePublicProfile(token, {
        name: name.trim(),
        email: email.trim(),
        phone: fullPhone.trim() || null,
        address: address.trim() || null,
        work_location: workLocation.trim() || null,
        reference_text: referenceText.trim() || null,
        languages: cleanRows(languages),
        passport_number: passportNumber.trim() || null,
        birth_date: birthDate.trim() || null,
        educations: cleanRows(educations),
        work_experiences: cleanRows(workExperiences),
        ...(hasInviter ? {} : { inviter_referral_code: inviterCode.trim() || null }),
      });
      if (data.user) {
        await updateLocalUser({ name: data.user.name, email: data.user.email });
      }
      Alert.alert('Berhasil', 'Profil publik berhasil diperbarui.', [
        { text: 'OK', onPress: () => navigation.goBack() },
      ]);
    } catch (err) {
      setError(err.message || 'Gagal menyimpan profil publik');
    } finally {
      setSaving(false);
    }
  };

  const handleShareReferralCode = async () => {
    if (!referralCode) return;
    try {
      await Share.share({ message: referralCode });
    } catch {
      // user dismissed
    }
  };

  const handlePreviewProfile = () => {
    if (!publicProfileUrl) return;
    Linking.openURL(publicProfileUrl);
  };

  return (
    <View style={styles.container}>
      <ScreenHeader
        title="Profil publik"
        subtitle="Info tampil di marketplace"
        onBack={() => navigation.goBack()}
        rightAction={
          publicProfileUrl ? (
            <TouchableOpacity style={styles.previewBtn} onPress={handlePreviewProfile} hitSlop={8}>
              <Ionicons name="open-outline" size={20} color={colors.baytgo} />
            </TouchableOpacity>
          ) : null
        }
      />

      {loading ? (
        <ActivityIndicator color={colors.baytgo} style={styles.loader} />
      ) : (
        <ScrollView contentContainerStyle={styles.form} keyboardShouldPersistTaps="handled">
          {error ? <Text style={styles.error}>{error}</Text> : null}

          {publicProfileUrl ? (
            <TouchableOpacity style={styles.previewCard} onPress={handlePreviewProfile} activeOpacity={0.9}>
              <Ionicons name="eye-outline" size={18} color={colors.baytgo} />
              <Text style={styles.previewCardText}>Preview profil publik</Text>
              <Ionicons name="chevron-forward" size={16} color={colors.slate400} />
            </TouchableOpacity>
          ) : verificationStatus !== 'approved' ? (
            <View style={styles.infoCard}>
              <Text style={styles.infoCardText}>
                Preview profil tersedia setelah admin menyetujui akun muthowif Anda.
              </Text>
            </View>
          ) : null}

          <Text style={styles.sectionTitle}>Dokumen verifikasi</Text>
          <UploadField
            label="Foto profil"
            imageUrl={photoUrl}
            uploading={uploadingPhoto}
            onPick={handleUploadPhoto}
          />
          <UploadField
            label="Foto KTP"
            imageUrl={ktpUrl}
            uploading={uploadingKtp}
            onPick={handleUploadKtp}
          />

          <View style={styles.docsSection}>
            <View style={styles.docsHeader}>
              <Text style={styles.uploadLabel}>Dokumen pendukung</Text>
              <TouchableOpacity
                style={styles.addDocBtn}
                onPress={handleAddDocument}
                disabled={uploadingDoc}
              >
                {uploadingDoc ? (
                  <ActivityIndicator color={colors.baytgo} size="small" />
                ) : (
                  <>
                    <Ionicons name="add-circle-outline" size={18} color={colors.baytgo} />
                    <Text style={styles.addDocText}>Tambah</Text>
                  </>
                )}
              </TouchableOpacity>
            </View>
            {documents.length === 0 ? (
              <Text style={styles.docsEmpty}>Belum ada dokumen pendukung.</Text>
            ) : (
              documents.map((doc) => (
                <DocumentRow
                  key={String(doc.id)}
                  doc={doc}
                  onDelete={handleDeleteDocument}
                  deleting={deletingDocId === doc.id}
                />
              ))
            )}
          </View>

          <Text style={styles.sectionTitle}>Informasi publik</Text>
          <AuthInput label="Nama lengkap" icon="person-outline" value={name} onChangeText={setName} />
          <AuthInput
            label="Email"
            icon="mail-outline"
            value={email}
            onChangeText={setEmail}
            keyboardType="email-address"
            autoCapitalize="none"
          />
          <PhoneInternationalInput
            label="Telepon / WhatsApp"
            dial={phoneDial}
            national={phoneNational}
            countryIso={phoneCountryIso}
            onChange={handlePhoneChange}
          />
          <AuthInput
            label="Nomor paspor"
            icon="airplane-outline"
            value={passportNumber}
            onChangeText={setPassportNumber}
          />
          <DatePickerField
            label="Tanggal lahir"
            value={birthDate}
            onChange={setBirthDate}
            placeholder="Pilih tanggal lahir"
            maximumDate={new Date()}
          />
          <AuthInput
            label="Alamat"
            icon="location-outline"
            value={address}
            onChangeText={setAddress}
            placeholder="Alamat domisili"
            multiline
          />
          <AuthInput
            label="Lokasi kerja"
            icon="business-outline"
            value={workLocation}
            onChangeText={setWorkLocation}
            placeholder="Contoh: Makkah, Madinah"
          />
          <RepeatingTextField
            label="Bahasa"
            items={languages}
            onChange={setLanguages}
            placeholder="Contoh: Arab (fasih), Inggris"
            addLabel="Tambah bahasa"
          />
          <RepeatingTextField
            label="Pendidikan"
            items={educations}
            onChange={setEducations}
            placeholder="Riwayat studi atau pendidikan formal"
            addLabel="Tambah pendidikan"
            optional
          />
          <RepeatingTextField
            label="Pengalaman kerja"
            items={workExperiences}
            onChange={setWorkExperiences}
            placeholder="Pengalaman sebagai muthowif"
            addLabel="Tambah pengalaman"
          />
          <AuthInput
            label="Referensi / bio"
            icon="document-text-outline"
            value={referenceText}
            onChangeText={setReferenceText}
            placeholder="Ceritakan pengalaman Anda"
            multiline
          />

          {!hasInviter ? (
            <AuthInput
              label="Kode referral pengundang"
              icon="gift-outline"
              value={inviterCode}
              onChangeText={setInviterCode}
              placeholder="Opsional"
            />
          ) : (
            <View style={styles.lockedCard}>
              <Text style={styles.lockedTitle}>Anda terhubung dengan muthowif pengundang</Text>
              <Text style={styles.lockedValue}>
                {inviterName || '—'}
                {inviterCode ? ` · ${inviterCode}` : ''}
              </Text>
              <Text style={styles.lockedHint}>
                Kode pengundang hanya bisa disimpan sekali dan sudah tercatat.
              </Text>
            </View>
          )}

          <View style={styles.referralCard}>
            <Text style={styles.referralTitle}>Kode referral Anda</Text>
            {referralCode ? (
              <>
                <Text style={styles.referralCode}>{referralCode}</Text>
                <Text style={styles.referralHint}>
                  Bagikan kode ini saat rekan mendaftar sebagai muthowif.
                </Text>
                <TouchableOpacity style={styles.shareBtn} onPress={handleShareReferralCode}>
                  <Ionicons name="share-outline" size={16} color={colors.baytgo} />
                  <Text style={styles.shareBtnText}>Bagikan kode</Text>
                </TouchableOpacity>
              </>
            ) : (
              <Text style={styles.referralPending}>
                Kode Anda akan tersedia setelah profil muthowif disetujui admin.
              </Text>
            )}
          </View>

          <TouchableOpacity style={styles.saveBtn} onPress={handleSave} disabled={saving} activeOpacity={0.9}>
            <LinearGradient colors={[colors.baytgo, colors.baytgoDark]} style={styles.saveGradient}>
              {saving ? (
                <ActivityIndicator color={colors.white} />
              ) : (
                <Text style={styles.saveText}>Simpan informasi</Text>
              )}
            </LinearGradient>
          </TouchableOpacity>
        </ScrollView>
      )}
    </View>
  );
}

const styles = StyleSheet.create({
  container: { flex: 1, backgroundColor: colors.canvas },
  loader: { marginTop: 40 },
  form: { padding: 20, paddingBottom: 32 },
  error: {
    backgroundColor: '#FEF2F2',
    color: '#B91C1C',
    padding: 12,
    borderRadius: 12,
    fontSize: 13,
    fontWeight: '600',
    marginBottom: 12,
  },
  sectionTitle: {
    fontSize: 16,
    fontWeight: '900',
    color: colors.baytgo,
    marginBottom: 12,
    marginTop: 8,
  },
  uploadField: { marginBottom: 16 },
  uploadLabel: { fontSize: 12, fontWeight: '800', color: colors.slate600, marginBottom: 8 },
  uploadBtn: {
    borderRadius: 16,
    overflow: 'hidden',
    borderWidth: 1,
    borderColor: colors.slate200,
    backgroundColor: colors.white,
  },
  uploadPreview: { width: '100%', height: 180, backgroundColor: colors.slate100 },
  uploadPlaceholder: {
    height: 140,
    alignItems: 'center',
    justifyContent: 'center',
    gap: 8,
  },
  uploadPlaceholderText: { fontSize: 13, fontWeight: '700', color: colors.slate500 },
  uploadOverlay: {
    ...StyleSheet.absoluteFillObject,
    backgroundColor: 'rgba(0,0,0,0.35)',
    alignItems: 'center',
    justifyContent: 'center',
  },
  docsSection: {
    backgroundColor: colors.white,
    borderRadius: 16,
    padding: 14,
    marginBottom: 16,
    borderWidth: 1,
    borderColor: colors.slate100,
  },
  docsHeader: {
    flexDirection: 'row',
    alignItems: 'center',
    justifyContent: 'space-between',
    marginBottom: 10,
  },
  addDocBtn: { flexDirection: 'row', alignItems: 'center', gap: 4 },
  addDocText: { fontSize: 13, fontWeight: '800', color: colors.baytgo },
  docsEmpty: { fontSize: 13, color: colors.slate500, fontWeight: '600' },
  docRow: {
    flexDirection: 'row',
    alignItems: 'center',
    gap: 10,
    paddingVertical: 8,
    borderTopWidth: 1,
    borderTopColor: colors.slate100,
  },
  docThumb: { width: 44, height: 44, borderRadius: 10, backgroundColor: colors.slate100 },
  docThumbPlaceholder: { alignItems: 'center', justifyContent: 'center' },
  docName: { flex: 1, fontSize: 13, fontWeight: '600', color: colors.slate700 },
  hint: {
    marginTop: -4,
    marginBottom: 12,
    fontSize: 11,
    fontWeight: '600',
    color: colors.slate500,
  },
  saveBtn: { marginTop: 16, borderRadius: 16, overflow: 'hidden' },
  saveGradient: { paddingVertical: 16, alignItems: 'center' },
  saveText: { color: colors.white, fontSize: 15, fontWeight: '800' },
  previewBtn: {
    width: 40,
    height: 40,
    borderRadius: 12,
    backgroundColor: colors.white,
    alignItems: 'center',
    justifyContent: 'center',
    borderWidth: 1,
    borderColor: colors.slate100,
  },
  previewCard: {
    flexDirection: 'row',
    alignItems: 'center',
    gap: 10,
    backgroundColor: colors.white,
    borderRadius: 14,
    padding: 14,
    marginBottom: 12,
    borderWidth: 1,
    borderColor: colors.slate100,
  },
  previewCardText: { flex: 1, fontSize: 14, fontWeight: '800', color: colors.baytgo },
  infoCard: {
    backgroundColor: '#FFFBEB',
    borderRadius: 14,
    padding: 14,
    marginBottom: 12,
    borderWidth: 1,
    borderColor: '#FDE68A',
  },
  infoCardText: { fontSize: 13, lineHeight: 20, color: '#92400E', fontWeight: '600' },
  lockedCard: {
    backgroundColor: colors.white,
    borderRadius: 14,
    padding: 14,
    marginBottom: 14,
    borderWidth: 1,
    borderColor: colors.slate100,
  },
  lockedTitle: { fontSize: 12, fontWeight: '800', color: colors.slate600 },
  lockedValue: { marginTop: 8, fontSize: 15, fontWeight: '800', color: colors.slate900 },
  lockedHint: { marginTop: 6, fontSize: 11, lineHeight: 16, color: colors.slate500, fontWeight: '600' },
  referralCard: {
    backgroundColor: colors.white,
    borderRadius: 14,
    padding: 14,
    marginBottom: 16,
    borderWidth: 1,
    borderColor: colors.slate100,
  },
  referralTitle: { fontSize: 12, fontWeight: '800', color: colors.slate600 },
  referralCode: {
    marginTop: 10,
    fontSize: 22,
    fontWeight: '900',
    letterSpacing: 2,
    color: colors.baytgo,
    fontVariant: ['tabular-nums'],
  },
  referralHint: { marginTop: 8, fontSize: 12, lineHeight: 18, color: colors.slate600, fontWeight: '500' },
  referralPending: { marginTop: 8, fontSize: 13, lineHeight: 20, color: colors.slate600, fontWeight: '600' },
  shareBtn: { flexDirection: 'row', alignItems: 'center', gap: 6, marginTop: 12 },
  shareBtnText: { fontSize: 13, fontWeight: '800', color: colors.baytgo },
});
