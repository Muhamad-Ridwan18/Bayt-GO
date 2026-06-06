<?php

namespace App\Http\Controllers;

use App\Http\Resources\BookingChatMessageResource;
use App\Models\BookingChatMessage;
use App\Models\MuthowifBooking;
use App\Services\UploadedImageOptimizer;
use App\Support\BookingChatBroadcast;
use App\Support\StoredImageResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\Response;

class BookingChatController extends Controller
{
    public function unreadCount(Request $request, MuthowifBooking $booking): JsonResponse
    {
        $this->authorize('viewBookingChat', $booking);

        return response()->json([
            'unread_for_me' => $this->unreadCountFor($booking, $request->user()->id),
        ]);
    }

    public function markRead(Request $request, MuthowifBooking $booking): JsonResponse
    {
        $this->authorize('viewBookingChat', $booking);

        $readerId = $request->user()->id;
        $marked = $booking->chatMessages()
            ->where('user_id', '!=', $readerId)
            ->whereNull('read_at')
            ->update(['read_at' => now()]);

        if ($marked > 0) {
            BookingChatBroadcast::notify($booking, 'read', null, (string) $readerId);
        }

        return response()->json([
            'unread_for_me' => 0,
            'marked' => $marked,
        ]);
    }

    public function index(Request $request, MuthowifBooking $booking): JsonResponse
    {
        $this->authorize('viewBookingChat', $booking);

        $readerId = $request->user()->id;

        if ($request->filled('after_id')) {
            return $this->incrementalMessages($request, $booking, $readerId);
        }

        return $this->initialMessages($booking, $readerId);
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
            $imagePath = app(UploadedImageOptimizer::class)->store(
                $upload,
                'booking-chat/'.$booking->getKey(),
                'local',
                'chat',
            );
        }

        $message = $booking->chatMessages()->create([
            'user_id' => $request->user()->id,
            'body' => $body,
            'image_path' => $imagePath,
        ]);

        $message->load('sender:id,name');

        BookingChatBroadcast::afterResponse(
            $booking,
            'message',
            (string) $message->getKey(),
            (string) $request->user()->id,
        );

        return response()->json([
            'message' => new BookingChatMessageResource($message),
            'chat_open' => $booking->isBookingChatOpen(),
        ], 201);
    }

    public function image(Request $request, MuthowifBooking $booking, BookingChatMessage $message): Response
    {
        abort_unless((string) $message->muthowif_booking_id === (string) $booking->getKey(), 404);
        $this->authorize('viewBookingChat', $booking);

        if ($message->image_path === null || $message->image_path === '') {
            abort(404);
        }

        return StoredImageResponse::fromDisk(
            'local',
            $message->image_path,
            basename($message->image_path),
            visibility: 'private',
        );
    }

    private function initialMessages(MuthowifBooking $booking, mixed $readerId): JsonResponse
    {
        $messages = $booking->chatMessages()
            ->with('sender:id,name')
            ->orderByDesc('created_at')
            ->limit(50)
            ->get()
            ->reverse()
            ->values();

        return response()->json([
            'messages' => BookingChatMessageResource::collection($messages),
            'chat_open' => $booking->isBookingChatOpen(),
            'unread_for_me' => $this->unreadCountFor($booking, $readerId),
        ]);
    }

    private function incrementalMessages(Request $request, MuthowifBooking $booking, mixed $readerId): JsonResponse
    {
        $pivotMessage = BookingChatMessage::query()
            ->where('muthowif_booking_id', $booking->getKey())
            ->find($request->input('after_id'));

        $query = $booking->chatMessages()
            ->with('sender:id,name')
            ->orderBy('created_at');

        if ($pivotMessage !== null) {
            $query->where('created_at', '>', $pivotMessage->created_at);
        }

        $messages = $query->get();

        if ($messages->isNotEmpty()) {
            $incomingIds = $messages->pluck('id');
            $marked = $booking->chatMessages()
                ->where('user_id', '!=', $readerId)
                ->whereNull('read_at')
                ->whereIn('id', $incomingIds)
                ->update(['read_at' => now()]);

            if ($marked > 0) {
                BookingChatBroadcast::notify($booking, 'read', null, (string) $readerId);
            }
        }

        return response()->json([
            'messages' => BookingChatMessageResource::collection($messages),
            'chat_open' => $booking->isBookingChatOpen(),
            'unread_for_me' => $this->unreadCountFor($booking, $readerId),
        ]);
    }

    private function unreadCountFor(MuthowifBooking $booking, mixed $readerId): int
    {
        return (int) $booking->chatMessages()
            ->where('user_id', '!=', $readerId)
            ->whereNull('read_at')
            ->count();
    }
}
