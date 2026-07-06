import React from 'react';
import { View, Text, StyleSheet, TouchableOpacity, ScrollView } from 'react-native';
import { Ionicons } from '@expo/vector-icons';
import * as ImagePicker from 'expo-image-picker';
import * as DocumentPicker from 'expo-document-picker';
import { colors } from '../theme/colors';

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
        <TouchableOpacity style={styles.btn} onPress={addImages} disabled={disabled || files.length >= 5}>
          <Ionicons name="image-outline" size={18} color={colors.baytgo} />
          <Text style={styles.btnText}>Foto</Text>
        </TouchableOpacity>
        <TouchableOpacity style={styles.btn} onPress={addPdf} disabled={disabled || files.length >= 5}>
          <Ionicons name="document-outline" size={18} color={colors.baytgo} />
          <Text style={styles.btnText}>PDF</Text>
        </TouchableOpacity>
      </View>

      {files.length > 0 ? (
        <ScrollView horizontal showsHorizontalScrollIndicator={false} contentContainerStyle={styles.fileList}>
          {files.map((file, index) => (
            <View key={`${file.uri}-${index}`} style={styles.chip}>
              <Text style={styles.chipText} numberOfLines={1}>
                {file.name || file.fileName || `Lampiran ${index + 1}`}
              </Text>
              <TouchableOpacity onPress={() => removeAt(index)} hitSlop={8}>
                <Ionicons name="close-circle" size={18} color={colors.slate400} />
              </TouchableOpacity>
            </View>
          ))}
        </ScrollView>
      ) : null}
    </View>
  );
}

const styles = StyleSheet.create({
  wrap: { marginBottom: 16 },
  label: { fontSize: 12, fontWeight: '800', color: colors.slate600, marginBottom: 6 },
  hint: { fontSize: 11, fontWeight: '600', color: colors.slate500, marginBottom: 8 },
  actions: { flexDirection: 'row', gap: 8 },
  btn: {
    flexDirection: 'row',
    alignItems: 'center',
    gap: 6,
    backgroundColor: colors.white,
    borderRadius: 12,
    paddingHorizontal: 12,
    paddingVertical: 10,
    borderWidth: 1,
    borderColor: colors.slate100,
  },
  btnText: { fontSize: 13, fontWeight: '700', color: colors.baytgo },
  fileList: { gap: 8, marginTop: 10 },
  chip: {
    flexDirection: 'row',
    alignItems: 'center',
    gap: 6,
    maxWidth: 180,
    backgroundColor: colors.canvas,
    borderRadius: 10,
    paddingHorizontal: 10,
    paddingVertical: 8,
    borderWidth: 1,
    borderColor: colors.slate100,
  },
  chipText: { flex: 1, fontSize: 11, fontWeight: '600', color: colors.slate700 },
});
