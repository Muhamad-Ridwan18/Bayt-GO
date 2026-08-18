import React, { useState } from 'react';
import { View, Text, StyleSheet, ScrollView, Alert, TextInput } from 'react-native';
import { AlertTriangle, CloudUpload } from 'lucide-react-native';
import * as ImagePicker from 'expo-image-picker';
import * as DocumentPicker from 'expo-document-picker';
import ScreenHeader from '../components/ScreenHeader';
import { submitEmergencyReport } from '../api/emergency';
import { useAuth } from '../context/AuthContext';
import { Button, Card, FilterChip, InlineAlert, PressableScale, UploadPreviewStrip } from '../ui';
import { notifySuccessThen } from '../utils/feedback';
import { colors, layout, radius, spacing, typography } from '../theme/tokens';
import { useLocale } from '../utils/locale';

export default function BookingEmergencyReportScreen({ navigation, route }) {
  const { token } = useAuth();
  const locale = useLocale(); const isEn = locale === 'en';
  const { bookingId, caseTypes = [] } = route.params;

  const [caseType, setCaseType] = useState(caseTypes[0]?.value || 'unreachable');
  const [description, setDescription] = useState('');
  const [evidence, setEvidence] = useState([]);
  const [loading, setLoading] = useState(false);
  const [error, setError] = useState('');

  const pickEvidence = async () => {
    if (evidence.length >= 5) {
      Alert.alert(isEn ? 'Maximum 5 files' : 'Maksimal 5 file', isEn ? 'Remove old files to add new evidence.' : 'Hapus file lama untuk menambah bukti baru.');
      return;
    }

    Alert.alert(isEn ? 'Upload evidence' : 'Unggah bukti', isEn ? 'Choose file source' : 'Pilih sumber file', [
      {
        text: isEn ? 'Gallery' : 'Galeri',
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
        text: isEn ? 'Document' : 'Dokumen',
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
      { text: isEn ? 'Cancel' : 'Batal', style: 'cancel' },
    ]);
  };

  const handleSubmit = () => {
    Alert.alert(
      isEn ? 'Submit emergency report?' : 'Kirim laporan darurat?',
      isEn ? 'The Bayt-GO team will review this incident report immediately.' : 'Tim Bayt-GO akan segera meninjau laporan insiden ini.',
      [
        { text: isEn ? 'Cancel' : 'Batal', style: 'cancel' },
        {
          text: isEn ? 'Submit' : 'Kirim',
          style: 'destructive',
          onPress: async () => {
            setLoading(true);
            setError('');
            try {
              await submitEmergencyReport(token, bookingId, { caseType, description, evidence });
              notifySuccessThen(
                navigation,
                isEn ? 'Emergency incident report has been submitted.' : 'Laporan insiden darurat telah dikirim.',
                'BookingDetail',
                { bookingId },
              );
            } catch (err) {
              setError(err.message || (isEn ? 'Failed to submit report' : 'Gagal mengirim laporan'));
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
      <ScreenHeader title={isEn ? 'Report Emergency Incident' : 'Lapor Insiden Darurat'} onBack={() => navigation.goBack()} />

      <ScrollView contentContainerStyle={styles.scroll} keyboardShouldPersistTaps="handled">
        <Card style={styles.warning} padding={spacing.lg} elevated={false}>
          <AlertTriangle size={20} color={colors.error} strokeWidth={2} />
          <Text style={styles.warningText}>
            {isEn ? 'Use this feature if the muthowif is unreachable, has abandoned duty, or violated the service agreement.' : 'Gunakan fitur ini jika muthowif tidak dapat dihubungi, meninggalkan tugas, atau melanggar kesepakatan layanan.'}
          </Text>
        </Card>

        {error ? <InlineAlert variant="error">{error}</InlineAlert> : null}

        <Text style={styles.label}>{isEn ? 'Incident type *' : 'Jenis insiden *'}</Text>
        <View style={styles.caseList}>
          {caseTypes.map((item) => (
            <FilterChip
              key={item.value}
              label={item.label}
              active={caseType === item.value}
              onPress={() => setCaseType(item.value)}
            />
          ))}
        </View>

        <Text style={styles.label}>{isEn ? 'Description' : 'Keterangan'}</Text>
        <TextInput
          style={styles.textarea}
          value={description}
          onChangeText={setDescription}
          placeholder={isEn ? 'Describe the situation...' : 'Jelaskan situasi yang terjadi...'}
          placeholderTextColor={colors.textMuted}
          multiline
          maxLength={5000}
          textAlignVertical="top"
        />

        <Text style={styles.label}>{isEn ? 'Evidence (optional, max 5)' : 'Bukti (opsional, max 5)'}</Text>
        <PressableScale onPress={pickEvidence} haptic="light">
          <Card style={styles.uploadBtn} padding={spacing.lg} elevated={false}>
            <CloudUpload size={20} color={colors.baytgo} strokeWidth={2} />
            <Text style={styles.uploadText}>{isEn ? 'Add photo / PDF' : 'Tambah foto / PDF'}</Text>
          </Card>
        </PressableScale>

        <UploadPreviewStrip
          files={evidence}
          onRemove={(index) => setEvidence((prev) => prev.filter((_, i) => i !== index))}
          style={styles.previewStrip}
        />

        <Button label={isEn ? 'Submit Emergency Report' : 'Kirim Laporan Darurat'} onPress={handleSubmit} loading={loading} variant="danger" style={styles.cta} />
      </ScrollView>
    </View>
  );
}

const styles = StyleSheet.create({
  container: { flex: 1, backgroundColor: colors.background },
  scroll: { padding: layout.screenPadding, paddingBottom: spacing['3xl'] },
  warning: {
    flexDirection: 'row',
    gap: spacing.md,
    backgroundColor: colors.errorLight,
    borderColor: '#FECACA',
    marginBottom: spacing.lg,
  },
  warningText: { flex: 1, ...typography.caption, lineHeight: 20, color: colors.error, fontWeight: '600' },
  label: { ...typography.label, color: colors.textSecondary, marginBottom: spacing.sm, marginTop: spacing.xs },
  caseList: { flexDirection: 'row', flexWrap: 'wrap', gap: spacing.sm, marginBottom: spacing.lg },
  textarea: {
    minHeight: 120,
    backgroundColor: colors.card,
    borderRadius: radius.sm,
    borderWidth: 1,
    borderColor: colors.border,
    padding: spacing.lg,
    ...typography.caption,
    color: colors.textPrimary,
    marginBottom: spacing.lg,
  },
  uploadBtn: {
    flexDirection: 'row',
    alignItems: 'center',
    justifyContent: 'center',
    gap: spacing.sm,
    marginBottom: spacing.md,
  },
  uploadText: { ...typography.caption, fontFamily: 'PlusJakartaSans_800ExtraBold', color: colors.baytgo },
  previewStrip: { marginBottom: spacing.md },
  cta: { marginTop: spacing.md },
});
