<?php

namespace App\Services;

use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use RuntimeException;

class FonnteService
{
    public function sendText(string $target, string $message): void
    {
        $token = config('services.fonnte.token');
        if ($token === null || $token === '') {
            throw new RuntimeException('FONNTE_TOKEN belum diatur.');
        }

        $url = config('services.fonnte.url', 'https://api.fonnte.com/send');

        try {
            /** @var Response $response */
            $response = Http::timeout(30)
                ->withHeaders([
                    'Authorization' => $token,
                ])
                ->asForm()
                ->post($url, [
                    'target' => $target,
                    'message' => $message,
                    'countryCode' => config('services.fonnte.country_code', '62'),
                ]);
        } catch (ConnectionException $e) {
            Log::warning('Fonnte connection failed', ['exception' => $e->getMessage()]);
            throw new RuntimeException('Tidak dapat terhubung ke layanan WhatsApp. Coba lagi.');
        }

        if (! $response->successful()) {
            Log::warning('Fonnte HTTP error', ['status' => $response->status(), 'body' => $response->body()]);
            throw new RuntimeException('Gagal menghubungi layanan WhatsApp.');
        }

        $data = $response->json();
        $ok = $data['status'] ?? $data['Status'] ?? false;

        if ($ok !== true) {
            $reason = $data['reason'] ?? $data['Reason'] ?? 'Tidak diketahui';
            Log::warning('Fonnte send failed', ['reason' => $reason, 'body' => $data]);
            throw new RuntimeException('WhatsApp: '.$reason);
        }
    }
}
