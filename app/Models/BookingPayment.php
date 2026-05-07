<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;

class BookingPayment extends Model
{
    use HasUuids;

    /** Status: pending | cancelled (sesi diganti user, tetap ada untuk webhook) | settlement | capture */
    protected $fillable = [
        'muthowif_booking_id',
        'booking_code',
        'order_id',
        'gross_amount',
        'platform_fee_amount',
        'muthowif_net_amount',
        'status',
        'checkout_token',
        'gateway_transaction_id',
        'payment_type',
        'settled_at',
        'wallet_credited_at',
        'gateway_notification_payload',
    ];

    protected function casts(): array
    {
        return [
            'gross_amount' => 'integer',
            'platform_fee_amount' => 'decimal:2',
            'muthowif_net_amount' => 'decimal:2',
            'settled_at' => 'datetime',
            'wallet_credited_at' => 'datetime',
            'gateway_notification_payload' => 'array',
        ];
    }

    public function muthowifBooking(): BelongsTo
    {
        return $this->belongsTo(MuthowifBooking::class, 'muthowif_booking_id');
    }

    /**
     * Order id gateway: BG + {@see MuthowifBooking} id (tanpa dash) + {@see BookingPayment} id (tanpa dash).
     * Bukan suffix acak — selalu konsisten dengan kunci booking & baris pembayaran.
     *
     * @return array{id: string, order_id: string}
     */
    public static function newPrimaryKeyAndOrderId(string $muthowifBookingId): array
    {
        $id = (string) Str::uuid();

        return [
            'id' => $id,
            'order_id' => self::composeOrderId($muthowifBookingId, $id),
        ];
    }

    public static function composeOrderId(string $muthowifBookingId, string $bookingPaymentId): string
    {
        return 'BG-'
            .str_replace('-', '', $muthowifBookingId)
            .'-'
            .str_replace('-', '', $bookingPaymentId);
    }

    public function isSettled(): bool
    {
        return in_array($this->status, ['settlement', 'capture'], true);
    }
}
