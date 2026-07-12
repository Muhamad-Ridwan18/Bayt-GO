import React from 'react';
import { StyleSheet, Text, View } from 'react-native';
import {
  FlaskConical, ShieldCheck, CreditCard, Building2, Circle, CircleDot,
} from 'lucide-react-native';
import PressableScale from '../../ui/PressableScale';
import Card from '../../ui/Card';
import { colors, radius, spacing, typography } from '../../theme/tokens';

export function StepIndicator({ step }) {
  return (
    <View style={styles.steps}>
      <View style={styles.stepItem}>
        <View style={[styles.stepDot, step >= 1 && styles.stepDotActive]}>
          <Text style={[styles.stepNum, step >= 1 && styles.stepNumActive]}>1</Text>
        </View>
        <Text style={[styles.stepLabel, step >= 1 && styles.stepLabelActive]}>Pilih rekening</Text>
      </View>
      <View style={[styles.stepLine, step >= 2 && styles.stepLineActive]} />
      <View style={styles.stepItem}>
        <View style={[styles.stepDot, step >= 2 && styles.stepDotActive]}>
          <Text style={[styles.stepNum, step >= 2 && styles.stepNumActive]}>2</Text>
        </View>
        <Text style={[styles.stepLabel, step >= 2 && styles.stepLabelActive]}>Transfer</Text>
      </View>
    </View>
  );
}

export function EnvironmentBanner({ environment }) {
  if (!environment?.label) return null;
  const isSandbox = environment.is_sandbox;
  const Icon = isSandbox ? FlaskConical : ShieldCheck;

  return (
    <Card
      style={[styles.envBanner, isSandbox ? styles.envSandbox : styles.envProduction]}
      padding={spacing.lg}
      elevated={false}
    >
      <Icon size={18} color={isSandbox ? '#B45309' : '#166534'} strokeWidth={2} />
      <View style={styles.envCopy}>
        <Text style={[styles.envTitle, isSandbox ? styles.envTitleSandbox : styles.envTitleProduction]}>
          {environment.label}
        </Text>
        {environment.hint ? (
          <Text style={[styles.envHint, isSandbox ? styles.envHintSandbox : styles.envHintProduction]}>
            {environment.hint}
          </Text>
        ) : null}
      </View>
    </Card>
  );
}

function guessMethodIcon(idOrName) {
  const key = String(idOrName || '').toLowerCase();
  if (key.includes('moota')) return Building2;
  return CreditCard;
}

export function MethodCard({ item, selected, environment, onPress }) {
  const displayLabel = item.bank_name || item.label;
  const isSandbox = environment?.is_sandbox;
  const Icon = guessMethodIcon(item.id || item.bank_name);
  const RadioIcon = selected ? CircleDot : Circle;

  return (
    <PressableScale onPress={onPress} haptic="light">
      <Card style={[styles.methodCard, selected && styles.methodCardActive]} padding={spacing.lg}>
        <View style={[styles.methodIcon, selected && styles.methodIconActive]}>
          <Icon size={20} color={selected ? colors.white : colors.baytgo} strokeWidth={2} />
        </View>
        <View style={styles.methodBody}>
          <View style={styles.methodTitleRow}>
            <Text style={styles.methodLabel}>{displayLabel}</Text>
            {isSandbox !== undefined ? (
              <View style={[styles.envChip, isSandbox ? styles.envChipSandbox : styles.envChipProduction]}>
                <Text style={[styles.envChipText, isSandbox ? styles.envChipTextSandbox : styles.envChipTextProduction]}>
                  {isSandbox ? 'Sandbox' : 'Live'}
                </Text>
              </View>
            ) : null}
          </View>
          {item.account_holder ? <Text style={styles.methodDetail}>a.n. {item.account_holder}</Text> : null}
          {item.account_number ? (
            <Text style={styles.methodAccount}>No. rekening {item.account_number}</Text>
          ) : (
            <Text style={styles.methodHint}>Transfer bank via Moota</Text>
          )}
          {item.bank_account_ref ? (
            <Text style={styles.methodRef}>Ref. Moota: {item.bank_account_ref}</Text>
          ) : null}
        </View>
        <RadioIcon size={22} color={colors.baytgo} strokeWidth={2} />
      </Card>
    </PressableScale>
  );
}

