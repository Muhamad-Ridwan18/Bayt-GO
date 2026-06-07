<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\BookingPayment;
use App\Models\MuthowifProfile;
use Illuminate\Http\Request;
use Illuminate\View\View;

class MuthowifReferralMonitorController extends Controller
{
    public function index(Request $request): View
    {
        $q = trim((string) $request->query('q', ''));
        $filter = $request->query('filter', 'has_referrals');
        if (! in_array($filter, ['has_referrals', 'all'], true)) {
            $filter = 'has_referrals';
        }

        $query = MuthowifProfile::query()
            ->with('user')
            ->withCount('referredMuthowifs')
            ->orderByDesc('referred_muthowifs_count')
            ->orderByDesc('created_at');

        if ($filter === 'has_referrals') {
            $query->has('referredMuthowifs');
        }

        if ($q !== '') {
            $like = '%'.$q.'%';
            $query->where(function ($builder) use ($like) {
                $builder->where('referral_code', 'like', $like)
                    ->orWhereHas('user', function ($userQuery) use ($like) {
                        $userQuery->where('name', 'like', $like)
                            ->orWhere('email', 'like', $like);
                    });
            });
        }

        $activeReferrers = MuthowifProfile::query()->has('referredMuthowifs')->count();
        $totalReferred = MuthowifProfile::query()
            ->whereNotNull('referred_by_muthowif_profile_id')
            ->count();
        $totalRewards = (float) BookingPayment::query()
            ->whereNotNull('referrer_muthowif_profile_id')
            ->where('referral_reward_amount', '>', 0)
            ->sum('referral_reward_amount');

        return view('admin.referrals.index', [
            'profiles' => $query->paginate(15)->withQueryString(),
            'q' => $q,
            'filter' => $filter,
            'stats' => [
                'referrers' => $activeReferrers,
                'referred_total' => $totalReferred,
                'rewards_total' => $totalRewards,
            ],
            'fmt' => static fn (float $n): string => number_format((int) round($n), 0, ',', '.'),
        ]);
    }

    public function show(MuthowifProfile $profile): View
    {
        $profile->load(['user', 'referredBy.user']);

        $referred = $profile->referredMuthowifs()
            ->with('user')
            ->orderByDesc('created_at')
            ->get();

        $totalRewards = (float) BookingPayment::query()
            ->where('referrer_muthowif_profile_id', $profile->id)
            ->where('referral_reward_amount', '>', 0)
            ->sum('referral_reward_amount');

        return view('admin.referrals.show', [
            'profile' => $profile,
            'referred' => $referred,
            'totalRewards' => $totalRewards,
            'fmt' => static fn (float $n): string => number_format((int) round($n), 0, ',', '.'),
        ]);
    }
}
