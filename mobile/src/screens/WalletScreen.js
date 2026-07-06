import React, { useCallback, useState } from 'react';
import {
  View,
  Text,
  StyleSheet,
  ScrollView,
  ActivityIndicator,
  RefreshControl,
  TouchableOpacity,
  TextInput,
  Alert,
} from 'react-native';
import { useFocusEffect } from '@react-navigation/native';
import TabPageHeader from '../components/TabPageHeader';
import { fetchWallet, submitWithdrawal } from '../api/wallet';
import { useAuth } from '../context/AuthContext';
import { colors } from '../theme/colors';
import { formatIdr } from '../utils/format';

const LEDGER_LABELS = {
  booking_credit: 'Kredit booking',
  referral_reward: 'Reward referral',
  withdraw_debit: 'Penarikan',
  withdraw_refund: 'Refund penarikan',
  refund_completed: 'Refund selesai',
};

function LedgerRow({ entry }) {
  const signed = Number(entry.signed_amount) || 0;
  const positive = signed >= 0;
  const label = LEDGER_LABELS[entry.kind] || entry.kind;

  return (
    <View style={styles.ledgerRow}>
      <View style={styles.ledgerMeta}>
        <Text style={styles.ledgerKind}>{label}</Text>
        <Text style={styles.ledgerTime}>{entry.at}</Text>
        {entry.booking_code ? <Text style={styles.ledgerCode}>{entry.booking_code}</Text> : null}
      </View>
      <Text style={[styles.ledgerAmount, positive ? styles.amountPlus : styles.amountMinus]}>
        {positive ? '+' : '−'} {formatIdr(Math.abs(signed))}
      </Text>
    </View>
  );
}

export default function WalletScreen() {
  const { token } = useAuth();
  const [loading, setLoading] = useState(true);
  const [refreshing, setRefreshing] = useState(false);
  const [balance, setBalance] = useState(0);
  const [ledger, setLedger] = useState([]);
  const [bankOptions, setBankOptions] = useState({});
  const [showForm, setShowForm] = useState(false);
  const [submitting, setSubmitting] = useState(false);
  const [amount, setAmount] = useState('');
  const [beneficiaryName, setBeneficiaryName] = useState('');
  const [beneficiaryBank, setBeneficiaryBank] = useState('');
  const [beneficiaryAccount, setBeneficiaryAccount] = useState('');
  const [notes, setNotes] = useState('');

  const load = useCallback(async (refresh = false) => {
    if (refresh) setRefreshing(true);
    else setLoading(true);

    try {
      const data = await fetchWallet(token);
      setBalance(Number(data.balance) || 0);
      setLedger(data.ledger || []);
      setBankOptions(data.bank_options || {});
    } catch (err) {
      Alert.alert('Gagal', err.message || 'Tidak dapat memuat dompet');
    } finally {
      setLoading(false);
      setRefreshing(false);
    }
  }, [token]);

  useFocusEffect(
    useCallback(() => {
      load();
    }, [load]),
  );

  const bankKeys = Object.keys(bankOptions);

  const handleWithdraw = async () => {
    const parsedAmount = Number(amount.replace(/\D/g, ''));
    if (!parsedAmount || parsedAmount < 10000) {
      Alert.alert('Validasi', 'Minimum penarikan Rp 10.000');
      return;
    }
    if (!beneficiaryName.trim() || !beneficiaryBank || !beneficiaryAccount.trim()) {
      Alert.alert('Validasi', 'Lengkapi data rekening penerima');
      return;
    }

    setSubmitting(true);
    try {
      await submitWithdrawal(token, {
        amount: parsedAmount,
        beneficiary_name: beneficiaryName.trim(),
        beneficiary_bank: beneficiaryBank,
        beneficiary_account: beneficiaryAccount.trim(),
        notes: notes.trim() || null,
      });
      Alert.alert('Berhasil', 'Permintaan withdraw diajukan.');
      setShowForm(false);
      setAmount('');
      setNotes('');
      await load(true);
    } catch (err) {
      Alert.alert('Gagal', err.message || 'Tidak dapat mengajukan withdraw');
    } finally {
      setSubmitting(false);
    }
  };

  return (
    <View style={styles.container}>
      <TabPageHeader title="Dompet" subtitle="Saldo & riwayat mutasi" />

      {loading && !refreshing ? (
        <ActivityIndicator color={colors.baytgo} style={styles.loader} />
      ) : (
        <ScrollView
          contentContainerStyle={styles.scroll}
          refreshControl={
            <RefreshControl refreshing={refreshing} onRefresh={() => load(true)} tintColor={colors.baytgo} />
          }
        >
          <View style={styles.balanceCard}>
            <Text style={styles.balanceLabel}>Saldo tersedia</Text>
            <Text style={styles.balanceValue}>{formatIdr(balance)}</Text>
            <TouchableOpacity style={styles.withdrawBtn} onPress={() => setShowForm((v) => !v)}>
              <Text style={styles.withdrawBtnText}>{showForm ? 'Tutup formulir' : 'Tarik saldo'}</Text>
            </TouchableOpacity>
          </View>

          {showForm ? (
            <View style={styles.formCard}>
              <Text style={styles.formTitle}>Ajukan penarikan</Text>
              <TextInput
                style={styles.input}
                placeholder="Jumlah (Rp)"
                keyboardType="numeric"
                value={amount}
                onChangeText={setAmount}
              />
              <TextInput
                style={styles.input}
                placeholder="Nama penerima"
                value={beneficiaryName}
                onChangeText={setBeneficiaryName}
              />
              <Text style={styles.fieldLabel}>Bank</Text>
              <ScrollView horizontal showsHorizontalScrollIndicator={false} style={styles.bankRow}>
                {bankKeys.map((key) => (
                  <TouchableOpacity
                    key={key}
                    style={[styles.bankChip, beneficiaryBank === key && styles.bankChipActive]}
                    onPress={() => setBeneficiaryBank(key)}
                  >
                    <Text style={[styles.bankChipText, beneficiaryBank === key && styles.bankChipTextActive]}>
                      {key}
                    </Text>
                  </TouchableOpacity>
                ))}
              </ScrollView>
              <TextInput
                style={styles.input}
                placeholder="Nomor rekening"
                keyboardType="number-pad"
                value={beneficiaryAccount}
                onChangeText={setBeneficiaryAccount}
              />
              <TextInput
                style={styles.input}
                placeholder="Catatan (opsional)"
                value={notes}
                onChangeText={setNotes}
              />
              <TouchableOpacity
                style={[styles.submitBtn, submitting && styles.submitBtnDisabled]}
                onPress={handleWithdraw}
                disabled={submitting}
              >
                <Text style={styles.submitBtnText}>{submitting ? 'Mengirim…' : 'Ajukan withdraw'}</Text>
              </TouchableOpacity>
            </View>
          ) : null}

          <Text style={styles.sectionTitle}>Riwayat mutasi</Text>
          {ledger.length === 0 ? (
            <Text style={styles.muted}>Belum ada mutasi.</Text>
          ) : (
            ledger.map((entry, idx) => <LedgerRow key={`${entry.at}-${idx}`} entry={entry} />)
          )}
        </ScrollView>
      )}
    </View>
  );
}

