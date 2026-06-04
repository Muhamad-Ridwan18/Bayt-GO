<?php

namespace App\Providers;

use App\Events\CustomerBookingUpdated;
use App\Events\MootaWebhookRecorded;
use App\Listeners\NotifyAdminServiceMonitorOnBookingChange;
use App\Listeners\ProcessMootaWebhookForBookingPayments;
use App\Models\BookingPayment;
use App\Models\BookingRefundRequest;
use App\Models\MuthowifWithdrawal;
use App\Payments\Contracts\SnapPaymentProviderInterface;
use App\Payments\Doku\DokuCheckoutPaymentProvider;
use App\Payments\Moota\MootaSnapPaymentProvider;
use App\Services\UploadedImageOptimizer;
use App\Models\Article;
use App\Models\Campaign;
use App\Models\MuthowifPortfolioImage;
use App\Models\MuthowifProfile;
use App\Support\AdminFinanceSummary;
use App\Support\WelcomePageCache;
use App\Support\MuthowifFinanceSummary;
use Composer\Autoload\ClassLoader;
use Illuminate\Pagination\Paginator;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        // Manual Autoloader Registration Fallback for maestroerror/php-heic-to-jpg
        if (file_exists(base_path('vendor/autoload.php'))) {
            $loader = require base_path('vendor/autoload.php');
            if ($loader instanceof ClassLoader) {
                $loader->setPsr4('Maestroerror\\', base_path('vendor/maestroerror/php-heic-to-jpg/src'));
                $loader->setPsr4('Maestroerror\\HeifConverter\\', base_path('vendor/maestroerror/heif-converter/src'));
            }
        }

        $this->app->singleton(UploadedImageOptimizer::class);

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
        Gate::define('viewLogViewer', fn ($user) => $user->isAdmin());

        $this->app->booted(function () {
            config([
                'log-viewer.back_to_system_url' => url('/admin/pengaturan'),
            ]);
        });

        Event::listen(MootaWebhookRecorded::class, ProcessMootaWebhookForBookingPayments::class);
        Event::listen(CustomerBookingUpdated::class, NotifyAdminServiceMonitorOnBookingChange::class);
        Paginator::useTailwind();

        foreach ([MuthowifProfile::class, Campaign::class, Article::class, MuthowifPortfolioImage::class] as $model) {
            $model::saved(fn () => WelcomePageCache::forget());
            $model::deleted(fn () => WelcomePageCache::forget());
        }

        // Automatic Cache Invalidation on Mutation for Finance Dashboards
        BookingPayment::saved(function ($payment) {
            AdminFinanceSummary::clearCache();
            if ($payment->muthowifBooking?->muthowif_profile_id) {
                MuthowifFinanceSummary::clearCache($payment->muthowifBooking->muthowif_profile_id);
            }
        });
        BookingPayment::deleted(function ($payment) {
            AdminFinanceSummary::clearCache();
            if ($payment->muthowifBooking?->muthowif_profile_id) {
                MuthowifFinanceSummary::clearCache($payment->muthowifBooking->muthowif_profile_id);
            }
        });
        BookingRefundRequest::saved(function ($refund) {
            AdminFinanceSummary::clearCache();
            if ($refund->muthowifBooking?->muthowif_profile_id) {
                MuthowifFinanceSummary::clearCache($refund->muthowifBooking->muthowif_profile_id);
            }
        });
        BookingRefundRequest::deleted(function ($refund) {
            AdminFinanceSummary::clearCache();
            if ($refund->muthowifBooking?->muthowif_profile_id) {
                MuthowifFinanceSummary::clearCache($refund->muthowifBooking->muthowif_profile_id);
            }
        });
        MuthowifWithdrawal::saved(function ($withdrawal) {
            AdminFinanceSummary::clearCache();
            if ($withdrawal->muthowif_profile_id) {
                MuthowifFinanceSummary::clearCache($withdrawal->muthowif_profile_id);
            }
        });
        MuthowifWithdrawal::deleted(function ($withdrawal) {
            AdminFinanceSummary::clearCache();
            if ($withdrawal->muthowif_profile_id) {
                MuthowifFinanceSummary::clearCache($withdrawal->muthowif_profile_id);
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
