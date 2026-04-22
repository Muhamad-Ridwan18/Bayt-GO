<?php

namespace App\Http\Controllers;

use App\Enums\BookingStatus;
use App\Models\MuthowifBooking;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class GlobalChatController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $user = $request->user();
        if (! $user) {
            return response()->json(['conversations' => []]);
        }

        $bookings = collect();

        $unreadForUser = function ($q) use ($user) {
            $q->where('user_id', '!=', $user->id)->whereNull('read_at');
        };

        // Customer bookings
        if ($user->isCustomer()) {
            $customerBookings = MuthowifBooking::where('customer_id', $user->id)
                ->with(['muthowifProfile.user', 'chatMessages' => function ($q) {
                    $q->latest()->limit(1);
                }])
                ->withCount(['chatMessages as unread_count' => $unreadForUser])
                ->get();
            $bookings = $bookings->concat($customerBookings);
        }

        // Muthowif bookings
        if ($user->isMuthowif() && $user->muthowifProfile) {
            $muthowifBookings = MuthowifBooking::where('muthowif_profile_id', $user->muthowifProfile->id)
                ->with(['customer', 'chatMessages' => function ($q) {
                    $q->latest()->limit(1);
                }])
                ->withCount(['chatMessages as unread_count' => $unreadForUser])
                ->get();
            $bookings = $bookings->concat($muthowifBookings);
        }

        $bookings = $bookings->unique('id')->filter(function (MuthowifBooking $booking) {
            $isCompletedPaid = $booking->status === BookingStatus::Completed && $booking->isPaid();

            return $booking->isBookingChatOpen() || $isCompletedPaid || $booking->chatMessages->isNotEmpty();
        });

        $conversations = $bookings->map(function (MuthowifBooking $booking) use ($user) {
            $isCustomerView = $booking->customer_id === $user->id;

            if ($isCustomerView) {
                $otherName = $booking->muthowifProfile->user->name ?? 'Muthowif';
                $photoUrl = route('layanan.photo', $booking->muthowifProfile);
                $chatMessagesUrl = route('bookings.chat.messages', $booking);
                $chatStoreUrl = route('bookings.chat.messages.store', $booking);
            } else {
                $otherName = $booking->customer->name ?? 'Customer';
                $photoUrl = null;
                $chatMessagesUrl = route('muthowif.bookings.chat.messages', $booking);
                $chatStoreUrl = route('muthowif.bookings.chat.messages.store', $booking);
            }

            $latestMessage = $booking->chatMessages->first();

            return [
                'id' => $booking->id,
                'booking_code' => $booking->booking_code ?? '--',
                'other_name' => $otherName,
                'photo_url' => $photoUrl,
                'service_type' => $booking->service_type?->label() ?? 'Service',
                'is_open' => $booking->isBookingChatOpen() || ($booking->status === BookingStatus::Completed && $booking->isPaid()),
                'last_message' => $latestMessage ? ($latestMessage->body ?: '📷 Gambar') : 'Belum ada pesan',
                'last_message_time' => $latestMessage ? $latestMessage->created_at->toISOString() : $booking->created_at->toISOString(),
                'unread_count' => (int) ($booking->unread_count ?? 0),
                'fetchUrl' => $chatMessagesUrl,
                'storeUrl' => $chatStoreUrl,
            ];
        })->sortByDesc('last_message_time')->values();

        return response()->json([
            'conversations' => $conversations,
        ]);
    }
}
