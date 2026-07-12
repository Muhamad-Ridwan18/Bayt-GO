import React from 'react';
import { StyleSheet, Text, View } from 'react-native';
import { MessageCircle } from 'lucide-react-native';
import { AppImage, Button, Card, PressableScale } from '../../ui';
import {
  bookingStatusMeta,
  formatDateRange,
  needsPayment,
  paymentStatusMeta,
} from '../../utils/bookingLabels';
import { resolveMediaUrl } from '../../utils/mediaUrl';
import { colors, radius, spacing, typography } from '../../theme/tokens';

function daysUntil(iso) {
  if (!iso) return null;
  const today = new Date();
  today.setHours(0, 0, 0, 0);
  const target = new Date(`${iso}T00:00:00`);
  const diff = Math.ceil((target - today) / 86400000);
  if (diff < 0) return null;
  if (diff === 0) return 'Hari ini';
  if (diff === 1) return 'Besok';
  return `${diff} hari lagi`;
}

export default function UpcomingTripCard({ booking, onPress, onPay, onChat }) {
  if (!booking) return null;

  const statusMeta = bookingStatusMeta(booking.status);
  const paymentMeta = paymentStatusMeta(booking.payment_status);
  const countdown = daysUntil(booking.starts_on);
  const showPay = needsPayment(booking);

  return (
    <PressableScale onPress={onPress} haptic="light">
      <Card style={styles.card} padding={spacing.lg - 2} elevated={false}>
        <View style={styles.header}>
          <View>
            <Text style={styles.kicker}>Perjalanan berikutnya</Text>
            {countdown ? <Text style={styles.countdown}>{countdown}</Text> : null}
          </View>
          <View style={[styles.status, { backgroundColor: `${statusMeta.color}20` }]}>
            <Text style={[styles.statusText, { color: statusMeta.color }]}>{statusMeta.label}</Text>
          </View>
        </View>
        <View style={styles.body}>
          <AppImage uri={resolveMediaUrl(booking.muthowif_avatar)} size={48} rounded={radius.sm} />
          <View style={styles.info}>
            <Text style={styles.name} numberOfLines={1}>{booking.muthowif_name}</Text>
            <Text style={styles.dates}>{formatDateRange(booking.starts_on, booking.ends_on)}</Text>
            <Text style={styles.payment}>{paymentMeta.label}</Text>
          </View>
          <View style={styles.actions}>
            {showPay ? (
              <Button
                label="Bayar"
                onPress={onPay}
                size="sm"
                fullWidth={false}
                variant="secondary"
              />
            ) : null}
            <PressableScale onPress={onChat} haptic="light" style={styles.chatBtn}>
              <MessageCircle size={18} color={colors.baytgo} strokeWidth={2} />
            </PressableScale>
          </View>
        </View>
      </Card>
    </PressableScale>
  );
}

const styles = StyleSheet.create({
  card: {
    borderLeftWidth: 4,
    borderLeftColor: colors.baytgo,
  },
  header: { flexDirection: 'row', justifyContent: 'space-between', alignItems: 'flex-start' },
  kicker: { ...typography.label, color: colors.textSecondary, textTransform: 'uppercase' },
  countdown: {
    marginTop: 2,
    ...typography.subtitle,
    fontSize: 16,
    color: colors.baytgo,
    fontFamily: 'PlusJakartaSans_800ExtraBold',
    fontWeight: '800',
  },
  status: { paddingHorizontal: spacing.sm, paddingVertical: spacing.xs, borderRadius: radius.full },
  statusText: { ...typography.label, fontSize: 10 },
  body: { flexDirection: 'row', alignItems: 'center', gap: spacing.md, marginTop: spacing.md },
  info: { flex: 1 },
  name: {
    ...typography.caption,
    fontFamily: 'PlusJakartaSans_800ExtraBold',
    fontWeight: '800',
    color: colors.textPrimary,
  },
  dates: { marginTop: 2, ...typography.small, color: colors.textSecondary },
  payment: { marginTop: 2, ...typography.small, color: colors.goldMuted },
  actions: { flexDirection: 'row', alignItems: 'center', gap: spacing.sm - 2 },
  chatBtn: {
    width: 34,
    height: 34,
    borderRadius: radius.sm - 2,
    backgroundColor: colors.background,
    alignItems: 'center',
    justifyContent: 'center',
    borderWidth: 1,
    borderColor: colors.border,
  },
});
