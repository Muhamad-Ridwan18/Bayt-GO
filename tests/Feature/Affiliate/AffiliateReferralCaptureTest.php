<?php

namespace Tests\Feature\Affiliate;

use App\Enums\AffiliateStatus;
use App\Enums\AffiliateWithdrawalStatus;
use App\Enums\BookingStatus;
use App\Enums\UserRole;
use App\Models\Affiliate;
use App\Models\AffiliateBankAccount;
use App\Models\AffiliateClick;
use App\Models\AffiliateCommission;
use App\Models\AffiliateWithdrawal;
use App\Models\MuthowifBooking;
use App\Models\MuthowifProfile;
use App\Models\User;
use App\Notifications\AffiliateCommissionAvailableNotification;
use App\Notifications\AffiliateReferralBookedNotification;
use App\Notifications\AffiliateWithdrawalApprovedNotification;
use App\Notifications\AffiliateWithdrawalPaidNotification;
use App\Services\AffiliateNotifier;
use App\Services\AffiliateWalletService;
use App\Support\AffiliateReferralCapture;
use App\Support\AffiliateSettings;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Str;
use Tests\TestCase;

class AffiliateReferralCaptureTest extends TestCase
{
    use RefreshDatabase;

    private function makeAffiliate(string $code = 'RIDWAN'): Affiliate
    {
        $user = User::factory()->create(['role' => UserRole::Customer]);

        return Affiliate::query()->create([
            'user_id' => $user->id,
            'code' => $code,
            'status' => AffiliateStatus::Active,
            'available_balance' => 0,
            'activated_at' => now(),
        ]);
    }

    public function test_ref_query_stores_session_cookie_and_click(): void
    {
        $affiliate = $this->makeAffiliate();

        $this->get('/?ref=RIDWAN')
            ->assertOk();

        $this->assertSame('RIDWAN', session(AffiliateReferralCapture::SESSION_KEY));
        $this->assertSame(1, AffiliateClick::query()->where('affiliate_id', $affiliate->id)->count());

        $this->get('/?ref=RIDWAN')->assertOk();
        $this->assertSame(1, AffiliateClick::query()->where('affiliate_id', $affiliate->id)->count());
    }

    public function test_short_landing_route_redirects_and_captures(): void
    {
        $affiliate = $this->makeAffiliate('KODE01');

        $this->get('/r/KODE01')
            ->assertRedirect(route('welcome'));

        $this->assertSame('KODE01', session(AffiliateReferralCapture::SESSION_KEY));
        $this->assertSame(1, AffiliateClick::query()->where('affiliate_id', $affiliate->id)->count());
    }

    public function test_invalid_ref_is_ignored(): void
    {
        $this->get('/?ref=NOPE99')->assertOk();

        $this->assertNull(session(AffiliateReferralCapture::SESSION_KEY));
        $this->assertSame(0, AffiliateClick::query()->count());
    }

    public function test_dashboard_includes_total_clicks(): void
    {
        $affiliate = $this->makeAffiliate('DASH01');
        AffiliateClick::query()->create([
            'affiliate_id' => $affiliate->id,
            'code_snapshot' => 'DASH01',
            'visitor_key' => 'abc',
            'created_at' => now(),
        ]);

        $this->actingAs($affiliate->user)
            ->get(route('affiliate.index'))
            ->assertOk()
            ->assertSee('Total Klik');

        $this->actingAs($affiliate->user)
            ->getJson('/api/affiliate/dashboard')
            ->assertOk()
            ->assertJsonPath('stats.total_clicks', 1);
    }

    public function test_notifications_for_commission_and_withdraw(): void
    {
        Notification::fake();
        AffiliateSettings::putMinWithdraw(100_000);

        $affiliate = $this->makeAffiliate('MAIL01');
        $customer = User::factory()->create(['role' => UserRole::Customer]);
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
            'booking_code' => 'BG-MAIL-1',
            'muthowif_profile_id' => $profile->id,
            'customer_id' => $customer->id,
            'service_type' => 'support',
            'pilgrim_count' => 1,
            'starts_on' => now()->toDateString(),
            'ends_on' => now()->toDateString(),
            'status' => BookingStatus::Pending,
            'affiliate_id' => $affiliate->id,
            'affiliate_code_snapshot' => 'MAIL01',
            'affiliate_rate_snapshot' => 0.01,
            'affiliate_base_amount_snapshot' => 1_000_000,
            'affiliate_commission_amount' => 10_000,
        ]);

        app(AffiliateNotifier::class)->referralBooked($booking);
        Notification::assertSentTo($affiliate->user, AffiliateReferralBookedNotification::class);

        $commission = AffiliateCommission::query()->create([
            'affiliate_id' => $affiliate->id,
            'muthowif_booking_id' => $booking->id,
            'customer_id' => $customer->id,
            'affiliate_code_snapshot' => 'MAIL01',
            'commission_rate_snapshot' => 0.01,
            'transaction_base_amount_snapshot' => 1_000_000,
            'platform_fee_amount_snapshot' => 75_000,
            'commission_amount' => 10_000,
            'status' => 'pending',
            'pending_at' => now(),
        ]);

        app(AffiliateNotifier::class)->commissionAvailable($commission);
        Notification::assertSentTo($affiliate->user, AffiliateCommissionAvailableNotification::class);

        $bank = AffiliateBankAccount::query()->create([
            'affiliate_id' => $affiliate->id,
            'bank_code' => 'bca',
            'bank_name' => 'BCA',
            'account_holder' => 'Test',
            'account_number' => '1234567890',
            'is_primary' => true,
            'verification_status' => 'verified',
            'verified_at' => now(),
        ]);

        $affiliate->update(['available_balance' => 200_000]);
        $wallet = app(AffiliateWalletService::class);
        $withdrawal = $wallet->requestWithdrawal($affiliate->fresh(), $bank, 100_000);
        $admin = User::factory()->create(['role' => UserRole::Admin]);

        $wallet->approve($withdrawal, $admin);
        Notification::assertSentTo($affiliate->user, AffiliateWithdrawalApprovedNotification::class);

        $wallet->markPaid($withdrawal->fresh(), $admin, 'proofs/x.pdf');
        Notification::assertSentTo($affiliate->user, AffiliateWithdrawalPaidNotification::class);

        $this->assertSame(AffiliateWithdrawalStatus::Paid, $withdrawal->fresh()->status);
        $this->assertInstanceOf(AffiliateWithdrawal::class, $withdrawal);
    }
}
