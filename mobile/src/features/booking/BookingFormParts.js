import React from 'react';
import { StyleSheet, Switch, Text, View } from 'react-native';
import { Minus, Plus } from 'lucide-react-native';
import { Card, PressableScale } from '../../ui';
import { colors, radius, spacing, typography } from '../../theme/tokens';
import { formatIdr } from '../../utils/format';

export function ServiceOption({ label, active, price, onPress }) {
  return (
    <PressableScale onPress={onPress} haptic="light" style={styles.serviceOptionWrap}>
      <Card
        style={[styles.serviceOption, active && styles.serviceOptionActive]}
        padding={spacing.lg}
        elevated={false}
      >
        <Text style={[styles.serviceOptionText, active && styles.serviceOptionTextActive]}>{label}</Text>
        <Text style={styles.serviceOptionPrice}>{formatIdr(price)} / hari</Text>
      </Card>
    </PressableScale>
  );
}

export function StepBadges({ step }) {
  return (
    <View style={styles.stepRow}>
      <View style={[styles.stepBadge, step === 1 && styles.stepBadgeActive]}>
        <Text style={[styles.stepBadgeText, step === 1 && styles.stepBadgeTextActive]}>1 Layanan</Text>
      </View>
      <View style={[styles.stepBadge, step === 2 && styles.stepBadgeActive]}>
        <Text style={[styles.stepBadgeText, step === 2 && styles.stepBadgeTextActive]}>2 Dokumen</Text>
      </View>
    </View>
  );
}

export function PilgrimCounter({ value, minPax, maxPax, onChange }) {
  const count = parseInt(value, 10) || minPax;
  return (
    <>
      <View style={styles.counterRow}>
        <PressableScale
          onPress={() => onChange(String(Math.max(minPax, count - 1)))}
          haptic="light"
          style={styles.counterBtn}
        >
          <Minus size={20} color={colors.baytgo} strokeWidth={2.5} />
        </PressableScale>
        <Text style={styles.counterValue}>{value}</Text>
        <PressableScale
          onPress={() => onChange(String(Math.min(maxPax, count + 1)))}
          haptic="light"
          style={styles.counterBtn}
        >
          <Plus size={20} color={colors.baytgo} strokeWidth={2.5} />
        </PressableScale>
      </View>
      <Text style={styles.hint}>Min {minPax}, max {maxPax} jamaah</Text>
    </>
  );
}

export function ToggleRow({ label, value, onValueChange }) {
  return (
    <Card style={styles.switchRow} padding={spacing.lg} elevated={false}>
      <Text style={styles.switchLabel}>{label}</Text>
      <Switch value={value} onValueChange={onValueChange} trackColor={{ true: colors.baytgo }} />
    </Card>
  );
}

export function AddOnToggle({ addon, value, onValueChange }) {
  return (
    <ToggleRow
      label={`${addon.name} (+${formatIdr(addon.price)})`}
      value={value}
      onValueChange={onValueChange}
    />
  );
}

export function BookingEstimateCard({ estimate }) {
  if (!estimate) return null;

  return (
    <Card style={styles.estimateCard} padding={spacing.lg} elevated={false}>
      <Text style={styles.estimateTitle}>Estimasi biaya</Text>
      {estimate.lines.map((line) => (
        <View key={line.key} style={styles.estimateRow}>
          <Text style={styles.estimateLabel}>{line.label}</Text>
          <Text style={styles.estimateValue}>{formatIdr(line.amount)}</Text>
        </View>
      ))}
      <View style={styles.estimateDivider} />
      <View style={styles.estimateRow}>
        <Text style={styles.estimateLabel}>Subtotal layanan</Text>
        <Text style={styles.estimateValue}>{formatIdr(estimate.base)}</Text>
      </View>
      {estimate.platform_fee > 0 ? (
        <View style={styles.estimateRow}>
          <Text style={styles.estimateLabel}>
            {`Biaya platform (${estimate.platform_fee_percent}%)`}
          </Text>
          <Text style={styles.estimateValue}>{formatIdr(estimate.platform_fee)}</Text>
        </View>
      ) : null}
      <View style={[styles.estimateRow, styles.estimateTotalRow]}>
        <Text style={styles.estimateTotalLabel}>Total estimasi</Text>
        <Text style={styles.estimateTotalValue}>{formatIdr(estimate.total_payable)}</Text>
      </View>
      <Text style={styles.estimateHint}>Perkiraan sebelum konfirmasi muthowif</Text>
    </Card>
  );
}

export function AddOnRow({ addon, selected, onPress }) {
  return (
    <PressableScale onPress={onPress} haptic="light">
      <Card style={[styles.addOnRow, selected && styles.addOnRowActive]} padding={spacing.lg} elevated={false}>
        <Text style={styles.addOnName}>{addon.name}</Text>
        <Text style={styles.addOnPrice}>{formatIdr(addon.price)}</Text>
      </Card>
    </PressableScale>
  );
}

