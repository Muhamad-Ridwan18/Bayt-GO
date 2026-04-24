<?php

namespace App\Http\Controllers\Api;

use App\Events\BookingChatUpdated;
use App\Http\Controllers\Controller;
use App\Http\Resources\BookingChatMessageResource;
use App\Models\BookingChatMessage;
use App\Models\MuthowifBooking;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\Response;

class BookingChatApiController extends Controller
{
    public function index(Request $request, MuthowifBooking $booking): JsonResponse
    {
        $this->authorize('viewBookingChat', $booking);

        $readerId = $request->user()->id;

        // Mark messages from others as read
        $toMark = $booking->chatMessages()
            ->where('user_id', '!=', $readerId)
            ->whereNull('read_at');

        if ($toMark->exists()) {
            $toMark->update(['read_at' => now()]);
        }

        // Support pagination via `after_id` for polling
        $query = $booking->chatMessages()
            ->with('sender:id,name')
            ->orderBy('created_at', 'asc');

        if ($request->filled('after_id')) {
            $pivotMessage = BookingChatMessage::find($request->input('after_id'));
            if ($pivotMessage) {
                $query->where('created_at', '>', $pivotMessage->created_at);
            }
        } else {
            // Initial load: return last 50 messages
            $query = $booking->chatMessages()
                ->with('sender:id,name')
                ->orderBy('created_at', 'desc')
                ->limit(50);

            $messages = $query->get()->reverse()->values();

            return response()->json([
                'messages' => BookingChatMessageResource::collection($messages),
                'chat_open' => $booking->isBookingChatOpen(),
            ]);
        }

        $messages = $query->get();

        return response()->json([
            'messages' => BookingChatMessageResource::collection($messages),
            'chat_open' => $booking->isBookingChatOpen(),
        ]);
    }

    public function store(Request $request, MuthowifBooking $booking): JsonResponse
    {
        $this->authorize('sendBookingChat', $booking);

        $validated = $request->validate([
            'body'  => ['nullable', 'string', 'max:4000'],
            'image' => ['nullable', 'image', 'max:5120', 'mimes:jpeg,jpg,png,gif,webp'],
        ]);

        $body   = trim((string) ($validated['body'] ?? ''));
        $upload = $request->file('image');

        if ($body === '' && $upload === null) {
            return response()->json(['message' => 'Pesan tidak boleh kosong.'], 422);
        }

        $imagePath = null;
        if ($upload !== null) {
            $imagePath = $upload->store('booking-chat/' . $booking->getKey(), 'local');
        }

        $message = $booking->chatMessages()->create([
            'user_id'    => $request->user()->id,
            'body'       => $body,
            'image_path' => $imagePath,
        ]);

        $message->load('sender:id,name');

        broadcast(new BookingChatUpdated($booking))->toOthers();

        return response()->json([
            'message'   => new BookingChatMessageResource($message),
            'chat_open' => $booking->fresh()->isBookingChatOpen(),
        ], 201);
    }

    public function image(Request $request, MuthowifBooking $booking, BookingChatMessage $message): Response
    {
        abort_unless((string) $message->muthowif_booking_id === (string) $booking->getKey(), 404);
        $this->authorize('viewBookingChat', $booking);

        if (!$message->image_path) {
            abort(404);
        }

        $disk = Storage::disk('local');
        abort_unless($disk->exists($message->image_path), 404);

        return $disk->response($message->image_path, basename($message->image_path));
    }
}
