<?php

namespace App\Support;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

final class RedirectExpiredSession
{
    public static function loginRoute(): string
    {
        return route('login');
    }

    public static function homeRoute(): string
    {
        return route('welcome');
    }

    public static function respond(Request $request): RedirectResponse|JsonResponse
    {
        return self::redirect($request, self::loginRoute(), 401);
    }

    public static function respondForbidden(Request $request): RedirectResponse|JsonResponse
    {
        $target = $request->user() === null
            ? self::loginRoute()
            : self::homeRoute();

        $status = $request->user() === null ? 401 : 403;

        return self::redirect($request, $target, $status);
    }

    private static function redirect(Request $request, string $target, int $jsonStatus): RedirectResponse|JsonResponse
    {
        if ($request->expectsJson()) {
            return response()->json([
                'message' => 'Sesi Anda telah berakhir. Silakan masuk kembali.',
                'redirect' => $target,
            ], $jsonStatus);
        }

        if ($target === self::loginRoute()) {
            return redirect()->guest($target);
        }

        return redirect($target);
    }
}
