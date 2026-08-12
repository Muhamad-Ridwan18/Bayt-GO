<?php

namespace Tests\Unit\Services;

use App\Enums\BookingStatus;
use App\Enums\MuthowifServiceType;
use App\Enums\PaymentStatus;
use App\Enums\UserRole;
use App\Models\BookingPayment;
use App\Models\MuthowifBooking;
use App\Models\MuthowifProfile;
use App\Models\User;
use App\Services\CancelUnpaidBookingAfterPaymentDeadline;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

class CancelUnpaidBookingAfterPaymentDeadlineTest extends TestCase
{
    use RefreshDatabase;

    private function makeAwaitingPaymentBooking(?\Carbon\CarbonInterface $dueAt = null): MuthowifBooking
    {
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
            'languages' => ['Bahasa Indonesia'],
            'work_experiences' => ['5 tahun'],
        ]);
        $profile->forceFill(['verification_status' => 'approved', 'wallet_balance' => 0])->save();

        $customer = User::factory()->create(['role' => UserRole::Customer]);

        return MuthowifBooking::query()->create([
            'booking_code' => 'BG-DEADLINE-1',
            'muthowif_profile_id' => $profile->id,
            'customer_id' => $customer->id,
            'service_type' => MuthowifServiceType::PrivateJamaah,
            'pilgrim_count' => 1,
            'starts_on' => now()->addWeek()->toDateString(),
            'ends_on' => now()->addWeeks(2)->toDateString(),
            'status' => BookingStatus::Confirmed,
            'payment_status' => PaymentStatus::Pending,
            'daily_price_snapshot' => 500000,
            'total_amount' => 500000,
            'payment_due_at' => $dueAt ?? now()->subMinute(),
        ]);
    }

    public function test_cancels_overdue_unpaid_booking_and_pending_payments(): void
    {
        $booking = $this->makeAwaitingPaymentBooking(now()->subMinute());

        $keys = BookingPayment::newPrimaryKeyAndOrderId((string) $booking->getKey());
        BookingPayment::query()->create([
            'id' => $keys['id'],
            'muthowif_booking_id' => $booking->getKey(),
            'order_id' => $keys['order_id'],
            'gross_amount' => 500000,
            'platform_fee_amount' => 0,
            'muthowif_net_amount' => 500000,
            'status' => 'pending',
        ]);

        $ok = app(CancelUnpaidBookingAfterPaymentDeadline::class)->cancelIfOverdue($booking, notify: false);

        $this->assertTrue($ok);
        $booking->refresh();
        $this->assertSame(BookingStatus::Cancelled, $booking->status);
        $this->assertSame(PaymentStatus::Pending, $booking->payment_status);
        $this->assertSame(__('bookings.payment_deadline.auto_cancel_note'), $booking->muthowif_rejection_note);
        $this->assertSame('cancelled', BookingPayment::query()->where('muthowif_booking_id', $booking->getKey())->value('status'));
    }

    public function test_does_not_cancel_future_deadline(): void
    {
        $booking = $this->makeAwaitingPaymentBooking(now()->addHour());

        $ok = app(CancelUnpaidBookingAfterPaymentDeadline::class)->cancelIfOverdue($booking, notify: false);

        $this->assertFalse($ok);
        $booking->refresh();
        $this->assertSame(BookingStatus::Confirmed, $booking->status);
    }

    public function test_process_timeouts_command_cancels_overdue(): void
    {
        $this->makeAwaitingPaymentBooking(now()->subMinutes(5));

        $this->artisan('bookings:process-timeouts')
            ->assertSuccessful();

        $this->assertSame(
            BookingStatus::Cancelled,
            MuthowifBooking::query()->where('booking_code', 'BG-DEADLINE-1')->value('status')
        );
    }
}
