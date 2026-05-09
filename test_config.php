<?php
require 'vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

$c = $app->make(\App\Http\Controllers\Admin\ArticlesAdminController::class);
$r = new ReflectionMethod($c, 'articleEditorConfig');
$r->setAccessible(true);
$config = $r->invoke($c, new \App\Models\Article());
echo json_encode($config, JSON_PRETTY_PRINT);
