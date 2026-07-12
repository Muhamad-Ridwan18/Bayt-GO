import React, { useState } from 'react';
import { View, Text, StyleSheet, ScrollView, TextInput } from 'react-native';
import ScreenHeader from '../components/ScreenHeader';
import AuthInput from '../components/AuthInput';
import { submitRefundRequest } from '../api/bookings';
import { useAuth } from '../context/AuthContext';
import { Button, Card, InlineAlert } from '../ui';
import { notifySuccessThen } from '../utils/feedback';
import { colors, layout, radius, spacing, typography } from '../theme/tokens';

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
      notifySuccessThen(
        navigation,
        'Permintaan refund berhasil diajukan.',
        'BookingDetail',
        { bookingId },
      );
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

        {error ? <InlineAlert variant="error">{error}</InlineAlert> : null}

        <AuthInput label="Nama bank" icon="business-outline" value={bankName} onChangeText={setBankName} placeholder="Contoh: BCA" />
        <AuthInput label="Nama pemilik rekening" icon="person-outline" value={accountHolder} onChangeText={setAccountHolder} placeholder="Sesuai buku tabungan" />
        <AuthInput label="Nomor rekening" icon="card-outline" value={accountNumber} onChangeText={setAccountNumber} placeholder="Hanya angka" keyboardType="number-pad" />

        <Text style={styles.label}>Catatan (opsional)</Text>
        <TextInput
          style={styles.textarea}
          value={note}
          onChangeText={setNote}
          placeholder="Alasan atau informasi tambahan..."
          placeholderTextColor={colors.textMuted}
          multiline
          maxLength={2000}
          textAlignVertical="top"
        />

        <Button label="Kirim Permintaan Refund" onPress={handleSubmit} loading={loading} />
      </ScrollView>
    </View>
  );
}

const styles = StyleSheet.create({
  container: { flex: 1, backgroundColor: colors.background },
  scroll: { padding: layout.screenPadding, paddingBottom: spacing['3xl'] },
  intro: { ...typography.caption, lineHeight: 22, color: colors.textSecondary, marginBottom: spacing.lg },
  label: { ...typography.label, color: colors.textSecondary, marginBottom: spacing.sm },
  textarea: {
    minHeight: 100,
    backgroundColor: colors.card,
    borderRadius: radius.sm,
    borderWidth: 1,
    borderColor: colors.border,
    padding: spacing.lg,
    ...typography.caption,
    color: colors.textPrimary,
    marginBottom: spacing.xl,
  },
});
