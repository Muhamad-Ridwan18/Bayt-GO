import React, { useCallback, useMemo, useState } from 'react';
import { StyleSheet, Text, View, ScrollView, RefreshControl, Alert } from 'react-native';
import { useFocusEffect } from '@react-navigation/native';
import {
  Wallet, CheckCircle, Star, FileText, MessageCircle, Banknote, Calendar, XCircle, CheckCheck,
} from 'lucide-react-native';
import { LinearGradient } from 'expo-linear-gradient';
import ScreenHeader from '../components/ScreenHeader';
import BookingDocumentGallery from '../components/BookingDocumentGallery';
import { fetchBooking, cancelBooking, resendSupportCompletionCode } from '../api/bookings';
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
import PaymentDeadlineBanner from '../features/booking/PaymentDeadlineBanner';
import ChangePolicyNote from '../features/booking/ChangePolicyNote';
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
import { useLocale } from '../utils/locale';
import {
  bookingStatusMeta, paymentStatusMeta, formatDateRange,
  needsPayment, canCancelBooking, canCompleteBooking, canReviewBooking, canViewInvoice,
  canRequestRefund, canRequestReschedule, hasPendingReschedule, canResendSupportCompletionCode,
  changeRequestStatusLabel, billingNights, canOpenBookingChat,
  hasMuthowifRejectionInfo,
} from '../utils/bookingLabels';
import { CustomerPricingBreakdown, customerPayableAmount } from '../components/BookingPricingBreakdown';

