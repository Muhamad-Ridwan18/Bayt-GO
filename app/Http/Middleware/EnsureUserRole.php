<?php

namespace App\Http\Middleware;

use App\Enums\UserRole;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureUserRole
{
    /**
     * @param  Closure(Request): (Response)  $next
     * @param  string  ...$roles  customer|muthowif|admin
     */
    public function handle(Request $request, Closure $next, string ...$roles): Response
    {
        $user = $request->user();
        if (! $user) {
            abort(403);
        }

        $map = [
            'customer' => UserRole::Customer,
            'muthowif' => UserRole::Muthowif,
            'admin' => UserRole::Admin,
        ];

        $allowed = collect($roles)
            ->map(fn (string $r) => $map[$r] ?? null)
            ->filter();

        if ($allowed->contains($user->role)) {
            return $next($request);
        }

        abort(403);
    }
}
