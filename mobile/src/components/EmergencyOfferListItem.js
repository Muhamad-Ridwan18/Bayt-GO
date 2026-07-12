import React, { memo } from 'react';
import { StyleSheet, Text, View } from 'react-native';
import { Calendar, User } from 'lucide-react-native';
import Button from '../ui/Button';
import Card from '../ui/Card';
import { colors, radius, spacing, typography } from '../theme/tokens';
import { formatDateRange } from '../utils/bookingLabels';

const STATUS_META = {
  offered: { label: 'Menunggu respons', bg: colors.warningLight, text: colors.warning },
  accepted: { label: 'Diterima', bg: colors.successLight, text: colors.success },
};

function EmergencyOfferListItem({ offer, onAccept, onDecline, busy }) {
  const pending = offer.status === 'offered';
  const statusMeta = STATUS_META[offer.status] || {
    label: offer.status,
    bg: colors.surface,
    text: colors.textSecondary,
  };

  return (
    <Card style={styles.card} padding={spacing.lg} elevated>
      <View style={styles.header}>
        <Text style={styles.bookingCode}>{offer.booking_code || 'Booking'}</Text>
        <View style={[styles.badge, { backgroundColor: statusMeta.bg }]}>
          <Text style={[styles.badgeText, { color: statusMeta.text }]}>
            {statusMeta.label}
          </Text>
        </View>
      </View>

      <View style={styles.metaRow}>
        <User size={14} color={colors.textMuted} strokeWidth={2} />
        <Text style={styles.customer}>{offer.customer_name || 'Jamaah'}</Text>
      </View>

      <View style={styles.metaRow}>
        <Calendar size={14} color={colors.textMuted} strokeWidth={2} />
        <Text style={styles.dates}>{formatDateRange(offer.starts_on, offer.ends_on)}</Text>
      </View>

      {offer.original_muthowif ? (
        <Text style={styles.original}>Menggantikan: {offer.original_muthowif}</Text>
      ) : null}

      {pending ? (
        <View style={styles.actions}>
          <View style={styles.actionBtn}>
            <Button
              label="Tolak"
              variant="danger"
              size="sm"
              onPress={() => onDecline(offer)}
              disabled={busy}
              fullWidth
            />
          </View>
          <View style={styles.actionBtn}>
            <Button
              label="Terima"
              size="sm"
              onPress={() => onAccept(offer)}
              disabled={busy}
              loading={busy}
              fullWidth
            />
          </View>
        </View>
      ) : null}
    </Card>
  );
}

export default memo(EmergencyOfferListItem);

const styles = StyleSheet.create({
  card: {
    borderRadius: radius.md,
    marginBottom: spacing.lg,
  },
  header: {
    flexDirection: 'row',
    justifyContent: 'space-between',
    alignItems: 'center',
    gap: spacing.sm,
  },
  bookingCode: {
    ...typography.caption,
    fontFamily: 'PlusJakartaSans_800ExtraBold',
    color: colors.baytgo,
    flex: 1,
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
  metaRow: {
    flexDirection: 'row',
    alignItems: 'center',
    gap: spacing.sm,
    marginTop: spacing.md,
  },
  customer: {
    ...typography.body,
    fontFamily: 'PlusJakartaSans_700Bold',
    color: colors.textPrimary,
    flex: 1,
  },
  dates: {
    ...typography.caption,
    color: colors.textSecondary,
    flex: 1,
  },
  original: {
    ...typography.small,
    color: colors.textSecondary,
    marginTop: spacing.sm,
    fontWeight: '500',
    fontFamily: 'PlusJakartaSans_500Medium',
  },
  actions: {
    flexDirection: 'row',
    gap: spacing.md,
    marginTop: spacing.lg,
  },
  actionBtn: { flex: 1 },
});
