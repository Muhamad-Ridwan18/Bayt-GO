import React from 'react';
import { View, Text, StyleSheet, TouchableOpacity } from 'react-native';
import { Ionicons } from '@expo/vector-icons';
import { colors } from '../theme/colors';
import { bookingStatusMeta, paymentStatusMeta } from '../utils/bookingLabels';
import { formatIdr } from '../utils/format';

function StatusBadge({ label, color }) {
  return (
    <View style={[styles.badge, { backgroundColor: color + '18' }]}>
      <Text style={[styles.badgeText, { color }]}>{label}</Text>
    </View>
  );
}

export default function MuthowifBookingListItem({ item, onPress }) {
  const bookingMeta = bookingStatusMeta(item.status);
  const paymentMeta = paymentStatusMeta(item.payment_status);
  const isPending = item.status === 'pending';

  return (
    <TouchableOpacity
      style={[styles.card, isPending && styles.cardPending]}
      onPress={onPress}
      activeOpacity={0.9}
    >
      <View style={[styles.iconWrap, isPending && styles.iconWrapPending]}>
        <Ionicons name="person" size={22} color={isPending ? '#7C3AED' : colors.baytgo} />
        {isPending ? <View style={styles.pendingDot} /> : null}
      </View>

      <View style={styles.body}>
        <View style={styles.topRow}>
          <Text style={styles.code}>{item.booking_code || `#${item.id}`}</Text>
          {isPending ? (
            <View style={styles.newChip}>
              <Text style={styles.newChipText}>Baru</Text>
            </View>
          ) : null}
        </View>

        <Text style={styles.name} numberOfLines={1}>{item.customer_name}</Text>

        <View style={styles.metaRow}>
          <Ionicons name="calendar-outline" size={13} color={colors.slate400} />
          <Text style={styles.dates}>{item.starts_on} — {item.ends_on}</Text>
        </View>

        <View style={styles.metaRow}>
          <Ionicons name="people-outline" size={13} color={colors.slate400} />
          <Text style={styles.metaText}>
            {item.pilgrim_count || 1} jamaah · {item.service_type || 'Layanan'}
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

      <Ionicons name="chevron-forward" size={18} color={colors.slate400} />
    </TouchableOpacity>
  );
}

const styles = StyleSheet.create({
  card: {
    flexDirection: 'row',
    alignItems: 'center',
    backgroundColor: colors.white,
    borderRadius: 18,
    padding: 14,
    marginBottom: 12,
    borderWidth: 1,
    borderColor: 'rgba(26,61,52,0.08)',
    gap: 12,
    shadowColor: '#0F2E28',
    shadowOffset: { width: 0, height: 4 },
    shadowOpacity: 0.04,
    shadowRadius: 10,
    elevation: 2,
  },
  cardPending: {
    borderColor: '#DDD6FE',
    backgroundColor: '#FDFCFF',
  },
  iconWrap: {
    width: 50,
    height: 50,
    borderRadius: 16,
    backgroundColor: colors.baytgoLight,
    alignItems: 'center',
    justifyContent: 'center',
  },
  iconWrapPending: { backgroundColor: '#EDE9FE' },
  pendingDot: {
    position: 'absolute',
    top: 8,
    right: 8,
    width: 8,
    height: 8,
    borderRadius: 4,
    backgroundColor: '#7C3AED',
    borderWidth: 1.5,
    borderColor: colors.white,
  },
  body: { flex: 1 },
  topRow: { flexDirection: 'row', alignItems: 'center', justifyContent: 'space-between', gap: 8 },
  code: { fontSize: 12, fontWeight: '800', color: colors.baytgo },
  newChip: {
    backgroundColor: '#EDE9FE',
    paddingHorizontal: 8,
    paddingVertical: 3,
    borderRadius: 999,
  },
  newChipText: { fontSize: 10, fontWeight: '800', color: '#7C3AED' },
  name: { marginTop: 3, fontSize: 16, fontWeight: '900', color: colors.slate900 },
  metaRow: { flexDirection: 'row', alignItems: 'center', gap: 5, marginTop: 6 },
  dates: { fontSize: 12, fontWeight: '600', color: colors.slate500, flex: 1 },
  metaText: { fontSize: 12, fontWeight: '600', color: colors.slate500, flex: 1 },
  badgeRow: { flexDirection: 'row', flexWrap: 'wrap', gap: 6, marginTop: 10 },
  badge: { borderRadius: 999, paddingHorizontal: 8, paddingVertical: 4 },
  badgeText: { fontSize: 10, fontWeight: '800' },
  amount: { marginTop: 8, fontSize: 14, fontWeight: '900', color: colors.baytgo },
  amountHint: { marginTop: 2, fontSize: 10, fontWeight: '700', color: colors.slate500 },
});
