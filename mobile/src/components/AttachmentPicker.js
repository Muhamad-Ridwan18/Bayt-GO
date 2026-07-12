import React from 'react';
import { View, Text, StyleSheet } from 'react-native';
import { FileText, Image as ImageIcon } from 'lucide-react-native';
import * as ImagePicker from 'expo-image-picker';
import * as DocumentPicker from 'expo-document-picker';
import { PressableScale, UploadPreviewStrip } from '../ui';
import { colors, spacing, radius, typography } from '../theme/tokens';

async function pickAttachments(existing = []) {
  const remaining = Math.max(0, 5 - existing.length);
  if (remaining === 0) return existing;

  const permission = await ImagePicker.requestMediaLibraryPermissionsAsync();
  if (!permission.granted) {
    throw new Error('Izinkan akses galeri untuk melampirkan file.');
  }

  const result = await ImagePicker.launchImageLibraryAsync({
    mediaTypes: ['images'],
    allowsMultipleSelection: true,
    selectionLimit: remaining,
    quality: 0.85,
  });

  if (!result.canceled && result.assets?.length) {
    return [...existing, ...result.assets].slice(0, 5);
  }

  return existing;
}

async function pickDocument(existing = []) {
  const remaining = Math.max(0, 5 - existing.length);
  if (remaining === 0) return existing;

  const result = await DocumentPicker.getDocumentAsync({
    type: ['image/*', 'application/pdf'],
    multiple: true,
    copyToCacheDirectory: true,
  });

  if (!result.canceled && result.assets?.length) {
    return [...existing, ...result.assets].slice(0, 5);
  }

  return existing;
}

export default function AttachmentPicker({ label, hint, files, onChange, disabled }) {
  const addImages = async () => {
    try {
      onChange(await pickAttachments(files));
    } catch {
      onChange(files);
    }
  };

  const addPdf = async () => {
    try {
      onChange(await pickDocument(files));
    } catch {
      onChange(files);
    }
  };

  const removeAt = (index) => {
    onChange(files.filter((_, i) => i !== index));
  };

  return (
    <View style={styles.wrap}>
      {label ? <Text style={styles.label}>{label}</Text> : null}
      {hint ? <Text style={styles.hint}>{hint}</Text> : null}

      <View style={styles.actions}>
        <PressableScale style={styles.btn} onPress={addImages} disabled={disabled || files.length >= 5} haptic="light">
          <ImageIcon size={18} color={colors.baytgo} strokeWidth={2} />
          <Text style={styles.btnText}>Foto</Text>
        </PressableScale>
        <PressableScale style={styles.btn} onPress={addPdf} disabled={disabled || files.length >= 5} haptic="light">
          <FileText size={18} color={colors.baytgo} strokeWidth={2} />
          <Text style={styles.btnText}>PDF</Text>
        </PressableScale>
      </View>

      <UploadPreviewStrip files={files} onRemove={removeAt} style={styles.previewStrip} />
    </View>
  );
}

const styles = StyleSheet.create({
  wrap: { marginBottom: spacing.lg },
  label: { ...typography.caption, fontWeight: '800', color: colors.slate600, marginBottom: spacing.sm - 2 },
  hint: { ...typography.small, fontWeight: '600', color: colors.textSecondary, marginBottom: spacing.sm },
  actions: { flexDirection: 'row', gap: spacing.sm },
  btn: {
    flexDirection: 'row',
    alignItems: 'center',
    gap: spacing.sm - 2,
    backgroundColor: colors.white,
    borderRadius: radius.sm,
    paddingHorizontal: spacing.md,
    paddingVertical: spacing.sm + 2,
    borderWidth: 1,
    borderColor: colors.surface,
  },
  btnText: { ...typography.caption, fontWeight: '700', color: colors.baytgo },
  previewStrip: { marginTop: spacing.sm },
});
