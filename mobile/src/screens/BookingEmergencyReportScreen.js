import React, { useState } from 'react';
import {
  View,
  Text,
  StyleSheet,
  ScrollView,
  TouchableOpacity,
  ActivityIndicator,
  Alert,
  TextInput,
} from 'react-native';
import { LinearGradient } from 'expo-linear-gradient';
import * as ImagePicker from 'expo-image-picker';
import * as DocumentPicker from 'expo-document-picker';
import { Ionicons } from '@expo/vector-icons';
import ScreenHeader from '../components/ScreenHeader';
import { submitEmergencyReport } from '../api/emergency';
import { useAuth } from '../context/AuthContext';
import { colors } from '../theme/colors';

export default function BookingEmergencyReportScreen({ navigation, route }) {
  const { token } = useAuth();
  const { bookingId, caseTypes = [] } = route.params;

  const [caseType, setCaseType] = useState(caseTypes[0]?.value || 'unreachable');
  const [description, setDescription] = useState('');
  const [evidence, setEvidence] = useState([]);
  const [loading, setLoading] = useState(false);
  const [error, setError] = useState('');

  const pickEvidence = async () => {
    if (evidence.length >= 5) {
      Alert.alert('Maksimal 5 file', 'Hapus file lama untuk menambah bukti baru.');
      return;
    }

    Alert.alert('Unggah bukti', 'Pilih sumber file', [
      {
        text: 'Galeri',
        onPress: async () => {
          const permission = await ImagePicker.requestMediaLibraryPermissionsAsync();
          if (!permission.granted) return;
          const result = await ImagePicker.launchImageLibraryAsync({
            mediaTypes: ['images'],
            quality: 0.85,
          });
          if (!result.canceled && result.assets[0]) {
            setEvidence((prev) => [...prev, result.assets[0]].slice(0, 5));
          }
        },
      },
      {
        text: 'Dokumen',
        onPress: async () => {
          const result = await DocumentPicker.getDocumentAsync({
            type: ['image/*', 'application/pdf'],
            copyToCacheDirectory: true,
            multiple: false,
          });
          if (!result.canceled && result.assets?.[0]) {
            setEvidence((prev) => [...prev, result.assets[0]].slice(0, 5));
          }
        },
      },
      { text: 'Batal', style: 'cancel' },
    ]);
  };

  const handleSubmit = () => {
    Alert.alert(
      'Kirim laporan darurat?',
      'Tim Bayt-GO akan segera meninjau laporan insiden ini.',
      [
        { text: 'Batal', style: 'cancel' },
        {
          text: 'Kirim',
          style: 'destructive',
          onPress: async () => {
            setLoading(true);
            setError('');
            try {
              await submitEmergencyReport(token, bookingId, {
                caseType,
                description,
                evidence,
              });
              Alert.alert('Berhasil', 'Laporan insiden darurat telah dikirim.', [
                { text: 'OK', onPress: () => navigation.navigate('BookingDetail', { bookingId }) },
              ]);
            } catch (err) {
              setError(err.message || 'Gagal mengirim laporan');
            } finally {
              setLoading(false);
            }
          },
        },
      ],
    );
  };

  return (
    <View style={styles.container}>
      <ScreenHeader title="Lapor Insiden Darurat" onBack={() => navigation.goBack()} />

      <ScrollView contentContainerStyle={styles.scroll} keyboardShouldPersistTaps="handled">
        <View style={styles.warning}>
          <Ionicons name="warning" size={20} color="#B91C1C" />
          <Text style={styles.warningText}>
            Gunakan fitur ini jika muthowif tidak dapat dihubungi, meninggalkan tugas, atau melanggar kesepakatan layanan.
          </Text>
        </View>

        {error ? <Text style={styles.error}>{error}</Text> : null}

        <Text style={styles.label}>Jenis insiden *</Text>
        <View style={styles.caseList}>
          {caseTypes.map((item) => {
            const active = caseType === item.value;
            return (
              <TouchableOpacity
                key={item.value}
                style={[styles.caseChip, active && styles.caseChipActive]}
                onPress={() => setCaseType(item.value)}
              >
                <Text style={[styles.caseChipText, active && styles.caseChipTextActive]}>{item.label}</Text>
              </TouchableOpacity>
            );
          })}
        </View>

        <Text style={styles.label}>Keterangan</Text>
        <TextInput
          style={styles.textarea}
          value={description}
          onChangeText={setDescription}
          placeholder="Jelaskan situasi yang terjadi..."
          placeholderTextColor={colors.slate400}
          multiline
          maxLength={5000}
          textAlignVertical="top"
        />

        <Text style={styles.label}>Bukti (opsional, max 5)</Text>
        <TouchableOpacity style={styles.uploadBtn} onPress={pickEvidence}>
          <Ionicons name="cloud-upload-outline" size={20} color={colors.baytgo} />
          <Text style={styles.uploadText}>Tambah foto / PDF</Text>
        </TouchableOpacity>

        {evidence.map((file, index) => (
          <View key={`${file.uri}-${index}`} style={styles.fileRow}>
            <Text style={styles.fileName} numberOfLines={1}>{file.name || file.fileName || `File ${index + 1}`}</Text>
            <TouchableOpacity onPress={() => setEvidence((prev) => prev.filter((_, i) => i !== index))}>
              <Text style={styles.fileRemove}>Hapus</Text>
            </TouchableOpacity>
          </View>
        ))}

        <TouchableOpacity style={styles.submitBtn} onPress={handleSubmit} disabled={loading} activeOpacity={0.9}>
          <LinearGradient colors={['#B91C1C', '#991B1B']} style={styles.submitGradient}>
            {loading ? (
              <ActivityIndicator color={colors.white} />
            ) : (
              <Text style={styles.submitText}>Kirim Laporan Darurat</Text>
            )}
          </LinearGradient>
        </TouchableOpacity>
      </ScrollView>
    </View>
  );
}

