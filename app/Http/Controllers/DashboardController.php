<?php

namespace App\Http\Controllers;

use App\Support\WelcomePageCache;
use App\ViewModels\Dashboard\AdminDashboardPageData;
use App\ViewModels\Dashboard\CustomerDashboardPageData;
use App\ViewModels\Dashboard\MuthowifDashboardPageData;
use Illuminate\Http\Request;
use Illuminate\View\View;

final class DashboardController extends Controller
{
    public function __invoke(Request $request): View
    {
        $user = $request->user();
        $welcome = WelcomePageCache::data();
        $kind = $this->resolveKind($user);

        $payload = [
            'activeCampaigns' => $welcome['activeCampaigns'],
            'featuredMuthowifs' => $welcome['featuredMuthowifs'],
            'latestArticles' => $welcome['latestArticles'],
            'galleryImages' => $welcome['galleryImages'],
            'dashboardKind' => $kind,
            'customerPage' => null,
            'muthowifPage' => null,
            'adminPage' => null,
        ];

        if ($kind === 'customer') {
            $payload['customerPage'] = CustomerDashboardPageData::for($user, $welcome);
        } elseif ($kind === 'muthowif') {
            $month = $request->query('month');
            $payload['muthowifPage'] = MuthowifDashboardPageData::for(
                $user,
                is_string($month) ? $month : null,
            );
        } elseif ($kind === 'admin') {
            $payload['adminPage'] = AdminDashboardPageData::make();
        }

        return view('dashboard', $payload);
    }

    private function resolveKind(\App\Models\User $user): string
    {
        if ($user->isCustomer()) {
            return 'customer';
        }
        if ($user->isAdmin()) {
            return 'admin';
        }
        if ($user->isVerifiedMuthowif()) {
            return 'muthowif';
        }
        if ($user->isMuthowif()) {
            return 'muthowif_pending';
        }

        return 'default';
    }
}
