<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Symfony\Component\HttpFoundation\Response;

class SetLocaleApi
{
    private const SUPPORTED = ['en', 'id', 'ar'];

    public function handle(Request $request, Closure $next): Response
    {
        $locale = $this->resolveLocale($request);

        app()->setLocale($locale);
        Carbon::setLocale($locale);

        return $next($request);
    }

    private function resolveLocale(Request $request): string
    {
        $headerLocale = $request->header('X-Locale');

        if (! is_string($headerLocale) || trim($headerLocale) === '') {
            $acceptLanguage = $request->header('Accept-Language');
            if (is_string($acceptLanguage) && $acceptLanguage !== '') {
                // Example: "en-US,en;q=0.9,id;q=0.8" → take the first lang tag
                $first = explode(',', $acceptLanguage)[0] ?? '';
                $headerLocale = trim($first);
            }
        }

        if (is_string($headerLocale) && $headerLocale !== '') {
            $normalized = strtolower(explode('-', $headerLocale)[0] ?? '');
            if (in_array($normalized, self::SUPPORTED, true)) {
                return $normalized;
            }
        }

        $user = $request->user();
        if ($user !== null && is_string($user->locale) && in_array($user->locale, self::SUPPORTED, true)) {
            return $user->locale;
        }

        $fallback = config('app.locale');
        if (is_string($fallback) && in_array($fallback, self::SUPPORTED, true)) {
            return $fallback;
        }

        return 'en';
    }
}

