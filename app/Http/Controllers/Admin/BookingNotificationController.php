<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Jobs\NotifyCustomerOfPaidBooking;
use App\Models\MuthowifBooking;
use Illuminate\Http\RedirectResponse;

final class BookingNotificationController extends Controller
{
    public function resendCustomerPaymentSettled(MuthowifBooking $booking): RedirectResponse
    {
        abort_unless(auth()->user()?->isAdmin(), 403);

        if ($booking->paid_at === null) {
            return back()->with('error', __('admin.finance.resend_customer_payment_wa_not_paid'));
        }

        NotifyCustomerOfPaidBooking::dispatch((string) $booking->getKey());

        return back()->with('status', __('admin.finance.resend_customer_payment_wa_ok'));
    }
}
