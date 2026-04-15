<?php

namespace App\Http\Controllers;

use App\Models\BookingChatMessage;
use App\Models\MuthowifBooking;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class BookingChatController extends Controller
{
    public function index(Request $request, MuthowifBooking $booking): JsonResponse
    {
        $this->authorize('viewBookingChat', $booking);

        $messages = $booking->chatMessages()
            ->with('sender:id,name')
            ->orderBy('created_at')
            ->get();

        return response()->json([
            'messages' => $messages->map(fn (BookingChatMessage $m) => [
                'id' => $m->id,
                'body' => $m->body,
                'sender_id' => $m->user_id,
                'sender_name' => $m->sender?->name ?? '—',
                'created_at' => $m->created_at?->toIso8601String(),
                'is_me' => (string) $m->user_id === (string) $request->user()->id,
            ]),
            'chat_open' => $booking->isBookingChatOpen(),
        ]);
    }

    public function store(Request $request, MuthowifBooking $booking): JsonResponse
    {
        $this->authorize('sendBookingChat', $booking);

        $validated = $request->validate([
            'body' => ['required', 'string', 'max:4000'],
        ]);

        $body = trim($validated['body']);
        if ($body === '') {
            return response()->json(['message' => __('bookings.chat.empty_body')], 422);
        }

        $message = $booking->chatMessages()->create([
            'user_id' => $request->user()->id,
            'body' => $body,
        ]);

        $message->load('sender:id,name');

        return response()->json([
            'message' => [
                'id' => $message->id,
                'body' => $message->body,
                'sender_id' => $message->user_id,
                'sender_name' => $message->sender?->name ?? '—',
                'created_at' => $message->created_at?->toIso8601String(),
                'is_me' => true,
            ],
            'chat_open' => $booking->fresh()->isBookingChatOpen(),
        ], 201);
    }
}
