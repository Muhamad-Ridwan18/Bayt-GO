<?php

namespace App\Support;

use App\Models\SiteSetting;

class WhatsAppNotifySettings
{
    public const SETTING_ADMIN_NUMBERS = 'wa_notify_admin_numbers';

    /**
     * @return array<string, array{config: string, label: string, group: string}>
     */
    public static function toggles(): array
    {
        return [
            'otp' => [
                'config' => 'services.fonnte.otp_enabled',
                'label' => 'admin.whatsapp_notify.toggles.otp',
                'group' => 'auth',
            ],
            'booking' => [
                'config' => 'services.fonnte.booking_notify_enabled',
                'label' => 'admin.whatsapp_notify.toggles.booking',
                'group' => 'booking',
            ],
            'payment' => [
                'config' => 'services.fonnte.payment_notify_enabled',
                'label' => 'admin.whatsapp_notify.toggles.payment',
                'group' => 'booking',
            ],
            'customer_booking_approved' => [
                'config' => 'services.fonnte.customer_booking_approved_notify_enabled',
                'label' => 'admin.whatsapp_notify.toggles.customer_booking_approved',
                'group' => 'booking',
            ],
            'customer_booking_rejected_jadwal_full' => [
                'config' => 'services.fonnte.customer_booking_rejected_jadwal_full_notify_enabled',
                'label' => 'admin.whatsapp_notify.toggles.customer_booking_rejected_jadwal_full',
                'group' => 'booking',
            ],
            'refund_transfer_proof' => [
                'config' => 'services.fonnte.refund_transfer_proof_notify_enabled',
                'label' => 'admin.whatsapp_notify.toggles.refund_transfer_proof',
                'group' => 'finance',
            ],
            'withdrawal_transfer_proof' => [
                'config' => 'services.fonnte.withdrawal_transfer_proof_notify_enabled',
                'label' => 'admin.whatsapp_notify.toggles.withdrawal_transfer_proof',
                'group' => 'finance',
            ],
            'refund_admin' => [
                'config' => 'services.fonnte.refund_admin_notify_enabled',
                'label' => 'admin.whatsapp_notify.toggles.refund_admin',
                'group' => 'admin',
            ],
            'muthowif_registration_admin' => [
                'config' => 'services.fonnte.muthowif_registration_admin_notify_enabled',
                'label' => 'admin.whatsapp_notify.toggles.muthowif_registration_admin',
                'group' => 'admin',
            ],
            'emergency_admin_report' => [
                'config' => 'services.fonnte.emergency_admin_report_notify_enabled',
                'label' => 'admin.whatsapp_notify.toggles.emergency_admin_report',
                'group' => 'emergency',
            ],
            'emergency_customer_report' => [
                'config' => 'services.fonnte.emergency_customer_report_notify_enabled',
                'label' => 'admin.whatsapp_notify.toggles.emergency_customer_report',
                'group' => 'emergency',
            ],
            'emergency_candidate' => [
                'config' => 'services.fonnte.emergency_candidate_notify_enabled',
                'label' => 'admin.whatsapp_notify.toggles.emergency_candidate',
                'group' => 'emergency',
            ],
            'emergency_offer' => [
                'config' => 'services.fonnte.emergency_offer_notify_enabled',
                'label' => 'admin.whatsapp_notify.toggles.emergency_offer',
                'group' => 'emergency',
            ],
            'emergency_selection' => [
                'config' => 'services.fonnte.emergency_selection_notify_enabled',
                'label' => 'admin.whatsapp_notify.toggles.emergency_selection',
                'group' => 'emergency',
            ],
        ];
    }

    /**
     * @return array<string, string>
     */
    public static function groups(): array
    {
        return [
            'auth' => 'admin.whatsapp_notify.groups.auth',
            'booking' => 'admin.whatsapp_notify.groups.booking',
            'finance' => 'admin.whatsapp_notify.groups.finance',
            'admin' => 'admin.whatsapp_notify.groups.admin',
            'emergency' => 'admin.whatsapp_notify.groups.emergency',
        ];
    }

    public static function enabled(string $key): bool
    {
        if (! isset(self::toggles()[$key])) {
            return false;
        }

        $stored = SiteSetting::getValue(self::settingKey($key));
        if ($stored !== null) {
            return filter_var($stored, FILTER_VALIDATE_BOOLEAN);
        }

        return (bool) config(self::toggles()[$key]['config'], true);
    }

    /**
     * @return list<string>
     */
    public static function adminNumbers(): array
    {
        $stored = SiteSetting::getValue(self::SETTING_ADMIN_NUMBERS);
        if ($stored !== null) {
            return array_values(array_filter(array_map(
                static fn (string $n): string => trim($n),
                explode(',', $stored),
            ), static fn (string $n): bool => $n !== ''));
        }

        return config('emergency.admin_whatsapp_numbers', []);
    }

    public static function adminNumbersForForm(): string
    {
        $stored = SiteSetting::getValue(self::SETTING_ADMIN_NUMBERS);
        if ($stored !== null) {
            return $stored;
        }

        return implode(', ', config('emergency.admin_whatsapp_numbers', []));
    }

    /**
     * @return array<string, bool>
     */
    public static function toggleValuesForForm(): array
    {
        $values = [];
        foreach (array_keys(self::toggles()) as $key) {
            $values[$key] = self::enabled($key);
        }

        return $values;
    }

    /**
     * @param  array<string, mixed>  $input
     */
    public static function saveFromInput(array $input): void
    {
        foreach (array_keys(self::toggles()) as $key) {
            $on = filter_var($input['toggle_'.$key] ?? false, FILTER_VALIDATE_BOOLEAN);
            SiteSetting::putValue(self::settingKey($key), $on ? '1' : '0');
        }

        $numbers = trim((string) ($input['admin_numbers'] ?? ''));
        SiteSetting::putValue(self::SETTING_ADMIN_NUMBERS, $numbers === '' ? null : $numbers);
    }

    private static function settingKey(string $key): string
    {
        return 'wa_notify_'.$key;
    }
}
