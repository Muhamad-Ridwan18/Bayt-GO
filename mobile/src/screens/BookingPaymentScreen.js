import React, { useCallback, useEffect, useRef, useState } from 'react';
import {
  View, Text, StyleSheet, ScrollView, Linking, Alert, RefreshControl, ActivityIndicator,
} from 'react-native';
import {
  Wallet, Receipt, User, Calendar, Clock, AlertCircle, ArrowLeftRight,
  AlarmClock, ExternalLink, CircleArrowRight, CircleCheck,
} from 'lucide-react-native';
import ScreenHeader from '../components/ScreenHeader';
import { fetchBooking, fetchPaymentMethods, initiatePayment } from '../api/bookings';
import { useAuth } from '../context/AuthContext';
import { navigateToBookingDetail } from '../navigation/rootNavigation';
import Button from '../ui/Button';
import Card from '../ui/Card';
import EmptyState from '../ui/EmptyState';
import StickyFooter from '../ui/StickyFooter';
import SuccessState from '../ui/SuccessState';
import { SkeletonList } from '../ui/Skeleton';
import { notifySuccess } from '../utils/feedback';
import StatusPill from '../features/booking/StatusPill';
import {
  StepIndicator, EnvironmentBanner, MethodCard, PaymentInfoRow,
} from '../features/booking/PaymentScreenParts';
import { colors, layout, radius, spacing, typography } from '../theme/tokens';
import { formatIdr } from '../utils/format';
import {
  bookingStatusMeta, paymentStatusMeta, formatDateRange, isAwaitingMuthowifConfirmation,
} from '../utils/bookingLabels';
import { CustomerPricingBreakdown } from '../components/BookingPricingBreakdown';
import { useHideTabBarOnFocus } from '../hooks/useHideTabBarOnFocus';

