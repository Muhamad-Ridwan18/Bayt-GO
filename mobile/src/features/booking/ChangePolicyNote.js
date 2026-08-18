import React from 'react';
import { StyleSheet, Text, View } from 'react-native';
import { colors, spacing, typography } from '../../theme/tokens';
import { formatIdr } from '../../utils/format';

function Bullet({ children }) {
  return (
    <View style={styles.bulletRow}>
      <Text style={styles.dot}>•</Text>
      <Text style={styles.bulletText}>{children}</Text>
    </View>
  );
}

export default function ChangePolicyNote({ policy, mode = 'all' }) {
  if (!policy) return null;

  const refundDays = policy.refund_min_days ?? 60;
  const rescheduleDays = policy.reschedule_min_days ?? 30;
  const platformPct = policy.refund_platform_percent ?? 15;
  const muthowifPct = policy.refund_muthowif_percent ?? 1;
  const preview = policy.refund_preview;
  const showRefund = mode === 'all' || mode === 'refund';
  const showReschedule = mode === 'all' || mode === 'reschedule';

  return (
    <View>
      {showRefund ? (
        <View style={styles.block}>
          <Text style={styles.heading}>Refund</Text>
          <Bullet>Pengajuan paling lambat H-{refundDays} sebelum tanggal mulai layanan.</Bullet>
          <Bullet>
            Potongan dari harga dasar: {platformPct}% admin dan {muthowifPct}% muthowif (order batal).
          </Bullet>
          <Bullet>Wajib mengisi bank dan nomor rekening penerima; admin mentransfer nominal bersih ke rekening tersebut.</Bullet>
        </View>
      ) : null}

      {showReschedule ? (
        <View style={styles.block}>
          <Text style={styles.heading}>Reschedule</Text>
          <Bullet>Pengajuan paling lambat H-{rescheduleDays} sebelum mulai layanan Anda sekarang.</Bullet>
          <Bullet>Tanggal mulai baru minimal H-{rescheduleDays} dari hari Anda mengajukan.</Bullet>
          <Bullet>Lama menginap sama, perlu persetujuan muthowif.</Bullet>
        </View>
      ) : null}

      {showRefund && preview?.net_refund_customer != null ? (
        <View style={styles.estimate}>
          <Text style={styles.estimateTitle}>
            Perkiraan dana kembali ke rekening Anda: {formatIdr(preview.net_refund_customer)}
          </Text>
          <Text style={styles.estimateSub}>
            Dari harga dasar {formatIdr(preview.service_base_amount)}: potongan admin {formatIdr(preview.refund_fee_platform)} dan potongan muthowif {formatIdr(preview.refund_fee_muthowif)}. Total yang Anda bayar {formatIdr(preview.customer_paid_amount)} (sudah termasuk biaya platform).
          </Text>
        </View>
      ) : null}
    </View>
  );
}

const styles = StyleSheet.create({
  block: { marginBottom: spacing.md },
  heading: {
    ...typography.caption,
    fontFamily: 'PlusJakartaSans_800ExtraBold',
    color: colors.textPrimary,
    marginBottom: spacing.xs,
  },
  bulletRow: { flexDirection: 'row', gap: spacing.sm, marginTop: 4 },
  dot: { ...typography.small, color: colors.textSecondary, lineHeight: 18 },
  bulletText: { flex: 1, ...typography.small, color: colors.textSecondary, lineHeight: 18 },
  estimate: {
    marginTop: spacing.xs,
    paddingTop: spacing.md,
    borderTopWidth: 1,
    borderTopColor: colors.border,
  },
  estimateTitle: {
    ...typography.caption,
    fontFamily: 'PlusJakartaSans_800ExtraBold',
    color: colors.baytgo,
    lineHeight: 20,
  },
  estimateSub: {
    marginTop: spacing.xs,
    ...typography.small,
    color: colors.textSecondary,
    lineHeight: 18,
  },
});
