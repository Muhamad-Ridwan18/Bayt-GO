<?php

namespace App\Http\Controllers;

use App\Events\BookingChatUpdated;
use App\Http\Resources\BookingChatMessageResource;
use App\Models\BookingChatMessage;
use App\Models\MuthowifBooking;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\Response;

class BookingChatController extends Controller
{
    public function unreadCount(Request $request, MuthowifBooking $booking): JsonResponse
    {
        $this->authorize('viewBookingChat', $booking);

        $n = $booking->chatMessages()
            ->where('user_id', '!=', $request->user()->id)
            ->whereNull('read_at')
            ->count();

        return response()->json(['unread_for_me' => $n]);
    }

    public function index(Request $request, MuthowifBooking $booking): JsonResponse
    {
        $this->authorize('viewBookingChat', $booking);

        $readerId = $request->user()->id;

        // Mark as read secara efisien
        $toMark = $booking->chatMessages()
            ->where('user_id', '!=', $readerId)
            ->whereNull('read_at');

        if ($toMark->exists()) {
            $toMark->update(['read_at' => now()]);
            broadcast(new BookingChatUpdated($booking))->toOthers();
        }

        // Gunakan Cursor Pagination untuk performa chat yang lebih baik
        $messages = $booking->chatMessages()
            ->with('sender:id,name')
            ->orderBy('created_at', 'desc')
            ->cursorPaginate(30);

        $unreadForMe = $booking->chatMessages()
            ->where('user_id', '!=', $readerId)
            ->whereNull('read_at')
            ->count();

        return response()->json([
            'messages' => BookingChatMessageResource::collection($messages->getCollection()->reverse()),
            'next_cursor' => $messages->nextCursor()?->encode(),
            'chat_open' => $booking->isBookingChatOpen(),
            'unread_for_me' => $unreadForMe,
        ]);
    }

    public function store(Request $request, MuthowifBooking $booking): JsonResponse
    {
        $this->authorize('sendBookingChat', $booking);

        $validated = $request->validate([
            'body' => ['nullable', 'string', 'max:4000'],
            'image' => ['nullable', 'image', 'max:5120', 'mimes:jpeg,jpg,png,gif,webp'],
        ]);

        $body = trim((string) ($validated['body'] ?? ''));
        $upload = $request->file('image');

        if ($body === '' && $upload === null) {
            return response()->json(['message' => __('bookings.chat.empty_body')], 422);
        }

        $imagePath = null;
        if ($upload !== null) {
            $imagePath = $upload->store('booking-chat/'.$booking->getKey(), 'local');
        }

        $message = $booking->chatMessages()->create([
            'user_id' => $request->user()->id,
            'body' => $body,
            'image_path' => $imagePath,
        ]);

        $message->load('sender:id,name');

        broadcast(new BookingChatUpdated($booking))->toOthers();

        return response()->json([
            'message' => new BookingChatMessageResource($message),
            'chat_open' => $booking->fresh()->isBookingChatOpen(),
        ], 201);
    }

    public function image(Request $request, MuthowifBooking $booking, BookingChatMessage $message): Response
    {
        abort_unless((string) $message->muthowif_booking_id === (string) $booking->getKey(), 404);
        $this->authorize('viewBookingChat', $booking);

        if ($message->image_path === null || $message->image_path === '') {
            abort(404);
        }

        $disk = Storage::disk('local');
        if (! $disk->exists($message->image_path)) {
            abort(404);
        }

        return $disk->response($message->image_path, basename($message->image_path));
    }

}
