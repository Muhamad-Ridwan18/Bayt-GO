<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Symfony\Component\HttpFoundation\Response;

/**
 * Mengunci bahasa berdasarkan URL, bukan session. Dipakai pada halaman yang punya
 * URL terpisah per bahasa agar satu URL selalu menyajikan satu bahasa.
 */
class ForceLocale
{
    public function handle(Request $request, Closure $next, string $locale): Response
    {
        app()->setLocale($locale);
        Carbon::setLocale($locale);

        return $next($request);
    }
}
