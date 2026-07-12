import React, { useCallback, useState } from 'react';
import { StyleSheet, View, ScrollView, Share } from 'react-native';
import { useFocusEffect } from '@react-navigation/native';
import { Share2 } from 'lucide-react-native';
import ScreenHeader from '../components/ScreenHeader';
import { fetchInvoice } from '../api/bookings';
import { useAuth } from '../context/AuthContext';
import Button from '../ui/Button';
import ErrorState from '../ui/ErrorState';
import PressableScale from '../ui/PressableScale';
import { SkeletonList } from '../ui/Skeleton';
import { InvoiceDocument } from '../features/booking/BookingInvoiceParts';
import { colors, layout, radius, spacing } from '../theme/tokens';
import { formatIdr } from '../utils/format';
import { formatDateRange } from '../utils/bookingLabels';

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

  useFocusEffect(useCallback(() => { load(); }, [load]));

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
      // dismissed
    }
  };

  if (loading) {
    return (
      <View style={styles.container}>
        <ScreenHeader title="Invoice" onBack={() => navigation.goBack()} />
        <SkeletonList count={3} style={styles.skeleton} />
      </View>
    );
  }

  if (error || !invoice) {
    return (
      <View style={styles.container}>
        <ScreenHeader title="Invoice" onBack={() => navigation.goBack()} />
        <ErrorState description={error || 'Invoice tidak tersedia'} onRetry={load} />
      </View>
    );
  }

  return (
    <View style={styles.container}>
      <ScreenHeader
        title="Invoice"
        subtitle={invoice.booking_code}
        onBack={() => navigation.goBack()}
        rightAction={(
          <PressableScale onPress={handleShare} haptic="light" style={styles.shareBtn}>
            <Share2 size={20} color={colors.baytgo} strokeWidth={2} />
          </PressableScale>
        )}
      />

      <ScrollView contentContainerStyle={styles.scroll} showsVerticalScrollIndicator={false}>
        <InvoiceDocument invoice={invoice} />

        <View style={styles.shareCta}>
          <Button
            label="Bagikan invoice"
            icon={<Share2 size={18} color={colors.white} strokeWidth={2} />}
            onPress={handleShare}
          />
        </View>
      </ScrollView>
    </View>
  );
}

const styles = StyleSheet.create({
  container: { flex: 1, backgroundColor: colors.background },
  scroll: { padding: layout.screenPadding, paddingBottom: spacing['4xl'] },
  skeleton: { padding: layout.screenPadding },
  shareBtn: {
    width: 44,
    height: 44,
    alignItems: 'center',
    justifyContent: 'center',
    borderRadius: radius.sm,
    backgroundColor: colors.baytgoLight,
  },
  shareCta: { marginTop: spacing.lg },
});
