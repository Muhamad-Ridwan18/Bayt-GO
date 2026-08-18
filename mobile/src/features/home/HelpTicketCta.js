import React from 'react';
import { StyleSheet, Text } from 'react-native';
import { LifeBuoy } from 'lucide-react-native';
import { Button, Card } from '../../ui';
import { colors, layout, radius, spacing, typography } from '../../theme/tokens';

export default function HelpTicketCta({ onPress }) {
  return (
    <Card style={styles.wrap} padding={spacing.xl} elevated={false}>
      <LifeBuoy size={22} color={colors.baytgo} strokeWidth={2} />
      <Text style={styles.title}>Butuh bantuan cepat?</Text>
      <Text style={styles.sub}>
        Buat tiket dukungan jika ada kendala pada platform atau pembayaran.
      </Text>
      <Button label="Buat atau lihat tiket" onPress={onPress} style={styles.btn} />
    </Card>
  );
}

const styles = StyleSheet.create({
  wrap: {
    marginHorizontal: layout.screenPadding,
    marginTop: spacing.xl,
    borderRadius: radius.md - 2,
  },
  title: {
    marginTop: spacing.md,
    ...typography.subtitle,
    fontSize: 17,
    color: colors.baytgo,
    fontFamily: 'PlusJakartaSans_800ExtraBold',
  },
  sub: {
    marginTop: spacing.sm,
    ...typography.caption,
    color: colors.textSecondary,
    lineHeight: 20,
  },
  btn: { marginTop: spacing.lg },
});
