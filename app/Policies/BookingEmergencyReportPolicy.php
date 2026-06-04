<?php

namespace App\Policies;

use App\Enums\EmergencyReportStatus;
use App\Enums\ReplacementOfferStatus;
use App\Models\BookingEmergencyReport;
use App\Models\BookingReplacementOffer;
use App\Models\MuthowifBooking;
use App\Models\User;

class BookingEmergencyReportPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->isAdmin();
    }

    public function view(User $user, BookingEmergencyReport $report): bool
    {
        return $user->isAdmin();
    }

    public function reportEmergency(User $user, MuthowifBooking $booking): bool
    {
        return $user->isCustomer()
            && (string) $booking->customer_id === (string) $user->id
            && $booking->status === \App\Enums\BookingStatus::Confirmed
            && $booking->isPaid()
            && $booking->emergency_replacement_at === null
            && $booking->activeEmergencyReport() === null;
    }

    public function selectReplacement(User $user, MuthowifBooking $booking, BookingReplacementOffer $offer): bool
    {
        return $user->isCustomer()
            && (string) $booking->customer_id === (string) $user->id
            && (string) $offer->report->muthowif_booking_id === (string) $booking->getKey()
            && $offer->status === ReplacementOfferStatus::Accepted
            && $booking->emergency_replacement_at === null;
    }

    public function update(User $user, BookingEmergencyReport $report): bool
    {
        return $user->isAdmin();
    }
}
