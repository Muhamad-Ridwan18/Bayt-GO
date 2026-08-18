import React, { useCallback, useMemo, useState } from 'react';
import {
  Alert,
  RefreshControl,
  ScrollView,
  Share,
  StyleSheet,
  Text,
  TextInput,
  View,
} from 'react-native';
import { useFocusEffect } from '@react-navigation/native';
import { LinearGradient } from 'expo-linear-gradient';
import { Link2, Share2, Wallet } from 'lucide-react-native';
import ScreenHeader from '../components/ScreenHeader';
import {
  deleteAffiliateBankAccount,
  fetchAffiliateDashboard,
  fetchAffiliateStatus,
  registerAffiliate,
  storeAffiliateBankAccount,
  storeAffiliateWithdrawal,
} from '../api/affiliate';
import { useAuth } from '../context/AuthContext';
import Button from '../ui/Button';
import Card from '../ui/Card';
import EmptyState from '../ui/EmptyState';
import ErrorState from '../ui/ErrorState';
import FilterChip from '../ui/FilterChip';
import { SkeletonList } from '../ui/Skeleton';
import StatTile from '../ui/StatTile';
import { colors, gradients, layout, radius, spacing, typography } from '../theme/tokens';
import { formatIdr } from '../utils/format';
import { notifyError, notifySuccess } from '../utils/feedback';
import { useLocale } from '../utils/locale';

