<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Support\BookingPaymentDeadlineSettings;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class BookingPaymentDeadlineSettingsController extends Controller
{
    public function edit(): View
    {
        return view('admin.booking-payment-deadline-settings.edit', [
            'values' => BookingPaymentDeadlineSettings::valuesForForm(),
            'missingDueAtCount' => BookingPaymentDeadlineSettings::countAwaitingPaymentMissingDueAt(),
        ]);
    }

    public function update(Request $request): RedirectResponse
    {
        $request->validate([
            'regular_minutes' => ['required', 'integer', 'min:1', 'max:10080'],
            'support_minutes' => ['required', 'integer', 'min:1', 'max:10080'],
        ]);

        BookingPaymentDeadlineSettings::saveFromInput($request->all());

        return redirect()
            ->route('admin.booking-payment-deadline-settings.edit')
            ->with('status', __('admin.booking_payment_deadline.settings_saved'));
    }

    public function stampMissing(Request $request): RedirectResponse
    {
        $stamped = BookingPaymentDeadlineSettings::stampMissingPaymentDueAt();

        return redirect()
            ->route('admin.booking-payment-deadline-settings.edit')
            ->with('status', __('admin.booking_payment_deadline.stamp_missing_done', ['count' => $stamped]));
    }
}
