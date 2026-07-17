<?php

require __DIR__.'/vendor/autoload.php';
$app = require __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

$profile = App\Models\MuthowifProfile::query()->approved()->first();
if (! $profile) {
    echo "NO_PROFILE\n";
    exit(0);
}

$request = Illuminate\Http\Request::create('/layanan/'.$profile->getKey(), 'GET');
$app->instance('request', $request);

try {
    $controller = $app->make(App\Http\Controllers\Public\MuthowifDirectoryController::class);
    $view = $controller->show($request, $profile);
    $html = $view->render();
    echo 'LEN='.strlen($html)."\n";
    echo str_contains($html, __('marketplace.show.addons_section')) ? "ADDONS_SECTION_PRESENT\n" : "ADDONS_SECTION_ABSENT (mungkin profil tanpa add-on)\n";
} catch (Throwable $e) {
    echo 'ERR: '.$e->getMessage().' @ '.$e->getFile().':'.$e->getLine()."\n";
}
