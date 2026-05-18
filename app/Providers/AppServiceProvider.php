<?php

namespace App\Providers;

use App\Events\MootaWebhookRecorded;
use App\Listeners\ProcessMootaWebhookForBookingPayments;
use App\Payments\Contracts\SnapPaymentProviderInterface;
use App\Payments\Doku\DokuCheckoutPaymentProvider;
use App\Payments\Moota\MootaSnapPaymentProvider;
use Illuminate\Pagination\Paginator;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->bind(SnapPaymentProviderInterface::class, function ($app): SnapPaymentProviderInterface {
            return match (config('services.booking.payment_driver', 'doku')) {
                'moota' => $app->make(MootaSnapPaymentProvider::class),
                default => $app->make(DokuCheckoutPaymentProvider::class),
            };
        });
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Event::listen(MootaWebhookRecorded::class, ProcessMootaWebhookForBookingPayments::class);
        Paginator::useTailwind();

        // Automatic Cache Invalidation on Mutation for Finance Dashboards
        \App\Models\BookingPayment::saved(function ($payment) {
            \App\Support\AdminFinanceSummary::clearCache();
            if ($payment->muthowifBooking?->muthowif_profile_id) {
                \App\Support\MuthowifFinanceSummary::clearCache($payment->muthowifBooking->muthowif_profile_id);
            }
        });
        \App\Models\BookingPayment::deleted(function ($payment) {
            \App\Support\AdminFinanceSummary::clearCache();
            if ($payment->muthowifBooking?->muthowif_profile_id) {
                \App\Support\MuthowifFinanceSummary::clearCache($payment->muthowifBooking->muthowif_profile_id);
            }
        });
        \App\Models\BookingRefundRequest::saved(function ($refund) {
            \App\Support\AdminFinanceSummary::clearCache();
            if ($refund->muthowifBooking?->muthowif_profile_id) {
                \App\Support\MuthowifFinanceSummary::clearCache($refund->muthowifBooking->muthowif_profile_id);
            }
        });
        \App\Models\BookingRefundRequest::deleted(function ($refund) {
            \App\Support\AdminFinanceSummary::clearCache();
            if ($refund->muthowifBooking?->muthowif_profile_id) {
                \App\Support\MuthowifFinanceSummary::clearCache($refund->muthowifBooking->muthowif_profile_id);
            }
        });
        \App\Models\MuthowifWithdrawal::saved(function ($withdrawal) {
            \App\Support\AdminFinanceSummary::clearCache();
            if ($withdrawal->muthowif_profile_id) {
                \App\Support\MuthowifFinanceSummary::clearCache($withdrawal->muthowif_profile_id);
            }
        });
        \App\Models\MuthowifWithdrawal::deleted(function ($withdrawal) {
            \App\Support\AdminFinanceSummary::clearCache();
            if ($withdrawal->muthowif_profile_id) {
                \App\Support\MuthowifFinanceSummary::clearCache($withdrawal->muthowif_profile_id);
            }
        });

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
