<?php

namespace Tests\Unit\Services;

use App\Enums\AffiliateStatus;
use App\Enums\UserRole;
use App\Models\Affiliate;
use App\Models\MuthowifBooking;
use App\Models\SiteSetting;
use App\Models\User;
use App\Services\AffiliateAttributionService;
use App\Services\AffiliateRegistrationService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AffiliateAttributionServiceTest extends TestCase
{
    use RefreshDatabase;

    private function seedFeeAndRate(): void
    {
        SiteSetting::putValue('platform_fee_rate', '0.075');
        SiteSetting::putValue('affiliate_commission_rate', '0.01');
    }

    public function test_snapshot_stores_rate_and_amount(): void
    {
        $this->seedFeeAndRate();

        $affiliateUser = User::factory()->create(['role' => UserRole::Customer]);
        $customer = User::factory()->create(['role' => UserRole::Customer]);

        $affiliate = Affiliate::query()->create([
            'user_id' => $affiliateUser->id,
            'code' => 'RIDWAN',
            'status' => AffiliateStatus::Active,
            'available_balance' => 0,
            'activated_at' => now(),
        ]);

        $booking = new MuthowifBooking([
            'customer_id' => $customer->id,
            'package_price_snapshot' => 1000000,
            'service_type' => 'support',
        ]);

        $snapshot = app(AffiliateAttributionService::class)->snapshotForBooking(
            $booking,
            'ridwan',
            (string) $customer->id,
        );

        $this->assertSame((string) $affiliate->id, $snapshot['affiliate_id']);
        $this->assertSame('RIDWAN', $snapshot['affiliate_code_snapshot']);
        $this->assertSame(0.01, $snapshot['affiliate_rate_snapshot']);
        $this->assertSame(1000000.0, $snapshot['affiliate_base_amount_snapshot']);
        $this->assertSame(10000.0, $snapshot['affiliate_commission_amount']);
    }

    public function test_self_referral_is_allowed(): void
    {
        $this->seedFeeAndRate();

        $user = User::factory()->create(['role' => UserRole::Customer]);
        $affiliate = Affiliate::query()->create([
            'user_id' => $user->id,
            'code' => 'SELF01',
            'status' => AffiliateStatus::Active,
            'available_balance' => 0,
            'activated_at' => now(),
        ]);

        $snapshot = app(AffiliateAttributionService::class)->snapshotForBooking(
            new MuthowifBooking(['package_price_snapshot' => 500000, 'service_type' => 'support']),
            'SELF01',
            (string) $user->id,
        );

        $this->assertSame((string) $affiliate->id, $snapshot['affiliate_id']);
        $this->assertSame(5000.0, $snapshot['affiliate_commission_amount']);
    }

    public function test_rate_change_does_not_affect_existing_snapshot_values(): void
    {
        $this->seedFeeAndRate();

        $affiliateUser = User::factory()->create();
        $customer = User::factory()->create();
        Affiliate::query()->create([
            'user_id' => $affiliateUser->id,
            'code' => 'RATE01',
            'status' => AffiliateStatus::Active,
            'available_balance' => 0,
            'activated_at' => now(),
        ]);

        $first = app(AffiliateAttributionService::class)->snapshotForBooking(
            new MuthowifBooking(['package_price_snapshot' => 1000000, 'service_type' => 'support']),
            'RATE01',
            (string) $customer->id,
        );

        SiteSetting::putValue('affiliate_commission_rate', '0.02');

        $second = app(AffiliateAttributionService::class)->snapshotForBooking(
            new MuthowifBooking(['package_price_snapshot' => 1000000, 'service_type' => 'support']),
            'RATE01',
            (string) $customer->id,
        );

        $this->assertSame(0.01, $first['affiliate_rate_snapshot']);
        $this->assertSame(10000.0, $first['affiliate_commission_amount']);
        $this->assertSame(0.02, $second['affiliate_rate_snapshot']);
        $this->assertSame(20000.0, $second['affiliate_commission_amount']);
    }

    public function test_normalize_code(): void
    {
        $service = app(AffiliateRegistrationService::class);
        $this->assertSame('ABC123', $service->normalizeCode(' abc-123 '));
        $this->assertNull($service->normalizeCode('ab'));
    }
}
