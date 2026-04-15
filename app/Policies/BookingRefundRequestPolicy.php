<?php

namespace App\Policies;

use App\Models\BookingRefundRequest;
use App\Models\User;

class BookingRefundRequestPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->isAdmin();
    }

    public function complete(User $user, BookingRefundRequest $refund): bool
    {
        return $user->isAdmin();
    }
}
