<?php

namespace Tests\Feature\Affiliate;

use App\Enums\AffiliateBankVerificationStatus;
use App\Enums\AffiliateStatus;
use App\Enums\AffiliateWithdrawalStatus;
use App\Enums\UserRole;
use App\Models\Affiliate;
use App\Models\AffiliateBankAccount;
use App\Models\SiteSetting;
use App\Models\User;
use App\Services\AffiliateWalletService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class AffiliateWithdrawTest extends TestCase
{
    use RefreshDatabase;

    private function makeAffiliateWithBalance(float $balance = 200000): array
    {
        SiteSetting::putValue('affiliate_min_withdraw', '100000');

        $user = User::factory()->create(['role' => UserRole::Customer]);
        $affiliate = Affiliate::query()->create([
            'user_id' => $user->id,
            'code' => 'WDR001',
            'status' => AffiliateStatus::Active,
            'available_balance' => $balance,
            'activated_at' => now(),
        ]);

        $bank = AffiliateBankAccount::query()->create([
            'affiliate_id' => $affiliate->id,
            'bank_code' => 'BCA',
            'bank_name' => 'Bank Central Asia (BCA)',
            'account_holder' => 'Test User',
            'account_number' => '1234567890',
            'is_primary' => true,
            'verification_status' => AffiliateBankVerificationStatus::Verified,
            'verified_at' => now(),
        ]);

        return [$user, $affiliate, $bank];
    }

    public function test_withdraw_reserves_balance(): void
    {
        [, $affiliate, $bank] = $this->makeAffiliateWithBalance();
        $withdrawal = app(AffiliateWalletService::class)->requestWithdrawal($affiliate, $bank, 100000);

        $this->assertSame(AffiliateWithdrawalStatus::Requested, $withdrawal->status);
        $this->assertEquals(100000, (float) $affiliate->fresh()->available_balance);
    }

    public function test_unverified_bank_rejected(): void
    {
        [, $affiliate, $bank] = $this->makeAffiliateWithBalance();
        $bank->update(['verification_status' => AffiliateBankVerificationStatus::Pending]);

        $this->expectException(ValidationException::class);
        app(AffiliateWalletService::class)->requestWithdrawal($affiliate, $bank->fresh(), 100000);
    }

    public function test_reject_releases_balance_once(): void
    {
        $admin = User::factory()->create(['role' => UserRole::Admin]);
        [, $affiliate, $bank] = $this->makeAffiliateWithBalance();
        $wallet = app(AffiliateWalletService::class);
        $withdrawal = $wallet->requestWithdrawal($affiliate, $bank, 100000);

        $wallet->reject($withdrawal, $admin, 'tidak valid');

        $this->assertEquals(200000, (float) $affiliate->fresh()->available_balance);
        $this->assertSame(AffiliateWithdrawalStatus::Rejected, $withdrawal->fresh()->status);

        $this->expectException(ValidationException::class);
        $wallet->reject($withdrawal->fresh(), $admin, 'ulang');
    }

    public function test_below_minimum_rejected(): void
    {
        [, $affiliate, $bank] = $this->makeAffiliateWithBalance();

        $this->expectException(ValidationException::class);
        app(AffiliateWalletService::class)->requestWithdrawal($affiliate, $bank, 50000);
    }
}
