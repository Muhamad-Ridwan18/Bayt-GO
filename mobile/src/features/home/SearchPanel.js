import React from 'react';
import { StyleSheet, Text, View } from 'react-native';
import { ArrowRight, LayoutGrid, Search } from 'lucide-react-native';
import DatePickerField from '../../components/DatePickerField';
import { Button, PressableScale, SearchBar } from '../../ui';
import { colors, layout, radius, shadows, spacing } from '../../theme/tokens';

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
  onServicePress,
}) {
  return (
    <View style={styles.card}>
      <View style={styles.topRow}>
        <View style={styles.dateCol}>
          <DatePickerField
            label="Berangkat"
            value={startDate}
            onChange={onStartDateChange}
            placeholder="Pilih tanggal"
            minimumDate={today}
            variant="chip"
          />
        </View>
        <View style={styles.dateCol}>
          <DatePickerField
            label="Pulang"
            value={endDate}
            onChange={onEndDateChange}
            placeholder="Opsional"
            minimumDate={endMinDate}
            maximumDate={endMaxDate}
            clearable
            onClear={onClearEndDate}
            variant="chip"
          />
        </View>
        <PressableScale onPress={onServicePress} haptic="light" style={styles.serviceBox}>
          <LayoutGrid size={16} color={colors.baytgo} strokeWidth={2.2} />
          <Text style={styles.serviceLabel}>Pilihan Layanan</Text>
          <Text style={styles.serviceValue} numberOfLines={1}>Semua Layanan</Text>
        </PressableScale>
      </View>

      <SearchBar
        value={searchName}
        onChangeText={onSearchNameChange}
        placeholder="Cari nama muthowif atau bahasa"
        style={styles.searchBar}
      />

      <Button
        label="Cari Muthowif"
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
  topRow: { flexDirection: 'row', gap: spacing.sm, marginBottom: spacing.md },
  dateCol: { flex: 1 },
  serviceBox: {
    width: 92,
    borderRadius: radius.sm - 2,
    backgroundColor: colors.background,
    borderWidth: 1,
    borderColor: colors.border,
    paddingHorizontal: spacing.sm,
    paddingVertical: spacing.sm,
    justifyContent: 'center',
  },
  serviceLabel: {
    marginTop: spacing.xs,
    fontSize: 9,
    fontWeight: '600',
    fontFamily: 'PlusJakartaSans_600SemiBold',
    color: colors.textSecondary,
  },
  serviceValue: {
    marginTop: 2,
    fontSize: 10,
    fontWeight: '700',
    fontFamily: 'PlusJakartaSans_700Bold',
    color: colors.baytgo,
  },
  searchBar: { marginBottom: spacing.md, ...shadows.sm, shadowOpacity: 0.03, elevation: 1 },
});
