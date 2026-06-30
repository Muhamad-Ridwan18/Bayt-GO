<?php

use App\Http\Middleware\AllowMootaWebhookIp;
use App\Http\Middleware\EnsureCustomerOrMuthowif;
use App\Http\Middleware\EnsureUserRole;
use App\Http\Middleware\EnsureVerifiedMuthowif;
use App\Http\Middleware\SetLocale;
use Illuminate\Broadcasting\BroadcastException;
use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Http\Exceptions\PostTooLargeException;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        channels: __DIR__.'/../routes/channels.php',
        health: '/up',
    )
    ->withSchedule(function (Schedule $schedule): void {
        $schedule->command('bookings:process-timeouts')->everyFiveMinutes();
        $schedule->command('bookings:auto-complete-service')->everyMinute();
        $schedule->command('bookings:process-support-lifecycle')->everyMinute();
    })
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->trustProxies(
            at: '*',
            headers: Request::HEADER_X_FORWARDED_FOR
                | Request::HEADER_X_FORWARDED_HOST
                | Request::HEADER_X_FORWARDED_PORT
                | Request::HEADER_X_FORWARDED_PROTO
                | Request::HEADER_X_FORWARDED_PREFIX,
        );

        $middleware->web(append: [
            SetLocale::class,
        ]);
        $middleware->redirectGuestsTo(fn () => route('login'));
        $middleware->validateCsrfTokens(except: [
            'payments/midtrans/notification',
            'payments/doku/notification',
            'webhooks/moota',
        ]);
        $middleware->alias([
            'moota.ip' => AllowMootaWebhookIp::class,
            'role' => EnsureUserRole::class,
            'reporter' => EnsureCustomerOrMuthowif::class,
            'verified.muthowif' => EnsureVerifiedMuthowif::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->dontReport([
            BroadcastException::class,
        ]);

        $exceptions->reportable(function (BroadcastException $e): bool {
            Log::warning('broadcast.skipped', [
                'message' => Str::limit($e->getMessage(), 160),
                'hint' => 'Pastikan `php artisan reverb:start` berjalan dan REVERB_SERVER_HOST/REVERB_SERVER_PORT mengarah ke server Reverb (bukan URL situs Laravel).',
            ]);

            return false;
        });

        $exceptions->renderable(function (PostTooLargeException $e, $request) {
            if ($request->expectsJson()) {
                return response()->json([
                    'message' => __('bookings.validation.document_upload_failed'),
                ], 413);
            }

            return redirect()->back()
                ->withInput()
                ->withErrors(['ticket_outbound' => __('bookings.validation.document_upload_failed')]);
        });
    })->create();
