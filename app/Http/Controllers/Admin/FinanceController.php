<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\BookingPayment;
use Illuminate\View\View;

class FinanceController extends Controller
{
    public function index(): View
    {
        $paidQuery = BookingPayment::query()->whereIn('status', ['settlement', 'capture']);

        $totalPlatformFees = (float) (clone $paidQuery)->sum('platform_fee_amount');
        $totalVolume = (int) (clone $paidQuery)->sum('gross_amount');

        $payments = BookingPayment::query()
            ->whereIn('status', ['settlement', 'capture'])
            ->with(['muthowifBooking.customer', 'muthowifBooking.muthowifProfile.user'])
            ->orderByDesc('settled_at')
            ->paginate(25);

        return view('admin.finance.index', [
            'payments' => $payments,
            'totalPlatformFees' => $totalPlatformFees,
            'totalVolume' => $totalVolume,
        ]);
    }
}
