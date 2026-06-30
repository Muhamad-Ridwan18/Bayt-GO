import React, { useCallback, useState } from 'react';
import {
  View,
  Text,
  StyleSheet,
  ScrollView,
  ActivityIndicator,
  TouchableOpacity,
  Share,
} from 'react-native';
import { useFocusEffect } from '@react-navigation/native';
import { Ionicons } from '@expo/vector-icons';
import ScreenHeader from '../components/ScreenHeader';
import { fetchInvoice } from '../api/bookings';
import { useAuth } from '../context/AuthContext';
import { colors } from '../theme/colors';
import { formatIdr } from '../utils/format';
import { formatDateRange, serviceTypeLabel } from '../utils/bookingLabels';

function Row({ label, value, bold }) {
  return (
    <View style={styles.row}>
      <Text style={styles.rowLabel}>{label}</Text>
      <Text style={[styles.rowValue, bold && styles.rowBold]}>{value}</Text>
    </View>
  );
}

export default function BookingInvoiceScreen({ navigation, route }) {
  const { token } = useAuth();
  const { bookingId } = route.params;

  const [invoice, setInvoice] = useState(null);
  const [loading, setLoading] = useState(true);
  const [error, setError] = useState(null);

  const load = useCallback(async () => {
    setLoading(true);
    try {
      const data = await fetchInvoice(token, bookingId);
      setInvoice(data);
      setError(null);
    } catch (err) {
      setError(err.message || 'Gagal memuat invoice');
    } finally {
      setLoading(false);
    }
  }, [token, bookingId]);

  useFocusEffect(
    useCallback(() => {
      load();
    }, [load]),
  );

  const handleShare = async () => {
    if (!invoice) return;
    const period = invoice.service_period;
    const lines = [
      `Invoice ${invoice.booking_code}`,
      `Pelanggan: ${invoice.customer?.name || '—'}`,
      `Muthowif: ${invoice.muthowif?.name || '—'}`,
      `Periode: ${formatDateRange(period?.starts_on, period?.ends_on)}`,
      `Total: ${formatIdr(invoice.amounts?.total || 0)}`,
    ];
    try {
      await Share.share({ message: lines.join('\n') });
    } catch {
      // user dismissed
    }
  };

  if (loading) {
    return (
      <View style={styles.container}>
        <ScreenHeader title="Invoice" onBack={() => navigation.goBack()} />
        <ActivityIndicator color={colors.baytgo} style={styles.loader} />
      </View>
    );
  }

  if (error || !invoice) {
    return (
      <View style={styles.container}>
        <ScreenHeader title="Invoice" onBack={() => navigation.goBack()} />
        <View style={styles.empty}>
          <Text style={styles.emptyText}>{error || 'Invoice tidak tersedia'}</Text>
          <TouchableOpacity onPress={load}>
            <Text style={styles.retry}>Coba lagi</Text>
          </TouchableOpacity>
        </View>
      </View>
    );
  }

  const paidAt = invoice.paid_at
    ? new Date(invoice.paid_at).toLocaleString('id-ID')
    : '—';

  return (
    <View style={styles.container}>
      <ScreenHeader
        title="Invoice"
        onBack={() => navigation.goBack()}
        rightAction={(
          <TouchableOpacity onPress={handleShare} hitSlop={8}>
            <Ionicons name="share-outline" size={22} color={colors.baytgo} />
          </TouchableOpacity>
        )}
      />

      <ScrollView contentContainerStyle={styles.scroll}>
        <View style={styles.card}>
          <Text style={styles.code}>{invoice.booking_code}</Text>
          <Text style={styles.paidAt}>Dibayar: {paidAt}</Text>
        </View>

        <View style={styles.section}>
          <Text style={styles.sectionTitle}>Pelanggan</Text>
          <Row label="Nama" value={invoice.customer?.name || '—'} />
          <Row label="Email" value={invoice.customer?.email || '—'} />
          <Row label="Telepon" value={invoice.customer?.phone || '—'} />
        </View>

        <View style={styles.section}>
          <Text style={styles.sectionTitle}>Layanan</Text>
          <Row label="Muthowif" value={invoice.muthowif?.name || '—'} />
          <Row label="Periode" value={formatDateRange(invoice.service_period?.starts_on, invoice.service_period?.ends_on)} />
          <Row label="Jamaah" value={String(invoice.pilgrim_count || '—')} />
          <Row label="Tipe" value={serviceTypeLabel(invoice.service_type)} />
        </View>

        <View style={styles.section}>
          <Text style={styles.sectionTitle}>Rincian Biaya</Text>
          <Row label="Biaya layanan" value={formatIdr(invoice.amounts?.base || 0)} />
          <Row label="Biaya platform" value={formatIdr(invoice.amounts?.platform_fee || 0)} />
          <Row label="Total dibayar" value={formatIdr(invoice.amounts?.total || 0)} bold />
        </View>

        {invoice.payment ? (
          <View style={styles.section}>
            <Text style={styles.sectionTitle}>Pembayaran</Text>
            <Row label="Order ID" value={invoice.payment.order_id || '—'} />
            <Row label="Metode" value={invoice.payment.payment_type || '—'} />
          </View>
        ) : null}
      </ScrollView>
    </View>
  );
}

const styles = StyleSheet.create({
  container: { flex: 1, backgroundColor: colors.canvas },
  scroll: { padding: 16, paddingBottom: 32 },
  loader: { marginTop: 40 },
  empty: { padding: 24, alignItems: 'center' },
  emptyText: { fontSize: 14, color: colors.slate500, fontWeight: '600', textAlign: 'center' },
  retry: { marginTop: 10, fontSize: 14, fontWeight: '800', color: colors.baytgo },
  card: {
    backgroundColor: colors.white,
    borderRadius: 20,
    padding: 20,
    alignItems: 'center',
    marginBottom: 12,
    borderWidth: 1,
    borderColor: colors.slate100,
  },
  code: { fontSize: 20, fontWeight: '900', color: colors.baytgo },
  paidAt: { marginTop: 6, fontSize: 12, color: colors.slate500, fontWeight: '600' },
  section: {
    backgroundColor: colors.white,
    borderRadius: 18,
    padding: 16,
    marginBottom: 12,
    borderWidth: 1,
    borderColor: colors.slate100,
  },
  sectionTitle: { fontSize: 14, fontWeight: '900', color: colors.baytgo, marginBottom: 10 },
  row: {
    flexDirection: 'row',
    justifyContent: 'space-between',
    gap: 12,
    paddingVertical: 8,
    borderBottomWidth: 1,
    borderBottomColor: colors.slate100,
  },
  rowLabel: { fontSize: 13, fontWeight: '600', color: colors.slate500 },
  rowValue: { flex: 1, fontSize: 13, fontWeight: '700', color: colors.slate900, textAlign: 'right' },
  rowBold: { fontWeight: '900', color: colors.baytgo, fontSize: 15 },
});
