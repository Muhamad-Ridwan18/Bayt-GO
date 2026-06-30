import React, { useState } from 'react';
import {
  View,
  Text,
  StyleSheet,
  ScrollView,
  TouchableOpacity,
  ActivityIndicator,
  Alert,
  TextInput,
} from 'react-native';
import { LinearGradient } from 'expo-linear-gradient';
import ScreenHeader from '../components/ScreenHeader';
import AuthInput from '../components/AuthInput';
import { submitRefundRequest } from '../api/bookings';
import { useAuth } from '../context/AuthContext';
import { colors } from '../theme/colors';

export default function BookingRefundScreen({ navigation, route }) {
  const { token } = useAuth();
  const { bookingId } = route.params;

  const [bankName, setBankName] = useState('');
  const [accountHolder, setAccountHolder] = useState('');
  const [accountNumber, setAccountNumber] = useState('');
  const [note, setNote] = useState('');
  const [loading, setLoading] = useState(false);
  const [error, setError] = useState('');

  const handleSubmit = async () => {
    if (!bankName.trim() || !accountHolder.trim() || !accountNumber.trim()) {
      setError('Data rekening refund wajib diisi.');
      return;
    }

    setLoading(true);
    setError('');
    try {
      await submitRefundRequest(token, bookingId, {
        refund_bank_name: bankName.trim(),
        refund_account_holder: accountHolder.trim(),
        refund_account_number: accountNumber.trim(),
        customer_note: note.trim() || null,
      });
      Alert.alert('Berhasil', 'Permintaan refund berhasil diajukan.', [
        { text: 'OK', onPress: () => navigation.navigate('BookingDetail', { bookingId }) },
      ]);
    } catch (err) {
      setError(err.message || 'Gagal mengajukan refund');
    } finally {
      setLoading(false);
    }
  };

  return (
    <View style={styles.container}>
      <ScreenHeader title="Ajukan Refund" onBack={() => navigation.goBack()} />

      <ScrollView contentContainerStyle={styles.scroll} keyboardShouldPersistTaps="handled">
        <Text style={styles.intro}>
          Isi rekening tujuan refund. Permintaan akan diproses oleh tim Bayt-GO sesuai kebijakan.
        </Text>

        {error ? <Text style={styles.error}>{error}</Text> : null}

        <AuthInput label="Nama bank" icon="business-outline" value={bankName} onChangeText={setBankName} placeholder="Contoh: BCA" />
        <AuthInput label="Nama pemilik rekening" icon="person-outline" value={accountHolder} onChangeText={setAccountHolder} placeholder="Sesuai buku tabungan" />
        <AuthInput label="Nomor rekening" icon="card-outline" value={accountNumber} onChangeText={setAccountNumber} placeholder="Hanya angka" keyboardType="number-pad" />

        <Text style={styles.label}>Catatan (opsional)</Text>
        <TextInput
          style={styles.textarea}
          value={note}
          onChangeText={setNote}
          placeholder="Alasan atau informasi tambahan..."
          placeholderTextColor={colors.slate400}
          multiline
          maxLength={2000}
          textAlignVertical="top"
        />

        <TouchableOpacity style={styles.submitBtn} onPress={handleSubmit} disabled={loading} activeOpacity={0.9}>
          <LinearGradient colors={[colors.baytgo, colors.baytgoDark]} style={styles.submitGradient}>
            {loading ? (
              <ActivityIndicator color={colors.white} />
            ) : (
              <Text style={styles.submitText}>Kirim Permintaan Refund</Text>
            )}
          </LinearGradient>
        </TouchableOpacity>
      </ScrollView>
    </View>
  );
}

const styles = StyleSheet.create({
  container: { flex: 1, backgroundColor: colors.canvas },
  scroll: { padding: 16, paddingBottom: 32 },
  intro: { fontSize: 14, lineHeight: 21, color: colors.slate600, fontWeight: '500', marginBottom: 16 },
  error: { marginBottom: 12, fontSize: 13, color: '#DC2626', fontWeight: '600' },
  label: { fontSize: 12, fontWeight: '800', color: colors.slate600, marginBottom: 8 },
  textarea: {
    minHeight: 100,
    backgroundColor: colors.white,
    borderRadius: 16,
    borderWidth: 1,
    borderColor: colors.slate100,
    padding: 14,
    fontSize: 14,
    fontWeight: '500',
    color: colors.slate900,
    marginBottom: 20,
  },
  submitBtn: { borderRadius: 16, overflow: 'hidden' },
  submitGradient: { paddingVertical: 16, alignItems: 'center' },
  submitText: { color: colors.white, fontSize: 15, fontWeight: '800' },
});
