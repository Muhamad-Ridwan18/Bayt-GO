import React, { useCallback, useState } from 'react';
import {
  View, Text, StyleSheet, ScrollView, Alert, Linking, Share,
} from 'react-native';
import { useFocusEffect } from '@react-navigation/native';
import {
  ChevronRight, ExternalLink, Eye, Share2,
} from 'lucide-react-native';
import * as ImagePicker from 'expo-image-picker';
import * as DocumentPicker from 'expo-document-picker';
import AuthInput from '../components/AuthInput';
import DatePickerField from '../components/DatePickerField';
import PhoneInternationalInput from '../components/PhoneInternationalInput';
import RepeatingTextField from '../components/RepeatingTextField';
import ScreenHeader from '../components/ScreenHeader';
import {
  fetchProfile, updatePublicProfile, uploadProfilePhoto, uploadProfileKtp,
  uploadSupportingDocument, deleteSupportingDocument,
} from '../api/profile';
import { useAuth } from '../context/AuthContext';
import { buildFullPhone, parsePhoneForInput } from '../utils/phoneCountries';
import { Button, Card, PressableScale, SkeletonList } from '../ui';
import { DocumentsSection, UploadField } from '../features/profile/EditMuthowifProfileParts';
import { colors, layout, radius, spacing, typography } from '../theme/tokens';
import { notifyError, notifySuccess, notifySuccessThen } from '../utils/feedback';
import { useLocale } from '../utils/locale';

function rowsFromList(items) {
  if (!items?.length) return [''];
  if (Array.isArray(items)) return items.map(String);
  return [String(items)];
}

function cleanRows(rows) {
  return (rows || []).map((s) => s.trim()).filter(Boolean);
}

