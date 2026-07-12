import React, { useCallback, useMemo, useState } from 'react';
import { StyleSheet, Text, View, ScrollView, RefreshControl, Alert } from 'react-native';
import { useFocusEffect } from '@react-navigation/native';
import {
  Wallet, CheckCircle, Star, FileText, MessageCircle, Banknote, Calendar, XCircle, CheckCheck,
} from 'lucide-react-native';
import { LinearGradient } from 'expo-linear-gradient';
import ScreenHeader from '../components/ScreenHeader';
import BookingDocumentGallery from '../components/BookingDocumentGallery';
import { fetchBooking, cancelBooking, requestSupportCompletion } from '../api/bookings';
import { selectEmergencyReplacement } from '../api/emergency';
import { useAuth } from '../context/AuthContext';
import { useUserBookingRealtime } from '../hooks/useUserBookingRealtime';
import { useHideTabBarOnFocus } from '../hooks/useHideTabBarOnFocus';
import Button from '../ui/Button';
import ErrorState from '../ui/ErrorState';
import PressableScale from '../ui/PressableScale';
import { SkeletonList } from '../ui/Skeleton';
import StickyFooter from '../ui/StickyFooter';
import { notifySuccess } from '../utils/feedback';
import BookingEmergencySection from '../features/booking/BookingEmergencySection';
import BookingSection from '../features/booking/BookingSection';
import PendingBanner from '../features/booking/PendingBanner';
import {
  BookingActionList,
  BookingDetailHero,
  BookingProgressBar,
  BookingCancellationAlert,
  HistoryItemCard,
  ReviewCard,
  TripSummaryGrid,
} from '../features/booking/BookingDetailParts';
import { colors, gradients, layout, radius, spacing, typography } from '../theme/tokens';
import { formatIdr } from '../utils/format';
import { resolveMediaUrl } from '../utils/mediaUrl';
import {
  bookingStatusMeta, paymentStatusMeta, formatDateRange,
  needsPayment, canCancelBooking, canCompleteBooking, canReviewBooking, canViewInvoice,
  canRequestRefund, canRequestReschedule, hasPendingReschedule, canRequestSupportCompletion,
  hasSupportCompletionPending, changeRequestStatusLabel, billingNights, canOpenBookingChat,
  hasMuthowifRejectionInfo,
} from '../utils/bookingLabels';
import { CustomerPricingBreakdown, customerPayableAmount } from '../components/BookingPricingBreakdown';

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

  useFocusEffect(useCallback(() => { load(); }, [load]));
  useHideTabBarOnFocus(navigation);

  useUserBookingRealtime({
    token, userId: user?.id,
    onEvent: (payload) => {
      if (String(payload?.booking_id) === String(bookingId)) load(true);
    },
  });

  const stickyAction = useMemo(() => {
    if (!booking) return null;

    const unpaid = needsPayment(booking);
    const payable = customerPayableAmount(booking.pricing, booking.total_amount);

    if (unpaid) {
      return {
        label: 'Bayar sekarang',
        icon: <Wallet size={18} color={colors.white} strokeWidth={2} />,
        onPress: () => navigation.navigate('BookingPayment', {
          bookingId: booking.id,
          bookingCode: booking.booking_code,
        }),
        gradient: true,
        priceLabel: 'Total bayar',
        priceValue: payable,
      };
    }
    if (canCompleteBooking(booking)) {
      return {
        label: 'Selesaikan layanan',
        icon: <CheckCircle size={18} color={colors.white} strokeWidth={2} />,
        onPress: () => navigation.navigate('BookingRating', { bookingId: booking.id, mode: 'complete' }),
      };
    }
    if (canReviewBooking(booking) && !booking.review) {
      return {
        label: 'Beri ulasan',
        variant: 'secondary',
        icon: <Star size={18} color={colors.baytgo} strokeWidth={2} />,
        onPress: () => navigation.navigate('BookingRating', { bookingId: booking.id, mode: 'review' }),
      };
    }
    return null;
  }, [booking, navigation]);

  const handleSelectReplacement = (offer) => {
    const name = offer.muthowif?.name || 'Muthowif';
    Alert.alert('Pilih muthowif pengganti?', `Layanan akan dilanjutkan dengan ${name}.`, [
      { text: 'Batal', style: 'cancel' },
      {
        text: 'Pilih',
        onPress: async () => {
          try {
            await selectEmergencyReplacement(token, bookingId, offer.id);
            notifySuccess('Muthowif pengganti telah dipilih.');
            load(true);
          } catch (err) {
            Alert.alert('Gagal', err.message || 'Tidak dapat memilih pengganti');
          }
        },
      },
    ]);
  };

  const handleCancel = useCallback(() => {
    Alert.alert('Batalkan pesanan?', 'Pesanan yang dibatalkan tidak dapat dipulihkan.', [
      { text: 'Tidak', style: 'cancel' },
      {
        text: 'Ya, batalkan', style: 'destructive',
        onPress: async () => {
          setCancelling(true);
          try {
            await cancelBooking(token, bookingId);
            notifySuccess('Pesanan berhasil dibatalkan.');
            load(true);
          } catch (err) {
            Alert.alert('Gagal', err.message || 'Tidak dapat membatalkan pesanan');
          } finally {
            setCancelling(false);
          }
        },
      },
    ]);
  }, [token, bookingId, load]);

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
              notifySuccess('Permintaan penyelesaian dikirim ke muthowif.');
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

  const openChat = useCallback((muthowifName) => {
    if (!booking) return;
    navigation.getParent()?.navigate('ChatTab', {
      screen: 'ChatRoom',
      params: { bookingId: booking.id, bookingCode: booking.booking_code, otherName: muthowifName },
    });
  }, [booking, navigation]);

  const quickActions = useMemo(() => {
    if (!booking) return [];

    const muthowifName = booking.muthowif_profile?.user?.name || 'Muthowif';
    const actions = [];

    if (canViewInvoice(booking)) {
      actions.push({
        key: 'invoice',
        label: 'Lihat invoice',
        hint: 'Bukti pembayaran resmi',
        icon: FileText,
        onPress: () => navigation.navigate('BookingInvoice', { bookingId: booking.id }),
      });
    }
    if (canOpenBookingChat(booking)) {
      actions.push({
        key: 'chat',
        label: 'Chat dengan muthowif',
        hint: 'Diskusi terkait pesanan',
        icon: MessageCircle,
        tone: 'success',
        onPress: () => openChat(muthowifName),
      });
    }
    if (canRequestRefund(booking)) {
      actions.push({
        key: 'refund',
        label: 'Ajukan refund',
        hint: 'Permintaan pengembalian dana',
        icon: Banknote,
        tone: 'warning',
        onPress: () => navigation.navigate('BookingRefund', { bookingId: booking.id }),
      });
    }
    if (canRequestReschedule(booking)) {
      actions.push({
        key: 'reschedule',
        label: 'Ajukan reschedule',
        hint: 'Ubah tanggal perjalanan',
        icon: Calendar,
        onPress: () => navigation.navigate('BookingReschedule', {
          bookingId: booking.id,
          startsOn: booking.starts_on,
          endsOn: booking.ends_on,
        }),
      });
    }
    if (canCancelBooking(booking)) {
      actions.push({
        key: 'cancel',
        label: cancelling ? 'Membatalkan pesanan...' : 'Batalkan pesanan',
        hint: 'Pesanan tidak dapat dipulihkan',
        icon: XCircle,
        tone: 'danger',
        disabled: cancelling,
        onPress: handleCancel,
      });
    }

    return actions;
  }, [booking, cancelling, navigation, openChat, handleCancel]);

  if (loading && !refreshing) {
    return (
      <View style={styles.container}>
        <ScreenHeader title="Detail Pesanan" onBack={() => navigation.goBack()} />
        <SkeletonList count={3} style={styles.skeleton} />
      </View>
    );
  }

  if (error || !booking) {
    return (
      <View style={styles.container}>
        <ScreenHeader title="Detail Pesanan" onBack={() => navigation.goBack()} />
        <ErrorState description={error || 'Pesanan tidak ditemukan'} onRetry={() => load()} />
      </View>
    );
  }

  const muthowif = booking.muthowif_profile;
  const muthowifName = muthowif?.user?.name || 'Muthowif';
  const bookingMeta = bookingStatusMeta(booking.status);
  const paymentMeta = paymentStatusMeta(booking.payment_status);
  const nights = billingNights(booking.starts_on, booking.ends_on);
  const emergency = booking.emergency || {};
  const showEmergencyZone = booking.status === 'confirmed' && booking.payment_status === 'paid';
  const feeHint = booking.pricing?.base > 0 && booking.pricing?.platform_fee > 0
    ? `Termasuk biaya platform ${booking.pricing.platform_fee_percent || 7.5}%`
    : null;

  return (
    <View style={styles.container}>
      <ScreenHeader title="Detail Pesanan" onBack={() => navigation.goBack()} />

      <ScrollView
        contentContainerStyle={[styles.scroll, stickyAction && styles.scrollWithFooter]}
        refreshControl={<RefreshControl refreshing={refreshing} onRefresh={() => load(true)} tintColor={colors.baytgo} />}
        showsVerticalScrollIndicator={false}
      >
        <BookingDetailHero
          bookingCode={booking.booking_code}
          muthowifName={muthowifName}
          avatarUri={resolveMediaUrl(muthowif?.avatar)}
          bookingMeta={bookingMeta}
          paymentMeta={paymentMeta}
          amount={customerPayableAmount(booking.pricing, booking.total_amount)}
          feeHint={feeHint}
          onPressMuthowif={canOpenBookingChat(booking) ? () => openChat(muthowifName) : null}
        />

        <BookingProgressBar status={booking.status} />

        {booking.status === 'cancelled' && hasMuthowifRejectionInfo(booking) ? (
          <BookingCancellationAlert booking={booking} muthowifName={muthowifName} />
        ) : null}

        {hasPendingReschedule(booking) ? (
          <View style={styles.pendingBanner}>
            <PendingBanner text="Permintaan reschedule sedang diproses" />
          </View>
        ) : null}

        <TripSummaryGrid booking={booking} nights={nights} />

        <BookingActionList actions={quickActions} />

        {(booking.documents || []).length > 0 ? (
          <BookingDocumentGallery token={token} bookingId={bookingId} documents={booking.documents} title="Dokumen Anda" />
        ) : null}

        <BookingSection title="Rincian biaya">
          <CustomerPricingBreakdown pricing={booking.pricing} />
        </BookingSection>

        {booking.paid_at ? (
          <BookingSection title="Pembayaran">
            <Text style={styles.paidText}>
              Dibayar pada {new Date(booking.paid_at).toLocaleString('id-ID')}
            </Text>
          </BookingSection>
        ) : null}

        {(booking.refund_requests?.length > 0 || booking.reschedule_requests?.length > 0) ? (
          <BookingSection title="Riwayat perubahan">
            {booking.refund_requests?.map((req) => (
              <HistoryItemCard
                key={req.id}
                title={`Refund — ${changeRequestStatusLabel(req.status)}`}
                lines={req.reason ? [req.reason] : []}
                date={req.created_at ? new Date(req.created_at).toLocaleString('id-ID') : null}
              />
            ))}
            {booking.reschedule_requests?.map((req) => (
              <HistoryItemCard
                key={req.id}
                title={`Reschedule — ${changeRequestStatusLabel(req.status)}`}
                lines={[
                  formatDateRange(req.starts_on, req.ends_on),
                  ...(req.reason ? [req.reason] : []),
                ]}
              />
            ))}
          </BookingSection>
        ) : null}

        {booking.review ? (
          <ReviewCard
            review={booking.review}
            onEdit={canReviewBooking(booking) ? () => navigation.navigate('BookingRating', {
              bookingId: booking.id,
              mode: 'review',
              initialRating: booking.review.rating,
              initialComment: booking.review.comment || '',
            }) : null}
          />
        ) : null}

        {showEmergencyZone ? (
          <BookingEmergencySection
            booking={booking}
            emergency={emergency}
            muthowifName={muthowifName}
            onReport={() => navigation.navigate('BookingEmergencyReport', {
              bookingId: booking.id, caseTypes: emergency.case_types || [],
            })}
            onSelectReplacement={handleSelectReplacement}
          />
        ) : null}

        {booking.is_support && booking.payment_status === 'paid' && booking.status === 'in_progress' ? (
          <BookingSection title="Penyelesaian layanan pendukung" variant="success">
            <Text style={styles.supportIntro}>
              Setelah layanan selesai, kirim permintaan agar muthowif mengonfirmasi penyelesaian.
            </Text>
            {hasSupportCompletionPending(booking) ? (
              <PendingBanner text="Menunggu konfirmasi muthowif" />
            ) : canRequestSupportCompletion(booking) ? (
              <Button
                label={requestingCompletion ? 'Mengirim...' : 'Minta penyelesaian layanan'}
                icon={<CheckCheck size={18} color={colors.white} strokeWidth={2} />}
                onPress={handleRequestSupportCompletion}
                loading={requestingCompletion}
              />
            ) : null}
          </BookingSection>
        ) : null}
      </ScrollView>

      {stickyAction ? (
        <StickyFooter
          priceLabel={stickyAction.priceLabel}
          priceValue={stickyAction.priceValue != null ? formatIdr(stickyAction.priceValue) : undefined}
        >
          {stickyAction.gradient ? (
            <PressableScale onPress={stickyAction.onPress} haptic="medium" style={styles.stickyPress}>
              <LinearGradient colors={gradients.gold} style={styles.stickyGradient}>
                {stickyAction.icon}
                <Text style={styles.stickyText}>{stickyAction.label}</Text>
              </LinearGradient>
            </PressableScale>
          ) : (
            <Button
              label={stickyAction.label}
              icon={stickyAction.icon}
              variant={stickyAction.variant || 'primary'}
              onPress={stickyAction.onPress}
            />
          )}
        </StickyFooter>
      ) : null}
    </View>
  );
}

const styles = StyleSheet.create({
  container: { flex: 1, backgroundColor: colors.background },
  scroll: { padding: layout.screenPadding, paddingBottom: spacing['4xl'] },
  scrollWithFooter: { paddingBottom: 120 },
  skeleton: { padding: layout.screenPadding },
  pendingBanner: { marginBottom: spacing.md },
  paidText: { ...typography.caption, color: colors.textSecondary, fontWeight: '600' },
  supportIntro: { ...typography.caption, color: colors.textSecondary, lineHeight: 20, marginBottom: spacing.md },
  stickyPress: { borderRadius: radius.sm, overflow: 'hidden' },
  stickyGradient: {
    flexDirection: 'row', alignItems: 'center', justifyContent: 'center',
    gap: spacing.sm, paddingVertical: spacing.lg, minHeight: layout.minTouch,
  },
  stickyText: { ...typography.caption, fontFamily: 'PlusJakartaSans_700Bold', color: colors.white },
});
