<?php

namespace App\Models;

use App\Enums\ReplacementOfferStatus;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class BookingReplacementOffer extends Model
{
    use HasUuids;

    protected $fillable = [
        'booking_emergency_report_id',
        'muthowif_profile_id',
        'batch_number',
        'source',
        'status',
        'offered_at',
        'responded_at',
        'decline_note',
    ];

    protected function casts(): array
    {
        return [
            'status' => ReplacementOfferStatus::class,
            'offered_at' => 'datetime',
            'responded_at' => 'datetime',
        ];
    }

    public function report(): BelongsTo
    {
        return $this->belongsTo(BookingEmergencyReport::class, 'booking_emergency_report_id');
    }

    public function muthowifProfile(): BelongsTo
    {
        return $this->belongsTo(MuthowifProfile::class);
    }
}
