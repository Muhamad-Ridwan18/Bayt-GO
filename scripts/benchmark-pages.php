<?php

$base = $argv[1] ?? 'http://127.0.0.1:8001';

$paths = [
    '/layanan',
    '/layanan/ridwan-hakim',
    '/dashboard',
];

foreach ($paths as $path) {
    $url = rtrim($base, '/').$path;
    $start = microtime(true);
    $ctx = stream_context_create(['http' => ['timeout' => 120, 'ignore_errors' => true]]);
    $body = @file_get_contents($url, false, $ctx);
    $elapsed = round(microtime(true) - $start, 2);
    $status = 'ERR';
    if (isset($http_response_header[0]) && preg_match('/\d{3}/', $http_response_header[0], $m)) {
        $status = $m[0];
    }
    $kb = $body !== false ? round(strlen($body) / 1024, 1) : 0;
    echo "{$path} => {$elapsed}s status={$status} size={$kb}KB\n";
}
