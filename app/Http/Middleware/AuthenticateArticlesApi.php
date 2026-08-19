<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

final class AuthenticateArticlesApi
{
    public function handle(Request $request, Closure $next): Response
    {
        $expected = trim((string) config('services.articles_api.token', ''));

        if ($expected === '') {
            return response()->json([
                'message' => 'Articles API token is not configured.',
            ], Response::HTTP_SERVICE_UNAVAILABLE);
        }

        $provided = $this->providedToken($request);

        if ($provided === '' || ! hash_equals($expected, $provided)) {
            return response()->json([
                'message' => 'Unauthenticated.',
            ], Response::HTTP_UNAUTHORIZED);
        }

        return $next($request);
    }

    private function providedToken(Request $request): string
    {
        $bearer = trim((string) $request->bearerToken());
        if ($bearer !== '') {
            return $bearer;
        }

        return trim((string) $request->header('X-Articles-Api-Token', ''));
    }
}
