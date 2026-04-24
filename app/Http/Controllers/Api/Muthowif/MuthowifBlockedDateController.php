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
            'blocked_on' => 'required|date|after_or_equal:today',
            'note' => 'nullable|string|max:255'
        ]);

        $user = $request->user();
        
        // Check if already blocked
        $exists = $user->muthowifProfile->blockedDates()
            ->where('blocked_on', $request->blocked_on)
            ->exists();

        if ($exists) {
            return response()->json(['message' => 'Tanggal ini sudah Anda blokir sebelumnya.'], 422);
        }

        $blockedDate = $user->muthowifProfile->blockedDates()->create([
            'blocked_on' => $request->blocked_on,
            'note' => $request->note
        ]);

        return response()->json([
            'message' => 'Jadwal libur berhasil disimpan',
            'blocked_date' => $blockedDate
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
