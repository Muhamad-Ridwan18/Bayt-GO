<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Crypt;
use Throwable;

class SiteSetting extends Model
{
    protected $fillable = [
        'key',
        'value',
    ];

    public static function getValue(string $key, ?string $default = null): ?string
    {
        $row = static::query()->where('key', $key)->first();

        return $row?->value ?? $default;
    }

    public static function putValue(string $key, ?string $value): void
    {
        static::query()->updateOrCreate(
            ['key' => $key],
            ['value' => $value],
        );
    }

    /** Baca secret: decrypt; fallback plaintext legacy. */
    public static function getSecret(string $key): ?string
    {
        $raw = self::getValue($key);
        if ($raw === null || $raw === '') {
            return null;
        }

        try {
            $plain = Crypt::decryptString($raw);
        } catch (Throwable) {
            $plain = $raw;
        }

        $plain = trim($plain);

        return $plain === '' ? null : $plain;
    }

    public static function putSecret(string $key, ?string $plain): void
    {
        if ($plain === null || trim($plain) === '') {
            self::putValue($key, null);

            return;
        }

        self::putValue($key, Crypt::encryptString(trim($plain)));
    }
}
