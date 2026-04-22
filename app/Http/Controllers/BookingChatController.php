<?php

namespace App\Http\Controllers;

use App\Models\BookingChatMessage;
use App\Models\MuthowifBooking;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\Response;

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
            'messages' => $messages->map(fn (BookingChatMessage $m) => $this->serializeMessage($m, $request, $booking)),
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

        broadcast(new \App\Events\BookingChatUpdated($booking))->toOthers();

        return response()->json([
            'message' => $this->serializeMessage($message, $request, $booking),
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

    /**
     * @return array<string, mixed>
     */
    private function serializeMessage(BookingChatMessage $m, Request $request, MuthowifBooking $booking): array
    {
        $routeName = $this->messageImageRouteName($request);

        return [
            'id' => $m->id,
            'body' => $m->body ?? '',
            'image_url' => $m->image_path
                ? route($routeName, ['booking' => $booking, 'message' => $m])
                : null,
            'sender_id' => $m->user_id,
            'sender_name' => $m->sender?->name ?? '—',
            'created_at' => $m->created_at?->toIso8601String(),
            'is_me' => (string) $m->user_id === (string) $request->user()->id,
        ];
    }

    private function messageImageRouteName(Request $request): string
    {
        if ($request->user()?->isCustomer()) {
            return 'bookings.chat.messages.image';
        }

        return 'muthowif.bookings.chat.messages.image';
    }
}
