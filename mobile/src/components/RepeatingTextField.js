import React from 'react';
import { View, Text, TextInput, TouchableOpacity, StyleSheet } from 'react-native';
import { Ionicons } from '@expo/vector-icons';
import { colors } from '../theme/colors';

export default function RepeatingTextField({
  label,
  items,
  onChange,
  placeholder,
  addLabel = 'Tambah baris',
  optional = false,
  multiline = false,
}) {
  const rows = items?.length ? items : [''];

  const updateRow = (index, value) => {
    const next = [...rows];
    next[index] = value;
    onChange(next);
  };

  const addRow = () => onChange([...rows, '']);

  const removeRow = (index) => {
    if (rows.length <= 1) {
      onChange(['']);
      return;
    }
    onChange(rows.filter((_, i) => i !== index));
  };

  return (
    <View style={styles.wrap}>
      <Text style={styles.label}>
        {label}
        {optional ? ' (opsional)' : ''}
      </Text>
      {rows.map((row, index) => (
        <View key={`row-${index}`} style={styles.row}>
          <TextInput
            style={[styles.input, multiline && styles.inputMultiline]}
            value={row}
            onChangeText={(v) => updateRow(index, v)}
            placeholder={placeholder}
            multiline={multiline}
          />
          <TouchableOpacity style={styles.removeBtn} onPress={() => removeRow(index)} hitSlop={8}>
            <Ionicons name="trash-outline" size={18} color="#B91C1C" />
          </TouchableOpacity>
        </View>
      ))}
      <TouchableOpacity style={styles.addBtn} onPress={addRow}>
        <Ionicons name="add-circle-outline" size={16} color={colors.baytgo} />
        <Text style={styles.addText}>{addLabel}</Text>
      </TouchableOpacity>
    </View>
  );
}

const styles = StyleSheet.create({
  wrap: { marginBottom: 14 },
  label: { fontSize: 12, fontWeight: '800', color: colors.slate600, marginBottom: 8, marginLeft: 2 },
  row: { flexDirection: 'row', alignItems: 'flex-start', gap: 8, marginBottom: 8 },
  input: {
    flex: 1,
    backgroundColor: colors.white,
    borderRadius: 16,
    borderWidth: 1,
    borderColor: colors.slate100,
    paddingHorizontal: 14,
    paddingVertical: 12,
    fontSize: 15,
    fontWeight: '600',
    color: colors.slate900,
  },
  inputMultiline: { minHeight: 72, textAlignVertical: 'top' },
  removeBtn: { marginTop: 12 },
  addBtn: { flexDirection: 'row', alignItems: 'center', gap: 6 },
  addText: { fontSize: 12, fontWeight: '800', color: colors.baytgo },
});
