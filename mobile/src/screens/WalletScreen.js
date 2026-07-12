import React, { useCallback, useState } from 'react';
import {
  Alert,
  RefreshControl,
  ScrollView,
  StyleSheet,
  Text,
  TextInput,
  View,
} from 'react-native';
import { useFocusEffect } from '@react-navigation/native';
import { LinearGradient } from 'expo-linear-gradient';
import { Wallet } from 'lucide-react-native';
import TabPageHeader from '../components/TabPageHeader';
import WalletLedgerRow from '../components/WalletLedgerRow';
import { fetchWallet, submitWithdrawal } from '../api/wallet';
import { useAuth } from '../context/AuthContext';
import Button from '../ui/Button';
import Card from '../ui/Card';
import EmptyState from '../ui/EmptyState';
import ErrorState from '../ui/ErrorState';
import FilterChip from '../ui/FilterChip';
import { SkeletonList } from '../ui/Skeleton';
import { colors, gradients, layout, radius, spacing, typography } from '../theme/tokens';
import { formatIdr } from '../utils/format';
import { notifyError, notifySuccess } from '../utils/feedback';

export default function WalletScreen() {
  const { token } = useAuth();
  const [loading, setLoading] = useState(true);
  const [refreshing, setRefreshing] = useState(false);
  const [error, setError] = useState(null);
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
      setError(null);
    } catch (err) {
      setError(err.message || 'Tidak dapat memuat dompet');
      if (!refresh) {
        setBalance(0);
        setLedger([]);
      }
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
      notifySuccess('Permintaan withdraw diajukan.');
      setShowForm(false);
      setAmount('');
      setNotes('');
      await load(true);
    } catch (err) {
      notifyError(err.message || 'Tidak dapat mengajukan withdraw');
    } finally {
      setSubmitting(false);
    }
  };

  if (loading && !refreshing) {
    return (
      <View style={styles.container}>
        <TabPageHeader title="Dompet" subtitle="Saldo & riwayat mutasi" />
        <SkeletonList count={3} style={styles.skeleton} />
      </View>
    );
  }

  if (error && ledger.length === 0 && balance === 0) {
    return (
      <View style={styles.container}>
        <TabPageHeader title="Dompet" subtitle="Saldo & riwayat mutasi" />
        <ErrorState description={error} onRetry={() => load()} />
      </View>
    );
  }

  return (
    <View style={styles.container}>
      <TabPageHeader title="Dompet" subtitle="Saldo & riwayat mutasi" />

      <ScrollView
        contentContainerStyle={styles.scroll}
        showsVerticalScrollIndicator={false}
        refreshControl={
          <RefreshControl
            refreshing={refreshing}
            onRefresh={() => load(true)}
            tintColor={colors.baytgo}
          />
        }
      >
        <LinearGradient colors={gradients.primary} style={styles.balanceCard}>
          <View style={styles.balanceTop}>
            <View>
              <Text style={styles.balanceLabel}>Saldo tersedia</Text>
              <Text style={styles.balanceValue}>{formatIdr(balance)}</Text>
            </View>
            <View style={styles.balanceIcon}>
              <Wallet size={28} color={colors.gold} strokeWidth={2} />
            </View>
          </View>
          <Button
            label={showForm ? 'Tutup formulir' : 'Tarik saldo'}
            variant="secondary"
            size="sm"
            fullWidth={false}
            onPress={() => setShowForm((v) => !v)}
          />
        </LinearGradient>

        {showForm ? (
          <Card style={styles.formCard} padding={spacing.lg} elevated>
            <Text style={styles.formTitle}>Ajukan penarikan</Text>
            <TextInput
              style={styles.input}
              placeholder="Jumlah (Rp)"
              placeholderTextColor={colors.textMuted}
              keyboardType="numeric"
              value={amount}
              onChangeText={setAmount}
            />
            <TextInput
              style={styles.input}
              placeholder="Nama penerima"
              placeholderTextColor={colors.textMuted}
              value={beneficiaryName}
              onChangeText={setBeneficiaryName}
            />
            <Text style={styles.fieldLabel}>Bank</Text>
            <ScrollView
              horizontal
              showsHorizontalScrollIndicator={false}
              contentContainerStyle={styles.bankRow}
            >
              {bankKeys.map((key) => (
                <FilterChip
                  key={key}
                  label={key}
                  active={beneficiaryBank === key}
                  onPress={() => setBeneficiaryBank(key)}
                />
              ))}
            </ScrollView>
            <TextInput
              style={styles.input}
              placeholder="Nomor rekening"
              placeholderTextColor={colors.textMuted}
              keyboardType="number-pad"
              value={beneficiaryAccount}
              onChangeText={setBeneficiaryAccount}
            />
            <TextInput
              style={styles.input}
              placeholder="Catatan (opsional)"
              placeholderTextColor={colors.textMuted}
              value={notes}
              onChangeText={setNotes}
            />
            <Button
              label="Ajukan withdraw"
              onPress={handleWithdraw}
              loading={submitting}
              disabled={submitting}
            />
          </Card>
        ) : null}

        <Text style={styles.sectionTitle}>Riwayat mutasi</Text>
        {ledger.length === 0 ? (
          <EmptyState
            variant="default"
            title="Belum ada mutasi"
            description="Riwayat kredit dan penarikan akan muncul di sini."
          />
        ) : (
          ledger.map((entry, idx) => (
            <WalletLedgerRow key={`${entry.at}-${idx}`} entry={entry} />
          ))
        )}
      </ScrollView>
    </View>
  );
}

const styles = StyleSheet.create({
  container: {
    flex: 1,
    backgroundColor: colors.background,
  },
  skeleton: {
    paddingHorizontal: layout.screenPadding,
    paddingTop: spacing.lg,
  },
  scroll: {
    paddingHorizontal: layout.screenPadding,
    paddingBottom: spacing.lg,
  },
  balanceCard: {
    borderRadius: radius.md,
    padding: spacing.xl,
    marginBottom: spacing.lg,
  },
  balanceTop: {
    flexDirection: 'row',
    justifyContent: 'space-between',
    alignItems: 'flex-start',
    marginBottom: spacing.lg,
  },
  balanceLabel: {
    ...typography.small,
    color: colors.goldLight,
    textTransform: 'uppercase',
    letterSpacing: 0.6,
  },
  balanceValue: {
    ...typography.hero,
    fontSize: 28,
    lineHeight: 36,
    color: colors.white,
    marginTop: spacing.sm,
  },
  balanceIcon: {
    width: 52,
    height: 52,
    borderRadius: radius.sm,
    backgroundColor: `${colors.white}30`,
    alignItems: 'center',
    justifyContent: 'center',
  },
  formCard: {
    borderRadius: radius.md,
    marginBottom: spacing.lg,
  },
  formTitle: {
    ...typography.subtitle,
    color: colors.baytgo,
    marginBottom: spacing.lg,
  },
  fieldLabel: {
    ...typography.small,
    color: colors.textSecondary,
    marginBottom: spacing.sm,
  },
  input: {
    backgroundColor: colors.surface,
    borderRadius: radius.sm,
    paddingHorizontal: spacing.lg,
    paddingVertical: spacing.md,
    marginBottom: spacing.md,
    ...typography.caption,
    color: colors.textPrimary,
    borderWidth: 1,
    borderColor: colors.border,
    minHeight: layout.minTouch,
  },
  bankRow: {
    gap: spacing.sm,
    paddingRight: spacing.lg,
    marginBottom: spacing.md,
  },
  sectionTitle: {
    ...typography.subtitle,
    color: colors.baytgo,
    marginBottom: spacing.lg,
  },
});
