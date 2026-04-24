import React, { useState, useEffect } from 'react';
import { 
  StyleSheet, 
  Text, 
  View, 
  TouchableOpacity, 
  ScrollView, 
  TextInput,
  ActivityIndicator,
  Alert,
  Dimensions,
  Image,
  KeyboardAvoidingView,
  Platform
} from 'react-native';
import { SafeAreaView } from 'react-native-safe-area-context';
import { StatusBar } from 'expo-status-bar';
import { apiClient } from '../api/client';
import * as ImagePicker from 'expo-image-picker';
import SwipeableScreen from '../components/SwipeableScreen';

const { width } = Dimensions.get('window');

export default function ProfileScreen({ user, onLogout, navigation }) {
  const [loading, setLoading] = useState(true);
  const [updating, setUpdating] = useState(false);
  const [activeTab, setActiveTab] = useState('account'); // 'account', 'public', 'skills'
  
  // Account States
  const [name, setName] = useState('');
  const [email, setEmail] = useState('');
  
  // Public Profile States
  const [publicPhone, setPublicPhone] = useState('');
  const [passport, setPassport] = useState('');
  const [birthDate, setBirthDate] = useState('');
  const [address, setAddress] = useState('');
  const [bio, setBio] = useState('');
  const [photoUrl, setPhotoUrl] = useState(null);
  const [ktpUrl, setKtpUrl] = useState(null);
  const [supportingDocs, setSupportingDocs] = useState([]);
  
  // Skills States (Repeating Fields)
  const [languages, setLanguages] = useState(['']);
  const [educations, setEducations] = useState(['']);
  const [experiences, setExperiences] = useState(['']);

  const fetchProfileData = async () => {
    try {
      const data = await apiClient.getProfile(user.token);
      setName(data.user.name);
      setEmail(data.user.email);
      
      if (data.muthowif) {
        setPublicPhone(data.muthowif.phone || '');
        setPassport(data.muthowif.passport_number || '');
        setBirthDate(data.muthowif.birth_date || '');
        setAddress(data.muthowif.address || '');
        setBio(data.muthowif.reference_text || '');
        setPhotoUrl(data.muthowif.photo_url || null);
        setKtpUrl(data.muthowif.ktp_url || null);
        setSupportingDocs(data.muthowif.supporting_documents || []);
        setLanguages(data.muthowif.languages.length > 0 ? data.muthowif.languages : ['']);
        setEducations(data.muthowif.educations.length > 0 ? data.muthowif.educations : ['']);
        setExperiences(data.muthowif.work_experiences.length > 0 ? data.muthowif.work_experiences : ['']);
      }
    } catch (error) {
      console.error('Fetch Profile Error:', error);
    } finally {
      setLoading(false);
    }
  };

  useEffect(() => {
    fetchProfileData();
  }, []);

  const handleUpdateAccount = async () => {
    setUpdating(true);
    try {
      await apiClient.updateProfile(user.token, { name, email });
      Alert.alert('Sukses', 'Informasi akun berhasil diperbarui');
    } catch (error) {
      Alert.alert('Error', error.message || 'Gagal memperbarui akun');
    } finally {
      setUpdating(false);
    }
  };

  const handleUpdatePublic = async () => {
    setUpdating(true);
    try {
      await apiClient.updatePublicProfile(user.token, {
        phone: publicPhone,
        passport_number: passport,
        birth_date: birthDate,
        address: address,
        reference_text: bio,
        languages: languages.filter(i => i.trim() !== ''),
        educations: educations.filter(i => i.trim() !== ''),
        work_experiences: experiences.filter(i => i.trim() !== ''),
      });
      Alert.alert('Sukses', 'Profil publik berhasil diperbarui');
    } catch (error) {
      Alert.alert('Error', error.message || 'Gagal memperbarui profil');
    } finally {
      setUpdating(false);
    }
  };

  const pickPhoto = async () => {
    let result = await ImagePicker.launchImageLibraryAsync({
      mediaTypes: ['images'],
      allowsEditing: true,
      aspect: [1, 1],
      quality: 0.8,
    });

    if (!result.canceled) {
      setUpdating(true);
      try {
        const data = await apiClient.uploadProfilePhoto(user.token, result.assets[0].uri);
        setPhotoUrl(data.photo_url);
        Alert.alert('Sukses', 'Foto profil berhasil diunggah');
      } catch (error) {
        Alert.alert('Error', 'Gagal mengunggah foto');
      } finally {
        setUpdating(false);
      }
    }
  };

  const pickKtp = async () => {
    let result = await ImagePicker.launchImageLibraryAsync({
      mediaTypes: ['images'],
      allowsEditing: true,
      quality: 0.8,
    });

    if (!result.canceled) {
      setUpdating(true);
      try {
        const data = await apiClient.uploadKtp(user.token, result.assets[0].uri);
        setKtpUrl(data.ktp_url);
        Alert.alert('Sukses', 'Scan KTP berhasil diunggah');
      } catch (error) {
        Alert.alert('Error', 'Gagal mengunggah KTP');
      } finally {
        setUpdating(false);
      }
    }
  };

  const pickDocument = async () => {
    let result = await ImagePicker.launchImageLibraryAsync({
      mediaTypes: ['images'],
      allowsEditing: true,
      quality: 0.8,
    });

    if (!result.canceled) {
      setUpdating(true);
      try {
        const data = await apiClient.uploadSupportingDocument(user.token, result.assets[0].uri);
        setSupportingDocs([...supportingDocs, data.document]);
        Alert.alert('Sukses', 'Dokumen berhasil diunggah');
      } catch (error) {
        Alert.alert('Error', 'Gagal mengunggah dokumen');
      } finally {
        setUpdating(false);
      }
    }
  };

  const deleteDocument = async (id) => {
    Alert.alert('Hapus Dokumen', 'Apakah Anda yakin ingin menghapus dokumen ini?', [
      { text: 'Batal', style: 'cancel' },
      { 
        text: 'Hapus', 
        style: 'destructive',
        onPress: async () => {
          setUpdating(true);
          try {
            await apiClient.deleteSupportingDocument(user.token, id);
            setSupportingDocs(supportingDocs.filter(d => d.id !== id));
          } catch (error) {
            Alert.alert('Error', 'Gagal menghapus dokumen');
          } finally {
            setUpdating(false);
          }
        }
      }
    ]);
  };

  const addRow = (setter, current) => setter([...current, '']);
  const updateRow = (setter, current, index, val) => {
    const next = [...current];
    next[index] = val;
    setter(next);
  };
  const removeRow = (setter, current, index) => {
    if (current.length === 1) {
      setter(['']);
      return;
    }
    const next = [...current];
    next.splice(index, 1);
    setter(next);
  };

  if (loading) {
    return <View style={styles.center}><ActivityIndicator size="large" color="#0984e3" /></View>;
  }

  return (
    <SwipeableScreen onSwipeBack={() => navigation.goBack()}>
    <SafeAreaView style={styles.container}>
      <StatusBar style="dark" />
      
      <View style={styles.header}>
        <Text style={styles.headerTitle}>Profil Saya</Text>
        <TouchableOpacity onPress={onLogout}>
          <Text style={styles.logoutBtn}>Keluar</Text>
        </TouchableOpacity>
      </View>

      <View style={styles.tabBar}>
        <TouchableOpacity 
          style={[styles.tab, activeTab === 'account' && styles.activeTab]} 
          onPress={() => setActiveTab('account')}
        >
          <Text style={[styles.tabLabel, activeTab === 'account' && styles.activeTabLabel]}>Akun</Text>
        </TouchableOpacity>
        <TouchableOpacity 
          style={[styles.tab, activeTab === 'public' && styles.activeTab]} 
          onPress={() => setActiveTab('public')}
        >
          <Text style={[styles.tabLabel, activeTab === 'public' && styles.activeTabLabel]}>Identitas</Text>
        </TouchableOpacity>
        <TouchableOpacity 
          style={[styles.tab, activeTab === 'skills' && styles.activeTab]} 
          onPress={() => setActiveTab('skills')}
        >
          <Text style={[styles.tabLabel, activeTab === 'skills' && styles.activeTabLabel]}>Keahlian</Text>
        </TouchableOpacity>
      </View>

      <KeyboardAvoidingView 
        behavior={Platform.OS === 'ios' ? 'padding' : 'height'}
        style={{ flex: 1 }}
      >
        <ScrollView contentContainerStyle={styles.scrollContent} showsVerticalScrollIndicator={false}>
          
          {activeTab === 'account' && (
            <View style={styles.section}>
              <View style={styles.inputGroup}>
                <Text style={styles.label}>NAMA LENGKAP</Text>
                <TextInput style={styles.input} value={name} onChangeText={setName} />
              </View>
              <View style={styles.inputGroup}>
                <Text style={styles.label}>EMAIL</Text>
                <TextInput style={styles.input} value={email} onChangeText={setEmail} keyboardType="email-address" />
              </View>
              <TouchableOpacity 
                style={[styles.saveBtn, updating && styles.disabledBtn]} 
                onPress={handleUpdateAccount}
                disabled={updating}
              >
                {updating ? <ActivityIndicator color="#fff" /> : <Text style={styles.saveBtnText}>Simpan Akun</Text>}
              </TouchableOpacity>
            </View>
          )}

          {activeTab === 'public' && (
            <View style={styles.section}>
              {/* Profile Photo */}
              <View style={styles.photoSection}>
                <View style={styles.avatarContainer}>
                  {photoUrl ? (
                    <Image source={{ uri: photoUrl }} style={styles.avatar} />
                  ) : (
                    <Text style={{ fontSize: 40 }}>👤</Text>
                  )}
                </View>
                <TouchableOpacity style={styles.changePhotoBtn} onPress={pickPhoto}>
                  <Text style={styles.changePhotoText}>Ubah Foto Profil</Text>
                </TouchableOpacity>
              </View>

              <View style={styles.inputGroup}>
                <Text style={styles.label}>WHATSAPP PUBLIK</Text>
                <TextInput style={styles.input} value={publicPhone} onChangeText={setPublicPhone} placeholder="62812..." />
              </View>

              <View style={styles.inputGroup}>
                <Text style={styles.label}>SCAN KTP</Text>
                <TouchableOpacity style={styles.ktpBtn} onPress={pickKtp}>
                  {ktpUrl ? (
                    <Image source={{ uri: ktpUrl }} style={styles.ktpPreview} />
                  ) : (
                    <View style={styles.ktpPlaceholder}>
                      <Text style={{ fontSize: 24 }}>🪪</Text>
                      <Text style={styles.ktpPlaceholderText}>Unggah Scan KTP</Text>
                    </View>
                  )}
                </TouchableOpacity>
              </View>

              <View style={styles.inputGroup}>
                <Text style={styles.label}>NOMOR PASSPORT</Text>
                <TextInput style={styles.input} value={passport} onChangeText={setPassport} />
              </View>

              {/* Supporting Documents Section */}
              <View style={styles.inputGroup}>
                <View style={styles.repeatingHeader}>
                  <Text style={styles.label}>DOKUMEN PENDUKUNG (PASSPORT/SERTIFIKAT)</Text>
                  <TouchableOpacity onPress={pickDocument}>
                    <Text style={styles.addMoreBtn}>+ Unggah</Text>
                  </TouchableOpacity>
                </View>
                
                <View style={styles.docsGrid}>
                  {supportingDocs.map((doc) => (
                    <View key={doc.id} style={styles.docItem}>
                      <Image source={{ uri: doc.url }} style={styles.docImage} />
                      <TouchableOpacity style={styles.deleteDocBtn} onPress={() => deleteDocument(doc.id)}>
                        <Text style={styles.deleteDocText}>✕</Text>
                      </TouchableOpacity>
                    </View>
                  ))}
                  {supportingDocs.length === 0 && (
                    <View style={styles.emptyDocsContainer}>
                      <Text style={styles.emptyDocsText}>Belum ada dokumen tambahan.</Text>
                    </View>
                  )}
                </View>
              </View>
              <View style={styles.inputGroup}>
                <Text style={styles.label}>TANGGAL LAHIR</Text>
                <TextInput style={styles.input} value={birthDate} onChangeText={setBirthDate} placeholder="YYYY-MM-DD" />
              </View>
              <View style={styles.inputGroup}>
                <Text style={styles.label}>ALAMAT DOMISILI</Text>
                <TextInput style={[styles.input, styles.textArea]} value={address} onChangeText={setAddress} multiline numberOfLines={3} />
              </View>
              <View style={styles.inputGroup}>
                <Text style={styles.label}>BIOGRAFI / REFERENSI</Text>
                <TextInput style={[styles.input, styles.textArea]} value={bio} onChangeText={setBio} multiline numberOfLines={4} />
              </View>
              <TouchableOpacity 
                style={[styles.saveBtn, updating && styles.disabledBtn]} 
                onPress={handleUpdatePublic}
                disabled={updating}
              >
                {updating ? <ActivityIndicator color="#fff" /> : <Text style={styles.saveBtnText}>Simpan Profil Publik</Text>}
              </TouchableOpacity>
            </View>
          )}

          {activeTab === 'skills' && (
            <View style={styles.section}>
              {/* Languages */}
              <View style={styles.repeatingHeader}>
                <Text style={styles.sectionTitle}>Bahasa</Text>
                <TouchableOpacity onPress={() => addRow(setLanguages, languages)}>
                  <Text style={styles.addMoreBtn}>+ Tambah</Text>
                </TouchableOpacity>
              </View>
              {languages.map((item, idx) => (
                <View key={idx} style={styles.repeatingRow}>
                  <TextInput style={[styles.input, { flex: 1 }]} value={item} onChangeText={(val) => updateRow(setLanguages, languages, idx, val)} />
                  <TouchableOpacity style={styles.removeBtn} onPress={() => removeRow(setLanguages, languages, idx)}><Text style={styles.removeText}>✕</Text></TouchableOpacity>
                </View>
              ))}

              {/* Education */}
              <View style={[styles.repeatingHeader, { marginTop: 20 }]}>
                <Text style={styles.sectionTitle}>Pendidikan</Text>
                <TouchableOpacity onPress={() => addRow(setEducations, educations)}>
                  <Text style={styles.addMoreBtn}>+ Tambah</Text>
                </TouchableOpacity>
              </View>
              {educations.map((item, idx) => (
                <View key={idx} style={styles.repeatingRow}>
                  <TextInput style={[styles.input, { flex: 1 }]} value={item} onChangeText={(val) => updateRow(setEducations, educations, idx, val)} />
                  <TouchableOpacity style={styles.removeBtn} onPress={() => removeRow(setEducations, educations, idx)}><Text style={styles.removeText}>✕</Text></TouchableOpacity>
                </View>
              ))}

              {/* Work Experience */}
              <View style={[styles.repeatingHeader, { marginTop: 20 }]}>
                <Text style={styles.sectionTitle}>Pengalaman Kerja</Text>
                <TouchableOpacity onPress={() => addRow(setExperiences, experiences)}>
                  <Text style={styles.addMoreBtn}>+ Tambah</Text>
                </TouchableOpacity>
              </View>
              {experiences.map((item, idx) => (
                <View key={idx} style={styles.repeatingRow}>
                  <TextInput style={[styles.input, { flex: 1 }]} value={item} onChangeText={(val) => updateRow(setExperiences, experiences, idx, val)} />
                  <TouchableOpacity style={styles.removeBtn} onPress={() => removeRow(setExperiences, experiences, idx)}><Text style={styles.removeText}>✕</Text></TouchableOpacity>
                </View>
              ))}

              <TouchableOpacity 
                style={[styles.saveBtn, { marginTop: 30 }, updating && styles.disabledBtn]} 
                onPress={handleUpdatePublic}
                disabled={updating}
              >
                {updating ? <ActivityIndicator color="#fff" /> : <Text style={styles.saveBtnText}>Simpan Keahlian</Text>}
              </TouchableOpacity>
            </View>
          )}

        </ScrollView>
      </KeyboardAvoidingView>

      {/* Bottom Nav Sync */}
      <View style={styles.bottomNav}>
        <TouchableOpacity style={styles.navItem} onPress={() => navigation.navigate('Dashboard')}>
          <Text style={{fontSize:22, opacity:0.3}}>🏠</Text>
          <Text style={styles.navLabel}>Home</Text>
        </TouchableOpacity>
        <TouchableOpacity style={styles.navItem} onPress={() => navigation.navigate('MuthowifBookings')}><Text style={{fontSize:22, opacity:0.3}}>📋</Text><Text style={styles.navLabel}>Jadwal</Text></TouchableOpacity>
        <TouchableOpacity style={styles.navItem}><Text style={{fontSize:22, opacity:0.3}}>👛</Text><Text style={styles.navLabel}>Dompet</Text></TouchableOpacity>
        <TouchableOpacity style={styles.navItem} onPress={() => navigation.navigate('Profile')}>
          <View style={styles.navActive}><Text style={{fontSize:22}}>👤</Text></View>
          <Text style={styles.navLabelActive}>Profil</Text>
        </TouchableOpacity>
      </View>
    </SafeAreaView>
    </SwipeableScreen>
  );
}

