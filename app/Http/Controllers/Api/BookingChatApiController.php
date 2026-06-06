<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\BookingChatMessageResource;
use App\Models\BookingChatMessage;
use App\Models\MuthowifBooking;
use App\Services\UploadedImageOptimizer;
use App\Support\BookingChatBroadcast;
use App\Support\StoredImageResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class BookingChatApiController extends Controller
{
    public function index(Request $request, MuthowifBooking $booking): JsonResponse
    {
        $this->authorize('viewBookingChat', $booking);

        $readerId = $request->user()->id;

        if ($request->filled('after_id')) {
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
                $marked = $booking->chatMessages()
                    ->where('user_id', '!=', $readerId)
                    ->whereNull('read_at')
                    ->whereIn('id', $messages->pluck('id'))
                    ->update(['read_at' => now()]);

                if ($marked > 0) {
                    BookingChatBroadcast::afterResponse($booking, 'read', null, (string) $readerId);
                }
            }

            return response()->json([
                'messages' => BookingChatMessageResource::collection($messages),
                'chat_open' => $booking->isBookingChatOpen(),
            ]);
        }

        $marked = $booking->chatMessages()
            ->where('user_id', '!=', $readerId)
            ->whereNull('read_at')
            ->update(['read_at' => now()]);

        if ($marked > 0) {
            BookingChatBroadcast::afterResponse($booking, 'read', null, (string) $readerId);
        }

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
            return response()->json(['message' => 'Pesan tidak boleh kosong.'], 422);
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

        if (! $message->image_path) {
            abort(404);
        }

        return StoredImageResponse::fromDisk(
            'local',
            $message->image_path,
            basename($message->image_path),
            visibility: 'private',
        );
    }
}
