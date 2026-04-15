<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Symfony\Component\HttpFoundation\Response;

class SetLocale
{
    private const SUPPORTED = ['en', 'id'];

    public function handle(Request $request, Closure $next): Response
    {
        $locale = $this->resolveLocale($request);

        app()->setLocale($locale);
        Carbon::setLocale($locale);

        return $next($request);
    }

    private function resolveLocale(Request $request): string
    {
        $sessionLocale = $request->session()->get('locale');
        if (is_string($sessionLocale) && in_array($sessionLocale, self::SUPPORTED, true)) {
            return $sessionLocale;
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
