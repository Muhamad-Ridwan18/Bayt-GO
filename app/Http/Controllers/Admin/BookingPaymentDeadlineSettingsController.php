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
}
