<?php

namespace App\Http\Controllers\Muthowif;

use App\Http\Controllers\Controller;
use App\Services\MuthowifDashboardCalendarDataBuilder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class MuthowifDashboardCalendarController extends Controller
{
    public function __invoke(Request $request): JsonResponse
    {
        $user = $request->user();
        if (! $user?->isVerifiedMuthowif()) {
            abort(403);
        }

        $mp = $user->muthowifProfile;
        $monthQuery = $request->query('month');

        $data = MuthowifDashboardCalendarDataBuilder::build(
            $mp,
            is_string($monthQuery) ? $monthQuery : null
        );

        return response()->json([
            'calendar' => view('partials.muthowif-calendar-panel', $data)->render(),
            'blocked' => view('partials.muthowif-blocked-panel', $data)->render(),
            'month' => $data['calendarMonth']->format('Y-m'),
            'is_current_month' => $data['calendarMonth']->isSameMonth(now()),
        ]);
    }
}
