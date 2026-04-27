<?php

namespace App\Services\Doku;

use Illuminate\Http\Request;

final class DokuSignature
{
    public static function digestBody(string $rawBody): string
    {
        return base64_encode(hash('sha256', $rawBody, true));
    }

    public static function signRequest(
        string $clientId,
        string $requestId,
        string $requestTimestampUtc,
        string $requestTarget,
        string $digestBase64,
        string $secretKey,
    ): string {
        $component = "Client-Id:{$clientId}\nRequest-Id:{$requestId}\nRequest-Timestamp:{$requestTimestampUtc}\nRequest-Target:{$requestTarget}\nDigest:{$digestBase64}";
        $hmac = base64_encode(hash_hmac('sha256', $component, $secretKey, true));

        return 'HMACSHA256='.$hmac;
    }

    /**
     * Verify DOKU HTTP notification (non-SNAP / Jokul header signature).
     *
     * @param  non-empty-string  $notificationRequestTarget  Path only, e.g. /payments/doku/notification
     */
    public static function notificationIsValid(
        Request $request,
        string $secretKey,
        string $notificationRequestTarget,
    ): bool {
        $clientId = (string) $request->header('Client-Id', '');
        $requestId = (string) $request->header('Request-Id', '');
        $timestamp = (string) $request->header('Request-Timestamp', '');
        $signatureHeader = (string) $request->header('Signature', '');
        if ($clientId === '' || $requestId === '' || $timestamp === '' || $signatureHeader === '') {
            return false;
        }

        $raw = $request->getContent();
        $digest = self::digestBody($raw);
        $expected = self::signRequest($clientId, $requestId, $timestamp, $notificationRequestTarget, $digest, $secretKey);

        return hash_equals($expected, $signatureHeader);
    }
}
