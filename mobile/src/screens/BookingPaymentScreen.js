import React, { useCallback, useEffect, useRef, useState } from 'react';
import {
  View,
  Text,
  StyleSheet,
  ScrollView,
  TouchableOpacity,
  ActivityIndicator,
  Linking,
  Alert,
  RefreshControl,
} from 'react-native';
import { LinearGradient } from 'expo-linear-gradient';
import { Ionicons } from '@expo/vector-icons';
import ScreenHeader from '../components/ScreenHeader';
import { fetchBooking, fetchPaymentMethods, initiatePayment } from '../api/bookings';
import { useAuth } from '../context/AuthContext';
import { navigateToBookingDetail } from '../navigation/rootNavigation';
import { colors } from '../theme/colors';
import { formatIdr } from '../utils/format';
import {
  bookingStatusMeta,
  paymentStatusMeta,
  formatDateRange,
  isAwaitingMuthowifConfirmation,
} from '../utils/bookingLabels';

const BANK_ICONS = {
  bca: 'card-outline',
  bni: 'card-outline',
  bri: 'card-outline',
  permata: 'card-outline',
  mandiri: 'card-outline',
  moota: 'business-outline',
};

function guessMethodIcon(idOrName) {
  const key = String(idOrName || '').toLowerCase();
  if (key.includes('bca')) return BANK_ICONS.bca;
  if (key.includes('bni')) return BANK_ICONS.bni;
  if (key.includes('bri')) return BANK_ICONS.bri;
  if (key.includes('permata')) return BANK_ICONS.permata;
  if (key.includes('mandiri')) return BANK_ICONS.mandiri;
  return BANK_ICONS.moota;
}

function StepIndicator({ step }) {
  return (
    <View style={styles.steps}>
      <View style={styles.stepItem}>
        <View style={[styles.stepDot, step >= 1 && styles.stepDotActive]}>
          <Text style={[styles.stepNum, step >= 1 && styles.stepNumActive]}>1</Text>
        </View>
        <Text style={[styles.stepLabel, step >= 1 && styles.stepLabelActive]}>Pilih rekening</Text>
      </View>
      <View style={[styles.stepLine, step >= 2 && styles.stepLineActive]} />
      <View style={styles.stepItem}>
        <View style={[styles.stepDot, step >= 2 && styles.stepDotActive]}>
          <Text style={[styles.stepNum, step >= 2 && styles.stepNumActive]}>2</Text>
        </View>
        <Text style={[styles.stepLabel, step >= 2 && styles.stepLabelActive]}>Transfer</Text>
      </View>
    </View>
  );
}

function EnvironmentBanner({ environment }) {
  if (!environment?.label) return null;

  const isSandbox = environment.is_sandbox;
  return (
    <View style={[styles.envBanner, isSandbox ? styles.envSandbox : styles.envProduction]}>
      <Ionicons
        name={isSandbox ? 'flask-outline' : 'shield-checkmark-outline'}
        size={18}
        color={isSandbox ? '#B45309' : '#166534'}
      />
      <View style={styles.envCopy}>
        <Text style={[styles.envTitle, isSandbox ? styles.envTitleSandbox : styles.envTitleProduction]}>
          {environment.label}
        </Text>
        {environment.hint ? (
          <Text style={[styles.envHint, isSandbox ? styles.envHintSandbox : styles.envHintProduction]}>
            {environment.hint}
          </Text>
        ) : null}
      </View>
    </View>
  );
}

function MethodCard({ item, selected, environment, onPress }) {
  const displayLabel = item.bank_name || item.label;
  const isSandbox = environment?.is_sandbox;

  return (
    <TouchableOpacity
      style={[styles.methodCard, selected && styles.methodCardActive]}
      onPress={onPress}
      activeOpacity={0.9}
    >
      <View style={[styles.methodIcon, selected && styles.methodIconActive]}>
        <Ionicons name={guessMethodIcon(item.id || item.bank_name)} size={20} color={selected ? colors.white : colors.baytgo} />
      </View>
      <View style={styles.methodBody}>
        <View style={styles.methodTitleRow}>
          <Text style={styles.methodLabel}>{displayLabel}</Text>
          {isSandbox !== undefined ? (
            <View style={[styles.envChip, isSandbox ? styles.envChipSandbox : styles.envChipProduction]}>
              <Text style={[styles.envChipText, isSandbox ? styles.envChipTextSandbox : styles.envChipTextProduction]}>
                {isSandbox ? 'Sandbox' : 'Live'}
              </Text>
            </View>
          ) : null}
        </View>
        {item.account_holder ? (
          <Text style={styles.methodDetail}>a.n. {item.account_holder}</Text>
        ) : null}
        {item.account_number ? (
          <Text style={styles.methodAccount}>No. rekening {item.account_number}</Text>
        ) : (
          <Text style={styles.methodHint}>Transfer bank via Moota</Text>
        )}
        {item.bank_account_ref ? (
          <Text style={styles.methodRef}>Ref. Moota: {item.bank_account_ref}</Text>
        ) : null}
      </View>
      <Ionicons name={selected ? 'radio-button-on' : 'radio-button-off'} size={22} color={colors.baytgo} />
    </TouchableOpacity>
  );
}

