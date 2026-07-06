import React, { useCallback, useState } from 'react';
import {
  View,
  Text,
  StyleSheet,
  ScrollView,
  TouchableOpacity,
  ActivityIndicator,
  RefreshControl,
  Alert,
  Modal,
  TextInput,
} from 'react-native';
import { useFocusEffect } from '@react-navigation/native';
import { Ionicons } from '@expo/vector-icons';
import ScreenHeader from '../components/ScreenHeader';
import {
  fetchMuthowifBooking,
  confirmMuthowifBooking,
  cancelMuthowifBooking,
  approveReschedule,
  rejectReschedule,
  approveSupportCompletion,
  rejectSupportCompletion,
} from '../api/muthowifBookings';
import { openBookingDocument } from '../utils/openDocument';
import { useAuth } from '../context/AuthContext';
import { colors } from '../theme/colors';
import { formatIdr } from '../utils/format';
import {
  bookingStatusMeta,
  paymentStatusMeta,
  serviceTypeLabel,
  formatDateRange,
  changeRequestStatusLabel,
} from '../utils/bookingLabels';

function InfoRow({ label, value }) {
  return (
    <View style={styles.infoRow}>
      <Text style={styles.infoLabel}>{label}</Text>
      <Text style={styles.infoValue}>{value}</Text>
    </View>
  );
}

function ActionBtn({ icon, label, onPress, variant = 'outline', danger }) {
  const isPrimary = variant === 'primary';
  return (
    <TouchableOpacity
      style={[styles.actionBtn, isPrimary && styles.actionPrimary, danger && styles.actionDanger]}
      onPress={onPress}
      activeOpacity={0.9}
    >
      <Ionicons
        name={icon}
        size={18}
        color={isPrimary ? colors.white : danger ? '#B91C1C' : colors.baytgo}
      />
      <Text style={[styles.actionText, isPrimary && styles.actionTextPrimary, danger && styles.actionTextDanger]}>
        {label}
      </Text>
    </TouchableOpacity>
  );
}

const REJECTION_OPTIONS = [
  { value: 'jadwal_full', label: 'Jadwal penuh' },
  { value: 'illness', label: 'Sakit' },
  { value: 'force_majeure', label: 'Force majeure' },
  { value: 'other', label: 'Lainnya' },
];

