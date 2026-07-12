import React, { memo } from 'react';
import { StyleSheet, Text, View } from 'react-native';
import Card from '../ui/Card';
import { colors, radius, spacing, typography } from '../theme/tokens';
import { formatIdr } from '../utils/format';

const LEDGER_LABELS = {
  booking_credit: 'Kredit booking',
  referral_reward: 'Reward referral',
  withdraw_debit: 'Penarikan',
  withdraw_refund: 'Refund penarikan',
  refund_completed: 'Refund selesai',
};

function WalletLedgerRow({ entry }) {
  const signed = Number(entry.signed_amount) || 0;
  const positive = signed >= 0;
  const label = LEDGER_LABELS[entry.kind] || entry.kind;

  return (
    <Card style={styles.card} padding={spacing.lg} elevated={false} variant="flat">
      <View style={styles.row}>
        <View style={styles.meta}>
          <Text style={styles.kind}>{label}</Text>
          <Text style={styles.time}>{entry.at}</Text>
          {entry.booking_code ? (
            <Text style={styles.code}>{entry.booking_code}</Text>
          ) : null}
        </View>
        <Text style={[styles.amount, positive ? styles.amountPlus : styles.amountMinus]}>
          {positive ? '+' : '−'} {formatIdr(Math.abs(signed))}
        </Text>
      </View>
    </Card>
  );
}

export default memo(WalletLedgerRow);

const styles = StyleSheet.create({
  card: {
    borderRadius: radius.sm,
    marginBottom: spacing.sm,
  },
  row: {
    flexDirection: 'row',
    justifyContent: 'space-between',
    alignItems: 'flex-start',
    gap: spacing.md,
  },
  meta: { flex: 1 },
  kind: {
    ...typography.caption,
    fontFamily: 'PlusJakartaSans_700Bold',
    color: colors.textPrimary,
  },
  time: {
    ...typography.label,
    color: colors.textSecondary,
    marginTop: spacing.xs,
  },
  code: {
    ...typography.label,
    color: colors.baytgo,
    marginTop: spacing.xs,
  },
  amount: {
    ...typography.caption,
    fontFamily: 'PlusJakartaSans_800ExtraBold',
  },
  amountPlus: { color: colors.success },
  amountMinus: { color: colors.error },
});
