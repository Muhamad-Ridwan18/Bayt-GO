import React from 'react';
import { StyleSheet, Text, View } from 'react-native';
import FilterChip from '../ui/FilterChip';
import { setStoredLocale, useLocale } from '../utils/locale';
import { colors, spacing, typography } from '../theme/tokens';

const OPTIONS = [
  { value: 'id', label: 'ID', title: 'Bahasa Indonesia' },
  { value: 'en', label: 'EN', title: 'English' },
];

export default function LanguageSwitcher({ onChange }) {
  const locale = useLocale();

  const handlePress = async (next) => {
    if (next === locale) return;
    await setStoredLocale(next);
    onChange?.(next);
  };

  return (
    <View style={styles.wrap}>
      <Text style={styles.title}>Bahasa</Text>
      <View style={styles.row}>
        {OPTIONS.map((option) => (
          <FilterChip
            key={option.value}
            label={option.label}
            active={locale === option.value}
            onPress={() => handlePress(option.value)}
          />
        ))}
      </View>
    </View>
  );
}

const styles = StyleSheet.create({
  wrap: { gap: spacing.sm },
  title: {
    ...typography.label,
    color: colors.textSecondary,
    textTransform: 'uppercase',
  },
  row: { flexDirection: 'row', flexWrap: 'wrap', gap: spacing.sm },
});
