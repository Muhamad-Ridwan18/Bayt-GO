<?php

namespace App\Support;

use App\Models\SiteSetting;

class MailjetSettings
{
    public const SETTING_API_KEY = 'mailjet_api_key';

    public const SETTING_SECRET_KEY = 'mailjet_secret_key';

    public const SETTING_FROM_ADDRESS = 'mailjet_from_address';

    public const SETTING_FROM_NAME = 'mailjet_from_name';

    public const SETTING_ADMIN_EMAILS = 'mailjet_admin_emails';

    private const DEFAULT_API_URL = 'https://api.mailjet.com/v3.1/send';

    public static function apiKey(): ?string
    {
        return SiteSetting::getSecret(self::SETTING_API_KEY);
    }

    public static function secretKey(): ?string
    {
        return SiteSetting::getSecret(self::SETTING_SECRET_KEY);
    }

    public static function fromAddress(): ?string
    {
        return self::storedValue(self::SETTING_FROM_ADDRESS);
    }

    public static function fromName(): ?string
    {
        return self::storedValue(self::SETTING_FROM_NAME)
            ?? config('app.name', 'BaytGo');
    }

    public static function apiUrl(): string
    {
        return self::DEFAULT_API_URL;
    }

    public static function hasCredentials(): bool
    {
        $apiKey = self::apiKey();
        $secretKey = self::secretKey();

        return is_string($apiKey) && $apiKey !== ''
            && is_string($secretKey) && $secretKey !== '';
    }

    /**
     * @return list<string>
     */
    public static function adminEmails(): array
    {
        $stored = SiteSetting::getValue(self::SETTING_ADMIN_EMAILS);
        if ($stored === null || trim($stored) === '') {
            return [];
        }

        return array_values(array_filter(array_map(
            static function (string $email): string {
                return strtolower(trim($email));
            },
            explode(',', $stored),
        ), static function (string $email): bool {
            return $email !== '' && filter_var($email, FILTER_VALIDATE_EMAIL);
        }));
    }

    public static function adminEmailsForForm(): string
    {
        return SiteSetting::getValue(self::SETTING_ADMIN_EMAILS) ?? '';
    }

    /**
     * @return array{
     *     api_key_set: bool,
     *     secret_key_set: bool,
     *     from_address: string,
     *     from_name: string,
     *     admin_emails: string,
     * }
     */
    public static function valuesForForm(): array
    {
        return [
            'api_key_set' => filled(self::apiKey()),
            'secret_key_set' => filled(self::secretKey()),
            'from_address' => self::fromAddress() ?? '',
            'from_name' => self::fromName() ?? (string) config('app.name', 'BaytGo'),
            'admin_emails' => self::adminEmailsForForm(),
        ];
    }

    /**
     * @param  array<string, mixed>  $input
     */
    public static function saveFromInput(array $input): void
    {
        $apiKey = trim((string) ($input['api_key'] ?? ''));
        if ($apiKey !== '') {
            SiteSetting::putSecret(self::SETTING_API_KEY, $apiKey);
        }

        $secretKey = trim((string) ($input['secret_key'] ?? ''));
        if ($secretKey !== '') {
            SiteSetting::putSecret(self::SETTING_SECRET_KEY, $secretKey);
        }

        SiteSetting::putValue(
            self::SETTING_FROM_ADDRESS,
            self::nullableTrimmed($input['from_address'] ?? null),
        );
        SiteSetting::putValue(
            self::SETTING_FROM_NAME,
            self::nullableTrimmed($input['from_name'] ?? null),
        );

        $adminEmails = trim((string) ($input['admin_emails'] ?? ''));
        SiteSetting::putValue(
            self::SETTING_ADMIN_EMAILS,
            $adminEmails === '' ? null : $adminEmails,
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
}
