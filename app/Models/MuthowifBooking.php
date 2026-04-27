<?php

namespace App\Models;

use App\Enums\BookingChangeRequestStatus;
use App\Enums\BookingStatus;
use App\Enums\MuthowifServiceType;
use App\Enums\PaymentStatus;
use Carbon\CarbonInterface;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Support\Collection;

class MuthowifBooking extends Model
{
    use HasUuids;

    protected $fillable = [
        'booking_code',
        'muthowif_profile_id',
        'customer_id',
        'service_type',
        'pilgrim_count',
        'selected_add_on_ids',
        'with_same_hotel',
        'with_transport',
        'starts_on',
        'ends_on',
        'status',
        'payment_status',
        'total_amount',
        'paid_at',
        'visa_path',
        'daily_price_snapshot',
        'same_hotel_price_snapshot',
        'transport_price_snapshot',
        'add_ons_snapshot',
    ];

    protected function casts(): array
    {
        return [
            'starts_on' => 'date',
            'ends_on' => 'date',
            'status' => BookingStatus::class,
            'service_type' => MuthowifServiceType::class,
            'selected_add_on_ids' => 'array',
            'with_same_hotel' => 'boolean',
            'with_transport' => 'boolean',
            'payment_status' => PaymentStatus::class,
            'total_amount' => 'decimal:2',
            'paid_at' => 'datetime',
            'daily_price_snapshot' => 'decimal:2',
            'same_hotel_price_snapshot' => 'decimal:2',
            'transport_price_snapshot' => 'decimal:2',
            'add_ons_snapshot' => 'array',
        ];
    }

    public function muthowifProfile(): BelongsTo
    {
        return $this->belongsTo(MuthowifProfile::class);
    }

    public function customer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'customer_id');
    }

    public function review(): HasOne
    {
        return $this->hasOne(BookingReview::class, 'muthowif_booking_id');
    }

    /**
     * @return HasMany<BookingPayment, $this>
     */
    public function bookingPayments(): HasMany
    {
        return $this->hasMany(BookingPayment::class, 'muthowif_booking_id');
    }

    /**
     * Pembayaran settlement/capture terbaru (satu baris) untuk tampilan admin / refund.
     *
     * @return HasOne<BookingPayment, $this>
     */
    public function latestSettledBookingPayment(): HasOne
    {
        return $this->hasOne(BookingPayment::class, 'muthowif_booking_id')
            ->whereIn('status', ['settlement', 'capture'])
            ->latestOfMany(['settled_at', 'id']);
    }

    /**
     * @return HasMany<BookingRefundRequest, $this>
     */
    public function refundRequests(): HasMany
    {
        return $this->hasMany(BookingRefundRequest::class, 'muthowif_booking_id');
    }

    /**
     * @return HasMany<BookingRescheduleRequest, $this>
     */
    public function rescheduleRequests(): HasMany
    {
        return $this->hasMany(BookingRescheduleRequest::class, 'muthowif_booking_id');
    }

    /**
     * @return HasMany<BookingChatMessage, $this>
     */
    public function chatMessages(): HasMany
    {
        return $this->hasMany(BookingChatMessage::class, 'muthowif_booking_id');
    }

    /**
     * Obrolan aktif: pembayaran sudah lunas dan layanan belum diselesaikan jamaah.
     */
    public function isBookingChatOpen(): bool
    {
        return in_array($this->status, [BookingStatus::Pending, BookingStatus::Confirmed, BookingStatus::Completed], true);
    }

    public function pendingRefundRequest(): ?BookingRefundRequest
    {
        return $this->refundRequests()
            ->where('status', BookingChangeRequestStatus::Pending)
            ->first();
    }

    public function pendingRescheduleRequest(): ?BookingRescheduleRequest
    {
        return $this->rescheduleRequests()
            ->where('status', BookingChangeRequestStatus::Pending)
            ->first();
    }

    public function latestBookingPayment(): ?BookingPayment
    {
        return $this->bookingPayments()->latest()->first();
    }

    public function settledBookingPayment(): ?BookingPayment
    {
        return $this->bookingPayments()
            ->whereIn('status', ['settlement', 'capture'])
            ->latest()
            ->first();
    }

    public function isBlockingCalendar(): bool
    {
        return in_array($this->status, BookingStatus::blocksAvailability(), true);
    }

    /**
     * Jumlah hari layanan inklusif (tanggal mulai & selesai dihitung sebagai hari).
     */
    public static function inclusiveSpanDays(CarbonInterface $start, CarbonInterface $end): int
    {
        $s = $start->copy()->startOfDay();
        $e = $end->copy()->startOfDay();

        return (int) max(1, $s->diffInDays($e) + 1);
    }

    /**
     * Jumlah malam menginap (inklusif tanggal mulai & selesai).
     */
    public function billingNightsInclusive(): int
    {
        if ($this->starts_on === null || $this->ends_on === null) {
            return 0;
        }

        return self::inclusiveSpanDays($this->starts_on, $this->ends_on);
    }

    /**
     * Hitung total dari tarif harian × malam + add-on + opsi tambahan.
     */
    public function computeTotalAmount(): float
    {
        return app(\App\Services\BookingPricingService::class)->calculateTotal($this);
    }

    /**
     * Nominal yang ditagih (pakai kolom snapshot jika sudah diisi).
     */
    public function resolvedAmountDue(): float
    {
        if ($this->total_amount !== null) {
            return (float) $this->total_amount;
        }

        return $this->computeTotalAmount();
    }

    public function isAwaitingPayment(): bool
    {
        return $this->status === BookingStatus::Confirmed
            && $this->payment_status === PaymentStatus::Pending;
    }

    public function isPaid(): bool
    {
        return $this->payment_status === PaymentStatus::Paid;
    }

    public function isRefunded(): bool
    {
        return $this->payment_status === PaymentStatus::Refunded;
    }

    public function isRefundPending(): bool
    {
        return $this->payment_status === PaymentStatus::RefundPending;
    }

    /**
     * @return Collection<int, MuthowifServiceAddOn>
     */
    public function resolvedAddOns(): Collection
    {
        $ids = $this->selected_add_on_ids;
        if (! is_array($ids) || $ids === [] || $this->service_type !== MuthowifServiceType::PrivateJamaah) {
            return collect();
        }

        return MuthowifServiceAddOn::query()
            ->whereIn('id', $ids)
            ->orderBy('sort_order')
            ->get();
    }
}
