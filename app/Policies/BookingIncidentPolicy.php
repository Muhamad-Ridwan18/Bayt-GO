<?php

namespace App\Policies;

use App\Enums\BookingIncidentOverlayStatus;
use App\Enums\BookingServicePhase;
use App\Enums\BookingStatus;
use App\Models\BookingIncident;
use App\Models\BookingReplacement;
use App\Models\MuthowifBooking;
use App\Models\User;

class BookingIncidentPolicy
{
    public function reportEmergency(User $user, MuthowifBooking $booking): bool
    {
        return $user->isCustomer()
            && (string) $booking->customer_id === (string) $user->id
            && $booking->status === BookingStatus::Confirmed
            && $booking->isPaid()
            && in_array($booking->service_phase, [BookingServicePhase::PreService, BookingServicePhase::InService], true)
            && $booking->incident_status !== BookingIncidentOverlayStatus::Open;
    }

    public function reportAsMuthowif(User $user, MuthowifBooking $booking): bool
    {
        return $this->muthowifOwns($user, $booking)
            && $booking->status === BookingStatus::Confirmed
            && $booking->isPaid()
            && ! $booking->hasOpenIncident();
    }

    public function manage(User $user): bool
    {
        return $user->isAdmin();
    }

    public function view(User $user, BookingIncident $incident): bool
    {
        if ($user->isAdmin()) {
            return true;
        }

        $booking = $incident->muthowifBooking;

        if ($user->isCustomer() && (string) $booking->customer_id === (string) $user->id) {
            return true;
        }

        return $this->muthowifOwns($user, $booking)
            || $this->isReplacementMuthowif($user, $incident);
    }

    public function acceptReplacement(User $user, BookingReplacement $replacement): bool
    {
        $booking = $replacement->incident->muthowifBooking;

        return $user->isCustomer()
            && (string) $booking->customer_id === (string) $user->id;
    }

    public function confirmReplacement(User $user, BookingReplacement $replacement): bool
    {
        $profile = $user->muthowifProfile;

        return $profile !== null
            && (string) $replacement->replacement_muthowif_profile_id === (string) $profile->getKey();
    }

    private function isReplacementMuthowif(User $user, BookingIncident $incident): bool
    {
        $profile = $user->muthowifProfile;
        if ($profile === null) {
            return false;
        }

        return $incident->replacements()
            ->where('replacement_muthowif_profile_id', $profile->getKey())
            ->exists();
    }

    private function muthowifOwns(User $user, MuthowifBooking $booking): bool
    {
        if (! $user->isVerifiedMuthowif() || ! $user->muthowifProfile) {
            return false;
        }

        return (string) $booking->muthowif_profile_id === (string) $user->muthowifProfile->id;
    }
}
