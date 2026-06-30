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
} from 'react-native';
import { LinearGradient } from 'expo-linear-gradient';
import { Ionicons } from '@expo/vector-icons';
import ScreenHeader from '../components/ScreenHeader';
import { fetchBooking, fetchPaymentMethods, initiatePayment } from '../api/bookings';
import { useAuth } from '../context/AuthContext';
import { navigateToBookingDetail } from '../navigation/rootNavigation';
import { colors } from '../theme/colors';
import { formatIdr } from '../utils/format';

function MethodCard({ item, selected, onPress }) {
  return (
    <TouchableOpacity
      style={[styles.methodCard, selected && styles.methodCardActive]}
      onPress={onPress}
      activeOpacity={0.9}
    >
      <Ionicons name="business-outline" size={22} color={colors.baytgo} />
      <View style={styles.methodBody}>
        <Text style={styles.methodLabel}>{item.label}</Text>
        <Text style={styles.methodHint}>Transfer via Moota</Text>
      </View>
      <Ionicons name={selected ? 'radio-button-on' : 'radio-button-off'} size={20} color={colors.baytgo} />
    </TouchableOpacity>
  );
}

export default function BookingPaymentScreen({ navigation, route }) {
  const { token } = useAuth();
  const { bookingId, bookingCode } = route.params;

  const [loading, setLoading] = useState(true);
  const [paying, setPaying] = useState(false);
  const [error, setError] = useState('');
  const [driver, setDriver] = useState('moota');
  const [amount, setAmount] = useState(0);
  const [methods, setMethods] = useState([]);
  const [selectedMethod, setSelectedMethod] = useState('');
  const [instructions, setInstructions] = useState(null);
  const [booking, setBooking] = useState(null);
  const pollRef = useRef(null);

  const loadMethods = useCallback(async () => {
    setLoading(true);
    setError('');
    try {
      const data = await fetchPaymentMethods(token, bookingId);
      if (data.driver !== 'moota') {
        setError('Aplikasi mobile saat ini hanya mendukung pembayaran Moota.');
        return;
      }
      setDriver(data.driver);
      setAmount(data.amount || 0);
      const meta = data.methods_meta || (data.methods || []).map((id) => ({ id, label: id }));
      setMethods(meta);
      if (meta.length === 1) setSelectedMethod(meta[0].id);
    } catch (err) {
      setError(err.message || 'Gagal memuat metode pembayaran');
    } finally {
      setLoading(false);
    }
  }, [token, bookingId]);

  const refreshBooking = useCallback(async () => {
    try {
      const data = await fetchBooking(token, bookingId);
      setBooking(data);
      if (data.payment_status === 'paid') {
        clearInterval(pollRef.current);
        Alert.alert('Pembayaran berhasil', 'Pesanan Anda sudah lunas.', [
          {
            text: 'Lihat pesanan',
            onPress: () => navigateToBookingDetail(navigation, bookingId),
          },
        ]);
      }
    } catch {
      // ignore poll errors
    }
  }, [token, bookingId, navigation]);

  useEffect(() => {
    loadMethods();
    refreshBooking();
    pollRef.current = setInterval(refreshBooking, 10000);
    return () => clearInterval(pollRef.current);
  }, [loadMethods, refreshBooking]);

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

  if (loading) {
    return (
      <View style={styles.container}>
        <ScreenHeader title="Pembayaran" onBack={() => navigation.goBack()} />
        <ActivityIndicator color={colors.baytgo} style={styles.loader} />
      </View>
    );
  }

  return (
    <View style={styles.container}>
      <ScreenHeader title="Pembayaran" onBack={() => navigation.goBack()} />

      <ScrollView contentContainerStyle={styles.scroll}>
        <View style={styles.summaryCard}>
          <Text style={styles.summaryLabel}>Kode pesanan</Text>
          <Text style={styles.summaryCode}>{bookingCode}</Text>
          <Text style={styles.summaryAmount}>{formatIdr(instructions?.gross_amount || amount)}</Text>
          {booking?.status ? (
            <Text style={styles.summaryStatus}>Status: {booking.status} · {booking.payment_status}</Text>
          ) : null}
        </View>

        {error ? <Text style={styles.error}>{error}</Text> : null}

        {instructions ? (
          <View style={styles.instructionsCard}>
            <Text style={styles.instructionsTitle}>Instruksi transfer Moota</Text>
            <Text style={styles.instructionsText}>
              Transfer sesuai nominal unik ke rekening yang ditampilkan di halaman Moota.
            </Text>
            {instructions.expected_transfer_total ? (
              <Text style={styles.instructionsAmount}>
                Nominal transfer: {formatIdr(instructions.expected_transfer_total)}
              </Text>
            ) : null}
            {instructions.expiry_time ? (
              <Text style={styles.instructionsExpiry}>Batas waktu: {instructions.expiry_time}</Text>
            ) : null}

            <TouchableOpacity style={styles.checkoutBtn} onPress={openCheckout} activeOpacity={0.9}>
              <LinearGradient colors={[colors.baytgo, colors.baytgoDark]} style={styles.checkoutGradient}>
                <Ionicons name="open-outline" size={18} color={colors.white} />
                <Text style={styles.checkoutText}>Buka halaman pembayaran Moota</Text>
              </LinearGradient>
            </TouchableOpacity>

            <Text style={styles.waitHint}>
              Status akan diperbarui otomatis setelah transfer terverifikasi Moota.
            </Text>
          </View>
        ) : (
          <>
            <Text style={styles.sectionTitle}>Pilih rekening tujuan</Text>
            {methods.map((item) => (
              <MethodCard
                key={item.id}
                item={item}
                selected={selectedMethod === item.id}
                onPress={() => setSelectedMethod(item.id)}
              />
            ))}

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
                  <Text style={styles.checkoutText}>Lanjut bayar</Text>
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
  scroll: { padding: 16, paddingBottom: 32 },
  loader: { marginTop: 40 },
  summaryCard: {
    backgroundColor: colors.white,
    borderRadius: 20,
    padding: 20,
    borderWidth: 1,
    borderColor: colors.slate100,
    marginBottom: 16,
  },
  summaryLabel: { fontSize: 12, fontWeight: '700', color: colors.slate500, textTransform: 'uppercase' },
  summaryCode: { marginTop: 4, fontSize: 18, fontWeight: '900', color: colors.baytgo },
  summaryAmount: { marginTop: 10, fontSize: 24, fontWeight: '900', color: colors.slate900 },
  summaryStatus: { marginTop: 8, fontSize: 12, fontWeight: '600', color: colors.slate500 },
  error: {
    backgroundColor: '#FEF2F2',
    color: '#B91C1C',
    padding: 12,
    borderRadius: 12,
    fontSize: 13,
    fontWeight: '600',
    marginBottom: 12,
  },
  sectionTitle: { fontSize: 16, fontWeight: '900', color: colors.baytgo, marginBottom: 12 },
  methodCard: {
    flexDirection: 'row',
    alignItems: 'center',
    gap: 12,
    backgroundColor: colors.white,
    borderRadius: 16,
    padding: 14,
    marginBottom: 10,
    borderWidth: 1,
    borderColor: colors.slate100,
  },
  methodCardActive: { borderColor: colors.baytgo, backgroundColor: colors.emerald50 },
  methodBody: { flex: 1 },
  methodLabel: { fontSize: 14, fontWeight: '800', color: colors.slate900 },
  methodHint: { marginTop: 2, fontSize: 12, fontWeight: '600', color: colors.slate500 },
  instructionsCard: {
    backgroundColor: colors.white,
    borderRadius: 20,
    padding: 20,
    borderWidth: 1,
    borderColor: colors.slate100,
  },
  instructionsTitle: { fontSize: 17, fontWeight: '900', color: colors.baytgo },
  instructionsText: { marginTop: 8, fontSize: 14, lineHeight: 21, color: colors.slate600, fontWeight: '500' },
  instructionsAmount: { marginTop: 14, fontSize: 16, fontWeight: '900', color: colors.baytgo },
  instructionsExpiry: { marginTop: 6, fontSize: 13, fontWeight: '600', color: colors.slate500 },
  checkoutBtn: { marginTop: 20, borderRadius: 16, overflow: 'hidden' },
  payBtn: { marginTop: 20, borderRadius: 16, overflow: 'hidden' },
  checkoutGradient: {
    flexDirection: 'row',
    alignItems: 'center',
    justifyContent: 'center',
    gap: 8,
    paddingVertical: 16,
  },
  checkoutText: { color: colors.white, fontSize: 15, fontWeight: '800' },
  waitHint: { marginTop: 14, fontSize: 12, lineHeight: 18, color: colors.slate500, fontWeight: '600', textAlign: 'center' },
});
