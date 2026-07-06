<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\MuthowifBooking;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\Response;

class BookingDocumentApiController extends Controller
{
    public function show(Request $request, MuthowifBooking $booking, string $type): Response
    {
        $user = $request->user();
        $isCustomer = (string) $booking->customer_id === (string) $user->getKey();
        $isMuthowif = $user->muthowifProfile
            && (string) $booking->muthowif_profile_id === (string) $user->muthowifProfile->getKey();

        if (! $isCustomer && ! $isMuthowif) {
            abort(403);
        }

        $column = match ($type) {
            'outbound' => 'ticket_outbound_path',
            'return' => 'ticket_return_path',
            'passport' => 'passport_path',
            'itinerary' => 'itinerary_path',
            'visa' => 'visa_path',
            default => null,
        };

        if ($column === null) {
            abort(404);
        }

        $path = $booking->{$column};
        if ($path === null || $path === '') {
            abort(404);
        }

        $disk = Storage::disk('local');
        if (! $disk->exists($path)) {
            abort(404);
        }

        $filename = basename(str_replace('\\', '/', $path));

        return $disk->response($path, $filename, [], 'inline');
    }
}
