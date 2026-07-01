<?php

namespace App\Support;

use App\Models\SiteSetting;

class WhatsAppNotifySettings
{
    private const DEFAULT_API_URL = 'https://api.fonnte.com/send';

    private const DEFAULT_COUNTRY_CODE = '62';

    public const SETTING_ADMIN_NUMBERS = 'wa_notify_admin_numbers';

    public const SETTING_TOKEN = 'wa_gateway_token';

    public const SETTING_SESSION_ID = 'wa_gateway_session_id';

    public const SETTING_API_URL = 'wa_gateway_api_url';

    public const SETTING_COUNTRY_CODE = 'wa_gateway_country_code';

    public const SETTING_MEDIA_PUBLIC_URL = 'wa_gateway_media_public_url';

    /**
     * @return array<string, array{label: string, group: string, default: bool}>
     */
    public static function toggles(): array
    {
        return [
            'otp' => [
                'label' => 'admin.whatsapp_notify.toggles.otp',
                'group' => 'auth',
                'default' => true,
            ],
            'booking' => [
                'label' => 'admin.whatsapp_notify.toggles.booking',
                'group' => 'booking',
                'default' => true,
            ],
            'payment' => [
                'label' => 'admin.whatsapp_notify.toggles.payment',
                'group' => 'booking',
                'default' => true,
            ],
            'customer_booking_approved' => [
                'label' => 'admin.whatsapp_notify.toggles.customer_booking_approved',
                'group' => 'booking',
                'default' => true,
            ],
            'customer_payment_settled' => [
                'label' => 'admin.whatsapp_notify.toggles.customer_payment_settled',
                'group' => 'booking',
                'default' => true,
            ],
            'customer_booking_rejected_jadwal_full' => [
                'label' => 'admin.whatsapp_notify.toggles.customer_booking_rejected_jadwal_full',
                'group' => 'booking',
                'default' => true,
            ],
            'refund_transfer_proof' => [
                'label' => 'admin.whatsapp_notify.toggles.refund_transfer_proof',
                'group' => 'finance',
                'default' => true,
            ],
            'withdrawal_transfer_proof' => [
                'label' => 'admin.whatsapp_notify.toggles.withdrawal_transfer_proof',
                'group' => 'finance',
                'default' => true,
            ],
            'refund_admin' => [
                'label' => 'admin.whatsapp_notify.toggles.refund_admin',
                'group' => 'admin',
                'default' => true,
            ],
            'muthowif_registration_admin' => [
                'label' => 'admin.whatsapp_notify.toggles.muthowif_registration_admin',
                'group' => 'admin',
                'default' => true,
            ],
            'emergency_admin_report' => [
                'label' => 'admin.whatsapp_notify.toggles.emergency_admin_report',
                'group' => 'emergency',
                'default' => true,
            ],
            'emergency_customer_report' => [
                'label' => 'admin.whatsapp_notify.toggles.emergency_customer_report',
                'group' => 'emergency',
                'default' => true,
            ],
            'emergency_candidate' => [
                'label' => 'admin.whatsapp_notify.toggles.emergency_candidate',
                'group' => 'emergency',
                'default' => true,
            ],
            'emergency_offer' => [
                'label' => 'admin.whatsapp_notify.toggles.emergency_offer',
                'group' => 'emergency',
                'default' => true,
            ],
            'emergency_selection' => [
                'label' => 'admin.whatsapp_notify.toggles.emergency_selection',
                'group' => 'emergency',
                'default' => true,
            ],
            'support_completion_requested' => [
                'label' => 'admin.whatsapp_notify.toggles.support_completion_requested',
                'group' => 'support',
                'default' => true,
            ],
            'support_completion_approved' => [
                'label' => 'admin.whatsapp_notify.toggles.support_completion_approved',
                'group' => 'support',
                'default' => true,
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
            'support' => 'admin.whatsapp_notify.groups.support',
        ];
    }

    public static function token(): ?string
    {
        return self::storedValue(self::SETTING_TOKEN);
    }

    public static function hasToken(): bool
    {
        $token = self::token();

        return is_string($token) && $token !== '';
    }

    public static function apiUrl(): string
    {
        return self::storedValue(self::SETTING_API_URL) ?? self::DEFAULT_API_URL;
    }

    public static function sessionId(): ?string
    {
        $value = self::storedValue(self::SETTING_SESSION_ID);

        return $value !== null && $value !== '' ? $value : null;
    }

    public static function countryCode(): string
    {
        return self::storedValue(self::SETTING_COUNTRY_CODE) ?? self::DEFAULT_COUNTRY_CODE;
    }

    public static function mediaPublicUrl(): ?string
    {
        $value = self::storedValue(self::SETTING_MEDIA_PUBLIC_URL)
            ?? self::storedValue('wa_bulk_gateway_media_public_url');

        return $value !== null && $value !== '' ? $value : null;
    }

    /**
     * @return array{
     *     api_url: string,
     *     session_id: string,
     *     country_code: string,
     *     media_public_url: string,
     *     token_set: bool,
     * }
     */
    public static function gatewayValuesForForm(): array
    {
        return [
            'api_url' => self::apiUrl(),
            'session_id' => self::sessionId() ?? '',
            'country_code' => self::countryCode(),
            'media_public_url' => self::mediaPublicUrl() ?? '',
            'token_set' => self::hasToken(),
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

        return self::toggles()[$key]['default'];
    }

    /**
     * @return list<string>
     */
    public static function adminNumbers(): array
    {
        $stored = SiteSetting::getValue(self::SETTING_ADMIN_NUMBERS);
        if ($stored === null || trim($stored) === '') {
            return [];
        }

        return array_values(array_filter(array_map(
            static fn (string $n): string => trim($n),
            explode(',', $stored),
        ), static fn (string $n): bool => $n !== ''));
    }

    public static function adminNumbersForForm(): string
    {
        return SiteSetting::getValue(self::SETTING_ADMIN_NUMBERS) ?? '';
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
     * @return array{token: string, api_url: string, session_id: ?string, country_code: string}
     */
    public static function gatewayFromInput(array $input): array
    {
        $token = trim((string) ($input['gateway_token'] ?? ''));
        if ($token === '') {
            $token = self::token() ?? '';
        }

        return [
            'token' => $token,
            'api_url' => self::nullableTrimmed($input['gateway_api_url'] ?? null) ?? self::apiUrl(),
            'session_id' => self::nullableTrimmed($input['gateway_session_id'] ?? null) ?? self::sessionId(),
            'country_code' => self::nullableTrimmed($input['gateway_country_code'] ?? null) ?? self::countryCode(),
        ];
    }

    /**
     * @param  array<string, mixed>  $input
     * @return list<string>
     */
    public static function adminNumbersFromInput(array $input): array
    {
        $raw = trim((string) ($input['admin_numbers'] ?? ''));
        if ($raw === '') {
            return self::adminNumbers();
        }

        return array_values(array_filter(array_map(
            static fn (string $n): string => trim($n),
            explode(',', $raw),
        ), static fn (string $n): bool => $n !== ''));
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

        $token = trim((string) ($input['gateway_token'] ?? ''));
        if ($token !== '') {
            SiteSetting::putValue(self::SETTING_TOKEN, $token);
        }

        SiteSetting::putValue(
            self::SETTING_API_URL,
            self::nullableTrimmed($input['gateway_api_url'] ?? null),
        );
        SiteSetting::putValue(
            self::SETTING_SESSION_ID,
            self::nullableTrimmed($input['gateway_session_id'] ?? null),
        );
        SiteSetting::putValue(
            self::SETTING_COUNTRY_CODE,
            self::nullableTrimmed($input['gateway_country_code'] ?? null),
        );
        SiteSetting::putValue(
            self::SETTING_MEDIA_PUBLIC_URL,
            self::nullableTrimmed($input['gateway_media_public_url'] ?? null),
        );
    }

    private static function storedValue(string $settingKey): ?string
    {
        $stored = SiteSetting::getValue($settingKey);
        if ($stored === null || $stored === '') {
            return null;
        }

        return $stored;
    }

    private static function nullableTrimmed(mixed $value): ?string
    {
        if (! is_string($value)) {
            return null;
        }

        $trimmed = trim($value);

        return $trimmed === '' ? null : $trimmed;
    }

    private static function settingKey(string $key): string
    {
        return 'wa_notify_'.$key;
    }
}
