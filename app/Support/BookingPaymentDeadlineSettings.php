<?php

namespace App\Support;

use App\Models\MuthowifBooking;
use App\Models\SiteSetting;
use Carbon\CarbonInterface;

final class BookingPaymentDeadlineSettings
{
    public const SETTING_REGULAR_MINUTES = 'booking_pay_deadline_minutes';

    /** @deprecated Diganti SETTING_REGULAR_MINUTES; masih dibaca untuk migrasi nilai lama. */
    public const SETTING_REGULAR_HOURS = 'booking_pay_deadline_hours';

    public const SETTING_SUPPORT_MINUTES = 'support_pay_deadline_minutes';

    private const DEFAULT_REGULAR_MINUTES = 2880; // 48 jam

    private const DEFAULT_SUPPORT_MINUTES = 120;

    public static function regularMinutes(): int
    {
        $stored = SiteSetting::getValue(self::SETTING_REGULAR_MINUTES);
        if ($stored !== null && is_numeric($stored)) {
            return max(1, min(10080, (int) $stored));
        }

        $legacyHours = SiteSetting::getValue(self::SETTING_REGULAR_HOURS);
        if ($legacyHours !== null && is_numeric($legacyHours)) {
            return max(1, min(10080, (int) $legacyHours * 60));
        }

        return self::DEFAULT_REGULAR_MINUTES;
    }

    public static function supportMinutes(): int
    {
        $stored = SiteSetting::getValue(self::SETTING_SUPPORT_MINUTES);
        if ($stored === null || ! is_numeric($stored)) {
            return self::DEFAULT_SUPPORT_MINUTES;
        }

        return max(1, min(10080, (int) $stored));
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
     * @return array{regular_minutes: int, support_minutes: int}
     */
    public static function valuesForForm(): array
    {
        return [
            'regular_minutes' => self::regularMinutes(),
            'support_minutes' => self::supportMinutes(),
        ];
    }

    /**
     * @param  array<string, mixed>  $input
     */
    public static function saveFromInput(array $input): void
    {
        $regular = max(1, min(10080, (int) ($input['regular_minutes'] ?? self::DEFAULT_REGULAR_MINUTES)));
        $support = max(1, min(10080, (int) ($input['support_minutes'] ?? self::DEFAULT_SUPPORT_MINUTES)));

        SiteSetting::putValue(self::SETTING_REGULAR_MINUTES, (string) $regular);
        SiteSetting::putValue(self::SETTING_SUPPORT_MINUTES, (string) $support);
        SiteSetting::putValue(self::SETTING_REGULAR_HOURS, null);
    }
}
