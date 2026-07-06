import React from 'react';
import { View, Text, StyleSheet, Image, TouchableOpacity } from 'react-native';
import { Ionicons } from '@expo/vector-icons';
import { resolveMediaUrl } from '../utils/mediaUrl';
import { colors } from '../theme/colors';
import { formatIdr } from '../utils/format';
import {
  bookingStatusMeta,
  paymentStatusMeta,
  formatDateRange,
  needsPayment,
} from '../utils/bookingLabels';

function StatusBadge({ label, color }) {
  return (
    <View style={[styles.badge, { backgroundColor: color + '18' }]}>
      <Text style={[styles.badgeText, { color }]}>{label}</Text>
    </View>
  );
}

export default function BookingListItem({ item, onPress }) {
  const bookingMeta = bookingStatusMeta(item.status);
  const paymentMeta = paymentStatusMeta(item.payment_status);
  const unpaid = needsPayment(item);

  return (
    <TouchableOpacity style={styles.card} onPress={onPress} activeOpacity={0.92}>
      <Image source={{ uri: resolveMediaUrl(item.muthowif_avatar) }} style={styles.avatar} />
      <View style={styles.body}>
        <Text style={styles.code}>{item.booking_code}</Text>
        <Text style={styles.name} numberOfLines={1}>{item.muthowif_name}</Text>
        <Text style={styles.dates}>{formatDateRange(item.starts_on, item.ends_on)}</Text>
        <View style={styles.badgeRow}>
          <StatusBadge label={bookingMeta.label} color={bookingMeta.color} />
          <StatusBadge label={paymentMeta.label} color={paymentMeta.color} />
        </View>
        <Text style={styles.amount}>{formatIdr(item.total_amount)}</Text>
      </View>
      <View style={styles.right}>
        {unpaid ? <Ionicons name="wallet-outline" size={18} color="#F59E0B" /> : null}
        <Ionicons name="chevron-forward" size={18} color={colors.slate400} />
      </View>
    </TouchableOpacity>
  );
}

const styles = StyleSheet.create({
  card: {
    flexDirection: 'row',
    alignItems: 'center',
    backgroundColor: colors.white,
    borderRadius: 18,
    padding: 12,
    marginBottom: 10,
    borderWidth: 1,
    borderColor: colors.slate100,
    gap: 12,
  },
  avatar: { width: 56, height: 56, borderRadius: 16, backgroundColor: colors.slate100 },
  body: { flex: 1 },
  code: { fontSize: 12, fontWeight: '800', color: colors.baytgo },
  name: { marginTop: 2, fontSize: 15, fontWeight: '800', color: colors.slate900 },
  dates: { marginTop: 4, fontSize: 12, fontWeight: '600', color: colors.slate500 },
  badgeRow: { flexDirection: 'row', flexWrap: 'wrap', gap: 6, marginTop: 8 },
  badge: { borderRadius: 999, paddingHorizontal: 8, paddingVertical: 4 },
  badgeText: { fontSize: 10, fontWeight: '800' },
  amount: { marginTop: 8, fontSize: 13, fontWeight: '800', color: colors.baytgo },
  right: { alignItems: 'center', gap: 8 },
});
