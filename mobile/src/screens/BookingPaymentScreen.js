import React, { useCallback, useEffect, useRef, useState } from 'react';
import {
  View, Text, StyleSheet, ScrollView, Linking, Alert, RefreshControl, ActivityIndicator, Share,
} from 'react-native';
import {
  Wallet, Receipt, User, Calendar, Clock, AlertCircle, ArrowLeftRight,
  AlarmClock, ExternalLink, CircleArrowRight, Copy,
} from 'lucide-react-native';
import ScreenHeader from '../components/ScreenHeader';
import { fetchBooking, fetchPaymentMethods, initiatePayment } from '../api/bookings';
import { useAuth } from '../context/AuthContext';
import { navigateToBookingDetail } from '../navigation/rootNavigation';
import Button from '../ui/Button';
import Card from '../ui/Card';
import PressableScale from '../ui/PressableScale';
import EmptyState from '../ui/EmptyState';
import StickyFooter from '../ui/StickyFooter';
import SuccessState from '../ui/SuccessState';
import { SkeletonList } from '../ui/Skeleton';
import {
  StepIndicator, EnvironmentBanner, MethodCard, PaymentInfoRow, CopyableValue, PaymentHowToSteps,
} from '../features/booking/PaymentScreenParts';
import { notifySuccess } from '../utils/feedback';
import StatusPill from '../features/booking/StatusPill';
import { colors, layout, radius, spacing, typography } from '../theme/tokens';
import { formatIdr } from '../utils/format';
import {
  bookingStatusMeta, paymentStatusMeta, formatDateRange, isAwaitingMuthowifConfirmation, needsPayment,
} from '../utils/bookingLabels';
import PaymentDeadlineBanner from '../features/booking/PaymentDeadlineBanner';
import { CustomerPricingBreakdown } from '../components/BookingPricingBreakdown';
import { useHideTabBarOnFocus } from '../hooks/useHideTabBarOnFocus';
import { useLocale } from '../utils/locale';

const METHOD_GROUP_LABELS_ID = {
  moota: 'Rekening tujuan',
  bank: 'Transfer bank',
  qris: 'QRIS',
  ewallet: 'E-wallet',
  other: 'Metode lain',
};

const METHOD_GROUP_LABELS_EN = {
  moota: 'Destination account',
  bank: 'Bank transfer',
  qris: 'QRIS',
  ewallet: 'E-wallet',
  other: 'Other methods',
};

function groupedMethods(methods, isEn) {
  const order = ['moota', 'bank', 'qris', 'ewallet', 'other'];
  const labels = isEn ? METHOD_GROUP_LABELS_EN : METHOD_GROUP_LABELS_ID;
  const map = {};
  methods.forEach((item) => {
    const key = item.group || 'other';
    if (!map[key]) map[key] = [];
    map[key].push(item);
  });
  return order.filter((key) => map[key]?.length).map((key) => ({
    key,
    title: labels[key] || key,
    items: map[key],
  }));
}

