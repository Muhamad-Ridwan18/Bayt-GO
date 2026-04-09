<?php

namespace App\Support;

use Illuminate\Http\Request;

final class DokuSignature
{
    /**
     * Base64(SHA-256(body)) untuk header Digest (POST).
     */
    public static function digest(string $rawBody): string
    {
        return base64_encode(hash('sha256', $rawBody, true));
    }

    /**
     * String komponen sebelum HMAC (satu field per baris, tanpa baris ekstra di akhir).
     */
    public static function componentString(
        string $clientId,
        string $requestId,
        string $requestTimestamp,
        string $requestTarget,
        string $digest,
    ): string {
        return "Client-Id:{$clientId}\nRequest-Id:{$requestId}\nRequest-Timestamp:{$requestTimestamp}\nRequest-Target:{$requestTarget}\nDigest:{$digest}";
    }

    public static function sign(string $componentString, string $sharedKey): string
    {
        $raw = hash_hmac('sha256', $componentString, $sharedKey, true);

        return 'HMACSHA256='.base64_encode($raw);
    }

    /**
     * Verifikasi notifikasi HTTP DOKU (non-SNAP): bandingkan header Signature.
     *
     * @param  string  $requestTarget  Path dengan leading slash, mis. /payments/doku/notification
     */
    public static function notificationValid(Request $request, string $sharedKey, string $clientId, string $requestTarget): bool
    {
        $body = $request->getContent();
        $digest = self::digest($body);

        $cid = (string) ($request->header('Client-Id') ?? '');
        $rid = (string) ($request->header('Request-Id') ?? '');
        $ts = (string) ($request->header('Request-Timestamp') ?? '');
        $sigHeader = (string) ($request->header('Signature') ?? '');

        if ($cid === '' || $rid === '' || $ts === '' || $sigHeader === '' || $sharedKey === '' || $clientId === '') {
            return false;
        }

        if (! hash_equals($clientId, $cid)) {
            return false;
        }

        $expected = self::sign(
            self::componentString($cid, $rid, $ts, $requestTarget, $digest),
            $sharedKey,
        );

        return hash_equals($expected, $sigHeader);
    }
}
