<?php

require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

$user = \App\Models\User::where('role', 'customer')->first();
$request = \Illuminate\Http\Request::create('/chat/conversations', 'GET');
$request->setUserResolver(function () use ($user) { return $user; });

$controller = new \App\Http\Controllers\GlobalChatController();
$response = $controller->index($request);

echo $response->getContent();
