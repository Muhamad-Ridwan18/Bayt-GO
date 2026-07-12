import React, { useState } from 'react';
import {
  View,
  Text,
  StyleSheet,
  Platform,
  Modal,
  Pressable,
} from 'react-native';
import DateTimePicker from '@react-native-community/datetimepicker';
import { Calendar, ChevronDown, X, XCircle } from 'lucide-react-native';
import { PressableScale } from '../ui';
import { colors, spacing, radius, typography } from '../theme/tokens';

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

      <PressableScale
        style={[
          styles.field,
          variant === 'soft' && styles.fieldSoft,
          isChip && styles.fieldChip,
        ]}
        onPress={openPicker}
        haptic="light"
      >
        {isChip ? (
          <>
            <View style={styles.chipTopRow}>
              <Text style={styles.chipLabel}>{label}</Text>
              {clearable && value ? (
                <Pressable
                  onPress={(e) => {
                    e.stopPropagation?.();
                    onClear?.();
                  }}
                  hitSlop={8}
                >
                  <XCircle size={14} color={colors.textMuted} strokeWidth={2} />
                </Pressable>
              ) : null}
            </View>
            <View style={styles.chipValueRow}>
              <Calendar size={15} color={colors.baytgo} strokeWidth={2} />
              <Text style={[styles.chipValue, !value && styles.placeholder]} numberOfLines={1}>
                {value ? formatDisplay(value, true) : placeholder}
              </Text>
            </View>
          </>
        ) : (
          <>
            <Calendar size={20} color={colors.baytgo} strokeWidth={2} />
            <Text style={[styles.value, !value && styles.placeholder]} numberOfLines={1}>
              {value ? formatDisplay(value) : placeholder}
            </Text>
            {clearable && value ? (
              <Pressable
                onPress={(e) => {
                  e.stopPropagation?.();
                  onClear?.();
                }}
                hitSlop={8}
              >
                <XCircle size={18} color={colors.textMuted} strokeWidth={2} />
              </Pressable>
            ) : (
              <ChevronDown size={16} color={colors.textMuted} strokeWidth={2} />
            )}
          </>
        )}
      </PressableScale>

      <Modal visible={show} transparent animationType="slide" onRequestClose={closePicker}>
        <Pressable style={styles.overlay} onPress={closePicker} />
        <View style={styles.sheet}>
          <View style={styles.sheetHandle} />
          <View style={styles.sheetHeader}>
            <Text style={styles.sheetTitle}>{label || 'Pilih tanggal'}</Text>
            <PressableScale onPress={closePicker} haptic="light" hitSlop={12}>
              <X size={22} color={colors.textSecondary} strokeWidth={2} />
            </PressableScale>
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
            <PressableScale style={styles.confirmBtn} onPress={confirm} haptic="medium">
              <Text style={styles.confirmBtnText}>Pilih tanggal</Text>
            </PressableScale>
          ) : null}
        </View>
      </Modal>
    </View>
  );
}

const styles = StyleSheet.create({
  wrap: { marginBottom: spacing.sm + 2 },
  wrapChip: { marginBottom: 0, flex: 1 },
  label: {
    ...typography.label,
    color: colors.textSecondary,
    marginBottom: spacing.xs,
    textTransform: 'uppercase',
  },
  field: {
    flexDirection: 'row',
    alignItems: 'center',
    gap: spacing.md,
    backgroundColor: colors.white,
    borderRadius: radius.sm,
    paddingHorizontal: spacing.md + 2,
    paddingVertical: spacing.md + 2,
    borderWidth: 1,
    borderColor: colors.surface,
  },
  fieldSoft: {
    backgroundColor: colors.canvas,
  },
  fieldChip: {
    flexDirection: 'column',
    alignItems: 'flex-start',
    gap: spacing.sm - 2,
    paddingHorizontal: spacing.md,
    paddingVertical: spacing.md,
    backgroundColor: colors.canvas,
    borderRadius: radius.md,
    borderColor: 'rgba(26,61,52,0.1)',
  },
  chipLabel: {
    ...typography.label,
    fontSize: 10,
    color: colors.textSecondary,
    textTransform: 'uppercase',
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
    gap: spacing.sm - 2,
    width: '100%',
  },
  chipValue: { flex: 1, ...typography.caption, fontWeight: '800', color: colors.textPrimary },
  value: { flex: 1, ...typography.caption, fontWeight: '600', color: colors.textPrimary },
  placeholder: { color: colors.textMuted, fontWeight: '600' },
  overlay: {
    flex: 1,
    backgroundColor: colors.overlay,
  },
  sheet: {
    backgroundColor: colors.white,
    borderTopLeftRadius: radius.lg,
    borderTopRightRadius: radius.lg,
    paddingBottom: Platform.OS === 'ios' ? 34 : spacing['2xl'],
    paddingHorizontal: spacing.xl,
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
    backgroundColor: colors.border,
    marginTop: spacing.sm + 2,
    marginBottom: spacing.sm,
  },
  sheetHeader: {
    flexDirection: 'row',
    alignItems: 'center',
    justifyContent: 'space-between',
    marginBottom: spacing.sm,
  },
  sheetTitle: { ...typography.subtitle, fontSize: 17, fontWeight: '900', color: colors.baytgo },
  calendarWrap: {
    alignItems: 'center',
    overflow: 'hidden',
  },
  iosCalendar: { width: '100%', height: 340 },
  confirmBtn: {
    marginTop: spacing.md,
    backgroundColor: colors.baytgo,
    borderRadius: radius.sm,
    paddingVertical: spacing.md + 3,
    alignItems: 'center',
  },
  confirmBtnText: { ...typography.body, fontSize: 15, fontWeight: '800', color: colors.white },
});
