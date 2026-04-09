<?php

namespace App\Models;

use App\Enums\BookingStatus;
use App\Enums\MuthowifServiceType;
use App\Enums\PaymentStatus;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class MuthowifBooking extends Model
{
    use HasUuids;

    protected $fillable = [
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

    /**
     * @return HasMany<BookingPayment, $this>
     */
    public function bookingPayments(): HasMany
    {
        return $this->hasMany(BookingPayment::class, 'muthowif_booking_id');
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
     * Jumlah malam menginap (inklusif tanggal mulai & selesai).
     */
    public function billingNightsInclusive(): int
    {
        if ($this->starts_on === null || $this->ends_on === null) {
            return 0;
        }

        return max(1, $this->starts_on->diffInDays($this->ends_on) + 1);
    }

    /**
     * Hitung total dari tarif harian × malam + add-on + opsi tambahan.
     */
    public function computeTotalAmount(): float
    {
        $this->loadMissing(['muthowifProfile.services.addOns']);
        $profile = $this->muthowifProfile;
        if (! $profile) {
            return 0.0;
        }

        $service = $profile->services->firstWhere('type', $this->service_type);
        $nights = $this->billingNightsInclusive();
        $daily = $service && $service->daily_price !== null ? (float) $service->daily_price : 0.0;
        $base = $nights * $daily;

        $addons = 0.0;
        if ($this->service_type === MuthowifServiceType::PrivateJamaah) {
            foreach ($this->resolvedAddOns() as $addon) {
                $addons += (float) $addon->price;
            }
        }

        $sameHotel = 0.0;
        if ($this->with_same_hotel && $service && $service->same_hotel_price_per_day !== null) {
            $sameHotel = $nights * (float) $service->same_hotel_price_per_day;
        }

        $transport = 0.0;
        if ($this->with_transport && $service && $service->transport_price_flat !== null) {
            $transport = (float) $service->transport_price_flat;
        }

        return round($base + $addons + $sameHotel + $transport, 2);
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

    /**
     * @return \Illuminate\Support\Collection<int, MuthowifServiceAddOn>
     */
    public function resolvedAddOns(): \Illuminate\Support\Collection
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