export default function BookingPaymentScreen({ navigation, route }) {
  const { token } = useAuth();
  const locale = useLocale(); const isEn = locale === 'en';
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
  const [driver, setDriver] = useState('moota');
  const pollRef = useRef(null);
  const bookingRef = useRef(null);

  const loadMethods = useCallback(async () => {
    setError('');
    try {
      const data = await fetchPaymentMethods(token, bookingId);
      setDriver(data.driver || 'doku');
      setAmount(data.amounts?.total || data.amount || 0);
      if (data.pricing) setPricing(data.pricing);
      const meta = data.methods_meta || (data.methods || []).map((id) => ({ id, label: id }));
      setMethods(meta);
      setPaymentEnvironment(data.payment_environment || null);
      if (meta.length === 1) {
        setSelectedMethod(meta[0].id);
        setSelectedMethodMeta(meta[0]);
      }
    } catch (err) {
      setError(err.message || (isEn ? 'Failed to load payment methods' : 'Gagal memuat metode pembayaran'));
    }
  }, [token, bookingId]);

  const refreshBooking = useCallback(async (silent = false) => {
    try {
      const data = await fetchBooking(token, bookingId);
      const prev = bookingRef.current;
      const unchanged = prev
        && prev.payment_status === data.payment_status
        && prev.status === data.status
        && prev.starts_on === data.starts_on
        && prev.ends_on === data.ends_on;

      if (!unchanged) {
        bookingRef.current = data;
        setBooking(data);
        if (data.pricing) setPricing(data.pricing);
      }

      if (data.payment_status === 'paid') {
        clearInterval(pollRef.current);
        if (!silent) {
          notifySuccess(isEn ? 'Payment successful. Order is paid.' : 'Pembayaran berhasil. Pesanan sudah lunas.');
          navigateToBookingDetail(navigation, bookingId);
        }
      }
      return data;
    } catch {
      return null;
    }
  }, [token, bookingId, navigation]);

  const loadAll = useCallback(async (refresh = false) => {
    if (refresh) {
      setRefreshing(true);
    } else {
      setLoading(true);
    }
    await Promise.all([loadMethods(), refreshBooking(true)]);
    setLoading(false);
    setRefreshing(false);
  }, [loadMethods, refreshBooking]);

  useEffect(() => {
    let cancelled = false;

    (async () => {
      setLoading(true);
      await Promise.all([loadMethods(), refreshBooking(true)]);
      if (!cancelled) setLoading(false);
    })();

    return () => {
      cancelled = true;
    };
  }, [loadMethods, refreshBooking]);

  useEffect(() => {
    pollRef.current = setInterval(() => refreshBooking(true), 10000);
    return () => clearInterval(pollRef.current);
  }, [refreshBooking]);
  useHideTabBarOnFocus(navigation);

  const handlePay = async () => {
    if (!selectedMethod) {
      setError(driver === 'moota'
        ? (isEn ? 'Select a destination account.' : 'Pilih rekening tujuan transfer.')
        : (isEn ? 'Select a payment method.' : 'Pilih metode pembayaran.'));
      return;
    }
    setPaying(true);
    setError('');
    try {
      const data = await initiatePayment(token, bookingId, selectedMethod);
      if (data.step === 'payment_instructions') {
        setInstructions(data);
        if (data.driver) setDriver(data.driver);
        if (data.payment_environment) setPaymentEnvironment(data.payment_environment);
        setSelectedMethodMeta(data.method_meta || methods.find((m) => m.id === selectedMethod) || null);
      } else {
        setError(isEn ? 'Invalid payment response.' : 'Respons pembayaran tidak valid.');
      }
    } catch (err) {
      setError(err.message || (isEn ? 'Failed to create payment instructions' : 'Gagal membuat instruksi pembayaran'));
    } finally {
      setPaying(false);
    }
  };

  const openUrl = (url) => {
    if (!url) {
      Alert.alert(isEn ? 'URL not available' : 'URL tidak tersedia', isEn ? 'Contact admin if the issue persists.' : 'Hubungi admin jika masalah berlanjut.');
      return;
    }
    Linking.openURL(url).catch(() => Alert.alert(isEn ? 'Failed to open' : 'Gagal membuka', url));
  };

  const copyValue = async (value) => {
    if (!value) return;
    try {
      await Share.share({ message: String(value) });
      notifySuccess(isEn ? 'Ready to copy / share.' : 'Siap disalin / dibagikan.');
    } catch {
      /* dismissed */
    }
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
        <ScreenHeader title={isEn ? 'Payment' : 'Pembayaran'} subtitle={bookingCode} onBack={() => navigation.goBack()} />
        <SkeletonList count={2} style={styles.skeleton} />
      </View>
    );
  }

  if (isPaid) {
    return (
      <View style={styles.container}>
        <ScreenHeader title={isEn ? 'Payment' : 'Pembayaran'} onBack={() => navigation.goBack()} />
        <View style={styles.paidWrap}>
          <SuccessState
            title={isEn ? 'Payment complete' : 'Pembayaran lunas'}
            description={isEn ? `Order ${bookingCode} has been paid.` : `Pesanan ${bookingCode} sudah dibayar.`}
            actionLabel={isEn ? 'View order details' : 'Lihat detail pesanan'}
            onAction={() => navigateToBookingDetail(navigation, bookingId)}
          />
        </View>
      </View>
    );
  }

  if (awaitingMuthowif) {
    return (
      <View style={styles.container}>
        <ScreenHeader title={isEn ? 'Payment' : 'Pembayaran'} subtitle={bookingCode} onBack={() => navigation.goBack()} />
        <View style={styles.paidWrap}>
          <EmptyState
            variant="package"
            icon={<Clock size={30} color="#7C3AED" strokeWidth={1.8} />}
            title={isEn ? 'Awaiting muthowif confirmation' : 'Menunggu konfirmasi muthowif'}
            description={isEn ? 'Payment will be available after the muthowif confirms your order.' : 'Pembayaran akan tersedia setelah muthowif mengonfirmasi pesanan Anda.'}
            actionLabel={isEn ? 'View order details' : 'Lihat detail pesanan'}
            onAction={() => navigateToBookingDetail(navigation, bookingId)}
          />
        </View>
      </View>
    );
  }

  const mootaAccountNumber = instructions?.account_number
    || selectedMethodMeta?.account_number
    || null;
  const uniqueAmount = instructions?.expected_transfer_total
    ? String(instructions.expected_transfer_total)
    : '';

  const footerAction = instructions
    ? (driver === 'moota'
      ? (mootaAccountNumber
        ? { label: isEn ? 'Copy account number' : 'Salin nomor rekening', onPress: () => copyValue(mootaAccountNumber) }
        : uniqueAmount
          ? { label: isEn ? 'Copy transfer amount' : 'Salin nominal transfer', onPress: () => copyValue(uniqueAmount) }
          : null)
      : instructions.deeplink_url
        ? { label: isEn ? 'Open e-wallet app' : 'Buka aplikasi e-wallet', onPress: () => openUrl(instructions.deeplink_url) }
        : instructions.checkout_url
          ? { label: isEn ? 'Open payment page' : 'Buka halaman pembayaran', onPress: () => openUrl(instructions.checkout_url) }
          : instructions.va_number
            ? { label: isEn ? 'Copy VA number' : 'Salin nomor VA', onPress: () => copyValue(instructions.va_number) }
            : null)
    : {
      label: isEn ? 'Continue to payment' : 'Lanjut ke pembayaran',
      onPress: handlePay,
      loading: paying,
      disabled: methods.length === 0,
    };

  const methodGroups = groupedMethods(methods, isEn);
  const methodTitle = driver === 'moota'
    ? (isEn ? 'Select destination account' : 'Pilih rekening tujuan')
    : (isEn ? 'Select payment method' : 'Pilih metode pembayaran');
  const methodSub = driver === 'moota'
    ? (isEn ? 'Select a bank account for your transfer' : 'Pilih rekening bank untuk transfer pembayaran Anda')
    : (isEn ? 'Select virtual account, QRIS, or e-wallet' : 'Pilih virtual account, QRIS, atau e-wallet');

  const footerCta = footerAction ? (
    <Button
      label={footerAction.label}
      icon={instructions
        ? (driver !== 'moota' && (instructions.deeplink_url || instructions.checkout_url)
          ? <ExternalLink size={18} color={colors.white} strokeWidth={2} />
          : <Copy size={18} color={colors.white} strokeWidth={2} />)
        : <CircleArrowRight size={18} color={colors.white} strokeWidth={2} />}
      onPress={footerAction.onPress}
      loading={footerAction.loading}
      disabled={footerAction.disabled}
    />
  ) : null;

  return (
    <View style={styles.container}>
      <ScreenHeader title={isEn ? 'Payment' : 'Pembayaran'} subtitle={bookingCode} onBack={() => navigation.goBack()} />

      <ScrollView
        contentContainerStyle={[styles.scroll, styles.scrollWithFooter]}
        refreshControl={<RefreshControl refreshing={refreshing} onRefresh={() => loadAll(true)} tintColor={colors.baytgo} />}
        showsVerticalScrollIndicator={false}
      >
        <StepIndicator step={currentStep} driver={driver} />
        <EnvironmentBanner environment={paymentEnvironment} />

        {needsPayment(booking) && booking?.payment_due_at ? (
          <View style={styles.deadlineWrap}>
            <PaymentDeadlineBanner dueAt={booking.payment_due_at} onExpire={() => loadAll(true)} />
          </View>
        ) : null}

        <Card style={styles.summaryCard} padding={spacing.xl}>
          <View style={styles.summaryTop}>
            <View>
              <Text style={styles.summaryLabel}>{isEn ? 'Total payment' : 'Total pembayaran'}</Text>
              <Text style={styles.summaryAmount}>{formatIdr(displayAmount)}</Text>
            </View>
            <View style={styles.summaryIcon}>
              <Wallet size={22} color={colors.baytgo} strokeWidth={2} />
            </View>
          </View>
          <View style={styles.summaryDivider} />
          <PaymentInfoRow icon={Receipt} label={isEn ? 'Order code' : 'Kode pesanan'} value={bookingCode || '—'} />
          {booking?.muthowif_profile?.user?.name ? (
            <PaymentInfoRow icon={User} label="Muthowif" value={booking.muthowif_profile.user.name} />
          ) : null}
          {booking?.starts_on ? (
            <PaymentInfoRow icon={Calendar} label={isEn ? 'Date' : 'Tanggal'} value={formatDateRange(booking.starts_on, booking.ends_on)} />
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
            <Text style={styles.pricingTitle}>{isEn ? 'Payment details' : 'Rincian pembayaran'}</Text>
            <CustomerPricingBreakdown pricing={pricing} />
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
              <Text style={styles.instructionsTitle}>{isEn ? 'Payment instructions' : 'Instruksi pembayaran'}</Text>
            </View>
            <Text style={styles.instructionsText}>
              {driver === 'moota'
                ? (isEn ? 'Transfer the exact unique amount to the account below. Do not round. Status updates automatically after funds are received.' : 'Transfer tepat sesuai nominal unik ke rekening di bawah. Jangan dibulatkan. Status berubah otomatis setelah dana masuk.')
                : (isEn ? 'Complete payment via the selected virtual account, QRIS, or e-wallet app.' : 'Selesaikan pembayaran lewat virtual account, QRIS, atau aplikasi e-wallet yang dipilih.')}
            </Text>

            {instructions.expected_transfer_total ? (
              <PressableScale onPress={() => copyValue(uniqueAmount)} haptic="light">
                <View style={styles.amountHighlight}>
                  <Text style={styles.amountHighlightLabel}>{isEn ? 'Transfer amount (tap to copy)' : 'Nominal transfer (ketuk untuk salin)'}</Text>
                  <Text style={styles.amountHighlightValue}>
                    {formatIdr(instructions.expected_transfer_total)}
                  </Text>
                </View>
              </PressableScale>
            ) : null}

            {selectedMethodMeta || instructions.bank_name ? (
              <View style={styles.selectedBankCard}>
                <Text style={styles.selectedBankTitle}>
                  {driver === 'moota' ? (isEn ? 'Destination account' : 'Rekening tujuan') : (isEn ? 'Method' : 'Metode')}
                </Text>
                <Text style={styles.selectedBankName}>
                  {instructions.bank_name || selectedMethodMeta?.bank_name || selectedMethodMeta?.label}
                </Text>
                {(instructions.account_holder || selectedMethodMeta?.account_holder) ? (
                  <Text style={styles.selectedBankLine}>
                    a.n. {instructions.account_holder || selectedMethodMeta?.account_holder}
                  </Text>
                ) : null}
                {instructions.va_bank ? (
                  <Text style={styles.selectedBankLine}>{instructions.va_bank}</Text>
                ) : null}
              </View>
            ) : null}

            <CopyableValue
              label={isEn ? 'Account number' : 'Nomor rekening'}
              value={mootaAccountNumber}
              onCopy={copyValue}
            />
            <CopyableValue label={isEn ? 'VA number' : 'Nomor VA'} value={instructions.va_number} onCopy={copyValue} />
            {instructions.bill_key && instructions.biller_code ? (
              <View style={styles.billRow}>
                <View style={styles.billItem}>
                  <Text style={styles.selectedBankTitle}>Bill key</Text>
                  <Text style={styles.selectedBankName}>{instructions.bill_key}</Text>
                </View>
                <View style={styles.billItem}>
                  <Text style={styles.selectedBankTitle}>{isEn ? 'Biller code' : 'Kode biller'}</Text>
                  <Text style={styles.selectedBankName}>{instructions.biller_code}</Text>
                </View>
              </View>
            ) : null}

            {driver !== 'moota' && instructions.deeplink_url && instructions.checkout_url ? (
              <View style={styles.secondaryLink}>
                <Button
                  label={isEn ? 'Open payment page' : 'Buka halaman pembayaran'}
                  variant="secondary"
                  icon={<ExternalLink size={16} color={colors.baytgo} strokeWidth={2} />}
                  onPress={() => openUrl(instructions.checkout_url)}
                />
              </View>
            ) : null}

            {instructions.expiry_time ? (
              <View style={styles.expiryRow}>
                <AlarmClock size={16} color={colors.textMuted} strokeWidth={2} />
                <Text style={styles.expiryText}>{isEn ? 'Deadline:' : 'Batas waktu:'} {instructions.expiry_time}</Text>
              </View>
            ) : null}

            <PaymentHowToSteps driver={driver} />

            <View style={styles.pollHint}>
              <ActivityIndicator color={colors.baytgo} size="small" />
              <Text style={styles.waitHint}>{isEn ? 'Waiting for payment verification. Status updates automatically.' : 'Menunggu verifikasi pembayaran. Status diperbarui otomatis.'}</Text>
            </View>
          </Card>
        ) : (
          <>
            <Text style={styles.sectionTitle}>{methodTitle}</Text>
            <Text style={styles.sectionSub}>{methodSub}</Text>

            {methods.length === 0 ? (
              <EmptyState
                variant="package"
                title={isEn ? 'Methods not available' : 'Metode belum tersedia'}
                description={isEn ? 'Payment methods are not yet available for this order.' : 'Metode pembayaran belum tersedia untuk pesanan ini.'}
              />
            ) : (
              methodGroups.map((group) => (
                <View key={group.key}>
                  {methodGroups.length > 1 ? (
                    <Text style={styles.groupTitle}>{group.title}</Text>
                  ) : null}
                  {group.items.map((item) => (
                    <MethodCard
                      key={item.id}
                      item={item}
                      environment={paymentEnvironment}
                      selected={selectedMethod === item.id}
                      onPress={() => { setSelectedMethod(item.id); setSelectedMethodMeta(item); }}
                    />
                  ))}
                </View>
              ))
            )}
          </>
        )}
      </ScrollView>

      <StickyFooter priceLabel={isEn ? 'Total transfer' : 'Total transfer'} priceValue={formatIdr(displayAmount)}>
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
  deadlineWrap: { marginBottom: spacing.lg },
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
  errorBox: {
    flexDirection: 'row', alignItems: 'flex-start', gap: spacing.md,
    marginBottom: spacing.lg, borderColor: '#FECACA', backgroundColor: colors.errorLight,
  },
  errorText: { flex: 1, ...typography.caption, color: colors.error, lineHeight: 18 },
  sectionTitle: { ...typography.subtitle, fontSize: 16, color: colors.baytgo, marginBottom: spacing.xs },
  sectionSub: { ...typography.small, color: colors.textSecondary, marginBottom: spacing.lg },
  groupTitle: {
    ...typography.label, color: colors.textSecondary, textTransform: 'uppercase',
    marginBottom: spacing.sm, marginTop: spacing.xs,
  },
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
  billRow: { marginTop: spacing.lg, flexDirection: 'row', gap: spacing.md },
  billItem: {
    flex: 1, backgroundColor: colors.background, borderRadius: radius.sm,
    padding: spacing.lg, borderWidth: 1, borderColor: colors.border,
  },
  secondaryLink: { marginTop: spacing.lg },
  expiryRow: { flexDirection: 'row', alignItems: 'center', gap: spacing.sm, marginTop: spacing.md },
  expiryText: { ...typography.caption, color: colors.textSecondary },
  pollHint: { flexDirection: 'row', alignItems: 'center', justifyContent: 'center', gap: spacing.sm, marginTop: spacing.lg },
  waitHint: { ...typography.small, color: colors.textSecondary, flex: 1 },
  paidWrap: { flex: 1, alignItems: 'center', justifyContent: 'center', padding: spacing['3xl'] },
  paidIcon: { marginBottom: spacing.lg },
  paidTitle: { ...typography.title, color: colors.baytgo },
  paidSub: { marginTop: spacing.sm, ...typography.caption, color: colors.textSecondary, textAlign: 'center', marginBottom: spacing['2xl'] },
});
