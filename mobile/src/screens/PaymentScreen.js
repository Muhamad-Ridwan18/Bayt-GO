import React, { useState, useCallback } from 'react';
import {
  StyleSheet, Text, View, TouchableOpacity, ScrollView,
  ActivityIndicator, Alert, StatusBar, Clipboard, Image
} from 'react-native';
import { SafeAreaView, useSafeAreaInsets } from 'react-native-safe-area-context';
import { Ionicons } from '@expo/vector-icons';
import { LinearGradient } from 'expo-linear-gradient';
import { apiClient } from '../api/client';
import SwipeableScreen from '../components/SwipeableScreen';

const formatIDR = (n) => (n ?? 0).toString().replace(/\B(?=(\d{3})+(?!\d))/g, '.');

const METHOD_CONFIG = {
  va_bca:          { label: 'BCA Virtual Account',     icon: '🏦', color: '#005BAA', desc: 'Transfer via ATM, m-Banking, atau iBanking BCA' },
  va_bni:          { label: 'BNI Virtual Account',     icon: '🏦', color: '#F15A22', desc: 'Transfer via ATM, m-Banking, atau iBanking BNI' },
  va_bri:          { label: 'BRI Virtual Account',     icon: '🏦', color: '#00529C', desc: 'Transfer via ATM, m-Banking, atau iBanking BRI' },
  va_permata:      { label: 'Permata Virtual Account', icon: '🏦', color: '#D2232A', desc: 'Transfer via ATM atau m-Banking Permata' },
  va_mandiri_bill: { label: 'Mandiri Bill',            icon: '🏦', color: '#003D7C', desc: 'Bayar via Mandiri Online / ATM Mandiri' },
  qris:            { label: 'QRIS',                    icon: '📱', color: '#00A859', desc: 'Scan QR dengan aplikasi e-wallet manapun' },
  gopay:           { label: 'GoPay',                   icon: '💚', color: '#00AED6', desc: 'Bayar langsung via aplikasi Gojek' },
  shopeepay:       { label: 'ShopeePay',               icon: '🧡', color: '#EE4D2D', desc: 'Bayar langsung via aplikasi Shopee' },
};

