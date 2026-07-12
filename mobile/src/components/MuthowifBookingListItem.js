import React, { memo } from 'react';
import { StyleSheet, Text, View } from 'react-native';
import { Calendar, ChevronRight, User, Users } from 'lucide-react-native';
import Card from '../ui/Card';
import PressableScale from '../ui/PressableScale';
import { colors, radius, spacing, typography } from '../theme/tokens';
import { formatIdr } from '../utils/format';
import {
  bookingStatusMeta,
  paymentStatusMeta,
  formatDateRange,
  serviceTypeLabel,
} from '../utils/bookingLabels';

function StatusBadge({ label, color }) {
  return (
    <View style={[styles.badge, { backgroundColor: `${color}18` }]}>
      <Text style={[styles.badgeText, { color }]}>{label}</Text>
    </View>
  );
}

function MuthowifBookingListItem({ item, onPress }) {
  const bookingMeta = bookingStatusMeta(item.status);
  const paymentMeta = paymentStatusMeta(item.payment_status);
  const isPending = item.status === 'pending';

  return (
    <PressableScale onPress={onPress} haptic="light" style={styles.press}>
      <Card
        style={[
          styles.card,
          isPending && {
            borderColor: `${bookingMeta.color}40`,
            backgroundColor: `${bookingMeta.color}08`,
          },
        ]}
        padding={spacing.lg}
        elevated
      >
        <View style={styles.row}>
          <View style={styles.avatarWrap}>
            <View style={[styles.avatar, { backgroundColor: `${bookingMeta.color}18` }]}>
              <User size={22} color={bookingMeta.color} strokeWidth={2} />
            </View>
            {isPending ? <View style={[styles.pendingDot, { backgroundColor: bookingMeta.color }]} /> : null}
          </View>

          <View style={styles.body}>
            <View style={styles.topRow}>
              <Text style={styles.code}>{item.booking_code || `#${item.id}`}</Text>
              {isPending ? (
                <View style={[styles.newChip, { backgroundColor: `${bookingMeta.color}18` }]}>
                  <Text style={[styles.newChipText, { color: bookingMeta.color }]}>Baru</Text>
                </View>
              ) : null}
            </View>

            <Text style={styles.name} numberOfLines={1}>{item.customer_name}</Text>

            <View style={styles.metaRow}>
              <Calendar size={14} color={colors.textMuted} strokeWidth={2} />
              <Text style={styles.metaText}>
                {formatDateRange(item.starts_on, item.ends_on)}
              </Text>
            </View>

            <View style={styles.metaRow}>
              <Users size={14} color={colors.textMuted} strokeWidth={2} />
              <Text style={styles.metaText}>
                {item.pilgrim_count || 1} jamaah · {serviceTypeLabel(item.service_type) || 'Layanan'}
              </Text>
            </View>

            <View style={styles.badgeRow}>
              <StatusBadge label={bookingMeta.label} color={bookingMeta.color} />
              <StatusBadge label={paymentMeta.label} color={paymentMeta.color} />
            </View>

            <Text style={styles.amount}>
              {item.pricing?.net_after_referral != null
                ? formatIdr(item.pricing.net_after_referral)
                : item.total_price}
            </Text>
            {item.pricing?.net_after_referral != null ? (
              <Text style={styles.amountHint}>Estimasi diterima</Text>
            ) : null}
          </View>

          <ChevronRight size={20} color={colors.textMuted} strokeWidth={2} />
        </View>
      </Card>
    </PressableScale>
  );
}

export default memo(MuthowifBookingListItem);

const styles = StyleSheet.create({
  press: { marginBottom: spacing.lg },
  card: { borderRadius: radius.md },
  row: { flexDirection: 'row', alignItems: 'flex-start', gap: spacing.md },
  avatarWrap: { position: 'relative' },
  avatar: {
    width: 56,
    height: 56,
    borderRadius: radius.md,
    alignItems: 'center',
    justifyContent: 'center',
  },
  pendingDot: {
    position: 'absolute',
    top: -2,
    right: -2,
    width: 12,
    height: 12,
    borderRadius: 6,
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
  newChip: {
    paddingHorizontal: spacing.sm,
    paddingVertical: spacing.xs,
    borderRadius: radius.full,
  },
  newChipText: {
    ...typography.label,
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
  metaText: {
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
  amountHint: {
    ...typography.label,
    color: colors.textSecondary,
    marginTop: spacing.xs,
  },
});
