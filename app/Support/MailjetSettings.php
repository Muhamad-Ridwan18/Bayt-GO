<?php

namespace App\Support;

use App\Models\SiteSetting;

class MailjetSettings
{
    public const SETTING_API_KEY = 'mailjet_api_key';

    public const SETTING_SECRET_KEY = 'mailjet_secret_key';

    public const SETTING_FROM_ADDRESS = 'mailjet_from_address';

    public const SETTING_FROM_NAME = 'mailjet_from_name';

    private const DEFAULT_API_URL = 'https://api.mailjet.com/v3.1/send';

    public static function apiKey(): ?string
    {
        return self::storedValue(self::SETTING_API_KEY);
    }

    public static function secretKey(): ?string
    {
        return self::storedValue(self::SETTING_SECRET_KEY);
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
     * @return array{
     *     api_key_set: bool,
     *     secret_key_set: bool,
     *     from_address: string,
     *     from_name: string,
     * }
     */
    public static function valuesForForm(): array
    {
        return [
            'api_key_set' => filled(self::apiKey()),
            'secret_key_set' => filled(self::secretKey()),
            'from_address' => self::fromAddress() ?? '',
            'from_name' => self::fromName() ?? (string) config('app.name', 'BaytGo'),
        ];
    }

    /**
     * @param  array<string, mixed>  $input
     */
    public static function saveFromInput(array $input): void
    {
        $apiKey = trim((string) ($input['api_key'] ?? ''));
        if ($apiKey !== '') {
            SiteSetting::putValue(self::SETTING_API_KEY, $apiKey);
        }

        $secretKey = trim((string) ($input['secret_key'] ?? ''));
        if ($secretKey !== '') {
            SiteSetting::putValue(self::SETTING_SECRET_KEY, $secretKey);
        }

        SiteSetting::putValue(
            self::SETTING_FROM_ADDRESS,
            self::nullableTrimmed($input['from_address'] ?? null),
        );
        SiteSetting::putValue(
            self::SETTING_FROM_NAME,
            self::nullableTrimmed($input['from_name'] ?? null),
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
