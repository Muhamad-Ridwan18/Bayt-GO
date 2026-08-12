<?php

namespace App\Support;

use App\Models\MuthowifBooking;
use App\Models\SiteSetting;
use Carbon\CarbonInterface;

final class BookingPaymentDeadlineSettings
{
    public const SETTING_REGULAR_HOURS = 'booking_pay_deadline_hours';

    public const SETTING_SUPPORT_MINUTES = 'support_pay_deadline_minutes';

    private const DEFAULT_REGULAR_HOURS = 48;

    private const DEFAULT_SUPPORT_MINUTES = 120;

    public static function regularHours(): int
    {
        $stored = SiteSetting::getValue(self::SETTING_REGULAR_HOURS);
        if ($stored === null || ! is_numeric($stored)) {
            return self::DEFAULT_REGULAR_HOURS;
        }

        return max(1, min(168, (int) $stored));
    }

    public static function regularMinutes(): int
    {
        return self::regularHours() * 60;
    }

    public static function supportMinutes(): int
    {
        $stored = SiteSetting::getValue(self::SETTING_SUPPORT_MINUTES);
        if ($stored === null || ! is_numeric($stored)) {
            return self::DEFAULT_SUPPORT_MINUTES;
        }

        return max(15, min(1440, (int) $stored));
    }

    public static function minutesFor(MuthowifBooking $booking): int
    {
        return $booking->isSupport()
            ? self::supportMinutes()
            : self::regularMinutes();
    }

    public static function dueAtFromNow(?MuthowifBooking $booking = null, ?CarbonInterface $from = null): CarbonInterface
    {
        $from ??= now();
        $minutes = $booking !== null
            ? self::minutesFor($booking)
            : self::regularMinutes();

        return $from->copy()->addMinutes($minutes);
    }

    /**
     * @return array{regular_hours: int, support_minutes: int}
     */
    public static function valuesForForm(): array
    {
        return [
            'regular_hours' => self::regularHours(),
            'support_minutes' => self::supportMinutes(),
        ];
    }

    /**
     * @param  array<string, mixed>  $input
     */
    public static function saveFromInput(array $input): void
    {
        $hours = max(1, min(168, (int) ($input['regular_hours'] ?? self::DEFAULT_REGULAR_HOURS)));
        $minutes = max(15, min(1440, (int) ($input['support_minutes'] ?? self::DEFAULT_SUPPORT_MINUTES)));

        SiteSetting::putValue(self::SETTING_REGULAR_HOURS, (string) $hours);
        SiteSetting::putValue(self::SETTING_SUPPORT_MINUTES, (string) $minutes);
    }
}
