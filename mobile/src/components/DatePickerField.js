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

/** Match web datetime-local: YYYY-MM-DDTHH:mm */
export function toIsoDateTime(date) {
  const y = date.getFullYear();
  const m = String(date.getMonth() + 1).padStart(2, '0');
  const d = String(date.getDate()).padStart(2, '0');
  const h = String(date.getHours()).padStart(2, '0');
  const min = String(date.getMinutes()).padStart(2, '0');
  return `${y}-${m}-${d}T${h}:${min}`;
}

export function parseIsoDate(value) {
  if (!value) return new Date();
  const match = /^(\d{4})-(\d{2})-(\d{2})$/.exec(value);
  if (!match) return new Date();
  const [, y, m, d] = match;
  const parsed = new Date(Number(y), Number(m) - 1, Number(d));
  return Number.isNaN(parsed.getTime()) ? new Date() : parsed;
}

export function parseIsoDateTime(value) {
  if (!value) return new Date();
  const dateOnly = /^(\d{4})-(\d{2})-(\d{2})$/.exec(String(value));
  if (dateOnly) {
    return new Date(Number(dateOnly[1]), Number(dateOnly[2]) - 1, Number(dateOnly[3]), 9, 0, 0);
  }
  const match = /^(\d{4})-(\d{2})-(\d{2})[T\s](\d{2}):(\d{2})(?::(\d{2}))?/.exec(String(value));
  if (!match) return new Date();
  const [, y, m, d, h, min, s] = match;
  const parsed = new Date(
    Number(y),
    Number(m) - 1,
    Number(d),
    Number(h),
    Number(min),
    Number(s || 0),
  );
  return Number.isNaN(parsed.getTime()) ? new Date() : parsed;
}

