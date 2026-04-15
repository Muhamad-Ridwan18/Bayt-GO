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
            ->paginate(20)
            ->withQueryString();

        return view('muthowif.jadwal.index', [
            'blockedDates' => $upcoming,
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $profile = $request->user()->muthowifProfile;

        $validated = $request->validate([
            'blocked_on' => [
                'required',
                'date',
                'after_or_equal:today',
                Rule::unique('muthowif_blocked_dates', 'blocked_on')->where(
                    fn ($q) => $q->where('muthowif_profile_id', $profile->id)
                ),
            ],
            'note' => ['nullable', 'string', 'max:255'],
        ]);

        $profile->blockedDates()->create([
            'blocked_on' => $validated['blocked_on'],
            'note' => $validated['note'] ?? null,
        ]);

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
