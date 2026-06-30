import React from 'react';
import { View, Text, StyleSheet, TouchableOpacity } from 'react-native';
import { Ionicons } from '@expo/vector-icons';
import { colors } from '../theme/colors';

export default function StarRatingPicker({ value, onChange, label }) {
  return (
    <View style={styles.wrap}>
      {label ? <Text style={styles.label}>{label}</Text> : null}
      <View style={styles.row}>
        {Array.from({ length: 5 }).map((_, i) => {
          const star = i + 1;
          const active = star <= value;
          return (
            <TouchableOpacity
              key={star}
              style={[styles.starBtn, active && styles.starBtnActive]}
              onPress={() => onChange(star)}
              activeOpacity={0.85}
            >
              <Ionicons name={active ? 'star' : 'star-outline'} size={22} color={active ? colors.gold : colors.slate400} />
              <Text style={[styles.starNum, active && styles.starNumActive]}>{star}</Text>
            </TouchableOpacity>
          );
        })}
      </View>
    </View>
  );
}

const styles = StyleSheet.create({
  wrap: { marginBottom: 16 },
  label: { fontSize: 12, fontWeight: '800', color: colors.slate600, marginBottom: 10 },
  row: { flexDirection: 'row', flexWrap: 'wrap', gap: 8 },
  starBtn: {
    flexDirection: 'row',
    alignItems: 'center',
    gap: 4,
    paddingHorizontal: 10,
    paddingVertical: 8,
    borderRadius: 12,
    borderWidth: 1,
    borderColor: colors.slate100,
    backgroundColor: colors.white,
  },
  starBtnActive: { borderColor: colors.goldLight, backgroundColor: '#FFFBEB' },
  starNum: { fontSize: 13, fontWeight: '700', color: colors.slate500 },
  starNumActive: { color: '#92400E' },
});
