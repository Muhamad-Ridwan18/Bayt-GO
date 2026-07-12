import React, { useMemo, useState } from 'react';
import {
  View,
  Text,
  TextInput,
  Modal,
  StyleSheet,
} from 'react-native';
import { FlashList } from '@shopify/flash-list';
import { ChevronDown, X } from 'lucide-react-native';
import {
  PHONE_COUNTRIES,
  DEFAULT_PHONE_COUNTRY,
  buildFullPhone,
  findCountryByIso,
  findCountryByDial,
} from '../utils/phoneCountries';
import { PressableScale } from '../ui';
import { colors, spacing, radius, typography } from '../theme/tokens';

export default function PhoneInternationalInput({
  label,
  dial,
  national,
  countryIso,
  onChange,
  hint,
}) {
  const [pickerOpen, setPickerOpen] = useState(false);
  const [query, setQuery] = useState('');

  const selected =
    findCountryByIso(countryIso) ||
    findCountryByDial(dial) ||
    DEFAULT_PHONE_COUNTRY;

  const filtered = useMemo(() => {
    const q = query.trim().toLowerCase().replace(/^\+/, '');
    if (!q) return PHONE_COUNTRIES;
    return PHONE_COUNTRIES.filter((c) => {
      return (
        c.name.toLowerCase().includes(q) ||
        c.d.includes(q) ||
        c.iso.toLowerCase().includes(q)
      );
    });
  }, [query]);

  const pickCountry = (country) => {
    onChange({
      dial: country.d,
      national,
      countryIso: country.iso,
      fullPhone: buildFullPhone(country.d, national),
    });
    setPickerOpen(false);
    setQuery('');
  };

  const updateNational = (value) => {
    onChange({
      dial,
      national: value,
      countryIso,
      fullPhone: buildFullPhone(dial, value),
    });
  };

  const placeholder =
    dial === '62' ? '812 3456 7890' : dial === '1' ? '(201) 555-0123' : '8123456789';

  return (
    <View style={styles.wrap}>
      {label ? <Text style={styles.label}>{label}</Text> : null}
      <View style={styles.field}>
        <PressableScale style={styles.countryBtn} onPress={() => setPickerOpen(true)} haptic="light">
          <Text style={styles.flag}>{selected.flag}</Text>
          <Text style={styles.dial}>+{dial || selected.d}</Text>
          <ChevronDown size={14} color={colors.textMuted} strokeWidth={2} />
        </PressableScale>
        <TextInput
          style={styles.input}
          value={national}
          onChangeText={updateNational}
          placeholder={placeholder}
          keyboardType="phone-pad"
        />
      </View>
      {hint ? <Text style={styles.hint}>{hint}</Text> : null}

      <Modal visible={pickerOpen} animationType="slide" onRequestClose={() => setPickerOpen(false)}>
        <View style={styles.modal}>
          <View style={styles.modalHeader}>
            <Text style={styles.modalTitle}>Pilih negara</Text>
            <PressableScale onPress={() => setPickerOpen(false)} haptic="light">
              <X size={24} color={colors.slate600} strokeWidth={2} />
            </PressableScale>
          </View>
          <TextInput
            style={styles.search}
            value={query}
            onChangeText={setQuery}
            placeholder="Cari negara atau kode..."
            autoCapitalize="none"
          />
          <FlashList
            data={filtered}
            keyExtractor={(item) => item.iso || item.d}
            estimatedItemSize={52}
            renderItem={({ item }) => (
              <PressableScale style={styles.countryRow} onPress={() => pickCountry(item)} haptic="light">
                <Text style={styles.flag}>{item.flag}</Text>
                <Text style={styles.countryName}>{item.name}</Text>
                <Text style={styles.countryDial}>+{item.d}</Text>
              </PressableScale>
            )}
            ListEmptyComponent={
              <Text style={styles.empty}>Negara tidak ditemukan.</Text>
            }
          />
        </View>
      </Modal>
    </View>
  );
}

const styles = StyleSheet.create({
  wrap: { marginBottom: spacing.md + 2 },
  label: { ...typography.caption, fontWeight: '800', color: colors.slate600, marginBottom: spacing.sm, marginLeft: 2 },
  field: {
    flexDirection: 'row',
    alignItems: 'center',
    backgroundColor: colors.white,
    borderRadius: radius.md,
    borderWidth: 1,
    borderColor: colors.surface,
    overflow: 'hidden',
  },
  countryBtn: {
    flexDirection: 'row',
    alignItems: 'center',
    gap: spacing.sm - 2,
    paddingHorizontal: spacing.md,
    paddingVertical: spacing.md + 2,
    borderRightWidth: 1,
    borderRightColor: colors.surface,
    backgroundColor: colors.canvas,
  },
  flag: { fontSize: 18 },
  dial: { ...typography.caption, fontWeight: '800', color: colors.slate700 },
  input: {
    flex: 1,
    paddingHorizontal: spacing.md + 2,
    paddingVertical: spacing.md + 2,
    ...typography.body,
    fontWeight: '600',
    color: colors.textPrimary,
  },
  hint: { marginTop: spacing.sm - 2, ...typography.small, fontWeight: '600', color: colors.textSecondary, marginLeft: 2 },
  modal: { flex: 1, backgroundColor: colors.canvas, paddingTop: spacing['5xl'] },
  modalHeader: {
    flexDirection: 'row',
    alignItems: 'center',
    justifyContent: 'space-between',
    paddingHorizontal: spacing.xl,
    marginBottom: spacing.md,
  },
  modalTitle: { ...typography.subtitle, fontWeight: '900', color: colors.baytgo },
  search: {
    marginHorizontal: spacing.xl,
    marginBottom: spacing.md,
    backgroundColor: colors.white,
    borderRadius: radius.sm,
    borderWidth: 1,
    borderColor: colors.surface,
    paddingHorizontal: spacing.md + 2,
    paddingVertical: spacing.md,
    ...typography.body,
    fontWeight: '600',
  },
  countryRow: {
    flexDirection: 'row',
    alignItems: 'center',
    gap: spacing.md,
    paddingHorizontal: spacing.xl,
    paddingVertical: spacing.md + 2,
    borderBottomWidth: 1,
    borderBottomColor: colors.surface,
    backgroundColor: colors.white,
  },
  countryName: { flex: 1, ...typography.body, fontWeight: '600', color: colors.textPrimary },
  countryDial: { ...typography.caption, fontWeight: '700', color: colors.textSecondary },
  empty: { textAlign: 'center', padding: spacing['2xl'], color: colors.textSecondary, fontWeight: '600' },
});