const styles = StyleSheet.create({
  container: { flex: 1, backgroundColor: colors.canvas },
  scroll: { padding: 16, paddingBottom: 32 },
  warning: {
    flexDirection: 'row',
    gap: 10,
    backgroundColor: '#FEF2F2',
    borderRadius: 14,
    padding: 14,
    marginBottom: 16,
    borderWidth: 1,
    borderColor: '#FECACA',
  },
  warningText: { flex: 1, fontSize: 13, lineHeight: 19, color: '#991B1B', fontWeight: '600' },
  error: { marginBottom: 12, fontSize: 13, color: '#DC2626', fontWeight: '600' },
  label: { fontSize: 12, fontWeight: '800', color: colors.slate600, marginBottom: 8, marginTop: 4 },
  caseList: { flexDirection: 'row', flexWrap: 'wrap', gap: 8, marginBottom: 16 },
  caseChip: {
    borderRadius: 999,
    paddingHorizontal: 12,
    paddingVertical: 8,
    backgroundColor: colors.white,
    borderWidth: 1,
    borderColor: colors.slate100,
  },
  caseChipActive: { backgroundColor: '#FEE2E2', borderColor: '#FECACA' },
  caseChipText: { fontSize: 12, fontWeight: '700', color: colors.slate700 },
  caseChipTextActive: { color: '#991B1B' },
  textarea: {
    minHeight: 120,
    backgroundColor: colors.white,
    borderRadius: 16,
    borderWidth: 1,
    borderColor: colors.slate100,
    padding: 14,
    fontSize: 14,
    fontWeight: '500',
    color: colors.slate900,
    marginBottom: 16,
  },
  uploadBtn: {
    flexDirection: 'row',
    alignItems: 'center',
    justifyContent: 'center',
    gap: 8,
    backgroundColor: colors.white,
    borderRadius: 14,
    padding: 14,
    borderWidth: 1,
    borderColor: colors.slate100,
    marginBottom: 10,
  },
  uploadText: { fontSize: 14, fontWeight: '800', color: colors.baytgo },
  fileRow: {
    flexDirection: 'row',
    alignItems: 'center',
    gap: 8,
    paddingVertical: 8,
  },
  fileName: { flex: 1, fontSize: 12, fontWeight: '600', color: colors.slate600 },
  fileRemove: { fontSize: 12, fontWeight: '800', color: colors.baytgo },
  submitBtn: { marginTop: 12, borderRadius: 16, overflow: 'hidden' },
  submitGradient: { paddingVertical: 16, alignItems: 'center' },
  submitText: { color: colors.white, fontSize: 15, fontWeight: '800' },
});