export default function BookingPaymentScreen({ navigation, route }) {
  const { token } = useAuth();
  const { bookingId, bookingCode } = route.params;
  const [loading, setLoading] = useState(true);
  const [refreshing, setRefreshing] = useState(false);
  const [paying, setPaying] = useState(false);
  const [error, setError] = useState('');
  const [amount, setAmount] = useState(0);
  const [pricing, setPricing] = useState(null);
  const [methods, setMethods] = useState([]);
  const [selectedMethod, setSelectedMethod] = useState('');
  const [instructions, setInstructions] = useState(null);
  const [booking, setBooking] = useState(null);
  const [paymentEnvironment, setPaymentEnvironment] = useState(null);
  const [selectedMethodMeta, setSelectedMethodMeta] = useState(null);
  const pollRef = useRef(null);

  const loadMethods = useCallback(async () => {
    setError('');
    try {
      const data = await fetchPaymentMethods(token, bookingId);
      if (data.driver !== 'moota') {
        setError('Aplikasi mobile saat ini hanya mendukung pembayaran Moota.');
        return;
      }
      setAmount(data.amounts?.total || data.amount || 0);
      setPricing(data.pricing || booking?.pricing || null);
      const meta = data.methods_meta || (data.methods || []).map((id) => ({ id, label: id }));
      setMethods(meta);
      setPaymentEnvironment(data.payment_environment || null);
      if (meta.length === 1) {
        setSelectedMethod(meta[0].id);
        setSelectedMethodMeta(meta[0]);
      }
    } catch (err) {
      setError(err.message || 'Gagal memuat metode pembayaran');
    }
  }, [token, bookingId, booking?.pricing]);

  const refreshBooking = useCallback(async (silent = false) => {
    try {
      const data = await fetchBooking(token, bookingId);
      setBooking(data);
      if (data.pricing) setPricing(data.pricing);
      if (data.payment_status === 'paid') {
        clearInterval(pollRef.current);
        if (!silent) {
          notifySuccess('Pembayaran berhasil. Pesanan sudah lunas.');
          navigateToBookingDetail(navigation, bookingId);
        }
      }
      return data;
    } catch {
      return null;
    }
  }, [token, bookingId, navigation]);

  const loadAll = useCallback(async (refresh = false) => {
    if (refresh) setRefreshing(true);
    else setLoading(true);
    await Promise.all([loadMethods(), refreshBooking(true)]);
    setLoading(false);
    setRefreshing(false);
  }, [loadMethods, refreshBooking]);

  useEffect(() => {
    loadAll();
    pollRef.current = setInterval(() => refreshBooking(true), 10000);
    return () => clearInterval(pollRef.current);
  }, [loadAll, refreshBooking]);
  useHideTabBarOnFocus(navigation);

  const handlePay = async () => {
    if (!selectedMethod) {
      setError('Pilih rekening tujuan transfer.');
      return;
    }
    setPaying(true);
    setError('');
    try {
      const data = await initiatePayment(token, bookingId, selectedMethod);
      if (data.step === 'payment_instructions' && data.driver === 'moota') {
        setInstructions(data);
        if (data.payment_environment) setPaymentEnvironment(data.payment_environment);
        setSelectedMethodMeta(data.method_meta || methods.find((m) => m.id === selectedMethod) || null);
      } else {
        setError('Respons pembayaran tidak valid.');
      }
    } catch (err) {
      setError(err.message || 'Gagal membuat instruksi pembayaran');
    } finally {
      setPaying(false);
    }
  };

  const openCheckout = () => {
    const url = instructions?.checkout_url;
    if (!url) {
      Alert.alert('URL tidak tersedia', 'Hubungi admin jika masalah berlanjut.');
      return;
    }
    Linking.openURL(url).catch(() => Alert.alert('Gagal membuka', url));
  };

  const isPaid = booking?.payment_status === 'paid';
  const awaitingMuthowif = booking && isAwaitingMuthowifConfirmation(booking);
  const bookingMeta = booking ? bookingStatusMeta(booking.status) : null;
  const paymentMeta = booking ? paymentStatusMeta(booking.payment_status) : null;
  const currentStep = instructions ? 2 : 1;
  const displayAmount = instructions?.gross_amount || instructions?.expected_transfer_total || amount;

  if (loading) {
    return (
      <View style={styles.container}>
        <ScreenHeader title="Pembayaran" subtitle={bookingCode} onBack={() => navigation.goBack()} />
        <SkeletonList count={2} style={styles.skeleton} />
      </View>
    );
  }

  if (isPaid) {
    return (
      <View style={styles.container}>
        <ScreenHeader title="Pembayaran" onBack={() => navigation.goBack()} />
        <View style={styles.paidWrap}>
          <SuccessState
            title="Pembayaran lunas"
            description={`Pesanan ${bookingCode} sudah dibayar.`}
            actionLabel="Lihat detail pesanan"
            onAction={() => navigateToBookingDetail(navigation, bookingId)}
          />
        </View>
      </View>
    );
  }

  const footerCta = instructions ? (
    <Button
      label="Buka halaman pembayaran Moota"
      icon={<ExternalLink size={18} color={colors.white} strokeWidth={2} />}
      onPress={openCheckout}
    />
  ) : (
    <Button
      label="Lanjut ke pembayaran"
      icon={<CircleArrowRight size={18} color={colors.white} strokeWidth={2} />}
      onPress={handlePay}
      loading={paying}
      disabled={methods.length === 0}
    />
  );

  return (
    <View style={styles.container}>
      <ScreenHeader title="Pembayaran" subtitle={bookingCode} onBack={() => navigation.goBack()} />

      <ScrollView
        contentContainerStyle={[styles.scroll, styles.scrollWithFooter]}
        refreshControl={<RefreshControl refreshing={refreshing} onRefresh={() => loadAll(true)} tintColor={colors.baytgo} />}
        showsVerticalScrollIndicator={false}
      >
        <StepIndicator step={currentStep} />
        <EnvironmentBanner environment={paymentEnvironment} />

        <Card style={styles.summaryCard} padding={spacing.xl}>
          <View style={styles.summaryTop}>
            <View>
              <Text style={styles.summaryLabel}>Total pembayaran</Text>
              <Text style={styles.summaryAmount}>{formatIdr(displayAmount)}</Text>
            </View>
            <View style={styles.summaryIcon}>
              <Wallet size={22} color={colors.baytgo} strokeWidth={2} />
            </View>
          </View>
          <View style={styles.summaryDivider} />
          <PaymentInfoRow icon={Receipt} label="Kode pesanan" value={bookingCode || '—'} />
          {booking?.muthowif_profile?.user?.name ? (
            <PaymentInfoRow icon={User} label="Muthowif" value={booking.muthowif_profile.user.name} />
          ) : null}
          {booking?.starts_on ? (
            <PaymentInfoRow icon={Calendar} label="Tanggal" value={formatDateRange(booking.starts_on, booking.ends_on)} />
          ) : null}
          {bookingMeta && paymentMeta ? (
            <View style={styles.badgeRow}>
              <StatusPill label={bookingMeta.label} color={bookingMeta.color} />
              <StatusPill label={paymentMeta.label} color={paymentMeta.color} />
            </View>
          ) : null}
        </Card>

        {pricing ? (
          <Card style={styles.pricingCard} padding={spacing.lg}>
            <Text style={styles.pricingTitle}>Rincian pembayaran</Text>
            <CustomerPricingBreakdown pricing={pricing} />
          </Card>
        ) : null}

        {awaitingMuthowif ? (
          <Card style={styles.waitBanner} padding={spacing.lg} elevated={false}>
            <Clock size={18} color="#7C3AED" strokeWidth={2} />
            <Text style={styles.waitBannerText}>
              Muthowif belum mengonfirmasi pesanan. Anda tetap bisa melanjutkan pembayaran.
            </Text>
          </Card>
        ) : null}

        {error ? (
          <Card style={styles.errorBox} padding={spacing.lg} elevated={false}>
            <AlertCircle size={18} color={colors.error} strokeWidth={2} />
            <Text style={styles.errorText}>{error}</Text>
          </Card>
        ) : null}

        {instructions ? (
          <Card style={styles.instructionsCard} padding={spacing.xl}>
            <View style={styles.instructionsHead}>
              <ArrowLeftRight size={20} color={colors.baytgo} strokeWidth={2} />
              <Text style={styles.instructionsTitle}>Instruksi transfer</Text>
            </View>
            <Text style={styles.instructionsText}>
              Transfer sesuai nominal unik ke rekening yang ditampilkan di halaman Moota.
            </Text>

            {instructions.expected_transfer_total ? (
              <View style={styles.amountHighlight}>
                <Text style={styles.amountHighlightLabel}>Nominal transfer</Text>
                <Text style={styles.amountHighlightValue}>
                  {formatIdr(instructions.expected_transfer_total)}
                </Text>
              </View>
            ) : null}

            {selectedMethodMeta ? (
              <View style={styles.selectedBankCard}>
                <Text style={styles.selectedBankTitle}>Rekening tujuan</Text>
                <Text style={styles.selectedBankName}>
                  {selectedMethodMeta.bank_name || selectedMethodMeta.label}
                </Text>
                {selectedMethodMeta.account_holder ? (
                  <Text style={styles.selectedBankLine}>a.n. {selectedMethodMeta.account_holder}</Text>
                ) : null}
                {selectedMethodMeta.account_number ? (
                  <Text style={styles.selectedBankLine}>No. {selectedMethodMeta.account_number}</Text>
                ) : null}
              </View>
            ) : null}

            {instructions.expiry_time ? (
              <View style={styles.expiryRow}>
                <AlarmClock size={16} color={colors.textMuted} strokeWidth={2} />
                <Text style={styles.expiryText}>Batas waktu: {instructions.expiry_time}</Text>
              </View>
            ) : null}

            <View style={styles.pollHint}>
              <ActivityIndicator color={colors.baytgo} size="small" />
              <Text style={styles.waitHint}>Menunggu verifikasi transfer. Status diperbarui otomatis.</Text>
            </View>
          </Card>
        ) : (
          <>
            <Text style={styles.sectionTitle}>Pilih rekening tujuan</Text>
            <Text style={styles.sectionSub}>Pilih rekening bank untuk transfer pembayaran Anda</Text>

            {methods.length === 0 ? (
              <EmptyState
                variant="package"
                title="Metode belum tersedia"
                description="Metode pembayaran belum tersedia untuk pesanan ini."
              />
            ) : (
              methods.map((item) => (
                <MethodCard
                  key={item.id}
                  item={item}
                  environment={paymentEnvironment}
                  selected={selectedMethod === item.id}
                  onPress={() => { setSelectedMethod(item.id); setSelectedMethodMeta(item); }}
                />
              ))
            )}

          </>
        )}
      </ScrollView>

      <StickyFooter priceLabel="Total transfer" priceValue={formatIdr(displayAmount)}>
        {footerCta}
      </StickyFooter>
    </View>
  );
}

