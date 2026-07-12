import React from 'react';
import { StyleSheet, Text, View } from 'react-native';
import { FilterChip } from '../../ui';
import { colors, spacing, typography } from '../../theme/tokens';

export function ChipPicker({ label, options, value, onChange }) {
  return (
    <View style={styles.field}>
      <Text style={styles.label}>{label}</Text>
      <View style={styles.chipList}>
        {options.map((item) => (
          <FilterChip
            key={item.value}
            label={item.label}
            active={value === item.value}
            onPress={() => onChange(item.value)}
          />
        ))}
      </View>
    </View>
  );
}

const styles = StyleSheet.create({
  field: { marginBottom: spacing.lg },
  label: { ...typography.label, color: colors.textSecondary, marginBottom: spacing.sm },
  chipList: { flexDirection: 'row', flexWrap: 'wrap', gap: spacing.sm },
});
