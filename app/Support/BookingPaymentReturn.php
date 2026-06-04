<?php

declare(strict_types=1);

namespace App\Support;

use App\Models\MuthowifBooking;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

final class BookingPaymentReturn
{
    public static function normalizeShowRedirect(Request $request, MuthowifBooking $booking): ?RedirectResponse
    {
        if (strtolower((string) $request->query('source', '')) !== 'moota') {
            return null;
        }

        $status = strtoupper(trim((string) $request->query('status', '')));

        if ($status === 'SUCCESS') {
            return redirect()
                ->route('bookings.show', [$booking, 'payment' => 'success'])
                ->with('status', __('bookings.flash.moota_payment_return_success'));
        }

        if (in_array($status, ['FAILED', 'FAILURE', 'CANCELLED', 'CANCEL', 'EXPIRED'], true)) {
            return redirect()
                ->route('bookings.show', $booking)
                ->with('error', __('bookings.flash.moota_payment_return_failed'));
        }

        return null;
    }

    public static function isAwaitingGatewayConfirmation(Request $request): bool
    {
        if ((string) $request->query('payment', '') === 'success') {
            return true;
        }

        return strtolower((string) $request->query('source', '')) === 'moota'
            && strtoupper(trim((string) $request->query('status', ''))) === 'SUCCESS';
    }
}