const styles = StyleSheet.create({
  container: { flex: 1, backgroundColor: colors.canvas },
  scroll: { paddingHorizontal: 20, paddingBottom: 32 },
  loader: { marginTop: 40 },
  balanceCard: {
    backgroundColor: colors.baytgo,
    borderRadius: 20,
    padding: 20,
    marginBottom: 16,
  },
  balanceLabel: { fontSize: 12, fontWeight: '700', color: colors.goldLight, textTransform: 'uppercase' },
  balanceValue: { marginTop: 8, fontSize: 28, fontWeight: '900', color: colors.white },
  withdrawBtn: {
    marginTop: 16,
    alignSelf: 'flex-start',
    backgroundColor: colors.white,
    borderRadius: 12,
    paddingHorizontal: 16,
    paddingVertical: 10,
  },
  withdrawBtnText: { fontSize: 13, fontWeight: '800', color: colors.baytgo },
  formCard: {
    backgroundColor: colors.white,
    borderRadius: 18,
    padding: 16,
    marginBottom: 16,
    borderWidth: 1,
    borderColor: colors.slate100,
  },
  formTitle: { fontSize: 16, fontWeight: '900', color: colors.baytgo, marginBottom: 12 },
  fieldLabel: { fontSize: 12, fontWeight: '700', color: colors.slate500, marginBottom: 8 },
  input: {
    backgroundColor: colors.canvas,
    borderRadius: 12,
    paddingHorizontal: 14,
    paddingVertical: 12,
    marginBottom: 10,
    fontSize: 14,
    fontWeight: '600',
    color: colors.slate900,
    borderWidth: 1,
    borderColor: colors.slate100,
  },
  bankRow: { marginBottom: 10 },
  bankChip: {
    borderRadius: 999,
    paddingHorizontal: 12,
    paddingVertical: 8,
    marginRight: 8,
    backgroundColor: colors.canvas,
    borderWidth: 1,
    borderColor: colors.slate200,
  },
  bankChipActive: { backgroundColor: colors.baytgo, borderColor: colors.baytgo },
  bankChipText: { fontSize: 12, fontWeight: '800', color: colors.slate600 },
  bankChipTextActive: { color: colors.white },
  submitBtn: {
    marginTop: 4,
    backgroundColor: colors.baytgo,
    borderRadius: 14,
    paddingVertical: 14,
    alignItems: 'center',
  },
  submitBtnDisabled: { opacity: 0.6 },
  submitBtnText: { color: colors.white, fontWeight: '800', fontSize: 14 },
  sectionTitle: { fontSize: 18, fontWeight: '900', color: colors.baytgo, marginBottom: 12 },
  muted: { fontSize: 14, color: colors.slate500, fontWeight: '600' },
  ledgerRow: {
    flexDirection: 'row',
    justifyContent: 'space-between',
    gap: 12,
    backgroundColor: colors.white,
    borderRadius: 14,
    padding: 14,
    marginBottom: 8,
    borderWidth: 1,
    borderColor: colors.slate100,
  },
  ledgerMeta: { flex: 1 },
  ledgerKind: { fontSize: 13, fontWeight: '800', color: colors.slate900 },
  ledgerTime: { marginTop: 4, fontSize: 11, color: colors.slate500, fontWeight: '600' },
  ledgerCode: { marginTop: 2, fontSize: 11, color: colors.baytgo, fontWeight: '700' },
  ledgerAmount: { fontSize: 13, fontWeight: '900' },
  amountPlus: { color: colors.emerald600 },
  amountMinus: { color: '#B91C1C' },
});
