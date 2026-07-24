<?php

namespace Tests\Unit\Services;

use App\Enums\AffiliateCommissionStatus;
use App\Enums\AffiliateStatus;
use App\Enums\UserRole;
use App\Models\Affiliate;
use App\Models\AffiliateCommission;
use App\Models\MuthowifBooking;
use App\Models\MuthowifProfile;
use App\Models\SiteSetting;
use App\Models\User;
use App\Services\AffiliateAttributionService;
use App\Services\AffiliateRegistrationService;
use App\Support\AffiliateSettings;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

class AffiliateAttributionServiceTest extends TestCase
{
    use RefreshDatabase;

    private function seedFeeAndTiers(): void
    {
        SiteSetting::putValue('platform_fee_rate', '0.075');
        AffiliateSettings::putTiers([
            ['min' => 0, 'rate' => 0.01],
            ['min' => 250_000_000, 'rate' => 0.015],
            ['min' => 500_000_000, 'rate' => 0.02],
        ]);
    }

    public function test_snapshot_stores_rate_and_amount(): void
    {
        $this->seedFeeAndTiers();

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

    public function test_snapshot_base_includes_hotel_transport_and_addons(): void
    {
        $this->seedFeeAndTiers();

        $affiliateUser = User::factory()->create(['role' => UserRole::Customer]);
        $customer = User::factory()->create(['role' => UserRole::Customer]);

        Affiliate::query()->create([
            'user_id' => $affiliateUser->id,
            'code' => 'FULL01',
            'status' => AffiliateStatus::Active,
            'available_balance' => 0,
            'activated_at' => now(),
        ]);

        // Even if total_amount is wrong/incomplete, affiliate base must use full components.
        $booking = new MuthowifBooking([
            'customer_id' => $customer->id,
            'service_type' => 'private',
            'starts_on' => '2026-08-01',
            'ends_on' => '2026-08-03',
            'daily_price_snapshot' => 1_000_000,
            'same_hotel_price_snapshot' => 200_000,
            'transport_price_snapshot' => 150_000,
            'with_same_hotel' => true,
            'with_transport' => true,
            'add_ons_snapshot' => [
                ['id' => '1', 'name' => 'Kursi roda', 'price' => 50_000],
            ],
            'total_amount' => 1_000_000,
        ]);

        $snapshot = app(AffiliateAttributionService::class)->snapshotForBooking(
            $booking,
            'FULL01',
            (string) $customer->id,
        );

        // 3 nights * 1jt + addon 50rb + hotel 3*200rb + transport 150rb = 3_800_000
        $this->assertSame(3_800_000.0, $snapshot['affiliate_base_amount_snapshot']);
        $this->assertSame(38_000.0, $snapshot['affiliate_commission_amount']);
    }

    public function test_self_referral_is_allowed(): void
    {
        $this->seedFeeAndTiers();

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

    public function test_tier_change_does_not_affect_existing_snapshot_values(): void
    {
        $this->seedFeeAndTiers();

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

        AffiliateSettings::putTiers([
            ['min' => 0, 'rate' => 0.02],
            ['min' => 250_000_000, 'rate' => 0.025],
            ['min' => 500_000_000, 'rate' => 0.03],
        ]);

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

    public function test_level_2_rate_when_volume_reaches_250jt(): void
    {
        $this->seedFeeAndTiers();

        $affiliateUser = User::factory()->create(['role' => UserRole::Customer]);
        $customer = User::factory()->create(['role' => UserRole::Customer]);
        $affiliate = Affiliate::query()->create([
            'user_id' => $affiliateUser->id,
            'code' => 'LVL200',
            'status' => AffiliateStatus::Active,
            'available_balance' => 0,
            'activated_at' => now(),
        ]);

        $this->seedVolumeCommission($affiliate, $customer, 250_000_000);

        $snapshot = app(AffiliateAttributionService::class)->snapshotForBooking(
            new MuthowifBooking(['package_price_snapshot' => 1_000_000, 'service_type' => 'support']),
            'LVL200',
            (string) $customer->id,
        );

        $this->assertSame(0.015, $snapshot['affiliate_rate_snapshot']);
        $this->assertSame(15000.0, $snapshot['affiliate_commission_amount']);
    }

    public function test_level_3_rate_when_volume_reaches_500jt(): void
    {
        $this->seedFeeAndTiers();

        $affiliateUser = User::factory()->create(['role' => UserRole::Customer]);
        $customer = User::factory()->create(['role' => UserRole::Customer]);
        $affiliate = Affiliate::query()->create([
            'user_id' => $affiliateUser->id,
            'code' => 'LVL300',
            'status' => AffiliateStatus::Active,
            'available_balance' => 0,
            'activated_at' => now(),
        ]);

        $this->seedVolumeCommission($affiliate, $customer, 500_000_000);

        $snapshot = app(AffiliateAttributionService::class)->snapshotForBooking(
            new MuthowifBooking(['package_price_snapshot' => 1_000_000, 'service_type' => 'support']),
            'LVL300',
            (string) $customer->id,
        );

        $this->assertSame(0.02, $snapshot['affiliate_rate_snapshot']);
        $this->assertSame(20000.0, $snapshot['affiliate_commission_amount']);
    }

    public function test_void_volume_does_not_count_toward_level(): void
    {
        $this->seedFeeAndTiers();

        $affiliateUser = User::factory()->create(['role' => UserRole::Customer]);
        $customer = User::factory()->create(['role' => UserRole::Customer]);
        $affiliate = Affiliate::query()->create([
            'user_id' => $affiliateUser->id,
            'code' => 'VOID01',
            'status' => AffiliateStatus::Active,
            'available_balance' => 0,
            'activated_at' => now(),
        ]);

        $this->seedVolumeCommission($affiliate, $customer, 500_000_000, AffiliateCommissionStatus::Void);

        $snapshot = app(AffiliateAttributionService::class)->snapshotForBooking(
            new MuthowifBooking(['package_price_snapshot' => 1_000_000, 'service_type' => 'support']),
            'VOID01',
            (string) $customer->id,
        );

        $this->assertSame(0.01, $snapshot['affiliate_rate_snapshot']);
        $this->assertSame(10000.0, $snapshot['affiliate_commission_amount']);
    }

    public function test_normalize_code(): void
    {
        $service = app(AffiliateRegistrationService::class);
        $this->assertSame('ABC123', $service->normalizeCode(' abc-123 '));
        $this->assertNull($service->normalizeCode('ab'));
    }

    private function seedVolumeCommission(
        Affiliate $affiliate,
        User $customer,
        float $baseAmount,
        AffiliateCommissionStatus $status = AffiliateCommissionStatus::Available,
    ): void {
        $muthowifUser = User::factory()->create(['role' => UserRole::Muthowif]);
        $profile = MuthowifProfile::query()->create([
            'id' => (string) Str::uuid(),
            'user_id' => $muthowifUser->id,
            'phone' => '081234567890',
            'address' => 'Makkah',
            'nik' => '1234567890123456',
            'birth_date' => '1990-01-01',
            'photo_path' => 'photos/test.jpg',
            'ktp_image_path' => 'ktp/test.jpg',
            'verification_status' => 'approved',
            'wallet_balance' => 0,
        ]);

        $booking = MuthowifBooking::query()->create([
            'booking_code' => 'BG-VOL-'.Str::upper(Str::random(6)),
            'muthowif_profile_id' => $profile->id,
            'customer_id' => $customer->id,
            'service_type' => 'support',
            'pilgrim_count' => 1,
            'starts_on' => now()->toDateString(),
            'ends_on' => now()->toDateString(),
            'status' => 'confirmed',
            'payment_status' => 'paid',
            'total_amount' => $baseAmount,
            'package_price_snapshot' => $baseAmount,
            'affiliate_id' => $affiliate->id,
            'affiliate_code_snapshot' => $affiliate->code,
            'affiliate_rate_snapshot' => 0.01,
            'affiliate_base_amount_snapshot' => $baseAmount,
            'affiliate_commission_amount' => round($baseAmount * 0.01, 2),
        ]);

        AffiliateCommission::query()->create([
            'affiliate_id' => $affiliate->id,
            'muthowif_booking_id' => $booking->id,
            'customer_id' => $customer->id,
            'affiliate_code_snapshot' => $affiliate->code,
            'commission_rate_snapshot' => 0.01,
            'transaction_base_amount_snapshot' => $baseAmount,
            'platform_fee_amount_snapshot' => round($baseAmount * 0.15, 2),
            'commission_amount' => round($baseAmount * 0.01, 2),
            'status' => $status,
            'pending_at' => now(),
            'available_at' => $status === AffiliateCommissionStatus::Available ? now() : null,
            'voided_at' => $status === AffiliateCommissionStatus::Void ? now() : null,
        ]);
    }
}
