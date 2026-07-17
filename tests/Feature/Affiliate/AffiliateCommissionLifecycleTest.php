<?php

namespace Tests\Feature\Affiliate;

use App\Enums\AffiliateCommissionStatus;
use App\Enums\AffiliateStatus;
use App\Enums\BookingStatus;
use App\Enums\PaymentStatus;
use App\Enums\UserRole;
use App\Models\Affiliate;
use App\Models\AffiliateCommission;
use App\Models\BookingChatMessage;
use App\Models\BookingPayment;
use App\Models\MuthowifBooking;
use App\Models\MuthowifProfile;
use App\Models\SiteSetting;
use App\Models\User;
use App\Services\AffiliateCommissionService;
use App\Services\BookingCompletionService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

class AffiliateCommissionLifecycleTest extends TestCase
{
    use RefreshDatabase;

    private function seedSettings(): void
    {
        SiteSetting::putValue('platform_fee_rate', '0.075');
        SiteSetting::putValue('affiliate_commission_rate', '0.01');
    }

    /** @return array{0: Affiliate, 1: MuthowifBooking, 2: BookingPayment} */
    private function makeAttributedPaidBooking(): array
    {
        $this->seedSettings();

        $affiliateUser = User::factory()->create(['role' => UserRole::Customer]);
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

        $affiliate = Affiliate::query()->create([
            'user_id' => $affiliateUser->id,
            'code' => 'AFFOK1',
            'status' => AffiliateStatus::Active,
            'available_balance' => 0,
            'activated_at' => now(),
        ]);

        $booking = MuthowifBooking::query()->create([
            'booking_code' => 'BG-TEST-1',
            'muthowif_profile_id' => $profile->id,
            'customer_id' => $customer->id,
            'service_type' => 'support',
            'pilgrim_count' => 1,
            'starts_on' => now()->toDateString(),
            'ends_on' => now()->toDateString(),
            'status' => BookingStatus::Confirmed,
            'payment_status' => PaymentStatus::Paid,
            'total_amount' => 1000000,
            'package_price_snapshot' => 1000000,
            'paid_at' => now(),
            'affiliate_id' => $affiliate->id,
            'affiliate_code_snapshot' => 'AFFOK1',
            'affiliate_rate_snapshot' => 0.01,
            'affiliate_base_amount_snapshot' => 1000000,
            'affiliate_commission_amount' => 10000,
        ]);

        $payment = BookingPayment::query()->create([
            'id' => (string) Str::uuid(),
            'muthowif_booking_id' => $booking->id,
            'booking_code' => $booking->booking_code,
            'order_id' => 'ORDER-'.Str::upper(Str::random(8)),
            'gross_amount' => 1075000,
            'platform_fee_amount' => 150000,
            'muthowif_net_amount' => 925000,
            'status' => 'settlement',
            'settled_at' => now(),
        ]);

        return [$affiliate, $booking, $payment];
    }

    public function test_pending_commission_created_idempotently_on_settlement(): void
    {
        [$affiliate, $booking, $payment] = $this->makeAttributedPaidBooking();
        $service = app(AffiliateCommissionService::class);

        $first = $service->createPendingFromSettledPayment($payment);
        $second = $service->createPendingFromSettledPayment($payment);

        $this->assertNotNull($first);
        $this->assertSame($first->id, $second->id);
        $this->assertSame(1, AffiliateCommission::query()->where('muthowif_booking_id', $booking->id)->count());
        $this->assertSame(AffiliateCommissionStatus::Pending, $first->status);
        $this->assertSame((string) $affiliate->id, (string) $first->affiliate_id);
        $this->assertEquals(10000, (float) $first->commission_amount);
    }

    public function test_completion_marks_commission_available_and_credits_wallet(): void
    {
        [$affiliate, $booking, $payment] = $this->makeAttributedPaidBooking();
        app(AffiliateCommissionService::class)->createPendingFromSettledPayment($payment);
        $chat = BookingChatMessage::query()->create([
            'muthowif_booking_id' => $booking->id,
            'user_id' => $booking->customer_id,
            'body' => 'Pesan yang harus di-soft delete setelah selesai.',
        ]);

        $result = app(BookingCompletionService::class)->complete($booking, 5, null);

        $this->assertTrue($result['completed']);
        $commission = AffiliateCommission::query()->where('muthowif_booking_id', $booking->id)->first();
        $this->assertSame(AffiliateCommissionStatus::Available, $commission->status);
        $this->assertEquals(10000, (float) $affiliate->fresh()->available_balance);
        $this->assertDatabaseHas('affiliate_wallet_transactions', [
            'affiliate_id' => $affiliate->id,
            'idempotency_key' => 'commission-credit:'.$commission->id,
        ]);
        $this->assertSoftDeleted('booking_chat_messages', [
            'id' => $chat->id,
        ]);
        $this->assertSame(0, BookingChatMessage::query()->where('muthowif_booking_id', $booking->id)->count());
        $this->assertNotNull($booking->fresh()->completed_at);
    }

    public function test_void_pending_commission_on_refund_path(): void
    {
        [, $booking, $payment] = $this->makeAttributedPaidBooking();
        $service = app(AffiliateCommissionService::class);
        $service->createPendingFromSettledPayment($payment);
        $service->voidForBooking($booking, 'refund_requested');

        $commission = AffiliateCommission::query()->where('muthowif_booking_id', $booking->id)->first();
        $this->assertSame(AffiliateCommissionStatus::Void, $commission->status);
    }
}
