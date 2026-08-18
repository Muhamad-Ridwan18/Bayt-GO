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

    public function test_defaults_are_forty_eight_hours_and_two_hours_support_in_minutes(): void
    {
        $this->assertSame(2880, BookingPaymentDeadlineSettings::regularMinutes());
        $this->assertSame(120, BookingPaymentDeadlineSettings::supportMinutes());
    }

    public function test_minutes_for_support_vs_regular(): void
    {
        SiteSetting::putValue(BookingPaymentDeadlineSettings::SETTING_REGULAR_MINUTES, '1440');
        SiteSetting::putValue(BookingPaymentDeadlineSettings::SETTING_SUPPORT_MINUTES, '90');

        $regular = new MuthowifBooking(['service_type' => MuthowifServiceType::PrivateJamaah]);
        $support = new MuthowifBooking(['service_type' => MuthowifServiceType::Support]);

        $this->assertSame(1440, BookingPaymentDeadlineSettings::minutesFor($regular));
        $this->assertSame(90, BookingPaymentDeadlineSettings::minutesFor($support));
    }

    public function test_legacy_hours_setting_is_converted_to_minutes(): void
    {
        SiteSetting::putValue(BookingPaymentDeadlineSettings::SETTING_REGULAR_HOURS, '24');

        $this->assertSame(1440, BookingPaymentDeadlineSettings::regularMinutes());
    }

    public function test_due_at_from_now_uses_booking_window(): void
    {
        $from = now()->startOfMinute();
        $support = new MuthowifBooking(['service_type' => MuthowifServiceType::Support]);

        $due = BookingPaymentDeadlineSettings::dueAtFromNow($support, $from);

        $this->assertTrue($due->equalTo($from->copy()->addMinutes(120)));
    }

    public function test_format_duration_label(): void
    {
        $this->assertSame('1 menit', BookingPaymentDeadlineSettings::formatDuration(1, 'id'));
        $this->assertSame('1 jam', BookingPaymentDeadlineSettings::formatDuration(60, 'id'));
        $this->assertSame('24 jam', BookingPaymentDeadlineSettings::formatDuration(1440, 'id'));
        $this->assertSame('48 jam', BookingPaymentDeadlineSettings::formatDuration(2880, 'id'));
        $this->assertSame('1 hour', BookingPaymentDeadlineSettings::formatDuration(60, 'en'));
        $this->assertSame('1 jam 30 menit', BookingPaymentDeadlineSettings::formatDuration(90, 'id'));
    }
}
