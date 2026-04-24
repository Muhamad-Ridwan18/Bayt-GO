import React, { useState, useEffect } from 'react';
import { 
  StyleSheet, 
  Text, 
  View, 
  TouchableOpacity, 
  ScrollView, 
  ActivityIndicator, 
  RefreshControl,
  Modal,
  TextInput,
  Alert,
  FlatList,
  Dimensions
} from 'react-native';
import { SafeAreaView } from 'react-native-safe-area-context';
import { StatusBar } from 'expo-status-bar';
import { Ionicons } from '@expo/vector-icons';
import { LinearGradient } from 'expo-linear-gradient';
import { apiClient } from '../api/client';

const { width } = Dimensions.get('window');

export default function WalletScreen({ user, navigation }) {
  const [loading, setLoading] = useState(true);
  const [refreshing, setRefreshing] = useState(false);
  const [activeTab, setActiveTab] = useState('ledger'); // ledger, withdrawals
  const [walletData, setWalletData] = useState({
    balance: 0,
    ledger: [],
    withdrawals: [],
    bank_options: {}
  });

  // Modal State
  const [modalVisible, setModalVisible] = useState(false);
  const [withdrawAmount, setWithdrawAmount] = useState('');
  const [beneficiaryName, setBeneficiaryName] = useState(user?.user?.name || '');
  const [beneficiaryBank, setBeneficiaryBank] = useState('');
  const [beneficiaryAccount, setBeneficiaryAccount] = useState('');
  const [notes, setNotes] = useState('');
  const [submitting, setSubmitting] = useState(false);

  const fetchData = async () => {
    try {
      const data = await apiClient.getWalletData(user.token);
      setWalletData(data);
    } catch (error) {
      console.error('Fetch Wallet Error:', error);
      Alert.alert('Error', 'Gagal memuat data dompet.');
    } finally {
      setLoading(false);
      setRefreshing(false);
    }
  };

  useEffect(() => {
    fetchData();
  }, []);

  const handleWithdraw = async () => {
    if (!withdrawAmount || !beneficiaryBank || !beneficiaryAccount || !beneficiaryName) {
      Alert.alert('Perhatian', 'Harap lengkapi semua data wajib.');
      return;
    }

    if (parseFloat(withdrawAmount) > walletData.balance) {
      Alert.alert('Gagal', 'Saldo tidak mencukupi.');
      return;
    }

    setSubmitting(true);
    try {
      const payload = {
        amount: withdrawAmount,
        beneficiary_name: beneficiaryName,
        beneficiary_bank: beneficiaryBank,
        beneficiary_account: beneficiaryAccount,
        notes: notes
      };
      await apiClient.requestWithdrawal(user.token, payload);
      Alert.alert('Sukses', 'Permintaan withdraw telah dikirim dan menunggu persetujuan admin.');
      setModalVisible(false);
      // Reset form
      setWithdrawAmount('');
      setNotes('');
      fetchData();
    } catch (error) {
      Alert.alert('Gagal', error.message);
    } finally {
      setSubmitting(false);
    }
  };

  const formatCurrency = (val) => {
    return 'Rp ' + (val || 0).toString().replace(/\B(?=(\d{3})+(?!\d))/g, ".");
  };

  const formatDate = (dateStr) => {
    const d = new Date(dateStr);
    return `${d.getDate()}/${d.getMonth() + 1}/${d.getFullYear()} ${d.getHours().toString().padStart(2, '0')}:${d.getMinutes().toString().padStart(2, '0')}`;
  };

  if (loading) {
    return <View style={styles.center}><ActivityIndicator size="large" color="#0984e3" /></View>;
  }

  return (
    <SafeAreaView style={styles.container}>
      <StatusBar style="dark" />
      
      {/* Header */}
      <View style={styles.header}>
        <TouchableOpacity onPress={() => navigation.goBack()} style={styles.backBtn}>
          <Ionicons name="arrow-back" size={24} color="#1E293B" />
        </TouchableOpacity>
        <Text style={styles.headerTitle}>Dompet Saya</Text>
        <TouchableOpacity style={styles.helpBtn}>
          <Ionicons name="help-circle-outline" size={24} color="#64748B" />
        </TouchableOpacity>
      </View>

      <ScrollView 
        showsVerticalScrollIndicator={false}
        refreshControl={<RefreshControl refreshing={refreshing} onRefresh={fetchData} color="#0984e3" />}
      >
        {/* Balance Card */}
        <LinearGradient
          colors={['#0984e3', '#00cec9']}
          start={{ x: 0, y: 0 }}
          end={{ x: 1, y: 1 }}
          style={styles.balanceCard}
        >
          <View style={styles.balanceHeader}>
            <View>
              <Text style={styles.balanceLabel}>SALDO TERSEDIA</Text>
              <Text style={styles.balanceValue}>{formatCurrency(walletData.balance)}</Text>
            </View>
            <View style={styles.balanceIconBg}>
              <Ionicons name="wallet" size={32} color="#FFFFFF" />
            </View>
          </View>
          <TouchableOpacity 
            style={styles.withdrawBtn}
            onPress={() => setModalVisible(true)}
          >
            <Text style={styles.withdrawBtnText}>Tarik Saldo</Text>
            <Ionicons name="arrow-forward" size={16} color="#0984e3" />
          </TouchableOpacity>
        </LinearGradient>

        {/* Tabs */}
        <View style={styles.tabContainer}>
          <TouchableOpacity 
            style={[styles.tab, activeTab === 'ledger' && styles.tabActive]}
            onPress={() => setActiveTab('ledger')}
          >
            <Text style={[styles.tabText, activeTab === 'ledger' && styles.tabTextActive]}>Transaksi</Text>
          </TouchableOpacity>
          <TouchableOpacity 
            style={[styles.tab, activeTab === 'withdrawals' && styles.tabActive]}
            onPress={() => setActiveTab('withdrawals')}
          >
            <Text style={[styles.tabText, activeTab === 'withdrawals' && styles.tabTextActive]}>Withdraw</Text>
          </TouchableOpacity>
        </View>

        {/* List Content */}
        <View style={styles.listContainer}>
          {activeTab === 'ledger' ? (
            walletData.ledger.length > 0 ? (
              walletData.ledger.map((item, idx) => (
                <View key={idx} style={styles.itemCard}>
                  <View style={styles.itemLeft}>
                    <View style={[styles.itemIcon, { backgroundColor: item.signed_amount > 0 ? '#ECFDF5' : '#FEF2F2' }]}>
                      <Ionicons 
                        name={item.signed_amount > 0 ? "trending-up" : "trending-down"} 
                        size={20} 
                        color={item.signed_amount > 0 ? "#10B981" : "#EF4444"} 
                      />
                    </View>
                    <View style={{ flex: 1 }}>
                      <Text style={styles.itemTitle} numberOfLines={1}>
                        {item.kind === 'booking_credit' ? `Booking ${item.booking_code}` : 
                         item.kind === 'withdraw_debit' ? 'Penarikan Saldo' : 
                         item.kind === 'withdraw_refund' ? 'Refund Penarikan' : 'Lainnya'}
                      </Text>
                      <Text style={styles.itemDate}>{formatDate(item.at)}</Text>
                    </View>
                  </View>
                  <Text style={[styles.itemAmount, { color: item.signed_amount > 0 ? '#10B981' : '#EF4444' }]}>
                    {item.signed_amount > 0 ? '+' : ''}{formatCurrency(item.signed_amount)}
                  </Text>
                </View>
              ))
            ) : (
              <View style={styles.emptyState}>
                <Ionicons name="receipt-outline" size={64} color="#E2E8F0" />
                <Text style={styles.emptyText}>Belum ada riwayat transaksi.</Text>
              </View>
            )
          ) : (
            walletData.withdrawals.length > 0 ? (
              walletData.withdrawals.map((item, idx) => (
                <View key={idx} style={styles.itemCard}>
                  <View style={styles.itemLeft}>
                    <View style={styles.itemIcon}>
                      <Ionicons name="card-outline" size={20} color="#64748B" />
                    </View>
                    <View style={{ flex: 1 }}>
                      <Text style={styles.itemTitle} numberOfLines={1}>{item.beneficiary_bank}</Text>
                      <Text style={styles.itemDate}>{formatDate(item.requested_at)}</Text>
                    </View>
                  </View>
                  <View style={{ alignItems: 'flex-end' }}>
                    <Text style={styles.itemAmount}>{formatCurrency(item.amount)}</Text>
                    <View style={[styles.statusBadge, { 
                      backgroundColor: item.status === 'succeeded' ? '#ECFDF5' : 
                                      item.status === 'pending_approval' ? '#FFFBEB' : '#FEF2F2' 
                    }]}>
                      <Text style={[styles.statusText, { 
                        color: item.status === 'succeeded' ? '#059669' : 
                               item.status === 'pending_approval' ? '#D97706' : '#DC2626' 
                      }]}>{item.status}</Text>
                    </View>
                  </View>
                </View>
              ))
            ) : (
              <View style={styles.emptyState}>
                <Ionicons name="list-outline" size={64} color="#E2E8F0" />
                <Text style={styles.emptyText}>Belum ada riwayat withdraw.</Text>
              </View>
            )
          )}
        </View>
      </ScrollView>

      {/* Withdrawal Modal */}
      <Modal
        animationType="slide"
        transparent={true}
        visible={modalVisible}
        onRequestClose={() => setModalVisible(false)}
      >
        <View style={styles.modalOverlay}>
          <View style={styles.modalContent}>
            <View style={styles.modalHeader}>
              <Text style={styles.modalTitle}>Tarik Saldo</Text>
              <TouchableOpacity onPress={() => setModalVisible(false)}>
                <Ionicons name="close" size={24} color="#1E293B" />
              </TouchableOpacity>
            </View>

            <ScrollView showsVerticalScrollIndicator={false}>
              <View style={styles.formGroup}>
                <Text style={styles.label}>NOMINAL PENARIKAN (RP)</Text>
                <TextInput 
                  style={styles.input} 
                  placeholder="Contoh: 1000000" 
                  value={withdrawAmount} 
                  onChangeText={setWithdrawAmount} 
                  keyboardType="number-pad" 
                />
                <Text style={styles.hint}>Min. penarikan Rp 10.000</Text>
              </View>

              <View style={styles.formGroup}>
                <Text style={styles.label}>NAMA PENERIMA</Text>
                <TextInput 
                  style={styles.input} 
                  placeholder="Nama pemilik rekening" 
                  value={beneficiaryName} 
                  onChangeText={setBeneficiaryName} 
                />
              </View>

              <View style={styles.formGroup}>
                <Text style={styles.label}>BANK TUJUAN</Text>
                <View style={styles.pickerContainer}>
                  {Object.keys(walletData.bank_options).map((key) => (
                    <TouchableOpacity 
                      key={key} 
                      style={[styles.bankOption, beneficiaryBank === key && styles.bankOptionActive]}
                      onPress={() => setBeneficiaryBank(key)}
                    >
                      <Text style={[styles.bankText, beneficiaryBank === key && styles.bankTextActive]}>{key}</Text>
                    </TouchableOpacity>
                  ))}
                </View>
              </View>

              <View style={styles.formGroup}>
                <Text style={styles.label}>NOMOR REKENING</Text>
                <TextInput 
                  style={styles.input} 
                  placeholder="Masukkan nomor rekening" 
                  value={beneficiaryAccount} 
                  onChangeText={setBeneficiaryAccount} 
                  keyboardType="number-pad" 
                />
              </View>

              <View style={styles.formGroup}>
                <Text style={styles.label}>CATATAN (OPSIONAL)</Text>
                <TextInput 
                  style={styles.input} 
                  placeholder="Tambahkan pesan..." 
                  value={notes} 
                  onChangeText={setNotes} 
                />
              </View>

              <TouchableOpacity 
                style={styles.submitBtn} 
                onPress={handleWithdraw}
                disabled={submitting}
              >
                {submitting ? (
                  <ActivityIndicator color="#FFFFFF" />
                ) : (
                  <Text style={styles.submitBtnText}>Ajukan Penarikan</Text>
                )}
              </TouchableOpacity>
              <View style={{ height: 20 }} />
            </ScrollView>
          </View>
        </View>
      </Modal>
    </SafeAreaView>
  );
}

