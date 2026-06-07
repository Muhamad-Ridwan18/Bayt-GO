<?php

namespace App\Http\Controllers;

use App\Models\MuthowifBooking;
use App\Services\ChatInboxService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class GlobalChatController extends Controller
{
    public function index(Request $request, ChatInboxService $inbox): JsonResponse
    {
        $user = $request->user();
        if (! $user) {
            return response()->json(['conversations' => []]);
        }

        $limit = (int) $request->query('limit', ChatInboxService::DEFAULT_LIMIT);
        $bookings = $inbox->bookingsFor($user, $limit);
        $latestByBooking = $inbox->latestMessagesForBookings($bookings->pluck('id')->all());

        $conversations = $bookings->map(function (MuthowifBooking $booking) use ($user, $latestByBooking) {
            $isCustomerView = (string) $booking->customer_id === (string) $user->id;

            if ($isCustomerView) {
                $otherName = $booking->muthowifProfile?->user?->name ?? 'Muthowif';
                $photoUrl = null;
                if ($booking->muthowifProfile) {
                    try {
                        $photoUrl = $booking->muthowifProfile->photoUrl();
                    } catch (\Throwable) {
                        $photoUrl = null;
                    }
                }
                $chatMessagesUrl = route('bookings.chat.messages', $booking);
                $chatStoreUrl = route('bookings.chat.messages.store', $booking);
                $chatReadUrl = route('bookings.chat.read', $booking);
            } else {
                $otherName = $booking->customer?->name ?? 'Customer';
                $photoUrl = null;
                $chatMessagesUrl = route('muthowif.bookings.chat.messages', $booking);
                $chatStoreUrl = route('muthowif.bookings.chat.messages.store', $booking);
                $chatReadUrl = route('muthowif.bookings.chat.read', $booking);
            }

            $latestMessage = $latestByBooking[(string) $booking->getKey()] ?? null;

            return [
                'id' => $booking->id,
                'booking_code' => $booking->booking_code ?? '--',
                'other_name' => $otherName,
                'photo_url' => $photoUrl,
                'service_type' => $booking->service_type?->label() ?? 'Service',
                'is_open' => $booking->isBookingChatOpen(),
                'last_message' => $latestMessage
                    ? ($latestMessage->body !== '' ? $latestMessage->body : '📷 Gambar')
                    : 'Belum ada pesan',
                'last_message_time' => $latestMessage
                    ? $latestMessage->created_at->toISOString()
                    : ($booking->last_message_at?->toISOString() ?? $booking->created_at->toISOString()),
                'unread_count' => (int) ($booking->unread_count ?? 0),
                'fetchUrl' => $chatMessagesUrl,
                'storeUrl' => $chatStoreUrl,
                'readUrl' => $chatReadUrl,
            ];
        })->values();

        return response()->json([
            'conversations' => $conversations,
        ]);
    }
}
