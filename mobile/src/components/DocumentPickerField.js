import React from 'react';
import { View, Text, StyleSheet, TouchableOpacity } from 'react-native';
import { Ionicons } from '@expo/vector-icons';
import { colors } from '../theme/colors';

export default function DocumentPickerField({ label, required, file, onPick, onClear }) {
  return (
    <View style={styles.field}>
      <Text style={styles.label}>
        {label}
        {required ? <Text style={styles.required}> *</Text> : null}
      </Text>
      <TouchableOpacity style={styles.btn} onPress={onPick} activeOpacity={0.9}>
        <Ionicons name="document-attach-outline" size={20} color={colors.baytgo} />
        <Text style={styles.btnText} numberOfLines={1}>
          {file?.name || 'Pilih file (PDF/JPG/PNG)'}
        </Text>
      </TouchableOpacity>
      {file ? (
        <TouchableOpacity onPress={onClear}>
          <Text style={styles.clear}>Hapus file</Text>
        </TouchableOpacity>
      ) : null}
    </View>
  );
}

const styles = StyleSheet.create({
  field: { marginBottom: 14 },
  label: { fontSize: 13, fontWeight: '800', color: colors.slate700, marginBottom: 8 },
  required: { color: '#DC2626' },
  btn: {
    flexDirection: 'row',
    alignItems: 'center',
    gap: 10,
    backgroundColor: colors.white,
    borderRadius: 14,
    paddingHorizontal: 14,
    paddingVertical: 14,
    borderWidth: 1,
    borderColor: colors.slate100,
  },
  btnText: { flex: 1, fontSize: 13, fontWeight: '600', color: colors.slate600 },
  clear: { marginTop: 6, fontSize: 12, fontWeight: '700', color: colors.baytgo },
});
