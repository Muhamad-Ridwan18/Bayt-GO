import React from 'react';
import { View, Text, StyleSheet, Image, TouchableOpacity } from 'react-native';
import { LinearGradient } from 'expo-linear-gradient';
import { Ionicons } from '@expo/vector-icons';
import { resolveMediaUrl } from '../utils/mediaUrl';
import { colors } from '../theme/colors';
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
    <View style={[styles.badge, { backgroundColor: color + '18' }]}>
      <Text style={[styles.badgeText, { color }]}>{label}</Text>
    </View>
  );
}

export default function BookingListItem({ item, onPress, onPay }) {
  const bookingMeta = bookingStatusMeta(item.status);
  const paymentMeta = paymentStatusMeta(item.payment_status);
  const showPay = canPayBooking(item);
  const awaiting = isAwaitingMuthowifConfirmation(item);
  const avatarUri = resolveMediaUrl(item.muthowif_avatar);

  return (
    <TouchableOpacity
      style={[styles.card, showPay && styles.cardUnpaid]}
      onPress={onPress}
      activeOpacity={0.9}
    >
      <View style={styles.avatarWrap}>
        {avatarUri ? (
          <Image source={{ uri: avatarUri }} style={styles.avatar} />
        ) : (
          <View style={[styles.avatar, styles.avatarPlaceholder]}>
            <Ionicons name="person" size={24} color={colors.slate400} />
          </View>
        )}
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
          <Ionicons name="calendar-outline" size={13} color={colors.slate400} />
          <Text style={styles.dates}>{formatDateRange(item.starts_on, item.ends_on)}</Text>
        </View>

        <View style={styles.badgeRow}>
          <StatusBadge label={bookingMeta.label} color={bookingMeta.color} />
          <StatusBadge label={paymentMeta.label} color={paymentMeta.color} />
        </View>

        <Text style={styles.amount}>{formatIdr(customerPayableAmount(item.pricing, item.total_amount))}</Text>

        {showPay ? (
          <TouchableOpacity
            style={styles.payBtn}
            onPress={(e) => {
              e?.stopPropagation?.();
              onPay?.(item);
            }}
            activeOpacity={0.9}
          >
            <LinearGradient colors={['#F59E0B', '#D97706']} style={styles.payGradient}>
              <Ionicons name="wallet-outline" size={16} color={colors.white} />
              <Text style={styles.payBtnText}>Bayar sekarang</Text>
            </LinearGradient>
          </TouchableOpacity>
        ) : null}
      </View>

      <Ionicons name="chevron-forward" size={18} color={colors.slate400} />
    </TouchableOpacity>
  );
}

const styles = StyleSheet.create({
  card: {
    flexDirection: 'row',
    alignItems: 'flex-start',
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
  cardUnpaid: {
    borderColor: '#FDE68A',
    backgroundColor: '#FFFBEB',
  },
  avatarWrap: { position: 'relative' },
  avatar: { width: 52, height: 52, borderRadius: 16, backgroundColor: colors.slate100 },
  avatarPlaceholder: { alignItems: 'center', justifyContent: 'center' },
  payDot: {
    position: 'absolute',
    top: -2,
    right: -2,
    width: 10,
    height: 10,
    borderRadius: 5,
    backgroundColor: '#F59E0B',
    borderWidth: 2,
    borderColor: colors.white,
  },
  body: { flex: 1 },
  topRow: { flexDirection: 'row', alignItems: 'center', justifyContent: 'space-between', gap: 8 },
  code: { fontSize: 12, fontWeight: '800', color: colors.baytgo },
  payChip: {
    backgroundColor: '#FEF3C7',
    paddingHorizontal: 8,
    paddingVertical: 3,
    borderRadius: 999,
  },
  payChipText: { fontSize: 10, fontWeight: '800', color: '#B45309' },
  waitChip: {
    backgroundColor: '#EDE9FE',
    paddingHorizontal: 8,
    paddingVertical: 3,
    borderRadius: 999,
  },
  waitChipText: { fontSize: 10, fontWeight: '800', color: '#7C3AED' },
  name: { marginTop: 3, fontSize: 16, fontWeight: '900', color: colors.slate900 },
  metaRow: { flexDirection: 'row', alignItems: 'center', gap: 5, marginTop: 6 },
  dates: { fontSize: 12, fontWeight: '600', color: colors.slate500, flex: 1 },
  badgeRow: { flexDirection: 'row', flexWrap: 'wrap', gap: 6, marginTop: 10 },
  badge: { borderRadius: 999, paddingHorizontal: 8, paddingVertical: 4 },
  badgeText: { fontSize: 10, fontWeight: '800' },
  amount: { marginTop: 8, fontSize: 15, fontWeight: '900', color: colors.baytgo },
  payBtn: { marginTop: 12, borderRadius: 12, overflow: 'hidden' },
  payGradient: {
    flexDirection: 'row',
    alignItems: 'center',
    justifyContent: 'center',
    gap: 6,
    paddingVertical: 11,
  },
  payBtnText: { fontSize: 13, fontWeight: '800', color: colors.white },
});
