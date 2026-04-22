<?php

use Illuminate\Support\Facades\Broadcast;

Broadcast::channel('App.Models.User.{id}', function ($user, $id) {
    return (int) $user->id === (int) $id;
});

Broadcast::channel('booking.chat.{booking}', function ($user, \App\Models\MuthowifBooking $booking) {
    return $user->can('viewBookingChat', $booking);
});
