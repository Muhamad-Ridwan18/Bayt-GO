import React, { useEffect, useState } from 'react';
import { View, Text, StyleSheet, ScrollView, TextInput } from 'react-native';
import ScreenHeader from '../components/ScreenHeader';
import AuthInput from '../components/AuthInput';
import { fetchBooking, submitRefundRequest } from '../api/bookings';
import { useAuth } from '../context/AuthContext';
import { Button, InlineAlert } from '../ui';
import ChangePolicyNote from '../features/booking/ChangePolicyNote';
import { notifySuccessThen } from '../utils/feedback';
import { colors, layout, radius, spacing, typography } from '../theme/tokens';
import { useLocale } from '../utils/locale';

export default function BookingRefundScreen({ navigation, route }) {
  const { token } = useAuth();
  const locale = useLocale(); const isEn = locale === 'en';
  const { bookingId, changePolicy: initialPolicy } = route.params;

  const [policy, setPolicy] = useState(initialPolicy || null);
  const [bankName, setBankName] = useState('');
  const [accountHolder, setAccountHolder] = useState('');
  const [accountNumber, setAccountNumber] = useState('');
  const [note, setNote] = useState('');
  const [loading, setLoading] = useState(false);
  const [error, setError] = useState('');

  useEffect(() => {
    if (initialPolicy || !token) return;
    fetchBooking(token, bookingId)
      .then((data) => setPolicy(data.change_policy || null))
      .catch(() => {});
  }, [token, bookingId, initialPolicy]);

  const handleSubmit = async () => {
    if (!bankName.trim() || !accountHolder.trim() || !accountNumber.trim()) {
      setError(isEn ? 'Refund account details are required.' : 'Data rekening refund wajib diisi.');
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
        isEn ? 'Refund request submitted successfully.' : 'Permintaan refund berhasil diajukan.',
        'BookingDetail',
        { bookingId },
      );
    } catch (err) {
      setError(err.message || (isEn ? 'Failed to submit refund' : 'Gagal mengajukan refund'));
    } finally {
      setLoading(false);
    }
  };

  return (
    <View style={styles.container}>
      <ScreenHeader title={isEn ? 'Request Refund' : 'Ajukan Refund'} onBack={() => navigation.goBack()} />

      <ScrollView contentContainerStyle={styles.scroll} keyboardShouldPersistTaps="handled">
        {policy ? (
          <View style={styles.policy}>
            <ChangePolicyNote policy={policy} mode="refund" />
          </View>
        ) : (
          <Text style={styles.intro}>
            {isEn ? 'Fill in the refund destination account. The request will be processed by the Bayt-GO team according to policy.' : 'Isi rekening tujuan refund. Permintaan akan diproses oleh tim Bayt-GO sesuai kebijakan.'}
          </Text>
        )}

        {error ? <InlineAlert variant="error">{error}</InlineAlert> : null}

        <AuthInput label={isEn ? 'Bank name' : 'Nama bank'} icon="business-outline" value={bankName} onChangeText={setBankName} placeholder={isEn ? 'e.g. BCA' : 'Contoh: BCA'} />
        <AuthInput label={isEn ? 'Account holder name' : 'Nama pemilik rekening'} icon="person-outline" value={accountHolder} onChangeText={setAccountHolder} placeholder={isEn ? 'As on bank book' : 'Sesuai buku tabungan'} />
        <AuthInput label={isEn ? 'Account number' : 'Nomor rekening'} icon="card-outline" value={accountNumber} onChangeText={setAccountNumber} placeholder={isEn ? 'Numbers only' : 'Hanya angka'} keyboardType="number-pad" />

        <Text style={styles.label}>{isEn ? 'Note (optional)' : 'Catatan (opsional)'}</Text>
        <TextInput
          style={styles.textarea}
          value={note}
          onChangeText={setNote}
          placeholder={isEn ? 'Reason or additional info...' : 'Alasan atau informasi tambahan...'}
          placeholderTextColor={colors.textMuted}
          multiline
          maxLength={2000}
          textAlignVertical="top"
        />

        <Button label={isEn ? 'Submit Refund Request' : 'Kirim Permintaan Refund'} onPress={handleSubmit} loading={loading} style={styles.submit} />
      </ScrollView>
    </View>
  );
}

const styles = StyleSheet.create({
  container: { flex: 1, backgroundColor: colors.background },
  scroll: { padding: layout.screenPadding, paddingBottom: spacing['3xl'] },
  intro: { ...typography.caption, lineHeight: 22, color: colors.textSecondary, marginBottom: spacing.lg },
  policy: { marginBottom: spacing.lg },
  submit: { marginTop: spacing.md },
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
