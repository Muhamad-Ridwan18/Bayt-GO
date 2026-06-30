<?php

namespace App\Services;

use App\Models\BookingChatMessage;
use App\Models\MuthowifBooking;
use App\Models\User;

final class BookingChatPushNotifier
{
    public function __construct(
        private ExpoPushNotificationService $expoPush,
    ) {}

    public function notifyNewMessage(MuthowifBooking $booking, BookingChatMessage $message, User $sender): void
    {
        $recipient = $this->recipientFor($booking, $sender);
        if ($recipient === null) {
            return;
        }

        $tokens = $recipient->devicePushTokens()->pluck('token')->all();
        if ($tokens === []) {
            return;
        }

        $isCustomerView = (string) $booking->customer_id === (string) $recipient->getKey();
        $otherName = $isCustomerView
            ? ($booking->muthowifProfile?->user?->name ?? 'Muthowif')
            : ($booking->customer?->name ?? 'Jamaah');

        $preview = trim((string) $message->body);
        if ($preview === '' && $message->image_path) {
            $preview = '📷 Gambar';
        }
        if ($preview === '') {
            $preview = 'Pesan baru';
        }

        $this->expoPush->send(
            $tokens,
            $otherName,
            $preview,
            [
                'type' => 'chat',
                'booking_id' => (string) $booking->getKey(),
                'booking_code' => $booking->booking_code ?? '',
                'other_name' => $otherName,
            ],
        );
    }

    private function recipientFor(MuthowifBooking $booking, User $sender): ?User
    {
        if ((string) $booking->customer_id === (string) $sender->getKey()) {
            return $booking->muthowifProfile?->user;
        }

        if ($booking->muthowifProfile && (string) $booking->muthowifProfile->user_id === (string) $sender->getKey()) {
            return $booking->customer;
        }

        return null;
    }
}