const styles = StyleSheet.create({
  container: { flex: 1, backgroundColor: '#FFFFFF' },
  center: { flex: 1, justifyContent: 'center', alignItems: 'center' },
  header: { 
    flexDirection: 'row', 
    justifyContent: 'space-between', 
    alignItems: 'center', 
    paddingHorizontal: 20, 
    paddingVertical: 15,
    borderBottomWidth: 1,
    borderBottomColor: '#F1F5F9'
  },
  headerTitle: { fontSize: 18, fontWeight: '800', color: '#1E293B' },
  backBtn: { padding: 5 },
  helpBtn: { padding: 5 },

  balanceCard: {
    margin: 20,
    borderRadius: 24,
    padding: 25,
    shadowColor: '#0984e3',
    shadowOffset: { width: 0, height: 10 },
    shadowOpacity: 0.2,
    shadowRadius: 15,
    elevation: 10,
  },
  balanceHeader: { flexDirection: 'row', justifyContent: 'space-between', alignItems: 'flex-start' },
  balanceLabel: { color: 'rgba(255,255,255,0.7)', fontSize: 12, fontWeight: '800', letterSpacing: 1 },
  balanceValue: { color: '#FFFFFF', fontSize: 32, fontWeight: '900', marginTop: 8 },
  balanceIconBg: { backgroundColor: 'rgba(255,255,255,0.2)', padding: 12, borderRadius: 16 },
  withdrawBtn: { 
    backgroundColor: '#FFFFFF', 
    alignSelf: 'flex-start', 
    paddingHorizontal: 20, 
    paddingVertical: 12, 
    borderRadius: 14, 
    marginTop: 25,
    flexDirection: 'row',
    alignItems: 'center',
    gap: 8
  },
  withdrawBtnText: { color: '#0984e3', fontSize: 14, fontWeight: '800' },

  tabContainer: { flexDirection: 'row', paddingHorizontal: 20, marginBottom: 15 },
  tab: { marginRight: 25, paddingVertical: 10, borderBottomWidth: 3, borderBottomColor: 'transparent' },
  tabActive: { borderBottomColor: '#0984e3' },
  tabText: { fontSize: 16, fontWeight: '700', color: '#94A3B8' },
  tabTextActive: { color: '#0984e3' },

  listContainer: { paddingHorizontal: 20 },
  itemCard: { 
    flexDirection: 'row', 
    justifyContent: 'space-between', 
    alignItems: 'center', 
    paddingVertical: 15,
    borderBottomWidth: 1,
    borderBottomColor: '#F8FAFC'
  },
  itemLeft: { flexDirection: 'row', alignItems: 'center', gap: 15, flex: 1, marginRight: 10 },
  itemIcon: { width: 45, height: 45, borderRadius: 14, justifyContent: 'center', alignItems: 'center' },
  itemTitle: { fontSize: 15, fontWeight: '700', color: '#1E293B' },
  itemDate: { fontSize: 12, color: '#94A3B8', marginTop: 2 },
  itemAmount: { fontSize: 15, fontWeight: '800' },

  statusBadge: { paddingHorizontal: 8, paddingVertical: 4, borderRadius: 6, marginTop: 4 },
  statusText: { fontSize: 10, fontWeight: '800', textTransform: 'capitalize' },

  emptyState: { alignItems: 'center', paddingVertical: 60, gap: 15 },
  emptyText: { color: '#94A3B8', fontSize: 14, fontWeight: '600' },

  modalOverlay: { flex: 1, backgroundColor: 'rgba(0,0,0,0.5)', justifyContent: 'flex-end' },
  modalContent: { 
    backgroundColor: '#FFFFFF', 
    borderTopLeftRadius: 30, 
    borderTopRightRadius: 30, 
    padding: 25, 
    maxHeight: '90%' 
  },
  modalHeader: { flexDirection: 'row', justifyContent: 'space-between', alignItems: 'center', marginBottom: 25 },
  modalTitle: { fontSize: 20, fontWeight: '800', color: '#1E293B' },
  formGroup: { marginBottom: 20 },
  label: { fontSize: 11, fontWeight: '800', color: '#94A3B8', letterSpacing: 1, marginBottom: 10 },
  input: { backgroundColor: '#F8FAFC', borderWidth: 1, borderColor: '#E2E8F0', padding: 15, borderRadius: 14, fontSize: 16, color: '#1E293B' },
  hint: { fontSize: 11, color: '#94A3B8', marginTop: 5, marginLeft: 5 },
  
  pickerContainer: { flexDirection: 'row', flexWrap: 'wrap', gap: 8 },
  bankOption: { paddingHorizontal: 12, paddingVertical: 8, borderRadius: 10, borderWidth: 1, borderColor: '#E2E8F0', backgroundColor: '#F8FAFC' },
  bankOptionActive: { borderColor: '#0984e3', backgroundColor: '#E0F2FE' },
  bankText: { fontSize: 12, fontWeight: '700', color: '#64748B' },
  bankTextActive: { color: '#0984e3' },

  submitBtn: { backgroundColor: '#0984e3', paddingVertical: 18, borderRadius: 18, alignItems: 'center', marginTop: 10 },
  submitBtnText: { color: '#FFFFFF', fontSize: 16, fontWeight: '800' }
});
