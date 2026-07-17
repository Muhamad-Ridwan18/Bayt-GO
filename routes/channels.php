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

Broadcast::channel('admin.withdrawals', function ($user) {
    return $user->isAdmin();
});

Broadcast::channel('admin.affiliate-withdrawals', function ($user) {
    return $user->isAdmin();
});

Broadcast::channel('admin.support-tickets', function ($user) {
    return $user->isAdmin();
});

Broadcast::channel('admin.service-monitor', function ($user) {
    return $user->isAdmin();
});

Broadcast::channel('admin.emergency-reports', function ($user) {
    return $user->isAdmin();
});

Broadcast::channel('admin.muthowif-profiles', function ($user) {
    return $user->isAdmin();
});
