import React, { useMemo, useState } from 'react';
import {
  View,
  Text,
  TextInput,
  TouchableOpacity,
  Modal,
  FlatList,
  StyleSheet,
} from 'react-native';
import { Ionicons } from '@expo/vector-icons';
import {
  PHONE_COUNTRIES,
  DEFAULT_PHONE_COUNTRY,
  buildFullPhone,
  findCountryByIso,
  findCountryByDial,
} from '../utils/phoneCountries';
import { colors } from '../theme/colors';

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
        <TouchableOpacity style={styles.countryBtn} onPress={() => setPickerOpen(true)}>
          <Text style={styles.flag}>{selected.flag}</Text>
          <Text style={styles.dial}>+{dial || selected.d}</Text>
          <Ionicons name="chevron-down" size={14} color={colors.slate400} />
        </TouchableOpacity>
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
            <TouchableOpacity onPress={() => setPickerOpen(false)}>
              <Ionicons name="close" size={24} color={colors.slate600} />
            </TouchableOpacity>
          </View>
          <TextInput
            style={styles.search}
            value={query}
            onChangeText={setQuery}
            placeholder="Cari negara atau kode..."
            autoCapitalize="none"
          />
          <FlatList
            data={filtered}
            keyExtractor={(item) => item.iso || item.d}
            renderItem={({ item }) => (
              <TouchableOpacity style={styles.countryRow} onPress={() => pickCountry(item)}>
                <Text style={styles.flag}>{item.flag}</Text>
                <Text style={styles.countryName}>{item.name}</Text>
                <Text style={styles.countryDial}>+{item.d}</Text>
              </TouchableOpacity>
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
  wrap: { marginBottom: 14 },
  label: { fontSize: 12, fontWeight: '800', color: colors.slate600, marginBottom: 8, marginLeft: 2 },
  field: {
    flexDirection: 'row',
    alignItems: 'center',
    backgroundColor: colors.white,
    borderRadius: 16,
    borderWidth: 1,
    borderColor: colors.slate100,
    overflow: 'hidden',
  },
  countryBtn: {
    flexDirection: 'row',
    alignItems: 'center',
    gap: 6,
    paddingHorizontal: 12,
    paddingVertical: 14,
    borderRightWidth: 1,
    borderRightColor: colors.slate100,
    backgroundColor: '#F8FAFC',
  },
  flag: { fontSize: 18 },
  dial: { fontSize: 14, fontWeight: '800', color: colors.slate700 },
  input: {
    flex: 1,
    paddingHorizontal: 14,
    paddingVertical: 14,
    fontSize: 15,
    fontWeight: '600',
    color: colors.slate900,
  },
  hint: { marginTop: 6, fontSize: 11, fontWeight: '600', color: colors.slate500, marginLeft: 2 },
  modal: { flex: 1, backgroundColor: colors.canvas, paddingTop: 48 },
  modalHeader: {
    flexDirection: 'row',
    alignItems: 'center',
    justifyContent: 'space-between',
    paddingHorizontal: 20,
    marginBottom: 12,
  },
  modalTitle: { fontSize: 18, fontWeight: '900', color: colors.baytgo },
  search: {
    marginHorizontal: 20,
    marginBottom: 12,
    backgroundColor: colors.white,
    borderRadius: 14,
    borderWidth: 1,
    borderColor: colors.slate100,
    paddingHorizontal: 14,
    paddingVertical: 12,
    fontSize: 15,
    fontWeight: '600',
  },
  countryRow: {
    flexDirection: 'row',
    alignItems: 'center',
    gap: 12,
    paddingHorizontal: 20,
    paddingVertical: 14,
    borderBottomWidth: 1,
    borderBottomColor: colors.slate100,
    backgroundColor: colors.white,
  },
  countryName: { flex: 1, fontSize: 15, fontWeight: '600', color: colors.slate900 },
  countryDial: { fontSize: 14, fontWeight: '700', color: colors.slate500 },
  empty: { textAlign: 'center', padding: 24, color: colors.slate500, fontWeight: '600' },
});
