<?php

namespace App\Support;

use App\Enums\MuthowifVerificationStatus;
use App\Models\MuthowifProfile;

/**
 * Reward referrer muthowif dengan bagian dari net layanan muthowif yang melayani booking.
 */
final class MuthowifReferralReward
{
    /** Persentase dari {@see BookingPayment::muthowif_net_amount} yang dialihkan ke referrer. */
    public const RATE = 0.005;

    /**
     * @return array{referrer_muthowif_profile_id: string|null, referral_reward_amount: string}
     */
    public static function paymentSnapshot(float $muthowifNet, string $serviceMuthowifProfileId): array
    {
        $net = round($muthowifNet, 2);
        if ($net <= 0) {
            return self::empty();
        }

        $referredByRaw = MuthowifProfile::query()
            ->whereKey($serviceMuthowifProfileId)
            ->value('referred_by_muthowif_profile_id');

        if ($referredByRaw === null || $referredByRaw === '') {
            return self::empty();
        }

        $referrerId = (string) $referredByRaw;
        if ($referrerId === $serviceMuthowifProfileId) {
            return self::empty();
        }

        $referrerOk = MuthowifProfile::query()
            ->whereKey($referrerId)
            ->where('verification_status', MuthowifVerificationStatus::Approved)
            ->exists();

        if (! $referrerOk) {
            return self::empty();
        }

        $reward = round($net * self::RATE, 2);
        if ($reward <= 0) {
            return self::empty();
        }

        if ($reward > $net) {
            $reward = $net;
        }

        return [
            'referrer_muthowif_profile_id' => $referrerId,
            'referral_reward_amount' => number_format($reward, 2, '.', ''),
        ];
    }

    /**
     * @return array{referrer_muthowif_profile_id: null, referral_reward_amount: string}
     */
    private static function empty(): array
    {
        return [
            'referrer_muthowif_profile_id' => null,
            'referral_reward_amount' => '0.00',
        ];
    }
}