export function PaymentInfoRow({ icon: Icon, label, value }) {
  return (
    <View style={styles.infoRow}>
      <Icon size={16} color={colors.textMuted} strokeWidth={2} />
      <Text style={styles.infoLabel}>{label}</Text>
      <Text style={styles.infoValue}>{value}</Text>
    </View>
  );
}

const styles = StyleSheet.create({
  steps: {
    flexDirection: 'row', alignItems: 'center', justifyContent: 'center',
    marginBottom: spacing.lg, paddingHorizontal: spacing.sm,
  },
  stepItem: { alignItems: 'center', gap: spacing.sm },
  stepDot: {
    width: 32, height: 32, borderRadius: 16,
    backgroundColor: colors.card, borderWidth: 2, borderColor: colors.border,
    alignItems: 'center', justifyContent: 'center',
  },
  stepDotActive: { backgroundColor: colors.baytgo, borderColor: colors.baytgo },
  stepNum: { ...typography.small, color: colors.textMuted },
  stepNumActive: { color: colors.white },
  stepLabel: { ...typography.label, color: colors.textMuted },
  stepLabelActive: { color: colors.baytgo },
  stepLine: { width: 48, height: 2, backgroundColor: colors.border, marginHorizontal: spacing.sm, marginBottom: spacing.xl },
  stepLineActive: { backgroundColor: colors.baytgo },
  envBanner: { flexDirection: 'row', alignItems: 'flex-start', gap: spacing.md, marginBottom: spacing.lg },
  envSandbox: { backgroundColor: colors.warningLight, borderColor: '#FDE68A' },
  envProduction: { backgroundColor: colors.successLight, borderColor: '#A7F3D0' },
  envCopy: { flex: 1 },
  envTitle: { ...typography.caption, fontFamily: 'PlusJakartaSans_800ExtraBold' },
  envTitleSandbox: { color: '#B45309' },
  envTitleProduction: { color: '#166534' },
  envHint: { marginTop: spacing.xs, ...typography.small, lineHeight: 17 },
  envHintSandbox: { color: '#92400E' },
  envHintProduction: { color: '#166534' },
  methodCard: { flexDirection: 'row', alignItems: 'center', gap: spacing.md, marginBottom: spacing.md },
  methodCardActive: { borderColor: colors.baytgo, backgroundColor: colors.baytgoLight },
  methodIcon: {
    width: 44, height: 44, borderRadius: radius.sm,
    backgroundColor: colors.baytgoLight, alignItems: 'center', justifyContent: 'center',
  },
  methodIconActive: { backgroundColor: colors.baytgo },
  methodBody: { flex: 1 },
  methodTitleRow: { flexDirection: 'row', alignItems: 'center', gap: spacing.sm, flexWrap: 'wrap' },
  methodLabel: { ...typography.caption, fontFamily: 'PlusJakartaSans_700Bold', color: colors.textPrimary },
  methodDetail: { marginTop: spacing.xs, ...typography.small, color: colors.textSecondary },
  methodAccount: { marginTop: 2, ...typography.caption, fontFamily: 'PlusJakartaSans_700Bold', color: colors.baytgo },
  methodHint: { marginTop: spacing.xs, ...typography.small, color: colors.textSecondary },
  methodRef: { marginTop: spacing.xs, ...typography.label, color: colors.textMuted },
  envChip: { paddingHorizontal: spacing.sm, paddingVertical: 3, borderRadius: radius.full },
  envChipSandbox: { backgroundColor: '#FEF3C7' },
  envChipProduction: { backgroundColor: '#DCFCE7' },
  envChipText: { ...typography.label, fontSize: 10 },
  envChipTextSandbox: { color: '#B45309' },
  envChipTextProduction: { color: '#166534' },
  infoRow: { flexDirection: 'row', alignItems: 'center', gap: spacing.sm, paddingVertical: spacing.sm },
  infoLabel: { flex: 1, ...typography.caption, color: colors.textSecondary },
  infoValue: { ...typography.caption, fontFamily: 'PlusJakartaSans_700Bold', color: colors.textPrimary },
});
