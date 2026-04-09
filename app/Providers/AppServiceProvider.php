<?php

namespace App\Providers;

use App\Payments\Contracts\SnapPaymentProviderInterface;
use App\Payments\Midtrans\MidtransSnapPaymentProvider;
use Illuminate\Pagination\Paginator;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->bind(SnapPaymentProviderInterface::class, MidtransSnapPaymentProvider::class);
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Paginator::useBootstrapFive();
    }
}
