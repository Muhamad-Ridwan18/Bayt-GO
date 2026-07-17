<?php

namespace App\Services;

use App\Enums\AffiliateStatus;
use App\Models\Affiliate;
use App\Models\MuthowifBooking;
use App\Support\AffiliateSettings;
use App\Support\PlatformFee;
use Illuminate\Validation\ValidationException;

class AffiliateAttributionService
{
    public function __construct(
        private readonly AffiliateRegistrationService $registration,
    ) {}

    /**
     * @return array{
     *     affiliate_id: string,
     *     affiliate_code_snapshot: string,
     *     affiliate_rate_snapshot: float,
     *     affiliate_base_amount_snapshot: float,
     *     affiliate_commission_amount: float
     * }|array{}
     */
    public function snapshotForBooking(
        MuthowifBooking $booking,
        ?string $affiliateCode,
        string $customerId,
        bool $isCompanyCustomer = false,
    ): array {
        $code = $this->registration->normalizeCode($affiliateCode);
        if ($code === null) {
            if (filled($affiliateCode)) {
                throw ValidationException::withMessages([
                    'affiliate_code' => ['Kode affiliate tidak valid.'],
                ]);
            }

            return [];
        }

        /** @var Affiliate|null $affiliate */
        $affiliate = Affiliate::query()
            ->where('code', $code)
            ->where('status', AffiliateStatus::Active->value)
            ->first();

        if ($affiliate === null) {
            throw ValidationException::withMessages([
                'affiliate_code' => ['Kode affiliate tidak ditemukan atau tidak aktif.'],
            ]);
        }

        if ((string) $affiliate->user_id === (string) $customerId) {
            throw ValidationException::withMessages([
                'affiliate_code' => ['Anda tidak dapat menggunakan kode affiliate sendiri.'],
            ]);
        }

        $base = round($booking->resolvedAmountDue(), 2);

        return $this->snapshotFromResolvedAffiliate($affiliate, $base, $isCompanyCustomer);
    }

    /**
     * Recompute base/commission from current booking pricing using an already-resolved affiliate.
     * Used when booking total is finalized at creation (support packages) or after price snapshots.
     *
     * @return array{
     *     affiliate_id: string,
     *     affiliate_code_snapshot: string,
     *     affiliate_rate_snapshot: float,
     *     affiliate_base_amount_snapshot: float,
     *     affiliate_commission_amount: float
     * }|array{}
     */
    public function snapshotFromResolvedAffiliate(Affiliate $affiliate, float $baseAmount, bool $isCompanyCustomer = false): array
    {
        if (! $affiliate->isActive()) {
            throw ValidationException::withMessages([
                'affiliate_code' => ['Kode affiliate tidak ditemukan atau tidak aktif.'],
            ]);
        }

        $base = round($baseAmount, 2);
        $rate = AffiliateSettings::getRate();
        $commission = round($base * $rate, 2);
        $platformFeeTotal = PlatformFee::split($base, $isCompanyCustomer)['platform_fee_total'];

        if ($commission > $platformFeeTotal || $commission <= 0 || $base < 1) {
            throw ValidationException::withMessages([
                'affiliate_code' => ['Komisi affiliate tidak dapat dihitung untuk booking ini.'],
            ]);
        }

        return [
            'affiliate_id' => (string) $affiliate->id,
            'affiliate_code_snapshot' => (string) $affiliate->code,
            'affiliate_rate_snapshot' => $rate,
            'affiliate_base_amount_snapshot' => $base,
            'affiliate_commission_amount' => $commission,
        ];
    }
}