export default function BookingDetailScreen({ navigation, route }) {
  const { token, user } = useAuth();
  const { bookingId } = route.params;
  const locale = useLocale();
  const isEn = locale === 'en';
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
      setError(err.message || (isEn ? 'Failed to load details' : 'Gagal memuat detail'));
    } finally {
      setLoading(false);
      setRefreshing(false);
    }
  }, [token, bookingId, isEn]);

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
        label: isEn ? 'Pay now' : 'Bayar sekarang',
        icon: <Wallet size={18} color={colors.white} strokeWidth={2} />,
        onPress: () => navigation.navigate('BookingPayment', {
          bookingId: booking.id,
          bookingCode: booking.booking_code,
        }),
        gradient: true,
        priceLabel: isEn ? 'Total' : 'Total bayar',
        priceValue: payable,
      };
    }
    if (canCompleteBooking(booking)) {
      return {
        label: isEn ? 'Complete service' : 'Selesaikan layanan',
        icon: <CheckCircle size={18} color={colors.white} strokeWidth={2} />,
        onPress: () => navigation.navigate('BookingRating', { bookingId: booking.id, mode: 'complete' }),
      };
    }
    if (canReviewBooking(booking) && !booking.review) {
      return {
        label: isEn ? 'Leave a review' : 'Beri ulasan',
        variant: 'secondary',
        icon: <Star size={18} color={colors.baytgo} strokeWidth={2} />,
        onPress: () => navigation.navigate('BookingRating', { bookingId: booking.id, mode: 'review' }),
      };
    }
    return null;
  }, [booking, navigation, isEn]);

  const handleSelectReplacement = (offer) => {
    const name = offer.muthowif?.name || 'Muthowif';
    Alert.alert(
      isEn ? 'Select replacement muthowif?' : 'Pilih muthowif pengganti?',
      isEn ? `The service will continue with ${name}.` : `Layanan akan dilanjutkan dengan ${name}.`,
      [
      { text: isEn ? 'Cancel' : 'Batal', style: 'cancel' },
      {
        text: isEn ? 'Select' : 'Pilih',
        onPress: async () => {
          try {
            await selectEmergencyReplacement(token, bookingId, offer.id);
            notifySuccess(isEn ? 'Replacement muthowif selected.' : 'Muthowif pengganti telah dipilih.');
            load(true);
          } catch (err) {
            Alert.alert(isEn ? 'Failed' : 'Gagal', err.message || (isEn ? 'Unable to select replacement' : 'Tidak dapat memilih pengganti'));
          }
        },
      },
    ]);
  };

  const handleCancel = useCallback(() => {
    Alert.alert(
      isEn ? 'Cancel order?' : 'Batalkan pesanan?',
      isEn ? 'A cancelled order cannot be restored.' : 'Pesanan yang dibatalkan tidak dapat dipulihkan.',
      [
      { text: isEn ? 'No' : 'Tidak', style: 'cancel' },
      {
        text: isEn ? 'Yes, cancel' : 'Ya, batalkan', style: 'destructive',
        onPress: async () => {
          setCancelling(true);
          try {
            await cancelBooking(token, bookingId);
            notifySuccess(isEn ? 'Order cancelled successfully.' : 'Pesanan berhasil dibatalkan.');
            load(true);
          } catch (err) {
            Alert.alert(isEn ? 'Failed' : 'Gagal', err.message || (isEn ? 'Unable to cancel order' : 'Tidak dapat membatalkan pesanan'));
          } finally {
            setCancelling(false);
          }
        },
      },
    ]);
  }, [token, bookingId, load, isEn]);

  const handleResendSupportCompletionCode = () => {
    Alert.alert(
      isEn ? 'Resend verification code?' : 'Kirim ulang kode verifikasi?',
      isEn ? 'A new code will be sent to your WhatsApp.' : 'Kode baru akan dikirim ke WhatsApp Anda.',
      [
        { text: isEn ? 'Cancel' : 'Batal', style: 'cancel' },
        {
          text: isEn ? 'Resend' : 'Kirim ulang',
          onPress: async () => {
            setRequestingCompletion(true);
            try {
              await resendSupportCompletionCode(token, bookingId);
              notifySuccess(isEn ? 'Verification code sent via WhatsApp.' : 'Kode verifikasi dikirim via WhatsApp.');
              load(true);
            } catch (err) {
              Alert.alert(isEn ? 'Failed' : 'Gagal', err.message || (isEn ? 'Unable to resend code' : 'Tidak dapat mengirim kode'));
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
        label: isEn ? 'View invoice' : 'Lihat invoice',
        hint: isEn ? 'Official payment proof' : 'Bukti pembayaran resmi',
        icon: FileText,
        onPress: () => navigation.navigate('BookingInvoice', { bookingId: booking.id }),
      });
    }
    if (canOpenBookingChat(booking)) {
      actions.push({
        key: 'chat',
        label: isEn ? 'Chat with muthowif' : 'Chat dengan muthowif',
        hint: isEn ? 'Order-related discussion' : 'Diskusi terkait pesanan',
        icon: MessageCircle,
        tone: 'success',
        onPress: () => openChat(muthowifName),
      });
    }
    if (canRequestRefund(booking)) {
      actions.push({
        key: 'refund',
        label: isEn ? 'Request refund' : 'Ajukan refund',
        hint: isEn ? 'Refund request' : 'Permintaan pengembalian dana',
        icon: Banknote,
        tone: 'warning',
        onPress: () => navigation.navigate('BookingRefund', {
          bookingId: booking.id,
          changePolicy: booking.change_policy,
        }),
      });
    }
    if (canRequestReschedule(booking)) {
      actions.push({
        key: 'reschedule',
        label: isEn ? 'Request reschedule' : 'Ajukan reschedule',
        hint: isEn ? 'Change travel date' : 'Ubah tanggal perjalanan',
        icon: Calendar,
        onPress: () => navigation.navigate('BookingReschedule', {
          bookingId: booking.id,
          startsOn: booking.starts_on,
          endsOn: booking.ends_on,
          changePolicy: booking.change_policy,
        }),
      });
    }
    if (canCancelBooking(booking)) {
      actions.push({
        key: 'cancel',
        label: cancelling ? (isEn ? 'Cancelling...' : 'Membatalkan pesanan...') : (isEn ? 'Cancel order' : 'Batalkan pesanan'),
        hint: isEn ? 'Order cannot be restored' : 'Pesanan tidak dapat dipulihkan',
        icon: XCircle,
        tone: 'danger',
        disabled: cancelling,
        onPress: handleCancel,
      });
    }

    return actions;
  }, [booking, cancelling, navigation, openChat, handleCancel, isEn]);

  if (loading && !refreshing) {
    return (
      <View style={styles.container}>
        <ScreenHeader title={isEn ? 'Order details' : 'Detail Pesanan'} onBack={() => navigation.goBack()} />
        <SkeletonList count={3} style={styles.skeleton} />
      </View>
    );
  }

  if (error || !booking) {
    return (
      <View style={styles.container}>
        <ScreenHeader title={isEn ? 'Order details' : 'Detail Pesanan'} onBack={() => navigation.goBack()} />
        <ErrorState description={error || (isEn ? 'Order not found' : 'Pesanan tidak ditemukan')} onRetry={() => load()} />
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
    ? (isEn
      ? `Includes platform fee ${booking.pricing.platform_fee_percent || 7.5}%`
      : `Termasuk biaya platform ${booking.pricing.platform_fee_percent || 7.5}%`)
    : null;

  return (
    <View style={styles.container}>
      <ScreenHeader title={isEn ? 'Order details' : 'Detail Pesanan'} onBack={() => navigation.goBack()} />

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

        {needsPayment(booking) && booking.payment_due_at ? (
          <View style={styles.pendingBanner}>
            <PaymentDeadlineBanner dueAt={booking.payment_due_at} onExpire={() => load(true)} />
          </View>
        ) : null}

        {booking.status === 'cancelled' && hasMuthowifRejectionInfo(booking) ? (
          <BookingCancellationAlert booking={booking} muthowifName={muthowifName} />
        ) : null}

        {hasPendingReschedule(booking) ? (
          <View style={styles.pendingBanner}>
            <PendingBanner text={isEn ? 'Reschedule request is being processed' : 'Permintaan reschedule sedang diproses'} />
          </View>
        ) : null}

        <TripSummaryGrid booking={booking} nights={nights} />

        {booking.change_policy && booking.status === 'confirmed' && booking.payment_status === 'paid' ? (
          <BookingSection title={isEn ? 'Refund & reschedule' : 'Refund & reschedule'}>
            <ChangePolicyNote policy={booking.change_policy} />
          </BookingSection>
        ) : null}

        <BookingActionList actions={quickActions} />

        {(booking.documents || []).length > 0 ? (
          <BookingDocumentGallery token={token} bookingId={bookingId} documents={booking.documents} title={isEn ? 'Your documents' : 'Dokumen Anda'} />
        ) : null}

        <BookingSection title={isEn ? 'Cost breakdown' : 'Rincian biaya'}>
          <CustomerPricingBreakdown pricing={booking.pricing} />
        </BookingSection>

        {booking.paid_at ? (
          <BookingSection title={isEn ? 'Payment' : 'Pembayaran'}>
            <Text style={styles.paidText}>
              {isEn ? 'Paid on ' : 'Dibayar pada '}
              {new Date(booking.paid_at).toLocaleString(isEn ? 'en-US' : 'id-ID')}
            </Text>
          </BookingSection>
        ) : null}

        {(booking.refund_requests?.length > 0 || booking.reschedule_requests?.length > 0) ? (
          <BookingSection title={isEn ? 'Change history' : 'Riwayat perubahan'}>
            {booking.refund_requests?.map((req) => (
              <HistoryItemCard
                key={req.id}
                title={isEn ? `Refund — ${changeRequestStatusLabel(req.status)}` : `Refund — ${changeRequestStatusLabel(req.status)}`}
                lines={req.reason ? [req.reason] : []}
                date={req.created_at ? new Date(req.created_at).toLocaleString(isEn ? 'en-US' : 'id-ID') : null}
              />
            ))}
            {booking.reschedule_requests?.map((req) => (
              <HistoryItemCard
                key={req.id}
                title={isEn ? `Reschedule — ${changeRequestStatusLabel(req.status)}` : `Reschedule — ${changeRequestStatusLabel(req.status)}`}
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

        {booking.is_support && booking.payment_status === 'paid' && ['confirmed', 'in_progress'].includes(booking.status) ? (
          <BookingSection title={isEn ? 'Completion verification code' : 'Kode verifikasi penyelesaian'} variant="success">
            <Text style={styles.supportIntro}>
              {isEn ? 'Show this code to the muthowif so the service can be completed.' : 'Tunjukkan kode ini kepada muthowif agar layanan dapat diselesaikan.'}
            </Text>
            {booking.completion_code ? (
              <Text style={styles.completionCode}>{booking.completion_code}</Text>
            ) : (
              <PendingBanner
                text={
                  isEn
                    ? 'Code is not available yet. Resend it or wait for payment confirmation.'
                    : 'Kode belum tersedia. Kirim ulang atau tunggu konfirmasi pembayaran.'
                }
              />
            )}
            {canResendSupportCompletionCode(booking) ? (
              <View style={{ marginTop: spacing.md }}>
                <Button
                  label={requestingCompletion ? (isEn ? 'Sending...' : 'Mengirim...') : (isEn ? 'Resend via WhatsApp' : 'Kirim ulang via WhatsApp')}
                  icon={<CheckCheck size={18} color={colors.white} strokeWidth={2} />}
                  onPress={handleResendSupportCompletionCode}
                  loading={requestingCompletion}
                />
              </View>
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
  completionCode: {
    ...typography.title,
    fontSize: 32,
    letterSpacing: 8,
    textAlign: 'center',
    color: colors.baytgo,
    fontWeight: '700',
    marginBottom: spacing.sm,
  },
  stickyPress: { borderRadius: radius.sm, overflow: 'hidden' },
  stickyGradient: {
    flexDirection: 'row', alignItems: 'center', justifyContent: 'center',
    gap: spacing.sm, paddingVertical: spacing.lg, minHeight: layout.minTouch,
  },
  stickyText: { ...typography.caption, fontFamily: 'PlusJakartaSans_700Bold', color: colors.white },
});
