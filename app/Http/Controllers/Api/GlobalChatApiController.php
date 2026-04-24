<?php

namespace App\Http\Controllers\Api;

use App\Enums\BookingStatus;
use App\Http\Controllers\Controller;
use App\Models\MuthowifBooking;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class GlobalChatApiController extends Controller
{
    public function conversations(Request $request): JsonResponse
    {
        $user = $request->user();

        $bookings = collect();

        $unreadForUser = function ($q) use ($user) {
            $q->where('user_id', '!=', $user->id)->whereNull('read_at');
        };

        if ($user->isCustomer()) {
            $customerBookings = MuthowifBooking::where('customer_id', $user->id)
                ->with(['muthowifProfile.user', 'chatMessages' => fn($q) => $q->latest()->limit(1)])
                ->withCount(['chatMessages as unread_count' => $unreadForUser])
                ->get();
            $bookings = $bookings->concat($customerBookings);
        }

        if ($user->isMuthowif() && $user->muthowifProfile) {
            $muthowifBookings = MuthowifBooking::where('muthowif_profile_id', $user->muthowifProfile->id)
                ->with(['customer', 'chatMessages' => fn($q) => $q->latest()->limit(1)])
                ->withCount(['chatMessages as unread_count' => $unreadForUser])
                ->get();
            $bookings = $bookings->concat($muthowifBookings);
        }

        $conversations = $bookings
            ->unique('id')
            ->filter(function (MuthowifBooking $booking) {
                $isCompletedPaid = $booking->status === BookingStatus::Completed && $booking->isPaid();
                return $booking->isBookingChatOpen() || $isCompletedPaid || $booking->chatMessages->isNotEmpty();
            })
            ->map(function (MuthowifBooking $booking) use ($user) {
                $isCustomerView = (string) $booking->customer_id === (string) $user->id;

                $otherName = $isCustomerView
                    ? ($booking->muthowifProfile?->user?->name ?? 'Muthowif')
                    : ($booking->customer?->name ?? 'Jamaah');

                $latestMessage = $booking->chatMessages->first();

                return [
                    'booking_id'        => $booking->id,
                    'booking_code'      => $booking->booking_code ?? '--',
                    'other_name'        => $otherName,
                    'is_open'           => $booking->isBookingChatOpen()
                        || ($booking->status === BookingStatus::Completed && $booking->isPaid()),
                    'last_message'      => $latestMessage ? ($latestMessage->body ?: '📷 Gambar') : 'Belum ada pesan',
                    'last_message_time' => $latestMessage
                        ? $latestMessage->created_at->toISOString()
                        : $booking->created_at->toISOString(),
                    'unread_count'      => (int) ($booking->unread_count ?? 0),
                ];
            })
            ->sortByDesc('last_message_time')
            ->values();

        return response()->json(['conversations' => $conversations]);
    }
}
