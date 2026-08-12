<?php

namespace Tests\Unit\Support;

use App\Enums\MuthowifServiceType;
use App\Models\MuthowifBooking;
use App\Models\SiteSetting;
use App\Support\BookingPaymentDeadlineSettings;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class BookingPaymentDeadlineSettingsTest extends TestCase
{
    use RefreshDatabase;

    public function test_defaults_are_forty_eight_hours_and_two_hours_support(): void
    {
        $this->assertSame(48, BookingPaymentDeadlineSettings::regularHours());
        $this->assertSame(2880, BookingPaymentDeadlineSettings::regularMinutes());
        $this->assertSame(120, BookingPaymentDeadlineSettings::supportMinutes());
    }

    public function test_minutes_for_support_vs_regular(): void
    {
        SiteSetting::putValue(BookingPaymentDeadlineSettings::SETTING_REGULAR_HOURS, '24');
        SiteSetting::putValue(BookingPaymentDeadlineSettings::SETTING_SUPPORT_MINUTES, '90');

        $regular = new MuthowifBooking(['service_type' => MuthowifServiceType::PrivateJamaah]);
        $support = new MuthowifBooking(['service_type' => MuthowifServiceType::Support]);

        $this->assertSame(24 * 60, BookingPaymentDeadlineSettings::minutesFor($regular));
        $this->assertSame(90, BookingPaymentDeadlineSettings::minutesFor($support));
    }

    public function test_due_at_from_now_uses_booking_window(): void
    {
        $from = now()->startOfMinute();
        $support = new MuthowifBooking(['service_type' => MuthowifServiceType::Support]);

        $due = BookingPaymentDeadlineSettings::dueAtFromNow($support, $from);

        $this->assertTrue($due->equalTo($from->copy()->addMinutes(120)));
    }
}
