<?php

namespace App\Http\Controllers\Api;

use App\Enums\BookingStatus;
use App\Http\Controllers\Controller;
use App\Models\MuthowifBooking;
use App\Services\ChatInboxService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class GlobalChatApiController extends Controller
{
    public function conversations(Request $request, ChatInboxService $inbox): JsonResponse
    {
        $user = $request->user();
        $limit = (int) $request->query('limit', ChatInboxService::DEFAULT_LIMIT);
        $bookings = $inbox->bookingsFor($user, $limit);
        $latestByBooking = $inbox->latestMessagesForBookings($bookings->pluck('id')->all());

        $conversations = $bookings->map(function (MuthowifBooking $booking) use ($user, $latestByBooking) {
            $isCustomerView = (string) $booking->customer_id === (string) $user->id;

            $otherName = $isCustomerView
                ? ($booking->muthowifProfile?->user?->name ?? 'Muthowif')
                : ($booking->customer?->name ?? 'Jamaah');

            $latestMessage = $latestByBooking[(string) $booking->getKey()] ?? null;

            return [
                'booking_id' => $booking->id,
                'booking_code' => $booking->booking_code ?? '--',
                'other_name' => $otherName,
                'is_open' => $booking->isBookingChatOpen()
                    || ($booking->status === BookingStatus::Completed && $booking->isPaid()),
                'last_message' => $latestMessage
                    ? ($latestMessage->body !== '' ? $latestMessage->body : '📷 Gambar')
                    : 'Belum ada pesan',
                'last_message_time' => $latestMessage
                    ? $latestMessage->created_at->toISOString()
                    : ($booking->last_message_at?->toISOString() ?? $booking->created_at->toISOString()),
                'unread_count' => (int) ($booking->unread_count ?? 0),
            ];
        })->values();

        return response()->json(['conversations' => $conversations]);
    }
}
