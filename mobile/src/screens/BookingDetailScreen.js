import React, { useCallback, useState } from 'react';
import {
  View,
  Text,
  StyleSheet,
  ScrollView,
  Image,
  TouchableOpacity,
  ActivityIndicator,
  RefreshControl,
  Alert,
} from 'react-native';
import { useFocusEffect } from '@react-navigation/native';
import { LinearGradient } from 'expo-linear-gradient';
import { Ionicons } from '@expo/vector-icons';
import ScreenHeader from '../components/ScreenHeader';
import BookingDocumentGallery from '../components/BookingDocumentGallery';
import { fetchBooking, cancelBooking, requestSupportCompletion } from '../api/bookings';
import { selectEmergencyReplacement } from '../api/emergency';
import { useAuth } from '../context/AuthContext';
import { useUserBookingRealtime } from '../hooks/useUserBookingRealtime';
import { colors } from '../theme/colors';
import { formatIdr } from '../utils/format';
import { resolveMediaUrl } from '../utils/mediaUrl';
import {
  bookingStatusMeta,
  paymentStatusMeta,
  serviceTypeLabel,
  formatDateRange,
  needsPayment,
  canCancelBooking,
  canCompleteBooking,
  canReviewBooking,
  canViewInvoice,
  canRequestRefund,
  canRequestReschedule,
  hasPendingReschedule,
  canRequestSupportCompletion,
  hasSupportCompletionPending,
  changeRequestStatusLabel,
  billingNights,
} from '../utils/bookingLabels';
import {
  CustomerPricingBreakdown,
  customerPayableAmount,
} from '../components/BookingPricingBreakdown';

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
      style={[
        styles.actionBtn,
        isPrimary && styles.actionPrimary,
        danger && styles.actionDanger,
      ]}
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

function canOpenChat(booking) {
  if (!booking || booking.payment_status !== 'paid') return false;
  return ['confirmed', 'in_progress', 'completed'].includes(booking.status);
}

function StatusPill({ label, color }) {
  return (
    <View style={[styles.pill, { backgroundColor: color + '18' }]}>
      <Text style={[styles.pillText, { color }]}>{label}</Text>
    </View>
  );
}

