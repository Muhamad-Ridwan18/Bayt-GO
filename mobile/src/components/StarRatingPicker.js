import React from 'react';
import { View, Text, StyleSheet } from 'react-native';
import { Star } from 'lucide-react-native';
import { PressableScale } from '../ui';
import { colors, spacing, radius, typography } from '../theme/tokens';

export default function StarRatingPicker({ value, onChange, label }) {
  return (
    <View style={styles.wrap}>
      {label ? <Text style={styles.label}>{label}</Text> : null}
      <View style={styles.row}>
        {Array.from({ length: 5 }).map((_, i) => {
          const star = i + 1;
          const active = star <= value;
          return (
            <PressableScale
              key={star}
              style={[styles.starBtn, active && styles.starBtnActive]}
              onPress={() => onChange(star)}
              haptic="light"
            >
              <Star
                size={22}
                color={active ? colors.gold : colors.textMuted}
                fill={active ? colors.gold : 'transparent'}
                strokeWidth={2}
              />
              <Text style={[styles.starNum, active && styles.starNumActive]}>{star}</Text>
            </PressableScale>
          );
        })}
      </View>
    </View>
  );
}

const styles = StyleSheet.create({
  wrap: { marginBottom: spacing.lg },
  label: { ...typography.caption, fontWeight: '800', color: colors.slate600, marginBottom: spacing.sm + 2 },
  row: { flexDirection: 'row', flexWrap: 'wrap', gap: spacing.sm },
  starBtn: {
    flexDirection: 'row',
    alignItems: 'center',
    gap: spacing.xs,
    paddingHorizontal: spacing.sm + 2,
    paddingVertical: spacing.sm,
    borderRadius: radius.sm,
    borderWidth: 1,
    borderColor: colors.surface,
    backgroundColor: colors.white,
  },
  starBtnActive: { borderColor: colors.goldLight, backgroundColor: colors.goldLight },
  starNum: { ...typography.caption, fontWeight: '700', color: colors.textSecondary },
  starNumActive: { color: '#92400E' },
});