export default function EditMuthowifProfileScreen({ navigation, route }) {
  const { token, updateLocalUser } = useAuth();
  const locale = useLocale(); const isEn = locale === 'en';
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
    if (user) { setName(user.name || ''); setEmail(user.email || ''); }
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
      setError(err.message || (isEn ? 'Failed to load public profile' : 'Gagal memuat profil publik'));
    } finally {
      setLoading(false);
    }
  }, [token, applyMuthowif]);

  useFocusEffect(useCallback(() => { load(); }, [load]));

  const pickImage = async () => {
    const permission = await ImagePicker.requestMediaLibraryPermissionsAsync();
    if (!permission.granted) {
      Alert.alert(isEn ? 'Permission required' : 'Izin diperlukan', isEn ? 'Allow gallery access to upload photos.' : 'Izinkan akses galeri untuk mengunggah foto.');
      return null;
    }
    const result = await ImagePicker.launchImageLibraryAsync({ mediaTypes: ['images'], quality: 0.85 });
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
      notifySuccess(data.message || (isEn ? 'Profile photo uploaded.' : 'Foto profil diunggah.'));
    } catch (err) {
      notifyError(err.message || (isEn ? 'Cannot upload photo' : 'Tidak dapat mengunggah foto'));
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
      notifySuccess(data.message || (isEn ? 'ID card uploaded.' : 'KTP diunggah.'));
    } catch (err) {
      notifyError(err.message || (isEn ? 'Cannot upload ID card' : 'Tidak dapat mengunggah KTP'));
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

    setUploadingDoc(true);
    try {
      const data = await uploadSupportingDocument(token, result.assets[0]);
      if (data.document) setDocuments((prev) => [...prev, data.document]);
      else await load();
      notifySuccess(data.message || (isEn ? 'Document uploaded.' : 'Dokumen diunggah.'));
    } catch (err) {
      notifyError(err.message || (isEn ? 'Cannot upload document' : 'Tidak dapat mengunggah dokumen'));
    } finally {
      setUploadingDoc(false);
    }
  };

  const handleDeleteDocument = (doc) => {
    Alert.alert(isEn ? 'Delete document?' : 'Hapus dokumen?', doc.name || (isEn ? 'This document will be permanently deleted.' : 'Dokumen ini akan dihapus permanen.'), [
      { text: isEn ? 'Cancel' : 'Batal', style: 'cancel' },
      {
        text: isEn ? 'Delete' : 'Hapus',
        style: 'destructive',
        onPress: async () => {
          setDeletingDocId(doc.id);
          try {
            await deleteSupportingDocument(token, doc.id);
            setDocuments((prev) => prev.filter((d) => d.id !== doc.id));
          } catch (err) {
            notifyError(err.message || (isEn ? 'Cannot delete document' : 'Tidak dapat menghapus dokumen'));
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
      if (data.user) await updateLocalUser({ name: data.user.name, email: data.user.email });
      notifySuccessThen(navigation, isEn ? 'Public profile updated successfully.' : 'Profil publik berhasil diperbarui.', () => navigation.goBack());
    } catch (err) {
      setError(err.message || (isEn ? 'Failed to save public profile' : 'Gagal menyimpan profil publik'));
    } finally {
      setSaving(false);
    }
  };

  const handleShareReferralCode = async () => {
    if (!referralCode) return;
    try { await Share.share({ message: referralCode }); } catch { /* dismissed */ }
  };

  const handlePreviewProfile = () => {
    if (publicProfileUrl) Linking.openURL(publicProfileUrl);
  };

  const handleShareProfile = async () => {
    if (!publicProfileUrl) return;
    try {
      await Share.share({
        message: isEn ? `Hello! I'm ${name || 'a muthowif'} on BaytGo. Check out my profile and services here: ${publicProfileUrl}` : `Halo! Saya ${name || 'saya'}, muthowif di BaytGo. Lihat profil dan layanan saya di sini: ${publicProfileUrl}`,
        url: publicProfileUrl,
      });
    } catch { /* dismissed */ }
  };

  return (
    <View style={styles.container}>
      <ScreenHeader
        variant="brand"
        title={isEn ? 'Public profile' : 'Profil publik'}
        subtitle={isEn ? 'Info shown on marketplace' : 'Info tampil di marketplace'}
        onBack={() => navigation.goBack()}
        rightAction={
          publicProfileUrl ? (
            <PressableScale onPress={handlePreviewProfile} haptic="light" style={styles.previewBtn}>
              <ExternalLink size={20} color={colors.white} strokeWidth={2} />
            </PressableScale>
          ) : null
        }
      />

      {loading ? (
        <SkeletonList count={4} style={styles.skeleton} />
      ) : (
        <ScrollView contentContainerStyle={styles.form} keyboardShouldPersistTaps="handled">
          {error ? (
            <Card style={styles.errorCard} padding={spacing.md} elevated={false}>
              <Text style={styles.errorText}>{error}</Text>
            </Card>
          ) : null}

          {publicProfileUrl ? (
            <>
              <PressableScale onPress={handlePreviewProfile} haptic="light">
                <Card style={styles.previewCard} padding={spacing.lg} elevated={false}>
                  <Eye size={18} color={colors.baytgo} strokeWidth={2} />
                  <Text style={styles.previewCardText}>{isEn ? 'Preview public profile' : 'Preview profil publik'}</Text>
                  <ChevronRight size={16} color={colors.textMuted} strokeWidth={2} />
                </Card>
              </PressableScale>
              <PressableScale onPress={handleShareProfile} haptic="light">
                <Card style={styles.previewCard} padding={spacing.lg} elevated={false}>
                  <Share2 size={18} color={colors.baytgo} strokeWidth={2} />
                  <Text style={styles.previewCardText}>{isEn ? 'Share public profile' : 'Bagikan profil publik'}</Text>
                  <ChevronRight size={16} color={colors.textMuted} strokeWidth={2} />
                </Card>
              </PressableScale>
            </>
          ) : verificationStatus !== 'approved' ? (
            <Card style={styles.infoCard} padding={spacing.lg} elevated={false}>
              <Text style={styles.infoCardText}>
                {isEn ? 'Profile preview available after admin approves your muthowif account.' : 'Preview profil tersedia setelah admin menyetujui akun muthowif Anda.'}
              </Text>
            </Card>
          ) : null}

          <Text style={styles.sectionTitle}>{isEn ? 'Verification documents' : 'Dokumen verifikasi'}</Text>
          <UploadField label={isEn ? 'Profile photo' : 'Foto profil'} imageUrl={photoUrl} uploading={uploadingPhoto} onPick={handleUploadPhoto} />
          <UploadField label={isEn ? 'ID card photo' : 'Foto KTP'} imageUrl={ktpUrl} uploading={uploadingKtp} onPick={handleUploadKtp} />

          <DocumentsSection
            documents={documents}
            uploadingDoc={uploadingDoc}
            onAdd={handleAddDocument}
            onDelete={handleDeleteDocument}
            deletingDocId={deletingDocId}
          />

          <Text style={styles.sectionTitle}>{isEn ? 'Public information' : 'Informasi publik'}</Text>
          <AuthInput label={isEn ? 'Full name' : 'Nama lengkap'} icon="person-outline" value={name} onChangeText={setName} />
          <AuthInput label={isEn ? 'Email' : 'Email'} icon="mail-outline" value={email} onChangeText={setEmail} keyboardType="email-address" autoCapitalize="none" />
          <PhoneInternationalInput label={isEn ? 'Phone / WhatsApp' : 'Telepon / WhatsApp'} dial={phoneDial} national={phoneNational} countryIso={phoneCountryIso} onChange={handlePhoneChange} />
          <AuthInput label={isEn ? 'Passport number' : 'Nomor paspor'} icon="airplane-outline" value={passportNumber} onChangeText={setPassportNumber} />
          <DatePickerField label={isEn ? 'Date of birth' : 'Tanggal lahir'} value={birthDate} onChange={setBirthDate} placeholder={isEn ? 'Select date of birth' : 'Pilih tanggal lahir'} maximumDate={new Date()} />
          <AuthInput label={isEn ? 'Address' : 'Alamat'} icon="location-outline" value={address} onChangeText={setAddress} placeholder={isEn ? 'Home address' : 'Alamat domisili'} multiline />
          <AuthInput label={isEn ? 'Work location' : 'Lokasi kerja'} icon="business-outline" value={workLocation} onChangeText={setWorkLocation} placeholder={isEn ? 'E.g.: Makkah, Madinah' : 'Contoh: Makkah, Madinah'} />
          <RepeatingTextField label={isEn ? 'Languages' : 'Bahasa'} items={languages} onChange={setLanguages} placeholder={isEn ? 'E.g.: Arabic (fluent), English' : 'Contoh: Arab (fasih), Inggris'} addLabel={isEn ? 'Add language' : 'Tambah bahasa'} />
          <RepeatingTextField label={isEn ? 'Education' : 'Pendidikan'} items={educations} onChange={setEducations} placeholder={isEn ? 'Study or formal education history' : 'Riwayat studi atau pendidikan formal'} addLabel={isEn ? 'Add education' : 'Tambah pendidikan'} optional />
          <RepeatingTextField label={isEn ? 'Work experience' : 'Pengalaman kerja'} items={workExperiences} onChange={setWorkExperiences} placeholder={isEn ? 'Experience as muthowif' : 'Pengalaman sebagai muthowif'} addLabel={isEn ? 'Add experience' : 'Tambah pengalaman'} />
          <AuthInput label={isEn ? 'Reference / bio' : 'Referensi / bio'} icon="document-text-outline" value={referenceText} onChangeText={setReferenceText} placeholder={isEn ? 'Tell about your experience' : 'Ceritakan pengalaman Anda'} multiline />

          {!hasInviter ? (
            <AuthInput label={isEn ? 'Inviter referral code' : 'Kode referral pengundang'} icon="gift-outline" value={inviterCode} onChangeText={setInviterCode} placeholder={isEn ? 'Optional' : 'Opsional'} />
          ) : (
            <Card style={styles.lockedCard} padding={spacing.lg} elevated={false}>
              <Text style={styles.lockedTitle}>{isEn ? 'You are connected with the inviting muthowif' : 'Anda terhubung dengan muthowif pengundang'}</Text>
              <Text style={styles.lockedValue}>
                {inviterName || '—'}
                {inviterCode ? ` · ${inviterCode}` : ''}
              </Text>
              <Text style={styles.lockedHint}>{isEn ? 'Inviter code can only be saved once and has been recorded.' : 'Kode pengundang hanya bisa disimpan sekali dan sudah tercatat.'}</Text>
            </Card>
          )}

          <Card style={styles.referralCard} padding={spacing.lg} elevated={false}>
            <Text style={styles.referralTitle}>{isEn ? 'Your referral code' : 'Kode referral Anda'}</Text>
            {referralCode ? (
              <>
                <Text style={styles.referralCode}>{referralCode}</Text>
                <Text style={styles.referralHint}>{isEn ? 'Share this code when a colleague registers as muthowif.' : 'Bagikan kode ini saat rekan mendaftar sebagai muthowif.'}</Text>
                <PressableScale onPress={handleShareReferralCode} haptic="light" style={styles.shareBtn}>
                  <Share2 size={16} color={colors.baytgo} strokeWidth={2} />
                  <Text style={styles.shareBtnText}>{isEn ? 'Share code' : 'Bagikan kode'}</Text>
                </PressableScale>
              </>
            ) : (
              <Text style={styles.referralPending}>
                {isEn ? 'Your code will be available after the admin approves your muthowif profile.' : 'Kode Anda akan tersedia setelah profil muthowif disetujui admin.'}
              </Text>
            )}
          </Card>

          <Button label={isEn ? 'Save information' : 'Simpan informasi'} onPress={handleSave} loading={saving} />
        </ScrollView>
      )}
    </View>
  );
}

const styles = StyleSheet.create({
  container: { flex: 1, backgroundColor: colors.background },
  skeleton: { padding: layout.screenPadding, paddingTop: spacing.lg },
  form: { padding: layout.screenPadding, paddingBottom: spacing['3xl'] },
  errorCard: { backgroundColor: colors.errorLight, borderColor: '#FECACA', marginBottom: spacing.md },
  errorText: { ...typography.caption, color: colors.error, fontWeight: '600' },
  sectionTitle: { ...typography.caption, fontFamily: 'PlusJakartaSans_800ExtraBold', color: colors.baytgo, marginBottom: spacing.md, marginTop: spacing.sm },
  previewBtn: {
    width: 44,
    height: 44,
    borderRadius: radius.sm,
    backgroundColor: colors.card,
    alignItems: 'center',
    justifyContent: 'center',
    borderWidth: 1,
    borderColor: colors.border,
  },
  previewCard: { flexDirection: 'row', alignItems: 'center', gap: spacing.md, marginBottom: spacing.md },
  previewCardText: { flex: 1, ...typography.caption, fontFamily: 'PlusJakartaSans_800ExtraBold', color: colors.baytgo },
  infoCard: { backgroundColor: colors.warningLight, borderColor: '#FDE68A', marginBottom: spacing.md },
  infoCardText: { ...typography.caption, lineHeight: 20, color: '#92400E', fontWeight: '600' },
  lockedCard: { marginBottom: spacing.md },
  lockedTitle: { ...typography.label, color: colors.textSecondary },
  lockedValue: { marginTop: spacing.sm, ...typography.caption, fontFamily: 'PlusJakartaSans_800ExtraBold', color: colors.textPrimary },
  lockedHint: { marginTop: spacing.sm, ...typography.small, lineHeight: 16, color: colors.textSecondary, fontWeight: '500' },
  referralCard: { marginBottom: spacing.lg },
  referralTitle: { ...typography.label, color: colors.textSecondary },
  referralCode: { marginTop: spacing.md, fontSize: 22, fontWeight: '900', letterSpacing: 2, color: colors.baytgo, fontVariant: ['tabular-nums'] },
  referralHint: { marginTop: spacing.sm, ...typography.small, lineHeight: 18, color: colors.textSecondary, fontWeight: '500' },
  referralPending: { marginTop: spacing.sm, ...typography.caption, lineHeight: 20, color: colors.textSecondary, fontWeight: '600' },
  shareBtn: { flexDirection: 'row', alignItems: 'center', gap: 6, marginTop: spacing.md },
  shareBtnText: { ...typography.caption, fontFamily: 'PlusJakartaSans_800ExtraBold', color: colors.baytgo },
});
