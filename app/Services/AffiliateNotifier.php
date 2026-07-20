<?php

namespace App\Services;

use App\Models\Affiliate;
use App\Models\AffiliateCommission;
use App\Models\AffiliateWithdrawal;
use App\Models\MuthowifBooking;
use App\Notifications\AffiliateCommissionAvailableNotification;
use App\Notifications\AffiliateReferralBookedNotification;
use App\Notifications\AffiliateWithdrawalApprovedNotification;
use App\Notifications\AffiliateWithdrawalPaidNotification;
use Illuminate\Support\Facades\Log;
use Throwable;

class AffiliateNotifier
{
    public function referralBooked(MuthowifBooking $booking): void
    {
        if ($booking->affiliate_id === null) {
            return;
        }

        $this->notifyAffiliateUser($booking->affiliate_id, new AffiliateReferralBookedNotification($booking));
    }

    public function commissionAvailable(AffiliateCommission $commission): void
    {
        $this->notifyAffiliateUser($commission->affiliate_id, new AffiliateCommissionAvailableNotification($commission));
    }

    public function withdrawalApproved(AffiliateWithdrawal $withdrawal): void
    {
        $this->notifyAffiliateUser($withdrawal->affiliate_id, new AffiliateWithdrawalApprovedNotification($withdrawal));
    }

    public function withdrawalPaid(AffiliateWithdrawal $withdrawal): void
    {
        $this->notifyAffiliateUser($withdrawal->affiliate_id, new AffiliateWithdrawalPaidNotification($withdrawal));
    }

    private function notifyAffiliateUser(string $affiliateId, object $notification): void
    {
        try {
            $affiliate = Affiliate::query()->with('user')->find($affiliateId);
            $user = $affiliate?->user;
            if ($user === null || ! filled($user->email)) {
                return;
            }

            $user->notify($notification);
        } catch (Throwable $e) {
            Log::warning('affiliate.notification.failed', [
                'affiliate_id' => $affiliateId,
                'notification' => $notification::class,
                'message' => $e->getMessage(),
            ]);
        }
    }
}
