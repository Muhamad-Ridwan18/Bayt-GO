<?php

namespace App\Providers;

use App\Payments\Contracts\SnapPaymentProviderInterface;
use App\Payments\Xendit\XenditInvoicePaymentProvider;
use Illuminate\Pagination\Paginator;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->bind(SnapPaymentProviderInterface::class, XenditInvoicePaymentProvider::class);
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Paginator::useBootstrapFive();
    }
}
