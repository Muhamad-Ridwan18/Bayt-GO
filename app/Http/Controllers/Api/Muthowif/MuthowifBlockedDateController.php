<?php

namespace App\Http\Controllers\Api\Muthowif;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\MuthowifBlockedDate;
use Carbon\Carbon;

class MuthowifBlockedDateController extends Controller
{
    public function index(Request $request)
    {
        $user = $request->user();
        $blockedDates = $user->muthowifProfile->blockedDates()
            ->where('blocked_on', '>=', now()->toDateString())
            ->orderBy('blocked_on', 'asc')
            ->get()
            ->map(function($bd) {
                return [
                    'id' => $bd->id,
                    'date' => $bd->blocked_on->format('d M Y'),
                    'raw_date' => $bd->blocked_on->toDateString(),
                    'note' => $bd->note ?? 'Tanpa keterangan'
                ];
            });

        return response()->json([
            'blocked_dates' => $blockedDates
        ]);
    }

    public function store(Request $request)
    {
        $request->validate([
            'blocked_on' => 'nullable|date|after_or_equal:today',
            'start_date' => 'nullable|date|after_or_equal:today',
            'end_date' => 'nullable|date|after_or_equal:start_date',
            'note' => 'nullable|string|max:255'
        ]);

        $startDate = $request->start_date ? Carbon::parse($request->start_date) : null;
        $endDate = $request->end_date ? Carbon::parse($request->end_date) : null;

        if (!$startDate && $request->blocked_on) {
            $startDate = Carbon::parse($request->blocked_on);
            $endDate = Carbon::parse($request->blocked_on);
        }

        if (!$startDate) {
            return response()->json(['message' => 'Silakan pilih tanggal mulai atau tanggal libur.'], 422);
        }

        $user = $request->user();
        $current = $startDate->copy();
        $added = [];

        while ($current->lte($endDate)) {
            $bd = $user->muthowifProfile->blockedDates()->firstOrCreate(
                ['blocked_on' => $current->toDateString()],
                ['note' => $request->note]
            );
            $added[] = $bd;
            $current->addDay();
        }

        return response()->json([
            'message' => 'Jadwal libur berhasil disimpan',
            'blocked_dates' => $added
        ]);
    }

    public function destroy(Request $request, $id)
    {
        $user = $request->user();
        $blockedDate = $user->muthowifProfile->blockedDates()->findOrFail($id);
        $blockedDate->delete();

        return response()->json([
            'message' => 'Jadwal libur berhasil dihapus'
        ]);
    }
}
