import React from 'react';
import { StyleSheet, View } from 'react-native';
import { ArrowRight } from 'lucide-react-native';
import DatePickerField from '../../components/DatePickerField';
import { Button, SearchBar } from '../../ui';
import { colors, layout, radius, shadows, spacing } from '../../theme/tokens';
import { useLocale } from '../../utils/locale';

export default function SearchPanel({
  searchName,
  onSearchNameChange,
  startDate,
  endDate,
  onStartDateChange,
  onEndDateChange,
  onClearEndDate,
  today,
  endMinDate,
  endMaxDate,
  onSearch,
}) {
  const locale = useLocale();
  const isEn = locale === 'en';
  return (
    <View style={styles.card}>
      <View style={styles.topRow}>
        <View style={styles.dateCol}>
          <DatePickerField
            label={isEn ? 'Departure' : 'Berangkat'}
            value={startDate}
            onChange={onStartDateChange}
            placeholder={isEn ? 'Select date' : 'Pilih tanggal'}
            minimumDate={today}
            variant="chip"
          />
        </View>
        <View style={styles.dateCol}>
          <DatePickerField
            label={isEn ? 'Return' : 'Pulang'}
            value={endDate}
            onChange={onEndDateChange}
            placeholder={isEn ? 'Optional' : 'Opsional'}
            minimumDate={endMinDate}
            maximumDate={endMaxDate}
            clearable
            onClear={onClearEndDate}
            variant="chip"
          />
        </View>
      </View>

      <SearchBar
        value={searchName}
        onChangeText={onSearchNameChange}
        placeholder={isEn ? 'Search muthowif name or language' : 'Cari nama muthowif atau bahasa'}
        style={styles.searchBar}
      />

      <Button
        label={isEn ? 'Find a Muthowif' : 'Cari Muthowif'}
        onPress={onSearch}
        icon={<ArrowRight size={16} color={colors.white} strokeWidth={2.4} />}
      />
    </View>
  );
}

const styles = StyleSheet.create({
  card: {
    marginHorizontal: layout.screenPadding,
    marginTop: spacing.md,
    backgroundColor: colors.card,
    borderRadius: radius.md,
    padding: spacing.lg,
    borderWidth: 1,
    borderColor: 'rgba(26,61,52,0.08)',
    ...shadows.float,
  },
  topRow: { flexDirection: 'row', gap: spacing.md, marginBottom: spacing.md },
  dateCol: { flex: 1 },
  searchBar: { marginBottom: spacing.md, ...shadows.sm, shadowOpacity: 0.03, elevation: 1 },
});
