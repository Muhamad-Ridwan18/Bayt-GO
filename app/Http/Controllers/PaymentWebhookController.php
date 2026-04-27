<?php

namespace App\Http\Controllers;

use App\Payments\Doku\DokuCheckoutPaymentProvider;
use App\Payments\Midtrans\MidtransSnapPaymentProvider;
use App\Support\PaymentFlowLog;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

class PaymentWebhookController extends Controller
{
    public function handle(Request $request, MidtransSnapPaymentProvider $provider): Response
    {
        return $provider->handleNotification($request);
    }

    public function doku(Request $request, DokuCheckoutPaymentProvider $provider): Response
    {
        PaymentFlowLog::info('http.doku_notification.route', [
            'path' => $request->path(),
            'method' => $request->method(),
        ]);

        return $provider->handleNotification($request);
    }
}
