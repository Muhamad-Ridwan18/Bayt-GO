import React, { memo } from 'react';
import { StyleSheet, Text, View } from 'react-native';
import { Calendar, ChevronRight, Wallet } from 'lucide-react-native';
import { LinearGradient } from 'expo-linear-gradient';
import AppImage from '../ui/AppImage';
import Card from '../ui/Card';
import PressableScale from '../ui/PressableScale';
import { colors, gradients, radius, spacing, typography } from '../theme/tokens';
import { resolveMediaUrl } from '../utils/mediaUrl';
import { formatIdr } from '../utils/format';
import {
  bookingStatusMeta,
  paymentStatusMeta,
  formatDateRange,
  canPayBooking,
  isAwaitingMuthowifConfirmation,
} from '../utils/bookingLabels';
import { customerPayableAmount } from '../components/BookingPricingBreakdown';

function StatusBadge({ label, color }) {
  return (
    <View style={[styles.badge, { backgroundColor: `${color}18` }]}>
      <Text style={[styles.badgeText, { color }]}>{label}</Text>
    </View>
  );
}

function BookingListItem({ item, onPress, onPay }) {
  const bookingMeta = bookingStatusMeta(item.status);
  const paymentMeta = paymentStatusMeta(item.payment_status);
  const showPay = canPayBooking(item);
  const awaiting = isAwaitingMuthowifConfirmation(item);

  return (
    <PressableScale onPress={onPress} haptic="light" style={styles.press}>
      <Card
        style={[styles.card, showPay && styles.cardUnpaid]}
        padding={spacing.lg}
        elevated
      >
        <View style={styles.row}>
          <View style={styles.avatarWrap}>
            <AppImage uri={resolveMediaUrl(item.muthowif_avatar)} name={item.muthowif_name} size={56} rounded={radius.md} />
            {showPay ? <View style={styles.payDot} /> : null}
          </View>

          <View style={styles.body}>
            <View style={styles.topRow}>
              <Text style={styles.code}>{item.booking_code}</Text>
              {showPay ? (
                <View style={styles.payChip}>
                  <Text style={styles.payChipText}>Belum bayar</Text>
                </View>
              ) : awaiting ? (
                <View style={styles.waitChip}>
                  <Text style={styles.waitChipText}>Menunggu</Text>
                </View>
              ) : null}
            </View>

            <Text style={styles.name} numberOfLines={1}>{item.muthowif_name}</Text>

            <View style={styles.metaRow}>
              <Calendar size={14} color={colors.textMuted} strokeWidth={2} />
              <Text style={styles.dates}>{formatDateRange(item.starts_on, item.ends_on)}</Text>
            </View>

            <View style={styles.badgeRow}>
              <StatusBadge label={bookingMeta.label} color={bookingMeta.color} />
              <StatusBadge label={paymentMeta.label} color={paymentMeta.color} />
            </View>

            <Text style={styles.amount}>
              {formatIdr(customerPayableAmount(item.pricing, item.total_amount))}
            </Text>

            {showPay ? (
              <PressableScale
                onPress={() => onPay?.(item)}
                haptic="medium"
                style={styles.payBtn}
              >
                <LinearGradient colors={gradients.gold} style={styles.payGradient}>
                  <Wallet size={16} color={colors.white} strokeWidth={2} />
                  <Text style={styles.payBtnText}>Bayar sekarang</Text>
                </LinearGradient>
              </PressableScale>
            ) : null}
          </View>

          <ChevronRight size={20} color={colors.textMuted} strokeWidth={2} />
        </View>
      </Card>
    </PressableScale>
  );
}

export default memo(BookingListItem);

const styles = StyleSheet.create({
  press: { marginBottom: spacing.lg },
  card: { borderRadius: radius.md },
  cardUnpaid: {
    borderColor: '#FDE68A',
    backgroundColor: colors.warningLight,
  },
  row: { flexDirection: 'row', alignItems: 'flex-start', gap: spacing.md },
  avatarWrap: { position: 'relative' },
  payDot: {
    position: 'absolute',
    top: -2,
    right: -2,
    width: 12,
    height: 12,
    borderRadius: 6,
    backgroundColor: colors.warning,
    borderWidth: 2,
    borderColor: colors.white,
  },
  body: { flex: 1 },
  topRow: {
    flexDirection: 'row',
    alignItems: 'center',
    justifyContent: 'space-between',
    gap: spacing.sm,
  },
  code: {
    ...typography.small,
    color: colors.baytgo,
    fontFamily: 'PlusJakartaSans_800ExtraBold',
  },
  payChip: {
    backgroundColor: '#FEF3C7',
    paddingHorizontal: spacing.sm,
    paddingVertical: spacing.xs,
    borderRadius: radius.full,
  },
  payChipText: {
    ...typography.label,
    color: '#B45309',
    fontSize: 10,
  },
  waitChip: {
    backgroundColor: '#EDE9FE',
    paddingHorizontal: spacing.sm,
    paddingVertical: spacing.xs,
    borderRadius: radius.full,
  },
  waitChipText: {
    ...typography.label,
    color: '#7C3AED',
    fontSize: 10,
  },
  name: {
    ...typography.body,
    fontFamily: 'PlusJakartaSans_700Bold',
    color: colors.textPrimary,
    marginTop: spacing.xs,
  },
  metaRow: {
    flexDirection: 'row',
    alignItems: 'center',
    gap: spacing.sm,
    marginTop: spacing.sm,
  },
  dates: {
    ...typography.caption,
    color: colors.textSecondary,
    flex: 1,
  },
  badgeRow: {
    flexDirection: 'row',
    flexWrap: 'wrap',
    gap: spacing.sm,
    marginTop: spacing.md,
  },
  badge: {
    borderRadius: radius.full,
    paddingHorizontal: spacing.md,
    paddingVertical: spacing.xs,
  },
  badgeText: {
    ...typography.label,
    fontSize: 10,
  },
  amount: {
    ...typography.subtitle,
    fontSize: 18,
    color: colors.baytgo,
    marginTop: spacing.md,
  },
  payBtn: {
    marginTop: spacing.lg,
    borderRadius: radius.sm,
    overflow: 'hidden',
  },
  payGradient: {
    flexDirection: 'row',
    alignItems: 'center',
    justifyContent: 'center',
    gap: spacing.sm,
    paddingVertical: spacing.md,
    minHeight: 48,
  },
  payBtnText: {
    ...typography.caption,
    fontFamily: 'PlusJakartaSans_700Bold',
    color: colors.white,
  },
});
