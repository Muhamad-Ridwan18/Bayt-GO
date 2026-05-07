<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\Response;

final class AllowMootaWebhookIp
{
    public function handle(Request $request, Closure $next): Response
    {
        /** @var list<string> */
        $allowed = config('services.moota.webhook_ips', []);

        if ($allowed === []) {
            Log::warning('moota.webhook.abort_no_ips_configured');

            abort(Response::HTTP_FORBIDDEN, 'Forbidden');
        }

        $resolved = self::resolveWebhookSourceIp($request);

        if (! is_string($resolved) || ! in_array($resolved, $allowed, true)) {
            Log::warning('moota.webhook.ip_rejected', [
                'resolved_ip' => $resolved,
                'laravel_ip' => $request->ip(),
                'cf_connecting_ip' => $request->headers->get('CF-Connecting-IP'),
                'x_forwarded_for' => $request->headers->get('X-Forwarded-For'),
                'remote_addr' => $request->server('REMOTE_ADDR'),
                'path' => $request->path(),
            ]);

            abort(Response::HTTP_FORBIDDEN, 'Forbidden');
        }

        Log::info('moota.webhook.ip_allowed', [
            'resolved_ip' => $resolved,
        ]);

        return $next($request);
    }

    /** IP yang dipakai untuk whitelist audit (sama logika penyimpanan history). */
    public static function resolveWebhookSourceIp(Request $request): ?string
    {
        foreach (['CF-Connecting-IP', 'True-Client-IP'] as $header) {
            $raw = $request->headers->get($header);
            if (! is_string($raw) || $raw === '') {
                continue;
            }
            $first = trim(explode(',', $raw, 2)[0]);
            if ($first !== '' && filter_var($first, FILTER_VALIDATE_IP) !== false) {
                return $first;
            }
        }

        $xff = $request->headers->get('X-Forwarded-For');
        if (is_string($xff) && $xff !== '') {
            $first = trim(explode(',', $xff, 2)[0]);
            if ($first !== '' && filter_var($first, FILTER_VALIDATE_IP) !== false) {
                return $first;
            }
        }

        $ip = $request->ip();

        return is_string($ip) && $ip !== '' ? $ip : null;
    }
}
