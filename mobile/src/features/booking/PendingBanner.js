import React from 'react';
import { StyleSheet, Text, View } from 'react-native';
import { Clock } from 'lucide-react-native';
import { colors, radius, spacing, typography } from '../../theme/tokens';

export default function PendingBanner({ text, icon: Icon = Clock }) {
  return (
    <View style={styles.banner}>
      <Icon size={16} color={colors.warning} strokeWidth={2} />
      <Text style={styles.text}>{text}</Text>
    </View>
  );
}

const styles = StyleSheet.create({
  banner: {
    flexDirection: 'row',
    alignItems: 'center',
    gap: spacing.sm,
    backgroundColor: colors.warningLight,
    borderRadius: radius.sm,
    padding: spacing.md,
    borderWidth: 1,
    borderColor: '#FDE68A',
  },
  text: {
    flex: 1,
    ...typography.small,
    fontFamily: 'PlusJakartaSans_700Bold',
    color: '#92400E',
  },
});
