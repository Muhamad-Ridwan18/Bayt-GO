<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class BookingIncidentEvent extends Model
{
    use HasUuids;

    public $timestamps = false;

    protected $fillable = [
        'booking_incident_id',
        'event_type',
        'actor_type',
        'actor_id',
        'payload',
        'created_at',
    ];

    protected function casts(): array
    {
        return [
            'payload' => 'array',
            'created_at' => 'datetime',
        ];
    }

    public function incident(): BelongsTo
    {
        return $this->belongsTo(BookingIncident::class, 'booking_incident_id');
    }
}
