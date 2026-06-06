<?php

namespace App\Services;

use App\Enums\BookingStatus;
use App\Enums\PaymentStatus;
use App\Models\BookingChatMessage;
use App\Models\MuthowifBooking;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

final class ChatInboxService
{
    public const DEFAULT_LIMIT = 40;

    /**
     * @return Collection<int, MuthowifBooking>
     */
    public function bookingsFor(User $user, int $limit = self::DEFAULT_LIMIT): Collection
    {
        $readerId = $user->id;
        $limit = min(self::DEFAULT_LIMIT, max(5, $limit));

        $latestMessageSub = BookingChatMessage::query()
            ->select('created_at')
            ->whereColumn('muthowif_booking_id', 'muthowif_bookings.id')
            ->latest('created_at')
            ->limit(1);

        return MuthowifBooking::query()
            ->select('muthowif_bookings.*')
            ->with([
                'muthowifProfile:id,user_id',
                'muthowifProfile.user:id,name',
                'customer:id,name',
            ])
            ->withCount([
                'chatMessages as unread_count' => static fn (Builder $q) => $q
                    ->where('user_id', '!=', $readerId)
                    ->whereNull('read_at'),
            ])
            ->where(static function (Builder $q) use ($user): void {
                $q->where('customer_id', $user->id);
                if ($user->isMuthowif() && $user->muthowifProfile) {
                    $q->orWhere('muthowif_profile_id', $user->muthowifProfile->id);
                }
            })
            ->where(static function (Builder $q): void {
                $q->whereHas('chatMessages')
                    ->orWhere(static function (Builder $inner): void {
                        $inner->where('status', BookingStatus::Completed)
                            ->where('payment_status', PaymentStatus::Paid);
                    });
            })
            ->addSelect(['last_message_at' => $latestMessageSub])
            ->orderByDesc(DB::raw('COALESCE(last_message_at, muthowif_bookings.updated_at, muthowif_bookings.created_at)'))
            ->limit($limit)
            ->get()
            ->filter(static function (MuthowifBooking $booking): bool {
                return $booking->isBookingChatOpen()
                    || ($booking->status === BookingStatus::Completed && $booking->isPaid())
                    || ((int) ($booking->unread_count ?? 0) > 0)
                    || $booking->last_message_at !== null;
            });
    }

    /**
     * @param  list<string>  $bookingIds
     * @return array<string, BookingChatMessage>
     */
    public function latestMessagesForBookings(array $bookingIds): array
    {
        if ($bookingIds === []) {
            return [];
        }

        $latest = BookingChatMessage::query()
            ->selectRaw('muthowif_booking_id, MAX(created_at) as latest_at')
            ->whereIn('muthowif_booking_id', $bookingIds)
            ->groupBy('muthowif_booking_id');

        $rows = BookingChatMessage::query()
            ->joinSub($latest, 'latest_msgs', static function ($join): void {
                $join->on('booking_chat_messages.muthowif_booking_id', '=', 'latest_msgs.muthowif_booking_id')
                    ->on('booking_chat_messages.created_at', '=', 'latest_msgs.latest_at');
            })
            ->get(['booking_chat_messages.muthowif_booking_id', 'booking_chat_messages.body', 'booking_chat_messages.created_at']);

        $map = [];
        foreach ($rows as $row) {
            $map[(string) $row->muthowif_booking_id] = $row;
        }

        return $map;
    }
}
