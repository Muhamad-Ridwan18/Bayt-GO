<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureVerifiedMuthowif
{
    /**
     * @param  Closure(Request): (Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();
        if (! $user || ! $user->isVerifiedMuthowif()) {
            return redirect()
                ->route('dashboard')
                ->with('error', 'Fitur ini hanya untuk muthowif yang sudah terverifikasi.');
        }

        return $next($request);
    }
}
