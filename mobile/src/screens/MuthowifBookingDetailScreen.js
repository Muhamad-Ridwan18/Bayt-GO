import React, { useCallback, useState } from 'react';
import { StyleSheet, Text, View, ScrollView, RefreshControl, Alert } from 'react-native';
import { useFocusEffect } from '@react-navigation/native';
import {
  User, Briefcase, Calendar, Clock, Users, Bed, Car, CheckCircle, XCircle, MessagesSquare,
  CircleCheckBig,
} from 'lucide-react-native';
import ScreenHeader from '../components/ScreenHeader';
import BookingDocumentGallery from '../components/BookingDocumentGallery';
import {
  fetchMuthowifBooking, confirmMuthowifBooking, cancelMuthowifBooking,
  approveReschedule, rejectReschedule, approveSupportCompletion, rejectSupportCompletion,
} from '../api/muthowifBookings';
import { useAuth } from '../context/AuthContext';
import { useHideTabBarOnFocus } from '../hooks/useHideTabBarOnFocus';
import Button from '../ui/Button';
import Card from '../ui/Card';
import ErrorState from '../ui/ErrorState';
import { SkeletonList } from '../ui/Skeleton';
import BookingSection from '../features/booking/BookingSection';
import StatusPill from '../features/booking/StatusPill';
import { REJECTION_OPTIONS, RejectBookingForm, RescheduleDecisionModal } from '../features/booking/MuthowifBookingModals';
import { notifyError, notifySuccess } from '../utils/feedback';
import { colors, layout, radius, spacing, typography } from '../theme/tokens';
import { formatIdr } from '../utils/format';
import { MuthowifPricingBreakdown } from '../components/BookingPricingBreakdown';
import {
  bookingStatusMeta, paymentStatusMeta, serviceTypeLabel, formatDateRange,
  billingNights, changeRequestStatusLabel,
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

  useFocusEffect(useCallback(() => { load(); }, [load]));
  useHideTabBarOnFocus(navigation);

  const handleConfirm = () => {
    Alert.alert('Setujui booking?', 'Jamaah akan diminta membayar setelah disetujui.', [
      { text: 'Batal', style: 'cancel' },
      {
        text: 'Setujui',
        onPress: async () => {
          setActing(true);
          try {
            await confirmMuthowifBooking(token, bookingId);
            notifySuccess('Booking disetujui.');
            await load(true);
          } catch (err) {
            notifyError(err.message || 'Tidak dapat menyetujui');
          } finally {
            setActing(false);
          }
        },
      },
    ]);
  };

  const submitReject = async () => {
    if (!rejectKind) {
      notifyError('Pilih alasan penolakan.');
      return;
    }

    Alert.alert(
      'Tolak booking?',
      'Jamaah akan menerima notifikasi beserta alasan penolakan Anda.',
      [
        { text: 'Batal', style: 'cancel' },
        {
          text: 'Tolak',
          style: 'destructive',
          onPress: async () => {
            setActing(true);
            try {
              await cancelMuthowifBooking(token, bookingId, {
                muthowif_rejection_kind: rejectKind,
                muthowif_rejection_note: rejectNote.trim() || null,
              });
              notifySuccess('Booking ditolak.');
              await load(true);
            } catch (err) {
              notifyError(err.message || 'Tidak dapat menolak');
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
      notifySuccess(rescheduleApprove ? 'Reschedule disetujui.' : 'Reschedule ditolak.');
      await load(true);
    } catch (err) {
      notifyError(err.message || 'Tidak dapat memproses');
    } finally {
      setActing(false);
      setRescheduleReq(null);
      setRescheduleNote('');
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
            if (approve) await approveSupportCompletion(token, bookingId);
            else await rejectSupportCompletion(token, bookingId);
            notifySuccess(approve ? 'Layanan ditandai selesai.' : 'Permintaan ditolak.');
            await load(true);
          } catch (err) {
            notifyError(err.message || 'Tidak dapat memproses');
          } finally {
            setActing(false);
          }
        },
      },
    ]);
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

  if (loading && !booking) {
    return (
      <View style={styles.container}>
        <ScreenHeader title="Detail permintaan" onBack={() => navigation.goBack()} />
        <SkeletonList count={3} style={styles.skeleton} />
      </View>
    );
  }

  if (error && !booking) {
    return (
      <View style={styles.container}>
        <ScreenHeader title="Detail permintaan" onBack={() => navigation.goBack()} />
        <ErrorState description={error} onRetry={() => load()} />
      </View>
    );
  }

  const bookingMeta = bookingStatusMeta(booking.status);
  const paymentMeta = paymentStatusMeta(booking.payment_status);
  const pendingReschedule = (booking.reschedule_requests || []).find((r) => r.status === 'pending');
  const showSupportCompletion = booking.is_support && booking.completion_requested_at && booking.status !== 'completed';
  const nights = billingNights(booking.starts_on, booking.ends_on);
  const isPendingDecision = booking.status === 'pending';

  return (
    <View style={styles.container}>
      <ScreenHeader
        title={booking.booking_code || 'Booking'}
        subtitle="Detail permintaan jamaah"
        onBack={() => navigation.goBack()}
      />

      <ScrollView
        contentContainerStyle={styles.scroll}
        showsVerticalScrollIndicator={false}
        refreshControl={
          <RefreshControl refreshing={refreshing} onRefresh={() => { setRefreshing(true); load(true); }} tintColor={colors.baytgo} />
        }
      >
        <Card style={styles.hero} padding={spacing.xl}>
          <View style={styles.heroTop}>
            <View style={styles.avatar}>
              <User size={28} color={colors.baytgo} strokeWidth={2} />
            </View>
            <View style={styles.heroCopy}>
              <Text style={styles.customerName}>{booking.customer?.name || 'Jamaah'}</Text>
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
            <Text style={styles.totalLabel}>Subtotal layanan</Text>
            <Text style={styles.totalValue}>{formatIdr(booking.pricing?.base ?? booking.total_amount)}</Text>
          </View>
          {booking.pricing?.net_after_referral != null ? (
            <View style={styles.netRow}>
              <Text style={styles.netLabel}>Estimasi diterima</Text>
              <Text style={styles.netValue}>{formatIdr(booking.pricing.net_after_referral)}</Text>
            </View>
          ) : null}
        </Card>

        <BookingSection title="Rincian pendapatan">
          <MuthowifPricingBreakdown pricing={booking.pricing} />
        </BookingSection>

        <BookingSection title="Informasi perjalanan">
          <InfoCell icon={Briefcase} label="Layanan" value={serviceTypeLabel(booking.service_type)} />
          <InfoCell icon={Calendar} label="Tanggal" value={formatDateRange(booking.starts_on, booking.ends_on)} />
          <InfoCell icon={Clock} label="Durasi" value={`${nights} hari`} />
          <InfoCell icon={Users} label="Jumlah jamaah" value={`${booking.pilgrim_count || 1} orang`} />
          {booking.with_same_hotel ? <InfoCell icon={Bed} label="Hotel sama" value="Ya" /> : null}
          {booking.with_transport ? <InfoCell icon={Car} label="Transport" value="Ya" /> : null}
        </BookingSection>

        {pendingReschedule ? (
          <AlertCard
            icon={Calendar}
            title="Pengajuan reschedule"
            body={`Jadwal baru: ${formatDateRange(pendingReschedule.starts_on, pendingReschedule.ends_on)}\nStatus: ${changeRequestStatusLabel(pendingReschedule.status)}`}
          >
            <View style={styles.actionRow}>
              <View style={styles.actionBtn}><Button label="Setujui" size="sm" icon={<CheckCircle size={16} color={colors.white} strokeWidth={2} />}
                onPress={() => openRescheduleModal(pendingReschedule, true)} fullWidth={false} /></View>
              <View style={styles.actionBtn}><Button label="Tolak" size="sm" variant="danger" icon={<XCircle size={16} color={colors.error} strokeWidth={2} />}
                onPress={() => openRescheduleModal(pendingReschedule, false)} fullWidth={false} /></View>
            </View>
          </AlertCard>
        ) : null}

        {showSupportCompletion ? (
          <AlertCard icon={CircleCheckBig} title="Permintaan penyelesaian layanan" body="Jamaah meminta layanan pendukung ditandai selesai.">
            <View style={styles.actionRow}>
              <View style={styles.actionBtn}><Button label="Setujui" size="sm" icon={<CheckCircle size={16} color={colors.white} strokeWidth={2} />}
                onPress={() => handleSupportCompletion(true)} fullWidth={false} /></View>
              <View style={styles.actionBtn}><Button label="Tolak" size="sm" variant="danger" icon={<XCircle size={16} color={colors.error} strokeWidth={2} />}
                onPress={() => handleSupportCompletion(false)} fullWidth={false} /></View>
            </View>
          </AlertCard>
        ) : null}

        <BookingDocumentGallery token={token} bookingId={bookingId} documents={booking.documents || []} title="Dokumen jamaah" />

        {isPendingDecision ? (
          <BookingSection title="Keputusan booking" style={styles.decisionCard}>
            <Text style={styles.decisionSub}>
              Tinjau dokumen jamaah, pilih alasan jika menolak, lalu setujui atau tolak permintaan ini.
            </Text>
            <RejectBookingForm
              rejectKind={rejectKind}
              rejectNote={rejectNote}
              onChangeKind={setRejectKind}
              onChangeNote={setRejectNote}
            />
            <View style={styles.decisionActions}>
              <Button
                label="Setujui booking"
                icon={<CheckCircle size={18} color={colors.white} strokeWidth={2} />}
                onPress={handleConfirm}
                loading={acting}
                disabled={acting}
              />
              <Button
                label="Tolak booking"
                variant="danger"
                icon={<XCircle size={18} color={colors.error} strokeWidth={2} />}
                onPress={submitReject}
                disabled={acting}
              />
            </View>
          </BookingSection>
        ) : null}

        {booking.payment_status === 'paid' ? (
          <Button label="Chat jamaah" variant="secondary"
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
  hero: { marginBottom: spacing.md },
  heroTop: { flexDirection: 'row', alignItems: 'center', gap: spacing.lg },
  avatar: {
    width: 56, height: 56, borderRadius: radius.sm,
    backgroundColor: colors.baytgoLight, alignItems: 'center', justifyContent: 'center',
  },
  heroCopy: { flex: 1 },
  customerName: { ...typography.subtitle, color: colors.baytgo },
  customerMeta: { marginTop: spacing.xs, ...typography.caption, color: colors.textSecondary },
  badgeRow: { flexDirection: 'row', flexWrap: 'wrap', gap: spacing.sm, marginTop: spacing.lg },
  totalRow: {
    flexDirection: 'row', justifyContent: 'space-between', alignItems: 'center',
    marginTop: spacing.lg, paddingTop: spacing.lg, borderTopWidth: 1, borderTopColor: colors.border,
  },
  totalLabel: { ...typography.caption, color: colors.textSecondary },
  totalValue: { ...typography.subtitle, color: colors.baytgo },
  netRow: {
    marginTop: spacing.md, paddingTop: spacing.md, borderTopWidth: 1, borderTopColor: colors.border,
    flexDirection: 'row', justifyContent: 'space-between', alignItems: 'center',
  },
  netLabel: { ...typography.caption, color: colors.textSecondary },
  netValue: { ...typography.title, fontSize: 20, color: colors.success },
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
  actionRow: { flexDirection: 'row', gap: spacing.md, marginTop: spacing.md },
  actionBtn: { flex: 1 },
  decisionCard: { borderColor: '#DDD6FE' },
  decisionSub: { ...typography.small, color: colors.textSecondary, lineHeight: 17 },
  decisionActions: { marginTop: spacing.lg, gap: spacing.sm },
});
