import React, { useCallback, useState } from 'react';
import { StyleSheet, Text, View, ScrollView, RefreshControl, Alert, TextInput } from 'react-native';
import { useFocusEffect } from '@react-navigation/native';
import { LinearGradient } from 'expo-linear-gradient';
import {
  User, Briefcase, Calendar, Clock, Users, Bed, Car, CheckCircle, XCircle, MessagesSquare,
} from 'lucide-react-native';
import ScreenHeader from '../components/ScreenHeader';
import BookingDocumentGallery from '../components/BookingDocumentGallery';
import {
  fetchMuthowifBooking, confirmMuthowifBooking, cancelMuthowifBooking,
  approveReschedule, rejectReschedule, completeSupportWithCode, resendSupportCompletionCode,
} from '../api/muthowifBookings';
import { useAuth } from '../context/AuthContext';
import { useHideTabBarOnFocus } from '../hooks/useHideTabBarOnFocus';
import Button from '../ui/Button';
import ErrorState from '../ui/ErrorState';
import { SkeletonList } from '../ui/Skeleton';
import BookingSection from '../features/booking/BookingSection';
import StatusPill from '../features/booking/StatusPill';
import { REJECTION_OPTIONS, RejectBookingForm, RescheduleDecisionModal } from '../features/booking/MuthowifBookingModals';
import { notifyError, notifySuccess } from '../utils/feedback';
import { colors, gradients, layout, radius, spacing, typography } from '../theme/tokens';
import { formatIdr } from '../utils/format';
import { MuthowifPricingBreakdown } from '../components/BookingPricingBreakdown';
import { useLocale } from '../utils/locale';
import {
  bookingStatusMeta, paymentStatusMeta, serviceTypeLabel, formatDateRange,
  billingNights, changeRequestStatusLabel, canCompleteSupportWithCode,
} from '../utils/bookingLabels';

function InfoCell({ icon: Icon, label, value }) {
  return (
    <View style={styles.infoCell}>
      <View style={styles.infoIcon}>
        <Icon size={16} color={colors.baytgo} strokeWidth={2} />
      </View>
      <View style={styles.infoCopy}>
        <Text style={styles.infoLabel}>{label}</Text>
        <Text style={styles.infoValue}>{value}</Text>
      </View>
    </View>
  );
}

function AlertCard({ icon: Icon, title, body, children }) {
  return (
    <BookingSection variant="warning">
      <View style={styles.alertHead}>
        <Icon size={20} color="#92400E" strokeWidth={2} />
        <Text style={styles.alertTitle}>{title}</Text>
      </View>
      <Text style={styles.alertBody}>{body}</Text>
      {children}
    </BookingSection>
  );
}

