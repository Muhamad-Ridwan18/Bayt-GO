<?php

namespace App\Events;

use App\Events\Concerns\RescuesBroadcastFailures;
use App\Models\MuthowifProfile;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class MuthowifVerificationUpdated implements ShouldBroadcastNow, RescuesBroadcastFailures
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(public MuthowifProfile $profile) {}

    public function broadcastAs(): string
    {
        return 'muthowif.verification.updated';
    }

    /**
     * @return array<int, PrivateChannel>
     */
    public function broadcastOn(): array
    {
        return [
            new PrivateChannel("App.Models.User.{$this->profile->user_id}"),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function broadcastWith(): array
    {
        return [
            'profile_id' => (string) $this->profile->getKey(),
            'verification_status' => $this->profile->verification_status instanceof \App\Enums\MuthowifVerificationStatus 
                ? $this->profile->verification_status->value 
                : (string) $this->profile->verification_status,
            'rejection_reason' => $this->profile->rejection_reason,
            'verified_at' => $this->profile->verified_at?->toDateTimeString(),
        ];
    }
}
