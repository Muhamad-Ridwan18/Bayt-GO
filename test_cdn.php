<?php
// Save all 3 files locally and grep for export global names
$files = [
    'header' => 'https://cdn.jsdelivr.net/npm/@editorjs/header@latest',
    'image'  => 'https://cdn.jsdelivr.net/npm/@editorjs/image@latest',
    'list'   => 'https://cdn.jsdelivr.net/npm/@editorjs/list@latest',
];
foreach ($files as $name => $url) {
    $c = file_get_contents($url);
    file_put_contents("test_$name.js", $c);
    // Find all alphanumeric quoted strings in last 2000 chars (where UMD global registration usually is)
    $tail = substr($c, -2000);
    preg_match_all('/["\'](([A-Z][a-zA-Z0-9]+))["\']\s*=/', $tail, $m);
    echo "$name -> " . implode(', ', array_unique($m[1] ?? [])) . "\n";
}