export function SectionTitle({ children }) {
  return <Text style={styles.sectionTitle}>{children}</Text>;
}

export function FormError({ message }) {
  if (!message) return null;
  return (
    <Card style={styles.errorCard} padding={spacing.md} elevated={false}>
      <Text style={styles.errorText}>{message}</Text>
    </Card>
  );
}

const styles = StyleSheet.create({
  serviceOptionWrap: { flex: 1 },
  serviceOption: { borderColor: colors.border },
  serviceOptionActive: { borderColor: colors.baytgo, backgroundColor: colors.successLight },
  serviceOptionText: { ...typography.caption, fontFamily: 'PlusJakartaSans_800ExtraBold', fontWeight: '800', color: colors.slate700 },
  serviceOptionTextActive: { color: colors.baytgo },
  serviceOptionPrice: { marginTop: spacing.xs, ...typography.small, color: colors.textSecondary },
  stepRow: { flexDirection: 'row', gap: spacing.sm, marginTop: spacing.lg, marginBottom: spacing.md },
  stepBadge: {
    paddingHorizontal: spacing.md,
    paddingVertical: 6,
    borderRadius: radius.full,
    backgroundColor: colors.card,
    borderWidth: 1,
    borderColor: colors.border,
  },
  stepBadgeActive: { backgroundColor: colors.successLight, borderColor: colors.baytgo },
  stepBadgeText: { ...typography.small, color: colors.textMuted },
  stepBadgeTextActive: { color: colors.baytgo, fontFamily: 'PlusJakartaSans_700Bold' },
  counterRow: { flexDirection: 'row', alignItems: 'center', gap: spacing.lg },
  counterBtn: {
    width: 44,
    height: 44,
    borderRadius: radius.sm,
    backgroundColor: colors.card,
    alignItems: 'center',
    justifyContent: 'center',
    borderWidth: 1,
    borderColor: colors.border,
  },
  counterValue: { ...typography.title, fontSize: 24, color: colors.textPrimary, minWidth: 40, textAlign: 'center' },
  hint: { marginTop: spacing.sm, ...typography.small, color: colors.textSecondary, fontWeight: '500' },
  switchRow: { flexDirection: 'row', alignItems: 'center', justifyContent: 'space-between', marginTop: spacing.md },
  switchLabel: { flex: 1, ...typography.caption, fontFamily: 'PlusJakartaSans_700Bold', color: colors.slate700, paddingRight: spacing.md },
  addOnRow: { flexDirection: 'row', justifyContent: 'space-between', marginBottom: spacing.sm, borderColor: colors.border },
  addOnRowActive: { borderColor: colors.baytgo, backgroundColor: colors.successLight },
  addOnName: { ...typography.caption, fontFamily: 'PlusJakartaSans_700Bold', color: colors.slate800 },
  addOnPrice: { ...typography.caption, fontFamily: 'PlusJakartaSans_800ExtraBold', color: colors.baytgo },
  estimateCard: { marginTop: spacing.lg, backgroundColor: colors.card, borderColor: colors.border },
  estimateTitle: { ...typography.caption, fontFamily: 'PlusJakartaSans_800ExtraBold', color: colors.baytgo, marginBottom: spacing.md },
  estimateRow: { flexDirection: 'row', justifyContent: 'space-between', alignItems: 'flex-start', gap: spacing.md, paddingVertical: spacing.xs },
  estimateLabel: { flex: 1, ...typography.small, color: colors.textSecondary, fontWeight: '600' },
  estimateValue: { ...typography.small, color: colors.textPrimary, fontWeight: '700' },
  estimateDivider: { height: 1, backgroundColor: colors.border, marginVertical: spacing.sm },
  estimateTotalRow: { paddingTop: spacing.xs },
  estimateTotalLabel: { flex: 1, ...typography.caption, fontFamily: 'PlusJakartaSans_800ExtraBold', color: colors.slate800 },
  estimateTotalValue: { ...typography.subtitle, color: colors.baytgo, fontWeight: '800' },
  estimateHint: { marginTop: spacing.sm, ...typography.small, color: colors.textMuted, fontWeight: '500' },
  sectionTitle: { ...typography.caption, fontFamily: 'PlusJakartaSans_800ExtraBold', color: colors.baytgo, marginTop: spacing.lg, marginBottom: spacing.md },
  errorCard: { backgroundColor: colors.errorLight, borderColor: '#FECACA', marginBottom: spacing.md },
  errorText: { ...typography.caption, color: colors.error, fontWeight: '600' },
});