export default function PaymentScreen({ route, navigation, user }) {
  const { booking_id, booking_code, amount: initialAmount } = route.params || {};
  const insets = useSafeAreaInsets();

  const [step, setStep] = useState('select_method'); // 'select_method' | 'payment_instructions'
  const [loading, setLoading] = useState(false);
  const [methods, setMethods] = useState(Object.keys(METHOD_CONFIG));
  const [amount, setAmount] = useState(initialAmount || 0);
  const [instructions, setInstructions] = useState(null);
  const [copied, setCopied] = useState(false);

  const selectMethod = useCallback(async (method) => {
    setLoading(true);
    try {
      const data = await apiClient.getPaymentUrl(user.token, booking_id, method);
      if (data.step === 'payment_instructions') {
        setInstructions(data);
        setStep('payment_instructions');
      }
    } catch (err) {
      Alert.alert('Gagal', err.message || 'Terjadi kesalahan. Coba lagi.');
    } finally {
      setLoading(false);
    }
  }, [user.token, booking_id]);

  const copyToClipboard = (text) => {
    Clipboard.setString(text);
    setCopied(true);
    setTimeout(() => setCopied(false), 2000);
  };

  const handleDone = () => {
    navigation.navigate('BookingDetail', { booking_id });
  };

  const cfg = instructions ? (METHOD_CONFIG[instructions.method] || {}) : {};

  return (
    <SwipeableScreen onSwipeBack={() => navigation.goBack()}>
      <View style={styles.container}>
        <StatusBar barStyle="dark-content" />

        {/* Header */}
        <View style={styles.header}>
          <SafeAreaView edges={['top']}>
            <View style={styles.headerContent}>
              <TouchableOpacity
                onPress={() => step === 'payment_instructions' ? setStep('select_method') : navigation.goBack()}
                style={styles.backBtn}
              >
                <Ionicons name="arrow-back" size={24} color="#0F172A" />
              </TouchableOpacity>
              <View style={styles.headerCenter}>
                <Text style={styles.headerTitle}>
                  {step === 'select_method' ? 'Pilih Metode Bayar' : 'Instruksi Pembayaran'}
                </Text>
                {booking_code && <Text style={styles.headerSub}>{booking_code}</Text>}
              </View>
              <View style={{ width: 44 }} />
            </View>
          </SafeAreaView>
        </View>

        {loading ? (
          <View style={styles.loadingBox}>
            <ActivityIndicator size="large" color="#3B82F6" />
            <Text style={styles.loadingText}>Menyiapkan pembayaran…</Text>
          </View>
        ) : step === 'select_method' ? (
          <ScrollView contentContainerStyle={styles.listPadding} showsVerticalScrollIndicator={false}>
            {/* Total banner */}
            <LinearGradient colors={['#0F172A', '#1E3A8A']} style={styles.amountBanner} start={{ x: 0, y: 0 }} end={{ x: 1, y: 1 }}>
              <Text style={styles.amountLabel}>TOTAL PEMBAYARAN</Text>
              <Text style={styles.amountValue}>Rp {formatIDR(amount)}</Text>
            </LinearGradient>

            <Text style={styles.sectionTitle}>Pilih Metode</Text>

            {/* Virtual Account Group */}
            <Text style={styles.groupLabel}>Virtual Account</Text>
            {['va_bca', 'va_bni', 'va_bri', 'va_permata', 'va_mandiri_bill'].map(m => (
              <MethodCard key={m} method={m} onPress={() => selectMethod(m)} />
            ))}

            {/* E-Wallet Group */}
            <Text style={styles.groupLabel}>E-Wallet & QRIS</Text>
            {['qris', 'gopay', 'shopeepay'].map(m => (
              <MethodCard key={m} method={m} onPress={() => selectMethod(m)} />
            ))}

            <View style={{ height: 40 }} />
          </ScrollView>
        ) : (
          <ScrollView contentContainerStyle={styles.listPadding} showsVerticalScrollIndicator={false}>
            {/* Method header */}
            <View style={[styles.instrMethodHeader, { borderLeftColor: cfg.color || '#3B82F6' }]}>
              <Text style={styles.instrMethodIcon}>{cfg.icon}</Text>
              <View>
                <Text style={styles.instrMethodName}>{cfg.label}</Text>
                <Text style={styles.instrMethodDesc}>{cfg.desc}</Text>
              </View>
            </View>

            {/* Amount */}
            <LinearGradient colors={['#0F172A', '#1E3A8A']} style={styles.amountBanner} start={{ x: 0, y: 0 }} end={{ x: 1, y: 1 }}>
              <Text style={styles.amountLabel}>TOTAL PEMBAYARAN</Text>
              <Text style={styles.amountValue}>Rp {formatIDR(instructions?.gross_amount)}</Text>
              {instructions?.expiry_time && (
                <View style={styles.expiryRow}>
                  <Ionicons name="time-outline" size={13} color="rgba(255,255,255,0.7)" />
                  <Text style={styles.expiryText}>Kadaluarsa: {new Date(instructions.expiry_time).toLocaleString('id-ID', { day: 'numeric', month: 'short', hour: '2-digit', minute: '2-digit' })}</Text>
                </View>
              )}
            </LinearGradient>

            {/* VA Number */}
            {instructions?.va_number && (
              <View style={styles.instrCard}>
                <Text style={styles.instrCardTitle}>Nomor Virtual Account</Text>
                <View style={styles.vaRow}>
                  <Text style={styles.vaNumber}>{instructions.va_number}</Text>
                  <TouchableOpacity onPress={() => copyToClipboard(instructions.va_number)} style={styles.copyBtn}>
                    <Ionicons name={copied ? 'checkmark' : 'copy-outline'} size={20} color={copied ? '#10B981' : '#3B82F6'} />
                  </TouchableOpacity>
                </View>
                <Text style={styles.bankName}>{(instructions.va_bank || '').toUpperCase()}</Text>
                <View style={styles.instrSteps}>
                  <Text style={styles.instrStepsTitle}>Cara Bayar:</Text>
                  <Text style={styles.instrStep}>1. Buka aplikasi m-Banking atau ATM {(instructions.va_bank || '').toUpperCase()}</Text>
                  <Text style={styles.instrStep}>2. Pilih menu Transfer → Virtual Account</Text>
                  <Text style={styles.instrStep}>3. Masukkan nomor VA di atas</Text>
                  <Text style={styles.instrStep}>4. Konfirmasi dan selesaikan pembayaran</Text>
                </View>
              </View>
            )}

            {/* Mandiri Bill */}
            {instructions?.bill_key && (
              <View style={styles.instrCard}>
                <Text style={styles.instrCardTitle}>Mandiri Bill Payment</Text>
                <View style={styles.mandiriRow}>
                  <View style={styles.mandiriField}>
                    <Text style={styles.mandiriLabel}>Kode Perusahaan (Biller Code)</Text>
                    <View style={styles.vaRow}>
                      <Text style={styles.vaNumber}>{instructions.biller_code}</Text>
                      <TouchableOpacity onPress={() => copyToClipboard(instructions.biller_code)} style={styles.copyBtn}>
                        <Ionicons name="copy-outline" size={18} color="#3B82F6" />
                      </TouchableOpacity>
                    </View>
                  </View>
                  <View style={styles.mandiriField}>
                    <Text style={styles.mandiriLabel}>Kode Tagihan (Bill Key)</Text>
                    <View style={styles.vaRow}>
                      <Text style={styles.vaNumber}>{instructions.bill_key}</Text>
                      <TouchableOpacity onPress={() => copyToClipboard(instructions.bill_key)} style={styles.copyBtn}>
                        <Ionicons name="copy-outline" size={18} color="#3B82F6" />
                      </TouchableOpacity>
                    </View>
                  </View>
                </View>
              </View>
            )}

            {/* QRIS */}
            {instructions?.qr_string && (
              <View style={styles.instrCard}>
                <Text style={styles.instrCardTitle}>Scan QR Code</Text>
                <Text style={styles.qrDesc}>Scan QR ini dengan aplikasi e-wallet Anda (GoPay, OVO, Dana, LinkAja, dll.)</Text>
                <View style={styles.qrBox}>
                  <Image
                    source={{ uri: `https://api.qrserver.com/v1/create-qr-code/?size=200x200&data=${encodeURIComponent(instructions.qr_string)}` }}
                    style={styles.qrImage}
                  />
                </View>
              </View>
            )}

            {/* GoPay / ShopeePay deeplink */}
            {(instructions?.deeplink_url || instructions?.checkout_url) && !instructions?.qr_string && (
              <TouchableOpacity
                style={styles.deeplinkBtn}
                onPress={() => {
                  const url = instructions.deeplink_url || instructions.checkout_url;
                  if (url) require('react-native').Linking.openURL(url);
                }}
              >
                <LinearGradient colors={[cfg.color || '#0F172A', '#0F172A']} style={styles.deeplinkGradient}>
                  <Text style={styles.deeplinkText}>Buka Aplikasi {cfg.label}</Text>
                  <Ionicons name="arrow-forward" size={18} color="#FFF" />
                </LinearGradient>
              </TouchableOpacity>
            )}

            {/* Selesai */}
            <TouchableOpacity style={styles.doneBtn} onPress={handleDone}>
              <Text style={styles.doneBtnText}>Saya Sudah Bayar — Cek Status</Text>
            </TouchableOpacity>

            <View style={{ height: 40 }} />
          </ScrollView>
        )}
      </View>
    </SwipeableScreen>
  );
}

