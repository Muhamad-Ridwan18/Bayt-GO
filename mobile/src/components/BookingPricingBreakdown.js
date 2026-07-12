import React from 'react';
import { View, Text, StyleSheet } from 'react-native';
import { colors, spacing, typography } from '../theme/tokens';
import { formatIdr } from '../utils/format';

function Row({ label, value, bold, negative, muted }) {
  return (
    <View style={styles.row}>
      <Text style={[styles.label, muted && styles.labelMuted]}>{label}</Text>
      <Text style={[
        styles.value,
        bold && styles.valueBold,
        negative && styles.valueNegative,
        muted && styles.valueMuted,
      ]}>
        {value}
      </Text>
    </View>
  );
}

export function customerPayableAmount(pricing, fallback = 0) {
  return pricing?.total_payable ?? fallback;
}

export function CustomerPricingBreakdown({ pricing, showLines = true }) {
  if (!pricing) return null;

  const feePercent = pricing.platform_fee_percent ?? 7.5;

  return (
    <View>
      {showLines && (pricing.lines || []).map((line) => (
        <Row key={`${line.key}-${line.label}`} label={line.label} value={formatIdr(line.amount)} />
      ))}
      <Row label="Subtotal layanan" value={formatIdr(pricing.base)} />
      {pricing.platform_fee > 0 ? (
        <Row
          label={`Biaya platform (${feePercent}%)`}
          value={formatIdr(pricing.platform_fee)}
        />
      ) : pricing.is_company_customer ? (
        <Row label="Biaya platform" value="Dibebaskan" muted />
      ) : null}
      <Row label="Total dibayar" value={formatIdr(pricing.total_payable)} bold />
    </View>
  );
}

export function MuthowifPricingBreakdown({ pricing, showLines = true }) {
  if (!pricing) return null;

  const feePercent = pricing.platform_fee_percent ?? 7.5;

  return (
    <View>
      {showLines && (pricing.lines || []).map((line) => (
        <Row key={`${line.key}-${line.label}`} label={line.label} value={formatIdr(line.amount)} />
      ))}
      <Row label="Subtotal layanan" value={formatIdr(pricing.base)} />
      <Row
        label={`Biaya platform (${feePercent}%)`}
        value={`- ${formatIdr(pricing.platform_fee)}`}
        negative
      />
      <Row label="Pendapatan bersih" value={formatIdr(pricing.net_earning)} />
      {pricing.referral_deduction > 0 ? (
        <Row
          label="Potongan referral"
          value={`- ${formatIdr(pricing.referral_deduction)}`}
          negative
        />
      ) : null}
      <Row label="Estimasi diterima" value={formatIdr(pricing.net_after_referral)} bold />
    </View>
  );
}

const styles = StyleSheet.create({
  row: {
    flexDirection: 'row',
    justifyContent: 'space-between',
    alignItems: 'flex-start',
    gap: spacing.md,
    paddingVertical: spacing.sm,
    borderBottomWidth: 1,
    borderBottomColor: colors.surface,
  },
  label: {
    flex: 1,
    ...typography.caption,
    fontWeight: '600',
    color: colors.slate600,
  },
  labelMuted: { color: colors.textMuted },
  value: {
    ...typography.caption,
    fontWeight: '700',
    color: colors.textPrimary,
    textAlign: 'right',
  },
  valueBold: {
    ...typography.body,
    fontSize: 15,
    fontWeight: '900',
    color: colors.baytgo,
  },
  valueNegative: { color: colors.error },
  valueMuted: { color: colors.textMuted },
});