export default function MuthowifBookingDetailScreen({ navigation, route }) {
  const { bookingId } = route.params;
  const { token } = useAuth();
  const locale = useLocale();
  const isEn = locale === 'en';
  const [booking, setBooking] = useState(null);
  const [loading, setLoading] = useState(true);
  const [refreshing, setRefreshing] = useState(false);
  const [error, setError] = useState(null);
  const [acting, setActing] = useState(false);
  const [rejectKind, setRejectKind] = useState(REJECTION_OPTIONS[0].value);
  const [rejectNote, setRejectNote] = useState('');
  const [rescheduleModalOpen, setRescheduleModalOpen] = useState(false);
  const [rescheduleApprove, setRescheduleApprove] = useState(true);
  const [rescheduleReq, setRescheduleReq] = useState(null);
  const [rescheduleNote, setRescheduleNote] = useState('');
  const [supportCode, setSupportCode] = useState('');

  const load = useCallback(async (silent = false) => {
    if (!silent) setLoading(true);
    try {
      const data = await fetchMuthowifBooking(token, bookingId);
      setBooking(data);
      setError(null);
    } catch (err) {
      setError(err.message || (isEn ? 'Failed to load details' : 'Gagal memuat detail'));
    } finally {
      setLoading(false);
      setRefreshing(false);
    }
  }, [token, bookingId, isEn]);

  useFocusEffect(useCallback(() => { load(); }, [load]));
  useHideTabBarOnFocus(navigation);

  const handleConfirm = () => {
    Alert.alert(isEn ? 'Approve booking?' : 'Setujui booking?', isEn ? 'The pilgrim will be asked to pay after approval.' : 'Jamaah akan diminta membayar setelah disetujui.', [
      { text: isEn ? 'Cancel' : 'Batal', style: 'cancel' },
      {
        text: isEn ? 'Approve' : 'Setujui',
        onPress: async () => {
          setActing(true);
          try {
            await confirmMuthowifBooking(token, bookingId);
            notifySuccess(isEn ? 'Booking approved.' : 'Booking disetujui.');
            await load(true);
          } catch (err) {
            notifyError(err.message || (isEn ? 'Unable to approve booking' : 'Tidak dapat menyetujui'));
          } finally {
            setActing(false);
          }
        },
      },
    ]);
  };

  const submitReject = async () => {
    if (!rejectKind) {
      notifyError(isEn ? 'Select a rejection reason.' : 'Pilih alasan penolakan.');
      return;
    }

    Alert.alert(
      isEn ? 'Reject booking?' : 'Tolak booking?',
      isEn ? 'The pilgrim will receive a notification with your rejection reason.' : 'Jamaah akan menerima notifikasi beserta alasan penolakan Anda.',
      [
        { text: isEn ? 'Cancel' : 'Batal', style: 'cancel' },
        {
          text: isEn ? 'Reject' : 'Tolak',
          style: 'destructive',
          onPress: async () => {
            setActing(true);
            try {
              await cancelMuthowifBooking(token, bookingId, {
                muthowif_rejection_kind: rejectKind,
                muthowif_rejection_note: rejectNote.trim() || null,
              });
              notifySuccess(isEn ? 'Booking rejected.' : 'Booking ditolak.');
              await load(true);
            } catch (err) {
              notifyError(err.message || (isEn ? 'Unable to reject booking' : 'Tidak dapat menolak'));
            } finally {
              setActing(false);
            }
          },
        },
      ],
    );
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
      notifySuccess(rescheduleApprove
        ? (isEn ? 'Reschedule approved.' : 'Reschedule disetujui.')
        : (isEn ? 'Reschedule rejected.' : 'Reschedule ditolak.'));
      await load(true);
    } catch (err) {
      notifyError(err.message || (isEn ? 'Unable to process request' : 'Tidak dapat memproses'));
    } finally {
      setActing(false);
      setRescheduleReq(null);
      setRescheduleNote('');
    }
  };

  const handleCompleteSupportWithCode = () => {
    const code = String(supportCode || '').replace(/\D+/g, '');
    if (code.length !== 6) {
      notifyError(isEn ? 'Enter the 6-digit verification code from the pilgrim.' : 'Masukkan 6 digit kode verifikasi dari jamaah.');
      return;
    }
    Alert.alert(isEn ? 'Complete service?' : 'Selesaikan layanan?', isEn ? 'A valid code will close the booking and record the balance.' : 'Kode benar akan menutup booking dan mencatat saldo.', [
      { text: isEn ? 'Cancel' : 'Batal', style: 'cancel' },
      {
        text: isEn ? 'Complete' : 'Selesaikan',
        onPress: async () => {
          setActing(true);
          try {
            await completeSupportWithCode(token, bookingId, code);
            notifySuccess(isEn ? 'Service marked as completed.' : 'Layanan ditandai selesai.');
            setSupportCode('');
            await load(true);
          } catch (err) {
            notifyError(err.message || (isEn ? 'Invalid code' : 'Kode tidak valid'));
          } finally {
            setActing(false);
          }
        },
      },
    ]);
  };

  const handleResendSupportCode = async () => {
    setActing(true);
    try {
      await resendSupportCompletionCode(token, bookingId);
      notifySuccess(isEn ? 'Code resent to pilgrim WhatsApp.' : 'Kode dikirim ulang ke WhatsApp jamaah.');
    } catch (err) {
      notifyError(err.message || (isEn ? 'Unable to resend code' : 'Tidak dapat mengirim ulang kode'));
    } finally {
      setActing(false);
    }
  };

  const openChat = () => {
    navigation.getParent()?.getParent()?.navigate('ChatTab', {
      screen: 'ChatRoom',
      params: {
        bookingId,
        bookingCode: booking?.booking_code || '--',
        otherName: booking?.customer?.name || (isEn ? 'Pilgrim' : 'Jamaah'),
      },
    });
  };

  if (loading && !booking) {
    return (
      <View style={styles.container}>
        <ScreenHeader variant="brand" title={isEn ? 'Request details' : 'Detail permintaan'} onBack={() => navigation.goBack()} />
        <SkeletonList count={3} style={styles.skeleton} />
      </View>
    );
  }

  if (error && !booking) {
    return (
      <View style={styles.container}>
        <ScreenHeader variant="brand" title={isEn ? 'Request details' : 'Detail permintaan'} onBack={() => navigation.goBack()} />
        <ErrorState description={error} onRetry={() => load()} />
      </View>
    );
  }

  const bookingMeta = bookingStatusMeta(booking.status);
  const paymentMeta = paymentStatusMeta(booking.payment_status);
  const pendingReschedule = (booking.reschedule_requests || []).find((r) => r.status === 'pending');
  const showSupportCompletion = canCompleteSupportWithCode(booking);
  const nights = billingNights(booking.starts_on, booking.ends_on);
  const isPendingDecision = booking.status === 'pending';

  return (
    <View style={styles.container}>
      <ScreenHeader
        variant="brand"
        title={booking.booking_code || 'Booking'}
        subtitle={isEn ? 'Pilgrim request details' : 'Detail permintaan jamaah'}
        onBack={() => navigation.goBack()}
      />

      <ScrollView
        contentContainerStyle={styles.scroll}
        showsVerticalScrollIndicator={false}
        refreshControl={
          <RefreshControl refreshing={refreshing} onRefresh={() => { setRefreshing(true); load(true); }} tintColor={colors.baytgo} />
        }
      >
        <LinearGradient colors={gradients.primary} start={{ x: 0, y: 0 }} end={{ x: 1, y: 1 }} style={styles.hero}>
          <View style={styles.heroTop}>
            <View style={styles.avatar}>
              <User size={28} color={colors.baytgoDark} strokeWidth={2} />
            </View>
            <View style={styles.heroCopy}>
              <Text style={styles.customerName}>{booking.customer?.name || (isEn ? 'Pilgrim' : 'Jamaah')}</Text>
              <Text style={styles.customerMeta}>
                {booking.customer?.phone || booking.customer?.email || '—'}
              </Text>
            </View>
          </View>
          <View style={styles.badgeRow}>
            <StatusPill label={bookingMeta.label} color={bookingMeta.color} />
            <StatusPill label={paymentMeta.label} color={paymentMeta.color} />
          </View>
          <View style={styles.totalRow}>
            <Text style={styles.totalLabel}>{isEn ? 'Service subtotal' : 'Subtotal layanan'}</Text>
            <Text style={styles.totalValue}>{formatIdr(booking.pricing?.base ?? booking.total_amount)}</Text>
          </View>
          {booking.pricing?.net_after_referral != null ? (
            <View style={styles.netRow}>
              <Text style={styles.netLabel}>{isEn ? 'Estimated payout' : 'Estimasi diterima'}</Text>
              <Text style={styles.netValue}>{formatIdr(booking.pricing.net_after_referral)}</Text>
            </View>
          ) : null}
        </LinearGradient>

        <BookingSection title={isEn ? 'Earnings details' : 'Rincian pendapatan'}>
          <MuthowifPricingBreakdown pricing={booking.pricing} />
        </BookingSection>

        <BookingSection title={isEn ? 'Trip information' : 'Informasi perjalanan'}>
          <InfoCell icon={Briefcase} label={isEn ? 'Service' : 'Layanan'} value={serviceTypeLabel(booking.service_type)} />
          <InfoCell icon={Calendar} label={isEn ? 'Dates' : 'Tanggal'} value={formatDateRange(booking.starts_on, booking.ends_on)} />
          <InfoCell icon={Clock} label={isEn ? 'Duration' : 'Durasi'} value={`${nights} ${isEn ? 'days' : 'hari'}`} />
          <InfoCell icon={Users} label={isEn ? 'Pilgrim count' : 'Jumlah jamaah'} value={`${booking.pilgrim_count || 1} ${isEn ? 'people' : 'orang'}`} />
          {booking.with_same_hotel ? <InfoCell icon={Bed} label={isEn ? 'Same hotel' : 'Hotel sama'} value={isEn ? 'Yes' : 'Ya'} /> : null}
          {booking.with_transport ? <InfoCell icon={Car} label="Transport" value={isEn ? 'Yes' : 'Ya'} /> : null}
        </BookingSection>

        {pendingReschedule ? (
          <AlertCard
            icon={Calendar}
            title={isEn ? 'Reschedule request' : 'Pengajuan reschedule'}
            body={`${isEn ? 'New schedule' : 'Jadwal baru'}: ${formatDateRange(pendingReschedule.starts_on, pendingReschedule.ends_on)}\nStatus: ${changeRequestStatusLabel(pendingReschedule.status)}`}
          >
            <View style={styles.actionRow}>
              <View style={styles.actionBtn}><Button label={isEn ? 'Approve' : 'Setujui'} size="sm" icon={<CheckCircle size={16} color={colors.white} strokeWidth={2} />}
                onPress={() => openRescheduleModal(pendingReschedule, true)} fullWidth={false} /></View>
              <View style={styles.actionBtn}><Button label={isEn ? 'Reject' : 'Tolak'} size="sm" variant="danger" icon={<XCircle size={16} color={colors.error} strokeWidth={2} />}
                onPress={() => openRescheduleModal(pendingReschedule, false)} fullWidth={false} /></View>
            </View>
          </AlertCard>
        ) : null}

        {showSupportCompletion ? (
          <BookingSection title={isEn ? 'Complete with code' : 'Selesaikan dengan kode'} variant="success">
            <Text style={styles.supportIntro}>
              {isEn
                ? 'Ask for the 6-digit code from the pilgrim, then enter it below to close the service.'
                : 'Minta kode 6 digit dari jamaah, lalu masukkan di bawah untuk menutup layanan.'}
            </Text>
            <TextInput
              value={supportCode}
              onChangeText={setSupportCode}
              keyboardType="number-pad"
              maxLength={6}
              placeholder="000000"
              style={styles.codeInput}
            />
            <Button
              label={acting ? (isEn ? 'Processing...' : 'Memproses...') : (isEn ? 'Complete service' : 'Selesaikan layanan')}
              icon={<CheckCircle size={18} color={colors.white} strokeWidth={2} />}
              onPress={handleCompleteSupportWithCode}
              loading={acting}
            />
            <View style={{ marginTop: spacing.sm }}>
              <Button
                label={isEn ? 'Resend code to pilgrim' : 'Kirim ulang kode ke jamaah'}
                variant="secondary"
                onPress={handleResendSupportCode}
                disabled={acting}
              />
            </View>
          </BookingSection>
        ) : null}

        <BookingDocumentGallery token={token} bookingId={bookingId} documents={booking.documents || []} title={isEn ? 'Pilgrim documents' : 'Dokumen jamaah'} />

        {isPendingDecision ? (
          <BookingSection title={isEn ? 'Booking decision' : 'Keputusan booking'} style={styles.decisionCard}>
            <Text style={styles.decisionSub}>
              {isEn
                ? 'Review the pilgrim documents, choose a reason if rejecting, then approve or reject this request.'
                : 'Tinjau dokumen jamaah, pilih alasan jika menolak, lalu setujui atau tolak permintaan ini.'}
            </Text>
            <RejectBookingForm
              rejectKind={rejectKind}
              rejectNote={rejectNote}
              onChangeKind={setRejectKind}
              onChangeNote={setRejectNote}
            />
            <View style={styles.decisionActions}>
              <Button
                label={isEn ? 'Approve booking' : 'Setujui booking'}
                icon={<CheckCircle size={18} color={colors.white} strokeWidth={2} />}
                onPress={handleConfirm}
                loading={acting}
                disabled={acting}
              />
              <Button
                label={isEn ? 'Reject booking' : 'Tolak booking'}
                variant="danger"
                icon={<XCircle size={18} color={colors.error} strokeWidth={2} />}
                onPress={submitReject}
                disabled={acting}
              />
            </View>
          </BookingSection>
        ) : null}

        {booking.payment_status === 'paid' ? (
          <Button label={isEn ? 'Chat pilgrim' : 'Chat jamaah'} variant="secondary"
            icon={<MessagesSquare size={18} color={colors.baytgo} strokeWidth={2} />}
            onPress={openChat} />
        ) : null}
      </ScrollView>

      <RescheduleDecisionModal
        visible={rescheduleModalOpen}
        approve={rescheduleApprove}
        note={rescheduleNote}
        onChangeNote={setRescheduleNote}
        onClose={() => setRescheduleModalOpen(false)}
        onSubmit={submitReschedule}
      />
    </View>
  );
}

