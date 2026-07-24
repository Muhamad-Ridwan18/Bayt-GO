<?php

namespace App\Support;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cookie;

final class AffiliateReferralCapture
{
    public const SESSION_KEY = 'affiliate_ref';

    public const COOKIE_NAME = 'affiliate_ref';

    public const CLICK_SESSION_PREFIX = 'affiliate_click_recorded:';

    /** Cookie lifetime in minutes (30 days). */
    public const COOKIE_MINUTES = 60 * 24 * 30;

    public static function code(?Request $request = null): ?string
    {
        $request ??= request();

        $fromSession = $request->session()->get(self::SESSION_KEY);
        if (is_string($fromSession) && $fromSession !== '') {
            return strtoupper($fromSession);
        }

        $fromCookie = $request->cookie(self::COOKIE_NAME);
        if (is_string($fromCookie) && $fromCookie !== '') {
            return strtoupper($fromCookie);
        }

        return null;
    }

    public static function remember(Request $request, string $code): void
    {
        $code = strtoupper($code);
        $request->session()->put(self::SESSION_KEY, $code);
        Cookie::queue(cookie(
            self::COOKIE_NAME,
            $code,
            self::COOKIE_MINUTES,
            '/',
            null,
            $request->isSecure(),
            true,
            false,
            'lax',
        ));
    }

    public static function clear(Request $request): void
    {
        $request->session()->forget(self::SESSION_KEY);
        Cookie::queue(Cookie::forget(self::COOKIE_NAME));
    }

    /**
     * Resolve affiliate code for a booking create.
     * - Field present in request (even empty): honor that value only — do not fall back to cookie.
     * - Field absent (e.g. API/deep-link flow): fall back to session/cookie from ?ref= capture.
     */
    public static function resolveForBooking(Request $request, ?string $explicitCode): ?string
    {
        if ($request->exists('affiliate_code')) {
            return filled($explicitCode) ? (string) $explicitCode : null;
        }

        if (filled($explicitCode)) {
            return (string) $explicitCode;
        }

        return self::code($request);
    }
}
