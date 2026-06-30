import React, { useState } from 'react';
import { View, Text, StyleSheet, TouchableOpacity, Platform } from 'react-native';
import DateTimePicker from '@react-native-community/datetimepicker';
import { Ionicons } from '@expo/vector-icons';
import { colors } from '../theme/colors';

export function toIsoDate(date) {
  const y = date.getFullYear();
  const m = String(date.getMonth() + 1).padStart(2, '0');
  const d = String(date.getDate()).padStart(2, '0');
  return `${y}-${m}-${d}`;
}

export function parseIsoDate(value) {
  if (!value) return new Date();
  const match = /^(\d{4})-(\d{2})-(\d{2})$/.exec(value);
  if (!match) return new Date();
  const [, y, m, d] = match;
  const parsed = new Date(Number(y), Number(m) - 1, Number(d));
  return Number.isNaN(parsed.getTime()) ? new Date() : parsed;
}

function formatDisplay(value) {
  if (!value) return '';
  try {
    return parseIsoDate(value).toLocaleDateString('id-ID', {
      day: 'numeric',
      month: 'short',
      year: 'numeric',
    });
  } catch {
    return value;
  }
}

export default function DatePickerField({
  label,
  value,
  onChange,
  placeholder = 'Pilih tanggal',
  minimumDate,
  maximumDate,
  onClear,
  clearable = false,
  variant = 'default',
}) {
  const [show, setShow] = useState(false);
  const [iosDraft, setIosDraft] = useState(parseIsoDate(value));

  const openPicker = () => {
    setIosDraft(parseIsoDate(value));
    setShow(true);
  };

  const handleChange = (event, selectedDate) => {
    if (Platform.OS === 'android') {
      setShow(false);
      if (event.type === 'dismissed') return;
      if (selectedDate) onChange(toIsoDate(selectedDate));
      return;
    }
    if (selectedDate) setIosDraft(selectedDate);
  };

  const confirmIos = () => {
    onChange(toIsoDate(iosDraft));
    setShow(false);
  };

  return (
    <View style={styles.wrap}>
      {label ? <Text style={styles.label}>{label}</Text> : null}
      <TouchableOpacity
        style={[styles.field, variant === 'soft' && styles.fieldSoft]}
        onPress={openPicker}
        activeOpacity={0.9}
      >
        <Ionicons name="calendar-outline" size={20} color={colors.slate400} />
        <Text style={[styles.value, !value && styles.placeholder]} numberOfLines={1}>
          {value ? formatDisplay(value) : placeholder}
        </Text>
        {clearable && value ? (
          <TouchableOpacity
            onPress={(e) => {
              e.stopPropagation?.();
              onClear?.();
            }}
            hitSlop={8}
          >
            <Ionicons name="close-circle" size={18} color={colors.slate400} />
          </TouchableOpacity>
        ) : (
          <Ionicons name="chevron-down" size={16} color={colors.slate400} />
        )}
      </TouchableOpacity>

      {show && Platform.OS === 'android' ? (
        <DateTimePicker
          value={parseIsoDate(value)}
          mode="date"
          display="default"
          minimumDate={minimumDate}
          maximumDate={maximumDate}
          onChange={handleChange}
        />
      ) : null}

      {show && Platform.OS === 'ios' ? (
        <View style={styles.iosSheet}>
          <View style={styles.iosToolbar}>
            <TouchableOpacity onPress={() => setShow(false)}>
              <Text style={styles.iosCancel}>Batal</Text>
            </TouchableOpacity>
            <TouchableOpacity onPress={confirmIos}>
              <Text style={styles.iosDone}>Selesai</Text>
            </TouchableOpacity>
          </View>
          <DateTimePicker
            value={iosDraft}
            mode="date"
            display="spinner"
            minimumDate={minimumDate}
            maximumDate={maximumDate}
            onChange={handleChange}
            locale="id-ID"
            style={styles.iosPicker}
          />
        </View>
      ) : null}
    </View>
  );
}

const styles = StyleSheet.create({
  wrap: { marginBottom: 10 },
  label: {
    fontSize: 11,
    fontWeight: '800',
    color: colors.slate500,
    marginBottom: 4,
    textTransform: 'uppercase',
  },
  field: {
    flexDirection: 'row',
    alignItems: 'center',
    gap: 12,
    backgroundColor: colors.white,
    borderRadius: 14,
    paddingHorizontal: 14,
    paddingVertical: 14,
    borderWidth: 1,
    borderColor: colors.slate100,
  },
  fieldSoft: {
    backgroundColor: colors.canvas,
  },
  value: { flex: 1, fontSize: 14, fontWeight: '600', color: colors.slate900 },
  placeholder: { color: colors.slate400 },
  iosSheet: {
    marginTop: 8,
    backgroundColor: colors.white,
    borderRadius: 16,
    borderWidth: 1,
    borderColor: colors.slate100,
    overflow: 'hidden',
  },
  iosToolbar: {
    flexDirection: 'row',
    justifyContent: 'space-between',
    paddingHorizontal: 16,
    paddingVertical: 12,
    borderBottomWidth: 1,
    borderBottomColor: colors.slate100,
  },
  iosCancel: { fontSize: 15, fontWeight: '600', color: colors.slate500 },
  iosDone: { fontSize: 15, fontWeight: '800', color: colors.baytgo },
  iosPicker: { height: 180 },
});
