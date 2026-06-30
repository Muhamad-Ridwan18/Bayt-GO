<?php

namespace App\Jobs;

use App\Models\BookingChatMessage;
use App\Models\MuthowifBooking;
use App\Models\User;
use App\Services\BookingChatPushNotifier;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

class SendBookingChatPushNotification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public string $bookingId,
        public string $messageId,
        public string $senderId,
    ) {}

    public function handle(BookingChatPushNotifier $notifier): void
    {
        $booking = MuthowifBooking::query()
            ->with(['customer:id,name', 'muthowifProfile.user:id,name'])
            ->find($this->bookingId);

        if ($booking === null) {
            return;
        }

        $message = BookingChatMessage::query()->find($this->messageId);
        $sender = User::query()->find($this->senderId);

        if ($message === null || $sender === null) {
            return;
        }

        $notifier->notifyNewMessage($booking, $message, $sender);
    }
}