const styles = StyleSheet.create({
  container: { flex: 1, backgroundColor: colors.background },
  scroll: { padding: layout.screenPadding, paddingBottom: spacing['4xl'] },
  skeleton: { padding: layout.screenPadding },
  hero: {
    marginBottom: spacing.md,
    borderRadius: radius.md,
    padding: spacing.xl,
    overflow: 'hidden',
  },
  heroTop: { flexDirection: 'row', alignItems: 'center', gap: spacing.lg },
  avatar: {
    width: 56, height: 56, borderRadius: radius.sm,
    backgroundColor: colors.gold, alignItems: 'center', justifyContent: 'center',
  },
  heroCopy: { flex: 1 },
  customerName: { ...typography.subtitle, color: colors.white },
  customerMeta: { marginTop: spacing.xs, ...typography.caption, color: 'rgba(255,255,255,0.7)' },
  badgeRow: { flexDirection: 'row', flexWrap: 'wrap', gap: spacing.sm, marginTop: spacing.lg },
  totalRow: {
    flexDirection: 'row', justifyContent: 'space-between', alignItems: 'center',
    marginTop: spacing.lg, paddingTop: spacing.lg, borderTopWidth: 1, borderTopColor: 'rgba(255,255,255,0.15)',
  },
  totalLabel: { ...typography.caption, color: 'rgba(255,255,255,0.65)' },
  totalValue: { ...typography.subtitle, color: colors.white },
  netRow: {
    marginTop: spacing.md, paddingTop: spacing.md, borderTopWidth: 1, borderTopColor: 'rgba(255,255,255,0.15)',
    flexDirection: 'row', justifyContent: 'space-between', alignItems: 'center',
  },
  netLabel: { ...typography.caption, color: 'rgba(232,220,184,0.9)' },
  netValue: { ...typography.title, fontSize: 20, color: colors.gold },
  infoCell: { flexDirection: 'row', alignItems: 'flex-start', gap: spacing.md, paddingVertical: spacing.sm },
  infoIcon: {
    width: 34, height: 34, borderRadius: radius.sm,
    backgroundColor: colors.baytgoLight, alignItems: 'center', justifyContent: 'center',
  },
  infoCopy: { flex: 1 },
  infoLabel: { ...typography.label, color: colors.textSecondary },
  infoValue: { marginTop: 2, ...typography.caption, fontFamily: 'PlusJakartaSans_700Bold', color: colors.textPrimary },
  alertHead: { flexDirection: 'row', alignItems: 'center', gap: spacing.sm, marginBottom: spacing.sm },
  alertTitle: { ...typography.caption, fontFamily: 'PlusJakartaSans_800ExtraBold', color: '#92400E' },
  alertBody: { ...typography.caption, lineHeight: 20, color: '#78350F' },
  supportIntro: { ...typography.caption, color: colors.textSecondary, lineHeight: 20, marginBottom: spacing.md },
  codeInput: {
    borderWidth: 1,
    borderColor: colors.border,
    borderRadius: radius.sm,
    paddingHorizontal: spacing.md,
    paddingVertical: spacing.md,
    marginBottom: spacing.md,
    letterSpacing: 8,
    fontSize: 22,
    fontWeight: '700',
    textAlign: 'center',
    color: colors.textPrimary,
    backgroundColor: colors.white,
  },
  actionRow: { flexDirection: 'row', gap: spacing.md, marginTop: spacing.md },
  actionBtn: { flex: 1 },
  decisionCard: { borderColor: '#DDD6FE' },
  decisionSub: { ...typography.small, color: colors.textSecondary, lineHeight: 17 },
  decisionActions: { marginTop: spacing.lg, gap: spacing.sm },
});
