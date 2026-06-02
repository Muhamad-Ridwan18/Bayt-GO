<?php

namespace App\Models;

use App\Enums\BookingReplacementSource;
use App\Enums\BookingReplacementStatus;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class BookingReplacement extends Model
{
    use HasUuids;

    protected $fillable = [
        'booking_incident_id',
        'original_muthowif_profile_id',
        'replacement_muthowif_profile_id',
        'status',
        'source',
        'volunteered_at',
        'proposed_by_admin_id',
        'approved_by_admin_id',
        'admin_approved_at',
        'replacement_confirmed_at',
        'offered_to_customer_at',
        'customer_accepted_at',
        'customer_rejected_at',
        'admin_note',
        'replacement_decline_note',
    ];

    protected function casts(): array
    {
        return [
            'status' => BookingReplacementStatus::class,
            'source' => BookingReplacementSource::class,
            'volunteered_at' => 'datetime',
            'admin_approved_at' => 'datetime',
            'replacement_confirmed_at' => 'datetime',
            'offered_to_customer_at' => 'datetime',
            'customer_accepted_at' => 'datetime',
            'customer_rejected_at' => 'datetime',
        ];
    }

    public function incident(): BelongsTo
    {
        return $this->belongsTo(BookingIncident::class, 'booking_incident_id');
    }

    public function originalProfile(): BelongsTo
    {
        return $this->belongsTo(MuthowifProfile::class, 'original_muthowif_profile_id');
    }

    public function replacementProfile(): BelongsTo
    {
        return $this->belongsTo(MuthowifProfile::class, 'replacement_muthowif_profile_id');
    }

    public function proposedByAdmin(): BelongsTo
    {
        return $this->belongsTo(User::class, 'proposed_by_admin_id');
    }

    public function approvedByAdmin(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by_admin_id');
    }
}
