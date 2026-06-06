<?php

/**
 * Laravel → Reverb (HTTP API internal) berbeda dari browser → Reverb (WSS publik).
 *
 * - REVERB_SERVER_HOST=0.0.0.0 = bind listen saja, BUKAN tujuan HTTP broadcast.
 * - REVERB_SCHEME=https = untuk klien (browser), BUKAN untuk API internal Reverb.
 */
$reverbBroadcastHost = env('REVERB_BROADCAST_HOST');
if ($reverbBroadcastHost === null || $reverbBroadcastHost === '') {
    $serverHost = (string) env('REVERB_SERVER_HOST', '127.0.0.1');
    $reverbBroadcastHost = in_array($serverHost, ['0.0.0.0', '::', '::0'], true)
        ? '127.0.0.1'
        : $serverHost;
}

$reverbBroadcastPort = (int) env('REVERB_BROADCAST_PORT', env('REVERB_SERVER_PORT', 8080));
$reverbBroadcastScheme = (string) env('REVERB_BROADCAST_SCHEME', 'http');

return [

    /*
    |--------------------------------------------------------------------------
    | Default Broadcaster
    |--------------------------------------------------------------------------
    |
    | This option controls the default broadcaster that will be used by the
    | framework when an event needs to be broadcast. You may set this to
    | any of the connections defined in the "connections" array below.
    |
    | Supported: "reverb", "pusher", "ably", "redis", "log", "null"
    |
    */

    'default' => env('BROADCAST_CONNECTION', 'null'),

    /*
    |--------------------------------------------------------------------------
    | Broadcast Connections
    |--------------------------------------------------------------------------
    |
    | Here you may define all of the broadcast connections that will be used
    | to broadcast events to other systems or over WebSockets. Samples of
    | each available type of connection are provided inside this array.
    |
    */

    'connections' => [

        'reverb' => [
            'driver' => 'reverb',
            'key' => env('REVERB_APP_KEY'),
            'secret' => env('REVERB_APP_SECRET'),
            'app_id' => env('REVERB_APP_ID'),
            'options' => [
                'host' => $reverbBroadcastHost,
                'port' => $reverbBroadcastPort,
                'scheme' => $reverbBroadcastScheme,
                'useTLS' => $reverbBroadcastScheme === 'https',
            ],
            'client_options' => [
                // Guzzle client options: https://docs.guzzlephp.org/en/stable/request-options.html
            ],
        ],

        'pusher' => [
            'driver' => 'pusher',
            'key' => env('PUSHER_APP_KEY'),
            'secret' => env('PUSHER_APP_SECRET'),
            'app_id' => env('PUSHER_APP_ID'),
            'options' => [
                'cluster' => env('PUSHER_APP_CLUSTER'),
                'host' => env('PUSHER_HOST') ?: 'api-'.env('PUSHER_APP_CLUSTER', 'mt1').'.pusher.com',
                'port' => env('PUSHER_PORT', 443),
                'scheme' => env('PUSHER_SCHEME', 'https'),
                'encrypted' => true,
                'useTLS' => env('PUSHER_SCHEME', 'https') === 'https',
            ],
            'client_options' => [
                // Guzzle client options: https://docs.guzzlephp.org/en/stable/request-options.html
            ],
        ],

        'ably' => [
            'driver' => 'ably',
            'key' => env('ABLY_KEY'),
        ],

        'log' => [
            'driver' => 'log',
        ],

        'null' => [
            'driver' => 'null',
        ],

    ],

];