export default function MuthowifBookingDetailScreen({ navigation, route }) {
  const { bookingId } = route.params;
  const { token } = useAuth();
  const [booking, setBooking] = useState(null);
  const [loading, setLoading] = useState(true);
  const [refreshing, setRefreshing] = useState(false);
  const [error, setError] = useState(null);
  const [acting, setActing] = useState(false);
  const [rejectModalOpen, setRejectModalOpen] = useState(false);
  const [rejectKind, setRejectKind] = useState(REJECTION_OPTIONS[0].value);
  const [rejectNote, setRejectNote] = useState('');
  const [rescheduleModalOpen, setRescheduleModalOpen] = useState(false);
  const [rescheduleApprove, setRescheduleApprove] = useState(true);
  const [rescheduleReq, setRescheduleReq] = useState(null);
  const [rescheduleNote, setRescheduleNote] = useState('');

  const load = useCallback(async (silent = false) => {
    if (!silent) setLoading(true);
    try {
      const data = await fetchMuthowifBooking(token, bookingId);
      setBooking(data);
      setError(null);
    } catch (err) {
      setError(err.message || 'Gagal memuat detail');
    } finally {
      setLoading(false);
      setRefreshing(false);
    }
  }, [token, bookingId]);

  useFocusEffect(
    useCallback(() => {
      load();
    }, [load]),
  );

  const handleConfirm = () => {
    Alert.alert('Setujui booking?', 'Jamaah akan diminta membayar setelah disetujui.', [
      { text: 'Batal', style: 'cancel' },
      {
        text: 'Setujui',
        onPress: async () => {
          setActing(true);
          try {
            await confirmMuthowifBooking(token, bookingId);
            Alert.alert('Berhasil', 'Booking disetujui.');
            await load(true);
          } catch (err) {
            Alert.alert('Gagal', err.message || 'Tidak dapat menyetujui');
          } finally {
            setActing(false);
          }
        },
      },
    ]);
  };

  const handleReject = () => {
    setRejectKind(REJECTION_OPTIONS[0].value);
    setRejectNote('');
    setRejectModalOpen(true);
  };

  const submitReject = async () => {
    setRejectModalOpen(false);
    setActing(true);
    try {
      await cancelMuthowifBooking(token, bookingId, {
        muthowif_rejection_kind: rejectKind,
        muthowif_rejection_note: rejectNote.trim() || null,
      });
      Alert.alert('Berhasil', 'Booking ditolak.');
      await load(true);
    } catch (err) {
      Alert.alert('Gagal', err.message || 'Tidak dapat menolak');
    } finally {
      setActing(false);
    }
  };

  const openRescheduleModal = (req, approve) => {
    setRescheduleReq(req);
    setRescheduleApprove(approve);
    setRescheduleNote('');
    setRescheduleModalOpen(true);
  };

  const submitReschedule = async () => {
    if (!rescheduleReq) return;
    setRescheduleModalOpen(false);
    setActing(true);
    try {
      const note = rescheduleNote.trim() || null;
      if (rescheduleApprove) {
        await approveReschedule(token, bookingId, rescheduleReq.id, note);
      } else {
        await rejectReschedule(token, bookingId, rescheduleReq.id, note);
      }
      Alert.alert('Berhasil', rescheduleApprove ? 'Reschedule disetujui.' : 'Reschedule ditolak.');
      await load(true);
    } catch (err) {
      Alert.alert('Gagal', err.message || 'Tidak dapat memproses');
    } finally {
      setActing(false);
      setRescheduleReq(null);
      setRescheduleNote('');
    }
  };

  const handleReschedule = (req, approve) => {
    openRescheduleModal(req, approve);
  };

  const openChat = () => {
    navigation.getParent()?.getParent()?.navigate('ChatTab', {
      screen: 'ChatRoom',
      params: {
        bookingId,
        bookingCode: booking?.booking_code || '--',
        otherName: booking?.customer?.name || 'Jamaah',
      },
    });
  };

  const handleOpenDocument = async (doc) => {
    setActing(true);
    try {
      await openBookingDocument(token, bookingId, doc.type, doc.label);
    } catch (err) {
      Alert.alert('Gagal', err.message || 'Tidak dapat membuka dokumen');
    } finally {
      setActing(false);
    }
  };

  const handleSupportCompletion = (approve) => {
    const title = approve ? 'Setujui penyelesaian layanan?' : 'Tolak permintaan penyelesaian?';
    Alert.alert(title, 'Konfirmasi keputusan Anda.', [
      { text: 'Batal', style: 'cancel' },
      {
        text: approve ? 'Setujui' : 'Tolak',
        style: approve ? 'default' : 'destructive',
        onPress: async () => {
          setActing(true);
          try {
            if (approve) {
              await approveSupportCompletion(token, bookingId);
            } else {
              await rejectSupportCompletion(token, bookingId);
            }
            Alert.alert('Berhasil', approve ? 'Layanan ditandai selesai.' : 'Permintaan ditolak.');
            await load(true);
          } catch (err) {
            Alert.alert('Gagal', err.message || 'Tidak dapat memproses');
          } finally {
            setActing(false);
          }
        },
      },
    ]);
  };

  if (loading && !booking) {
    return (
      <View style={styles.container}>
        <ScreenHeader title="Detail permintaan" onBack={() => navigation.goBack()} />
        <ActivityIndicator color={colors.baytgo} style={styles.loader} />
      </View>
    );
  }

  if (error && !booking) {
    return (
      <View style={styles.container}>
        <ScreenHeader title="Detail permintaan" onBack={() => navigation.goBack()} />
        <Text style={styles.errorText}>{error}</Text>
      </View>
    );
  }

  const bookingMeta = bookingStatusMeta(booking.status);
  const paymentMeta = paymentStatusMeta(booking.payment_status);
  const pendingReschedule = (booking.reschedule_requests || []).find((r) => r.status === 'pending');
  const documents = booking.documents || [];
  const showSupportCompletion = booking.is_support && booking.completion_requested_at && booking.status !== 'completed';

  return (
    <View style={styles.container}>
      <ScreenHeader title={booking.booking_code || 'Booking'} onBack={() => navigation.goBack()} />
      <ScrollView
        contentContainerStyle={styles.scroll}
        refreshControl={
          <RefreshControl refreshing={refreshing} onRefresh={() => { setRefreshing(true); load(true); }} tintColor={colors.baytgo} />
        }
      >
        <View style={styles.card}>
          <Text style={styles.customerName}>{booking.customer?.name || 'Jamaah'}</Text>
          <Text style={styles.customerMeta}>{booking.customer?.phone || booking.customer?.email || '—'}</Text>
          <View style={styles.badgeRow}>
            <View style={[styles.pill, { backgroundColor: bookingMeta.color + '18' }]}>
              <Text style={[styles.pillText, { color: bookingMeta.color }]}>{bookingMeta.label}</Text>
            </View>
            <View style={[styles.pill, { backgroundColor: paymentMeta.color + '18' }]}>
              <Text style={[styles.pillText, { color: paymentMeta.color }]}>{paymentMeta.label}</Text>
            </View>
          </View>
        </View>

        <View style={styles.card}>
          <InfoRow label="Layanan" value={serviceTypeLabel(booking.service_type)} />
          <InfoRow label="Tanggal" value={formatDateRange(booking.starts_on, booking.ends_on)} />
          <InfoRow label="Jamaah" value={`${booking.pilgrim_count || 1} orang`} />
          <InfoRow label="Total" value={formatIdr(booking.total_amount)} />
        </View>

        {pendingReschedule ? (
          <View style={styles.alertCard}>
            <Text style={styles.alertTitle}>Pengajuan reschedule</Text>
            <Text style={styles.alertBody}>
              {formatDateRange(pendingReschedule.starts_on, pendingReschedule.ends_on)}
              {'\n'}Status: {changeRequestStatusLabel(pendingReschedule.status)}
            </Text>
            <View style={styles.actionRow}>
              <ActionBtn icon="checkmark-circle-outline" label="Setujui" variant="primary" onPress={() => handleReschedule(pendingReschedule, true)} />
              <ActionBtn icon="close-circle-outline" label="Tolak" danger onPress={() => handleReschedule(pendingReschedule, false)} />
            </View>
          </View>
        ) : null}

        {showSupportCompletion ? (
          <View style={styles.alertCard}>
            <Text style={styles.alertTitle}>Permintaan penyelesaian layanan</Text>
            <Text style={styles.alertBody}>Jamaah meminta layanan pendukung ditandai selesai.</Text>
            <View style={styles.actionRow}>
              <ActionBtn icon="checkmark-circle-outline" label="Setujui" variant="primary" onPress={() => handleSupportCompletion(true)} />
              <ActionBtn icon="close-circle-outline" label="Tolak" danger onPress={() => handleSupportCompletion(false)} />
            </View>
          </View>
        ) : null}

        {documents.length > 0 ? (
          <View style={styles.card}>
            <Text style={styles.sectionLabel}>Dokumen jamaah</Text>
            {documents.map((doc) => (
              <ActionBtn
                key={doc.type}
                icon="document-text-outline"
                label={doc.label}
                onPress={() => handleOpenDocument(doc)}
              />
            ))}
          </View>
        ) : null}

        <View style={styles.actions}>
          {booking.status === 'pending' ? (
            <>
              <ActionBtn icon="checkmark-circle-outline" label="Setujui booking" variant="primary" onPress={handleConfirm} />
              <ActionBtn icon="close-circle-outline" label="Tolak booking" danger onPress={handleReject} />
            </>
          ) : null}
          {booking.payment_status === 'paid' ? (
            <ActionBtn icon="chatbubble-ellipses-outline" label="Chat jamaah" onPress={openChat} />
          ) : null}
        </View>

        {acting ? <ActivityIndicator color={colors.baytgo} style={{ marginTop: 16 }} /> : null}
      </ScrollView>

      <Modal visible={rejectModalOpen} transparent animationType="fade" onRequestClose={() => setRejectModalOpen(false)}>
        <View style={styles.modalBackdrop}>
          <View style={styles.modalCard}>
            <Text style={styles.modalTitle}>Tolak booking</Text>
            <Text style={styles.modalLabel}>Alasan penolakan</Text>
            <View style={styles.chipRow}>
              {REJECTION_OPTIONS.map((opt) => (
                <TouchableOpacity
                  key={opt.value}
                  style={[styles.chip, rejectKind === opt.value && styles.chipActive]}
                  onPress={() => setRejectKind(opt.value)}
                >
                  <Text style={[styles.chipText, rejectKind === opt.value && styles.chipTextActive]}>
                    {opt.label}
                  </Text>
                </TouchableOpacity>
              ))}
            </View>
            <Text style={styles.modalLabel}>Catatan (opsional)</Text>
            <TextInput
              style={styles.modalInput}
              value={rejectNote}
              onChangeText={setRejectNote}
              placeholder="Jelaskan alasan penolakan kepada jamaah"
              multiline
              maxLength={2000}
            />
            <View style={styles.modalActions}>
              <TouchableOpacity style={styles.modalCancelBtn} onPress={() => setRejectModalOpen(false)}>
                <Text style={styles.modalCancelText}>Batal</Text>
              </TouchableOpacity>
              <TouchableOpacity style={styles.modalRejectBtn} onPress={submitReject}>
                <Text style={styles.modalRejectText}>Tolak booking</Text>
              </TouchableOpacity>
            </View>
          </View>
        </View>
      </Modal>

      <Modal
        visible={rescheduleModalOpen}
        transparent
        animationType="fade"
        onRequestClose={() => setRescheduleModalOpen(false)}
      >
        <View style={styles.modalBackdrop}>
          <View style={styles.modalCard}>
            <Text style={styles.modalTitle}>
              {rescheduleApprove ? 'Setujui reschedule' : 'Tolak reschedule'}
            </Text>
            <Text style={styles.modalLabel}>Catatan untuk jamaah (opsional)</Text>
            <TextInput
              style={styles.modalInput}
              value={rescheduleNote}
              onChangeText={setRescheduleNote}
              placeholder={
                rescheduleApprove
                  ? 'Contoh: Jadwal baru sudah saya sesuaikan'
                  : 'Jelaskan alasan penolakan reschedule'
              }
              multiline
              maxLength={2000}
            />
            <View style={styles.modalActions}>
              <TouchableOpacity style={styles.modalCancelBtn} onPress={() => setRescheduleModalOpen(false)}>
                <Text style={styles.modalCancelText}>Batal</Text>
              </TouchableOpacity>
              <TouchableOpacity
                style={[styles.modalRejectBtn, rescheduleApprove && styles.modalApproveBtn]}
                onPress={submitReschedule}
              >
                <Text style={[styles.modalRejectText, rescheduleApprove && styles.modalApproveText]}>
                  {rescheduleApprove ? 'Setujui' : 'Tolak'}
                </Text>
              </TouchableOpacity>
            </View>
          </View>
        </View>
      </Modal>
    </View>
  );
}

