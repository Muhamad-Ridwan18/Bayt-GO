<?php

namespace App\Services\Incident;

use App\Models\BookingIncident;
use App\Models\BookingIncidentEvent;
use Illuminate\Support\Str;

final class BookingIncidentEventLogger
{
    public function log(
        BookingIncident $incident,
        string $eventType,
        string $actorType,
        ?string $actorId = null,
        ?array $payload = null,
    ): BookingIncidentEvent {
        return BookingIncidentEvent::query()->create([
            'id' => (string) Str::uuid(),
            'booking_incident_id' => $incident->getKey(),
            'event_type' => $eventType,
            'actor_type' => $actorType,
            'actor_id' => $actorId,
            'payload' => $payload,
            'created_at' => now(),
        ]);
    }
}
