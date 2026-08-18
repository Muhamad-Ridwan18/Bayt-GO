import React, { useEffect, useRef } from 'react';
import { StyleSheet, Text, View } from 'react-native';
import { AlarmClock } from 'lucide-react-native';
import { colors, radius, spacing, typography } from '../../theme/tokens';
import { formatDueDatetime, useCountdown } from '../../hooks/useCountdown';

export default function PaymentDeadlineBanner({ dueAt, onExpire }) {
  const { expired, label } = useCountdown(dueAt);
  const fired = useRef(false);

  useEffect(() => {
    fired.current = false;
  }, [dueAt]);

  useEffect(() => {
    if (!expired || !dueAt || fired.current) return;
    fired.current = true;
    onExpire?.();
  }, [expired, dueAt, onExpire]);

  if (!dueAt || !label) return null;

  const datetime = formatDueDatetime(dueAt);

  return (
    <View style={[styles.banner, expired && styles.expired]}>
      <AlarmClock size={16} color={expired ? colors.error : colors.warning} strokeWidth={2} />
      <View style={styles.body}>
        <Text style={[styles.text, expired && styles.expiredText]}>
          {datetime
            ? `Selesaikan pembayaran sebelum ${datetime}. Lewat batas, pesanan dibatalkan otomatis.`
            : 'Selesaikan pembayaran sebelum batas waktu. Lewat batas, pesanan dibatalkan otomatis.'}
        </Text>
        <Text style={[styles.remaining, expired && styles.expiredRemaining]}>
          {expired ? 'Waktu habis' : `Sisa waktu bayar: ${label}`}
        </Text>
      </View>
    </View>
  );
}

const styles = StyleSheet.create({
  banner: {
    flexDirection: 'row',
    alignItems: 'flex-start',
    gap: spacing.sm,
    backgroundColor: colors.warningLight,
    borderRadius: radius.sm,
    padding: spacing.md,
    borderWidth: 1,
    borderColor: '#FDE68A',
  },
  expired: {
    backgroundColor: '#FEF2F2',
    borderColor: '#FECACA',
  },
  body: { flex: 1, gap: spacing.xs },
  text: {
    ...typography.small,
    fontFamily: 'PlusJakartaSans_600SemiBold',
    color: '#92400E',
    lineHeight: 18,
  },
  expiredText: { color: '#991B1B' },
  remaining: {
    ...typography.small,
    fontFamily: 'PlusJakartaSans_800ExtraBold',
    color: '#B45309',
    fontVariant: ['tabular-nums'],
  },
  expiredRemaining: { color: colors.error },
});
