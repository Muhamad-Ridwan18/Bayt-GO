<?php

namespace App\Http\Controllers\Muthowif;

use App\Http\Controllers\Controller;
use App\Models\MuthowifBlockedDate;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class MuthowifScheduleController extends Controller
{
    public function index(Request $request): View
    {
        $profile = $request->user()->muthowifProfile;

        $upcoming = $profile->blockedDates()
            ->where('blocked_on', '>=', now()->toDateString())
            ->orderBy('blocked_on')
            ->paginate(5)
            ->withQueryString();

        return view('muthowif.jadwal.index', [
            'blockedDates' => $upcoming,
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $profile = $request->user()->muthowifProfile;

        $validated = $request->validate([
            'start_date' => [
                'required',
                'date',
                'after_or_equal:today',
            ],
            'end_date' => [
                'required',
                'date',
                'after_or_equal:start_date',
            ],
            'note' => ['nullable', 'string', 'max:255'],
        ]);

        $startDate = \Carbon\Carbon::parse($validated['start_date']);
        $endDate = \Carbon\Carbon::parse($validated['end_date']);

        $current = $startDate->copy();
        while ($current->lte($endDate)) {
            $profile->blockedDates()->firstOrCreate(
                ['blocked_on' => $current->toDateString()],
                ['note' => $validated['note'] ?? null]
            );
            $current->addDay();
        }

        return redirect()
            ->route('muthowif.jadwal.index')
            ->with('status', __('muthowif.jadwal.flash_added'));
    }

    public function destroy(MuthowifBlockedDate $blockedDate): RedirectResponse
    {
        $this->authorize('delete', $blockedDate);

        $blockedDate->delete();

        return redirect()
            ->route('muthowif.jadwal.index')
            ->with('status', __('muthowif.jadwal.flash_removed'));
    }
}
