const BOOKING_STATUS = {
  pending: { label: 'Menunggu', color: '#6C5CE7' },
  confirmed: { label: 'Dikonfirmasi', color: '#0984E3' },
  in_progress: { label: 'Berlangsung', color: '#0984E3' },
  completed: { label: 'Selesai', color: '#00B894' },
  cancelled: { label: 'Dibatalkan', color: '#64748B' },
};

const PAYMENT_STATUS = {
  pending: { label: 'Belum bayar', color: '#F59E0B' },
  paid: { label: 'Lunas', color: '#00B894' },
  refund_pending: { label: 'Refund diproses', color: '#F97316' },
  refunded: { label: 'Refund selesai', color: '#64748B' },
};

const SERVICE_TYPE = {
  group: 'Grup',
  private: 'Privat',
  support: 'Layanan pendukung',
};

export function bookingStatusMeta(status) {
  return BOOKING_STATUS[status] || { label: status, color: '#64748B' };
}

export function paymentStatusMeta(status) {
  return PAYMENT_STATUS[status] || { label: status, color: '#64748B' };
}

export function serviceTypeLabel(type) {
  return SERVICE_TYPE[type] || type;
}

export function needsPayment(booking) {
  return canPayBooking(booking);
}

export function canPayBooking(booking) {
  if (!booking || booking.payment_status !== 'pending') return false;
  if (booking.status === 'cancelled') return false;
  return booking.status === 'confirmed';
}

export function isAwaitingMuthowifConfirmation(booking) {
  return booking?.status === 'pending' && booking?.payment_status === 'pending';
}

export function formatDateRange(start, end) {
  if (!start) return '—';
  if (!end || end === start) return start;
  return `${start} — ${end}`;
}

export function billingNights(start, end) {
  if (!start || !end) return 1;
  const s = new Date(`${start}T00:00:00`);
  const e = new Date(`${end}T00:00:00`);
  const diff = Math.round((e - s) / 86400000);
  return Math.max(1, diff + 1);
}

export function canCancelBooking(booking) {
  if (!booking || booking.status === 'cancelled') return false;
  if (booking.status === 'pending') return true;
  return booking.status === 'confirmed' && booking.payment_status === 'pending';
}

export function canCompleteBooking(booking) {
  return !booking?.is_support && booking?.status === 'confirmed' && booking?.payment_status === 'paid';
}

export function canRequestSupportCompletion(booking) {
  if (booking?.can_request_support_completion !== undefined) {
    return booking.can_request_support_completion;
  }
  return booking?.is_support
    && booking?.status === 'in_progress'
    && booking?.payment_status === 'paid'
    && !booking?.completion_requested_at;
}

export function hasSupportCompletionPending(booking) {
  return booking?.is_support && booking?.completion_requested_at && booking?.status !== 'completed';
}

export function canReviewBooking(booking) {
  return booking?.status === 'completed';
}

export function canViewInvoice(booking) {
  return ['paid', 'refund_pending', 'refunded'].includes(booking?.payment_status);
}

export function canRequestRefund(booking) {
  return booking?.status === 'confirmed' && booking?.payment_status === 'paid';
}

export function hasPendingReschedule(booking) {
  return (booking?.reschedule_requests || []).some((r) => r.status === 'pending');
}

export function canRequestReschedule(booking) {
  return canRequestRefund(booking) && !hasPendingReschedule(booking);
}

export function canOpenBookingChat(booking) {
  if (!booking || booking.payment_status !== 'paid') return false;
  return ['confirmed', 'in_progress', 'completed'].includes(booking.status);
}

export function changeRequestStatusLabel(status) {
  const map = {
    pending: 'Menunggu',
    approved: 'Disetujui',
    rejected: 'Ditolak',
  };
  return map[status] || status;
}
