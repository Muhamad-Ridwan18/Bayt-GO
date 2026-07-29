<?php

namespace App\Http\Middleware;

use App\Support\RedirectExpiredSession;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureCustomerOrMuthowif
{
    /**
     * @param  Closure(Request): (Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();
        if ($user === null) {
            return RedirectExpiredSession::respond($request);
        }

        if ($user->isCustomer() || $user->isMuthowif()) {
            return $next($request);
        }

        return RedirectExpiredSession::respondForbidden($request);
    }
}
