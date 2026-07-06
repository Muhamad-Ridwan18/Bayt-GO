import React, { useState } from 'react';
import {
  View,
  Text,
  StyleSheet,
  TouchableOpacity,
  Platform,
  Modal,
  Pressable,
} from 'react-native';
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

function formatDisplay(value, compact = false) {
  if (!value) return '';
  try {
    return parseIsoDate(value).toLocaleDateString('id-ID', compact
      ? { day: 'numeric', month: 'short' }
      : { day: 'numeric', month: 'short', year: 'numeric' });
  } catch {
    return value;
  }
}

const CALENDAR_DISPLAY = Platform.OS === 'ios' ? 'inline' : 'calendar';

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
  compact = false,
}) {
  const [show, setShow] = useState(false);
  const [draft, setDraft] = useState(parseIsoDate(value));

  const openPicker = () => {
    setDraft(parseIsoDate(value));
    setShow(true);
  };

  const closePicker = () => setShow(false);

  const handleChange = (event, selectedDate) => {
    if (Platform.OS === 'android') {
      closePicker();
      if (event.type === 'dismissed') return;
      if (selectedDate) onChange(toIsoDate(selectedDate));
      return;
    }
    if (selectedDate) setDraft(selectedDate);
  };

  const confirm = () => {
    onChange(toIsoDate(draft));
    closePicker();
  };

  const isChip = variant === 'chip';

  return (
    <View style={[styles.wrap, isChip && styles.wrapChip]}>
      {label && !isChip ? <Text style={styles.label}>{label}</Text> : null}

      <TouchableOpacity
        style={[
          styles.field,
          variant === 'soft' && styles.fieldSoft,
          isChip && styles.fieldChip,
        ]}
        onPress={openPicker}
        activeOpacity={0.88}
      >
        {isChip ? (
          <>
            <View style={styles.chipTopRow}>
              <Text style={styles.chipLabel}>{label}</Text>
              {clearable && value ? (
                <TouchableOpacity
                  onPress={(e) => {
                    e.stopPropagation?.();
                    onClear?.();
                  }}
                  hitSlop={8}
                >
                  <Ionicons name="close-circle" size={14} color={colors.slate400} />
                </TouchableOpacity>
              ) : null}
            </View>
            <View style={styles.chipValueRow}>
              <Ionicons name="calendar" size={15} color={colors.baytgo} />
              <Text style={[styles.chipValue, !value && styles.placeholder]} numberOfLines={1}>
                {value ? formatDisplay(value, true) : placeholder}
              </Text>
            </View>
          </>
        ) : (
          <>
            <Ionicons name="calendar-outline" size={20} color={colors.baytgo} />
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
          </>
        )}
      </TouchableOpacity>

      <Modal visible={show} transparent animationType="slide" onRequestClose={closePicker}>
        <Pressable style={styles.overlay} onPress={closePicker} />
        <View style={styles.sheet}>
          <View style={styles.sheetHandle} />
          <View style={styles.sheetHeader}>
            <Text style={styles.sheetTitle}>{label || 'Pilih tanggal'}</Text>
            <TouchableOpacity onPress={closePicker} hitSlop={12}>
              <Ionicons name="close" size={22} color={colors.slate500} />
            </TouchableOpacity>
          </View>

          <View style={styles.calendarWrap}>
            <DateTimePicker
              value={draft}
              mode="date"
              display={CALENDAR_DISPLAY}
              minimumDate={minimumDate}
              maximumDate={maximumDate}
              onChange={handleChange}
              locale="id-ID"
              themeVariant="light"
              accentColor={colors.baytgo}
              style={Platform.OS === 'ios' ? styles.iosCalendar : undefined}
            />
          </View>

          {Platform.OS === 'ios' ? (
            <TouchableOpacity style={styles.confirmBtn} onPress={confirm} activeOpacity={0.9}>
              <Text style={styles.confirmBtnText}>Pilih tanggal</Text>
            </TouchableOpacity>
          ) : null}
        </View>
      </Modal>
    </View>
  );
}

const styles = StyleSheet.create({
  wrap: { marginBottom: 10 },
  wrapChip: { marginBottom: 0, flex: 1 },
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
  fieldChip: {
    flexDirection: 'column',
    alignItems: 'flex-start',
    gap: 6,
    paddingHorizontal: 12,
    paddingVertical: 12,
    backgroundColor: colors.canvas,
    borderRadius: 16,
    borderColor: 'rgba(26,61,52,0.1)',
  },
  chipLabel: {
    fontSize: 10,
    fontWeight: '800',
    color: colors.slate500,
    textTransform: 'uppercase',
    letterSpacing: 0.3,
  },
  chipTopRow: {
    flexDirection: 'row',
    alignItems: 'center',
    justifyContent: 'space-between',
    width: '100%',
  },
  chipValueRow: {
    flexDirection: 'row',
    alignItems: 'center',
    gap: 6,
    width: '100%',
  },
  chipValue: { flex: 1, fontSize: 14, fontWeight: '800', color: colors.slate900 },
  value: { flex: 1, fontSize: 14, fontWeight: '600', color: colors.slate900 },
  placeholder: { color: colors.slate400, fontWeight: '600' },
  overlay: {
    flex: 1,
    backgroundColor: 'rgba(15,23,42,0.45)',
  },
  sheet: {
    backgroundColor: colors.white,
    borderTopLeftRadius: 24,
    borderTopRightRadius: 24,
    paddingBottom: Platform.OS === 'ios' ? 34 : 24,
    paddingHorizontal: 20,
    shadowColor: '#000',
    shadowOffset: { width: 0, height: -4 },
    shadowOpacity: 0.12,
    shadowRadius: 16,
    elevation: 20,
  },
  sheetHandle: {
    alignSelf: 'center',
    width: 40,
    height: 4,
    borderRadius: 2,
    backgroundColor: colors.slate200,
    marginTop: 10,
    marginBottom: 8,
  },
  sheetHeader: {
    flexDirection: 'row',
    alignItems: 'center',
    justifyContent: 'space-between',
    marginBottom: 8,
  },
  sheetTitle: { fontSize: 17, fontWeight: '900', color: colors.baytgo },
  calendarWrap: {
    alignItems: 'center',
    overflow: 'hidden',
  },
  iosCalendar: { width: '100%', height: 340 },
  confirmBtn: {
    marginTop: 12,
    backgroundColor: colors.baytgo,
    borderRadius: 14,
    paddingVertical: 15,
    alignItems: 'center',
  },
  confirmBtnText: { fontSize: 15, fontWeight: '800', color: colors.white },
});