const styles = StyleSheet.create({
  container: { flex: 1, backgroundColor: colors.canvas },
  scroll: { padding: 20, paddingBottom: 32 },
  loader: { marginTop: 40 },
  errorText: { marginTop: 40, textAlign: 'center', color: colors.slate500, fontWeight: '600' },
  card: {
    backgroundColor: colors.white,
    borderRadius: 18,
    padding: 16,
    marginBottom: 12,
    borderWidth: 1,
    borderColor: colors.slate100,
  },
  customerName: { fontSize: 20, fontWeight: '900', color: colors.baytgo },
  customerMeta: { marginTop: 4, fontSize: 13, color: colors.slate500, fontWeight: '600' },
  badgeRow: { flexDirection: 'row', flexWrap: 'wrap', gap: 8, marginTop: 12 },
  pill: { borderRadius: 999, paddingHorizontal: 10, paddingVertical: 5 },
  pillText: { fontSize: 11, fontWeight: '800' },
  infoRow: { flexDirection: 'row', justifyContent: 'space-between', gap: 12, paddingVertical: 8 },
  infoLabel: { fontSize: 13, color: colors.slate500, fontWeight: '600' },
  infoValue: { flex: 1, textAlign: 'right', fontSize: 13, fontWeight: '800', color: colors.slate900 },
  sectionLabel: { fontSize: 14, fontWeight: '900', color: colors.baytgo, marginBottom: 8 },
  alertCard: {
    backgroundColor: '#FFFBEB',
    borderRadius: 18,
    padding: 16,
    marginBottom: 12,
    borderWidth: 1,
    borderColor: '#FDE68A',
  },
  alertTitle: { fontSize: 14, fontWeight: '900', color: '#92400E' },
  alertBody: { marginTop: 8, fontSize: 13, lineHeight: 20, color: '#78350F', fontWeight: '600' },
  actionRow: { flexDirection: 'row', gap: 10, marginTop: 12 },
  actions: { gap: 10 },
  actionBtn: {
    flexDirection: 'row',
    alignItems: 'center',
    justifyContent: 'center',
    gap: 8,
    borderRadius: 14,
    paddingVertical: 14,
    borderWidth: 1,
    borderColor: colors.slate200,
    backgroundColor: colors.white,
  },
  actionPrimary: { backgroundColor: colors.baytgo, borderColor: colors.baytgo },
  actionDanger: { backgroundColor: '#FEF2F2', borderColor: '#FECACA' },
  actionText: { fontSize: 14, fontWeight: '800', color: colors.baytgo },
  actionTextPrimary: { color: colors.white },
  actionTextDanger: { color: '#B91C1C' },
  modalBackdrop: {
    flex: 1,
    backgroundColor: 'rgba(15, 23, 42, 0.45)',
    justifyContent: 'center',
    padding: 20,
  },
  modalCard: {
    backgroundColor: colors.white,
    borderRadius: 20,
    padding: 18,
  },
  modalTitle: { fontSize: 18, fontWeight: '900', color: colors.baytgo },
  modalLabel: { marginTop: 14, marginBottom: 8, fontSize: 12, fontWeight: '800', color: colors.slate600 },
  chipRow: { flexDirection: 'row', flexWrap: 'wrap', gap: 8 },
  chip: {
    borderRadius: 999,
    paddingHorizontal: 12,
    paddingVertical: 8,
    backgroundColor: colors.canvas,
    borderWidth: 1,
    borderColor: colors.slate200,
  },
  chipActive: { backgroundColor: colors.baytgo, borderColor: colors.baytgo },
  chipText: { fontSize: 12, fontWeight: '800', color: colors.slate600 },
  chipTextActive: { color: colors.white },
  modalInput: {
    minHeight: 88,
    borderRadius: 14,
    borderWidth: 1,
    borderColor: colors.slate200,
    paddingHorizontal: 14,
    paddingVertical: 12,
    fontSize: 14,
    fontWeight: '600',
    color: colors.slate900,
    textAlignVertical: 'top',
  },
  modalActions: { flexDirection: 'row', gap: 10, marginTop: 18 },
  modalCancelBtn: {
    flex: 1,
    paddingVertical: 14,
    borderRadius: 14,
    borderWidth: 1,
    borderColor: colors.slate200,
    alignItems: 'center',
  },
  modalCancelText: { fontSize: 14, fontWeight: '800', color: colors.slate600 },
  modalRejectBtn: {
    flex: 1,
    paddingVertical: 14,
    borderRadius: 14,
    backgroundColor: '#B91C1C',
    alignItems: 'center',
  },
  modalRejectText: { fontSize: 14, fontWeight: '800', color: colors.white },
  modalApproveBtn: { backgroundColor: colors.baytgo },
  modalApproveText: { color: colors.white },
});
