<?php

use App\Models\MuthowifBooking;
use Illuminate\Support\Facades\Broadcast;

Broadcast::channel('App.Models.User.{id}', function ($user, $id) {
    return (string) $user->getKey() === (string) $id;
});

Broadcast::channel('booking.chat.{booking}', function ($user, MuthowifBooking $booking) {
    return $user->can('viewBookingChat', $booking);
});

Broadcast::channel('admin.moota-webhooks', function ($user) {
    return $user->isAdmin();
});
