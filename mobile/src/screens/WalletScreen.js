import React, { useCallback, useMemo, useRef, useState } from 'react';
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
import Animated, { FadeInDown, FadeOutUp } from 'react-native-reanimated';
import { ArrowDownLeft, ArrowUpRight, Wallet } from 'lucide-react-native';
import TabPageHeader from '../components/TabPageHeader';
import WalletLedgerRow from '../components/WalletLedgerRow';
import { fetchWallet, submitWithdrawal } from '../api/wallet';
import { useAuth } from '../context/AuthContext';
import Button from '../ui/Button';
import Card from '../ui/Card';
import EmptyState from '../ui/EmptyState';
import ErrorState from '../ui/ErrorState';
import FilterChip from '../ui/FilterChip';
import PressableScale from '../ui/PressableScale';
import { SkeletonList } from '../ui/Skeleton';
import { colors, gradients, layout, radius, spacing, typography } from '../theme/tokens';
import { notifyError, notifySuccess } from '../utils/feedback';
import { useLocale } from '../utils/locale';

function formatMoney(value) {
  const n = Math.round(Number(value) || 0);
  return `Rp ${n.toString().replace(/\B(?=(\d{3})+(?!\d))/g, '.')}`;
}

function formatDigits(raw) {
  const digits = String(raw || '').replace(/\D/g, '');
  if (!digits) return '';
  return digits.replace(/\B(?=(\d{3})+(?!\d))/g, '.');
}

const STATUS_META = {
  pending_approval: { id: 'Menunggu', en: 'Pending', bg: colors.warningLight, color: '#B45309' },
  processing: { id: 'Diproses', en: 'Processing', bg: '#EFF6FF', color: '#1D4ED8' },
  succeeded: { id: 'Berhasil', en: 'Paid', bg: colors.successLight, color: colors.success },
  failed: { id: 'Gagal', en: 'Failed', bg: colors.errorLight, color: colors.error },
};

