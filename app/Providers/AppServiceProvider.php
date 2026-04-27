<?php

namespace App\Providers;

use App\Payments\Contracts\SnapPaymentProviderInterface;
use App\Payments\Doku\DokuCheckoutPaymentProvider;
use Illuminate\Pagination\Paginator;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->bind(SnapPaymentProviderInterface::class, DokuCheckoutPaymentProvider::class);
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Paginator::useBootstrapFive();

        /*
         * Tanpa ini, route(..., absolute: true) memakai host permintaan saat ini ($request->root()).
         * Dev sering buka http://127.0.0.1:8000 sementara DOKU harus memanggil URL publik di APP_URL
         * (mis. Cloudflare Tunnel). Paksa root + skema dari APP_URL.
         */
        $root = rtrim((string) config('app.url'), '/');
        if ($root !== '') {
            URL::forceRootUrl($root);
            if (str_starts_with($root, 'https://')) {
                URL::forceScheme('https');
            } elseif (str_starts_with($root, 'http://')) {
                URL::forceScheme('http');
            }
        }
    }
}
