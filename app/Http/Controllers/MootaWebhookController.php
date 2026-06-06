<?php

namespace App\Http\Controllers;

use App\Events\MootaWebhookRecorded;
use App\Support\MootaWebhookBroadcast;
use App\Http\Middleware\AllowMootaWebhookIp;
use App\Models\MootaWebhookHistory;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

final class MootaWebhookController extends Controller
{
    /**
     * Penerima callback mutasi bank dari Moota — simpan tiap webhook ke riwayat.
     *
     * @see https://moota.co/guide/webhook/
     */
    public function __invoke(Request $request): JsonResponse
    {
        $rawBody = $request->getContent();
        $sourceIp = AllowMootaWebhookIp::resolveWebhookSourceIp($request) ?: (string) $request->ip();

        Log::info('moota.webhook.request', [
            'ip' => $sourceIp !== '' ? $sourceIp : null,
            'bytes' => strlen($rawBody),
            'hint' => 'Jika Anda bayar di Moota tapi tidak pernah ada baris ini, Moota tidak meng-POST ke URL webhook Anda (tunnel/URL salah, atau webhook belum dikirim sandbox).',
        ]);

        $parsedPayload = null;
        $parseError = null;
        /** @var array<string, mixed>|null $decoded */
        $decoded = json_decode($rawBody, true, 512);

        if (json_last_error() !== JSON_ERROR_NONE) {
            $parseError = Str::limit(json_last_error_msg(), 2040);
        } elseif (! is_array($decoded)) {
            $parseError = 'Akar JSON harus berupa array atau objek.';
        } else {
            $parsedPayload = $decoded;
        }

        $secret = (string) config('services.moota.signing_secret', '');
        $sigHeaderRaw = $request->header('Signature');

        $signatureVerified = null;
        if ($secret !== '') {
            $provided = is_string($sigHeaderRaw) ? trim($sigHeaderRaw) : '';
            $expected = hash_hmac('sha256', $rawBody, $secret);
            $signatureVerified = hash_equals($expected, $provided);
        }

        $history = MootaWebhookHistory::query()->create([
            'source_ip' => $sourceIp !== '' ? $sourceIp : null,
            'user_agent' => $request->userAgent(),
            'x_moota_user' => $request->header('X-MOOTA-USER'),
            'x_moota_webhook' => $request->header('X-MOOTA-WEBHOOK'),
            'signature_header' => is_string($sigHeaderRaw) ? $sigHeaderRaw : null,
            'signature_verified' => $signatureVerified,
            'payload' => $parsedPayload,
            'payload_raw' => $rawBody,
            'parse_error' => $parseError,
        ]);

        MootaWebhookRecorded::dispatch($history);

        MootaWebhookBroadcast::afterResponse($history);

        Log::info('moota.webhook.stored', [
            'ip' => $sourceIp ?: null,
            'signature_verified' => $signatureVerified,
            'parse_error' => $parseError,
            'bytes' => strlen($rawBody),
        ]);

        if ($secret !== '' && $signatureVerified === false) {
            return response()->json([
                'ok' => false,
                'error' => 'invalid_signature',
            ], Response::HTTP_FORBIDDEN)->header('Cache-Control', 'no-store');
        }

        /*
         * Sukses: HTTP 200 + JSON kecil (bukan body kosong) agar mudah dicek di dashboard Moota / alat debug.
         * Versi sebelumnya memakai 204 No Content — itu juga valid untuk Moota; daftar IP & signature tetap wajib benar.
         */
        return response()->json([
            'ok' => true,
            'received_at' => now()->toIso8601String(),
        ], Response::HTTP_OK)->header('Cache-Control', 'no-store');
    }
}