export default function BookingDetailScreen({ navigation, route }) {
  const { token, user } = useAuth();
  const { bookingId } = route.params;

  const [booking, setBooking] = useState(null);
  const [loading, setLoading] = useState(true);
  const [refreshing, setRefreshing] = useState(false);
  const [error, setError] = useState(null);
  const [cancelling, setCancelling] = useState(false);
  const [requestingCompletion, setRequestingCompletion] = useState(false);

  const load = useCallback(async (refresh = false) => {
    if (refresh) setRefreshing(true);
    else setLoading(true);

    try {
      const data = await fetchBooking(token, bookingId);
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

  useUserBookingRealtime({
    token,
    userId: user?.id,
    onEvent: (payload) => {
      if (String(payload?.booking_id) === String(bookingId)) {
        load(true);
      }
    },
  });

  const handleSelectReplacement = (offer) => {
    const name = offer.muthowif?.name || 'Muthowif';
    Alert.alert(
      'Pilih muthowif pengganti?',
      `Layanan akan dilanjutkan dengan ${name}.`,
      [
        { text: 'Batal', style: 'cancel' },
        {
          text: 'Pilih',
          onPress: async () => {
            try {
              await selectEmergencyReplacement(token, bookingId, offer.id);
              Alert.alert('Berhasil', 'Muthowif pengganti telah dipilih.');
              load(true);
            } catch (err) {
              Alert.alert('Gagal', err.message || 'Tidak dapat memilih pengganti');
            }
          },
        },
      ],
    );
  };

  const handleCancel = () => {
    Alert.alert('Batalkan pesanan?', 'Pesanan yang dibatalkan tidak dapat dipulihkan.', [
      { text: 'Tidak', style: 'cancel' },
      {
        text: 'Ya, batalkan',
        style: 'destructive',
        onPress: async () => {
          setCancelling(true);
          try {
            await cancelBooking(token, bookingId);
            Alert.alert('Dibatalkan', 'Pesanan berhasil dibatalkan.');
            load(true);
          } catch (err) {
            Alert.alert('Gagal', err.message || 'Tidak dapat membatalkan pesanan');
          } finally {
            setCancelling(false);
          }
        },
      },
    ]);
  };

  const handleRequestSupportCompletion = () => {
    Alert.alert(
      'Minta penyelesaian layanan?',
      'Muthowif akan diminta mengonfirmasi bahwa layanan pendukung sudah selesai.',
      [
        { text: 'Batal', style: 'cancel' },
        {
          text: 'Kirim permintaan',
          onPress: async () => {
            setRequestingCompletion(true);
            try {
              await requestSupportCompletion(token, bookingId);
              Alert.alert('Berhasil', 'Permintaan penyelesaian telah dikirim ke muthowif.');
              load(true);
            } catch (err) {
              Alert.alert('Gagal', err.message || 'Tidak dapat mengirim permintaan');
            } finally {
              setRequestingCompletion(false);
            }
          },
        },
      ],
    );
  };

  if (loading && !refreshing) {
    return (
      <View style={styles.container}>
        <ScreenHeader title="Detail Pesanan" onBack={() => navigation.goBack()} />
        <ActivityIndicator color={colors.baytgo} style={styles.loader} />
      </View>
    );
  }

  if (error || !booking) {
    return (
      <View style={styles.container}>
        <ScreenHeader title="Detail Pesanan" onBack={() => navigation.goBack()} />
        <View style={styles.empty}>
          <Text style={styles.emptyText}>{error || 'Pesanan tidak ditemukan'}</Text>
          <TouchableOpacity onPress={() => load()}>
            <Text style={styles.retry}>Coba lagi</Text>
          </TouchableOpacity>
        </View>
      </View>
    );
  }

  const muthowif = booking.muthowif_profile;
  const muthowifName = muthowif?.user?.name || 'Muthowif';
  const bookingMeta = bookingStatusMeta(booking.status);
  const paymentMeta = paymentStatusMeta(booking.payment_status);
  const unpaid = needsPayment(booking);
  const showChat = canOpenChat(booking);
  const nights = billingNights(booking.starts_on, booking.ends_on);
  const emergency = booking.emergency || {};
  const emergencyReport = emergency.report;
  const replacementOffers = emergency.replacement_offers || [];
  const showEmergencyZone = booking.status === 'confirmed' && booking.payment_status === 'paid';

  return (
    <View style={styles.container}>
      <ScreenHeader title={booking.booking_code} onBack={() => navigation.goBack()} />

      <ScrollView
        contentContainerStyle={styles.scroll}
        refreshControl={
          <RefreshControl refreshing={refreshing} onRefresh={() => load(true)} tintColor={colors.baytgo} />
        }
      >
        <View style={styles.heroCard}>
          {muthowif?.avatar ? (
            <Image source={{ uri: resolveMediaUrl(muthowif.avatar) }} style={styles.avatar} />
          ) : null}
          <Text style={styles.muthowifName}>{muthowifName}</Text>
          <View style={styles.pillRow}>
            <StatusPill label={bookingMeta.label} color={bookingMeta.color} />
            <StatusPill label={paymentMeta.label} color={paymentMeta.color} />
          </View>
          <Text style={styles.total}>
            {formatIdr(customerPayableAmount(booking.pricing, booking.total_amount))}
          </Text>
          {booking.pricing?.base > 0 && booking.pricing?.platform_fee > 0 ? (
            <Text style={styles.totalHint}>
              Termasuk biaya platform {booking.pricing.platform_fee_percent || 7.5}%
            </Text>
          ) : null}
        </View>

        <View style={styles.section}>
          <Text style={styles.sectionTitle}>Perjalanan</Text>
          <InfoRow label="Tanggal" value={formatDateRange(booking.starts_on, booking.ends_on)} />
          <InfoRow label="Durasi" value={`${nights} hari`} />
          <InfoRow label="Layanan" value={serviceTypeLabel(booking.service_type)} />
          <InfoRow label="Jumlah jamaah" value={String(booking.pilgrim_count)} />
          {booking.with_same_hotel ? <InfoRow label="Hotel sama" value="Ya" /> : null}
          {booking.with_transport ? <InfoRow label="Transport" value="Ya" /> : null}
        </View>

        {(booking.documents || []).length > 0 ? (
          <BookingDocumentGallery
            token={token}
            bookingId={bookingId}
            documents={booking.documents}
            title="Dokumen Anda"
          />
        ) : null}

        <View style={styles.section}>
          <Text style={styles.sectionTitle}>Rincian Biaya</Text>
          <CustomerPricingBreakdown pricing={booking.pricing} />
        </View>

        {booking.paid_at ? (
          <View style={styles.section}>
            <Text style={styles.sectionTitle}>Pembayaran</Text>
            <InfoRow label="Dibayar pada" value={new Date(booking.paid_at).toLocaleString('id-ID')} />
          </View>
        ) : null}

        {(booking.refund_requests?.length > 0 || booking.reschedule_requests?.length > 0) ? (
          <View style={styles.section}>
            <Text style={styles.sectionTitle}>Riwayat Perubahan</Text>
            {booking.refund_requests?.map((req) => (
              <View key={req.id} style={styles.historyItem}>
                <Text style={styles.historyTitle}>Refund — {changeRequestStatusLabel(req.status)}</Text>
                {req.reason ? <Text style={styles.historyNote}>{req.reason}</Text> : null}
                {req.created_at ? (
                  <Text style={styles.historyDate}>{new Date(req.created_at).toLocaleString('id-ID')}</Text>
                ) : null}
              </View>
            ))}
            {booking.reschedule_requests?.map((req) => (
              <View key={req.id} style={styles.historyItem}>
                <Text style={styles.historyTitle}>
                  Reschedule — {changeRequestStatusLabel(req.status)}
                </Text>
                <Text style={styles.historyNote}>{formatDateRange(req.starts_on, req.ends_on)}</Text>
                {req.reason ? <Text style={styles.historyNote}>{req.reason}</Text> : null}
              </View>
            ))}
          </View>
        ) : null}

        {booking.review ? (
          <View style={styles.section}>
            <View style={styles.reviewHeader}>
              <Text style={styles.sectionTitle}>Ulasan Anda</Text>
              {canReviewBooking(booking) ? (
                <TouchableOpacity
                  onPress={() => navigation.navigate('BookingRating', {
                    bookingId: booking.id,
                    mode: 'review',
                    initialRating: booking.review.rating,
                    initialComment: booking.review.comment || '',
                  })}
                >
                  <Text style={styles.editLink}>Edit</Text>
                </TouchableOpacity>
              ) : null}
            </View>
            <View style={styles.reviewRow}>
              {Array.from({ length: 5 }).map((_, i) => (
                <Ionicons
                  key={i}
                  name={i < booking.review.rating ? 'star' : 'star-outline'}
                  size={16}
                  color="#F59E0B"
                />
              ))}
            </View>
            {booking.review.comment ? (
              <Text style={styles.reviewComment}>{booking.review.comment}</Text>
            ) : null}
          </View>
        ) : null}

        {showEmergencyZone ? (
          <View style={[styles.section, styles.emergencySection]}>
            <View style={styles.emergencyHeader}>
              <Ionicons name="warning" size={20} color="#B91C1C" />
              <Text style={styles.emergencyTitle}>Insiden Darurat</Text>
            </View>

            {emergency.has_replacement ? (
              <View style={styles.emergencySuccess}>
                <Text style={styles.emergencySuccessTitle}>Pengganti muthowif aktif</Text>
                <Text style={styles.emergencySuccessText}>
                  Layanan dilanjutkan dengan {muthowifName}.
                </Text>
              </View>
            ) : emergencyReport ? (
              <>
                <Text style={styles.emergencyStatus}>
                  {emergencyReport.case_type_label} · {emergencyReport.status_label}
                </Text>
                {emergencyReport.description ? (
                  <Text style={styles.emergencyDesc}>{emergencyReport.description}</Text>
                ) : null}

                {['submitted', 'under_review'].includes(emergencyReport.status) ? (
                  <Text style={styles.emergencyHint}>Tim Bayt-GO sedang meninjau laporan Anda.</Text>
                ) : null}

                {emergencyReport.status === 'verified' && replacementOffers.length === 0 ? (
                  <Text style={styles.emergencyHint}>Menunggu muthowif pengganti tersedia.</Text>
                ) : null}

                {replacementOffers.length > 0 ? (
                  <View style={styles.candidateList}>
                    <Text style={styles.candidateHeading}>Pilih muthowif pengganti</Text>
                    {replacementOffers.map((offer) => (
                      <View key={offer.id} style={styles.candidateCard}>
                        <View style={styles.candidateRow}>
                          {offer.muthowif?.avatar ? (
                            <Image source={{ uri: resolveMediaUrl(offer.muthowif.avatar) }} style={styles.candidateAvatar} />
                          ) : null}
                          <View style={styles.candidateMeta}>
                            <Text style={styles.candidateName}>{offer.muthowif?.name}</Text>
                            {offer.muthowif?.rating ? (
                              <Text style={styles.candidateRating}>★ {offer.muthowif.rating} ({offer.muthowif.reviews_count || 0})</Text>
                            ) : null}
                          </View>
                        </View>
                        <TouchableOpacity
                          style={styles.candidateBtn}
                          onPress={() => handleSelectReplacement(offer)}
                        >
                          <Text style={styles.candidateBtnText}>Pilih muthowif ini</Text>
                        </TouchableOpacity>
                      </View>
                    ))}
                  </View>
                ) : null}
              </>
            ) : booking.can_report_emergency ? (
              <>
                <Text style={styles.emergencyHint}>
                  Laporkan jika muthowif tidak dapat dihubungi, meninggalkan tugas, atau melanggar kesepakatan.
                </Text>
                <TouchableOpacity
                  style={styles.emergencyBtn}
                  onPress={() => navigation.navigate('BookingEmergencyReport', {
                    bookingId: booking.id,
                    caseTypes: emergency.case_types || [],
                  })}
                >
                  <Text style={styles.emergencyBtnText}>Lapor Insiden Darurat</Text>
                </TouchableOpacity>
              </>
            ) : null}
          </View>
        ) : null}

        {booking.is_support && booking.payment_status === 'paid' && booking.status === 'in_progress' ? (
          <View style={[styles.section, styles.supportSection]}>
            <Text style={styles.sectionTitle}>Penyelesaian layanan pendukung</Text>
            <Text style={styles.supportIntro}>
              Setelah layanan selesai, kirim permintaan agar muthowif mengonfirmasi penyelesaian.
            </Text>
            {hasSupportCompletionPending(booking) ? (
              <View style={styles.pendingBanner}>
                <Ionicons name="time-outline" size={16} color="#B45309" />
                <Text style={styles.pendingText}>Menunggu konfirmasi muthowif</Text>
              </View>
            ) : canRequestSupportCompletion(booking) ? (
              <ActionBtn
                icon="checkmark-done-outline"
                label={requestingCompletion ? 'Mengirim...' : 'Minta penyelesaian layanan'}
                variant="primary"
                onPress={handleRequestSupportCompletion}
              />
            ) : null}
          </View>
        ) : null}

        <View style={styles.actions}>
          {unpaid ? (
            <TouchableOpacity style={styles.payBtn} onPress={() => navigation.navigate('BookingPayment', { bookingId: booking.id, bookingCode: booking.booking_code })} activeOpacity={0.9}>
              <LinearGradient colors={[colors.baytgo, colors.baytgoDark]} style={styles.payGradient}>
                <Ionicons name="wallet-outline" size={18} color={colors.white} />
                <Text style={styles.payText}>Bayar sekarang</Text>
              </LinearGradient>
            </TouchableOpacity>
          ) : null}

          {canCompleteBooking(booking) ? (
            <ActionBtn
              icon="checkmark-circle-outline"
              label="Selesaikan layanan"
              variant="primary"
              onPress={() => navigation.navigate('BookingRating', { bookingId: booking.id, mode: 'complete' })}
            />
          ) : null}

          {canReviewBooking(booking) && !booking.review ? (
            <ActionBtn
              icon="star-outline"
              label="Beri ulasan"
              onPress={() => navigation.navigate('BookingRating', { bookingId: booking.id, mode: 'review' })}
            />
          ) : null}

          {canViewInvoice(booking) ? (
            <ActionBtn
              icon="document-text-outline"
              label="Lihat invoice"
              onPress={() => navigation.navigate('BookingInvoice', { bookingId: booking.id })}
            />
          ) : null}

          {showChat ? (
            <ActionBtn
              icon="chatbubbles-outline"
              label="Chat dengan muthowif"
              onPress={() => {
                navigation.getParent()?.navigate('ChatTab', {
                  screen: 'ChatRoom',
                  params: {
                    bookingId: booking.id,
                    bookingCode: booking.booking_code,
                    otherName: muthowifName,
                  },
                });
              }}
            />
          ) : null}

          {canRequestRefund(booking) ? (
            <ActionBtn
              icon="cash-outline"
              label="Ajukan refund"
              onPress={() => navigation.navigate('BookingRefund', { bookingId: booking.id })}
            />
          ) : null}

          {canRequestReschedule(booking) ? (
            <ActionBtn
              icon="calendar-outline"
              label="Ajukan reschedule"
              onPress={() => navigation.navigate('BookingReschedule', {
                bookingId: booking.id,
                startsOn: booking.starts_on,
                endsOn: booking.ends_on,
              })}
            />
          ) : null}

          {hasPendingReschedule(booking) ? (
            <View style={styles.pendingBanner}>
              <Ionicons name="time-outline" size={16} color="#B45309" />
              <Text style={styles.pendingText}>Permintaan reschedule sedang diproses</Text>
            </View>
          ) : null}

          {canCancelBooking(booking) ? (
            <ActionBtn
              icon="close-circle-outline"
              label={cancelling ? 'Membatalkan...' : 'Batalkan pesanan'}
              danger
              onPress={handleCancel}
            />
          ) : null}
        </View>
      </ScrollView>
    </View>
  );
}

const styles = StyleSheet.create({
  container: { flex: 1, backgroundColor: colors.canvas },
  scroll: { padding: 16, paddingBottom: 32 },
  loader: { marginTop: 40 },
  empty: { padding: 24, alignItems: 'center' },
  emptyText: { fontSize: 14, color: colors.slate500, fontWeight: '600', textAlign: 'center' },
  retry: { marginTop: 10, fontSize: 14, fontWeight: '800', color: colors.baytgo },
  heroCard: {
    backgroundColor: colors.white,
    borderRadius: 20,
    padding: 20,
    alignItems: 'center',
    borderWidth: 1,
    borderColor: colors.slate100,
    marginBottom: 12,
  },
  avatar: { width: 72, height: 72, borderRadius: 20, backgroundColor: colors.slate100 },
  muthowifName: { marginTop: 12, fontSize: 20, fontWeight: '900', color: colors.baytgo, textAlign: 'center' },
  pillRow: { flexDirection: 'row', flexWrap: 'wrap', gap: 8, marginTop: 12, justifyContent: 'center' },
  pill: { borderRadius: 999, paddingHorizontal: 10, paddingVertical: 5 },
  pillText: { fontSize: 11, fontWeight: '800' },
  total: { marginTop: 14, fontSize: 22, fontWeight: '900', color: colors.slate900 },
  totalHint: { marginTop: 4, fontSize: 11, fontWeight: '600', color: colors.slate500 },
  section: {
    backgroundColor: colors.white,
    borderRadius: 18,
    padding: 16,
    marginBottom: 12,
    borderWidth: 1,
    borderColor: colors.slate100,
  },
  sectionTitle: { fontSize: 15, fontWeight: '900', color: colors.baytgo, marginBottom: 12 },
  supportSection: { borderColor: '#A7F3D0', backgroundColor: '#F0FDF4' },
  supportIntro: { fontSize: 13, lineHeight: 20, color: colors.slate600, fontWeight: '500', marginBottom: 12 },
  infoRow: {
    flexDirection: 'row',
    justifyContent: 'space-between',
    gap: 12,
    paddingVertical: 8,
    borderBottomWidth: 1,
    borderBottomColor: colors.slate100,
  },
  infoLabel: { fontSize: 13, fontWeight: '600', color: colors.slate500 },
  infoValue: { flex: 1, fontSize: 13, fontWeight: '800', color: colors.slate900, textAlign: 'right' },
  historyItem: {
    backgroundColor: colors.slate100,
    borderRadius: 12,
    padding: 12,
    marginBottom: 8,
  },
  historyTitle: { fontSize: 13, fontWeight: '800', color: colors.slate900 },
  historyNote: { marginTop: 4, fontSize: 12, color: colors.slate600, fontWeight: '500' },
  historyDate: { marginTop: 4, fontSize: 11, color: colors.slate400, fontWeight: '600' },
  reviewHeader: { flexDirection: 'row', justifyContent: 'space-between', alignItems: 'center', marginBottom: 4 },
  editLink: { fontSize: 13, fontWeight: '800', color: colors.baytgo },
  reviewRow: { flexDirection: 'row', gap: 4 },
  reviewComment: { marginTop: 10, fontSize: 14, lineHeight: 20, color: colors.slate600, fontWeight: '500' },
  actions: { gap: 10, marginTop: 4 },
  actionBtn: {
    flexDirection: 'row',
    alignItems: 'center',
    justifyContent: 'center',
    gap: 8,
    backgroundColor: colors.white,
    borderRadius: 16,
    paddingVertical: 14,
    borderWidth: 1,
    borderColor: colors.slate100,
  },
  actionPrimary: { backgroundColor: colors.emerald600, borderColor: colors.emerald600 },
  actionDanger: { backgroundColor: '#FEF2F2', borderColor: '#FECACA' },
  actionText: { fontSize: 14, fontWeight: '800', color: colors.baytgo },
  actionTextPrimary: { color: colors.white },
  actionTextDanger: { color: '#B91C1C' },
  pendingBanner: {
    flexDirection: 'row',
    alignItems: 'center',
    gap: 8,
    backgroundColor: '#FFFBEB',
    borderRadius: 12,
    padding: 12,
    borderWidth: 1,
    borderColor: '#FDE68A',
  },
  pendingText: { flex: 1, fontSize: 12, fontWeight: '700', color: '#92400E' },
  payBtn: { borderRadius: 16, overflow: 'hidden' },
  payGradient: {
    flexDirection: 'row',
    alignItems: 'center',
    justifyContent: 'center',
    gap: 8,
    paddingVertical: 16,
  },
  payText: { color: colors.white, fontSize: 15, fontWeight: '800' },
  emergencySection: { borderColor: '#FECACA', backgroundColor: '#FFFBFB' },
  emergencyHeader: { flexDirection: 'row', alignItems: 'center', gap: 8, marginBottom: 10 },
  emergencyTitle: { fontSize: 15, fontWeight: '900', color: '#991B1B' },
  emergencyStatus: { fontSize: 13, fontWeight: '800', color: '#92400E' },
  emergencyDesc: { marginTop: 8, fontSize: 13, lineHeight: 20, color: colors.slate700, fontWeight: '500' },
  emergencyHint: { marginTop: 10, fontSize: 12, lineHeight: 18, color: colors.slate600, fontWeight: '600' },
  emergencyBtn: {
    marginTop: 12,
    backgroundColor: '#B91C1C',
    borderRadius: 14,
    paddingVertical: 14,
    alignItems: 'center',
  },
  emergencyBtnText: { color: colors.white, fontSize: 14, fontWeight: '800' },
  emergencySuccess: {
    backgroundColor: colors.emerald50,
    borderRadius: 12,
    padding: 12,
    borderWidth: 1,
    borderColor: '#A7F3D0',
  },
  emergencySuccessTitle: { fontSize: 13, fontWeight: '800', color: colors.emerald600 },
  emergencySuccessText: { marginTop: 4, fontSize: 12, color: colors.slate700, fontWeight: '600' },
  candidateList: { marginTop: 14, gap: 10 },
  candidateHeading: { fontSize: 13, fontWeight: '800', color: colors.slate900 },
  candidateCard: {
    backgroundColor: colors.white,
    borderRadius: 14,
    padding: 12,
    borderWidth: 1,
    borderColor: '#A7F3D0',
  },
  candidateRow: { flexDirection: 'row', gap: 10, alignItems: 'center' },
  candidateAvatar: { width: 48, height: 48, borderRadius: 14, backgroundColor: colors.slate100 },
  candidateMeta: { flex: 1 },
  candidateName: { fontSize: 14, fontWeight: '800', color: colors.slate900 },
  candidateRating: { marginTop: 2, fontSize: 12, fontWeight: '600', color: '#92400E' },
  candidateBtn: {
    marginTop: 10,
    backgroundColor: colors.emerald600,
    borderRadius: 12,
    paddingVertical: 12,
    alignItems: 'center',
  },
  candidateBtnText: { color: colors.white, fontSize: 13, fontWeight: '800' },
});