function InfoRow({ icon, label, value }) {
  return (
    <View style={styles.infoRow}>
      <Ionicons name={icon} size={16} color={colors.slate500} />
      <Text style={styles.infoLabel}>{label}</Text>
      <Text style={styles.infoValue}>{value}</Text>
    </View>
  );
}

export default function BookingPaymentScreen({ navigation, route }) {
  const { token } = useAuth();
  const { bookingId, bookingCode } = route.params;

  const [loading, setLoading] = useState(true);
  const [refreshing, setRefreshing] = useState(false);
  const [paying, setPaying] = useState(false);
  const [error, setError] = useState('');
  const [amount, setAmount] = useState(0);
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
      setAmount(data.amount || 0);
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
  }, [token, bookingId]);

  const refreshBooking = useCallback(async (silent = false) => {
    try {
      const data = await fetchBooking(token, bookingId);
      setBooking(data);
      if (data.payment_status === 'paid') {
        clearInterval(pollRef.current);
        if (!silent) {
          Alert.alert('Pembayaran berhasil', 'Pesanan Anda sudah lunas.', [
            {
              text: 'Lihat pesanan',
              onPress: () => navigateToBookingDetail(navigation, bookingId),
            },
          ]);
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
        setSelectedMethodMeta(
          data.method_meta || methods.find((m) => m.id === selectedMethod) || null,
        );
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
    Linking.openURL(url).catch(() => {
      Alert.alert('Gagal membuka', url);
    });
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
        <ActivityIndicator color={colors.baytgo} style={styles.loader} />
      </View>
    );
  }

  if (isPaid) {
    return (
      <View style={styles.container}>
        <ScreenHeader title="Pembayaran" onBack={() => navigation.goBack()} />
        <View style={styles.paidWrap}>
          <View style={styles.paidIcon}>
            <Ionicons name="checkmark-circle" size={56} color={colors.emerald600} />
          </View>
          <Text style={styles.paidTitle}>Pembayaran lunas</Text>
          <Text style={styles.paidSub}>Pesanan {bookingCode} sudah dibayar.</Text>
          <TouchableOpacity
            style={styles.paidBtn}
            onPress={() => navigateToBookingDetail(navigation, bookingId)}
            activeOpacity={0.9}
          >
            <LinearGradient colors={[colors.baytgo, colors.baytgoDark]} style={styles.paidGradient}>
              <Text style={styles.paidBtnText}>Lihat detail pesanan</Text>
            </LinearGradient>
          </TouchableOpacity>
        </View>
      </View>
    );
  }

  return (
    <View style={styles.container}>
      <ScreenHeader title="Pembayaran" subtitle={bookingCode} onBack={() => navigation.goBack()} />

      <ScrollView
        contentContainerStyle={styles.scroll}
        refreshControl={
          <RefreshControl refreshing={refreshing} onRefresh={() => loadAll(true)} tintColor={colors.baytgo} />
        }
        showsVerticalScrollIndicator={false}
      >
        <StepIndicator step={currentStep} />

        <EnvironmentBanner environment={paymentEnvironment} />

        <View style={styles.summaryCard}>
          <View style={styles.summaryTop}>
            <View>
              <Text style={styles.summaryLabel}>Total pembayaran</Text>
              <Text style={styles.summaryAmount}>{formatIdr(displayAmount)}</Text>
            </View>
            <View style={styles.summaryIcon}>
              <Ionicons name="wallet" size={22} color={colors.baytgo} />
            </View>
          </View>

          <View style={styles.summaryDivider} />

          <InfoRow icon="receipt-outline" label="Kode pesanan" value={bookingCode || '—'} />
          {booking?.muthowif_profile?.user?.name ? (
            <InfoRow icon="person-outline" label="Muthowif" value={booking.muthowif_profile.user.name} />
          ) : null}
          {booking?.starts_on ? (
            <InfoRow
              icon="calendar-outline"
              label="Tanggal"
              value={formatDateRange(booking.starts_on, booking.ends_on)}
            />
          ) : null}
          {bookingMeta && paymentMeta ? (
            <View style={styles.badgeRow}>
              <View style={[styles.badge, { backgroundColor: bookingMeta.color + '18' }]}>
                <Text style={[styles.badgeText, { color: bookingMeta.color }]}>{bookingMeta.label}</Text>
              </View>
              <View style={[styles.badge, { backgroundColor: paymentMeta.color + '18' }]}>
                <Text style={[styles.badgeText, { color: paymentMeta.color }]}>{paymentMeta.label}</Text>
              </View>
            </View>
          ) : null}
        </View>

        {awaitingMuthowif ? (
          <View style={styles.waitBanner}>
            <Ionicons name="time-outline" size={18} color="#7C3AED" />
            <Text style={styles.waitBannerText}>
              Muthowif belum mengonfirmasi pesanan. Anda tetap bisa melanjutkan pembayaran.
            </Text>
          </View>
        ) : null}

        {error ? (
          <View style={styles.errorBox}>
            <Ionicons name="alert-circle-outline" size={18} color="#B91C1C" />
            <Text style={styles.errorText}>{error}</Text>
          </View>
        ) : null}

        {instructions ? (
          <View style={styles.instructionsCard}>
            <View style={styles.instructionsHead}>
              <Ionicons name="swap-horizontal" size={20} color={colors.baytgo} />
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
                <Ionicons name="alarm-outline" size={16} color={colors.slate500} />
                <Text style={styles.expiryText}>Batas waktu: {instructions.expiry_time}</Text>
              </View>
            ) : null}

            <TouchableOpacity style={styles.checkoutBtn} onPress={openCheckout} activeOpacity={0.9}>
              <LinearGradient colors={[colors.baytgo, colors.baytgoDark]} style={styles.checkoutGradient}>
                <Ionicons name="open-outline" size={18} color={colors.white} />
                <Text style={styles.checkoutText}>Buka halaman pembayaran Moota</Text>
              </LinearGradient>
            </TouchableOpacity>

            <View style={styles.pollHint}>
              <ActivityIndicator color={colors.baytgo} size="small" />
              <Text style={styles.waitHint}>
                Menunggu verifikasi transfer. Status diperbarui otomatis.
              </Text>
            </View>
          </View>
        ) : (
          <>
            <Text style={styles.sectionTitle}>Pilih rekening tujuan</Text>
            <Text style={styles.sectionSub}>Pilih rekening bank untuk transfer pembayaran Anda</Text>

            {methods.length === 0 ? (
              <View style={styles.emptyMethods}>
                <Ionicons name="business-outline" size={28} color={colors.slate400} />
                <Text style={styles.emptyMethodsText}>Metode pembayaran belum tersedia</Text>
              </View>
            ) : (
              methods.map((item) => (
                <MethodCard
                  key={item.id}
                  item={item}
                  environment={paymentEnvironment}
                  selected={selectedMethod === item.id}
                  onPress={() => {
                    setSelectedMethod(item.id);
                    setSelectedMethodMeta(item);
                  }}
                />
              ))
            )}

            <TouchableOpacity
              style={styles.payBtn}
              onPress={handlePay}
              disabled={paying || methods.length === 0}
              activeOpacity={0.9}
            >
              <LinearGradient colors={[colors.baytgo, colors.baytgoDark]} style={styles.checkoutGradient}>
                {paying ? (
                  <ActivityIndicator color={colors.white} />
                ) : (
                  <>
                    <Ionicons name="arrow-forward-circle-outline" size={18} color={colors.white} />
                    <Text style={styles.checkoutText}>Lanjut ke pembayaran</Text>
                  </>
                )}
              </LinearGradient>
            </TouchableOpacity>
          </>
        )}
      </ScrollView>
    </View>
  );
}