export default function WalletScreen() {
  const { token } = useAuth();
  const locale = useLocale();
  const isEn = locale === 'en';
  const [loading, setLoading] = useState(true);
  const [refreshing, setRefreshing] = useState(false);
  const [error, setError] = useState(null);
  const [balance, setBalance] = useState(0);
  const [ledger, setLedger] = useState([]);
  const [withdrawals, setWithdrawals] = useState([]);
  const [bankOptions, setBankOptions] = useState({});
  const [showForm, setShowForm] = useState(false);
  const [step, setStep] = useState(1);
  const [tab, setTab] = useState('ledger');
  const [submitting, setSubmitting] = useState(false);
  const [amount, setAmount] = useState('');
  const [beneficiaryName, setBeneficiaryName] = useState('');
  const [beneficiaryBank, setBeneficiaryBank] = useState('');
  const [beneficiaryAccount, setBeneficiaryAccount] = useState('');
  const [notes, setNotes] = useState('');
  const didPrefill = useRef(false);

  const load = useCallback(async (refresh = false) => {
    if (refresh) setRefreshing(true);
    else setLoading(true);

    try {
      const data = await fetchWallet(token);
      const nextWithdrawals = data.withdrawals || [];
      setBalance(Number(data.balance) || 0);
      setLedger(data.ledger || []);
      setWithdrawals(nextWithdrawals);
      setBankOptions(data.bank_options || {});
      setError(null);

      const latest = nextWithdrawals[0];
      if (latest && !didPrefill.current) {
        didPrefill.current = true;
        setBeneficiaryName(latest.beneficiary_name || '');
        setBeneficiaryBank(latest.beneficiary_bank || '');
        setBeneficiaryAccount(latest.beneficiary_account || '');
      }
    } catch (err) {
      setError(err.message || (isEn ? 'Could not load wallet' : 'Tidak dapat memuat dompet'));
      if (!refresh) {
        setBalance(0);
        setLedger([]);
        setWithdrawals([]);
      }
    } finally {
      setLoading(false);
      setRefreshing(false);
    }
  }, [token, isEn]);

  useFocusEffect(
    useCallback(() => {
      load();
    }, [load]),
  );

  const bankKeys = Object.keys(bankOptions);
  const amountNumber = Number(String(amount).replace(/\D/g, '')) || 0;
  const remaining = balance - amountNumber;
  const pendingCount = withdrawals.filter((w) =>
    ['pending_approval', 'processing'].includes(w.status),
  ).length;
  const pendingAmount = withdrawals
    .filter((w) => ['pending_approval', 'processing'].includes(w.status))
    .reduce((sum, w) => sum + (Number(w.amount) || 0), 0);
  const monthIn = ledger
    .filter((e) => (e.at || '').startsWith(new Date().toISOString().slice(0, 7)) && Number(e.signed_amount) > 0)
    .reduce((sum, e) => sum + (Number(e.signed_amount) || 0), 0);
  const monthOut = ledger
    .filter((e) => (e.at || '').startsWith(new Date().toISOString().slice(0, 7)) && Number(e.signed_amount) < 0)
    .reduce((sum, e) => sum + Math.abs(Number(e.signed_amount) || 0), 0);
  const quickAmounts = [100000, 500000, 1000000, 2000000].filter((n) => n <= balance);
  const canStep1 = amountNumber >= 10000 && amountNumber <= balance;
  const canStep2 = Boolean(beneficiaryName.trim() && beneficiaryBank && beneficiaryAccount.trim());

  const copy = useMemo(() => ({
    title: isEn ? 'Wallet' : 'Dompet',
    subtitle: isEn ? 'Balance, payouts & activity' : 'Saldo, penarikan & mutasi',
    available: isEn ? 'Available balance' : 'Saldo tersedia',
    pending: isEn
      ? `${pendingCount} in progress`
      : `${pendingCount} sedang diproses`,
    withdraw: isEn ? 'Withdraw' : 'Tarik dana',
    closeForm: isEn ? 'Close form' : 'Tutup formulir',
    formTitle: isEn ? 'Request a withdrawal' : 'Ajukan penarikan',
    formHint: isEn
      ? 'Admin reviews each request. Transfers usually take 1–2 business days.'
      : 'Admin meninjau setiap permintaan. Pencairan biasanya 1–2 hari kerja.',
    amount: isEn ? 'Amount' : 'Nominal',
    all: isEn ? 'All' : 'Semua',
    remaining: isEn ? 'Remaining' : 'Sisa',
    over: isEn ? 'Amount exceeds available balance.' : 'Nominal melebihi saldo tersedia.',
    name: isEn ? 'Account holder' : 'Nama penerima',
    bank: isEn ? 'Bank' : 'Bank',
    account: isEn ? 'Account number' : 'Nomor rekening',
    notes: isEn ? 'Note (optional)' : 'Catatan (opsional)',
    submit: isEn ? 'Submit request' : 'Ajukan withdraw',
    min: isEn ? 'Minimum Rp 10.000' : 'Minimal Rp 10.000',
    ledger: isEn ? 'Activity' : 'Mutasi',
    history: isEn ? 'Withdrawals' : 'Withdraw',
    emptyTitle: isEn ? 'No balance movements yet.' : 'Belum ada pergerakan saldo.',
    emptyDesc: isEn
      ? 'Credits and withdrawals will appear here.'
      : 'Riwayat kredit dan penarikan akan muncul di sini.',
    emptyWdTitle: isEn ? 'No withdrawals yet' : 'Belum ada penarikan',
    emptyWdDesc: isEn
      ? 'After you request a payout, status will show here.'
      : 'Setelah Anda mengajukan withdraw, statusnya tampil di sini.',
    next: isEn ? 'Continue' : 'Lanjut',
    back: isEn ? 'Back' : 'Kembali',
    review: isEn ? 'Review before sending' : 'Cek ulang sebelum kirim',
    monthIn: isEn ? 'In' : 'Masuk',
    monthOut: isEn ? 'Out' : 'Keluar',
    waiting: isEn ? 'Waiting' : 'Menunggu',
    stepAmount: isEn ? 'Amount' : 'Nominal',
    stepAccount: isEn ? 'Account' : 'Rekening',
    stepReview: isEn ? 'Confirm' : 'Konfirmasi',
    eta: isEn ? 'Usually paid in 1–2 business days after approval.' : 'Estimasi cair 1–2 hari kerja setelah disetujui.',
  }), [isEn, pendingCount]);

  const handleWithdraw = async () => {
    if (!amountNumber || amountNumber < 10000) {
      Alert.alert(isEn ? 'Check amount' : 'Validasi', copy.min);
      return;
    }
    if (amountNumber > balance) {
      Alert.alert(isEn ? 'Check amount' : 'Validasi', copy.over);
      return;
    }
    if (!beneficiaryName.trim() || !beneficiaryBank || !beneficiaryAccount.trim()) {
      Alert.alert(
        isEn ? 'Missing details' : 'Validasi',
        isEn ? 'Fill in the destination account.' : 'Lengkapi data rekening penerima',
      );
      return;
    }

    setSubmitting(true);
    try {
      await submitWithdrawal(token, {
        amount: amountNumber,
        beneficiary_name: beneficiaryName.trim(),
        beneficiary_bank: beneficiaryBank,
        beneficiary_account: beneficiaryAccount.trim(),
        notes: notes.trim() || null,
      });
      notifySuccess(isEn ? 'Withdrawal requested.' : 'Permintaan withdraw diajukan.');
      setShowForm(false);
      setStep(1);
      setAmount('');
      setNotes('');
      setTab('withdrawals');
      await load(true);
    } catch (err) {
      notifyError(err.message || (isEn ? 'Could not submit withdrawal' : 'Tidak dapat mengajukan withdraw'));
    } finally {
      setSubmitting(false);
    }
  };

  if (loading && !refreshing) {
    return (
      <View style={styles.container}>
        <TabPageHeader variant="brand" title={copy.title} subtitle={copy.subtitle} />
        <SkeletonList count={3} style={styles.skeleton} />
      </View>
    );
  }

  if (error && ledger.length === 0 && balance === 0) {
    return (
      <View style={styles.container}>
        <TabPageHeader variant="brand" title={copy.title} subtitle={copy.subtitle} />
        <ErrorState description={error} onRetry={() => load()} />
      </View>
    );
  }

  return (
    <View style={styles.container}>
      <TabPageHeader variant="brand" title={copy.title} subtitle={copy.subtitle} />

      <ScrollView
        contentContainerStyle={styles.scroll}
        showsVerticalScrollIndicator={false}
        keyboardShouldPersistTaps="handled"
        refreshControl={
          <RefreshControl
            refreshing={refreshing}
            onRefresh={() => load(true)}
            tintColor={colors.baytgo}
          />
        }
      >
        <LinearGradient colors={gradients.primary} style={styles.balanceCard}>
          <View style={styles.orb} />
          <View style={styles.balanceTop}>
            <View style={styles.balanceCopy}>
              <Text style={styles.brand}>BaytGo Wallet</Text>
              <Text style={styles.balanceLabel}>{copy.available}</Text>
              <Text style={styles.balanceValue}>{formatMoney(balance)}</Text>
            </View>
            <View style={styles.chip}>
              <Wallet size={18} color={colors.baytgo} strokeWidth={2.2} />
            </View>
          </View>
          <View style={styles.statRow}>
            <View style={styles.statPill}>
              <Text style={styles.statLabel}>{copy.monthIn}</Text>
              <Text style={styles.statValue}>+{formatMoney(monthIn)}</Text>
            </View>
            <View style={styles.statPill}>
              <Text style={styles.statLabel}>{copy.monthOut}</Text>
              <Text style={styles.statValue}>−{formatMoney(monthOut)}</Text>
            </View>
            <View style={styles.statPill}>
              <Text style={styles.statLabel}>{copy.waiting}</Text>
              <Text style={styles.statValue}>{formatMoney(pendingAmount)}</Text>
            </View>
          </View>
          <Button
            label={showForm ? copy.closeForm : copy.withdraw}
            variant="secondary"
            size="sm"
            fullWidth={false}
            onPress={() => {
              setShowForm((v) => !v);
              setStep(1);
            }}
          />
        </LinearGradient>

        {showForm ? (
          <Animated.View entering={FadeInDown.springify().damping(18)} exiting={FadeOutUp.duration(160)}>
            <Card style={styles.formCard} padding={spacing.xl} elevated>
              <Text style={styles.formTitle}>{copy.formTitle}</Text>
              <View style={styles.stepper}>
                {[copy.stepAmount, copy.stepAccount, copy.stepReview].map((label, idx) => (
                  <PressableScale key={label} onPress={() => setStep(idx + 1)} style={styles.stepHit}>
                    <View style={[styles.stepBar, step >= idx + 1 && styles.stepBarOn]} />
                    <Text style={[styles.stepLabel, step === idx + 1 && styles.stepLabelOn]}>{label}</Text>
                  </PressableScale>
                ))}
              </View>

              {step === 1 ? (
                <>
                  <View style={styles.amountHead}>
                    <Text style={styles.fieldLabel}>{copy.amount}</Text>
                    {balance >= 10000 ? (
                      <PressableScale onPress={() => setAmount(formatDigits(String(Math.floor(balance))))} haptic="light">
                        <Text style={styles.allBtn}>{copy.all}</Text>
                      </PressableScale>
                    ) : null}
                  </View>
                  <TextInput
                    style={[styles.input, styles.amountInput]}
                    placeholder="Rp 0"
                    placeholderTextColor={colors.textMuted}
                    keyboardType="numeric"
                    value={amount}
                    onChangeText={(v) => setAmount(formatDigits(v))}
                  />
                  {quickAmounts.length > 0 ? (
                    <View style={styles.quickRow}>
                      {quickAmounts.map((n) => (
                        <PressableScale key={n} onPress={() => setAmount(formatDigits(String(n)))} haptic="light">
                          <View style={styles.quickChip}>
                            <Text style={styles.quickChipText}>{formatMoney(n)}</Text>
                          </View>
                        </PressableScale>
                      ))}
                    </View>
                  ) : null}
                  {amountNumber > 0 ? (
                    <Text style={[styles.remainText, remaining < 0 && styles.remainOver]}>
                      {remaining < 0 ? copy.over : `${copy.remaining}: ${formatMoney(remaining)}`}
                    </Text>
                  ) : (
                    <Text style={styles.minText}>{copy.min}</Text>
                  )}
                  <Button label={copy.next} onPress={() => canStep1 && setStep(2)} disabled={!canStep1} />
                </>
              ) : null}

              {step === 2 ? (
                <>
                  <TextInput
                    style={styles.input}
                    placeholder={copy.name}
                    placeholderTextColor={colors.textMuted}
                    value={beneficiaryName}
                    onChangeText={setBeneficiaryName}
                  />
                  <Text style={styles.fieldLabel}>{copy.bank}</Text>
                  <ScrollView horizontal showsHorizontalScrollIndicator={false} contentContainerStyle={styles.bankRow}>
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
                    placeholder={copy.account}
                    placeholderTextColor={colors.textMuted}
                    keyboardType="number-pad"
                    value={beneficiaryAccount}
                    onChangeText={setBeneficiaryAccount}
                  />
                  <TextInput
                    style={styles.input}
                    placeholder={copy.notes}
                    placeholderTextColor={colors.textMuted}
                    value={notes}
                    onChangeText={setNotes}
                  />
                  <View style={styles.stepActions}>
                    <View style={styles.stepAction}>
                      <Button label={copy.back} variant="secondary" onPress={() => setStep(1)} />
                    </View>
                    <View style={styles.stepAction}>
                      <Button label={copy.next} onPress={() => canStep2 && setStep(3)} disabled={!canStep2} />
                    </View>
                  </View>
                </>
              ) : null}

              {step === 3 ? (
                <>
                  <LinearGradient colors={gradients.primary} style={styles.reviewCard}>
                    <Text style={styles.reviewKicker}>{copy.review}</Text>
                    <Text style={styles.reviewAmount}>{formatMoney(amountNumber)}</Text>
                    <Text style={styles.reviewName}>{beneficiaryName}</Text>
                    <Text style={styles.reviewBank}>
                      {beneficiaryBank} · {beneficiaryAccount.replace(/\d(?=\d{4})/g, '•')}
                    </Text>
                  </LinearGradient>
                  <Text style={styles.formHint}>{copy.eta}</Text>
                  <View style={styles.stepActions}>
                    <View style={styles.stepAction}>
                      <Button label={copy.back} variant="secondary" onPress={() => setStep(2)} />
                    </View>
                    <View style={styles.stepAction}>
                      <Button label={copy.submit} onPress={handleWithdraw} loading={submitting} disabled={submitting || remaining < 0} />
                    </View>
                  </View>
                </>
              ) : null}
            </Card>
          </Animated.View>
        ) : null}

        <View style={styles.tabs}>
          <PressableScale onPress={() => setTab('ledger')} haptic="light" style={styles.tabHit}>
            <View style={[styles.tab, tab === 'ledger' && styles.tabActive]}>
              <Text style={[styles.tabLabel, tab === 'ledger' && styles.tabLabelActive]}>{copy.ledger}</Text>
            </View>
          </PressableScale>
          <PressableScale onPress={() => setTab('withdrawals')} haptic="light" style={styles.tabHit}>
            <View style={[styles.tab, tab === 'withdrawals' && styles.tabActive]}>
              <Text style={[styles.tabLabel, tab === 'withdrawals' && styles.tabLabelActive]}>{copy.history}</Text>
            </View>
          </PressableScale>
        </View>

        {tab === 'ledger' ? (
          ledger.length === 0 ? (
            <EmptyState variant="default" title={copy.emptyTitle} description={copy.emptyDesc} />
          ) : (
            ledger.map((entry, idx) => (
              <WalletLedgerRow key={`${entry.at}-${idx}`} entry={entry} />
            ))
          )
        ) : withdrawals.length === 0 ? (
          <EmptyState variant="default" title={copy.emptyWdTitle} description={copy.emptyWdDesc} />
        ) : (
          withdrawals.map((item) => {
            const meta = STATUS_META[item.status] || STATUS_META.pending_approval;
            return (
              <Card key={item.id} style={styles.wdCard} padding={spacing.lg} elevated={false} variant="flat">
                <View style={styles.wdRow}>
                  <View style={styles.wdIcon}>
                    {item.status === 'succeeded' ? (
                      <ArrowUpRight size={16} color={colors.success} strokeWidth={2.4} />
                    ) : (
                      <ArrowDownLeft size={16} color={colors.warning} strokeWidth={2.4} />
                    )}
                  </View>
                  <View style={styles.wdMeta}>
                    <Text style={styles.wdAmount}>{formatMoney(item.amount)}</Text>
                    <Text style={styles.wdBank}>
                      {item.beneficiary_bank} · {item.beneficiary_account}
                    </Text>
                    <View style={styles.timeline}>
                      {[1, 2, 3].map((n) => {
                        const done = item.status === 'failed' ? n === 1 : n <= (item.status === 'succeeded' ? 3 : item.status === 'processing' ? 2 : 1);
                        return <View key={n} style={[styles.timelineDot, done && styles.timelineDotOn]} />;
                      })}
                    </View>
                  </View>
                  <View style={[styles.statusPill, { backgroundColor: meta.bg }]}>
                    <Text style={[styles.statusText, { color: meta.color }]}>
                      {isEn ? meta.en : meta.id}
                    </Text>
                  </View>
                </View>
              </Card>
            );
          })
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
    paddingBottom: spacing['7xl'],
  },
  balanceCard: {
    borderRadius: radius.lg,
    padding: spacing.xl,
    marginBottom: spacing.lg,
    overflow: 'hidden',
  },
  orb: {
    position: 'absolute',
    right: -24,
    top: -28,
    width: 120,
    height: 120,
    borderRadius: 60,
    backgroundColor: 'rgba(197,160,89,0.18)',
  },
  brand: {
    ...typography.label,
    color: colors.gold,
    textTransform: 'uppercase',
    letterSpacing: 1.2,
    marginBottom: spacing.sm,
  },
  chip: {
    width: 44,
    height: 34,
    borderRadius: 8,
    backgroundColor: colors.gold,
    alignItems: 'center',
    justifyContent: 'center',
  },
  statRow: {
    flexDirection: 'row',
    gap: spacing.sm,
    marginBottom: spacing.lg,
  },
  statPill: {
    flex: 1,
    backgroundColor: 'rgba(255,255,255,0.1)',
    borderRadius: radius.sm,
    padding: spacing.sm,
  },
  statLabel: {
    ...typography.label,
    color: 'rgba(255,255,255,0.6)',
    textTransform: 'uppercase',
  },
  statValue: {
    ...typography.small,
    color: colors.white,
    marginTop: 4,
  },
  balanceTop: {
    flexDirection: 'row',
    justifyContent: 'space-between',
    alignItems: 'flex-start',
    marginBottom: spacing.lg,
  },
  balanceCopy: { flex: 1, paddingRight: spacing.md },
  balanceLabel: {
    ...typography.small,
    color: colors.goldLight,
    textTransform: 'uppercase',
    letterSpacing: 0.8,
  },
  balanceValue: {
    ...typography.hero,
    fontSize: 30,
    lineHeight: 38,
    color: colors.white,
    marginTop: spacing.sm,
  },
  pendingText: {
    ...typography.small,
    color: 'rgba(255,255,255,0.72)',
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
    marginBottom: spacing.md,
  },
  stepper: {
    flexDirection: 'row',
    gap: spacing.sm,
    marginBottom: spacing.lg,
  },
  stepHit: { flex: 1 },
  stepBar: {
    height: 4,
    borderRadius: 99,
    backgroundColor: colors.border,
  },
  stepBarOn: {
    backgroundColor: colors.gold,
  },
  stepLabel: {
    ...typography.label,
    color: colors.textMuted,
    marginTop: 6,
    textTransform: 'uppercase',
  },
  stepLabelOn: { color: colors.baytgo },
  amountInput: {
    fontSize: 28,
    fontFamily: 'PlusJakartaSans_800ExtraBold',
    minHeight: 64,
  },
  stepActions: {
    flexDirection: 'row',
    gap: spacing.sm,
  },
  stepAction: { flex: 1 },
  reviewCard: {
    borderRadius: radius.md,
    padding: spacing.xl,
    marginBottom: spacing.md,
  },
  reviewKicker: {
    ...typography.label,
    color: colors.gold,
    textTransform: 'uppercase',
  },
  reviewAmount: {
    ...typography.title,
    color: colors.white,
    marginTop: spacing.sm,
  },
  reviewName: {
    ...typography.caption,
    color: colors.white,
    marginTop: spacing.lg,
  },
  reviewBank: {
    ...typography.small,
    color: 'rgba(255,255,255,0.75)',
    marginTop: 4,
  },
  timeline: {
    flexDirection: 'row',
    gap: 6,
    marginTop: spacing.sm,
  },
  timelineDot: {
    width: 7,
    height: 7,
    borderRadius: 99,
    backgroundColor: colors.border,
  },
  timelineDotOn: {
    backgroundColor: colors.baytgo,
  },
  formHint: {
    ...typography.small,
    color: colors.textSecondary,
    marginTop: spacing.sm,
    marginBottom: spacing.lg,
    lineHeight: 18,
  },
  amountHead: {
    flexDirection: 'row',
    justifyContent: 'space-between',
    alignItems: 'center',
    marginBottom: spacing.sm,
  },
  fieldLabel: {
    ...typography.small,
    color: colors.textSecondary,
    marginBottom: spacing.sm,
  },
  allBtn: {
    ...typography.small,
    color: colors.baytgo,
    fontFamily: 'PlusJakartaSans_700Bold',
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
  quickRow: {
    flexDirection: 'row',
    flexWrap: 'wrap',
    gap: spacing.sm,
    marginBottom: spacing.md,
  },
  quickChip: {
    borderRadius: radius.full,
    backgroundColor: colors.baytgoLight,
    paddingHorizontal: spacing.md,
    paddingVertical: spacing.sm,
  },
  quickChipText: {
    ...typography.small,
    color: colors.baytgo,
  },
  remainText: {
    ...typography.small,
    color: colors.success,
    marginBottom: spacing.md,
  },
  remainOver: { color: colors.error },
  minText: {
    ...typography.small,
    color: colors.textMuted,
    marginBottom: spacing.md,
  },
  bankRow: {
    gap: spacing.sm,
    paddingRight: spacing.lg,
    marginBottom: spacing.md,
  },
  tabs: {
    flexDirection: 'row',
    backgroundColor: colors.surface,
    borderRadius: radius.full,
    padding: 4,
    marginBottom: spacing.lg,
  },
  tabHit: { flex: 1 },
  tab: {
    alignItems: 'center',
    paddingVertical: spacing.sm,
    borderRadius: radius.full,
  },
  tabActive: {
    backgroundColor: colors.white,
  },
  tabLabel: {
    ...typography.small,
    color: colors.textSecondary,
  },
  tabLabelActive: {
    color: colors.baytgo,
    fontFamily: 'PlusJakartaSans_700Bold',
  },
  wdCard: {
    borderRadius: radius.sm,
    marginBottom: spacing.sm,
  },
  wdRow: {
    flexDirection: 'row',
    alignItems: 'center',
    gap: spacing.md,
  },
  wdIcon: {
    width: 36,
    height: 36,
    borderRadius: 12,
    backgroundColor: colors.baytgoLight,
    alignItems: 'center',
    justifyContent: 'center',
  },
  wdMeta: { flex: 1 },
  wdAmount: {
    ...typography.caption,
    fontFamily: 'PlusJakartaSans_700Bold',
    color: colors.textPrimary,
  },
  wdBank: {
    ...typography.label,
    color: colors.textSecondary,
    marginTop: 2,
  },
  statusPill: {
    borderRadius: radius.full,
    paddingHorizontal: spacing.md,
    paddingVertical: spacing.xs,
  },
  statusText: {
    ...typography.label,
  },
});