function MethodCard({ method, onPress }) {
  const cfg = METHOD_CONFIG[method];
  return (
    <TouchableOpacity style={styles.methodCard} onPress={onPress} activeOpacity={0.75}>
      <View style={[styles.methodIconBox, { backgroundColor: cfg.color + '15' }]}>
        <Text style={styles.methodEmoji}>{cfg.icon}</Text>
      </View>
      <View style={styles.methodInfo}>
        <Text style={styles.methodLabel}>{cfg.label}</Text>
        <Text style={styles.methodDesc} numberOfLines={1}>{cfg.desc}</Text>
      </View>
      <Ionicons name="chevron-forward" size={18} color="#CBD5E1" />
    </TouchableOpacity>
  );
}

const styles = StyleSheet.create({
  container: { flex: 1, backgroundColor: '#F8FAFC' },

  header: {
    backgroundColor: '#FFFFFF',
    paddingBottom: 20,
    borderBottomLeftRadius: 30,
    borderBottomRightRadius: 30,
    shadowColor: '#000', shadowOffset: { width: 0, height: 4 }, shadowOpacity: 0.03, shadowRadius: 10, elevation: 5,
  },
  headerContent: { flexDirection: 'row', alignItems: 'center', justifyContent: 'space-between', paddingHorizontal: 20, paddingTop: 10 },
  backBtn: { width: 44, height: 44, justifyContent: 'center', alignItems: 'center' },
  headerCenter: { alignItems: 'center' },
  headerTitle: { fontSize: 18, fontWeight: '900', color: '#0F172A' },
  headerSub: { fontSize: 11, color: '#94A3B8', fontWeight: '700', marginTop: 2, letterSpacing: 0.5 },

  loadingBox: { flex: 1, justifyContent: 'center', alignItems: 'center', gap: 16 },
  loadingText: { fontSize: 14, color: '#64748B', fontWeight: '600' },

  listPadding: { padding: 20 },

  amountBanner: { borderRadius: 24, padding: 24, marginBottom: 24 },
  amountLabel: { fontSize: 10, color: 'rgba(255,255,255,0.6)', fontWeight: '800', letterSpacing: 1 },
  amountValue: { fontSize: 28, fontWeight: '900', color: '#FFF', marginTop: 8 },
  expiryRow: { flexDirection: 'row', alignItems: 'center', gap: 6, marginTop: 10 },
  expiryText: { fontSize: 11, color: 'rgba(255,255,255,0.7)', fontWeight: '600' },

  sectionTitle: { fontSize: 22, fontWeight: '900', color: '#0F172A', marginBottom: 20 },
  groupLabel: { fontSize: 11, fontWeight: '800', color: '#94A3B8', textTransform: 'uppercase', letterSpacing: 1, marginBottom: 10, marginTop: 6 },

  methodCard: {
    backgroundColor: '#FFFFFF', borderRadius: 20, padding: 18, marginBottom: 12,
    flexDirection: 'row', alignItems: 'center', gap: 16,
    borderWidth: 1, borderColor: '#F1F5F9',
    shadowColor: '#000', shadowOffset: { width: 0, height: 2 }, shadowOpacity: 0.03, shadowRadius: 8, elevation: 2,
  },
  methodIconBox: { width: 48, height: 48, borderRadius: 16, justifyContent: 'center', alignItems: 'center', flexShrink: 0 },
  methodEmoji: { fontSize: 22 },
  methodInfo: { flex: 1 },
  methodLabel: { fontSize: 15, fontWeight: '800', color: '#0F172A' },
  methodDesc: { fontSize: 12, color: '#94A3B8', marginTop: 3, fontWeight: '500' },

  instrMethodHeader: {
    flexDirection: 'row', alignItems: 'center', gap: 16,
    backgroundColor: '#FFF', borderRadius: 20, padding: 18, marginBottom: 16,
    borderLeftWidth: 4,
    shadowColor: '#000', shadowOffset: { width: 0, height: 2 }, shadowOpacity: 0.03, shadowRadius: 8, elevation: 2,
  },
  instrMethodIcon: { fontSize: 28 },
  instrMethodName: { fontSize: 16, fontWeight: '900', color: '#0F172A' },
  instrMethodDesc: { fontSize: 12, color: '#64748B', marginTop: 2, fontWeight: '500' },

  instrCard: {
    backgroundColor: '#FFF', borderRadius: 24, padding: 22, marginBottom: 16,
    borderWidth: 1, borderColor: '#F1F5F9',
    shadowColor: '#000', shadowOffset: { width: 0, height: 2 }, shadowOpacity: 0.03, shadowRadius: 8, elevation: 2,
  },
  instrCardTitle: { fontSize: 13, fontWeight: '800', color: '#94A3B8', textTransform: 'uppercase', letterSpacing: 1, marginBottom: 16 },

  vaRow: { flexDirection: 'row', alignItems: 'center', justifyContent: 'space-between' },
  vaNumber: { fontSize: 26, fontWeight: '900', color: '#0F172A', letterSpacing: 2, flex: 1 },
  bankName: { fontSize: 12, color: '#94A3B8', fontWeight: '700', marginTop: 4, marginBottom: 20 },
  copyBtn: { width: 44, height: 44, justifyContent: 'center', alignItems: 'center', backgroundColor: '#F0F9FF', borderRadius: 14 },

  instrSteps: { backgroundColor: '#F8FAFC', borderRadius: 16, padding: 16 },
  instrStepsTitle: { fontSize: 12, fontWeight: '800', color: '#64748B', marginBottom: 10 },
  instrStep: { fontSize: 13, color: '#475569', lineHeight: 22, fontWeight: '500' },

  mandiriRow: { gap: 16 },
  mandiriField: { gap: 8 },
  mandiriLabel: { fontSize: 11, fontWeight: '700', color: '#94A3B8', textTransform: 'uppercase', letterSpacing: 0.5 },

  qrDesc: { fontSize: 13, color: '#64748B', marginBottom: 16, lineHeight: 20 },
  qrBox: { alignItems: 'center', padding: 16, backgroundColor: '#F8FAFC', borderRadius: 20 },
  qrImage: { width: 200, height: 200, borderRadius: 12 },

  deeplinkBtn: { marginBottom: 16, borderRadius: 20, overflow: 'hidden' },
  deeplinkGradient: { flexDirection: 'row', alignItems: 'center', justifyContent: 'center', paddingVertical: 20, gap: 12 },
  deeplinkText: { fontSize: 16, fontWeight: '900', color: '#FFF' },

  doneBtn: {
    backgroundColor: '#F8FAFC', borderRadius: 20, paddingVertical: 18, alignItems: 'center',
    borderWidth: 1, borderColor: '#E2E8F0', marginTop: 8,
  },
  doneBtnText: { fontSize: 15, fontWeight: '700', color: '#64748B' },
});