const styles = StyleSheet.create({
  container: { flex: 1, backgroundColor: '#FFFFFF' },
  center: { flex: 1, justifyContent: 'center', alignItems: 'center' },
  header: { 
    flexDirection: 'row', 
    justifyContent: 'space-between', 
    alignItems: 'center', 
    paddingHorizontal: 20, 
    paddingVertical: 15,
    borderBottomWidth: 1,
    borderBottomColor: '#F1F5F9'
  },
  headerTitle: { fontSize: 18, fontWeight: '800', color: '#1E293B' },
  logoutBtn: { color: '#EF4444', fontWeight: '700', fontSize: 14 },
  
  photoSection: { alignItems: 'center', marginBottom: 25 },
  avatarContainer: { 
    width: 100, 
    height: 100, 
    borderRadius: 50, 
    backgroundColor: '#F1F5F9', 
    justifyContent: 'center', 
    alignItems: 'center',
    borderWidth: 3,
    borderColor: '#E2E8F0',
    overflow: 'hidden'
  },
  avatar: { width: '100%', height: '100%' },
  changePhotoBtn: { marginTop: 12 },
  changePhotoText: { fontSize: 14, fontWeight: '800', color: '#0984e3' },

  tabBar: { flexDirection: 'row', paddingHorizontal: 20, marginTop: 10, gap: 10 },
  tab: { paddingHorizontal: 15, paddingVertical: 8, borderRadius: 10, backgroundColor: '#F1F5F9' },
  activeTab: { backgroundColor: '#0984e3' },
  tabLabel: { fontSize: 12, fontWeight: '700', color: '#64748B' },
  activeTabLabel: { color: '#fff' },
  
  scrollContent: { padding: 25, paddingBottom: 150 },
  section: { marginBottom: 30 },
  sectionTitle: { fontSize: 14, fontWeight: '800', color: '#1E293B' },
  
  inputGroup: { marginBottom: 20 },
  label: { fontSize: 10, fontWeight: '800', color: '#94A3B8', marginBottom: 10, letterSpacing: 0.5 },
  input: {
    backgroundColor: '#F8FAFC',
    borderWidth: 1,
    borderColor: '#E2E8F0',
    borderRadius: 15,
    padding: 15,
    fontSize: 14,
    color: '#1E293B',
    fontWeight: '600'
  },
  textArea: { height: 80, textAlignVertical: 'top' },

  ktpBtn: {
    width: '100%',
    height: 180,
    backgroundColor: '#F8FAFC',
    borderRadius: 20,
    borderWidth: 2,
    borderColor: '#E2E8F0',
    borderStyle: 'dashed',
    justifyContent: 'center',
    alignItems: 'center',
    overflow: 'hidden'
  },
  ktpPreview: { width: '100%', height: '100%', resizeMode: 'cover' },
  ktpPlaceholder: { alignItems: 'center' },
  ktpPlaceholderText: { fontSize: 13, fontWeight: '700', color: '#94A3B8', marginTop: 10 },

  docsGrid: { flexDirection: 'row', flexWrap: 'wrap', gap: 10, marginTop: 10 },
  docItem: { width: (width - 60) / 3, height: (width - 60) / 3, borderRadius: 12, backgroundColor: '#F1F5F9', overflow: 'hidden', position: 'relative' },
  docImage: { width: '100%', height: '100%' },
  deleteDocBtn: { position: 'absolute', top: 5, right: 5, width: 20, height: 20, borderRadius: 10, backgroundColor: 'rgba(239, 68, 68, 0.9)', justifyContent: 'center', alignItems: 'center' },
  deleteDocText: { color: '#fff', fontSize: 10, fontWeight: 'bold' },
  emptyDocsContainer: { width: '100%', padding: 20, backgroundColor: '#F8FAFC', borderRadius: 15, borderWidth: 1, borderColor: '#F1F5F9', alignItems: 'center' },
  emptyDocsText: { fontSize: 12, color: '#94A3B8', fontWeight: '600' },
  
  repeatingHeader: { flexDirection: 'row', justifyContent: 'space-between', alignItems: 'center', marginBottom: 12 },
  addMoreBtn: { fontSize: 12, fontWeight: '800', color: '#0984e3' },
  repeatingRow: { flexDirection: 'row', alignItems: 'center', gap: 10, marginBottom: 10 },
  removeBtn: { width: 30, height: 30, justifyContent: 'center', alignItems: 'center' },
  removeText: { color: '#EF4444', fontWeight: 'bold' },

  saveBtn: {
    backgroundColor: '#0984e3',
    paddingVertical: 18,
    borderRadius: 18,
    alignItems: 'center',
    shadowColor: '#0984e3', shadowOffset: { width: 0, height: 4 }, shadowOpacity: 0.2, shadowRadius: 10, elevation: 5
  },
  disabledBtn: { opacity: 0.7 },
  saveBtnText: { color: '#fff', fontSize: 15, fontWeight: '800' },

  bottomNav: {
    position: 'absolute', bottom: 0, left: 0, right: 0, height: 85,
    backgroundColor: '#FFFFFF', flexDirection: 'row', justifyContent: 'space-around', alignItems: 'center',
    paddingBottom: 25, borderTopWidth: 1, borderTopColor: '#F1F5F9',
  },
  navItem: { alignItems: 'center', flex: 1 },
  navActive: { backgroundColor: '#F0F9FF', width: 45, height: 45, borderRadius: 15, justifyContent: 'center', alignItems: 'center', marginBottom: 4 },
  navLabel: { fontSize: 10, fontWeight: '700', color: '#94A3B8' },
  navLabelActive: { fontSize: 10, fontWeight: '800', color: '#0984e3' },
});