export default function AffiliateScreen({ navigation }) {
  const locale = useLocale(); const isEn = locale === 'en';
  const { token, isAuthenticated } = useAuth();
  const [loading, setLoading] = useState(true);
  const [refreshing, setRefreshing] = useState(false);
  const [error, setError] = useState(null);
  const [registered, setRegistered] = useState(false);
  const [statusMeta, setStatusMeta] = useState(null);
  const [dashboard, setDashboard] = useState(null);
  const [registerCode, setRegisterCode] = useState('');
  const [submitting, setSubmitting] = useState(false);

  const [showBankForm, setShowBankForm] = useState(false);
  const [bankCode, setBankCode] = useState('BCA');
  const [accountHolder, setAccountHolder] = useState('');
  const [accountNumber, setAccountNumber] = useState('');

  const [showWithdraw, setShowWithdraw] = useState(false);
  const [withdrawAmount, setWithdrawAmount] = useState('');
  const [withdrawBankId, setWithdrawBankId] = useState('');
  const [withdrawNotes, setWithdrawNotes] = useState('');

  const load = useCallback(async (refresh = false) => {
    if (!token) {
      setLoading(false);
      setError(isEn ? 'Login required to access Affiliate' : 'Login diperlukan untuk membuka Affiliate');
      return;
    }
    if (refresh) setRefreshing(true);
    else setLoading(true);

    try {
      const status = await fetchAffiliateStatus(token);
      setRegistered(Boolean(status.registered));
      setStatusMeta(status);

      if (status.registered) {
        const dash = await fetchAffiliateDashboard(token);
        setDashboard(dash);
        const banks = dash.bank_accounts || [];
        const primary = banks.find((b) => b.is_primary) || banks[0];
        if (primary?.id) setWithdrawBankId(String(primary.id));
        const options = dash.bank_options || {};
        const keys = Object.keys(options);
        if (keys.length) setBankCode((prev) => (keys.includes(prev) ? prev : keys[0]));
      } else {
        setDashboard(null);
      }
      setError(null);
    } catch (err) {
      setError(err.message || (isEn ? 'Failed to load affiliate' : 'Gagal memuat affiliate'));
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

  const bankOptions = useMemo(() => dashboard?.bank_options || {}, [dashboard]);
  const bankKeys = Object.keys(bankOptions);
  const stats = dashboard?.stats || {};
  const affiliate = dashboard?.affiliate || statusMeta?.affiliate;

  const handleRegister = async () => {
    setSubmitting(true);
    try {
      await registerAffiliate(token, registerCode.trim());
      notifySuccess(isEn ? 'Affiliate activated' : 'Affiliate diaktifkan');
      await load(true);
    } catch (err) {
      notifyError(err.message || (isEn ? 'Registration failed' : 'Gagal mendaftar'));
    } finally {
      setSubmitting(false);
    }
  };

  const handleShare = async () => {
    const url = dashboard?.share_url || affiliate?.share_url;
    const code = affiliate?.code;
    if (!url && !code) return;
    try {
      await Share.share({
        message: isEn ? `Join BaytGo via my link: ${url || code}` : `Gabung BaytGo lewat tautan saya: ${url || code}`,
      });
    } catch {
      // ignore
    }
  };

  const handleAddBank = async () => {
    if (!accountHolder.trim() || !accountNumber.trim() || !bankCode) {
      Alert.alert(isEn ? 'Validation' : 'Validasi', isEn ? 'Complete the bank account details' : 'Lengkapi data rekening');
      return;
    }
    setSubmitting(true);
    try {
      await storeAffiliateBankAccount(token, {
        bank_code: bankCode,
        account_holder: accountHolder.trim(),
        account_number: accountNumber.replace(/\D/g, ''),
        is_primary: true,
      });
      notifySuccess(isEn ? 'Bank account added' : 'Rekening ditambahkan');
      setShowBankForm(false);
      setAccountHolder('');
      setAccountNumber('');
      await load(true);
    } catch (err) {
      notifyError(err.message || (isEn ? 'Failed to add account' : 'Gagal menambah rekening'));
    } finally {
      setSubmitting(false);
    }
  };

  const handleDeleteBank = (bank) => {
    Alert.alert(isEn ? 'Delete account' : 'Hapus rekening', `${isEn ? 'Delete' : 'Hapus'} ${bank.bank_name} · ${bank.account_number}?`, [
      { text: isEn ? 'Cancel' : 'Batal', style: 'cancel' },
      {
        text: isEn ? 'Delete' : 'Hapus',
        style: 'destructive',
        onPress: async () => {
          try {
            await deleteAffiliateBankAccount(token, bank.id);
            notifySuccess(isEn ? 'Account deleted' : 'Rekening dihapus');
            await load(true);
          } catch (err) {
            notifyError(err.message || (isEn ? 'Failed to delete' : 'Gagal menghapus'));
          }
        },
      },
    ]);
  };

  const handleWithdraw = async () => {
    const amount = Number(String(withdrawAmount).replace(/\D/g, ''));
    const min = Number(stats.min_withdraw || 0);
    if (!amount || amount < min) {
      Alert.alert(isEn ? 'Validation' : 'Validasi', isEn ? `Minimum withdrawal ${formatIdr(min)}` : `Minimum penarikan ${formatIdr(min)}`);
      return;
    }
    if (!withdrawBankId) {
      Alert.alert(isEn ? 'Validation' : 'Validasi', isEn ? 'Select a destination account' : 'Pilih rekening tujuan');
      return;
    }
    setSubmitting(true);
    try {
      await storeAffiliateWithdrawal(token, {
        amount,
        bank_account_id: withdrawBankId,
        notes: withdrawNotes.trim() || null,
      });
      notifySuccess(isEn ? 'Withdrawal submitted' : 'Withdraw diajukan');
      setShowWithdraw(false);
      setWithdrawAmount('');
      setWithdrawNotes('');
      await load(true);
    } catch (err) {
      notifyError(err.message || (isEn ? 'Failed to submit withdrawal' : 'Gagal mengajukan withdraw'));
    } finally {
      setSubmitting(false);
    }
  };

  if (!isAuthenticated) {
    return (
      <View style={styles.flex}>
        <ScreenHeader title="Affiliate" onBack={() => navigation.goBack()} />
        <EmptyState
          title={isEn ? 'Login required' : 'Login diperlukan'}
          description={isEn ? 'Sign in to register and manage your affiliate.' : 'Masuk untuk mendaftar dan mengelola affiliate.'}
        />
      </View>
    );
  }

  return (
    <View style={styles.flex}>
      <ScreenHeader title="Affiliate" subtitle={isEn ? 'Referral commission' : 'Komisi referral'} onBack={() => navigation.goBack()} />

      {loading ? <SkeletonList count={4} /> : null}
      {!loading && error ? <ErrorState description={error} onRetry={() => load()} /> : null}

      {!loading && !error && !registered ? (
        <ScrollView contentContainerStyle={styles.content} keyboardShouldPersistTaps="handled">
          <LinearGradient colors={gradients.primary} style={styles.hero}>
            <Text style={styles.heroTitle}>{isEn ? 'Become a BaytGo Affiliate' : 'Jadi Affiliate BaytGo'}</Text>
            <Text style={styles.heroSub}>
              {isEn ? 'Share your referral code and earn commissions from successful bookings.' : 'Bagikan kode referral dan dapatkan komisi dari booking yang sukses.'}
            </Text>
            <Text style={styles.heroMeta}>
              Rate default {(Number(statusMeta?.default_rate || 0) * 100).toFixed(1)}% · Min withdraw{' '}
              {formatIdr(statusMeta?.min_withdraw || 0)}
            </Text>
          </LinearGradient>

          <Card style={styles.card}>
            <Text style={styles.label}>{isEn ? 'Custom code (optional)' : 'Kode kustom (opsional)'}</Text>
            <TextInput
              value={registerCode}
              onChangeText={setRegisterCode}
              placeholder={isEn ? 'e.g. BAYTGO01' : 'Contoh: BAYTGO01'}
              autoCapitalize="characters"
              style={styles.input}
              placeholderTextColor={colors.textMuted}
            />
            <Button label={isEn ? 'Activate Affiliate' : 'Aktifkan Affiliate'} onPress={handleRegister} loading={submitting} />
          </Card>
        </ScrollView>
      ) : null}

      {!loading && !error && registered ? (
        <ScrollView
          contentContainerStyle={styles.content}
          keyboardShouldPersistTaps="handled"
          refreshControl={<RefreshControl refreshing={refreshing} onRefresh={() => load(true)} />}
        >
          <LinearGradient colors={gradients.primary} style={styles.hero}>
            <Text style={styles.heroEyebrow}>{stats.level_label || affiliate?.level_label || 'Affiliate'}</Text>
            <Text style={styles.heroTitle}>{affiliate?.code}</Text>
            <Text style={styles.heroSub}>{dashboard?.share_url}</Text>
            <View style={styles.heroActions}>
              <View style={styles.heroBtn}>
                <Button
                  label={isEn ? 'Share' : 'Bagikan'}
                  onPress={handleShare}
                  variant="secondary"
                  size="sm"
                  icon={<Share2 size={16} color={colors.baytgo} />}
                />
              </View>
            </View>
          </LinearGradient>

          <View style={styles.statsRow}>
            <StatTile label={isEn ? 'Balance' : 'Saldo'} value={formatIdr(stats.available_balance || 0)} icon={Wallet} color={colors.baytgo} />
            <StatTile label="Pending" value={formatIdr(stats.pending_commission || 0)} icon={Link2} color={colors.goldMuted} />
          </View>
          <View style={styles.statsRow}>
            <StatTile label={isEn ? 'Successful bookings' : 'Booking sukses'} value={String(stats.success_booking || 0)} color={colors.baytgo} />
            <StatTile label={isEn ? 'Clicks' : 'Klik'} value={String(stats.total_clicks || 0)} color={colors.baytgo} />
          </View>

          <Card style={styles.card}>
            <Text style={styles.sectionTitle}>{isEn ? 'Bank Accounts' : 'Rekening'}</Text>
            {(dashboard?.bank_accounts || []).length === 0 ? (
              <Text style={styles.muted}>{isEn ? 'No bank accounts yet. Add one to withdraw.' : 'Belum ada rekening. Tambahkan untuk withdraw.'}</Text>
            ) : (
              (dashboard?.bank_accounts || []).map((bank) => (
                <View key={bank.id} style={styles.bankRow}>
                  <View style={styles.flex}>
                    <Text style={styles.bankName}>{bank.bank_name}</Text>
                    <Text style={styles.muted}>{bank.account_holder} · {bank.account_number}</Text>
                  </View>
                  <Button label={isEn ? 'Delete' : 'Hapus'} variant="ghost" size="sm" fullWidth={false} onPress={() => handleDeleteBank(bank)} />
                </View>
              ))
            )}
            <Button
              label={showBankForm ? (isEn ? 'Close form' : 'Tutup form') : (isEn ? 'Add account' : 'Tambah rekening')}
              variant="secondary"
              onPress={() => setShowBankForm((v) => !v)}
            />
            {showBankForm ? (
              <View style={styles.formBlock}>
                <Text style={styles.label}>{isEn ? 'Bank' : 'Bank'}</Text>
                <ScrollView horizontal showsHorizontalScrollIndicator={false} contentContainerStyle={styles.chips}>
                  {bankKeys.map((key) => (
                    <FilterChip
                      key={key}
                      label={bankOptions[key] || key}
                      active={bankCode === key}
                      onPress={() => setBankCode(key)}
                    />
                  ))}
                </ScrollView>
                <TextInput
                  value={accountHolder}
                  onChangeText={setAccountHolder}
                  placeholder={isEn ? 'Account holder name' : 'Nama pemilik rekening'}
                  style={styles.input}
                  placeholderTextColor={colors.textMuted}
                />
                <TextInput
                  value={accountNumber}
                  onChangeText={setAccountNumber}
                  placeholder={isEn ? 'Account number' : 'Nomor rekening'}
                  keyboardType="number-pad"
                  style={styles.input}
                  placeholderTextColor={colors.textMuted}
                />
                <Button label={isEn ? 'Save account' : 'Simpan rekening'} onPress={handleAddBank} loading={submitting} />
              </View>
            ) : null}
          </Card>

          <Card style={styles.card}>
            <Text style={styles.sectionTitle}>{isEn ? 'Withdraw' : 'Withdraw'}</Text>
            <Text style={styles.muted}>Min. {formatIdr(stats.min_withdraw || 0)}</Text>
            <Button
              label={showWithdraw ? (isEn ? 'Close form' : 'Tutup form') : (isEn ? 'Request withdrawal' : 'Ajukan withdraw')}
              onPress={() => setShowWithdraw((v) => !v)}
            />
            {showWithdraw ? (
              <View style={styles.formBlock}>
                <Text style={styles.label}>{isEn ? 'Destination account' : 'Rekening tujuan'}</Text>
                <ScrollView horizontal showsHorizontalScrollIndicator={false} contentContainerStyle={styles.chips}>
                  {(dashboard?.bank_accounts || []).map((bank) => (
                    <FilterChip
                      key={bank.id}
                      label={`${bank.bank_name} ·${String(bank.account_number).slice(-4)}`}
                      active={withdrawBankId === String(bank.id)}
                      onPress={() => setWithdrawBankId(String(bank.id))}
                    />
                  ))}
                </ScrollView>
                <TextInput
                  value={withdrawAmount}
                  onChangeText={setWithdrawAmount}
                  placeholder={isEn ? 'Amount' : 'Nominal'}
                  keyboardType="number-pad"
                  style={styles.input}
                  placeholderTextColor={colors.textMuted}
                />
                <TextInput
                  value={withdrawNotes}
                  onChangeText={setWithdrawNotes}
                  placeholder={isEn ? 'Notes (optional)' : 'Catatan (opsional)'}
                  style={styles.input}
                  placeholderTextColor={colors.textMuted}
                />
                <Button label={isEn ? 'Submit request' : 'Kirim permintaan'} onPress={handleWithdraw} loading={submitting} />
              </View>
            ) : null}
          </Card>

          <Card style={styles.card}>
            <Text style={styles.sectionTitle}>{isEn ? 'Recent commissions' : 'Komisi terbaru'}</Text>
            {(dashboard?.commissions || []).length === 0 ? (
              <Text style={styles.muted}>{isEn ? 'No commissions yet.' : 'Belum ada komisi.'}</Text>
            ) : (
              (dashboard?.commissions || []).slice(0, 8).map((row) => (
                <View key={row.id} style={styles.listRow}>
                  <View style={styles.flex}>
                    <Text style={styles.bankName}>{row.booking?.booking_code || row.id}</Text>
                    <Text style={styles.muted}>{row.status}</Text>
                  </View>
                  <Text style={styles.amount}>{formatIdr(row.commission_amount)}</Text>
                </View>
              ))
            )}
          </Card>
        </ScrollView>
      ) : null}
    </View>
  );
}

const styles = StyleSheet.create({
  flex: { flex: 1, backgroundColor: colors.background },
  content: {
    paddingHorizontal: layout.screenPadding,
    paddingBottom: spacing.xxl,
    gap: spacing.md,
  },
  hero: {
    borderRadius: radius.lg,
    padding: spacing.xl,
    gap: spacing.sm,
  },
  heroEyebrow: {
    ...typography.caption,
    color: 'rgba(255,255,255,0.75)',
    fontWeight: '700',
  },
  heroTitle: {
    ...typography.title,
    color: colors.white,
    fontFamily: 'PlusJakartaSans_800ExtraBold',
  },
  heroSub: {
    ...typography.caption,
    color: 'rgba(255,255,255,0.85)',
  },
  heroMeta: {
    ...typography.small,
    color: 'rgba(255,255,255,0.7)',
    marginTop: spacing.sm,
  },
  heroActions: {
    flexDirection: 'row',
    gap: spacing.sm,
    marginTop: spacing.md,
  },
  heroBtn: { flex: 1 },
  statsRow: {
    flexDirection: 'row',
    gap: spacing.md,
  },
  card: { gap: spacing.sm },
  sectionTitle: {
    ...typography.subtitle,
    color: colors.textPrimary,
    fontFamily: 'PlusJakartaSans_700Bold',
  },
  label: {
    ...typography.caption,
    color: colors.textSecondary,
    fontWeight: '600',
  },
  muted: {
    ...typography.caption,
    color: colors.textMuted,
  },
  input: {
    borderWidth: 1,
    borderColor: colors.border,
    borderRadius: radius.md,
    paddingHorizontal: spacing.md,
    paddingVertical: spacing.md,
    backgroundColor: colors.card,
    color: colors.textPrimary,
    ...typography.body,
  },
  formBlock: { gap: spacing.sm, marginTop: spacing.sm },
  chips: { gap: spacing.sm, paddingVertical: spacing.xs },
  bankRow: {
    flexDirection: 'row',
    alignItems: 'center',
    gap: spacing.sm,
    paddingVertical: spacing.sm,
    borderBottomWidth: StyleSheet.hairlineWidth,
    borderBottomColor: colors.border,
  },
  bankName: {
    ...typography.caption,
    fontWeight: '700',
    color: colors.textPrimary,
  },
  listRow: {
    flexDirection: 'row',
    alignItems: 'center',
    justifyContent: 'space-between',
    paddingVertical: spacing.sm,
    borderBottomWidth: StyleSheet.hairlineWidth,
    borderBottomColor: colors.border,
  },
  amount: {
    ...typography.caption,
    fontWeight: '700',
    color: colors.baytgo,
  },
});
