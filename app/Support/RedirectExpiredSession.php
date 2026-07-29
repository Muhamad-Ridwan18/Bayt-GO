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

    public static function respond(Request $request): RedirectResponse|JsonResponse
    {
        if ($request->expectsJson()) {
            return response()->json([
                'message' => 'Sesi Anda telah berakhir. Silakan masuk kembali.',
                'redirect' => self::loginRoute(),
            ], 401);
        }

        return redirect()->guest(self::loginRoute());
    }
}