const styles = StyleSheet.create({
  container: { flex: 1, backgroundColor: colors.canvas },
  scroll: { padding: 20, paddingBottom: 40 },
  loader: { marginTop: 40 },
  steps: {
    flexDirection: 'row',
    alignItems: 'center',
    justifyContent: 'center',
    marginBottom: 18,
    paddingHorizontal: 8,
  },
  stepItem: { alignItems: 'center', gap: 6 },
  stepDot: {
    width: 32,
    height: 32,
    borderRadius: 16,
    backgroundColor: colors.white,
    borderWidth: 2,
    borderColor: colors.slate200,
    alignItems: 'center',
    justifyContent: 'center',
  },
  stepDotActive: { backgroundColor: colors.baytgo, borderColor: colors.baytgo },
  stepNum: { fontSize: 13, fontWeight: '900', color: colors.slate400 },
  stepNumActive: { color: colors.white },
  stepLabel: { fontSize: 11, fontWeight: '700', color: colors.slate400 },
  stepLabelActive: { color: colors.baytgo },
  stepLine: { width: 48, height: 2, backgroundColor: colors.slate200, marginHorizontal: 8, marginBottom: 20 },
  stepLineActive: { backgroundColor: colors.baytgo },
  summaryCard: {
    backgroundColor: colors.white,
    borderRadius: 20,
    padding: 18,
    marginBottom: 14,
    borderWidth: 1,
    borderColor: 'rgba(26,61,52,0.08)',
    shadowColor: '#0F2E28',
    shadowOffset: { width: 0, height: 4 },
    shadowOpacity: 0.05,
    shadowRadius: 12,
    elevation: 2,
  },
  summaryTop: { flexDirection: 'row', justifyContent: 'space-between', alignItems: 'flex-start' },
  summaryLabel: { fontSize: 12, fontWeight: '700', color: colors.slate500, textTransform: 'uppercase' },
  summaryAmount: { marginTop: 4, fontSize: 28, fontWeight: '900', color: colors.baytgo },
  summaryIcon: {
    width: 44,
    height: 44,
    borderRadius: 14,
    backgroundColor: colors.baytgoLight,
    alignItems: 'center',
    justifyContent: 'center',
  },
  summaryDivider: { height: 1, backgroundColor: colors.slate100, marginVertical: 14 },
  infoRow: { flexDirection: 'row', alignItems: 'center', gap: 8, paddingVertical: 6 },
  infoLabel: { flex: 1, fontSize: 13, fontWeight: '600', color: colors.slate500 },
  infoValue: { fontSize: 13, fontWeight: '800', color: colors.slate900 },
  badgeRow: { flexDirection: 'row', flexWrap: 'wrap', gap: 8, marginTop: 10 },
  badge: { borderRadius: 999, paddingHorizontal: 10, paddingVertical: 5 },
  badgeText: { fontSize: 11, fontWeight: '800' },
  waitBanner: {
    flexDirection: 'row',
    alignItems: 'flex-start',
    gap: 10,
    backgroundColor: '#F5F3FF',
    borderRadius: 14,
    padding: 14,
    marginBottom: 14,
    borderWidth: 1,
    borderColor: '#DDD6FE',
  },
  waitBannerText: { flex: 1, fontSize: 13, lineHeight: 19, fontWeight: '600', color: '#5B21B6' },
  errorBox: {
    flexDirection: 'row',
    alignItems: 'flex-start',
    gap: 10,
    backgroundColor: '#FEF2F2',
    padding: 14,
    borderRadius: 14,
    marginBottom: 14,
    borderWidth: 1,
    borderColor: '#FECACA',
  },
  errorText: { flex: 1, fontSize: 13, fontWeight: '600', color: '#B91C1C', lineHeight: 18 },
  sectionTitle: { fontSize: 16, fontWeight: '900', color: colors.baytgo, marginBottom: 4 },
  sectionSub: { fontSize: 12, fontWeight: '600', color: colors.slate500, marginBottom: 14 },
  methodCard: {
    flexDirection: 'row',
    alignItems: 'center',
    gap: 12,
    backgroundColor: colors.white,
    borderRadius: 16,
    padding: 14,
    marginBottom: 10,
    borderWidth: 1.5,
    borderColor: colors.slate100,
  },
  methodCardActive: { borderColor: colors.baytgo, backgroundColor: colors.baytgoLight },
  methodIcon: {
    width: 44,
    height: 44,
    borderRadius: 12,
    backgroundColor: colors.baytgoLight,
    alignItems: 'center',
    justifyContent: 'center',
  },
  methodIconActive: { backgroundColor: colors.baytgo },
  methodBody: { flex: 1 },
  methodTitleRow: { flexDirection: 'row', alignItems: 'center', gap: 8, flexWrap: 'wrap' },
  methodLabel: { fontSize: 14, fontWeight: '800', color: colors.slate900 },
  methodDetail: { marginTop: 4, fontSize: 12, fontWeight: '600', color: colors.slate600 },
  methodAccount: { marginTop: 2, fontSize: 13, fontWeight: '800', color: colors.baytgo },
  methodHint: { marginTop: 4, fontSize: 12, fontWeight: '600', color: colors.slate500 },
  methodRef: { marginTop: 4, fontSize: 10, fontWeight: '700', color: colors.slate400 },
  envBanner: {
    flexDirection: 'row',
    alignItems: 'flex-start',
    gap: 10,
    borderRadius: 14,
    padding: 14,
    marginBottom: 14,
    borderWidth: 1,
  },
  envSandbox: { backgroundColor: '#FFFBEB', borderColor: '#FDE68A' },
  envProduction: { backgroundColor: '#ECFDF5', borderColor: '#A7F3D0' },
  envCopy: { flex: 1 },
  envTitle: { fontSize: 13, fontWeight: '900' },
  envTitleSandbox: { color: '#B45309' },
  envTitleProduction: { color: '#166534' },
  envHint: { marginTop: 4, fontSize: 12, lineHeight: 17, fontWeight: '600' },
  envHintSandbox: { color: '#92400E' },
  envHintProduction: { color: '#166534' },
  envChip: {
    paddingHorizontal: 8,
    paddingVertical: 3,
    borderRadius: 999,
  },
  envChipSandbox: { backgroundColor: '#FEF3C7' },
  envChipProduction: { backgroundColor: '#DCFCE7' },
  envChipText: { fontSize: 10, fontWeight: '800' },
  envChipTextSandbox: { color: '#B45309' },
  envChipTextProduction: { color: '#166534' },
  emptyMethods: {
    alignItems: 'center',
    paddingVertical: 32,
    backgroundColor: colors.white,
    borderRadius: 16,
    marginBottom: 14,
    borderWidth: 1,
    borderColor: colors.slate100,
  },
  emptyMethodsText: { marginTop: 10, fontSize: 13, fontWeight: '600', color: colors.slate500 },
  instructionsCard: {
    backgroundColor: colors.white,
    borderRadius: 20,
    padding: 18,
    borderWidth: 1,
    borderColor: 'rgba(26,61,52,0.08)',
  },
  instructionsHead: { flexDirection: 'row', alignItems: 'center', gap: 8, marginBottom: 10 },
  instructionsTitle: { fontSize: 16, fontWeight: '900', color: colors.baytgo },
  instructionsText: { fontSize: 14, lineHeight: 21, color: colors.slate600, fontWeight: '500' },
  amountHighlight: {
    marginTop: 16,
    backgroundColor: colors.baytgoLight,
    borderRadius: 14,
    padding: 14,
    alignItems: 'center',
  },
  amountHighlightLabel: { fontSize: 12, fontWeight: '700', color: colors.slate600 },
  amountHighlightValue: { marginTop: 4, fontSize: 22, fontWeight: '900', color: colors.baytgo },
  selectedBankCard: {
    marginTop: 14,
    backgroundColor: colors.canvas,
    borderRadius: 14,
    padding: 14,
    borderWidth: 1,
    borderColor: colors.slate100,
  },
  selectedBankTitle: { fontSize: 11, fontWeight: '800', color: colors.slate500, textTransform: 'uppercase' },
  selectedBankName: { marginTop: 6, fontSize: 16, fontWeight: '900', color: colors.baytgo },
  selectedBankLine: { marginTop: 4, fontSize: 13, fontWeight: '700', color: colors.slate700 },
  expiryRow: { flexDirection: 'row', alignItems: 'center', gap: 6, marginTop: 12 },
  expiryText: { fontSize: 13, fontWeight: '600', color: colors.slate500 },
  checkoutBtn: { marginTop: 18, borderRadius: 14, overflow: 'hidden' },
  payBtn: { marginTop: 8, borderRadius: 14, overflow: 'hidden' },
  checkoutGradient: {
    flexDirection: 'row',
    alignItems: 'center',
    justifyContent: 'center',
    gap: 8,
    paddingVertical: 16,
  },
  checkoutText: { color: colors.white, fontSize: 15, fontWeight: '800' },
  pollHint: {
    flexDirection: 'row',
    alignItems: 'center',
    justifyContent: 'center',
    gap: 8,
    marginTop: 16,
  },
  waitHint: { fontSize: 12, fontWeight: '600', color: colors.slate500, flex: 1 },
  paidWrap: { flex: 1, alignItems: 'center', justifyContent: 'center', padding: 32 },
  paidIcon: { marginBottom: 16 },
  paidTitle: { fontSize: 22, fontWeight: '900', color: colors.baytgo },
  paidSub: { marginTop: 8, fontSize: 14, fontWeight: '600', color: colors.slate500, textAlign: 'center' },
  paidBtn: { marginTop: 24, width: '100%', borderRadius: 14, overflow: 'hidden' },
  paidGradient: { paddingVertical: 16, alignItems: 'center' },
  paidBtnText: { color: colors.white, fontSize: 15, fontWeight: '800' },
});
