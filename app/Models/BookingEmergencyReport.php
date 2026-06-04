<?php

namespace App\Models;

use App\Enums\EmergencyReportCaseType;
use App\Enums\EmergencyReportStatus;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class BookingEmergencyReport extends Model
{
    use HasUuids;

    protected $fillable = [
        'muthowif_booking_id',
        'reported_by_user_id',
        'case_type',
        'description',
        'evidence_paths',
        'status',
        'verified_by_admin_id',
        'verified_at',
        'admin_note',
        'replacement_batch_number',
        'recruitment_open',
    ];

    protected function casts(): array
    {
        return [
            'case_type' => EmergencyReportCaseType::class,
            'status' => EmergencyReportStatus::class,
            'evidence_paths' => 'array',
            'verified_at' => 'datetime',
            'recruitment_open' => 'boolean',
        ];
    }

    public function muthowifBooking(): BelongsTo
    {
        return $this->belongsTo(MuthowifBooking::class);
    }

    public function reportedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reported_by_user_id');
    }

    public function verifiedByAdmin(): BelongsTo
    {
        return $this->belongsTo(User::class, 'verified_by_admin_id');
    }

    /**
     * @return HasMany<BookingReplacementOffer, $this>
     */
    public function offers(): HasMany
    {
        return $this->hasMany(BookingReplacementOffer::class);
    }

    public function acceptedOffersCount(): int
    {
        return $this->offers()
            ->where('status', \App\Enums\ReplacementOfferStatus::Accepted->value)
            ->count();
    }

    public function hasCustomerSelectionPending(): bool
    {
        return $this->status === EmergencyReportStatus::Verified
            && $this->recruitment_open
            && $this->acceptedOffersCount() > 0
            && $this->muthowifBooking?->emergency_replacement_at === null;
    }
}
