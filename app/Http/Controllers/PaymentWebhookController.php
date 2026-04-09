<?php

namespace App\Http\Controllers;

use App\Payments\Contracts\SnapPaymentProviderInterface;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

class PaymentWebhookController extends Controller
{
    public function handle(Request $request, SnapPaymentProviderInterface $provider): Response
    {
        return $provider->handleNotification($request);
    }
}
