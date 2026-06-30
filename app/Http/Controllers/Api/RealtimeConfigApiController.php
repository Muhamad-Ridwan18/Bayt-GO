<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class RealtimeConfigApiController extends Controller
{
    public function show(Request $request): JsonResponse
    {
        $driver = (string) config('broadcasting.default', 'null');
        $key = (string) config('broadcasting.connections.reverb.key', '');

        if ($driver === 'null' || $key === '') {
            return response()->json(['enabled' => false]);
        }

        $host = (string) env('REVERB_HOST', '127.0.0.1');
        $port = (int) env('REVERB_PORT', 8080);
        $scheme = (string) env('REVERB_SCHEME', 'http');

        return response()->json([
            'enabled' => true,
            'key' => $key,
            'host' => $host,
            'port' => $port,
            'scheme' => $scheme,
            'auth_endpoint' => url('/api/broadcasting/auth'),
            'user_id' => (string) $request->user()->getKey(),
        ]);
    }
}
