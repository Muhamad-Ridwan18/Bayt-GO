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

            return $this->forbidden('Daftar IP webhook Moota kosong di config/services.php.');
        }

        $resolved = self::resolveWebhookSourceIp($request);

        if (! is_string($resolved) || ! in_array($resolved, $allowed, true)) {
            Log::warning('moota.webhook.ip_rejected', [
                'resolved_ip' => $resolved,
                'allowed_ips' => $allowed,
                'laravel_ip' => $request->ip(),
                'remote_addr' => $request->server('REMOTE_ADDR'),
                'path' => $request->path(),
            ]);

            $hint = config('app.debug')
                ? 'IP terdeteksi: '.($resolved ?? 'tidak diketahui').'. Tambahkan ke services.moota.webhook_ips di config/services.php.'
                : 'Forbidden';

            return $this->forbidden($hint);
        }

        Log::info('moota.webhook.ip_allowed', [
            'resolved_ip' => $resolved,
        ]);

        return $next($request);
    }

    /**
     * IP peer untuk whitelist. Pakai $request->ip() (menghormati TRUSTED_PROXIES),
     * bukan header CF/XFF mentah — header itu mudah di-spoof bila trustProxies='*'.
     * Auth utama webhook: HMAC Signature (wajib di non-local).
     */
    public static function resolveWebhookSourceIp(Request $request): ?string
    {
        $ip = $request->ip();

        return is_string($ip) && $ip !== '' && filter_var($ip, FILTER_VALIDATE_IP) !== false
            ? $ip
            : null;
    }

    private function forbidden(string $message): Response
    {
        return response()->json(['message' => $message], Response::HTTP_FORBIDDEN);
    }
}
