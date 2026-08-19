import React from 'react';
import { StyleSheet, Text, View } from 'react-native';
import { Search } from 'lucide-react-native';
import DatePickerField from '../../components/DatePickerField';
import { Button, PressableScale } from '../../ui';
import { colors, radius, shadows, spacing, typography } from '../../theme/tokens';
import { useLocale } from '../../utils/locale';
import { HOME_CATEGORIES } from './FeatureChips';

export default function SearchPanel({
  selectedKey,
  onSelectCategory,
  startDate,
  endDate,
  onStartDateChange,
  onEndDateChange,
  onClearEndDate,
  startsAt,
  onStartsAtChange,
  today,
  endMinDate,
  endMaxDate,
  dateError,
  onSearch,
}) {
  const locale = useLocale();
  const isEn = locale === 'en';
  const categories = HOME_CATEGORIES[locale] || HOME_CATEGORIES.id;
  const selected = categories.find((c) => c.key === selectedKey) || categories[0];
  const isLayanan = selected?.type === 'layanan';

  return (
    <View style={styles.wrap}>
      <View style={styles.tabs} accessibilityRole="tablist">
        {categories.map((cat) => {
          const active = cat.key === selected.key;
          return (
            <PressableScale
              key={cat.key}
              onPress={() => onSelectCategory(cat)}
              haptic="light"
              style={[styles.tab, active && styles.tabActive]}
              accessibilityRole="tab"
              accessibilityState={{ selected: active }}
            >
              <View style={styles.tabIcon}>
                <cat.Icon size={18} color={active ? colors.baytgo : colors.white} strokeWidth={2} />
              </View>
              <Text style={[styles.tabLabel, active && styles.tabLabelActive]} numberOfLines={2}>
                {cat.title}
              </Text>
            </PressableScale>
          );
        })}
      </View>

      <View style={styles.divider} />

      <View style={styles.card}>
        {isLayanan ? (
          <View style={styles.dateRow}>
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
        ) : (
          <View style={styles.slotWrap}>
            <DatePickerField
              label={isEn ? 'Start date & time' : 'Tanggal & jam mulai'}
              value={startsAt}
              onChange={onStartsAtChange}
              placeholder={isEn ? 'Select date & time' : 'Pilih tanggal & jam'}
              minimumDate={today}
              mode="datetime"
              variant="chip"
            />
          </View>
        )}

        {dateError ? <Text style={styles.error}>{dateError}</Text> : null}

        <Button
          label={isEn ? 'Search Now' : 'Cari Sekarang'}
          onPress={onSearch}
          icon={<Search size={16} color={colors.white} strokeWidth={2.4} />}
        />
      </View>
    </View>
  );
}

const styles = StyleSheet.create({
  wrap: { marginTop: spacing.md },
  tabs: {
    flexDirection: 'row',
    gap: 4,
  },
  tab: {
    flex: 1,
    minWidth: 0,
    alignItems: 'center',
    gap: 6,
    paddingHorizontal: 2,
    paddingVertical: 10,
    borderRadius: radius.sm,
  },
  tabActive: {
    backgroundColor: colors.white,
  },
  tabIcon: {
    width: 32,
    height: 32,
    alignItems: 'center',
    justifyContent: 'center',
  },
  tabLabel: {
    width: '100%',
    ...typography.small,
    fontSize: 9,
    lineHeight: 12,
    fontFamily: 'PlusJakartaSans_700Bold',
    fontWeight: '700',
    color: 'rgba(255,255,255,0.9)',
    textAlign: 'center',
  },
  tabLabelActive: {
    color: colors.baytgo,
  },
  divider: {
    height: StyleSheet.hairlineWidth,
    backgroundColor: 'rgba(255,255,255,0.2)',
    marginTop: spacing.md,
    marginBottom: spacing.md,
  },
  card: {
    backgroundColor: colors.card,
    borderRadius: radius.md,
    padding: spacing.md,
    ...shadows.float,
  },
  dateRow: { flexDirection: 'row', gap: spacing.md, marginBottom: spacing.md },
  dateCol: { flex: 1 },
  slotWrap: { marginBottom: spacing.md },
  error: {
    ...typography.small,
    color: colors.error,
    marginTop: spacing.sm,
    marginBottom: spacing.sm,
    fontWeight: '700',
  },
});
