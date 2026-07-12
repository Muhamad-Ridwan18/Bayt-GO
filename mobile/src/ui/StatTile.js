import React from 'react';
import { StyleSheet, Text, View } from 'react-native';
import Card from './Card';
import { colors, radius, spacing, typography } from '../theme/tokens';

export default function StatTile({ label, value, color, icon: Icon }) {
  return (
    <Card style={styles.tile} padding={spacing.lg} elevated={false} variant="flat">
      <View style={[styles.iconWrap, { backgroundColor: `${color}18` }]}>
        {Icon ? <Icon size={18} color={color} strokeWidth={2} /> : null}
      </View>
      <Text style={[styles.value, { color }]}>{value}</Text>
      <Text style={styles.label}>{label}</Text>
    </Card>
  );
}

const styles = StyleSheet.create({
  tile: {
    flex: 1,
    alignItems: 'center',
    borderRadius: radius.md,
  },
  iconWrap: {
    width: 36,
    height: 36,
    borderRadius: radius.sm,
    alignItems: 'center',
    justifyContent: 'center',
    marginBottom: spacing.sm,
  },
  value: {
    ...typography.subtitle,
    fontSize: 22,
    lineHeight: 28,
  },
  label: {
    ...typography.small,
    color: colors.textSecondary,
    marginTop: spacing.xs,
    textAlign: 'center',
  },
});
