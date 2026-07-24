<?php

namespace App\View\Composers;

use App\Enums\BookingStatus;
use App\Enums\MuthowifVerificationStatus;
use App\Models\MuthowifBooking;
use App\Models\MuthowifProfile;
use App\Support\AdminEmergencyReportCounts;
use App\Support\MuthowifEmergencyOfferCounts;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

final class NavigationComposer
{
    public function compose(View $view): void
    {
        $muthowifPendingIncomingCount = 0;
        $muthowifPendingEmergencyOfferCount = 0;
        $adminPendingMuthowifCount = 0;
        $adminOpenEmergencyReportCount = 0;
        $adminHubActive = false;

        $user = Auth::user();
        $user?->loadMissing('muthowifProfile');

        if ($user?->isVerifiedMuthowif()) {
            $mpNav = $user->muthowifProfile;
            if ($mpNav) {
                $muthowifPendingIncomingCount = (int) MuthowifBooking::query()
                    ->where('muthowif_profile_id', $mpNav->id)
                    ->where('status', BookingStatus::Pending)
                    ->count();
            }
            $muthowifPendingEmergencyOfferCount = MuthowifEmergencyOfferCounts::pendingOfferedCountForUser($user);
        }

        if ($user?->isAdmin()) {
            $adminOpenEmergencyReportCount = AdminEmergencyReportCounts::openCount();
            $adminPendingMuthowifCount = (int) MuthowifProfile::query()
                ->where('verification_status', MuthowifVerificationStatus::Pending)
                ->count();

            $adminHubActive = request()->routeIs([
                'admin.settings.index',
                'admin.site-appearance.*',
                'admin.articles.*',
                'admin.users.*',
                'admin.muthowif.*',
                'admin.referrals.*',
                'admin.affiliates.*',
                'admin.support-tickets.*',
                'admin.service_monitor.*',
                'admin.moota_webhooks.*',
                'admin.whatsapp-broadcast.*',
                'log-viewer.*',
            ]);
        }

        $view->with([
            'muthowifPendingIncomingCount' => $muthowifPendingIncomingCount,
            'muthowifPendingEmergencyOfferCount' => $muthowifPendingEmergencyOfferCount,
            'adminPendingMuthowifCount' => $adminPendingMuthowifCount,
            'adminOpenEmergencyReportCount' => $adminOpenEmergencyReportCount,
            'adminHubActive' => $adminHubActive,
            'muthowifManageActive' => request()->routeIs([
                'muthowif.kelola-layanan',
                'muthowif.pelayanan.*',
                'muthowif.pelayanan-pendukung.*',
                'muthowif.bookings.*',
                'affiliate.*',
            ]),
        ]);
    }
}
