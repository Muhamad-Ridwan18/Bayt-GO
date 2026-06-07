<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class BookingChatMessageResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        /** @var \App\Models\BookingChatMessage $this */
        $user = $request->user();
        $isMe = (string) $this->user_id === (string) $user?->id;

        // Mobile API only — web chat also sends Accept: application/json but must use
        // session-authenticated web routes so <img> can load with the browser cookie.
        if ($request->is('api/*')) {
            $imageUrl = $this->image_path
                ? route('api.chat.image', ['booking' => $this->muthowif_booking_id, 'message' => $this->id])
                : null;
        } else {
            $routeName = $user?->isCustomer()
                ? 'bookings.chat.messages.image'
                : 'muthowif.bookings.chat.messages.image';

            $imageUrl = $this->image_path
                ? route($routeName, ['booking' => $this->muthowif_booking_id, 'message' => $this->id])
                : null;
        }

        return [
            'id'          => $this->id,
            'body'        => $this->body ?? '',
            'image_url'   => $imageUrl,
            'sender_id'   => $this->user_id,
            'sender_name' => $this->sender?->name ?? '—',
            'created_at'  => $this->created_at?->toIso8601String(),
            'is_me'       => $isMe,
            'is_read'     => $isMe ? $this->read_at !== null : true,
        ];
    }
}
