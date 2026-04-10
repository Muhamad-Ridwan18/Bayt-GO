<?php

namespace App\Policies;

use App\Enums\BookingStatus;
use App\Enums\PaymentStatus;
use App\Models\MuthowifBooking;
use App\Models\User;

class MuthowifBookingPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->isCustomer();
    }

    public function create(User $user): bool
    {
        return $user->isCustomer();
    }

    public function view(User $user, MuthowifBooking $booking): bool
    {
        if ($user->isCustomer() && $booking->customer_id === $user->id) {
            return true;
        }

        return $this->muthowifOwns($user, $booking);
    }

    public function pay(User $user, MuthowifBooking $booking): bool
    {
        return $user->isCustomer()
            && $booking->customer_id === $user->id
            && $booking->status === BookingStatus::Confirmed
            && $booking->payment_status === PaymentStatus::Pending;
    }

    public function invoice(User $user, MuthowifBooking $booking): bool
    {
        return $user->isCustomer()
            && $booking->customer_id === $user->id
            && $booking->payment_status === PaymentStatus::Paid;
    }

    public function complete(User $user, MuthowifBooking $booking): bool
    {
        return $user->isCustomer()
            && $booking->customer_id === $user->id
            && $booking->status === BookingStatus::Confirmed
            && $booking->payment_status === PaymentStatus::Paid;
    }

    public function review(User $user, MuthowifBooking $booking): bool
    {
        return $user->isCustomer()
            && $booking->customer_id === $user->id
            && $booking->status === BookingStatus::Completed;
    }

    public function cancelAsCustomer(User $user, MuthowifBooking $booking): bool
    {
        if (! $user->isCustomer() || $booking->customer_id !== $user->id) {
            return false;
        }

        if ($booking->status === BookingStatus::Pending) {
            return true;
        }

        return $booking->status === BookingStatus::Confirmed
            && $booking->payment_status === PaymentStatus::Pending;
    }

    public function confirm(User $user, MuthowifBooking $booking): bool
    {
        return $this->muthowifOwns($user, $booking)
            && $booking->status === BookingStatus::Pending;
    }

    public function cancelAsMuthowif(User $user, MuthowifBooking $booking): bool
    {
        if (! $this->muthowifOwns($user, $booking)) {
            return false;
        }

        if (($booking->status === BookingStatus::Confirmed || $booking->status === BookingStatus::Completed) && $booking->payment_status === PaymentStatus::Paid) {
            return false;
        }

        return in_array($booking->status, [BookingStatus::Pending, BookingStatus::Confirmed], true);
    }

    private function muthowifOwns(User $user, MuthowifBooking $booking): bool
    {
        if (! $user->isVerifiedMuthowif() || ! $user->muthowifProfile) {
            return false;
        }

        return $booking->muthowif_profile_id === $user->muthowifProfile->id;
    }
}