const styles = StyleSheet.create({
  container: { flex: 1, backgroundColor: colors.background },
  scroll: { padding: layout.screenPadding, paddingBottom: spacing['5xl'] },
  scrollWithFooter: { paddingBottom: 120 },
  skeleton: { padding: layout.screenPadding },
  summaryCard: { marginBottom: spacing.lg },
  summaryTop: { flexDirection: 'row', justifyContent: 'space-between', alignItems: 'flex-start' },
  summaryLabel: { ...typography.label, color: colors.textSecondary, textTransform: 'uppercase' },
  summaryAmount: { marginTop: spacing.xs, ...typography.hero, fontSize: 28, color: colors.baytgo },
  summaryIcon: {
    width: 44, height: 44, borderRadius: radius.sm,
    backgroundColor: colors.baytgoLight, alignItems: 'center', justifyContent: 'center',
  },
  summaryDivider: { height: 1, backgroundColor: colors.border, marginVertical: spacing.lg },
  pricingCard: { marginBottom: spacing.lg },
  pricingTitle: { ...typography.caption, fontFamily: 'PlusJakartaSans_700Bold', color: colors.textPrimary, marginBottom: spacing.xs },
  badgeRow: { flexDirection: 'row', flexWrap: 'wrap', gap: spacing.sm, marginTop: spacing.md },
  waitBanner: {
    flexDirection: 'row', alignItems: 'flex-start', gap: spacing.md,
    marginBottom: spacing.lg, borderColor: '#DDD6FE', backgroundColor: '#F5F3FF',
  },
  waitBannerText: { flex: 1, ...typography.caption, color: '#5B21B6', lineHeight: 19 },
  errorBox: {
    flexDirection: 'row', alignItems: 'flex-start', gap: spacing.md,
    marginBottom: spacing.lg, borderColor: '#FECACA', backgroundColor: colors.errorLight,
  },
  errorText: { flex: 1, ...typography.caption, color: colors.error, lineHeight: 18 },
  sectionTitle: { ...typography.subtitle, fontSize: 16, color: colors.baytgo, marginBottom: spacing.xs },
  sectionSub: { ...typography.small, color: colors.textSecondary, marginBottom: spacing.lg },
  instructionsCard: { marginBottom: spacing.lg },
  instructionsHead: { flexDirection: 'row', alignItems: 'center', gap: spacing.sm, marginBottom: spacing.md },
  instructionsTitle: { ...typography.subtitle, fontSize: 16, color: colors.baytgo },
  instructionsText: { ...typography.caption, color: colors.textSecondary, lineHeight: 21 },
  amountHighlight: {
    marginTop: spacing.lg, backgroundColor: colors.baytgoLight,
    borderRadius: radius.sm, padding: spacing.lg, alignItems: 'center',
  },
  amountHighlightLabel: { ...typography.caption, color: colors.textSecondary },
  amountHighlightValue: { marginTop: spacing.xs, ...typography.title, fontSize: 22, color: colors.baytgo },
  selectedBankCard: {
    marginTop: spacing.lg, backgroundColor: colors.background,
    borderRadius: radius.sm, padding: spacing.lg, borderWidth: 1, borderColor: colors.border,
  },
  selectedBankTitle: { ...typography.label, color: colors.textSecondary, textTransform: 'uppercase' },
  selectedBankName: { marginTop: spacing.sm, ...typography.subtitle, color: colors.baytgo },
  selectedBankLine: { marginTop: spacing.xs, ...typography.caption, fontFamily: 'PlusJakartaSans_700Bold', color: colors.slate700 },
  expiryRow: { flexDirection: 'row', alignItems: 'center', gap: spacing.sm, marginTop: spacing.md },
  expiryText: { ...typography.caption, color: colors.textSecondary },
  pollHint: { flexDirection: 'row', alignItems: 'center', justifyContent: 'center', gap: spacing.sm, marginTop: spacing.lg },
  waitHint: { ...typography.small, color: colors.textSecondary, flex: 1 },
  paidWrap: { flex: 1, alignItems: 'center', justifyContent: 'center', padding: spacing['3xl'] },
  paidIcon: { marginBottom: spacing.lg },
  paidTitle: { ...typography.title, color: colors.baytgo },
  paidSub: { marginTop: spacing.sm, ...typography.caption, color: colors.textSecondary, textAlign: 'center', marginBottom: spacing['2xl'] },
});