function formatDisplay(value, { compact = false, withTime = false } = {}) {
  if (!value) return '';
  try {
    const date = withTime ? parseIsoDateTime(value) : parseIsoDate(value);
    if (withTime) {
      return date.toLocaleString('id-ID', {
        day: 'numeric',
        month: compact ? 'short' : 'short',
        year: compact ? undefined : 'numeric',
        hour: '2-digit',
        minute: '2-digit',
      });
    }
    return date.toLocaleDateString('id-ID', compact
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
  placeholder,
  minimumDate,
  maximumDate,
  onClear,
  clearable = false,
  variant = 'default',
  compact = false,
  mode = 'date',
}) {
  const isDateTime = mode === 'datetime';
  const parseValue = isDateTime ? parseIsoDateTime : parseIsoDate;
  const serialize = isDateTime ? toIsoDateTime : toIsoDate;
  const resolvedPlaceholder = placeholder
    || (isDateTime ? 'Pilih tanggal & jam' : 'Pilih tanggal');

  const [show, setShow] = useState(false);
  const [draft, setDraft] = useState(parseValue(value));
  const [androidStep, setAndroidStep] = useState('date'); // date | time

  const openPicker = () => {
    setDraft(parseValue(value));
    setAndroidStep('date');
    setShow(true);
  };

  const closePicker = () => {
    setShow(false);
    setAndroidStep('date');
  };

  const commit = (date) => {
    onChange(serialize(date));
    closePicker();
  };

  const handleChange = (event, selectedDate) => {
    if (Platform.OS === 'android') {
      if (event.type === 'dismissed') {
        closePicker();
        return;
      }
      if (!selectedDate) {
        closePicker();
        return;
      }

      if (isDateTime && androidStep === 'date') {
        const next = new Date(selectedDate);
        next.setHours(draft.getHours(), draft.getMinutes(), 0, 0);
        setDraft(next);
        setAndroidStep('time');
        return;
      }

      if (isDateTime && androidStep === 'time') {
        const next = new Date(draft);
        next.setHours(selectedDate.getHours(), selectedDate.getMinutes(), 0, 0);
        commit(next);
        return;
      }

      commit(selectedDate);
      return;
    }

    if (selectedDate) setDraft(selectedDate);
  };

  const handleTimeChange = (_event, selectedDate) => {
    if (selectedDate) {
      const next = new Date(draft);
      next.setHours(selectedDate.getHours(), selectedDate.getMinutes(), 0, 0);
      setDraft(next);
    }
  };

  const confirm = () => commit(draft);

  const isChip = variant === 'chip';
  const displayValue = value
    ? formatDisplay(value, { compact, withTime: isDateTime })
    : '';

  // Android datetime: native dialogs (date then time) — no custom sheet.
  if (Platform.OS === 'android' && show && isDateTime) {
    return (
      <View style={[styles.wrap, isChip && styles.wrapChip]}>
        {label && !isChip ? <Text style={styles.label}>{label}</Text> : null}
        <FieldButton
          isChip={isChip}
          label={label}
          clearable={clearable}
          value={value}
          displayValue={displayValue}
          placeholder={resolvedPlaceholder}
          onPress={openPicker}
          onClear={onClear}
          variant={variant}
        />
        <DateTimePicker
          key={androidStep}
          value={draft}
          mode={isDateTime && androidStep === 'time' ? 'time' : 'date'}
          display={isDateTime && androidStep === 'time' ? 'default' : CALENDAR_DISPLAY}
          is24Hour
          minimumDate={androidStep === 'date' ? minimumDate : undefined}
          maximumDate={androidStep === 'date' ? maximumDate : undefined}
          onChange={handleChange}
        />
      </View>
    );
  }

  return (
    <View style={[styles.wrap, isChip && styles.wrapChip]}>
      {label && !isChip ? <Text style={styles.label}>{label}</Text> : null}

      <FieldButton
        isChip={isChip}
        label={label}
        clearable={clearable}
        value={value}
        displayValue={displayValue}
        placeholder={resolvedPlaceholder}
        onPress={openPicker}
        onClear={onClear}
        variant={variant}
      />

      <Modal visible={show} transparent animationType="slide" onRequestClose={closePicker}>
        <Pressable style={styles.overlay} onPress={closePicker} />
        <View style={styles.sheet}>
          <View style={styles.sheetHandle} />
          <View style={styles.sheetHeader}>
            <Text style={styles.sheetTitle}>
              {label || (isDateTime ? 'Pilih tanggal & jam' : 'Pilih tanggal')}
            </Text>
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
            {isDateTime ? (
              <View style={styles.timeWrap}>
                <Text style={styles.timeLabel}>Jam mulai</Text>
                <DateTimePicker
                  value={draft}
                  mode="time"
                  display="spinner"
                  is24Hour
                  minuteInterval={5}
                  onChange={handleTimeChange}
                  locale="id-ID"
                  themeVariant="light"
                  accentColor={colors.baytgo}
                  style={styles.iosTime}
                />
              </View>
            ) : null}
          </View>

          {Platform.OS === 'ios' ? (
            <PressableScale style={styles.confirmBtn} onPress={confirm} haptic="medium">
              <Text style={styles.confirmBtnText}>
                {isDateTime ? 'Pilih tanggal & jam' : 'Pilih tanggal'}
              </Text>
            </PressableScale>
          ) : null}
        </View>
      </Modal>
    </View>
  );
}

function FieldButton({
  isChip,
  label,
  clearable,
  value,
  displayValue,
  placeholder,
  onPress,
  onClear,
  variant,
}) {
  return (
    <PressableScale
      style={[
        styles.field,
        variant === 'soft' && styles.fieldSoft,
        isChip && styles.fieldChip,
      ]}
      onPress={onPress}
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
              {value ? displayValue : placeholder}
            </Text>
          </View>
        </>
      ) : (
        <>
          <Calendar size={20} color={colors.baytgo} strokeWidth={2} />
          <Text style={[styles.value, !value && styles.placeholder]} numberOfLines={1}>
            {value ? displayValue : placeholder}
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
  timeWrap: {
    width: '100%',
    marginTop: spacing.sm,
    borderTopWidth: 1,
    borderTopColor: colors.border,
    paddingTop: spacing.sm,
  },
  timeLabel: {
    ...typography.label,
    color: colors.textSecondary,
    textTransform: 'uppercase',
    marginBottom: spacing.xs,
  },
  iosTime: { width: '100%', height: 160 },
  confirmBtn: {
    marginTop: spacing.md,
    backgroundColor: colors.baytgo,
    borderRadius: radius.sm,
    paddingVertical: spacing.md + 3,
    alignItems: 'center',
  },
  confirmBtnText: { ...typography.body, fontSize: 15, fontWeight: '800', color: colors.white },
});
